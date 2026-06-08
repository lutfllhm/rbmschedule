FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    unzip \
    curl \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP settings for production
RUN { \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /var/www/html/logs/php_error.log'; \
    echo 'upload_max_filesize = 20M'; \
    echo 'post_max_size = 20M'; \
    echo 'max_execution_time = 300'; \
    echo 'memory_limit = 256M'; \
    echo 'date.timezone = Asia/Jakarta'; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Copy application source
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Create necessary directories and set permissions
RUN mkdir -p /var/www/html/logs /var/www/html/backup && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/logs /var/www/html/backup

# Expose HTTP port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Use default Apache startup command
CMD ["apache2-foreground"]
