FROM ghcr.io/serversideup/php:8.4-fpm-nginx-alpine AS base

ENV PHP_DATE_TIMEZONE="Europe/Berlin"
ENV PHP_MEMORY_LIMIT="512M"
ENV PHP_OPCACHE_ENABLE=1
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN install-php-extensions \
    intl \
    zip

COPY . /var/www/html
WORKDIR /var/www/html

# Install Composer dependencies
FROM base AS vendor

RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

# Install Node.js dependencies
FROM node:24-alpine AS node-builder
ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
RUN corepack enable
COPY . /var/www/html
WORKDIR /var/www/html

# Install dependencies using pnpm and build the frontend
FROM node-builder AS frontend-build
COPY --from=vendor /var/www/html/vendor /var/www/html/vendor
COPY --from=vendor /var/www/html/public /var/www/html/public
RUN --mount=type=cache,id=pnpm,target=/pnpm/store pnpm install --frozen-lockfile
RUN pnpm run build


FROM base

COPY . /var/www/html
COPY --from=vendor /var/www/html/vendor /var/www/html/vendor
COPY --from=frontend-build /var/www/html/public /var/www/html/public

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative
RUN docker-php-serversideup-set-file-permissions --owner 1000:1000 --service nginx

WORKDIR /var/www/html

EXPOSE 80
