#!/bin/bash

# DevFlow Pro Deployment Script
# Usage: ./deploy.sh

set -e

SERVER_IP="31.220.90.121"
SERVER_USER="root"
PROJECT_NAME="devflow-pro"
REMOTE_PATH="/var/www/$PROJECT_NAME"

echo "🚀 Starting DevFlow Pro Deployment to $SERVER_IP"
echo "================================================"

# Create deployment package
echo "📦 Creating deployment package..."
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
    echo "❌ Failed to create deployment package"
    exit 1
fi

echo "✅ Package created: devflow-pro.tar.gz"

# Copy to server
echo "📤 Uploading to server..."
scp devflow-pro.tar.gz $SERVER_USER@$SERVER_IP:/tmp/

echo "🔧 Setting up on server..."
ssh $SERVER_USER@$SERVER_IP << 'ENDSSH'
set -e

PROJECT_NAME="devflow-pro"
REMOTE_PATH="/var/www/$PROJECT_NAME"

# Create directory if not exists
mkdir -p $REMOTE_PATH

# Extract files
echo "📂 Extracting files..."
tar -xzf /tmp/devflow-pro.tar.gz -C $REMOTE_PATH

# Navigate to project
cd $REMOTE_PATH

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

# Set permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data $REMOTE_PATH
chmod -R 755 $REMOTE_PATH
chmod -R 775 $REMOTE_PATH/storage
chmod -R 775 $REMOTE_PATH/bootstrap/cache

# Fix Git ownership issues
echo "🔧 Configuring Git safe directories..."
git config --global --add safe.directory $REMOTE_PATH 2>/dev/null || true
git config --global --add safe.directory "/var/www/*" 2>/dev/null || true

# Build assets
echo "🎨 Building assets..."
npm run build

# Run migrations (queue tables are created via migration file with hasTable check)
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Publish Livewire assets
echo "📦 Publishing Livewire assets..."
php artisan livewire:publish --assets

# Optimize Laravel
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

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
    (crontab -l 2>/dev/null; echo "$CRON_CMD") | crontab -
    echo "✅ Scheduler cron job added"
fi

# Restart queue workers gracefully
echo "🔄 Restarting queue workers..."
php artisan queue:restart

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
echo "   ✅ Worker logs: $REMOTE_PATH/storage/logs/worker.log"
echo ""
echo "🔧 Management commands:"
echo "   - Check workers: supervisorctl status devflow-worker:*"
echo "   - Restart workers: supervisorctl restart devflow-worker:*"
echo "   - Stop workers: supervisorctl stop devflow-worker:*"
echo "   - View logs: tail -f $REMOTE_PATH/storage/logs/worker.log"
echo "   - Check queue: php artisan queue:work --once"
echo ""
echo "🌐 Web root: $REMOTE_PATH/public"
echo ""

ENDSSH

# Cleanup local package
rm -f devflow-pro.tar.gz

echo ""
echo "🎉 Deployment script completed!"
echo "📝 Check server output above for next steps"
echo ""

