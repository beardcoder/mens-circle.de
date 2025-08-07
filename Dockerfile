ROM dunglas/frankenphp:1-php8.4 AS frankenphp_upstream

FROM frankenphp_upstream AS frankenphp_base
WORKDIR /app
VOLUME /app/var/

# persistent / runtime deps
# hadolint ignore=DL3008
RUN apt-get update && apt-get install -y --no-install-recommends \
	acl \
	file \
	gettext \
	graphicsmagick \
    imagemagick \
    ghostscript \
	git \
	&& rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    install-php-extensions \
        @composer \
        apcu \
        redis \
        gd \
        pdo_mysql \
        intl \
        opcache \
        zip \
    ;

ENV PHP_DATE_TIMEZONE="Europe/Berlin"
ENV PHP_MEMORY_LIMIT="512M"
ENV PHP_OPCACHE_ENABLE=1
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_BASE_DIR="/app"
ENV SERVER_NAME=:80
ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

# Install Composer dependencies
FROM base AS vendor

COPY . /app
WORKDIR /app

RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

# Install Node.js dependencies
FROM oven/bun:latest AS frontend-build
COPY . /app
WORKDIR /app

COPY --from=vendor /app/vendor /app/vendor
COPY --from=vendor /app/public /app/public
RUN bun install

FROM base

COPY . /app
WORKDIR /app

COPY --from=vendor /app/vendor /app/vendor
COPY --from=frontend-build /app/public /app/public

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

RUN chown -R www-data:www-data /app

# Configure PHP
COPY config/php.ini /conf.d/php.ini

# Allow ImageMagick 6 to read/write pdf files
COPY config/imagemagick-policy.xml /etc/ImageMagick-6/policy.xml

COPY config/Caddyfile /etc/Caddyfile

WORKDIR /app

CMD ["frankenphp", "run", "--config", "/etc/Caddyfile" ]
