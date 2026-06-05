#!/bin/sh
set -e

cd /var/www/html

PORT="${PORT:-8080}"
export PORT

# Render nginx config (Railway injects $PORT)
sed "s/\${PORT}/${PORT}/g" /etc/nginx/templates/default.conf.template \
    > /etc/nginx/sites-enabled/default

if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY is not set. Add it in Railway variables (php artisan key:generate --show)." >&2
    exit 1
fi

# 1. ON FORCE LE NETTOYAGE COMPLET DU CACHE AVANT TOUT
php artisan config:clear --no-ansi
php artisan cache:clear --no-ansi

# 2. Liens et packages
php artisan storage:link --force --no-ansi 2>/dev/null || true
php artisan package:discover --ansi

# 3. Les migrations vont maintenant lire DATABASE_URL en direct sans cache bloquant
php artisan migrate --force --no-ansi

# 4. On cache UNIQUEMENT les routes et les vues. 
# ON NE CACHE PAS LA CONFIGURATION (config:cache) pour laisser Railway injecter ses variables au runtime.
php artisan route:cache --no-ansi
php artisan view:cache --no-ansi

exec "$@"