#!/bin/sh
set -e

# Ensure storage directories exist and have correct permissions
mkdir -p /var/www/html/var/
mkdir -p /var/www/html/var/cache
mkdir -p /var/log/supervisor
mkdir -p /var/www/html/config/system

# Set permissions
chown -R www-data:www-data /var/www/html

# Cache configuration for production
echo "Caching configuration for production..."
php vendor/bin/typo3 cache:flush
php vendor/bin/typo3 extension:setup

# Execute the main command
exec "$@"
