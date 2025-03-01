#!/bin/bash
set -e

# Set JWT secret if not already set
if [ -z "$JWT_SECRET" ]; then
  export JWT_SECRET="RT9cObl5vsnGV4jno6rJcMtRnPPg6C9nCY6wQfRfhIH7b28sVCptNErVFfGLtMVp"
fi

# Enable debugging
export API_DEBUG=true

# Clear config cache
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# List available routes
php artisan route:list

# Start the PHP built-in server
php -S 0.0.0.0:${PORT:-8080} -t public
