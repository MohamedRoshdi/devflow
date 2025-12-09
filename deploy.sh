#!/bin/bash

# DevFlow Pro Deployment Script
# Usage: ./deploy.sh [--rollback]
# Options:
#   --rollback    Restore the previous deployment

set -e

SERVER_IP="31.220.90.121"
SERVER_USER="root"
PROJECT_NAME="devflow-pro"
REMOTE_PATH="/var/www/$PROJECT_NAME"
BACKUP_PATH="/var/www/backups/$PROJECT_NAME"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check for rollback flag
if [ "$1" == "--rollback" ]; then
    echo -e "${YELLOW}🔄 Rolling back to previous deployment...${NC}"
    ssh $SERVER_USER@$SERVER_IP << 'ENDSSH'
    set -e

    PROJECT_NAME="devflow-pro"
    REMOTE_PATH="/var/www/$PROJECT_NAME"
    BACKUP_PATH="/var/www/backups/$PROJECT_NAME"

    # Find latest backup
    LATEST_BACKUP=$(ls -t $BACKUP_PATH/app_*.tar.gz 2>/dev/null | head -1)
    LATEST_DB_BACKUP=$(ls -t $BACKUP_PATH/db_*.sql 2>/dev/null | head -1)

    if [ -z "$LATEST_BACKUP" ]; then
        echo "❌ No backup found to rollback to!"
        exit 1
    fi

    echo "📦 Found backup: $LATEST_BACKUP"

    # Restore application files
    echo "📂 Restoring application files..."
    cd $REMOTE_PATH
    tar -xzf $LATEST_BACKUP -C $REMOTE_PATH

    # Restore database if backup exists
    if [ -n "$LATEST_DB_BACKUP" ] && [ -f "$LATEST_DB_BACKUP" ]; then
        echo "🗄️  Restoring database..."
        source .env
        mysql -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < $LATEST_DB_BACKUP
    fi

    # Clear caches
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Restart services
    systemctl restart php8.2-fpm 2>/dev/null || systemctl restart php8.4-fpm 2>/dev/null || true
    supervisorctl restart devflow-worker:* 2>/dev/null || true

    echo "✅ Rollback completed!"
ENDSSH
    exit 0
fi

echo -e "${BLUE}🚀 Starting DevFlow Pro Deployment to $SERVER_IP${NC}"
echo "================================================"
echo -e "${BLUE}📅 Deployment timestamp: $TIMESTAMP${NC}"

# Create deployment package
echo -e "${YELLOW}📦 Creating deployment package...${NC}"
tar -czf devflow-pro.tar.gz \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    --exclude='storage/framework/testing' \
    --exclude='bootstrap/cache' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='*.tar.gz' \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    --exclude='*.log' \
    --warning=no-file-changed \
    --warning=no-file-removed \
    . 2>/dev/null || true

# Check if tar succeeded in creating the file
if [ ! -f devflow-pro.tar.gz ]; then
    echo -e "${RED}❌ Failed to create deployment package${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Package created: devflow-pro.tar.gz${NC}"

# Copy to server
echo -e "${YELLOW}📤 Uploading to server...${NC}"
scp devflow-pro.tar.gz $SERVER_USER@$SERVER_IP:/tmp/

echo -e "${YELLOW}🔧 Setting up on server...${NC}"
ssh $SERVER_USER@$SERVER_IP << ENDSSH
set -e

PROJECT_NAME="devflow-pro"
REMOTE_PATH="/var/www/\$PROJECT_NAME"
BACKUP_PATH="/var/www/backups/\$PROJECT_NAME"
TIMESTAMP="$TIMESTAMP"

# Create backup directory
mkdir -p \$BACKUP_PATH

# ============================================
# BACKUP CURRENT DEPLOYMENT
# ============================================
if [ -d "\$REMOTE_PATH" ] && [ -f "\$REMOTE_PATH/artisan" ]; then
    echo "📦 Creating backup of current deployment..."

    # Backup application files (excluding vendor, node_modules, storage)
    cd \$REMOTE_PATH
    tar -czf \$BACKUP_PATH/app_\$TIMESTAMP.tar.gz \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='storage/logs/*' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        . 2>/dev/null || true

    # Backup database
    if [ -f "\$REMOTE_PATH/.env" ]; then
        source \$REMOTE_PATH/.env
        if [ -n "\$DB_DATABASE" ] && [ -n "\$DB_USERNAME" ]; then
            echo "🗄️  Backing up database..."
            mysqldump -u\$DB_USERNAME -p\$DB_PASSWORD \$DB_DATABASE > \$BACKUP_PATH/db_\$TIMESTAMP.sql 2>/dev/null || echo "   ⚠️  Database backup failed (may not exist yet)"
        fi
    fi

    # Keep only last 5 backups
    echo "🧹 Cleaning old backups (keeping last 5)..."
    ls -t \$BACKUP_PATH/app_*.tar.gz 2>/dev/null | tail -n +6 | xargs rm -f 2>/dev/null || true
    ls -t \$BACKUP_PATH/db_*.sql 2>/dev/null | tail -n +6 | xargs rm -f 2>/dev/null || true

    echo "✅ Backup completed: \$BACKUP_PATH/app_\$TIMESTAMP.tar.gz"
