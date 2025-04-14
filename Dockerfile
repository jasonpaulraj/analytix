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
RUN mkdir -p app

# Copy the application code
COPY . .

# Create necessary directories and set permissions
RUN mkdir -p /var/www/app/bootstrap/cache \
    && mkdir -p /var/www/app/storage/logs \
    && mkdir -p /var/www/app/storage/framework/sessions \
    && mkdir -p /var/www/app/storage/framework/views \
    && mkdir -p /var/www/app/storage/framework/cache \
    && mkdir -p /var/www/app/.cache/composer \
    && chown -R www-data:www-data /var/www/app \
    && chmod -R 755 /var/www/app \
    && chmod -R 775 /var/www/app/bootstrap/cache \
    && chmod -R 775 /var/www/app/storage \
    && chmod -R 775 /var/www/app/.cache 

# Switch to www-data user for remaining operations
USER www-data

# Install dependencies
RUN composer install
RUN npm install

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]