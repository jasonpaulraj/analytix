@echo off
echo ============================================
echo Starting Docker Setup for Laravel Application
echo ============================================
echo.

REM Create necessary directories
echo [1/6] Creating necessary directories...
if not exist "docker\nginx" (
    mkdir "docker\nginx"
    echo Created directory: docker\nginx
) else (
    echo Directory already exists: docker\nginx
)
echo.

REM Copy .env file if it doesn't exist
echo [2/6] Checking .env file...
if not exist ".env" (
    copy ".env.example" ".env"
    echo Created .env file from .env.example
) else (
    echo .env file already exists
)
echo.

REM Start Docker containers
echo [3/6] Checking Docker containers...
docker ps --filter "name=analytix-app" --format "{{.Names}}" | findstr /C:"analytix-app" >nul
if %errorlevel% equ 0 (
    echo Docker containers are already running
) else (
    echo Starting Docker containers...
    docker-compose up -d
    if %errorlevel% equ 0 (
        echo Docker containers started successfully
    ) else (
        echo Failed to start Docker containers
        exit /b %errorlevel%
    )
)

REM Wait for containers to be ready
echo [4/6] Loading Containers
timeout /t 1 >nul

REM Execute commands in the app container

echo [5/6] Generate Laravel Application Key
docker exec -it analytix-app bash -c "php artisan key:generate --no-ansi --no-interaction  > /dev/null 2>&1"

echo [5/6] Run Migrations
docker exec -it analytix-app bash -c "php artisan migrate  > /dev/null 2>&1"

echo [5/6] Run Build Vite Assets
docker exec -it analytix-app bash -c "npm run build > /dev/null 2>&1"

echo [5/6] Run Update Permissions
docker exec -it analytix-app bash -c "chown -R www-data:www-data /var/www/storage"
docker exec -it analytix-app bash -c "chown -R www-data:www-data /var/www/bootstrap/cache"

echo [5/6] Run Clear Caches
docker exec -it analytix-app bash -c "php artisan optimize:clear > /dev/null 2>&1"
    
if %errorlevel% equ 0 (
    echo Container setup completed successfully
) else (
    echo Container setup failed
    exit /b %errorlevel%
)
echo.

REM Final message
echo [6/6] Setup completed!
echo.
echo ============================================
echo Your Laravel application is now running.
echo ============================================