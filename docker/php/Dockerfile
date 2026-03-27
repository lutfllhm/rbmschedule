FROM php:8.2-fpm-alpine

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /var/www/html/rbmschedule

# Use production-friendly PHP defaults
RUN printf '%s\n' \
    'expose_php=Off' \
    'display_errors=Off' \
    'log_errors=On' \
    'memory_limit=256M' \
    'upload_max_filesize=10M' \
    'post_max_size=10M' \
    'max_execution_time=300' \
    'date.timezone=Asia/Jakarta' \
    > /usr/local/etc/php/conf.d/rbmschedule.ini
