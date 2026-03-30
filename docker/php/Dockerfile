FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libicu-dev \
    default-mysql-client \
    netcat-openbsd \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        zip \
        gd \
        intl \
        bcmath \
        pcntl \
        opcache \
        mbstring \
        exif

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/conf.d/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/php
