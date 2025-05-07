FROM php:7.4.33-fpm

RUN apt-get update && apt-get install -y \
    unzip \
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

WORKDIR /var/www/html

COPY fuelphp-1.8.2.zip .
RUN unzip fuelphp-1.8.2.zip \
    && ln -s fuelphp-1.8.2/fuel fuel \
    && rm fuelphp-1.8.2.zip \
    && chown -R :www-data /var/www/html/

EXPOSE 9000
CMD ["php-fpm"]
