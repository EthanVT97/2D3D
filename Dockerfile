FROM php:8.1-apache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy Apache configuration
COPY apache2.conf /etc/apache2/apache2.conf

# Copy application files
COPY . /var/www/html/
WORKDIR /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && a2enmod rewrite headers \
    && service apache2 restart

EXPOSE 80
CMD ["apache2-foreground"]
