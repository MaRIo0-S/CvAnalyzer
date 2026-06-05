#!/bin/sh
set -e

cd /var/www/html

PORT="${PORT:-8080}"
export PORT

echo "Binding nginx on 0.0.0.0:${PORT} (Railway PORT=${PORT})"

# Render nginx config (Railway injects $PORT)
sed "s/\${PORT}/${PORT}/g" /etc/nginx/templates/default.conf.template \
    > /etc/nginx/sites-enabled/default

if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY is not set. Add it in Railway variables (php artisan key:generate --show)." >&2
    exit 1
fi

php artisan config:clear --no-ansi 2>/dev/null || true
php artisan storage:link --force --no-ansi 2>/dev/null || true

# Migrations + cache en arrière-plan : Nginx doit répondre vite pour le healthcheck Railway (/up)
(
    php artisan package:discover --ansi
    php artisan migrate --force --no-ansi
    php artisan config:cache --no-ansi
    php artisan route:cache --no-ansi
    php artisan view:cache --no-ansi
    echo "Bootstrap terminé (migrations + cache)."
) &

exec "$@"
