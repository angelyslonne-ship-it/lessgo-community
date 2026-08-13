FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev && docker-php-ext-install pdo pdo_pgsql pdo_mysql && a2enmod rewrite && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY . /var/www/html/
RUN chmod +x /var/www/html/docker-entrypoint.sh && \
    rm -f /etc/apache2/sites-enabled/000-default.conf && \
    printf '%s\n' \
    '<VirtualHost *:80>' \
    '    DocumentRoot /var/www/html/frontend' \
    '    <Directory /var/www/html/frontend>' \
    '        AllowOverride All' \
    '        Require all granted' \
    '        DirectoryIndex index.html index.php' \
    '    </Directory>' \
    '    <Directory /var/www/html/backend>' \
    '        AllowOverride All' \
    '        Require all granted' \
    '    </Directory>' \
    '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
    '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
    '</VirtualHost>' > /etc/apache2/sites-enabled/000-default.conf
EXPOSE 80
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
