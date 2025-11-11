#!/bin/bash
# Livewire Component Cache Fix Script

echo "🔧 Fixing Livewire Component Cache Issue..."
echo "============================================"

cd /var/www/devflow-pro || exit 1

echo "1️⃣  Removing Livewire cache files..."
rm -rf storage/framework/cache/livewire-components.php
rm -rf bootstrap/cache/livewire*
rm -rf storage/framework/livewire*

echo "2️⃣  Regenerating Composer autoload..."
composer dump-autoload --optimize --quiet

echo "3️⃣  Clearing all Laravel caches..."
php artisan optimize:clear --quiet

echo "4️⃣  Caching configuration..."
php artisan config:cache --quiet
php artisan route:cache --quiet

echo "5️⃣  Restarting PHP-FPM..."
systemctl restart php8.2-fpm

echo ""
echo "✅ Done! Waiting for PHP-FPM to stabilize..."
sleep 3

if systemctl is-active --quiet php8.2-fpm; then
    echo "✅ PHP-FPM is running"
else
    echo "❌ PHP-FPM failed to start"
    exit 1
fi

echo ""
echo "🎉 Livewire cache fix completed successfully!"
