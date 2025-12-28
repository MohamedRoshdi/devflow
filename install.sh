#!/bin/bash

# =============================================================================
# DevFlow Pro - Full Installation Script
# Multi-Project Deployment & Management System
# Installs PHP 8.4, PostgreSQL 16/MySQL 8, Redis, Node.js 22
# For Ubuntu/Debian-based systems
#
# Usage:
#   ./install.sh                                              # Development mode
#   ./install.sh --production --domain devflow.example.com --email admin@example.com
#
# Options:
#   --production    Enable production mode (security hardening, skip dev tools)
#   --domain        Domain name for SSL certificate (required for production)
#   --email         Email for Let's Encrypt notifications (required for production)
#   --skip-ssl      Skip SSL setup in production (if using external SSL/proxy)
#   --db-driver     Database driver: pgsql or mysql (default: pgsql)
#   --db-password   Set custom database password
#   --help          Show this help message
# =============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# =============================================================================
# Default Configuration
# =============================================================================
PRODUCTION_MODE=false
DOMAIN=""
EMAIL=""
SKIP_SSL=false
DB_DRIVER="pgsql"
DB_NAME="devflow_pro"
DB_USER="devflow"
DB_PASSWORD="devflow_secret_2024"
DB_TEST_NAME="devflow_pro_test"

# Get script directory (project root)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# =============================================================================
# Parse Command Line Arguments
# =============================================================================
show_help() {
    echo -e "${CYAN}DevFlow Pro Installation Script${NC}"
    echo ""
    echo "Usage: ./install.sh [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --production          Enable production mode with security hardening"
    echo "  --domain DOMAIN       Domain name for SSL (required for --production)"
    echo "  --email EMAIL         Email for Let's Encrypt (required for --production)"
    echo "  --skip-ssl            Skip SSL setup (use if behind reverse proxy)"
    echo "  --db-driver DRIVER    Database: pgsql (default) or mysql"
    echo "  --db-password PASS    Set custom database password"
    echo "  --help                Show this help message"
    echo ""
    echo -e "Examples:"
    echo -e "  ${GREEN}Development:${NC}"
    echo "    ./install.sh"
    echo ""
    echo -e "  ${GREEN}Production with PostgreSQL:${NC}"
    echo "    ./install.sh --production --domain devflow.example.com --email admin@example.com"
    echo ""
    echo -e "  ${GREEN}Production with MySQL:${NC}"
    echo "    ./install.sh --production --domain devflow.example.com --email admin@example.com --db-driver mysql"
    echo ""
    echo -e "  ${GREEN}Production behind proxy (no SSL):${NC}"
    echo "    ./install.sh --production --domain devflow.example.com --skip-ssl"
    echo ""
    exit 0
}

while [[ $# -gt 0 ]]; do
    case $1 in
        --production)
            PRODUCTION_MODE=true
            shift
            ;;
        --domain)
            DOMAIN="$2"
            shift 2
            ;;
        --email)
            EMAIL="$2"
            shift 2
            ;;
        --skip-ssl)
            SKIP_SSL=true
            shift
            ;;
        --db-driver)
            DB_DRIVER="$2"
            if [[ "$DB_DRIVER" != "pgsql" && "$DB_DRIVER" != "mysql" ]]; then
                echo -e "${RED}Error: --db-driver must be 'pgsql' or 'mysql'${NC}"
                exit 1
            fi
            shift 2
            ;;
        --db-password)
            DB_PASSWORD="$2"
            shift 2
            ;;
        --help|-h)
            show_help
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Validate production requirements
if [ "$PRODUCTION_MODE" = true ]; then
    if [ -z "$DOMAIN" ]; then
        echo -e "${RED}Error: --domain is required for production mode${NC}"
        echo "Example: ./install.sh --production --domain devflow.example.com --email admin@example.com"
        exit 1
    fi
    if [ -z "$EMAIL" ] && [ "$SKIP_SSL" = false ]; then
        echo -e "${RED}Error: --email is required for SSL certificate${NC}"
        echo "Use --skip-ssl if you're behind a reverse proxy with SSL termination"
        exit 1
    fi
    # Generate secure password for production if using default
    if [ "$DB_PASSWORD" = "devflow_secret_2024" ]; then
        DB_PASSWORD=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 32)
        echo -e "${YELLOW}[!] Generated secure database password (will be shown at end)${NC}"
    fi
