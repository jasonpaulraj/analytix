#!/bin/bash

# Create necessary directories
mkdir -p docker/nginx

# Copy .env file if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env file from .env.example"
fi

# Start Docker containers
docker-compose up -d

# Wait for containers to be ready
echo "Waiting for containers to be ready..."
sleep 10

# Enter the app container
docker exec -it analytix-app bash -c "
    # Generate application key
    php artisan key:generate
    
    # Run migrations
    php artisan migrate
    
    # Build assets with Vite
    npm run build
    
    # Set proper permissions
    chown -R www-data:www-data /var/www/storage
    chown -R www-data:www-data /var/www/bootstrap/cache
    
    # Clear caches
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    
    echo 'Laravel setup completed!'
"

echo "Setup completed! Your Laravel application is now running."