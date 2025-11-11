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

# Run migrations
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

# Cleanup
rm -f /tmp/devflow-pro.tar.gz

echo ""
echo "✅ Deployment completed successfully!"
echo ""
echo "📋 Next steps:"
echo "   1. Edit .env file: nano $REMOTE_PATH/.env"
echo "   2. Configure your web server (Nginx/Apache)"
echo "   3. Setup supervisor for queue worker"
echo "   4. Configure cron for scheduled tasks"
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

