FROM php:8.1-apache

# Install required extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    unzip \
    && docker-php-ext-install mysqli \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Copy application source
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Ensure logs folder exists and has proper permissions
RUN mkdir -p /var/www/html/logs && chown -R www-data:www-data /var/www/html/logs

# Expose HTTP port
EXPOSE 80

# Use default Apache startup command
CMD ["apache2-foreground"]