else
    echo "ℹ️  No existing deployment found, skipping backup"
fi

# Create directory if not exists
mkdir -p \$REMOTE_PATH

# Extract files
echo "📂 Extracting files..."
tar -xzf /tmp/devflow-pro.tar.gz -C \$REMOTE_PATH

# Navigate to project
cd \$REMOTE_PATH

# Create necessary directories
echo "📁 Creating required directories..."
mkdir -p storage/logs
mkdir -p storage/framework/{cache,sessions,views,testing}
mkdir -p bootstrap/cache
touch storage/logs/.gitkeep
touch storage/framework/cache/.gitkeep
touch storage/framework/sessions/.gitkeep
touch storage/framework/views/.gitkeep

# Install dependencies
echo "📥 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "📥 Installing Node dependencies..."
npm install

# Setup environment
if [ ! -f .env ]; then
    echo "⚙️  Setting up environment..."
    cp .env.example .env

    # Generate app key
    php artisan key:generate --no-interaction

    echo ""
    echo "⚠️  IMPORTANT: Edit .env file and configure:"
    echo "   - Database credentials"
    echo "   - Redis configuration"
    echo "   - Pusher credentials (optional)"
    echo ""
fi

# Update queue connection in .env if not set
echo "⚙️  Configuring queue driver..."
if ! grep -q "QUEUE_CONNECTION=database" .env 2>/dev/null; then
    if grep -q "QUEUE_CONNECTION=" .env; then
        sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/' .env
    else
        echo "QUEUE_CONNECTION=database" >> .env
    fi
    echo "✅ Queue driver set to database"
fi

# Ensure production environment settings
echo "🔒 Enforcing production environment settings..."
sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env
echo "✅ Production environment configured"

# Set permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data \$REMOTE_PATH
chmod -R 755 \$REMOTE_PATH
chmod -R 775 \$REMOTE_PATH/storage
chmod -R 775 \$REMOTE_PATH/bootstrap/cache

# Fix Git ownership issues
echo "🔧 Configuring Git safe directories..."
git config --global --add safe.directory \$REMOTE_PATH 2>/dev/null || true
git config --global --add safe.directory "/var/www/*" 2>/dev/null || true

# Build assets
echo "🎨 Building assets..."
npm run build

# Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Publish Livewire assets
echo "📦 Publishing Livewire assets..."
php artisan livewire:publish --assets 2>/dev/null || echo "   (Livewire assets published or not needed)"

# Clear ALL caches comprehensively
echo "🧹 Clearing ALL caches comprehensively..."
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear 2>/dev/null || true

# Clear Livewire-specific cache files
echo "🧹 Clearing Livewire cache files..."
rm -rf storage/framework/cache/livewire-components.php 2>/dev/null || true
rm -rf bootstrap/cache/livewire* 2>/dev/null || true
rm -rf storage/framework/livewire* 2>/dev/null || true

