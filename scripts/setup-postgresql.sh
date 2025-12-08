#!/bin/bash

###############################################################################
# DevFlow Pro - PostgreSQL Setup Script
# This script sets up PostgreSQL for DevFlow Pro production environment
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
DB_NAME="devflow_pro"
DB_USER="devflow"
DB_PASSWORD=""
DB_HOST="localhost"
DB_PORT="5432"

echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║     DevFlow Pro - PostgreSQL Setup Script                 ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   echo -e "${YELLOW}Warning: This script should not be run as root${NC}"
   echo -e "Please run as a regular user with sudo privileges"
   exit 1
fi

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check if PostgreSQL is installed
if ! command_exists psql; then
    echo -e "${YELLOW}PostgreSQL is not installed. Installing...${NC}"

    # Detect OS
    if [[ -f /etc/debian_version ]]; then
        # Debian/Ubuntu
        sudo apt update
        sudo apt install -y postgresql postgresql-contrib
    elif [[ -f /etc/redhat-release ]]; then
        # CentOS/RHEL
        sudo yum install -y postgresql-server postgresql-contrib
        sudo postgresql-setup initdb
    else
        echo -e "${RED}Unsupported operating system${NC}"
        exit 1
    fi

    # Start PostgreSQL service
    sudo systemctl start postgresql
    sudo systemctl enable postgresql

    echo -e "${GREEN}✓ PostgreSQL installed successfully${NC}"
else
    echo -e "${GREEN}✓ PostgreSQL is already installed${NC}"
fi

# Prompt for database password
echo ""
echo -e "${YELLOW}Please enter a secure password for the PostgreSQL user '${DB_USER}':${NC}"
read -s DB_PASSWORD
echo ""
echo -e "${YELLOW}Confirm password:${NC}"
read -s DB_PASSWORD_CONFIRM
echo ""

if [ "$DB_PASSWORD" != "$DB_PASSWORD_CONFIRM" ]; then
    echo -e "${RED}✗ Passwords do not match${NC}"
    exit 1
fi

if [ -z "$DB_PASSWORD" ]; then
    echo -e "${RED}✗ Password cannot be empty${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Password confirmed${NC}"

# Create PostgreSQL database and user
echo ""
echo -e "${YELLOW}Creating PostgreSQL database and user...${NC}"

sudo -u postgres psql <<EOF
-- Create user
DO \$\$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_user WHERE usename = '${DB_USER}') THEN
        CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASSWORD}';
    ELSE
        ALTER USER ${DB_USER} WITH PASSWORD '${DB_PASSWORD}';
    END IF;
END
\$\$;

-- Create database
SELECT 'CREATE DATABASE ${DB_NAME} OWNER ${DB_USER}'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '${DB_NAME}')\gexec

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};

-- Connect to the database and create extensions
\c ${DB_NAME}

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
CREATE EXTENSION IF NOT EXISTS "btree_gin";
CREATE EXTENSION IF NOT EXISTS "btree_gist";
CREATE EXTENSION IF NOT EXISTS "pg_stat_statements";

-- Set timezone
ALTER DATABASE ${DB_NAME} SET timezone TO 'UTC';

-- Grant schema privileges
GRANT ALL PRIVILEGES ON SCHEMA public TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO ${DB_USER};
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON FUNCTIONS TO ${DB_USER};

-- Create helper functions
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS \$func\$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
\$func\$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION generate_slug(input_text TEXT)
RETURNS TEXT AS \$func\$
BEGIN
    RETURN lower(
        regexp_replace(
            regexp_replace(input_text, '[^a-zA-Z0-9\s-]', '', 'g'),
            '\s+', '-', 'g'
        )
    );
END;
\$func\$ LANGUAGE plpgsql IMMUTABLE;

EOF

echo -e "${GREEN}✓ Database and user created successfully${NC}"

# Update .env file
echo ""
echo -e "${YELLOW}Updating .env file...${NC}"

ENV_FILE=".env"

if [ ! -f "$ENV_FILE" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${GREEN}✓ Created .env from .env.example${NC}"
    else
        echo -e "${RED}✗ .env.example not found${NC}"
        exit 1
    fi
fi

# Update database configuration in .env
sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=pgsql|g" $ENV_FILE
sed -i "s|DB_HOST=.*|DB_HOST=${DB_HOST}|g" $ENV_FILE
sed -i "s|DB_PORT=.*|DB_PORT=${DB_PORT}|g" $ENV_FILE
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|g" $ENV_FILE
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|g" $ENV_FILE
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|g" $ENV_FILE

echo -e "${GREEN}✓ .env file updated${NC}"

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" $ENV_FILE; then
    echo ""
    echo -e "${YELLOW}Generating application key...${NC}"
    php artisan key:generate
    echo -e "${GREEN}✓ Application key generated${NC}"
fi

# Run migrations
echo ""
echo -e "${YELLOW}Running database migrations...${NC}"
php artisan migrate --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migrations completed successfully${NC}"
else
    echo -e "${RED}✗ Migration failed${NC}"
    exit 1
fi

# Clear caches
echo ""
echo -e "${YELLOW}Clearing application caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

# Optimize for production
echo ""
echo -e "${YELLOW}Optimizing for production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Optimization completed${NC}"

# Test database connection
echo ""
echo -e "${YELLOW}Testing database connection...${NC}"
php artisan tinker --execute="echo 'Database connection successful'; \DB::connection()->getPdo();"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database connection successful${NC}"
else
    echo -e "${RED}✗ Database connection failed${NC}"
    exit 1
fi

# Display configuration summary
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              Setup Completed Successfully!                 ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Database Configuration:${NC}"
echo -e "  Host:     ${DB_HOST}"
echo -e "  Port:     ${DB_PORT}"
echo -e "  Database: ${DB_NAME}"
echo -e "  Username: ${DB_USER}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo -e "  1. Review your .env file for any additional configuration"
echo -e "  2. Run: ${GREEN}php artisan db:seed${NC} (if you have seeders)"
echo -e "  3. Start your application: ${GREEN}php artisan serve${NC}"
echo -e "  4. Or use Docker: ${GREEN}docker-compose -f docker-compose.production.yml up -d${NC}"
echo ""
echo -e "${YELLOW}Database Management:${NC}"
echo -e "  Connect to PostgreSQL: ${GREEN}psql -U ${DB_USER} -d ${DB_NAME}${NC}"
echo -e "  Backup database:       ${GREEN}pg_dump -U ${DB_USER} ${DB_NAME} > backup.sql${NC}"
echo -e "  Restore database:      ${GREEN}psql -U ${DB_USER} ${DB_NAME} < backup.sql${NC}"
echo ""
echo -e "${YELLOW}Documentation:${NC}"
echo -e "  Migration Guide: ${GREEN}docs/POSTGRESQL_MIGRATION.md${NC}"
echo ""
echo -e "${GREEN}Happy deploying! 🚀${NC}"
