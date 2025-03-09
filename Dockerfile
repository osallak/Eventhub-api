FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip

# Set working directory
WORKDIR /var/www/html

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Set permissions
RUN chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Environment variables
ENV PORT=8080

# Expose port
EXPOSE 8080

# Add startup script
COPY docker-startup.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-startup.sh

# Start command
CMD ["/usr/local/bin/docker-startup.sh"]
