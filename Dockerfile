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

# Copy application code (keep ownership root first)
COPY . /var/www/app

# Create necessary directories and set permissions
RUN mkdir -p /var/www/app/bootstrap/cache \
    && mkdir -p /var/www/app/storage/framework/{sessions,views,cache} \
    && mkdir -p /var/www/app/storage/logs \
    && mkdir -p /var/www/.cache/composer \
    && mkdir -p /var/www/.npm \
    && chown -R www-data:www-data /var/www/app /var/www/.cache /var/www/.npm \
    && chmod -R 775 /var/www/app/bootstrap/cache \
    && chmod -R 775 /var/www/app/storage

# Install dependencies as root (avoids npm/composer permission issues)
RUN composer install --no-interaction --no-scripts \
    && npm install \
    && npm run build

# Startup script (fix perms every time in case of mounted volumes)
RUN echo '#!/bin/bash\n\
echo "Running startup script..."\n\
chown -R www-data:www-data /var/www/app/bootstrap /var/www/app/storage\n\
chmod -R 775 /var/www/app/bootstrap/cache /var/www/app/storage\n\
if [ -f /var/www/app/.env ] && (! grep -q "^APP_KEY=" /var/www/app/.env || grep -q "^APP_KEY=$" /var/www/app/.env); then\n\
  echo "Generating application key..."\n\
  php artisan key:generate --force\n\
fi\n\
exec php-fpm\n\
' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

# Expose PHP-FPM
EXPOSE 9000

# Run as www-data by default
USER www-data

CMD ["/usr/local/bin/start.sh"]
