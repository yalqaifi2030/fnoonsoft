FROM serversideup/php:8.3-fpm-nginx

WORKDIR /var/www/html

USER root

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN if [ -f package.json ]; then npm install && npm run build; fi

# Publish Filament's static assets and create the public/storage symlink.
# These are env-independent, so they are safe to run at build time.
# (config/route/view caching is intentionally left to runtime — see Coolify
#  post-deploy command — because it would otherwise bake build-time env.)
RUN php artisan filament:assets \
    && php artisan storage:link || true

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 8080