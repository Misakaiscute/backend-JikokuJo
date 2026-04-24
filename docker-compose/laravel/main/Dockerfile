FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    default-mysql-client \
    && docker-php-ext-install zip pdo_mysql pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY --from=composer:2.9.5 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

COPY ./docker-compose/entrypoint.sh /entrypoint.sh

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

RUN chown +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

CMD ["php-fpm"]