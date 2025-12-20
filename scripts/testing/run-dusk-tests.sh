#!/bin/bash

# DevFlow Pro - Laravel Dusk Test Runner with Docker/Selenium
# This script sets up and runs Laravel Dusk browser tests using Docker containers

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
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

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Parse command line arguments
FILTER=""
STOP_ON_FAILURE=""
COVERAGE=""
GROUP=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --filter=*)
            FILTER="${1#*=}"
            shift
            ;;
        --stop-on-failure)
            STOP_ON_FAILURE="--stop-on-failure"
            shift
            ;;
        --coverage)
            COVERAGE="--coverage"
            shift
            ;;
        --group=*)
            GROUP="--group=${1#*=}"
            shift
            ;;
        --help)
            echo "Usage: ./run-dusk-tests.sh [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --filter=<pattern>      Run only tests matching the pattern"
            echo "  --stop-on-failure       Stop execution upon first error or failure"
            echo "  --coverage              Generate code coverage report"
            echo "  --group=<group>         Run tests from specific group"
            echo "  --help                  Display this help message"
            echo ""
            echo "Examples:"
            echo "  ./run-dusk-tests.sh"
            echo "  ./run-dusk-tests.sh --filter=AuthenticationTest"
            echo "  ./run-dusk-tests.sh --filter=testLoginSuccessful"
            echo "  ./run-dusk-tests.sh --group=authentication --stop-on-failure"
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            echo "Use --help to see available options"
            exit 1
            ;;
    esac
done

print_header "DevFlow Pro - Dusk Test Suite"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker and try again."
    exit 1
fi

print_success "Docker is running"

# Check if docker-compose.dusk.yml exists
if [ ! -f "docker-compose.dusk.yml" ]; then
    print_error "docker-compose.dusk.yml not found!"
    exit 1
fi

print_success "Docker Compose configuration found"

# Copy environment file for Docker
if [ -f ".env.dusk.docker" ]; then
    cp .env.dusk.docker .env.dusk.testing
    print_success "Environment file configured for Docker"
else
    print_error ".env.dusk.docker file not found!"
    exit 1
fi

# Stop any existing containers
print_info "Stopping any existing Dusk containers..."
docker compose -f docker-compose.dusk.yml down -v > /dev/null 2>&1 || true

# Start Docker containers
print_info "Starting Docker containers (MySQL, Selenium, App)..."
docker compose -f docker-compose.dusk.yml up -d

# Wait for MySQL to be ready
print_info "Waiting for MySQL to be ready..."
MAX_TRIES=30
TRIES=0
until docker compose -f docker-compose.dusk.yml exec -T mysql mysqladmin ping -h localhost -u root -proot --silent > /dev/null 2>&1; do
    TRIES=$((TRIES+1))
    if [ $TRIES -ge $MAX_TRIES ]; then
        print_error "MySQL failed to start within expected time"
        docker compose -f docker-compose.dusk.yml logs mysql
        docker compose -f docker-compose.dusk.yml down
        exit 1
    fi
    echo -n "."
    sleep 2
done
echo ""
print_success "MySQL is ready"

# Wait for Selenium to be ready
print_info "Waiting for Selenium to be ready..."
TRIES=0
until curl -sSf http://localhost:4444/wd/hub/status > /dev/null 2>&1; do
    TRIES=$((TRIES+1))
    if [ $TRIES -ge $MAX_TRIES ]; then
        print_error "Selenium failed to start within expected time"
        docker compose -f docker-compose.dusk.yml logs selenium
        docker compose -f docker-compose.dusk.yml down
        exit 1
    fi
    echo -n "."
    sleep 2
done
echo ""
print_success "Selenium is ready"

# Wait for application to be ready
print_info "Waiting for application to be ready..."
TRIES=0
until curl -sSf http://localhost:8088/up > /dev/null 2>&1; do
    TRIES=$((TRIES+1))
    if [ $TRIES -ge $MAX_TRIES ]; then
        print_error "Application failed to start within expected time"
        docker compose -f docker-compose.dusk.yml logs app
        docker compose -f docker-compose.dusk.yml down
        exit 1
    fi
    echo -n "."
    sleep 2
done
echo ""
print_success "Application is ready"

# Run migrations
print_info "Running database migrations..."
docker compose -f docker-compose.dusk.yml exec -T app php artisan migrate:fresh --seed --force
print_success "Database migrations completed"

# Create necessary directories
print_info "Creating test output directories..."
docker compose -f docker-compose.dusk.yml exec -T app mkdir -p tests/Browser/screenshots
docker compose -f docker-compose.dusk.yml exec -T app mkdir -p tests/Browser/console
print_success "Test directories created"

# Build test command
TEST_COMMAND="php artisan dusk"

if [ -n "$FILTER" ]; then
    TEST_COMMAND="$TEST_COMMAND --filter=$FILTER"
fi

if [ -n "$STOP_ON_FAILURE" ]; then
    TEST_COMMAND="$TEST_COMMAND $STOP_ON_FAILURE"
fi

if [ -n "$GROUP" ]; then
    TEST_COMMAND="$TEST_COMMAND $GROUP"
fi

# Display test information
print_header "Running Dusk Tests"
if [ -n "$FILTER" ]; then
    print_info "Filter: $FILTER"
fi
if [ -n "$GROUP" ]; then
    print_info "Group: $GROUP"
fi
print_info "VNC Viewer: http://localhost:7900 (no password)"
print_info "You can view tests running in real-time!"
echo ""

# Run Dusk tests
docker compose -f docker-compose.dusk.yml exec -T app $TEST_COMMAND
TEST_EXIT_CODE=$?

# Show results
echo ""
if [ $TEST_EXIT_CODE -eq 0 ]; then
    print_header "Test Results: SUCCESS"
    print_success "All tests passed!"
else
    print_header "Test Results: FAILURE"
    print_error "Some tests failed. Check the output above for details."
    print_info "Screenshots saved in: tests/Browser/screenshots/"
    print_info "Console logs saved in: tests/Browser/console/"
fi

# Ask user if they want to keep containers running
echo ""
read -p "Keep Docker containers running? (y/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_info "Stopping Docker containers..."
    docker compose -f docker-compose.dusk.yml down
    print_success "Docker containers stopped"
else
    print_info "Containers are still running. Use 'docker compose -f docker-compose.dusk.yml down' to stop them."
    print_info "VNC Viewer: http://localhost:7900"
    print_info "Selenium Grid: http://localhost:4444"
fi

# Clean up temporary env file
rm -f .env.dusk.testing

exit $TEST_EXIT_CODE
