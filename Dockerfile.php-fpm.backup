# ============================================
# Stage 1: PHP Dependencies (Composer)
# ============================================
FROM composer:2 AS composer-builder

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./
COPY packages ./packages

# Install production dependencies only (no dev)
# Ignore platform requirements as composer:2 image doesn't have all PHP extensions
# The final PHP 8.4 image will have all required extensions
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
# Stage 3: Final Production Image
# ============================================
FROM php:8.4-fpm-alpine

# Install system dependencies and PHP extensions
# Install build dependencies (will be removed later)
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    nginx \
    supervisor \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    icu-dev \
    libsodium-dev \
    imagemagick \
    imagemagick-dev

# Install PHP extensions
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        pdo_mysql \
        zip \
        opcache \
        sodium \
    && pecl install redis imagick \
    && docker-php-ext-enable redis imagick

# Clean up build dependencies
RUN apk del --no-cache $PHPIZE_DEPS \
    && rm -rf /tmp/* /var/cache/apk/*

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install runtime dependencies for Composer
RUN apk add --no-cache \
    git \
    unzip \
    && rm -rf /var/cache/apk/*

# Configure PHP for production
COPY .docker/php/typo3.ini /usr/local/etc/php/conf.d/typo3.ini

# Configure PHP-FPM
COPY .docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Configure nginx
RUN rm -rf /etc/nginx/http.d/* && mkdir -p /var/www/html
COPY .docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Configure supervisor
COPY .docker/supervisor/services.conf /etc/supervisor/conf.d/services.conf

WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .

# Copy composer dependencies
COPY --from=composer-builder --chown=www-data:www-data /app/vendor ./vendor

# Copy frontend build artifacts
COPY --from=frontend-builder --chown=www-data:www-data /app/public/_assets ./public/_assets

# Create necessary directories and set permissions
RUN mkdir -p var/log var/cache var/charset var/lock \
    && chown -R www-data:www-data var/ public/ \
    && find . -type d -exec chmod 755 {} \; \
    && find . -type f -exec chmod 644 {} \; \
    && find vendor/bin -type f -exec chmod +x {} \;

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

# Expose port
EXPOSE 80

# Start services with supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/services.conf"]
