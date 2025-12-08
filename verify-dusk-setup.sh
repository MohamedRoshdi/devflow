#!/bin/bash

# DevFlow Pro - Dusk Setup Verification Script
# This script verifies that all Dusk testing components are properly configured

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_header() {
    echo -e "${BLUE}================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

ERRORS=0
WARNINGS=0

print_header "DevFlow Pro - Dusk Setup Verification"

# Check Docker
echo -e "\n${BLUE}Checking Docker...${NC}"
if command -v docker &> /dev/null; then
    print_success "Docker is installed"
    if docker info > /dev/null 2>&1; then
        print_success "Docker is running"
        DOCKER_VERSION=$(docker --version)
        print_info "$DOCKER_VERSION"
    else
        print_error "Docker is not running"
        ERRORS=$((ERRORS+1))
    fi
else
    print_error "Docker is not installed"
    ERRORS=$((ERRORS+1))
fi

# Check Docker Compose
echo -e "\n${BLUE}Checking Docker Compose...${NC}"
if command -v docker-compose &> /dev/null; then
    print_success "Docker Compose v1 is installed"
    COMPOSE_VERSION=$(docker-compose --version)
    print_info "$COMPOSE_VERSION"
elif docker compose version &> /dev/null; then
    print_success "Docker Compose v2 is installed"
    COMPOSE_VERSION=$(docker compose version)
    print_info "$COMPOSE_VERSION"
else
    print_error "Docker Compose is not installed"
    ERRORS=$((ERRORS+1))
fi

# Check required files
echo -e "\n${BLUE}Checking Configuration Files...${NC}"

FILES=(
    "docker-compose.dusk.yml"
    "Dockerfile.dusk"
    ".env.dusk.docker"
    "run-dusk-tests.sh"
    "tests/DuskTestCase.php"
    "phpunit.dusk.xml"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        print_success "Found: $file"
    else
        print_error "Missing: $file"
        ERRORS=$((ERRORS+1))
    fi
done

# Check if run-dusk-tests.sh is executable
if [ -f "run-dusk-tests.sh" ]; then
    if [ -x "run-dusk-tests.sh" ]; then
        print_success "run-dusk-tests.sh is executable"
    else
        print_warning "run-dusk-tests.sh is not executable (will be fixed)"
        chmod +x run-dusk-tests.sh
        print_success "Made run-dusk-tests.sh executable"
    fi
fi

# Check Laravel Dusk installation
echo -e "\n${BLUE}Checking Laravel Dusk...${NC}"
if [ -f "composer.json" ]; then
    if grep -q "laravel/dusk" composer.json; then
        print_success "Laravel Dusk is in composer.json"
        if [ -d "vendor/laravel/dusk" ]; then
            print_success "Laravel Dusk is installed in vendor"
        else
            print_warning "Laravel Dusk not installed. Run: composer install"
            WARNINGS=$((WARNINGS+1))
        fi
    else
        print_error "Laravel Dusk not found in composer.json"
        ERRORS=$((ERRORS+1))
    fi
else
    print_error "composer.json not found"
    ERRORS=$((ERRORS+1))
fi

# Check test directory structure
echo -e "\n${BLUE}Checking Test Directory Structure...${NC}"
if [ -d "tests/Browser" ]; then
    print_success "tests/Browser directory exists"
    TEST_COUNT=$(find tests/Browser -name "*Test.php" 2>/dev/null | wc -l)
    print_info "Found $TEST_COUNT test files"

    # Create screenshot and console directories if they don't exist
    mkdir -p tests/Browser/screenshots
    mkdir -p tests/Browser/console
    print_success "Screenshot and console directories ready"
else
    print_error "tests/Browser directory not found"
    ERRORS=$((ERRORS+1))
fi

# Check storage directories
echo -e "\n${BLUE}Checking Storage Directories...${NC}"
STORAGE_DIRS=(
    "storage/app"
    "storage/logs"
    "storage/framework/cache"
    "storage/framework/sessions"
    "storage/framework/views"
)

for dir in "${STORAGE_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        print_success "Found: $dir"
    else
        print_warning "Missing: $dir (will be created)"
        mkdir -p "$dir"
        print_success "Created: $dir"
    fi
done

# Check PHP extensions requirements
echo -e "\n${BLUE}Checking PHP Requirements...${NC}"
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    print_info "PHP Version: $PHP_VERSION"

    REQUIRED_EXTENSIONS=(
        "curl"
        "mbstring"
        "xml"
        "zip"
    )

    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^$ext$"; then
            print_success "PHP extension $ext is installed"
        else
            print_warning "PHP extension $ext is not installed (needed for local development)"
            WARNINGS=$((WARNINGS+1))
        fi
    done
else
    print_warning "PHP is not installed locally (OK if using Docker only)"
fi

# Check ports availability
echo -e "\n${BLUE}Checking Port Availability...${NC}"
check_port() {
    local port=$1
    local service=$2

    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
        print_warning "Port $port is already in use ($service may conflict)"
        WARNINGS=$((WARNINGS+1))
    else
        print_success "Port $port is available ($service)"
    fi
}

check_port 9000 "Application"
check_port 4444 "Selenium"
check_port 7900 "VNC"
check_port 33061 "MySQL"

# Check documentation
echo -e "\n${BLUE}Checking Documentation...${NC}"
if [ -f "DUSK_TESTING.md" ]; then
    print_success "Found: DUSK_TESTING.md"
else
    print_warning "Documentation file DUSK_TESTING.md not found"
    WARNINGS=$((WARNINGS+1))
fi

if [ -f "DUSK_QUICK_START.md" ]; then
    print_success "Found: DUSK_QUICK_START.md"
else
    print_warning "Quick start guide DUSK_QUICK_START.md not found"
    WARNINGS=$((WARNINGS+1))
fi

# Validate docker-compose.dusk.yml syntax
echo -e "\n${BLUE}Validating Docker Compose Configuration...${NC}"
if [ -f "docker-compose.dusk.yml" ]; then
    if docker compose -f docker-compose.dusk.yml config > /dev/null 2>&1; then
        print_success "docker-compose.dusk.yml syntax is valid"
    elif command -v docker-compose &> /dev/null && docker-compose -f docker-compose.dusk.yml config > /dev/null 2>&1; then
        print_success "docker-compose.dusk.yml syntax is valid"
    else
        print_error "docker-compose.dusk.yml has syntax errors"
        ERRORS=$((ERRORS+1))
    fi
fi

# Summary
echo ""
print_header "Verification Summary"

if [ $ERRORS -eq 0 ]; then
    print_success "No critical errors found!"
else
    print_error "Found $ERRORS critical error(s)"
fi

if [ $WARNINGS -eq 0 ]; then
    print_success "No warnings"
else
    print_warning "Found $WARNINGS warning(s) (non-critical)"
fi

echo ""

if [ $ERRORS -eq 0 ]; then
    print_header "Setup is Ready!"
    echo ""
    echo -e "${GREEN}You can now run Dusk tests:${NC}"
    echo ""
    echo "  ./run-dusk-tests.sh"
    echo ""
    echo "Or using Make:"
    echo ""
    echo "  make -f Makefile.dusk dusk-test"
    echo ""
    echo -e "${BLUE}For more information, see:${NC}"
    echo "  - DUSK_TESTING.md (full documentation)"
    echo "  - DUSK_QUICK_START.md (quick reference)"
    echo ""
    exit 0
else
    print_header "Setup Incomplete"
    echo ""
    echo -e "${RED}Please fix the errors above before running tests.${NC}"
    echo ""
    exit 1
fi
