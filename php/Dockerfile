FROM php:8.3-fpm-alpine

# Install essential system dependencies only
RUN apk add --no-cache \
    curl \
    libzip-dev \
    zip \
    unzip

# Install only essential PHP extensions
RUN docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        zip \
        opcache \
        bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html/api

# Copy PHP configuration
COPY php.ini /usr/local/etc/php/php.ini

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]