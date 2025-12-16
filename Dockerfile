FROM php:8.2-apache

# Nainstaluj knihovny pro MySQL driver
RUN docker-php-ext-install pdo pdo_mysql

# Set custom DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/www

RUN a2enmod rewrite

# Update Apache config to use new DocumentRoot
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
 && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}/!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf