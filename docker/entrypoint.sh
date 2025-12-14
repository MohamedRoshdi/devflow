#!/bin/bash
# DevFlow Pro - Docker Entrypoint Script
# Initializes the Laravel application on container startup

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}DevFlow Pro - Initializing...${NC}"
echo -e "${GREEN}================================${NC}"

# Function to wait for a service
wait_for_service() {
    local host=$1
    local port=$2
    local service=$3
    local max_attempts=30
    local attempt=1

    echo -e "${YELLOW}Waiting for ${service} to be ready...${NC}"

    while ! nc -z "$host" "$port" 2>/dev/null; do
        if [ $attempt -eq $max_attempts ]; then
            echo -e "${RED}Failed to connect to ${service} after ${max_attempts} attempts${NC}"
            exit 1
        fi
        echo -e "${YELLOW}Attempt ${attempt}/${max_attempts}: ${service} is unavailable - sleeping${NC}"
        sleep 2
        attempt=$((attempt + 1))
    done

    echo -e "${GREEN}${service} is ready!${NC}"
}

# Wait for database
if [ ! -z "$DB_HOST" ]; then
    wait_for_service "$DB_HOST" "${DB_PORT:-5432}" "PostgreSQL"
fi

# Wait for Redis
if [ ! -z "$REDIS_HOST" ]; then
    wait_for_service "$REDIS_HOST" "${REDIS_PORT:-6379}" "Redis"
fi

# Environment check
echo -e "${YELLOW}Checking environment...${NC}"
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo -e "${RED}ERROR: APP_KEY is not set!${NC}"
    echo -e "${YELLOW}Run: docker-compose exec app php artisan key:generate${NC}"
    exit 1
fi
echo -e "${GREEN}Environment OK${NC}"

# Create required directories
echo -e "${YELLOW}Creating required directories...${NC}"
mkdir -p \
    storage/framework/{sessions,views,cache} \
    storage/logs \
    storage/app/public \
    bootstrap/cache

# Set permissions (only if we have write access)
if [ -w /var/www/html/storage ]; then
    echo -e "${YELLOW}Setting storage permissions...${NC}"
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi

# Clear and cache configuration (production only)
if [ "$APP_ENV" = "production" ]; then
    echo -e "${YELLOW}Caching configuration for production...${NC}"

    # Clear all caches first
    php artisan config:clear || echo -e "${YELLOW}Config clear skipped${NC}"
    php artisan route:clear || echo -e "${YELLOW}Route clear skipped${NC}"
    php artisan view:clear || echo -e "${YELLOW}View clear skipped${NC}"

    # Cache everything for production
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Event cache (if you have events)
    php artisan event:cache || echo -e "${YELLOW}Event cache skipped${NC}"

    echo -e "${GREEN}Configuration cached!${NC}"
fi

# Run migrations (with safety checks)
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo -e "${YELLOW}Running database migrations...${NC}"

    # Check if we can connect to database
    if php artisan db:show 2>/dev/null; then
        # Run migrations
        php artisan migrate --force --isolated
        echo -e "${GREEN}Migrations completed!${NC}"
    else
        echo -e "${RED}Cannot connect to database, skipping migrations${NC}"
    fi
fi

# Create storage link if it doesn't exist
if [ ! -L public/storage ]; then
    echo -e "${YELLOW}Creating storage symlink...${NC}"
    php artisan storage:link || echo -e "${YELLOW}Storage link already exists${NC}"
fi

# Optimize autoloader for production
if [ "$APP_ENV" = "production" ]; then
    echo -e "${YELLOW}Optimizing autoloader...${NC}"
    composer dump-autoload --optimize --classmap-authoritative --no-dev
fi

# Clear OPcache (production)
if [ "$APP_ENV" = "production" ] && command -v php-fpm &> /dev/null; then
    echo -e "${YELLOW}Clearing OPcache...${NC}"
    # Send USR2 signal to php-fpm to reload
    killall -USR2 php-fpm 2>/dev/null || true
fi

# Queue restart (if queue workers are running)
if [ "${RESTART_QUEUE:-false}" = "true" ]; then
    echo -e "${YELLOW}Restarting queue workers...${NC}"
    php artisan queue:restart || echo -e "${YELLOW}Queue restart skipped${NC}"
fi

# Application warmup
echo -e "${YELLOW}Warming up application...${NC}"
php artisan inspire || true

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}DevFlow Pro - Ready!${NC}"
echo -e "${GREEN}Environment: ${APP_ENV:-production}${NC}"
echo -e "${GREEN}================================${NC}"

# Execute the main container command
exec "$@"
