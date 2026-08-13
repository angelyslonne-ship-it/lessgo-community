FROM php:8.3-apache

# Installer les extensions nécessaires
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql \
    && a2enmod rewrite

# Copier tout le projet
COPY . /var/www/html/

# Donner les permissions
RUN chmod -R 755 /var/www/html/backend \
    && chmod -R 755 /var/www/html/frontend

# Activer le mod_rewrite
RUN a2enmod rewrite

# Configurer Apache
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/frontend\n\
    <Directory /var/www/html/frontend>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    Alias /api /var/www/html/backend\n\
    <Directory /var/www/html/backend>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
