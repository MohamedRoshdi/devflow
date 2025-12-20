#!/bin/bash

# DevFlow Pro - Test Runner Script
# This script helps manage PostgreSQL test database and run tests

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored messages
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker and try again."
        exit 1
    fi
}

# Function to start test database
start_db() {
    print_info "Starting PostgreSQL test database..."
    docker-compose -f docker-compose.testing.yml up -d postgres_test

    print_info "Waiting for PostgreSQL to be ready..."
    timeout=30
    counter=0
    while ! docker-compose -f docker-compose.testing.yml exec -T postgres_test pg_isready -U devflow_test -d devflow_test > /dev/null 2>&1; do
        sleep 1
        counter=$((counter + 1))
        if [ $counter -ge $timeout ]; then
            print_error "PostgreSQL failed to start within ${timeout} seconds"
            exit 1
        fi
    done

    print_success "PostgreSQL test database is ready!"
}

# Function to stop test database
stop_db() {
    print_info "Stopping PostgreSQL test database..."
    docker-compose -f docker-compose.testing.yml down
    print_success "PostgreSQL test database stopped."
}

# Function to reset test database
reset_db() {
    print_info "Resetting test database..."

    # Drop and recreate database
    docker-compose -f docker-compose.testing.yml exec -T postgres_test psql -U devflow_test -d postgres -c "DROP DATABASE IF EXISTS devflow_test;" 2>/dev/null || true
    docker-compose -f docker-compose.testing.yml exec -T postgres_test psql -U devflow_test -d postgres -c "CREATE DATABASE devflow_test;"

    print_success "Database reset complete."

    # Run migrations
    print_info "Running migrations..."
    php artisan migrate --env=testing --force
    print_success "Migrations complete."
}

# Function to run migrations
run_migrations() {
    print_info "Running migrations on test database..."
    php artisan migrate --env=testing --force
    print_success "Migrations complete."
}

# Function to run tests
run_tests() {
    local suite="$1"

    if [ -z "$suite" ]; then
        print_info "Running all tests..."
        vendor/bin/phpunit
    else
        print_info "Running ${suite} tests..."
        vendor/bin/phpunit --testsuite="${suite}"
    fi
}

# Function to show database logs
show_logs() {
    print_info "Showing PostgreSQL logs (Ctrl+C to exit)..."
    docker-compose -f docker-compose.testing.yml logs -f postgres_test
}

# Function to access database shell
db_shell() {
    print_info "Opening PostgreSQL shell..."
    docker-compose -f docker-compose.testing.yml exec postgres_test psql -U devflow_test -d devflow_test
}

# Function to show status
show_status() {
    print_info "Test Database Status:"
    docker-compose -f docker-compose.testing.yml ps
}

# Function to clean up
cleanup() {
    print_info "Cleaning up test environment..."
    docker-compose -f docker-compose.testing.yml down -v
    print_success "Cleanup complete. All test data removed."
}

# Main script logic
case "${1:-help}" in
    start)
        check_docker
        start_db
        ;;
    stop)
        stop_db
        ;;
    restart)
        check_docker
        stop_db
        start_db
        ;;
    reset)
        check_docker
        start_db
        reset_db
        ;;
    migrate)
        run_migrations
        ;;
    test)
        check_docker
        start_db
        run_tests "${2}"
        ;;
    unit)
        check_docker
        start_db
        run_tests "Unit"
        ;;
    feature)
        check_docker
        start_db
        run_tests "Feature"
        ;;
    browser)
        check_docker
        start_db
        run_tests "Browser"
        ;;
    performance)
        check_docker
        start_db
        run_tests "Performance"
        ;;
    security)
        check_docker
        start_db
        run_tests "Security"
        ;;
    logs)
        show_logs
        ;;
    shell)
        db_shell
        ;;
    status)
        show_status
        ;;
    cleanup)
        cleanup
        ;;
    help|*)
        echo "DevFlow Pro - Test Runner"
        echo ""
        echo "Usage: ./run-tests.sh [command]"
        echo ""
        echo "Commands:"
        echo "  start         Start PostgreSQL test database"
        echo "  stop          Stop PostgreSQL test database"
        echo "  restart       Restart PostgreSQL test database"
        echo "  reset         Reset database (drop, recreate, migrate)"
        echo "  migrate       Run migrations on test database"
        echo "  test [suite]  Run tests (all or specific suite)"
        echo "  unit          Run unit tests only"
        echo "  feature       Run feature tests only"
        echo "  browser       Run browser tests only"
        echo "  performance   Run performance tests only"
        echo "  security      Run security tests only"
        echo "  logs          Show PostgreSQL logs"
        echo "  shell         Open PostgreSQL shell"
        echo "  status        Show database status"
        echo "  cleanup       Stop database and remove all volumes"
        echo "  help          Show this help message"
        echo ""
        echo "Examples:"
        echo "  ./run-tests.sh start          # Start test database"
        echo "  ./run-tests.sh test           # Run all tests"
        echo "  ./run-tests.sh unit           # Run unit tests only"
        echo "  ./run-tests.sh reset          # Reset and migrate database"
        echo "  ./run-tests.sh cleanup        # Clean up everything"
        ;;
esac
