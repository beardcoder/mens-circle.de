# ============================================
# Stage 1: PHP Dependencies (Composer)
# ============================================
FROM dunglas/frankenphp:1-php8.5 AS base

# Install system dependencies and configure in single layer
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    wget \
    locales \
    unzip \
    supervisor \
    zlib1g-dev \
    libpng-dev \
    libjpeg62-turbo \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    ghostscript \
    imagemagick \
    graphicsmagick \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-libdir=/usr/include/ --with-jpeg --with-freetype --with-webp \
    && sed -i '/en_US.UTF-8/s/^# //g' /etc/locale.gen \
    && sed -i '/de_DE.UTF-8/s/^# //g' /etc/locale.gen \
    && locale-gen \
    && rm -rf /var/lib/apt/lists/*

# Set locale environment
ENV LC_ALL=de_DE.UTF-8 \
    LANG=de_DE.UTF-8 \
    LANGUAGE=de_DE:de

# Install PHP extensions
RUN set -eux; \
    install-php-extensions \
    @composer \
    exif \
    gd \
    memcached \
    intl \
    pdo_mysql \
    zip \
    redis

COPY . /var/www/html
WORKDIR /var/www/html

FROM base AS composer-builder

# Install production dependencies only
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# ============================================
# Stage 2: Frontend Build (Bun/Vite)
# ============================================
FROM oven/bun:1-alpine AS frontend-builder

WORKDIR /app

# Copy composer dependencies required by vite-plugin-typo3
COPY --from=composer-builder /var/www/html/vendor ./vendor
COPY --from=base /var/www/html/composer.json ./composer.json

# Copy package files and install dependencies
COPY package.json bun.lock ./
COPY packages ./packages
RUN bun install --frozen-lockfile

# Copy build configuration and source files
COPY vite.config.ts tsconfig.json* ./
COPY .prettierrc* .stylelintrc* ./
COPY config ./config
COPY public ./public

# Build frontend assets
RUN bun run build

# ============================================
# Stage 3: Final Production Image (FrankenPHP)
# ============================================
FROM composer-builder

# Set working directory
WORKDIR /var/www/html

# Configure FrankenPHP environment
ENV FRANKENPHP_CONFIG="" \
    SERVER_NAME=":80"

# Copy configuration files
COPY .docker/php/typo3.ini /usr/local/etc/php/conf.d/typo3.ini
COPY .docker/frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY .docker/imagemagick-policy.xml /etc/ImageMagick-7/policy.xml
COPY .docker/supervisor/supervisord.conf /etc/supervisord.conf

# Create required directories
RUN mkdir -p /var/www/html/var/cache /var/log/supervisor

# Copy build artifacts from previous stages
COPY --from=frontend-builder --chown=www-data:www-data /app/public/_assets ./public/_assets

RUN chown -R www-data:www-data /var/www/html

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost/typo3/login || exit 1

# Expose ports
EXPOSE 80 443

# Start FrankenPHP + queue worker via Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
