FROM php:8.2-fpm-alpine

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /var/www/html/rbmschedule

# Use production-friendly PHP defaults
RUN {
    echo "expose_php=Off";
    echo "display_errors=Off";
    echo "log_errors=On";
    echo "memory_limit=256M";
    echo "upload_max_filesize=10M";
    echo "post_max_size=10M";
    echo "max_execution_time=300";
    echo "date.timezone=Asia/Jakarta";
} > /usr/local/etc/php/conf.d/rbmschedule.ini
