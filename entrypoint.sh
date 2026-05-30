#!/bin/sh
set -e

# Ensure working dirs
cd /var/www/html || exit 1
mkdir -p storage/app/public

# Write firebase credentials file if provided
if [ -n "$FIREBASE_CREDENTIALS_JSON" ]; then
  printf '%s' "$FIREBASE_CREDENTIALS_JSON" > storage/app/public/firebase-service-account.json
  chown www-data:www-data storage/app/public/firebase-service-account.json || true
fi

# Generate app key if missing (non-fatal)
php artisan key:generate --ansi || true

# Run migrations (non-fatal)
php artisan migrate --force || true

# Cache config/routes/views
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Ensure permissions
chown -R www-data:www-data storage bootstrap/cache || true

# Start Apache
exec "$@"
