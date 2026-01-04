# =============================================================================
# Multi-stage Dockerfile for TYPO3 v14 with Coolify v4
# Using serversideup/php:8.5-fpm-nginx-alpine (optimized defaults)
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Composer Dependencies
# -----------------------------------------------------------------------------
FROM composer:2.8 AS composer-build

WORKDIR /app

COPY composer.json composer.lock ./
COPY packages/ packages/

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --classmap-authoritative

# -----------------------------------------------------------------------------
# Stage 2: Node.js Build (Vite)
# -----------------------------------------------------------------------------
FROM oven/bun:1-slim AS assets
WORKDIR /app

COPY package.json bun.lock ./

RUN --mount=type=cache,target=/root/.bun/install/cache \
    bun install --frozen-lockfile

COPY --from=composer-build --chown=www-data:www-data /app/vendor/ ./vendor/
COPY vite.config.ts tsconfig.json ./
COPY packages/ packages/

RUN bun run build

# -----------------------------------------------------------------------------
# Stage 3: Production Image
# -----------------------------------------------------------------------------
FROM serversideup/php:8.5-fpm-nginx-alpine AS production

LABEL maintainer="Mens Circle <info@mens-circle.de>"
LABEL org.opencontainers.image.source="https://github.com/beardcoder/mens-circle.de"
LABEL org.opencontainers.image.description="TYPO3 v14 production image for mens-circle.de"

USER root

# Install additional PHP extensions required by TYPO3
RUN install-php-extensions \
    intl \
    gd \
    zip \
    pdo_mysql \
    redis \
    apcu \
    bcmath \
    exif \
    imagick

# Install image processing tools
RUN apk add --no-cache \
    imagemagick \
    graphicsmagick \
    ghostscript \
    poppler-utils

# Create TYPO3 directories
RUN mkdir -p \
    /var/www/html/public/typo3temp \
    /var/www/html/public/fileadmin \
    /var/www/html/var/cache \
    /var/www/html/var/log \
    /var/www/html/var/lock \
    /var/www/html/var/session \
    /var/www/html/config/sites

# Copy TYPO3-specific nginx configuration (routing only)
COPY docker/nginx/typo3.conf /etc/nginx/server-opts.d/typo3.conf

WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .

# Copy Composer dependencies
COPY --from=composer-build --chown=www-data:www-data /app/vendor/ ./vendor/
COPY --from=composer-build --chown=www-data:www-data /app/public/ ./public/

# Copy built assets
COPY --from=assets --chown=www-data:www-data /app/public/_assets ./public/_assets

# Set permissions
RUN chown -R www-data:www-data \
    /var/www/html/public/typo3temp \
    /var/www/html/public/fileadmin \
    /var/www/html/var \
    /var/www/html/config \
    && chmod -R 775 \
    /var/www/html/public/typo3temp \
    /var/www/html/public/fileadmin \
    /var/www/html/var

USER www-data

# =============================================================================
# Environment Variables (serversideup/php built-in + TYPO3)
# Override these in Coolify
# =============================================================================
ENV APP_ENV=production \
    TYPO3_CONTEXT=Production \
    # serversideup/php nginx settings
    SSL_MODE=off \
    NGINX_WEBROOT=/var/www/html/public \
    # serversideup/php PHP settings (using built-in vars)
    PHP_MEMORY_LIMIT=512M \
    PHP_POST_MAX_SIZE=100M \
    PHP_UPLOAD_MAX_FILESIZE=100M \
    PHP_MAX_EXECUTION_TIME=240 \
    PHP_MAX_INPUT_TIME=240 \
    PHP_MAX_INPUT_VARS=1500 \
    # OPcache (serversideup/php built-in)
    PHP_OPCACHE_ENABLE=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=256 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=30000 \
    # PHP-FPM tuning
    PHP_FPM_PM_CONTROL=dynamic \
    PHP_FPM_PM_MAX_CHILDREN=50 \
    PHP_FPM_PM_START_SERVERS=5 \
    PHP_FPM_PM_MIN_SPARE_SERVERS=5 \
    PHP_FPM_PM_MAX_SPARE_SERVERS=35

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

EXPOSE 8080
