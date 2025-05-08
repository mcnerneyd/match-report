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

# Set working directory
WORKDIR /var/www/html

# Copy and unzip FuelPHP core
COPY fuelphp-1.8.2.zip .
RUN echo "4f7de4eda568300fbc0a79f0e108617481699716845f2f05dbec5c6145192227  fuelphp-1.8.2.zip" | sha256sum -c - \
    && unzip fuelphp-1.8.2.zip \
    && ln -s fuelphp-1.8.2/fuel fuel \
    && rm fuelphp-1.8.2.zip \
    && chown -R www-data:www-data /var/www/html/

COPY init-volume.sh /usr/local/bin/init-volume.sh

# Expose FPM port
EXPOSE 9000

CMD ["php-fpm"]
