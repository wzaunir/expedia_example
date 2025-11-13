FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update \
    && apt-get install -y git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip

# Copy composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Install PHP dependencies (ignore failures if offline)
RUN composer install --no-interaction --prefer-dist || true

CMD ["php-fpm"]
