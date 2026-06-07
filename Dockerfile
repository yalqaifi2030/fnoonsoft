# syntax=docker/dockerfile:1
# ---------------------------------------------------------------------------
# Fnoon — production image for Coolify / VPS.
# PHP 8.3 (FPM) + Nginx + Supervisor in one image. No Node build step
# (the front-end uses Tailwind/Alpine via CDN), so the image stays lean.
# The same image runs the web, queue worker and scheduler (command override).
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS base

# --- System packages + PHP extensions --------------------------------------
RUN apk add --no-cache \
        nginx supervisor bash tzdata fcgi \
        icu-libs libzip libpng libjpeg-turbo freetype oniguruma \
        mysql-client \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd intl bcmath pdo_mysql zip exif pcntl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

# --- Composer --------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --- Runtime configuration -------------------------------------------------
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-fnoon.ini
# Overwrite the default pool (avoid a duplicate [www] pool definition).
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# --- Application -----------------------------------------------------------
WORKDIR /var/www/html

# Install PHP deps first (better layer caching) …
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

# … then the app, and finish the autoloader (scripts/package-discovery run at
# container start instead, where the runtime env is available).
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && mkdir -p storage/framework/{cache,sessions,views} storage/app/public storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
