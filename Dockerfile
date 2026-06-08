FROM serversideup/php:8.3-fpm-nginx

WORKDIR /var/www/html

USER root

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN if [ -f package.json ]; then npm install && npm run build; fi

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 8080