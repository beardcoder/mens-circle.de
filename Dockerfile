FROM ghcr.io/serversideup/php:8.4-fpm-nginx-alpine AS base

ENV PHP_DATE_TIMEZONE="Europe/Berlin"
ENV PHP_MEMORY_LIMIT="512M"
ENV PHP_OPCACHE_ENABLE=1
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_BASE_DIR="/app"

USER root
RUN install-php-extensions intl

# Install Composer dependencies
FROM base AS vendor

COPY . /app
WORKDIR /app

RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

# Install Node.js dependencies
FROM node:24-alpine AS node-builder
ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
RUN corepack enable
COPY . /app
WORKDIR /app

# Install dependencies using pnpm and build the frontend
FROM node-builder AS frontend-build
COPY --from=vendor /app/vendor /app/vendor
COPY --from=vendor /app/public /app/public
RUN --mount=type=cache,id=pnpm,target=/pnpm/store pnpm install --frozen-lockfile
RUN pnpm run build


FROM base

COPY . /app
WORKDIR /app

COPY --from=vendor /app/vendor /app/vendor
COPY --from=frontend-build /app/public /app/public

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

RUN chown -R www-data:www-data /app

USER www-data