fi

# =============================================================================
# Display Installation Mode
# =============================================================================
echo -e "${MAGENTA}"
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                                                                ║"
echo "║           ██████╗ ███████╗██╗   ██╗███████╗██╗      ██████╗   ║"
echo "║           ██╔══██╗██╔════╝██║   ██║██╔════╝██║     ██╔═══██╗  ║"
echo "║           ██║  ██║█████╗  ██║   ██║█████╗  ██║     ██║   ██║  ║"
echo "║           ██║  ██║██╔══╝  ╚██╗ ██╔╝██╔══╝  ██║     ██║   ██║  ║"
echo "║           ██████╔╝███████╗ ╚████╔╝ ██║     ███████╗╚██████╔╝  ║"
echo "║           ╚═════╝ ╚══════╝  ╚═══╝  ╚═╝     ╚══════╝ ╚═════╝   ║"
echo "║                         PRO                                    ║"
echo "║                                                                ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

if [ "$PRODUCTION_MODE" = true ]; then
    echo -e "${BLUE}=============================================="
    echo "   PRODUCTION Installation"
    echo -e "==============================================${NC}"
    echo ""
    echo -e "${CYAN}Mode:${NC}     Production (Security Hardened)"
    echo -e "${CYAN}Domain:${NC}   ${DOMAIN}"
    echo -e "${CYAN}Database:${NC} ${DB_DRIVER}"
    echo -e "${CYAN}SSL:${NC}      $([ "$SKIP_SSL" = true ] && echo 'Skipped (External/Proxy)' || echo 'Let'\''s Encrypt')"
    echo ""
    echo -e "${GREEN}Production features enabled:${NC}"
    echo "  ✓ UFW Firewall (ports 22, 80, 443 only)"
    echo "  ✓ Fail2Ban (brute force protection)"
    if [ "$SKIP_SSL" = false ]; then
        echo "  ✓ SSL/HTTPS with Let's Encrypt"
    fi
    echo "  ✓ PHP OPcache + JIT optimizations"
    echo "  ✓ Nginx security headers & gzip"
    echo "  ✓ Secure file permissions"
    echo "  ✓ Composer --no-dev"
    echo ""
    echo -e "${YELLOW}Dev tools skipped:${NC}"
    echo "  ✗ Mailpit (use real SMTP in production)"
else
    echo -e "${BLUE}=============================================="
    echo "   DEVELOPMENT Installation"
    echo -e "==============================================${NC}"
    echo ""
    echo -e "${CYAN}Mode:${NC}     Development (with dev tools)"
    echo -e "${CYAN}Database:${NC} ${DB_DRIVER}"
    echo ""
    echo -e "${GREEN}Development features:${NC}"
    echo "  ✓ Mailpit (email testing)"
    echo "  ✓ Debug mode enabled"
    echo "  ✓ Full composer dependencies"
fi
echo ""
echo "Project directory: ${SCRIPT_DIR}"

# Confirm before proceeding in production
if [ "$PRODUCTION_MODE" = true ]; then
    echo ""
    echo -e "${YELLOW}⚠ This will configure your server for PRODUCTION use.${NC}"
    echo -e "${YELLOW}  Firewall rules will be applied and dev tools skipped.${NC}"
    echo ""
    read -p "Continue with production installation? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Installation cancelled."
        exit 0
    fi
fi

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    echo -e "${RED}Please do not run this script as root. Run as regular user with sudo access.${NC}"
    exit 1
fi

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to print step
print_step() {
    echo -e "\n${GREEN}[STEP]${NC} $1"
}

# Function to print info
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

# Function to print warning
print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Function to print error
print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# =============================================================================
# Step 1: Update system packages
# =============================================================================
print_step "Updating system packages..."
sudo apt update && sudo apt upgrade -y

# =============================================================================
# Step 2: Install common dependencies
# =============================================================================
print_step "Installing common dependencies..."
sudo apt install -y \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    unzip \
    git \
    acl

# =============================================================================
# Step 3: Install PHP 8.4
# =============================================================================
print_step "Installing PHP 8.4..."

if command_exists php && php -v | grep -q "PHP 8.4"; then
    print_info "PHP 8.4 is already installed"