# Clear compiled blade views completely
echo "🧹 Clearing compiled blade views..."
rm -rf storage/framework/views/* 2>/dev/null || true

# Clear bootstrap cache files
echo "🧹 Clearing bootstrap cache..."
rm -rf bootstrap/cache/*.php 2>/dev/null || true

# Regenerate Composer autoload
echo "🔄 Regenerating Composer autoload..."
composer dump-autoload --optimize --no-scripts 2>/dev/null || true

# CRITICAL: Restart PHP-FPM BEFORE caching views (clears opcache first)
echo "🔄 Restarting PHP-FPM to clear opcache BEFORE caching..."
systemctl restart php8.2-fpm 2>/dev/null || systemctl restart php8.4-fpm 2>/dev/null || systemctl restart php-fpm 2>/dev/null || true
sleep 3

# NOW optimize Laravel for production (with fresh PHP code loaded)
echo "⚡ Optimizing Laravel for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

# Fix public directory permissions
echo "🔐 Setting public directory permissions..."
chown -R www-data:www-data public/
chmod -R 755 public/

# Install supervisor if not present
echo "📦 Checking supervisor installation..."
if ! command -v supervisorctl &> /dev/null; then
    echo "Installing supervisor..."
    apt-get update -qq
    apt-get install -y supervisor
    systemctl enable supervisor
    systemctl start supervisor
    echo "✅ Supervisor installed"
else
    echo "✅ Supervisor already installed"
fi

# Configure supervisor for queue workers
echo "⚙️  Configuring supervisor for queue workers..."
cat > /etc/supervisor/conf.d/devflow-queue.conf << 'EOF'
[program:devflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/devflow-pro/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/devflow-pro/storage/logs/worker.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stopwaitsecs=3600
EOF

echo "✅ Supervisor configuration created"

# Reload supervisor and start workers
echo "🔄 Reloading supervisor..."
supervisorctl reread
supervisorctl update
supervisorctl restart devflow-worker:* 2>/dev/null || supervisorctl start devflow-worker:*

echo "✅ Queue workers started"

# Setup Laravel scheduler cron job
echo "⏰ Setting up Laravel scheduler..."
CRON_CMD="* * * * * cd /var/www/devflow-pro && php artisan schedule:run >> /dev/null 2>&1"

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    echo "✅ Scheduler cron job already exists"
else
    # Add cron job
    (crontab -l 2>/dev/null; echo "\$CRON_CMD") | crontab -
    echo "✅ Scheduler cron job added"
fi

# Restart queue workers gracefully
echo "🔄 Restarting queue workers..."
php artisan queue:restart

# ============================================
# HEALTH CHECK
# ============================================
echo ""
echo "🏥 Running health check..."
sleep 2

# Get APP_URL from .env
source .env
APP_URL=\${APP_URL:-"http://localhost"}

# Check if site responds
HTTP_CODE=\$(curl -s -o /dev/null -w "%{http_code}" "\$APP_URL" 2>/dev/null || echo "000")

if [ "\$HTTP_CODE" == "200" ] || [ "\$HTTP_CODE" == "302" ]; then
    echo "✅ Health check PASSED - Site responding with HTTP \$HTTP_CODE"
else
    echo "⚠️  Health check WARNING - Site returned HTTP \$HTTP_CODE"
    echo "   This might be normal if APP_URL is not configured correctly"
    echo "   You can manually verify the site is working"
fi

# Check database connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>/dev/null && echo "✅ Database connection OK" || echo "⚠️  Database connection check failed"

# Check queue workers
WORKER_STATUS=\$(supervisorctl status devflow-worker:* 2>/dev/null | grep -c "RUNNING" || echo "0")
if [ "\$WORKER_STATUS" -gt 0 ]; then
    echo "✅ Queue workers running: \$WORKER_STATUS processes"
else
    echo "⚠️  Queue workers may not be running"
fi

# Cleanup
rm -f /tmp/devflow-pro.tar.gz

echo ""
echo "✅ Deployment completed successfully!"
echo ""
echo "📊 Service Status:"
supervisorctl status devflow-worker:* || echo "   ⚠️  Could not get worker status"
echo ""
echo "📋 Configuration:"
echo "   ✅ Queue driver: database"
echo "   ✅ Queue workers: 2 processes (supervisor)"
echo "   ✅ Laravel scheduler: configured (cron)"
echo "   ✅ Worker logs: \$REMOTE_PATH/storage/logs/worker.log"
echo "   ✅ Backups: \$BACKUP_PATH"
echo ""
echo "🔧 Management commands:"
echo "   - Check workers: supervisorctl status devflow-worker:*"
echo "   - Restart workers: supervisorctl restart devflow-worker:*"
echo "   - Stop workers: supervisorctl stop devflow-worker:*"
echo "   - View logs: tail -f \$REMOTE_PATH/storage/logs/worker.log"
echo "   - Check queue: php artisan queue:work --once"
echo "   - ROLLBACK: ./deploy.sh --rollback"
echo ""
echo "🌐 Web root: \$REMOTE_PATH/public"
echo ""

ENDSSH

# Cleanup local package
rm -f devflow-pro.tar.gz

echo ""
echo -e "${GREEN}🎉 Deployment script completed!${NC}"
echo "📝 Check server output above for health check results"
echo ""
echo -e "${BLUE}💡 Rollback command: ./deploy.sh --rollback${NC}"
echo ""
