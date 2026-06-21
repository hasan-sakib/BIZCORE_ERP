# syntax=docker/dockerfile:1
FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    bash curl git unzip libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    icu-dev oniguruma-dev libxml2-dev linux-headers $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_mysql mysqli \
        gd zip bcmath mbstring \
        intl exif xml opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

RUN mkdir -p /var/log/php /var/lib/php/sessions /var/lib/php/wsdlcache \
    && chown -R www-data:www-data /var/log/php /var/lib/php

WORKDIR /var/www/html

# ─── Development Stage ─────────────────────────────────────────────────────────
FROM base AS development

RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/bizcore.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

USER www-data

# ─── Production Stage ──────────────────────────────────────────────────────────
FROM base AS production

COPY docker/php/php.ini /usr/local/etc/php/conf.d/bizcore.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

COPY --chown=www-data:www-data . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
