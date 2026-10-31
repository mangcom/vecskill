# PHP + Apache runtime (api/stats.php, api/track.php ใช้ PDO MySQL)
FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html
