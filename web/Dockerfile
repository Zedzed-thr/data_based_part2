# web/Dockerfile
FROM php:8.0-apache

# Active quelques extensions PHP utiles (ajuste selon tes besoins)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copie ton site dans le dossier servi par Apache
COPY . /var/www/html

# Désactive le cache d’opcode en dev (hot‑reload)
RUN sed -i 's/opcache.enable=1/opcache.enable=0/' /usr/local/etc/php/php.ini-production