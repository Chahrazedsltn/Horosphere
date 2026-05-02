FROM php:8.3-fpm-alpine AS base

# Extensions système
RUN apk add --no-cache \
    git \
    curl \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    openssl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        intl \
        zip \
        opcache \
        gd \
        mbstring

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/api

# ─────────────────────────────────────────
FROM base AS dev

RUN apk add --no-cache bash

# php.ini dev
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini /usr/local/etc/php/conf.d/horosphere.ini

# Xdebug
RUN apk add --no-cache linux-headers $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

COPY --chown=www-data:www-data . .

RUN composer install --no-interaction --prefer-dist

EXPOSE 9000
CMD ["php-fpm"]

# ─────────────────────────────────────────
FROM base AS prod

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini /usr/local/etc/php/conf.d/horosphere.ini

COPY --chown=www-data:www-data . .

RUN composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader \
    && php bin/console cache:clear --env=prod \
    && php bin/console cache:warmup --env=prod

EXPOSE 9000
CMD ["php-fpm"]
