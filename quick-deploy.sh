#!/bin/bash

# Quick Deploy Script - Handles everything in one go
# Usage: ./quick-deploy.sh

set -e

SERVER_IP="31.220.90.121"
SERVER_USER="root"

echo "🚀 DevFlow Pro - Quick Deployment"
echo "=================================="
echo ""
echo "Target: $SERVER_USER@$SERVER_IP"
echo ""

# Step 1: Copy setup script to server
echo "📤 [1/3] Uploading server setup script..."
scp setup-server.sh $SERVER_USER@$SERVER_IP:/tmp/

# Step 2: Run setup on server
echo "🔧 [2/3] Running server setup (this may take a few minutes)..."
ssh $SERVER_USER@$SERVER_IP "bash /tmp/setup-server.sh"

# Step 3: Deploy application
echo "📦 [3/3] Deploying DevFlow Pro application..."
./deploy.sh

echo ""
echo "🎉 Deployment Complete!"
echo "======================="
echo ""
echo "🌐 Access your application:"
echo "   http://31.220.90.121"
echo ""
echo "📝 Default credentials need to be created:"
echo "   1. SSH to server: ssh root@31.220.90.121"
echo "   2. Navigate to: cd /var/www/devflow-pro"
echo "   3. Edit .env if needed: nano .env"
echo "   4. Visit the site and register your admin account"
echo ""
echo "🔐 Database configured:"
echo "   Database: devflow_pro"
echo "   Username: devflow"
echo "   Password: devflow_secure_password_123"
echo ""

