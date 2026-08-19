#!/bin/sh
set -e
cd /var/www/html

# Pastikan direktori writable ada & milik www-data (php-fpm worker)
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

if [ "${APP_SETUP:-false}" = "true" ]; then
    # ---- Inisialisasi SEKALI (hanya container app utama) ----
    [ -f .env ] || cp .env.example .env
    grep -qE '^APP_KEY=base64:.+' .env || php artisan key:generate --force --ansi
    php artisan package:discover --ansi || true
    php artisan storage:link || true

    echo "Menunggu database..."
    tries=0
    until php artisan migrate:status >/dev/null 2>&1 || [ "$tries" -ge 90 ]; do
        tries=$((tries+1)); sleep 2
    done
    php artisan migrate --force
    php artisan config:cache || true

    touch storage/.initialized
    echo "EAMS app terinisialisasi."
else
    # ---- Service dependen (queue/scheduler) tunggu app selesai init ----
    echo "Menunggu container app..."
    tries=0
    until [ -f storage/.initialized ] || [ "$tries" -ge 120 ]; do
        tries=$((tries+1)); sleep 2
    done
fi

exec "$@"
