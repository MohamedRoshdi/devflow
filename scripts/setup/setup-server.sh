#!/bin/bash

# Server Setup Script for DevFlow Pro
# Run this on the VPS server as root

set -e

echo "🚀 Setting up VPS for DevFlow Pro"
echo "=================================="

# Update system
echo "📦 Updating system packages..."
apt-get update
apt-get upgrade -y

# Install required packages
echo "📥 Installing required packages..."
apt-get install -y \
    nginx \
    mysql-server \
    redis-server \
    supervisor \
    git \
    curl \
    unzip \
    software-properties-common

# Install PHP 8.2
echo "🐘 Installing PHP 8.2..."
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get install -y \
    php8.2 \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-redis \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-gd \
    php8.2-bcmath \
    php8.2-intl

# Install Composer
if ! command -v composer &> /dev/null; then
    echo "📦 Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

# Install Node.js 20
if ! command -v node &> /dev/null; then
    echo "📦 Installing Node.js 20..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
fi

# Setup MySQL (if not already done)
echo "🗄️  Configuring MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS devflow_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || true
mysql -e "CREATE USER IF NOT EXISTS 'devflow'@'localhost' IDENTIFIED BY 'devflow_secure_password_123';" || true
mysql -e "GRANT ALL PRIVILEGES ON devflow_pro.* TO 'devflow'@'localhost';" || true
mysql -e "FLUSH PRIVILEGES;" || true

# Configure Nginx
echo "🌐 Configuring Nginx..."
cat > /etc/nginx/sites-available/devflow-pro << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root /var/www/devflow-pro/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/devflow-pro /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and reload Nginx
nginx -t
systemctl reload nginx

# Setup Supervisor for queue worker
echo "👷 Configuring Supervisor..."
cat > /etc/supervisor/conf.d/devflow-pro.conf << 'EOF'
[program:devflow-pro-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/devflow-pro/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/devflow-pro/storage/logs/worker.log
stopwaitsecs=3600
EOF

supervisorctl reread
supervisorctl update

# Setup cron for scheduled tasks
echo "⏰ Setting up cron jobs..."
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/devflow-pro && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Start services
echo "🔄 Starting services..."
systemctl enable nginx
systemctl enable mysql
systemctl enable redis-server
systemctl enable supervisor
systemctl restart nginx
systemctl restart php8.2-fpm
systemctl restart redis-server
systemctl restart supervisor

echo ""
echo "✅ Server setup completed!"
echo ""
echo "📋 Next steps:"
echo "   1. Run the deployment script from your local machine: ./deploy.sh"
echo "   2. Configure .env file on the server"
echo "   3. Access the application at: http://31.220.90.121"
echo ""
echo "📊 Service status:"
systemctl status nginx --no-pager | grep Active
systemctl status mysql --no-pager | grep Active
systemctl status redis-server --no-pager | grep Active
systemctl status supervisor --no-pager | grep Active
echo ""

