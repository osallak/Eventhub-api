FROM php:8.3-apache

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

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Configure Apache DocumentRoot
RUN sed -i -e "s/\/var\/www\/html/\/var\/www\/html\/public/g" /etc/apache2/sites-available/000-default.conf

# Add a startup script to run Laravel setup steps
COPY docker-startup.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-startup.sh

# Use Apache environment variables to specify port
ENV APACHE_RUN_PORT=8080
ENV PORT=8080
EXPOSE 8080

# Set the entry point
CMD ["/usr/local/bin/docker-startup.sh"]
