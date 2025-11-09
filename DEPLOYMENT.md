# DevFlow Pro - Deployment Guide

## Quick Deployment (Recommended)

Deploy everything in one command:

```bash
./quick-deploy.sh
```

This will:
1. Setup the VPS server with all requirements
2. Deploy the DevFlow Pro application
3. Configure Nginx, MySQL, Redis, and Supervisor

**Note:** You'll be prompted for the server password during deployment.

## Manual Deployment

### Step 1: Setup Server

SSH into your VPS and run:

```bash
scp setup-server.sh root@31.220.90.121:/tmp/
ssh root@31.220.90.121 "bash /tmp/setup-server.sh"
```

### Step 2: Deploy Application

From your local machine:

```bash
./deploy.sh
```

## Post-Deployment Configuration

### 1. Configure Environment

SSH to the server:

```bash
ssh root@31.220.90.121
cd /var/www/devflow-pro
nano .env
```

Update the following variables:

```env
APP_URL=http://31.220.90.121

DB_DATABASE=devflow_pro
DB_USERNAME=devflow
DB_PASSWORD=devflow_secure_password_123

# Optional: For real-time features
PUSHER_APP_ID=your_pusher_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
```

### 2. Create Admin User

Visit the application and register:
- URL: http://31.220.90.121/register
- Create your admin account

### 3. Test the System

1. **Add a Server:**
   - Go to Servers → Add Server
   - Add the VPS itself or other servers

2. **Create a Project:**
   - Go to Projects → New Project
   - Configure a test project

3. **Deploy:**
   - Open project details
   - Click "Deploy" button
   - Monitor the deployment

## Server Stack

The deployment installs:

- **Web Server:** Nginx
- **PHP:** 8.2-FPM
- **Database:** MySQL
- **Cache/Queue:** Redis
- **Process Manager:** Supervisor
- **Runtime:** Node.js 20

## Service Management

### Check Service Status

```bash
systemctl status nginx
systemctl status mysql
systemctl status redis-server
systemctl status supervisor
```

### View Queue Workers

```bash
supervisorctl status
```

### View Logs

```bash
# Application logs
tail -f /var/www/devflow-pro/storage/logs/laravel.log

# Worker logs
tail -f /var/www/devflow-pro/storage/logs/worker.log

# Nginx logs
tail -f /var/log/nginx/error.log
```

## SSL Setup (Optional)

Install Certbot and obtain SSL certificate:

```bash
apt-get install -y certbot python3-certbot-nginx
certbot --nginx -d yourdomain.com
```

## Firewall Configuration

```bash
# Allow HTTP/HTTPS
ufw allow 80/tcp
ufw allow 443/tcp

# Allow SSH
ufw allow 22/tcp

# Enable firewall
ufw enable
```

## Troubleshooting

### Permission Issues

```bash
cd /var/www/devflow-pro
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

### Clear Cache

```bash
cd /var/www/devflow-pro
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Restart Services

```bash
systemctl restart nginx
systemctl restart php8.2-fpm
systemctl restart supervisor
supervisorctl restart all
```

## Updating the Application

```bash
# From local machine
./deploy.sh

# On server
cd /var/www/devflow-pro
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
supervisorctl restart all
```

## Monitoring

### Server Metrics

The application automatically monitors:
- CPU usage
- Memory usage
- Disk usage
- Network traffic

View in: Dashboard → Analytics

### Queue Status

```bash
supervisorctl status devflow-pro-worker:*
```

## Backup

### Database Backup

```bash
mysqldump -u devflow -p devflow_pro > backup_$(date +%Y%m%d).sql
```

### Application Backup

```bash
tar -czf devflow-backup-$(date +%Y%m%d).tar.gz /var/www/devflow-pro
```

## Support

For issues:
- Check logs: `/var/www/devflow-pro/storage/logs/`
- Review Nginx errors: `/var/log/nginx/error.log`
- Check queue workers: `supervisorctl status`

## Security Recommendations

1. Change default database password
2. Setup firewall (ufw)
3. Install SSL certificate
4. Regular backups
5. Keep system updated: `apt-get update && apt-get upgrade`
6. Use strong passwords
7. Consider fail2ban for SSH protection

