#!/bin/bash
# DevFlow Pro - Docker Setup Script
# Quick setup for development or production environment

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Print colored message
print_message() {
    echo -e "${2}${1}${NC}"
}

# Print header
print_header() {
    echo ""
    echo -e "${GREEN}================================${NC}"
    echo -e "${GREEN}$1${NC}"
    echo -e "${GREEN}================================${NC}"
}

# Check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Main function
main() {
    print_header "DevFlow Pro - Docker Setup"

    # Check prerequisites
    print_message "Checking prerequisites..." "$YELLOW"

    if ! command_exists docker; then
        print_message "Error: Docker is not installed!" "$RED"
        print_message "Install from: https://docs.docker.com/get-docker/" "$YELLOW"
        exit 1
    fi

    if ! command_exists docker-compose; then
        print_message "Error: Docker Compose is not installed!" "$RED"
        print_message "Install from: https://docs.docker.com/compose/install/" "$YELLOW"
        exit 1
    fi

    print_message "Prerequisites check passed!" "$GREEN"

    # Ask for environment
    echo ""
    print_message "Select environment:" "$BLUE"
    echo "1) Development (with Xdebug, Mailhog, pgAdmin)"
    echo "2) Production (optimized, no dev tools)"
    read -p "Enter choice [1-2]: " env_choice

    # Setup .env file
    if [ ! -f .env ]; then
        print_message "Creating .env file from example..." "$YELLOW"
        cp .env.example .env
        print_message ".env file created. Please review and update it!" "$GREEN"

        # Generate APP_KEY
        print_message "Would you like to generate APP_KEY now? (requires PHP locally)" "$YELLOW"
        read -p "Generate APP_KEY? [y/N]: " gen_key
        if [[ $gen_key =~ ^[Yy]$ ]]; then
            if command_exists php; then
                php artisan key:generate --show | sed 's/base64://' | base64 -d | sed 's/^/APP_KEY=base64:/' > .env.tmp
                cat .env.tmp >> .env
                rm .env.tmp
                print_message "APP_KEY generated!" "$GREEN"
            else
                print_message "PHP not found locally. Will generate after Docker setup." "$YELLOW"
            fi
        fi
    else
        print_message ".env file already exists, skipping..." "$YELLOW"
    fi

    # Setup based on environment
    if [ "$env_choice" = "1" ]; then
        setup_development
    else
        setup_production
    fi

    # Final instructions
    print_header "Setup Complete!"
    print_message "Next steps:" "$GREEN"

    if [ "$env_choice" = "1" ]; then
        echo ""
        echo "1. Review your .env file: nano .env"
        echo "2. Access your application:"
        echo "   - Application: http://localhost:8080"
        echo "   - Mailhog: http://localhost:8025"
        echo "   - pgAdmin: http://localhost:5050"
        echo "   - Redis Commander: http://localhost:8081"
        echo ""
        echo "3. Useful commands:"
        echo "   - View logs: docker-compose logs -f"
        echo "   - Shell access: docker-compose exec app bash"
        echo "   - Run migrations: docker-compose exec app php artisan migrate"
        echo "   - Or use Makefile: make help"
    else
        echo ""
        echo "1. Update .env with production values"
        echo "2. Configure SSL certificates in /opt/devflow/ssl/"
        echo "3. Update nginx configuration for HTTPS"
        echo "4. Access your application at the configured domain"
        echo ""
        echo "5. Production commands:"
        echo "   - View status: docker-compose ps"
        echo "   - View logs: docker-compose logs -f"
        echo "   - Run migrations: docker-compose exec app php artisan migrate --force"
    fi
}

# Setup development environment
setup_development() {
    print_header "Setting up Development Environment"

    # Create override file
    if [ ! -f docker-compose.override.yml ]; then
        print_message "Creating docker-compose.override.yml..." "$YELLOW"
        cp docker-compose.override.yml.example docker-compose.override.yml
        print_message "Override file created!" "$GREEN"
    fi

    # Create required directories
    print_message "Creating required directories..." "$YELLOW"
    mkdir -p storage/{app,framework,logs}
    mkdir -p storage/framework/{cache,sessions,testing,views}
    mkdir -p bootstrap/cache
    mkdir -p storage/data/{postgres,redis}
    mkdir -p storage/logs/nginx

    # Build containers
    print_message "Building Docker images (this may take a few minutes)..." "$YELLOW"
    docker-compose build

    # Start services
    print_message "Starting services..." "$YELLOW"
    docker-compose up -d

    # Wait for services to be ready
    print_message "Waiting for services to be ready..." "$YELLOW"
    sleep 10

    # Install composer dependencies
    print_message "Installing Composer dependencies..." "$YELLOW"
    docker-compose exec -T app composer install

    # Generate APP_KEY if not set
    if ! grep -q "APP_KEY=base64:" .env; then
        print_message "Generating application key..." "$YELLOW"
        docker-compose exec -T app php artisan key:generate
    fi

    # Run migrations
    print_message "Running database migrations..." "$YELLOW"
    docker-compose exec -T app php artisan migrate || true

    # Create storage link
    print_message "Creating storage symlink..." "$YELLOW"
    docker-compose exec -T app php artisan storage:link || true

    # Install npm dependencies (if needed)
    if [ -f package.json ]; then
        print_message "Would you like to install npm dependencies?" "$YELLOW"
        read -p "Install npm packages? [y/N]: " install_npm
        if [[ $install_npm =~ ^[Yy]$ ]]; then
            print_message "Installing npm dependencies..." "$YELLOW"
            docker-compose exec -T app npm install
            print_message "Building assets..." "$YELLOW"
            docker-compose exec -T app npm run dev
        fi
    fi

    print_message "Development environment ready!" "$GREEN"
}

# Setup production environment
setup_production() {
    print_header "Setting up Production Environment"

    # Create required directories on host
    print_message "Creating required directories..." "$YELLOW"
    sudo mkdir -p /opt/devflow/{projects,backups,logs,ssl}
    sudo chown -R $(whoami):$(whoami) /opt/devflow

    mkdir -p storage/data/{postgres,redis}
    mkdir -p storage/logs/nginx

    # Build production images
    print_message "Building production Docker images (this may take a few minutes)..." "$YELLOW"
    docker-compose build --no-cache

    # Start services
    print_message "Starting production services..." "$YELLOW"
    docker-compose up -d

    # Wait for services
    print_message "Waiting for services to be ready..." "$YELLOW"
    sleep 15

    # Generate APP_KEY if not set
    if ! grep -q "APP_KEY=base64:" .env; then
        print_message "Generating application key..." "$YELLOW"
        docker-compose exec -T app php artisan key:generate
    fi

    # Run migrations
    print_message "Running database migrations..." "$YELLOW"
    docker-compose exec -T app php artisan migrate --force

    # Seed database if needed
    print_message "Would you like to seed the database?" "$YELLOW"
    read -p "Seed database? [y/N]: " seed_db
    if [[ $seed_db =~ ^[Yy]$ ]]; then
        docker-compose exec -T app php artisan db:seed --force
    fi

    # Create storage link
    print_message "Creating storage symlink..." "$YELLOW"
    docker-compose exec -T app php artisan storage:link

    # Optimize for production
    print_message "Optimizing for production..." "$YELLOW"
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache
    docker-compose exec -T app php artisan optimize
    docker-compose exec -T app composer dump-autoload --optimize --classmap-authoritative

    print_message "Production environment ready!" "$GREEN"
    print_message "IMPORTANT: Update your .env file with production credentials!" "$RED"
}

# Run main function
main
