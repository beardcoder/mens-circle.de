# ============================================
# Stage 1: Frontend Build (Node/Vite)
# ============================================
FROM node:22-alpine AS frontend-builder

WORKDIR /app

# Copy package files
COPY package*.json ./
COPY packages ./packages

# Install dependencies and build
RUN npm ci --no-audit --prefer-offline
COPY vite.config.js* ./
COPY .vite* ./
COPY packages ./packages
RUN npm run build

# ============================================
# Stage 2: PHP Dependencies (Composer)
# ============================================
FROM composer:2 AS composer-builder

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install production dependencies only (no dev)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Copy application code for post-install scripts
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

# ============================================
# Stage 3: Final Production Image
# ============================================
FROM php:8.4-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
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
    imagemagick-dev \
    && docker-php-ext-configure gd \
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
    && docker-php-ext-enable redis imagick \
    && apk del --no-cache ${PHPIZE_DEPS} \
    && rm -rf /tmp/* /var/cache/apk/*

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
    && find . -type f -exec chmod 644 {} \;

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

# Expose port
EXPOSE 80

# Start services with supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/services.conf"]
