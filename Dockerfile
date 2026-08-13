FROM php:8.3-apache

# Activer les extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql \
    && a2enmod rewrite

# Copier tout le projet
COPY . /var/www/html/

# Configurer Apache pour pointer vers frontend
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/frontend\n\
    <Directory /var/www/html/frontend>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    Alias /backend /var/www/html/backend\n\
    <Directory /var/www/html/backend>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
