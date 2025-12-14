#!/bin/bash
# DevFlow Pro - Docker Setup Validation Script
# Validates that all required files and configurations are present

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

errors=0
warnings=0

print_header() {
    echo ""
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================${NC}"
}

check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $1"
        return 0
    else
        echo -e "${RED}✗${NC} $1 ${RED}(MISSING)${NC}"
        errors=$((errors + 1))
        return 1
    fi
}

check_file_optional() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $1"
        return 0
    else
        echo -e "${YELLOW}!${NC} $1 ${YELLOW}(Optional - Not found)${NC}"
        warnings=$((warnings + 1))
        return 1
    fi
}

check_dir() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✓${NC} $1/"
        return 0
    else
        echo -e "${RED}✗${NC} $1/ ${RED}(MISSING)${NC}"
        errors=$((errors + 1))
        return 1
    fi
}

print_header "DevFlow Pro - Docker Setup Validation"

# Check required commands
print_header "Checking Prerequisites"
if command -v docker &> /dev/null; then
    echo -e "${GREEN}✓${NC} Docker is installed ($(docker --version))"
else
    echo -e "${RED}✗${NC} Docker is NOT installed"
    errors=$((errors + 1))
fi

if command -v docker-compose &> /dev/null; then
    echo -e "${GREEN}✓${NC} Docker Compose is installed ($(docker-compose --version))"
else
    echo -e "${RED}✗${NC} Docker Compose is NOT installed"
    errors=$((errors + 1))
fi

# Check core Docker files
print_header "Checking Core Docker Files"
check_file "docker-compose.yml"
check_file "Dockerfile"
check_file ".dockerignore"
check_file_optional "docker-compose.override.yml"
check_file "docker-compose.override.yml.example"

# Check Docker configuration directories
print_header "Checking Docker Directories"
check_dir "docker"
check_dir "docker/nginx"
check_dir "docker/nginx/conf.d"
check_dir "docker/nginx/snippets"
check_dir "docker/php"
check_dir "docker/postgres"

# Check Nginx configuration files
print_header "Checking Nginx Configuration"
check_file "docker/nginx/nginx.conf"
check_file "docker/nginx/conf.d/default.conf"
check_file "docker/nginx/snippets/laravel.conf"
check_file "docker/nginx/snippets/ssl-params.conf"

# Check PHP configuration files
print_header "Checking PHP Configuration"
check_file "docker/php/php.ini"
check_file "docker/php/php-dev.ini"
check_file "docker/php/opcache.ini"
check_file "docker/php/opcache-dev.ini"
check_file "docker/php/php-fpm.conf"
check_file "docker/php/php-fpm-healthcheck"

# Check PostgreSQL configuration
print_header "Checking PostgreSQL Configuration"
check_file "docker/postgres/postgresql.conf"

# Check entrypoint scripts
print_header "Checking Entrypoint Scripts"
check_file "docker/entrypoint.sh"
check_file "docker/scheduler-entrypoint.sh"

if [ -f "docker/entrypoint.sh" ]; then
    if [ -x "docker/entrypoint.sh" ]; then
        echo -e "${GREEN}✓${NC} docker/entrypoint.sh is executable"
    else
        echo -e "${YELLOW}!${NC} docker/entrypoint.sh is not executable (run: chmod +x docker/entrypoint.sh)"
        warnings=$((warnings + 1))
    fi
fi

# Check helper scripts
print_header "Checking Helper Scripts"
check_file "docker-setup.sh"
check_file "Makefile.docker"

if [ -f "docker-setup.sh" ]; then
    if [ -x "docker-setup.sh" ]; then
        echo -e "${GREEN}✓${NC} docker-setup.sh is executable"
    else
        echo -e "${YELLOW}!${NC} docker-setup.sh is not executable (run: chmod +x docker-setup.sh)"
        warnings=$((warnings + 1))
    fi
fi

# Check documentation
print_header "Checking Documentation"
check_file "DOCKER_README.md"
check_file "DOCKER_QUICK_START.md"
check_file "DOCKER_SETUP_SUMMARY.md"

# Check environment file
print_header "Checking Environment Configuration"
check_file_optional ".env"
check_file ".env.example"

if [ -f ".env" ]; then
    if grep -q "APP_KEY=base64:" .env && [ "$(grep 'APP_KEY=base64:' .env | cut -d: -f2- | wc -c)" -gt 10 ]; then
        echo -e "${GREEN}✓${NC} APP_KEY is set in .env"
    else
        echo -e "${YELLOW}!${NC} APP_KEY not set in .env (run: docker-compose exec app php artisan key:generate)"
        warnings=$((warnings + 1))
    fi

    if grep -q "DB_PASSWORD=.*[a-zA-Z0-9]" .env; then
        echo -e "${GREEN}✓${NC} DB_PASSWORD is set in .env"
    else
        echo -e "${YELLOW}!${NC} DB_PASSWORD should be set in .env for production"
        warnings=$((warnings + 1))
    fi
fi

# Check Laravel directories
print_header "Checking Laravel Directories"
check_dir "storage"
check_dir "storage/app"
check_dir "storage/framework"
check_dir "storage/logs"
check_dir "bootstrap"
check_dir "bootstrap/cache"

# Check if storage directories are writable
if [ -d "storage" ]; then
    if [ -w "storage" ]; then
        echo -e "${GREEN}✓${NC} storage/ is writable"
    else
        echo -e "${YELLOW}!${NC} storage/ is not writable (run: chmod -R 775 storage)"
        warnings=$((warnings + 1))
    fi
fi

# Check Docker Compose syntax
print_header "Validating Docker Compose Configuration"
if docker-compose config > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} docker-compose.yml syntax is valid"
else
    echo -e "${RED}✗${NC} docker-compose.yml has syntax errors"
    echo -e "${YELLOW}Run 'docker-compose config' to see errors${NC}"
    errors=$((errors + 1))
fi

# Check if containers are running (optional)
print_header "Checking Container Status (Optional)"
if docker-compose ps 2>&1 | grep -q "Up"; then
    echo -e "${GREEN}✓${NC} Some containers are running"
    docker-compose ps
else
    echo -e "${YELLOW}!${NC} No containers are currently running (run: docker-compose up -d)"
    warnings=$((warnings + 1))
fi

# Final summary
print_header "Validation Summary"
if [ $errors -eq 0 ] && [ $warnings -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed! Docker setup is complete and valid.${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Review and update .env file: nano .env"
    echo "  2. Start containers: docker-compose up -d"
    echo "  3. Or use setup script: ./docker-setup.sh"
    exit 0
elif [ $errors -eq 0 ]; then
    echo -e "${YELLOW}⚠ Validation passed with ${warnings} warning(s).${NC}"
    echo ""
    echo "The setup is functional but some optional files or configurations are missing."
    echo "Review the warnings above and address them if needed."
    exit 0
else
    echo -e "${RED}✗ Validation failed with ${errors} error(s) and ${warnings} warning(s).${NC}"
    echo ""
    echo "Please fix the errors above before proceeding."
    echo "Some required files are missing from the Docker setup."
    exit 1
fi
