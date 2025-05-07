FROM php:7.4.33-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    zip \
    curl \
    git \
    && docker-php-ext-install pdo_mysql mysqli mbstring gd zip intl

RUN apt-get clean && rm -rf /var/lib/apt/lists/*
