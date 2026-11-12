FROM php:8.3-apache
RUN docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite headers
WORKDIR /var/www/html
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html \
    && printf '\n<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' >> /etc/apache2/apache2.conf
EXPOSE 80
CMD ["apache2-foreground"]
