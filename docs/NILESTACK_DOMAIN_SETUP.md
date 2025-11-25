# NileStack Domain Setup Guide
## Domain: nilestack.duckdns.org

---

## 📋 Quick Summary

Your applications will be accessible at:
- **DevFlow Pro:** https://devflow.nilestack.duckdns.org
- **ATS Pro:** https://ats.nilestack.duckdns.org
- **Portfolio:** https://portfolio.nilestack.duckdns.org

**Server IP:** 31.220.90.121

---

## 🚀 Setup Steps

### Step 1: Configure DuckDNS (2 minutes)

1. **Go to DuckDNS:**
   - Visit: https://www.duckdns.org
   - Login with GitHub or Google account

2. **Create Subdomain:**
   - Enter subdomain name: `nilestack`
   - Set IP address to: `31.220.90.121`
   - Click "update ip"

3. **Verify:**
   - You should see: `nilestack.duckdns.org` → `31.220.90.121`
   - **Important:** DuckDNS automatically supports wildcards!
   - This means all these work automatically:
     - `devflow.nilestack.duckdns.org`
     - `ats.nilestack.duckdns.org`
     - `portfolio.nilestack.duckdns.org`

### Step 2: Wait for DNS Propagation (2-3 minutes)

Wait 2-3 minutes for DNS to propagate globally.

**Test DNS (optional):**
```bash
nslookup devflow.nilestack.duckdns.org
# Should return: 31.220.90.121
```

### Step 3: Run the Setup Script

```bash
# SSH to your server
ssh root@31.220.90.121

# Run the automated setup
/opt/scripts/setup-nilestack-domain.sh
```

The script will:
1. ✅ Install certbot
2. ✅ Fix port conflicts (DevFlow → 8001, ATS → 8000, Portfolio → 9000)
3. ✅ Configure Nginx reverse proxy for all 3 apps
4. ✅ Obtain SSL certificates from Let's Encrypt
5. ✅ Configure HTTPS with auto-redirect
6. ✅ Update application .env files
7. ✅ Enable auto-renewal for SSL certificates

---

## 🎯 What the Script Does

### Port Configuration
- **DevFlow Pro:** Port 8001 → `https://devflow.nilestack.duckdns.org`
- **ATS Pro:** Port 8000 → `https://ats.nilestack.duckdns.org`
- **Portfolio:** Port 9000 → `https://portfolio.nilestack.duckdns.org`

### Nginx Reverse Proxy
The script creates Nginx configurations that:
- Route external HTTPS traffic to internal application ports
- Handle SSL/TLS termination
- Enable HTTP → HTTPS redirects
- Set proper proxy headers for Laravel/PHP applications

### SSL Certificates
- Uses Let's Encrypt (free, trusted certificates)
- Automatic renewal every 60 days
- Wildcard support via certbot timer

---

## 📝 Complete Example

Here's what you'll do:

### 1. On DuckDNS Website
```
Subdomain: nilestack
IP Address: 31.220.90.121
[Update IP button]
```

### 2. On Your Server
```bash
ssh root@31.220.90.121
/opt/scripts/setup-nilestack-domain.sh
```

### 3. During Setup
The script will pause and show:
```
==================================
IMPORTANT: Configure DuckDNS first!
==================================
1. Go to: https://www.duckdns.org
2. Login with GitHub/Google
3. Create subdomain: nilestack
4. Set IP to: 31.220.90.121
5. Save and wait 2 minutes for DNS

Press Enter when DNS is configured (or Ctrl+C to cancel)...
```

Press Enter after configuring DuckDNS.

### 4. SSL Certificate Creation
The script will request certificates:
```
Obtaining SSL certificates...
Testing DNS resolution...
✓ devflow.nilestack.duckdns.org resolves
✓ ats.nilestack.duckdns.org resolves
✓ portfolio.nilestack.duckdns.org resolves

Requesting SSL certificates from Let's Encrypt...
```

### 5. Success!
```
==========================================
✓ SSL Setup Complete!
==========================================

Your applications are now available at:
  ✓ https://devflow.nilestack.duckdns.org
  ✓ https://ats.nilestack.duckdns.org
  ✓ https://portfolio.nilestack.duckdns.org

✓ Application URLs updated
✓ SSL auto-renewal enabled
```

---

## 🔍 Verify Setup

After setup completes, test each application:

```bash
# Test DevFlow Pro
curl -I https://devflow.nilestack.duckdns.org

# Test ATS Pro
curl -I https://ats.nilestack.duckdns.org

# Test Portfolio
curl -I https://portfolio.nilestack.duckdns.org
```

