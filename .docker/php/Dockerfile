FROM php:8.3-fpm-alpine

RUN apk --update --no-cache add \
    wget \
    curl \
    grep \
    libpq-dev &&\
docker-php-ext-install pdo pgsql pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY storage storage
RUN mkdir storage/app &&\
    chown -R www-data:www-data ./storage/*

CMD composer install && php-fpm
