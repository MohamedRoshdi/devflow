#!/bin/bash

# DevFlow Pro - Browser Test Database Seeder Script
# This script prepares the test database for browser tests

set -e

echo "=================================================="
echo "  DevFlow Pro - Browser Test Database Setup"
echo "=================================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: Must be run from the project root directory${NC}"
    exit 1
fi

echo -e "${YELLOW}Step 1: Running migrations on test database...${NC}"
php artisan migrate --env=testing

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migrations completed successfully${NC}"
else
    echo -e "${RED}✗ Migration failed${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 2: Seeding browser test data...${NC}"
php artisan db:seed --class=BrowserTestSeeder --env=testing

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Browser test data seeded successfully${NC}"
else
    echo -e "${RED}✗ Seeding failed${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 3: Verifying test data...${NC}"
echo ""

# Verify data using tinker
php artisan tinker --env=testing << EOF
echo "Users: " . App\Models\User::count() . PHP_EOL;
echo "Servers: " . App\Models\Server::count() . PHP_EOL;
echo "Projects: " . App\Models\Project::count() . PHP_EOL;
echo "Deployments: " . App\Models\Deployment::count() . PHP_EOL;
echo "Domains: " . App\Models\Domain::count() . PHP_EOL;
exit
EOF

echo ""
echo -e "${GREEN}=================================================="
echo "  ✓ Browser Test Database Setup Complete!"
echo -e "==================================================${NC}"
echo ""
echo "Test Credentials:"
echo "  Email: admin@devflow.test"
echo "  Password: password"
echo ""
echo "You can now run browser tests:"
echo "  php artisan dusk"
echo "  php artisan dusk tests/Browser/DeploymentShowTest.php"
echo "  php artisan dusk tests/Browser/DomainManagerTest.php"
echo ""