else
    # Add PHP repository
    sudo add-apt-repository -y ppa:ondrej/php
    sudo apt update

    # Install PHP 8.4 and extensions
    sudo apt install -y \
        php8.4 \
        php8.4-fpm \
        php8.4-cli \
        php8.4-common \
        php8.4-pgsql \
        php8.4-mysql \
        php8.4-mbstring \
        php8.4-xml \
        php8.4-curl \
        php8.4-zip \
        php8.4-gd \
        php8.4-intl \
        php8.4-bcmath \
        php8.4-opcache \
        php8.4-redis \
        php8.4-imagick \
        php8.4-exif \
        php8.4-soap

    # Set PHP 8.4 as default
    sudo update-alternatives --set php /usr/bin/php8.4

    print_info "PHP 8.4 installed successfully"
fi

php -v

# =============================================================================
# Step 4: Install Composer
# =============================================================================
print_step "Installing Composer..."

if command_exists composer; then
    print_info "Composer is already installed"
    composer self-update 2>/dev/null || true
else
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
    print_info "Composer installed successfully"
fi

composer --version

# =============================================================================
# Step 5: Install Database (PostgreSQL or MySQL)
# =============================================================================
if [ "$DB_DRIVER" = "pgsql" ]; then
    print_step "Installing PostgreSQL 16..."

    if command_exists psql && psql --version | grep -q "16"; then
        print_info "PostgreSQL 16 is already installed"
    else
        # Add PostgreSQL repository
        sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
        curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo gpg --dearmor -o /etc/apt/trusted.gpg.d/postgresql.gpg 2>/dev/null || true
        sudo apt update

        # Install PostgreSQL 16
        sudo apt install -y postgresql-16 postgresql-contrib-16

        # Start and enable PostgreSQL
        sudo systemctl start postgresql
        sudo systemctl enable postgresql

        print_info "PostgreSQL 16 installed successfully"
    fi

    psql --version

    # Configure PostgreSQL database
    print_step "Configuring PostgreSQL database..."

    sudo -u postgres psql <<EOF
DO \$\$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '${DB_USER}') THEN
        CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASSWORD}';
    END IF;
END
\$\$;

SELECT 'CREATE DATABASE ${DB_NAME} OWNER ${DB_USER}'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '${DB_NAME}')\gexec

SELECT 'CREATE DATABASE ${DB_TEST_NAME} OWNER ${DB_USER}'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '${DB_TEST_NAME}')\gexec

GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};
GRANT ALL PRIVILEGES ON DATABASE ${DB_TEST_NAME} TO ${DB_USER};
ALTER USER ${DB_USER} CREATEDB;
EOF

    print_info "PostgreSQL databases configured: ${DB_NAME}, ${DB_TEST_NAME}"

