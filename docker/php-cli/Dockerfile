FROM composer:2 AS composer

FROM php:8.5-cli-alpine

RUN apk add --no-cache \
    git \
    unzip \
    libpq-dev \
    postgresql-client \
    && docker-php-ext-install pdo_pgsql

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
