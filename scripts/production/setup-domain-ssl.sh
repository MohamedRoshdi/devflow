#!/bin/bash

###############################################################################
# Domain & SSL Setup Script for DevFlow Pro
# Sets up domains, SSL certificates, and Nginx reverse proxy
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=========================================="
echo "DevFlow Pro - Domain & SSL Setup"
echo -e "==========================================${NC}\n"

# Get domain information from user
read -p "Enter your primary domain (e.g., techflow.digital or yourname.duckdns.org): " PRIMARY_DOMAIN
read -p "Enter subdomain for DevFlow Pro (default: devflow): " DEVFLOW_SUBDOMAIN
DEVFLOW_SUBDOMAIN=${DEVFLOW_SUBDOMAIN:-devflow}
read -p "Enter subdomain for ATS Pro (default: ats): " ATS_SUBDOMAIN
ATS_SUBDOMAIN=${ATS_SUBDOMAIN:-ats}
read -p "Enter subdomain for Portfolio (default: portfolio): " PORTFOLIO_SUBDOMAIN
PORTFOLIO_SUBDOMAIN=${PORTFOLIO_SUBDOMAIN:-portfolio}
read -p "Enter your email for SSL certificates: " SSL_EMAIL

# Construct full domains
DEVFLOW_DOMAIN="${DEVFLOW_SUBDOMAIN}.${PRIMARY_DOMAIN}"
ATS_DOMAIN="${ATS_SUBDOMAIN}.${PRIMARY_DOMAIN}"
PORTFOLIO_DOMAIN="${PORTFOLIO_SUBDOMAIN}.${PRIMARY_DOMAIN}"

echo -e "\n${YELLOW}Configuration:${NC}"
echo "  DevFlow Pro:  https://${DEVFLOW_DOMAIN}"
echo "  ATS Pro:      https://${ATS_DOMAIN}"
echo "  Portfolio:    https://${PORTFOLIO_DOMAIN}"
echo "  SSL Email:    ${SSL_EMAIL}"
echo ""
read -p "Is this correct? (y/n): " CONFIRM

if [ "$CONFIRM" != "y" ]; then
    echo "Setup cancelled"
    exit 1
fi

###############################################################################
# Install Required Packages
###############################################################################
echo -e "\n${BLUE}[1/6] Installing required packages...${NC}"
apt update
apt install -y nginx certbot python3-certbot-nginx