else
    print_step "Installing MySQL 8.0..."

    if command_exists mysql && mysql --version | grep -q "8.0"; then
        print_info "MySQL 8.0 is already installed"
    else
        sudo apt install -y mysql-server mysql-client

        # Start and enable MySQL
        sudo systemctl start mysql
        sudo systemctl enable mysql

        print_info "MySQL 8.0 installed successfully"
    fi

    mysql --version

    # Configure MySQL database
    print_step "Configuring MySQL database..."

    sudo mysql <<EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS ${DB_TEST_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON ${DB_TEST_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

    print_info "MySQL databases configured: ${DB_NAME}, ${DB_TEST_NAME}"
fi

# =============================================================================
# Step 6: Install Redis
# =============================================================================
print_step "Installing Redis..."

if command_exists redis-server; then
    print_info "Redis is already installed"
else
    sudo apt install -y redis-server

    # Configure Redis to start on boot
    sudo systemctl start redis-server
    sudo systemctl enable redis-server

    print_info "Redis installed successfully"
fi

redis-server --version

# =============================================================================
# Step 7: Install Node.js 22
# =============================================================================
print_step "Installing Node.js 22..."

if command_exists node && node -v | grep -q "v22"; then
    print_info "Node.js 22 is already installed"
else
    # Install Node.js 22 via NodeSource
    curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
    sudo apt install -y nodejs

    print_info "Node.js 22 installed successfully"
fi

node -v
npm -v

# =============================================================================
# Step 8: Install Nginx
# =============================================================================
print_step "Installing Nginx..."

if command_exists nginx; then
    print_info "Nginx is already installed"
else
    sudo apt install -y nginx

    # Start and enable Nginx
    sudo systemctl start nginx
    sudo systemctl enable nginx

    print_info "Nginx installed successfully"
fi

nginx -v

# =============================================================================
# Step 9: Install image optimization tools
# =============================================================================
print_step "Installing image optimization tools..."
sudo apt install -y jpegoptim optipng pngquant gifsicle

# =============================================================================
# Step 10: Fix ownership of project files
# =============================================================================
print_step "Fixing file ownership..."
sudo chown -R ${USER}:${USER} "${SCRIPT_DIR}"
print_info "File ownership set to ${USER}"

# =============================================================================
# Step 11: Setup project
# =============================================================================
print_step "Setting up DevFlow Pro project..."

cd "$SCRIPT_DIR"

# Install PHP dependencies
print_info "Installing Composer dependencies..."
if [ "$PRODUCTION_MODE" = true ]; then
    composer install --no-dev --optimize-autoloader --no-interaction
else
    composer install
fi

# Install Node.js dependencies
print_info "Installing NPM dependencies..."
npm ci

# Build assets
print_info "Building frontend assets..."
npm run build

# =============================================================================
# Step 12: Configure environment
# =============================================================================
print_step "Configuring environment..."

if [ ! -f .env ]; then
    cp .env.example .env
    print_info "Created .env from .env.example"
fi

# Update .env with database settings
sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=${DB_DRIVER}/" .env
sed -i "s/^DB_HOST=.*/DB_HOST=127.0.0.1/" .env
if [ "$DB_DRIVER" = "pgsql" ]; then
    sed -i "s/^DB_PORT=.*/DB_PORT=5432/" .env
else
    sed -i "s/^DB_PORT=.*/DB_PORT=3306/" .env
fi
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env

# Update Redis settings
sed -i "s/^REDIS_HOST=.*/REDIS_HOST=127.0.0.1/" .env
sed -i "s/^REDIS_PORT=.*/REDIS_PORT=6379/" .env

# Configure based on environment mode
if [ "$PRODUCTION_MODE" = true ]; then
    # Production settings
    sed -i "s/^APP_ENV=.*/APP_ENV=production/" .env
    sed -i "s/^APP_DEBUG=.*/APP_DEBUG=false/" .env
    sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env

    # Use Redis for cache, sessions, queue in production
    sed -i "s/^CACHE_STORE=.*/CACHE_STORE=redis/" .env
    sed -i "s/^SESSION_DRIVER=.*/SESSION_DRIVER=redis/" .env
    sed -i "s/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/" .env

    # Security settings
    sed -i "s/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/" .env
    sed -i "s/^SESSION_ENCRYPT=.*/SESSION_ENCRYPT=true/" .env

    # Logging
    sed -i "s/^LOG_LEVEL=.*/LOG_LEVEL=warning/" .env

    print_info "Configured .env for PRODUCTION"
else
    # Development settings
    sed -i "s/^APP_ENV=.*/APP_ENV=local/" .env
    sed -i "s/^APP_DEBUG=.*/APP_DEBUG=true/" .env
    sed -i "s|^APP_URL=.*|APP_URL=http://devflow.test|" .env

    print_info "Configured .env for DEVELOPMENT"
fi

# Generate application key if not set
if grep -q "^APP_KEY=$" .env || grep -q "^APP_KEY=\"\"" .env; then
    php artisan key:generate
    print_info "Generated application key"
fi

# =============================================================================
# Step 13: Run migrations and seed
# =============================================================================
print_step "Running database migrations..."
php artisan migrate --force

print_step "Seeding initial data..."
php artisan db:seed --force 2>/dev/null || print_warning "Seeders not found or already run"

# =============================================================================
# Step 14: Set permissions for www-data access
# =============================================================================
print_step "Setting file permissions..."

# Make project readable by www-data
sudo chmod -R a+rX "${SCRIPT_DIR}"

# Make storage and cache writable
sudo chown -R ${USER}:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Use ACL for better permission management
sudo setfacl -R -m u:www-data:rX "${SCRIPT_DIR}" 2>/dev/null || true
sudo setfacl -R -m u:www-data:rwX "${SCRIPT_DIR}/storage" 2>/dev/null || true
sudo setfacl -R -m u:www-data:rwX "${SCRIPT_DIR}/bootstrap/cache" 2>/dev/null || true

print_info "Permissions configured"

# =============================================================================
# Step 15: Configure Nginx virtual host
# =============================================================================
print_step "Configuring Nginx virtual host..."

NGINX_CONF="/etc/nginx/sites-available/devflow-pro"
NGINX_LINK="/etc/nginx/sites-enabled/devflow-pro"

if [ "$PRODUCTION_MODE" = true ]; then
    # Production Nginx configuration with security headers
    sudo tee $NGINX_CONF > /dev/null <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};
    root ${SCRIPT_DIR}/public;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    index index.php;
    charset utf-8;

    # Logging
    access_log /var/log/nginx/devflow-pro.access.log;
    error_log /var/log/nginx/devflow-pro.error.log;

    # Handle Laravel routes
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # PHP-FPM configuration with production buffers
    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;

        # Production timeouts
        fastcgi_connect_timeout 60s;
        fastcgi_send_timeout 300s;
        fastcgi_read_timeout 300s;

        # Production buffers
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets aggressively
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg|eot|webp|avif)\$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
        access_log off;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript
               application/xml application/rss+xml application/atom+xml image/svg+xml;

    client_max_body_size 100M;
}
NGINX
    print_info "Production Nginx config created for ${DOMAIN}"

