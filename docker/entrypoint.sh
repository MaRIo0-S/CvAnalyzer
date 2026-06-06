#!/bin/sh
set -e

cd /var/www/html

PORT="${PORT:-8080}"
export PORT

echo "Binding nginx on 0.0.0.0:${PORT} (PORT=${PORT})"

# Render nginx config ($PORT injecté par la plateforme d'hébergement)
sed "s/\${PORT}/${PORT}/g" /etc/nginx/templates/default.conf.template \
    > /etc/nginx/sites-enabled/default

if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2
    exit 1
fi

php artisan config:clear --no-ansi 2>/dev/null || true
php artisan storage:link --force --no-ansi 2>/dev/null || true

mkdir -p \
    storage/app/temp \
    storage/app/public/cvs \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Volume persistant (ex. storage/app) : droits d'écriture pour php-fpm (www-data)
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

if ! su -s /bin/sh www-data -c 'touch storage/app/public/.write-test && rm storage/app/public/.write-test'; then
    echo "WARN: www-data cannot write to storage/app/public — CV uploads will fail." >&2
    chmod -R 777 storage/app/public 2>/dev/null || true
fi

# Migrations + cache en arrière-plan : Nginx doit répondre vite pour le healthcheck (/up)
(
    php artisan package:discover --ansi
    php artisan migrate --force --no-ansi
    php artisan route:cache --no-ansi
    php artisan view:cache --no-ansi
    echo "Bootstrap terminé (migrations + cache)."
) &

exec "$@"
