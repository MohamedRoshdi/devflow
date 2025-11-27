# Quick SSH Access Reference

**Quick reference for accessing the DevFlow Pro production server.**

---

## 🚀 Instant Access

```bash
ssh root@31.220.90.121
```

**Alternative using hostname:**
```bash
ssh root@nilestack.duckdns.org
```

---

## 📋 Server Details

| Item | Value |
|------|-------|
| **IP Address** | `31.220.90.121` |
| **Hostname** | `nilestack.duckdns.org` |
| **SSH User** | `root` |
| **SSH Port** | `22` |
| **Authentication** | SSH Key (or password) |

---

## ⚡ Common Commands

### Quick File Transfer
```bash
# Upload file
scp file.txt root@31.220.90.121:/var/www/devflow-pro/

# Download file
scp root@31.220.90.121:/var/www/devflow-pro/file.txt .

# Upload directory
scp -r ./directory root@31.220.90.121:/var/www/devflow-pro/
```

### Application Commands
```bash
# View Laravel logs
ssh root@31.220.90.121 "tail -f /var/www/devflow-pro/storage/logs/laravel.log"

# Clear cache
ssh root@31.220.90.121 "cd /var/www/devflow-pro && php artisan cache:clear"

# Restart services
ssh root@31.220.90.121 "systemctl restart nginx php8.2-fpm"

# Check Docker
ssh root@31.220.90.121 "docker ps"
```

### Server Status
```bash
# System info
ssh root@31.220.90.121 "uname -a && df -h && free -h"

# Check services
ssh root@31.220.90.121 "systemctl status nginx mysql php8.2-fpm"
```

---

## 🔧 SSH Config (Recommended)

Add to `~/.ssh/config`:

```bash
Host devflow
    HostName 31.220.90.121
    User root
    Port 22
    IdentityFile ~/.ssh/id_ed25519
```

Then connect with:
```bash
ssh devflow
```

---

## 🎯 Most Used Paths

- **Application:** `/var/www/devflow-pro`
- **Logs:** `/var/www/devflow-pro/storage/logs`
- **Environment:** `/var/www/devflow-pro/.env`
- **Nginx:** `/etc/nginx/sites-available/`
- **PHP-FPM:** `/etc/php/8.2/fpm/`

---

## 🔥 Emergency Commands

```bash
# Restart everything
ssh root@31.220.90.121 "systemctl restart nginx php8.2-fpm mysql"

# Check what's using port 80
ssh root@31.220.90.121 "netstat -tulpn | grep :80"

# Disk space
ssh root@31.220.90.121 "df -h"

# Memory usage
ssh root@31.220.90.121 "free -h"

# View recent errors
ssh root@31.220.90.121 "tail -100 /var/log/nginx/error.log"
```

---

## 📖 Full Documentation

For detailed SSH guide, security practices, and troubleshooting:
👉 **[SSH_ACCESS.md](SSH_ACCESS.md)**

---

**Quick Help:**
- Test connection: `ping 31.220.90.121`
- Check SSH service: `telnet 31.220.90.121 22`
- Verbose mode: `ssh -v root@31.220.90.121`