else
    # Development Nginx configuration
    sudo tee $NGINX_CONF > /dev/null <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name devflow.test;
    root ${SCRIPT_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    # Handle Laravel routes
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # PHP-FPM configuration
    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg|eot)\$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    client_max_body_size 100M;
}
NGINX
    print_info "Development Nginx config created for devflow.test"
fi

# Enable the site
sudo ln -sf $NGINX_CONF $NGINX_LINK

# Remove default site if exists
sudo rm -f /etc/nginx/sites-enabled/default

# Test Nginx configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx

# Add hosts entry for development only
if [ "$PRODUCTION_MODE" = false ]; then
    if ! grep -q "devflow.test" /etc/hosts; then
        echo "127.0.0.1 devflow.test" | sudo tee -a /etc/hosts
        print_info "Added devflow.test to /etc/hosts"
    fi
    print_info "Nginx configured for http://devflow.test"
else
    print_info "Nginx configured for ${DOMAIN}"
fi

# =============================================================================
# Step 16: Install and configure Supervisor (Queue Worker)
# =============================================================================
print_step "Installing Supervisor for queue worker..."

if command_exists supervisorctl; then
    print_info "Supervisor is already installed"
else
    sudo apt install -y supervisor
    sudo systemctl start supervisor
    sudo systemctl enable supervisor
    print_info "Supervisor installed successfully"
fi

