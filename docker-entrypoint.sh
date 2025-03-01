#!/bin/bash
set -e

# Clear configuration cache
php artisan config:clear

# Run migrations if needed
php artisan migrate --force || true

# Start PHP server on the correct port
exec php -S 0.0.0.0:9000 -t public
