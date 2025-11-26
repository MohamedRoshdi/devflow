# DevFlow Pro - System Credentials & Access Information

**Last Updated:** November 25, 2025
**Environment:** Production
**Version:** 2.5.0 with Advanced Features

---

## 🔐 Production Server Access

### VPS Server Details

**Server Information:**
- **IP Address:** `31.220.90.121`
- **Hostname:** vmi2892671
- **Operating System:** Ubuntu 24.04.3 LTS
- **Kernel:** Linux 6.8.0-86-generic x86_64

**SSH Access:**
```bash
ssh root@31.220.90.121
```

**Server Specifications:**
- **CPU:** 2 cores
- **RAM:** Varies (use `free -h` to check)
- **Storage:** 192.69GB (1.7% used)
- **Network:** IPv4 + IPv6 enabled

---

## 🗄️ Database Credentials

### MySQL Database

**Primary User (All Applications):**
- **Host:** `127.0.0.1` or `localhost`
- **Port:** `3306`
- **Username:** `devflow_user`
- **Password:** `devflow_pass`

**Databases:**
- `devflow_pro` - DevFlow Pro Admin Panel
- `portfolio_db` - Portfolio Website
- `ats_pro` - ATS Pro Application

**Root Access:**
- **Username:** `root`
- **Password:** (Requires sudo on server)

**For Docker Containers:**
- **Host:** `172.17.0.1` (Docker bridge gateway)
- **Port:** `3306`
- **Grant command already executed for:** `'devflow'@'172.17.%'`

**MySQL Grant Example:**
```sql
GRANT ALL PRIVILEGES ON devflow_pro.* TO 'devflow'@'localhost';
GRANT ALL PRIVILEGES ON devflow_pro.* TO 'devflow'@'172.17.%';
FLUSH PRIVILEGES;
```

---

## 🌐 Application Access

### DevFlow Pro Application

**Production URLs:**
```
Portfolio Site: http://nilestack.duckdns.org
Admin Panel: http://admin.nilestack.duckdns.org
ATS System: http://ats.nilestack.duckdns.org
Direct IP: http://31.220.90.121 (shows portfolio)
```

**Application Path:**
```
/var/www/devflow-pro
```

**Environment File Location:**
```
/var/www/devflow-pro/.env
```

**Key Application Settings:**
```env
APP_NAME=DevFlowPro
APP_ENV=production
APP_DEBUG=false
APP_URL=http://admin.nilestack.duckdns.org
APP_KEY=base64:T8rSGPT1V36a7b0wc2YvB/jj7uoBB19ys3gyXHVc8cQ=
```

---

## 👤 System Users

### Admin User

**Create First Admin:**
```bash
cd /var/www/devflow-pro
php artisan tinker
>>> \App\Models\User::create([
...     'name' => 'Admin User',
...     'email' => 'admin@devflow.local',
...     'password' => bcrypt('ChangeThisPassword123!'),
... ]);
```

**Notes:**
- Self-registration is DISABLED for security
- Users must be created manually by administrators
- Share credentials privately with trusted team members

---

## 🐳 Docker Configuration

### Docker Details

**Docker Version:** Installed and configured
**Docker Compose:** Available

**Docker Network (Bridge):**
- **Gateway:** `172.17.0.1`
- **Subnet:** `172.17.0.0/16`

**Important:** All containerized apps should use `172.17.0.1` to connect to host MySQL on Linux servers

---

## 📦 Service Status & Ports

### Running Services

**Web Server:**
- **Nginx:** Port 80 (HTTP)
- **Status:** Active and running
- **Config:** `/etc/nginx/sites-available/devflow-pro`

**PHP:**
- **Version:** PHP 8.2
- **FPM Socket:** `/var/run/php/php8.2-fpm.sock`
- **Status:** Active and running

**Database:**
- **MySQL:** Port 3306
- **Status:** Active and running
- **Config:** `/etc/mysql/mysql.conf.d/mysqld.cnf`

**Cache:**
- **Redis:** Port 6379 (default)
- **Status:** Active and running

**Queue Workers:**
- **Supervisor:** 2 workers running
- **Process Name:** devflow-pro-worker:00, devflow-pro-worker:01
- **Status:** Running
- **Log:** `/var/www/devflow-pro/storage/logs/worker.log`

---

## 🔑 SSH Key Authentication

### Server SSH Key

**Public Key Location:**
```bash
cat /root/.ssh/id_rsa_new.pub
```

**Public Key Content:**
```
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQDOhrS6afoT8BZvvWetOr0CawaetDf48og5u6SWkNUl33L2BEj/wuQE3YbryijR3DMs1GihlreH7+JSBL6358kXluWHP52PjmI1PXoykygoxuuI7ogbCoA/rdaTgVLDOvhcvcdm1/3MPJBjPsnOqqZdVNzAdJ4rLa4DFunNQ8HY9jESDBc0cxT2WVqgdOpl5GAW0QbQ+dtdkN7BOpxLBFNDlGfiPM0/PdFfIbxyAn0bQTg/Oh7jmsDkI7yEyhvA1nPCSNWW72WMIdSP774MpPBHnpz3cSCCTtb+NeYTMCSmgYgVRieZav2ZX/3s797Ni4caQ6R+g9D6aSZGyblat8iCe4t47ln7CAnYiLEAublb55hZZDBse9MTvN14Gued8NWgz7WTX93Dk9oNtH8c/KbvFknHUfxfS1HdAmkaixc1qbvQNa37aASuPBhFQh0Kj5AdY1DdbA86mxvgRuTDNLdRbiO229i/UMpzTMGkL2j8yWIFgNG7VuMSMHyvR7NeK6U= roshdy@roshdy
```

