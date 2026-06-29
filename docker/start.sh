#!/bin/sh
set -eu

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
php artisan storage:link 2>/dev/null || true

su-exec www-data php artisan migrate --force

if [ "${RENDER:-false}" = "true" ] && [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
    su-exec www-data php artisan db:seed --force
fi

su-exec www-data php artisan config:cache
su-exec www-data php artisan view:cache

exec su-exec www-data php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"