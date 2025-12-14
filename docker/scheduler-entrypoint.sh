#!/bin/bash
# DevFlow Pro - Scheduler (Cron) Entrypoint Script
# Runs Laravel scheduler every minute

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}DevFlow Pro - Scheduler Starting${NC}"
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
            echo -e "${RED}Failed to connect to ${service}${NC}"
            exit 1
        fi
        echo -e "${YELLOW}Attempt ${attempt}/${max_attempts}: ${service} is unavailable - sleeping${NC}"
        sleep 2
        attempt=$((attempt + 1))
    done

    echo -e "${GREEN}${service} is ready!${NC}"
}

# Wait for dependencies
if [ ! -z "$DB_HOST" ]; then
    wait_for_service "$DB_HOST" "${DB_PORT:-5432}" "PostgreSQL"
fi

if [ ! -z "$REDIS_HOST" ]; then
    wait_for_service "$REDIS_HOST" "${REDIS_PORT:-6379}" "Redis"
fi

echo -e "${GREEN}Starting Laravel scheduler...${NC}"
echo -e "${YELLOW}Running schedule:run every minute${NC}"

# Infinite loop to run scheduler
while true; do
    # Run the scheduler
    php artisan schedule:run --verbose --no-interaction >> /proc/1/fd/1 2>&1

    # Sleep for 60 seconds
    sleep 60
done
