#!/bin/bash
set -e

# Clear config and cache
php artisan config:clear

# Run migrations if the database is available
php artisan migrate --force || true

# Update Apache port configuration
sed -i "s/Listen 80/Listen ${PORT:-8080}/" /etc/apache2/ports.conf

# Start Apache in foreground
apache2-foreground