All should return `HTTP/2 200` or `HTTP/2 302`.

---

## 🛠️ Troubleshooting

### DNS Not Resolving

**Problem:** Domain doesn't resolve to your IP

**Solution:**
```bash
# Check DNS
nslookup devflow.nilestack.duckdns.org

# If it doesn't return 31.220.90.121:
# 1. Wait another 2-3 minutes
# 2. Verify DuckDNS configuration
# 3. Try: dig devflow.nilestack.duckdns.org
```

### SSL Certificate Fails

**Problem:** Certbot can't obtain certificates

**Check:**
1. DNS is correctly configured
2. Ports 80 and 443 are open:
   ```bash
   ss -tlnp | grep ':80\|:443'
   ```
3. Nginx is running:
   ```bash
   systemctl status nginx
   ```

**Manual fix:**
```bash
# Test certificate manually
certbot certonly --standalone -d devflow.nilestack.duckdns.org

# If successful, run setup script again
```

### Application Not Responding

**Problem:** HTTPS works but application doesn't load

**Check backend ports:**
```bash
# Check if applications are running
docker ps

# Check ports
ss -tlnp | grep -E ':(8000|8001|9000)'

# Test locally
curl http://localhost:8001  # DevFlow
curl http://localhost:8000  # ATS
curl http://localhost:9000  # Portfolio
```

**Restart applications:**
```bash
# DevFlow Pro
cd /var/www/devflow-pro && docker compose restart

# ATS Pro
cd /var/www/ats-pro && docker compose restart

# Portfolio
cd /var/www/portfolio && docker compose restart
```

### Nginx Errors

**Check Nginx logs:**
```bash
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log
```

**Test configuration:**
```bash
nginx -t
```

**Restart Nginx:**
```bash
systemctl restart nginx
```

---

## 📊 SSL Certificate Management

### Check Certificate Status
```bash
certbot certificates
```

Output shows:
- Certificate names
- Domains covered
- Expiry dates
- Certificate paths

### Test Auto-Renewal
```bash
certbot renew --dry-run
```

### Manual Renewal
```bash
certbot renew
systemctl reload nginx
```

### Certificate Locations
```
Certificates: /etc/letsencrypt/live/devflow.nilestack.duckdns.org/
  - fullchain.pem (certificate + chain)
  - privkey.pem (private key)
  - cert.pem (certificate only)
  - chain.pem (chain only)
```

---

## 🔄 Updating URLs Later

If you need to change domain names:

1. Update Nginx configs in `/etc/nginx/sites-available/`
2. Run: `nginx -t && systemctl reload nginx`
3. Obtain new SSL certificates:
   ```bash
   certbot --nginx -d newdomain.example.com
   ```
4. Update application .env files:
   ```bash
   APP_URL=https://newdomain.example.com
   ```

---

## ✅ Post-Setup Checklist

After successful setup:

- [ ] All 3 applications accessible via HTTPS
- [ ] HTTP automatically redirects to HTTPS
- [ ] SSL certificates valid (check in browser)
- [ ] No browser security warnings
- [ ] Applications loading correctly
- [ ] SSL auto-renewal timer running: `systemctl status certbot.timer`

---

## 📚 Additional Commands

### View Nginx Configuration
```bash
cat /etc/nginx/sites-available/devflow-pro
cat /etc/nginx/sites-available/ats-pro
cat /etc/nginx/sites-available/portfolio
```

### View Application .env URLs
```bash
grep APP_URL /var/www/devflow-pro/.env
grep APP_URL /var/www/ats-pro/.env
grep APP_URL /var/www/portfolio/.env
```

### Check All Services
```bash
docker ps
systemctl status nginx
systemctl status certbot.timer
```

---

## 🎉 Success Criteria

You'll know setup is complete when:

1. ✅ You can visit https://devflow.nilestack.duckdns.org (shows DevFlow Pro)
2. ✅ You can visit https://ats.nilestack.duckdns.org (shows ATS Pro)
3. ✅ You can visit https://portfolio.nilestack.duckdns.org (shows Portfolio)
4. ✅ All URLs show valid SSL certificate (green lock icon)
5. ✅ HTTP URLs automatically redirect to HTTPS

---

## 📞 Need Help?

If you encounter issues:

1. Check the troubleshooting section above
2. Review nginx error logs: `tail -f /var/log/nginx/error.log`
3. Verify DNS: `nslookup devflow.nilestack.duckdns.org`
4. Check certbot logs: `journalctl -u certbot -n 50`

---

**Setup Time:** 5-10 minutes total
**Cost:** $0 (completely free)
**Maintenance:** Automatic SSL renewal, no manual intervention needed
