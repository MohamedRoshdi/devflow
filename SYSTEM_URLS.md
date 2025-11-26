# NileStack Platform - System URLs & Configuration

## Production URLs

### Main Domains
- **Portfolio Site:** http://nilestack.duckdns.org
  - Status: ✅ Active
  - Purpose: Main portfolio website
  - Content: Laravel Portfolio Application
  - Database: portfolio_db

- **DevFlow Pro Admin:** http://admin.nilestack.duckdns.org
  - Status: ✅ Active
  - Purpose: Project Management & Deployment System
  - Access: Public (contains sensitive data - consider adding auth)
  - Laravel Application
  - Database: devflow_pro

- **ATS Pro:** http://ats.nilestack.duckdns.org
  - Status: ✅ Database Configured & Migrations Run
  - Purpose: Applicant Tracking System
  - Laravel Application
  - Database: ats_pro

### Infrastructure Services
- **Portainer:** http://nilestack.duckdns.org:9443
  - Purpose: Docker Container Management
  - Access: HTTPS on port 9443

- **Server Direct Access:** http://31.220.90.121
  - Purpose: Direct IP access for troubleshooting
  - SSH Port: 22

## Server Configuration

### Directory Structure
```
/var/www/
├── portfolio/         # Portfolio site (nilestack.duckdns.org)
│   └── [Laravel App]
├── devflow-pro/       # DevFlow Pro (admin.nilestack.duckdns.org)
│   └── [Laravel App]
├── ats-pro/           # ATS Pro (ats.nilestack.duckdns.org)
│   └── [Laravel App]
└── main/              # Static landing page (unused)
    └── index.html
```

### Nginx Configuration Files
```
/etc/nginx/sites-available/
├── portfolio-main     # nilestack.duckdns.org
├── devflow-admin      # admin.nilestack.duckdns.org
├── ats-pro            # ats.nilestack.duckdns.org
└── main-site          # (disabled - static landing page)
```

### PHP Configuration
- **PHP 8.2:** Used by DevFlow Pro and Portfolio
- **PHP 8.3:** Used by ATS Pro
- **FPM Sockets:**
  - `/var/run/php/php8.2-fpm.sock` (DevFlow Pro & Portfolio)
  - `/var/run/php/php8.3-fpm.sock` (ATS Pro)

## Environment Variables

### Portfolio (.env)
```env
APP_URL=http://nilestack.duckdns.org
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=portfolio_db
DB_USERNAME=devflow_user
DB_PASSWORD=devflow_pass
```

### DevFlow Pro (.env)
```env
APP_URL=http://admin.nilestack.duckdns.org
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=devflow_pro
DB_USERNAME=devflow_user
DB_PASSWORD=devflow_pass
```

### ATS Pro (.env)
```env
APP_URL=http://ats.nilestack.duckdns.org
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=ats_pro
DB_USERNAME=devflow_user
DB_PASSWORD=devflow_pass
REDIS_HOST=127.0.0.1
```

## DNS Configuration

All subdomains are configured through DuckDNS and point to: **31.220.90.121**

### Active DNS Records
- `nilestack.duckdns.org` → 31.220.90.121
- `admin.nilestack.duckdns.org` → 31.220.90.121
- `ats.nilestack.duckdns.org` → 31.220.90.121
- `www.nilestack.duckdns.org` → 31.220.90.121

## Security Recommendations

1. **Add Basic Authentication** to admin.nilestack.duckdns.org:
   ```bash
   # Already prepared in /etc/nginx/.htpasswd
   # Username: admin
   # Password: SecureAdmin@2025
   # Uncomment auth lines in /etc/nginx/sites-available/devflow-admin
   ```

2. **Enable SSL/HTTPS** using Let's Encrypt:
   ```bash
   certbot --nginx -d nilestack.duckdns.org -d admin.nilestack.duckdns.org -d ats.nilestack.duckdns.org
   ```

3. **Configure Firewall** for additional security:
   ```bash
   ufw allow 80/tcp
   ufw allow 443/tcp
   ufw allow 22/tcp
   ufw allow 9443/tcp
   ufw enable
   ```

## Maintenance Commands

### Service Management
```bash
# Restart Nginx
systemctl restart nginx

# Restart PHP-FPM
systemctl restart php8.2-fpm
systemctl restart php8.3-fpm

# Check service status
systemctl status nginx
systemctl status php8.2-fpm
systemctl status php8.3-fpm
```

### Application Cache Clear
```bash
# DevFlow Pro
cd /var/www/devflow-pro && php artisan config:cache

# ATS Pro
cd /var/www/ats-pro && php artisan config:clear
```

### Log Locations
- **Nginx Access:** `/var/log/nginx/*-access.log`
- **Nginx Error:** `/var/log/nginx/*-error.log`
- **DevFlow Laravel:** `/var/www/devflow-pro/storage/logs/laravel.log`
- **ATS Laravel:** `/var/www/ats-pro/storage/logs/laravel.log`

## Quick Access Links

| Service | URL | Status |
|---------|-----|--------|
| Portfolio | [nilestack.duckdns.org](http://nilestack.duckdns.org) | ✅ Active |
| DevFlow Pro | [admin.nilestack.duckdns.org](http://admin.nilestack.duckdns.org) | ✅ Active |
| ATS Pro | [ats.nilestack.duckdns.org](http://ats.nilestack.duckdns.org) | ✅ Configured |
| Portainer | [nilestack.duckdns.org:9443](http://nilestack.duckdns.org:9443) | ✅ Active |

## Troubleshooting

### If a site shows 500 error:
1. Check Laravel logs: `tail -f /var/www/{project}/storage/logs/laravel.log`
2. Check Nginx logs: `tail -f /var/log/nginx/{site}-error.log`
3. Verify PHP-FPM is running: `systemctl status php8.x-fpm`
4. Clear application cache: `php artisan config:clear`

### If DNS not resolving:
1. Check DNS propagation: `nslookup {subdomain}.nilestack.duckdns.org`
2. Verify DuckDNS configuration
3. Test with direct IP: `curl -H "Host: {subdomain}.nilestack.duckdns.org" http://31.220.90.121`

---
*Last Updated: November 25, 2025*
*Server IP: 31.220.90.121*
*Platform: Ubuntu with Nginx, PHP 8.2/8.3, MySQL, Redis*