###############################################################################
# Configure Nginx for DevFlow Pro
###############################################################################
echo -e "\n${BLUE}[2/6] Configuring Nginx for DevFlow Pro...${NC}"
cat > /etc/nginx/sites-available/devflow-pro << EOF
server {
    listen 80;
    server_name ${DEVFLOW_DOMAIN};

    # Let's Encrypt verification
    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    location / {
        proxy_pass http://localhost:8000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_cache_bypass \$http_upgrade;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
EOF

ln -sf /etc/nginx/sites-available/devflow-pro /etc/nginx/sites-enabled/

###############################################################################
# Configure Nginx for ATS Pro
###############################################################################
echo -e "\n${BLUE}[3/6] Configuring Nginx for ATS Pro...${NC}"
cat > /etc/nginx/sites-available/ats-pro << EOF
server {
    listen 80;
    server_name ${ATS_DOMAIN};

    # Let's Encrypt verification
    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    location / {
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_cache_bypass \$http_upgrade;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
EOF

ln -sf /etc/nginx/sites-available/ats-pro /etc/nginx/sites-enabled/

###############################################################################
# Configure Nginx for Portfolio
###############################################################################
echo -e "\n${BLUE}[4/6] Configuring Nginx for Portfolio...${NC}"
cat > /etc/nginx/sites-available/portfolio << EOF
server {
    listen 80;
    server_name ${PORTFOLIO_DOMAIN};

    # Let's Encrypt verification
    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    location / {
        proxy_pass http://localhost:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_cache_bypass \$http_upgrade;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
EOF

ln -sf /etc/nginx/sites-available/portfolio /etc/nginx/sites-enabled/

###############################################################################
# Test and Reload Nginx
###############################################################################
echo -e "\n${BLUE}[5/6] Testing Nginx configuration...${NC}"
nginx -t

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Nginx configuration is valid${NC}"
    systemctl reload nginx
else
    echo -e "${RED}✗ Nginx configuration error${NC}"
    exit 1
fi

###############################################################################
# Obtain SSL Certificates
###############################################################################
echo -e "\n${BLUE}[6/6] Obtaining SSL certificates...${NC}"
echo -e "${YELLOW}Note: Make sure DNS records are pointing to this server IP!${NC}"
echo -e "${YELLOW}Server IP: $(curl -s ifconfig.me)${NC}\n"
read -p "Press Enter when DNS is ready, or Ctrl+C to cancel..."

# Obtain certificates
certbot --nginx \
    -d ${DEVFLOW_DOMAIN} \
    -d ${ATS_DOMAIN} \
    -d ${PORTFOLIO_DOMAIN} \
    --email ${SSL_EMAIL} \
    --agree-tos \
    --no-eff-email \
    --redirect

if [ $? -eq 0 ]; then
    echo -e "\n${GREEN}=========================================="
    echo "✓ SSL Setup Complete!"
    echo -e "==========================================${NC}\n"
    echo -e "Your applications are now available at:"
    echo -e "  ${GREEN}✓ https://${DEVFLOW_DOMAIN}${NC}"
    echo -e "  ${GREEN}✓ https://${ATS_DOMAIN}${NC}"
    echo -e "  ${GREEN}✓ https://${PORTFOLIO_DOMAIN}${NC}"
    echo ""
    echo -e "SSL certificates will auto-renew via certbot timer"
    echo -e "Check renewal status: ${YELLOW}systemctl status certbot.timer${NC}"
else
    echo -e "\n${RED}✗ SSL certificate setup failed${NC}"
    echo "Please check:"
    echo "  1. DNS records are pointing to this server"
    echo "  2. Port 80 and 443 are open in firewall"
    echo "  3. No other service is using port 80/443"
    exit 1
fi

###############################################################################
# Configure Auto-renewal
###############################################################################
echo -e "\n${BLUE}Configuring SSL auto-renewal...${NC}"
systemctl enable certbot.timer
systemctl start certbot.timer

echo -e "${GREEN}✓ Auto-renewal configured${NC}"

###############################################################################
# Update Application URLs
###############################################################################
echo -e "\n${BLUE}Updating application configurations...${NC}"

# Update DevFlow Pro .env
if [ -f /var/www/devflow-pro/.env ]; then
    sed -i "s|APP_URL=.*|APP_URL=https://${DEVFLOW_DOMAIN}|" /var/www/devflow-pro/.env
    echo -e "${GREEN}✓ Updated DevFlow Pro URL${NC}"
fi

# Update ATS Pro .env
if [ -f /var/www/ats-pro/.env ]; then
    sed -i "s|APP_URL=.*|APP_URL=https://${ATS_DOMAIN}|" /var/www/ats-pro/.env
    echo -e "${GREEN}✓ Updated ATS Pro URL${NC}"
fi

# Update Portfolio .env
if [ -f /var/www/portfolio/.env ]; then
    sed -i "s|APP_URL=.*|APP_URL=https://${PORTFOLIO_DOMAIN}|" /var/www/portfolio/.env
    echo -e "${GREEN}✓ Updated Portfolio URL${NC}"
fi

###############################################################################
# Summary
###############################################################################
cat << EOF

${GREEN}=========================================="
Domain & SSL Setup Complete!"
==========================================${NC}

${YELLOW}Your Applications:${NC}
  • DevFlow Pro:  https://${DEVFLOW_DOMAIN}
  • ATS Pro:      https://${ATS_DOMAIN}
  • Portfolio:    https://${PORTFOLIO_DOMAIN}

${YELLOW}SSL Certificate Info:${NC}
  • Certificates Location: /etc/letsencrypt/live/
  • Auto-renewal: Enabled (runs twice daily)
  • Check renewal: sudo certbot renew --dry-run

${YELLOW}Nginx Configuration:${NC}
  • Config Location: /etc/nginx/sites-available/
  • Reload Nginx: sudo systemctl reload nginx
  • Test Config: sudo nginx -t

${YELLOW}Next Steps:${NC}
  1. Test all applications via HTTPS
  2. Update DNS records if needed
  3. Configure firewall to allow ports 80, 443
  4. Set up monitoring for SSL expiration

${YELLOW}Useful Commands:${NC}
  • Check SSL status: sudo certbot certificates
  • Renew certificates: sudo certbot renew
  • Nginx logs: sudo tail -f /var/log/nginx/error.log

EOF

exit 0