# Create queue worker configuration
sudo tee /etc/supervisor/conf.d/devflow-pro-queue.conf > /dev/null <<SUPERVISOR
[program:devflow-pro-queue]
process_name=%(program_name)s_%(process_num)02d
command=php ${SCRIPT_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${USER}
numprocs=2
redirect_stderr=true
stdout_logfile=${SCRIPT_DIR}/storage/logs/queue.log
stopwaitsecs=3600
SUPERVISOR

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start devflow-pro-queue:* 2>/dev/null || true

print_info "Queue worker configured (2 processes)"

# =============================================================================
# Step 17: Configure Laravel Scheduler (Cron)
# =============================================================================
print_step "Configuring Laravel scheduler..."

# Add cron job for Laravel scheduler
CRON_JOB="* * * * * cd ${SCRIPT_DIR} && php artisan schedule:run >> /dev/null 2>&1"

# Check if cron job already exists
if ! crontab -l 2>/dev/null | grep -q "${SCRIPT_DIR}.*schedule:run"; then
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    print_info "Laravel scheduler cron job added"
else
    print_info "Laravel scheduler cron job already exists"
fi

# =============================================================================
# Step 18: Install Mailpit (Email Testing) - Development Only
# =============================================================================
if [ "$PRODUCTION_MODE" = false ]; then
    print_step "Installing Mailpit for email testing..."

    if command_exists mailpit; then
        print_info "Mailpit is already installed"
    else
        # Download and install Mailpit
        MAILPIT_VERSION="v1.21.0"
        curl -sL "https://github.com/axllent/mailpit/releases/download/${MAILPIT_VERSION}/mailpit-linux-amd64.tar.gz" | sudo tar -xz -C /usr/local/bin mailpit
        sudo chmod +x /usr/local/bin/mailpit

        # Create systemd service for Mailpit
        sudo tee /etc/systemd/system/mailpit.service > /dev/null <<'MAILPIT_SERVICE'
[Unit]
Description=Mailpit - Email Testing Tool
After=network.target

[Service]
Type=simple
ExecStart=/usr/local/bin/mailpit --listen 0.0.0.0:8025 --smtp 0.0.0.0:1025
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
MAILPIT_SERVICE

        # Enable and start Mailpit
        sudo systemctl daemon-reload
        sudo systemctl enable mailpit
        sudo systemctl start mailpit

        print_info "Mailpit installed - Web UI: http://localhost:8025, SMTP: localhost:1025"
    fi

    # Update .env for Mailpit
    sed -i "s/^MAIL_MAILER=.*/MAIL_MAILER=smtp/" .env
    sed -i "s/^MAIL_HOST=.*/MAIL_HOST=127.0.0.1/" .env
    sed -i "s/^MAIL_PORT=.*/MAIL_PORT=1025/" .env
    sed -i "s/^MAIL_USERNAME=.*/MAIL_USERNAME=/" .env
    sed -i "s/^MAIL_PASSWORD=.*/MAIL_PASSWORD=/" .env
    sed -i "s/^MAIL_ENCRYPTION=.*/MAIL_ENCRYPTION=null/" .env

    print_info "Configured .env to use Mailpit"
else
    print_step "Skipping Mailpit (production mode)..."
    print_info "Configure real SMTP settings in .env for production"
fi

# =============================================================================
# PRODUCTION ONLY: Security & Performance Hardening
# =============================================================================
if [ "$PRODUCTION_MODE" = true ]; then

    # =========================================================================
    # Step 19: Configure UFW Firewall
    # =========================================================================
    print_step "Configuring UFW Firewall..."

    sudo apt install -y ufw

    # Reset and configure firewall rules
    sudo ufw --force reset

    # Default policies
    sudo ufw default deny incoming
    sudo ufw default allow outgoing

    # Allow essential ports
    sudo ufw allow 22/tcp comment 'SSH'
    sudo ufw allow 80/tcp comment 'HTTP'
    sudo ufw allow 443/tcp comment 'HTTPS'

    # Enable firewall
    sudo ufw --force enable

    print_info "UFW Firewall enabled (ports 22, 80, 443 open)"
    sudo ufw status

    # =========================================================================
    # Step 20: Install and Configure Fail2Ban
    # =========================================================================
    print_step "Installing Fail2Ban for brute force protection..."

    sudo apt install -y fail2ban

    # Create Laravel-specific jail configuration
    sudo tee /etc/fail2ban/jail.d/devflow.conf > /dev/null <<FAIL2BAN_JAIL
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 3
bantime = 3600

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
port = http,https
logpath = /var/log/nginx/devflow-pro.error.log
maxretry = 5

[devflow-auth]
enabled = true
filter = devflow-auth
port = http,https
logpath = ${SCRIPT_DIR}/storage/logs/laravel.log
maxretry = 5
bantime = 3600
findtime = 600
FAIL2BAN_JAIL

    # Create DevFlow authentication filter
    sudo tee /etc/fail2ban/filter.d/devflow-auth.conf > /dev/null <<'FAIL2BAN_FILTER'
[Definition]
failregex = ^.*"POST.*/login.*" 401.*$
            ^.*"POST.*/login.*" 422.*$
            ^.*Failed login attempt.*IP: <HOST>.*$
            ^.*Authentication attempt failed.*<HOST>.*$
ignoreregex =
FAIL2BAN_FILTER

    # Restart Fail2Ban
    sudo systemctl restart fail2ban
    sudo systemctl enable fail2ban

    print_info "Fail2Ban installed and configured"
    sudo fail2ban-client status

    # =========================================================================
    # Step 21: Configure Let's Encrypt SSL
    # =========================================================================
    if [ "$SKIP_SSL" = false ]; then
        print_step "Installing Let's Encrypt SSL certificate..."

        # Install Certbot
        sudo apt install -y certbot python3-certbot-nginx

        # Obtain SSL certificate
        print_info "Requesting SSL certificate for ${DOMAIN}..."
        sudo certbot --nginx \
            -d ${DOMAIN} \
            -d www.${DOMAIN} \
            --non-interactive \
            --agree-tos \
            --email ${EMAIL} \
            --redirect

        # Verify auto-renewal
        sudo certbot renew --dry-run

        print_info "SSL certificate installed successfully!"
        print_info "Auto-renewal configured via certbot timer"
    else
        print_step "Skipping SSL setup (--skip-ssl flag used)..."
        print_info "Configure SSL manually or use reverse proxy"
    fi

    # =========================================================================
    # Step 22: PHP Production Optimizations
    # =========================================================================
    print_step "Applying PHP production optimizations..."

    # Create production PHP configuration
    sudo tee /etc/php/8.4/fpm/conf.d/99-devflow-production.ini > /dev/null <<'PHP_PRODUCTION'
; =============================================================================
; DevFlow Pro Production PHP Configuration
; =============================================================================

; Memory and execution
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
max_input_vars = 5000

; Upload limits
upload_max_filesize = 100M
post_max_size = 100M

; Security
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; OPcache (CRITICAL for production performance)
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 32
opcache.max_accelerated_files = 20000
opcache.revalidate_freq = 0
opcache.validate_timestamps = 0
opcache.save_comments = 1
opcache.fast_shutdown = 1
opcache.enable_cli = 0
opcache.jit = 1255
opcache.jit_buffer_size = 128M

; Realpath cache
realpath_cache_size = 4096K
realpath_cache_ttl = 600

; Session
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
PHP_PRODUCTION

    # Create PHP log directory
    sudo mkdir -p /var/log/php
    sudo chown www-data:www-data /var/log/php

    # Restart PHP-FPM with new config
    sudo systemctl restart php8.4-fpm

    print_info "PHP production optimizations applied"
    print_info "OPcache enabled with JIT compilation"

    # =========================================================================
    # Step 23: Secure File Permissions (Production)
    # =========================================================================
    print_step "Setting secure production file permissions..."

    # Set strict ownership
    sudo chown -R www-data:www-data "${SCRIPT_DIR}"

    # Set directory permissions (755 = rwxr-xr-x)
    sudo find "${SCRIPT_DIR}" -type d -exec chmod 755 {} \;

    # Set file permissions (644 = rw-r--r--)
    sudo find "${SCRIPT_DIR}" -type f -exec chmod 644 {} \;

    # Make artisan executable
    sudo chmod 750 "${SCRIPT_DIR}/artisan"

    # Secure .env file (only owner can read)
    sudo chmod 640 "${SCRIPT_DIR}/.env"

    # Storage and cache need to be writable
    sudo chmod -R 775 "${SCRIPT_DIR}/storage"
    sudo chmod -R 775 "${SCRIPT_DIR}/bootstrap/cache"

    # Ensure proper ownership for writable directories
    sudo chown -R www-data:www-data "${SCRIPT_DIR}/storage"
    sudo chown -R www-data:www-data "${SCRIPT_DIR}/bootstrap/cache"

    print_info "Secure file permissions applied"

fi
# END PRODUCTION ONLY SECTION

# =============================================================================
# Step 24: Clear and rebuild caches
# =============================================================================
print_step "Optimizing application..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache 2>/dev/null || true

# Create storage link
php artisan storage:link 2>/dev/null || true

# Restart PHP-FPM to pick up changes
sudo systemctl restart php8.4-fpm

# =============================================================================
# Step 25: Verify installation
# =============================================================================
print_step "Verifying installation..."

# Test the site
if [ "$PRODUCTION_MODE" = true ]; then
    TEST_URL="https://${DOMAIN}/"
else
    TEST_URL="http://devflow.test/"
fi

HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "${TEST_URL}" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    print_info "Site is accessible: HTTP $HTTP_CODE"
else
    print_warning "Site returned HTTP $HTTP_CODE - may need manual verification"
fi

# =============================================================================
# Complete!
# =============================================================================
echo -e "\n${GREEN}"
echo "╔════════════════════════════════════════════════════════════════╗"
if [ "$PRODUCTION_MODE" = true ]; then
    echo "║           PRODUCTION Installation Complete!                    ║"
else
    echo "║           DEVELOPMENT Installation Complete!                   ║"
fi
echo "╚════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo -e "${BLUE}Installed Components:${NC}"
echo "  - PHP $(php -v | head -1 | cut -d' ' -f2)"
echo "  - Composer $(composer --version 2>/dev/null | grep -oP 'Composer version \K[0-9.]+' || echo 'installed')"
if [ "$DB_DRIVER" = "pgsql" ]; then
    echo "  - PostgreSQL $(psql --version | cut -d' ' -f3)"
else
    echo "  - MySQL $(mysql --version | grep -oP '[0-9]+\.[0-9]+\.[0-9]+' | head -1)"
fi
echo "  - Redis $(redis-server --version | cut -d'=' -f2 | cut -d' ' -f1)"
echo "  - Node.js $(node -v)"
echo "  - NPM $(npm -v)"
echo "  - Nginx $(nginx -v 2>&1 | cut -d'/' -f2)"
echo "  - Supervisor (queue worker)"
if [ "$PRODUCTION_MODE" = false ]; then
    echo "  - Mailpit (email testing)"
fi

echo -e "\n${BLUE}Database Configuration:${NC}"
echo "  - Driver: ${DB_DRIVER}"
echo "  - Database: ${DB_NAME}"
if [ "$PRODUCTION_MODE" = false ]; then
    echo "  - Test Database: ${DB_TEST_NAME}"
fi
echo "  - User: ${DB_USER}"
if [ "$PRODUCTION_MODE" = true ]; then
    echo -e "  - Password: ${YELLOW}${DB_PASSWORD}${NC}"
    echo -e "  ${RED}⚠ SAVE THIS PASSWORD! It won't be shown again.${NC}"
fi

echo -e "\n${BLUE}Web Server:${NC}"
if [ "$PRODUCTION_MODE" = true ]; then
    echo "  - URL: https://${DOMAIN}"
    if [ "$SKIP_SSL" = false ]; then
        echo "  - SSL: Let's Encrypt (auto-renewal enabled)"
    else
        echo "  - SSL: Skipped (configure manually)"
    fi
else
    echo "  - URL: http://devflow.test"
fi
echo "  - Document Root: ${SCRIPT_DIR}/public"
echo "  - Nginx Config: /etc/nginx/sites-available/devflow-pro"

if [ "$PRODUCTION_MODE" = true ]; then
    echo -e "\n${BLUE}Security Features:${NC}"
    echo "  - UFW Firewall: Enabled (ports 22, 80, 443)"
    echo "  - Fail2Ban: Enabled (SSH + DevFlow auth protection)"
    echo "  - PHP OPcache: Enabled with JIT"
    echo "  - File Permissions: Hardened"
fi

echo -e "\n${BLUE}Background Services:${NC}"
echo "  - Queue Worker: 2 processes (Supervisor)"
echo "  - Scheduler: Running via cron (every minute)"
if [ "$PRODUCTION_MODE" = false ]; then
    echo "  - Mailpit: http://localhost:8025 (SMTP: 1025)"
fi
echo "  - Logs: ${SCRIPT_DIR}/storage/logs/queue.log"

echo -e "\n${GREEN}Access the application:${NC}"
if [ "$PRODUCTION_MODE" = true ]; then
    echo "  https://${DOMAIN}"
else
    echo "  http://devflow.test"
fi

echo -e "\n${GREEN}Manage queue workers:${NC}"
echo "  sudo supervisorctl status devflow-pro-queue:*"
echo "  sudo supervisorctl restart devflow-pro-queue:*"

if [ "$PRODUCTION_MODE" = true ]; then
    echo -e "\n${GREEN}Monitor security:${NC}"
    echo "  sudo ufw status"
    echo "  sudo fail2ban-client status"
    echo "  sudo fail2ban-client status devflow-auth"

    echo -e "\n${GREEN}SSL certificate renewal:${NC}"
    echo "  sudo certbot renew --dry-run"

    echo -e "\n${GREEN}View logs:${NC}"
    echo "  tail -f ${SCRIPT_DIR}/storage/logs/laravel.log"
    echo "  tail -f /var/log/nginx/devflow-pro.error.log"

    echo -e "\n${YELLOW}Important Production Notes:${NC}"
    echo "  1. Configure real SMTP settings in .env"
    echo "  2. Set up database backups"
    echo "  3. Monitor disk space and logs"
    echo "  4. Keep system updated (apt update && apt upgrade)"
else
    echo -e "\n${GREEN}To run tests:${NC}"
    echo "  php artisan test"

    echo -e "\n${GREEN}To watch frontend assets:${NC}"
    echo "  npm run dev"

    echo -e "\n${YELLOW}Note:${NC} For production deployment, use:"
    echo "  ./install.sh --production --domain yourdomain.com --email admin@yourdomain.com"
fi

echo ""
