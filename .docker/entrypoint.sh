#!/bin/sh
set -e

# Ensure storage directories exist and have correct permissions
mkdir -p /var/www/html/var/
mkdir -p /var/www/html/var/cache
mkdir -p /var/log/supervisor
mkdir -p /var/www/html/config/system

# Set permissions
chown -R www-data:www-data /var/www/html

# Wait for database to be ready
echo "Waiting for database..."
until php -r "try { new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT'), getenv('DB_USER'), getenv('DB_PASSWORD')); exit(0); } catch(Exception \$e) { exit(1); }" 2>/dev/null; do
    sleep 2
done
echo "Database is ready!"

# Cache warmup for production
echo "Warming up caches for production..."

# Flush all caches first
php vendor/bin/typo3 cache:flush --group=system 2>/dev/null || true

# Setup extensions
php vendor/bin/typo3 extension:setup

# Warm up caches
php vendor/bin/typo3 cache:warmup 2>/dev/null || true

# Update language files if needed
php vendor/bin/typo3 language:update 2>/dev/null || true

echo "Cache warmup complete!"

# Execute the main command
exec "$@"
