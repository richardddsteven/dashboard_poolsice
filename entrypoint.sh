#!/bin/sh
set -e

# Ensure working dirs
cd /var/www/html || exit 1
mkdir -p storage/app/public storage/logs bootstrap/cache
touch storage/logs/laravel.log

# Make public storage available for /storage/* asset URLs
php artisan storage:link --force || true

# Copy logo asset from the Flutter folder if it exists in the repo image
if [ -f "driver_app_flutter/assets/images/poolsice.png" ]; then
  cp driver_app_flutter/assets/images/poolsice.png storage/app/public/poolsice.png
  chown www-data:www-data storage/app/public/poolsice.png || true
fi

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

# Fix Apache MPM conflicts: ensure only one MPM is enabled (use prefork for mod_php)
# Disable event MPM if present and enable prefork
if command -v a2dismod >/dev/null 2>&1; then
  a2dismod mpm_event || true
  a2enmod mpm_prefork || true
fi

echo "Starting Apache (entrypoint)..."
# Start Apache
exec "$@"
