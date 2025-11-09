# DevFlow Pro - Manual Deployment Instructions

Since automated SSH requires password input, please follow these manual steps:

## Step 1: Setup SSH Key (One-time)

Run this command and enter your server password when prompted:

```bash
ssh-copy-id root@31.220.90.121
```

After this, you won't need passwords for future deployments.

## Step 2: Deploy Using Quick Script

Once SSH key is configured, run:

```bash
cd /home/roshdy/Work/projects/DEVFLOW_PRO
./quick-deploy.sh
```

## Alternative: Manual Step-by-Step Deployment

If you prefer manual control:

### A. Setup Server (First Time Only)

```bash
# Copy setup script
scp setup-server.sh root@31.220.90.121:/tmp/

# SSH to server and run setup
ssh root@31.220.90.121
bash /tmp/setup-server.sh
exit
```

### B. Deploy Application

```bash
# From your local machine
cd /home/roshdy/Work/projects/DEVFLOW_PRO
./deploy.sh
```

### C. Configure Application

```bash
# SSH to server
ssh root@31.220.90.121

# Edit environment
cd /var/www/devflow-pro
nano .env

# Update these lines:
# DB_DATABASE=devflow_pro
# DB_USERNAME=devflow
# DB_PASSWORD=devflow_secure_password_123
# APP_URL=http://31.220.90.121

# Save and exit (Ctrl+X, Y, Enter)

# Run migrations
php artisan migrate --force

# Optimize
php artisan config:cache
php artisan route:cache

exit
```

## Step 3: Access Application

Open in browser:
```
http://31.220.90.121
```

Register your admin account at:
```
http://31.220.90.121/register
```

## Step 4: Test DevFlow Pro

1. **Add Your VPS as a Server:**
   - Go to: Servers → Add Server
   - Name: Production VPS
   - IP: 31.220.90.121
   - Username: root
   - (Add your SSH key if needed)

2. **Create Test Project:**
   - Go to: Projects → New Project
   - Configure and deploy

3. **Monitor:**
   - Check Dashboard for metrics
   - View Analytics for performance

## Quick Commands Reference

```bash
# SSH to server
ssh root@31.220.90.121

# Check services
systemctl status nginx
systemctl status mysql
systemctl status redis
supervisorctl status

# View logs
tail -f /var/www/devflow-pro/storage/logs/laravel.log

# Restart services
systemctl restart nginx
supervisorctl restart all

# Clear cache
cd /var/www/devflow-pro
php artisan cache:clear
php artisan config:clear
```

## Troubleshooting

### Can't connect to server?
```bash
ssh -v root@31.220.90.121
```

### Permission denied?
```bash
cd /var/www/devflow-pro
sudo chown -R www-data:www-data .
sudo chmod -R 775 storage bootstrap/cache
```

### Database issues?
```bash
# Reset database
mysql -u devflow -p
# Password: devflow_secure_password_123
DROP DATABASE IF EXISTS devflow_pro;
CREATE DATABASE devflow_pro;
exit

cd /var/www/devflow-pro
php artisan migrate:fresh --force
```

---

**Ready to deploy? Start with Step 1 above!**

