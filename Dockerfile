FROM php:8.3-apache

# Activer le mod_rewrite
RUN a2enmod rewrite

# Copier tout le projet
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html

# FORCER le DocumentRoot vers frontend
RUN sed -i 's!/var/www/html!/var/www/html/frontend!g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
