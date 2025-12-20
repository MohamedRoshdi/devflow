#!/bin/bash

# DevFlow Pro - Storage Permissions Fix Script
# Run this script whenever you encounter storage permission issues

echo "🔧 Fixing DevFlow Pro storage permissions..."

# Create all required storage directories
mkdir -p storage/framework/{cache,sessions,views,testing}
mkdir -p storage/logs
mkdir -p storage/app/{public,backups}
mkdir -p bootstrap/cache

# Set proper permissions (775 for directories, 664 for files)
chmod -R 775 storage bootstrap/cache

# Clear Laravel caches
rm -rf bootstrap/cache/*.php
php artisan view:clear 2>/dev/null || echo "⚠ Could not clear views cache"
php artisan config:clear 2>/dev/null || echo "⚠ Could not clear config cache"

echo "✅ Storage permissions fixed!"
echo "📁 All required directories created"
echo "🧹 Caches cleared"
echo ""
echo "You can run this script anytime with: ./fix-storage.sh"
