# ============================================
# Stage 1: PHP Dependencies (Composer)
# ============================================
FROM composer AS composer-builder

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./
COPY packages ./packages

# Install production dependencies only (no dev)
# Ignore platform requirements as composer:2 image doesn't have all PHP extensions
# The final PHP 8.5 image will have all required extensions
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# Copy application code for post-install scripts
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

# ============================================
# Stage 2: Frontend Build (Bun/Vite)
# Requires Composer dependencies from Stage 1
# ============================================
FROM oven/bun:1-alpine AS frontend-builder

WORKDIR /app

# Copy composer dependencies (needed by frontend build)
COPY --from=composer-builder /app/vendor ./vendor

# Copy composer.json (required by vite-plugin-typo3)
COPY --from=composer-builder /app/composer.json ./composer.json

# Copy package files
COPY package.json bun.lock ./
COPY packages ./packages

# Install dependencies and build
RUN bun install --frozen-lockfile

# Copy necessary files for Vite build
COPY vite.config.ts ./
COPY tsconfig.json* ./
COPY .prettierrc* ./
COPY .stylelintrc* ./
COPY config ./config
COPY public ./public

# Run build
RUN bun run build

# ============================================
# Stage 3: Final Production Image (FrankenPHP)
# ============================================
FROM dunglas/frankenphp:1-php8.5

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    wget \
    unzip \
    supervisor \
    libzip-dev \
    libpng-dev \
    libwebp-dev \
    libsodium-dev \
    graphicsmagick \
    imagemagick \
    ghostscript \
    $PHPIZE_DEPS

RUN set -eux; \
    install-php-extensions \
    intl \
    pdo_mysql \
    zip \
    redis \
    ;

# Clean up build dependencies
RUN rm -rf /var/lib/apt/lists/*

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure PHP for production
COPY .docker/php/typo3.ini /usr/local/etc/php/conf.d/typo3.ini

# Copy Caddyfile
COPY .docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

# FrankenPHP Environment Variables
ENV FRANKENPHP_CONFIG=""
ENV SERVER_NAME=":80"

WORKDIR /var/www/html

# Allow ImageMagick 6 to read/write pdf files
COPY .docker/imagemagick-policy.xml /etc/ImageMagick-7/policy.xml

# Copy application files
COPY --chown=www-data:www-data . .

# Copy composer dependencies
COPY --from=composer-builder --chown=www-data:www-data /app/vendor ./vendor

# Copy frontend build artifacts
COPY --from=frontend-builder --chown=www-data:www-data /app/public/_assets ./public/_assets

# Supervisor configuration (FrankenPHP + queue worker)
COPY .docker/supervisor/supervisord.conf /etc/supervisord.conf

# Ensure Supervisor log directory exists
RUN mkdir -p /var/log/supervisor

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost/typo3/login || exit 1

# Expose ports
EXPOSE 80 443

# Start FrankenPHP + queue worker via Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
