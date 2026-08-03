FROM php:8.2-apache

# Cai extension MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy source code vao Apache
COPY . /var/www/html/

# Cap quyen
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80