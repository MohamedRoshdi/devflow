# Free Domain & SSL Setup Guide for DevFlow Pro

This guide will help you set up free domains and SSL certificates for testing your DevFlow Pro applications.

---

## Option 1: DuckDNS (Recommended - Easiest)

### Why DuckDNS?
- ✅ 100% Free forever
- ✅ Instant activation
- ✅ Supports Let's Encrypt SSL
- ✅ Dynamic DNS updates
- ✅ No registration required (just login with GitHub/Google)

### Step 1: Get Your Free Domain

1. Go to https://www.duckdns.org
2. Login with GitHub, Google, or other provider
3. Create a subdomain (e.g., `mbfouad-devflow`)
4. You'll get: `mbfouad-devflow.duckdns.org`
5. Copy your token (you'll need it)

### Step 2: Point Domain to Your Server

**Get your server IP:**
```bash
ssh root@31.220.90.121 "curl -s ifconfig.me"
```

On DuckDNS dashboard:
1. Enter your subdomain name
2. Paste your server IP in the "current ip" field
3. Click "update ip"

### Step 3: Create Subdomains Using Wildcard

DuckDNS supports wildcards, so if you have `mbfouad.duckdns.org`, you automatically get:
- `devflow.mbfouad.duckdns.org`
- `ats.mbfouad.duckdns.org`
- `portfolio.mbfouad.duckdns.org`

All automatically point to your server IP!

### Step 4: Run the SSL Setup Script

```bash
# Upload the script to your server
scp scripts/production/setup-domain-ssl.sh root@31.220.90.121:/opt/scripts/

# SSH to your server
ssh root@31.220.90.121

# Make it executable
chmod +x /opt/scripts/setup-domain-ssl.sh

# Run the setup
/opt/scripts/setup-domain-ssl.sh
```

**Example inputs:**
```
Primary domain: mbfouad.duckdns.org
DevFlow subdomain: devflow
ATS subdomain: ats
Portfolio subdomain: portfolio
Email: your-email@gmail.com
```

This will set up:
- https://devflow.mbfouad.duckdns.org (DevFlow Pro)
- https://ats.mbfouad.duckdns.org (ATS Pro)
- https://portfolio.mbfouad.duckdns.org (Portfolio)

---

## Option 2: FreeDNS (afraid.org)

### Why FreeDNS?
- ✅ Free forever
- ✅ Many domain choices (mooo.com, chickenkiller.com, etc.)
- ✅ Supports Let's Encrypt
- ✅ Can create multiple subdomains

### Setup Steps

1. **Register:** https://freedns.afraid.org/signup/
2. **Create Subdomain:**
   - Go to "Subdomains" → "Add"
   - Choose from 100+ free domains
   - Example: `mbfouad.mooo.com`
   - Point to your server IP: `31.220.90.121`

3. **Create Additional Subdomains:**
   - `devflow-mbfouad.mooo.com`
   - `ats-mbfouad.mooo.com`
   - `portfolio-mbfouad.mooo.com`

4. **Run Setup Script** (same as DuckDNS above)

---

## Option 3: No-IP

### Why No-IP?
- ✅ Free plan available
- ✅ Dynamic DNS support
- ✅ 3 hostnames on free plan
- ✅ Works with Let's Encrypt

### Setup Steps

1. **Register:** https://www.noip.com/sign-up
2. **Create Hostname:**
   - Choose from domains: ddns.net, hopto.org, etc.
   - Example: `mbfouad.ddns.net`
3. **Confirm every 30 days** (free plan requirement)
4. **Run Setup Script**

---

## Option 4: eu.org (Free Second-Level Domain)

### Why eu.org?
- ✅ Real second-level domain (yourname.eu.org)
- ✅ More professional looking
- ✅ Free forever
- ❌ Takes 2-3 weeks for approval

### Setup Steps

1. **Register:** https://nic.eu.org
2. **Request domain:** `mbfouad.eu.org`
3. **Wait for approval** (1-3 weeks)
4. **Configure DNS** to point to your server
5. **Run Setup Script**

---

## Quick Setup Commands

### 1. Get Your Server IP
```bash
ssh root@31.220.90.121 "curl -s ifconfig.me"
```

### 2. Check Port Accessibility
```bash
# Check if ports 80 and 443 are accessible
ssh root@31.220.90.121 "netstat -tlnp | grep -E ':(80|443)'"
```

### 3. Open Firewall Ports (if needed)
```bash
ssh root@31.220.90.121 "
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw status
"
```

### 4. Upload and Run Setup Script
```bash
# From your local machine
cd /home/roshdy/Work/projects/DEVFLOW_PRO
scp scripts/production/setup-domain-ssl.sh root@31.220.90.121:/opt/scripts/

# On the server
ssh root@31.220.90.121
chmod +x /opt/scripts/setup-domain-ssl.sh
/opt/scripts/setup-domain-ssl.sh
```

---

## Example: Complete DuckDNS Setup

Let's say you chose `techflow-demo.duckdns.org`:

### Step-by-Step:

```bash
# 1. Get your server IP
ssh root@31.220.90.121 "curl -s ifconfig.me"
# Output: 31.220.90.121

# 2. On DuckDNS website:
#    - Create subdomain: techflow-demo
#    - Set IP: 31.220.90.121
#    - You now have: techflow-demo.duckdns.org

# 3. Upload the setup script
scp scripts/production/setup-domain-ssl.sh root@31.220.90.121:/opt/scripts/

# 4. SSH to server
ssh root@31.220.90.121

# 5. Make executable
chmod +x /opt/scripts/setup-domain-ssl.sh

# 6. Run setup
/opt/scripts/setup-domain-ssl.sh

# When prompted:
# Primary domain: techflow-demo.duckdns.org
# DevFlow subdomain: devflow
# ATS subdomain: ats
# Portfolio subdomain: portfolio
# Email: your-email@example.com

# 7. Wait for SSL certificates to be issued (2-3 minutes)
```

### Result:
✅ https://devflow.techflow-demo.duckdns.org - DevFlow Pro
✅ https://ats.techflow-demo.duckdns.org - ATS Pro
✅ https://portfolio.techflow-demo.duckdns.org - Portfolio

All with valid SSL certificates!

---

## Troubleshooting

### DNS Not Resolving

**Check DNS propagation:**
```bash
nslookup devflow.yourdomain.duckdns.org
dig devflow.yourdomain.duckdns.org
```

**Wait 5-10 minutes** for DNS to propagate.

### SSL Certificate Fails

**Common issues:**

1. **DNS not pointing to server:**
   ```bash
   # Verify DNS points to your IP
   nslookup yourdomain.duckdns.org
   ```

2. **Port 80 blocked:**
   ```bash
   # Test from external
   curl http://yourdomain.duckdns.org

   # Check firewall
   ufw status
   ```

3. **Nginx not running:**
   ```bash
   systemctl status nginx
   systemctl start nginx
   ```

4. **Try manual certificate:**
   ```bash
   certbot certonly --standalone -d yourdomain.duckdns.org
   ```

### Application Not Accessible

**Check if applications are running:**
```bash
# DevFlow Pro (port 8000)
curl http://localhost:8000

# ATS Pro (port 8080)
curl http://localhost:8080

# Portfolio (port 8081)
curl http://localhost:8081

# Check Docker containers
docker ps
```

**Check Nginx logs:**
```bash
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log
```

**Test Nginx config:**
```bash
nginx -t
systemctl reload nginx
```

---

## Manual SSL Setup (Alternative)

If the automated script doesn't work, use this manual method:

### 1. Install Certbot
```bash
apt update
apt install -y certbot python3-certbot-nginx
```

### 2. Create Nginx Config
```bash
cat > /etc/nginx/sites-available/devflow-pro << 'EOF'
server {
    listen 80;
    server_name devflow.yourdomain.duckdns.org;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF

ln -s /etc/nginx/sites-available/devflow-pro /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

### 3. Get SSL Certificate
```bash
certbot --nginx -d devflow.yourdomain.duckdns.org
```

### 4. Repeat for Other Apps
```bash
# ATS Pro
certbot --nginx -d ats.yourdomain.duckdns.org

# Portfolio
certbot --nginx -d portfolio.yourdomain.duckdns.org
```

---

## SSL Certificate Management

### Check Certificate Status
```bash
certbot certificates
```

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
Certificates: /etc/letsencrypt/live/yourdomain/
  - fullchain.pem (certificate + chain)
  - privkey.pem (private key)
  - cert.pem (certificate only)
  - chain.pem (chain only)
```

---

## Production Checklist

After setup, verify:

- [ ] All domains resolve to correct IP
- [ ] All applications accessible via HTTPS
- [ ] SSL certificates valid (check with browser)
- [ ] HTTP redirects to HTTPS
- [ ] No mixed content warnings
- [ ] Auto-renewal configured
- [ ] Monitoring includes SSL expiration checks

### Test Commands
```bash
# Test DevFlow Pro
curl -I https://devflow.yourdomain.duckdns.org

# Test ATS Pro
curl -I https://ats.yourdomain.duckdns.org

# Test Portfolio
curl -I https://portfolio.yourdomain.duckdns.org

# Check SSL expiration
openssl s_client -connect devflow.yourdomain.duckdns.org:443 -servername devflow.yourdomain.duckdns.org < /dev/null 2>/dev/null | openssl x509 -noout -dates
```

---

## Cost Comparison

| Option | Cost | Setup Time | Renewal | Professional |
|--------|------|------------|---------|--------------|
| DuckDNS | Free | 5 min | Auto | ⭐⭐⭐ |
| FreeDNS | Free | 10 min | Auto | ⭐⭐⭐ |
| No-IP | Free | 10 min | Manual (30d) | ⭐⭐ |
| eu.org | Free | 2-3 weeks | Auto | ⭐⭐⭐⭐ |
| .com domain | $10-15/yr | 15 min | Manual | ⭐⭐⭐⭐⭐ |

---

## My Recommendation

**For Testing:** Use **DuckDNS** - it's the fastest and easiest.

**For Production:**
1. If budget allows, buy a real domain (.com, .io, .dev) from Namecheap/Cloudflare
2. If must be free, use **eu.org** (but wait 2-3 weeks for approval)

**Quick Start Now:**
1. Go to https://www.duckdns.org
2. Login and create: `mbfouad-devflow.duckdns.org`
3. Point to: `31.220.90.121`
4. Run the setup script above
5. Done! 🎉

---

## Need Help?

If you encounter issues:

1. Check DNS: `nslookup yourdomain.duckdns.org`
2. Check ports: `telnet yourdomain.duckdns.org 80`
3. Check nginx: `nginx -t`
4. Check certbot: `certbot certificates`
5. Check logs: `tail -f /var/log/nginx/error.log`

Still stuck? Share the error message and I'll help you troubleshoot!
