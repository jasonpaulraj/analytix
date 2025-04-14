FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js and npm
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Set working directory
WORKDIR /var/www/html/app

# Copy the application code
COPY . /var/www/html/app

# Create necessary directories and set permissions
RUN mkdir -p /var/www/html/app/bootstrap/cache \
    && mkdir -p /var/www/html/app/storage/logs \
    && mkdir -p /var/www/html/app/storage/framework/sessions \
    && mkdir -p /var/www/html/app/storage/framework/views \
    && mkdir -p /var/www/html/app/storage/framework/cache \
    && mkdir -p /var/www/html/app/.cache/composer \
    && chown -R www-data:www-data /var/www/html/app \
    && chmod -R 755 /var/www/html/app \
    && chmod -R 775 /var/www/html/app/bootstrap/cache \
    && chmod -R 775 /var/www/html/app/storage \
    && chmod -R 775 /var/www/html/app/.cache \
    && git config --global --add safe.directory /var/www/html/app

# Switch to www-data user for remaining operations
USER www-data

# Install dependencies
RUN cd /var/www/html/app && composer install
RUN cd /var/www/html/app && npm install

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]