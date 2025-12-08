#!/bin/bash

# DevFlow Pro - Test Setup Verification Script
# This script verifies that PostgreSQL testing is properly configured

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}======================================${NC}"
echo -e "${BLUE}DevFlow Pro - Test Setup Verification${NC}"
echo -e "${BLUE}======================================${NC}"
echo ""

# Check 1: Docker installed
echo -n "Checking Docker installation... "
if command -v docker &> /dev/null; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo -e "${RED}Docker is not installed. Please install Docker first.${NC}"
    exit 1
fi

# Check 2: Docker running
echo -n "Checking Docker is running... "
if docker info &> /dev/null; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo -e "${RED}Docker is not running. Please start Docker.${NC}"
    exit 1
fi

# Check 3: Docker Compose installed
echo -n "Checking Docker Compose... "
if command -v docker-compose &> /dev/null || docker compose version &> /dev/null; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo -e "${RED}Docker Compose is not installed.${NC}"
    exit 1
fi

# Check 4: Configuration files exist
echo -n "Checking docker-compose.testing.yml... "
if [ -f "docker-compose.testing.yml" ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo -e "${RED}docker-compose.testing.yml not found.${NC}"
    exit 1
fi

echo -n "Checking phpunit.xml... "
if [ -f "phpunit.xml" ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo -e "${RED}phpunit.xml not found.${NC}"
    exit 1
fi

echo -n "Checking .env.testing... "
if [ -f ".env.testing" ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo -e "${RED}.env.testing not found.${NC}"
    exit 1
fi

# Check 5: Configuration values
echo -n "Checking PostgreSQL config in phpunit.xml... "
if grep -q "pgsql_testing" phpunit.xml; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠${NC}"
    echo -e "${YELLOW}Warning: pgsql_testing not found in phpunit.xml${NC}"
fi

echo -n "Checking PostgreSQL config in .env.testing... "
if grep -q "pgsql_testing" .env.testing; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠${NC}"
    echo -e "${YELLOW}Warning: pgsql_testing not found in .env.testing${NC}"
fi

# Check 6: Port availability
echo -n "Checking port 5433 availability... "
if ! lsof -Pi :5433 -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠${NC}"
    echo -e "${YELLOW}Warning: Port 5433 is already in use (PostgreSQL test DB might be running)${NC}"
fi

# Check 7: PHP PDO PostgreSQL extension
echo -n "Checking PHP PDO PostgreSQL extension... "
if php -m | grep -q pdo_pgsql; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo -e "${RED}PHP PDO PostgreSQL extension is not installed.${NC}"
    echo -e "${YELLOW}Install with: sudo apt-get install php-pgsql${NC}"
    exit 1
fi

# Check 8: Composer dependencies
echo -n "Checking vendor directory... "
if [ -d "vendor" ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠${NC}"
    echo -e "${YELLOW}Vendor directory not found. Run: composer install${NC}"
fi

echo -n "Checking PHPUnit installation... "
if [ -f "vendor/bin/phpunit" ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo -e "${RED}PHPUnit not found. Run: composer install${NC}"
    exit 1
fi

# Check 9: Test runner script
echo -n "Checking run-tests.sh script... "
if [ -f "run-tests.sh" ] && [ -x "run-tests.sh" ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠${NC}"
    if [ -f "run-tests.sh" ]; then
        echo -e "${YELLOW}run-tests.sh exists but is not executable. Run: chmod +x run-tests.sh${NC}"
    else
        echo -e "${YELLOW}run-tests.sh not found.${NC}"
    fi
fi

# Check 10: Makefile
echo -n "Checking Makefile... "
if [ -f "Makefile" ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠${NC}"
    echo -e "${YELLOW}Makefile not found (optional).${NC}"
fi

echo ""
echo -e "${BLUE}======================================${NC}"
echo -e "${GREEN}Verification Complete!${NC}"
echo -e "${BLUE}======================================${NC}"
echo ""

# Offer to start test database
read -p "Would you like to start the test database now? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo -e "${BLUE}Starting PostgreSQL test database...${NC}"
    ./run-tests.sh start

    echo ""
    read -p "Would you like to run migrations? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo ""
        echo -e "${BLUE}Running migrations...${NC}"
        ./run-tests.sh migrate

        echo ""
        read -p "Would you like to run tests now? (y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            echo ""
            echo -e "${BLUE}Running tests...${NC}"
            ./run-tests.sh test
        fi
    fi
fi

echo ""
echo -e "${GREEN}Setup verification complete!${NC}"
echo ""
echo "Next steps:"
echo "  1. Start database: ./run-tests.sh start"
echo "  2. Run migrations: ./run-tests.sh migrate"
echo "  3. Run tests: ./run-tests.sh test"
echo ""
echo "For more information, see: TESTING_POSTGRESQL.md"