**Add to GitHub:**
1. Go to https://github.com/settings/keys
2. Click "New SSH key"
3. Title: "DevFlow Pro Production Server"
4. Paste the public key above
5. Click "Add SSH key"

**Test SSH Connection:**
```bash
ssh -T git@github.com
```

---

## 🛠️ Service Management Commands

### Systemd Services

**Nginx:**
```bash
systemctl status nginx
systemctl restart nginx
systemctl reload nginx
```

**PHP-FPM:**
```bash
systemctl status php8.2-fpm    # DevFlow & Portfolio
systemctl restart php8.2-fpm
systemctl status php8.3-fpm    # ATS Pro
systemctl restart php8.3-fpm
```

**MySQL:**
```bash
systemctl status mysql
systemctl restart mysql
```

**Redis:**
```bash
systemctl status redis-server
systemctl restart redis-server
```

**Supervisor (Queue Workers):**
```bash
systemctl status supervisor
systemctl restart supervisor
supervisorctl status
supervisorctl restart devflow-pro-worker:*
```

---

## 📁 Important File Locations

### Application Files

**Main Directory:**
```
/var/www/devflow-pro/
```

**Environment Configuration:**
```
/var/www/devflow-pro/.env
```

**Laravel Logs:**
```
/var/www/devflow-pro/storage/logs/laravel.log
```

**Queue Worker Logs:**
```
/var/www/devflow-pro/storage/logs/worker.log
```

**Nginx Configuration:**
```
/etc/nginx/sites-available/devflow-pro
/etc/nginx/sites-enabled/devflow-pro
```

**Supervisor Configuration:**
```
/etc/supervisor/conf.d/devflow-pro.conf
```

---

## 🔧 Maintenance Commands

### Laravel Artisan Commands

**Clear All Caches:**
```bash
cd /var/www/devflow-pro
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

**Optimize for Production:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

**Database:**
```bash
php artisan migrate --force
php artisan db:seed
php artisan migrate:status
```

**Queue Management:**
```bash
php artisan queue:work
php artisan queue:listen
php artisan queue:failed
php artisan queue:retry all
```

---

## 🔄 Deployment Commands

### Manual Deployment

**From Local Machine:**
```bash
cd /path/to/DEVFLOW_PRO
./quick-deploy.sh
```

**Or Individual Scripts:**
```bash
# 1. Setup server (one-time)
./setup-server.sh

# 2. Deploy application
./deploy.sh
```

### On Server

**Pull Latest Changes:**
```bash
cd /var/www/devflow-pro
git pull origin master
composer install --optimize-autoloader --no-dev
npm install && npm run build
php artisan migrate --force
php artisan optimize
supervisorctl restart devflow-pro-worker:*
systemctl restart php8.2-fpm
```

---

## 🔐 Security Notes

### Important Security Practices

**DO:**
- ✅ Change default passwords immediately
- ✅ Use SSH keys instead of passwords
- ✅ Keep SSH keys secure and private
- ✅ Regularly update system packages
- ✅ Monitor access logs
- ✅ Use strong passwords for database
- ✅ Restrict MySQL access to necessary hosts only

**DON'T:**
- ❌ Share credentials publicly
- ❌ Commit .env files to git
- ❌ Use default/weak passwords in production
- ❌ Allow root SSH with password
- ❌ Expose sensitive data in logs
- ❌ Share production database credentials with untrusted parties

---

## 📊 Monitoring & Logs

### System Logs

**Nginx Access Log:**
```bash
tail -f /var/log/nginx/access.log
```

**Nginx Error Log:**
```bash
tail -f /var/log/nginx/error.log
```

**PHP-FPM Log:**
```bash
tail -f /var/log/php8.2-fpm.log
```

**MySQL Log:**
```bash
tail -f /var/log/mysql/error.log
```

**Supervisor Log:**
```bash
tail -f /var/log/supervisor/supervisord.log
```

**Application Log:**
```bash
tail -f /var/www/devflow-pro/storage/logs/laravel.log
```

---

## 🆘 Emergency Contacts & Resources

### Quick Reference

**GitHub Repository:**
```
https://github.com/yourusername/devflow-pro
```

**Documentation:**
- README.md - Main documentation
- DOCUMENTATION.md - Complete user guide
- CHANGELOG.md - Version history
- TROUBLESHOOTING.md - Common issues

**Support:**
- GitHub Issues: Report bugs
- GitHub Discussions: Ask questions
- Email: support@devflowpro.com (if configured)

---

## 📝 Change Log

| Date | Changed By | Changes |
|------|------------|---------|
| 2025-11-24 | System | Initial deployment completed |
| 2025-11-24 | System | Database configured and migrations run |
| 2025-11-24 | System | SSH key authentication set up |
| 2025-11-24 | System | All services started and verified |
| 2025-11-25 | System | Portfolio configured as main site |
| 2025-11-25 | System | DevFlow moved to admin subdomain |
| 2025-11-25 | System | MySQL user devflow_user created |
| 2025-11-25 | System | PHP 8.3 installed for ATS Pro |
| 2025-11-25 | System | All three applications configured |

---

## ⚠️ Important Reminders

1. **Change All Default Passwords** - Especially database and first admin user
2. **Backup Regularly** - Database and application files
3. **Monitor Disk Space** - Run cleanup when needed
4. **Update Software** - Keep system packages updated
5. **Review Logs** - Check for errors or suspicious activity
6. **Test Deployments** - Always test in staging first if available

---

**Document Version:** 1.0
**Last Reviewed:** November 24, 2025
**Review Schedule:** Monthly

---

🔒 **Keep this document secure and private!**
