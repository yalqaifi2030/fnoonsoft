#!/bin/sh
set -e
cd /var/www/html

# nginx pid dir + storage scaffolding (the storage volume starts empty).
mkdir -p /run/nginx \
    storage/framework/cache storage/framework/sessions storage/framework/views \
    storage/app/public storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Public symlink + Filament's published assets (idempotent).
php artisan storage:link 2>/dev/null || true
php artisan filament:assets 2>/dev/null || true

# Migrate only where explicitly enabled (the web service), never the workers.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "[entrypoint] Running database migrations…"
    php artisan migrate --force || true
fi

# Cache config/routes/views/events for production speed.
php artisan optimize 2>/dev/null || true

exec "$@"
