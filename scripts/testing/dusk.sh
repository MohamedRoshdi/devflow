#!/bin/bash
# DevFlow Pro - Dusk Browser Test Runner
# Usage: ./scripts/dusk.sh [command] [options]
#
# Commands:
#   build     - Build Docker images
#   up        - Start containers
#   down      - Stop and remove containers
#   test      - Run all Dusk tests
#   test:file - Run specific test file (e.g., ./scripts/dusk.sh test:file tests/Browser/LoginTest.php)
#   logs      - Show container logs
#   shell     - Open shell in app container
#   vnc       - Show VNC URL to watch tests in real-time
#   clean     - Remove containers, volumes, and images
#   status    - Show container status
#   help      - Show this help message

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Project root directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
COMPOSE_FILE="$PROJECT_ROOT/docker-compose.dusk.yml"

# Functions
print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  DevFlow Pro - Dusk Browser Tests${NC}"
    echo -e "${BLUE}========================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}→ $1${NC}"
}

check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi

    if ! docker info &> /dev/null; then
        print_error "Docker daemon is not running or you don't have permission."
        echo "Try: sudo usermod -aG docker \$USER && newgrp docker"
        exit 1
    fi
}

build() {
    print_info "Building Docker images..."
    docker compose -f "$COMPOSE_FILE" build --no-cache
    print_success "Build completed!"
}

up() {
    print_info "Starting containers..."
    docker compose -f "$COMPOSE_FILE" up -d

    print_info "Waiting for services to be healthy..."

    # Wait for MySQL
    echo -n "  MySQL: "
    for i in {1..60}; do
        if docker compose -f "$COMPOSE_FILE" exec -T mysql mysqladmin ping -h localhost -u root -proot &> /dev/null; then
            echo -e "${GREEN}ready${NC}"
            break
        fi
        echo -n "."
        sleep 2
    done

    # Wait for Selenium
    echo -n "  Selenium: "
    for i in {1..30}; do
        if curl -sf http://localhost:4444/wd/hub/status &> /dev/null; then
            echo -e "${GREEN}ready${NC}"
            break
        fi
        echo -n "."
        sleep 2
    done

    # Wait for App
    echo -n "  App: "
    for i in {1..60}; do
        if curl -sf http://localhost:8088/up &> /dev/null; then
            echo -e "${GREEN}ready${NC}"
            break
        fi
        echo -n "."
        sleep 2
    done

    print_success "All containers are running!"
    echo ""
    echo "Access points:"
    echo "  - App: http://localhost:8088"
    echo "  - Selenium Hub: http://localhost:4444"
    echo "  - VNC Viewer: http://localhost:7900 (watch tests in real-time)"
}

down() {
    print_info "Stopping containers..."
    docker compose -f "$COMPOSE_FILE" down
    print_success "Containers stopped!"
}

run_tests() {
    local test_file="$1"

    print_info "Running Dusk tests..."

    # Ensure containers are running
    if ! docker compose -f "$COMPOSE_FILE" ps --status running | grep -q "devflow_dusk_app"; then
        print_info "Containers not running. Starting them first..."
        up
    fi

    # Run migrations and seed
    print_info "Preparing database..."
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan migrate:fresh --seed --force

    # Clear caches
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan config:clear
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan cache:clear

    # Run Dusk tests
    if [ -n "$test_file" ]; then
        print_info "Running: $test_file"
        docker compose -f "$COMPOSE_FILE" exec -T app php artisan dusk "$test_file"
    else
        docker compose -f "$COMPOSE_FILE" exec -T app php artisan dusk
    fi

    print_success "Tests completed!"
    echo ""
    echo "Screenshots saved in: tests/Browser/screenshots/"
    echo "Console logs saved in: tests/Browser/console/"
}

show_logs() {
    local service="${1:-app}"
    docker compose -f "$COMPOSE_FILE" logs -f "$service"
}

open_shell() {
    docker compose -f "$COMPOSE_FILE" exec app bash
}

show_vnc() {
    echo ""
    echo "VNC Viewer URL: http://localhost:7900"
    echo ""
    echo "Open this URL in your browser to watch tests running in real-time."
    echo "No password required (SE_VNC_NO_PASSWORD=1)."
    echo ""
}

clean() {
    print_info "Cleaning up Docker resources..."
    docker compose -f "$COMPOSE_FILE" down -v --rmi local --remove-orphans
    print_success "Cleanup completed!"
}

status() {
    print_header
    echo ""
    docker compose -f "$COMPOSE_FILE" ps
}

show_help() {
    print_header
    echo ""
    echo "Usage: $0 [command] [options]"
    echo ""
    echo "Commands:"
    echo "  build       Build Docker images"
    echo "  up          Start all containers"
    echo "  down        Stop and remove containers"
    echo "  test        Run all Dusk browser tests"
    echo "  test:file   Run specific test file"
    echo "              Example: $0 test:file tests/Browser/LoginTest.php"
    echo "  logs [svc]  Show logs (default: app, options: app, mysql, selenium, redis)"
    echo "  shell       Open bash shell in app container"
    echo "  vnc         Show VNC URL for watching tests"
    echo "  clean       Remove all containers, volumes, and images"
    echo "  status      Show container status"
    echo "  help        Show this help message"
    echo ""
    echo "Quick Start:"
    echo "  1. $0 build    # Build images (first time only)"
    echo "  2. $0 up       # Start containers"
    echo "  3. $0 test     # Run tests"
    echo "  4. $0 down     # Stop containers when done"
    echo ""
    echo "Watch tests in real-time:"
    echo "  Open http://localhost:7900 in your browser after starting containers"
    echo ""
}

# Main
check_docker

case "${1:-help}" in
    build)
        print_header
        build
        ;;
    up)
        print_header
        up
        ;;
    down)
        print_header
        down
        ;;
    test)
        print_header
        run_tests
        ;;
    test:file)
        print_header
        run_tests "$2"
        ;;
    logs)
        show_logs "$2"
        ;;
    shell)
        open_shell
        ;;
    vnc)
        show_vnc
        ;;
    clean)
        print_header
        clean
        ;;
    status)
        status
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        print_error "Unknown command: $1"
        show_help
        exit 1
        ;;
esac
