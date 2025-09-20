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
WORKDIR /var/www/app

# Copy the application code
COPY --chown=www-data:www-data . /var/www/app

# Create necessary directories and set permissions
RUN mkdir -p /var/www/app/bootstrap/cache \
    && mkdir -p /var/www/app/storage/logs \
    && mkdir -p /var/www/app/storage/framework/sessions \
    && mkdir -p /var/www/app/storage/framework/views \
    && mkdir -p /var/www/app/storage/framework/cache \
    && mkdir -p /var/www/.cache/composer \
    && mkdir -p /var/www/.npm \
    && chown -R www-data:www-data /var/www/app \
    && chown -R www-data:www-data /var/www/.cache \
    && chown -R www-data:www-data /var/www/.npm \
    && chmod -R 755 /var/www/app \
    && chmod -R 775 /var/www/app/bootstrap/cache \
    && chmod -R 775 /var/www/app/storage \
    && chmod -R 775 /var/www/.cache \
    && chmod -R 775 /var/www/.npm

# Create startup script
RUN echo '#!/bin/bash\n\
echo "Running startup script..."\n\
# Fix git permissions\n\
git config --system --add safe.directory /var/www/app\n\
# Ensure cache directories exist and have correct permissions\n\
mkdir -p /var/www/app/bootstrap/cache\n\
mkdir -p /var/www/app/storage/framework/{sessions,views,cache}\n\
mkdir -p /var/www/app/storage/logs\n\
# Set ownership to www-data\n\
chown -R www-data:www-data /var/www/app/bootstrap\n\
chown -R www-data:www-data /var/www/app/storage\n\
# Set proper permissions\n\
chmod -R 775 /var/www/app/bootstrap/cache\n\
chmod -R 775 /var/www/app/storage\n\
# Generate application key if not exists\n\
if [ -f /var/www/app/.env ] && ! grep -q "^APP_KEY=" /var/www/app/.env || grep -q "^APP_KEY=$" /var/www/app/.env; then\n\
  echo "Generating application key..."\n\
  php artisan key:generate --force\n\
fi\n\
# Clear and optimize Laravel caches as www-data\n\
su -s /bin/bash www-data -c "php artisan config:clear" || true\n\
su -s /bin/bash www-data -c "php artisan cache:clear" || true\n\
su -s /bin/bash www-data -c "php artisan view:clear" || true\n\
# Start PHP-FPM as www-data\n\
exec su -s /bin/bash www-data -c "php-fpm"\n\
' > /usr/local/bin/start.sh

# Make the script executable
RUN chmod +x /usr/local/bin/start.sh

# Configure git for www-data user
RUN git config --system --add safe.directory /var/www/app

# Switch to www-data user for remaining operations
USER www-data

# Install dependencies
RUN composer install --no-interaction --no-scripts
RUN npm install

# Build assets with Vite
RUN npm run build

# Switch back to root for startup script execution
USER root

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["/usr/local/bin/start.sh"]