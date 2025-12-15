#!/bin/sh
set -e

# Run migrations if database file exists
if [ -f /var/www/html/database/database.sqlite ]; then
    echo "Running migrations..."
    php /var/www/html/artisan migrate --force
fi

# Run composer scripts
echo "Running composer scripts..."
composer run-script post-autoload-dump --working-dir=/var/www/html || true

# Clear and optimize
echo "Optimizing application..."
php /var/www/html/artisan config:clear || true
php /var/www/html/artisan route:clear || true
php /var/www/html/artisan view:clear || true
php /var/www/html/artisan optimize || true

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
