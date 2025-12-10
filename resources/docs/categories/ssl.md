---
title: SSL Certificates
description: SSL certificate management and automation
---

# SSL Certificates

Secure your websites with automatic SSL certificate management.

## Automatic SSL

DevFlow Pro automatically generates and manages SSL certificates.

**How it works:**

1. You add a domain
2. DevFlow verifies domain ownership
3. Let's Encrypt certificate requested
4. Certificate installed automatically
5. HTTPS configured
6. Auto-renewal configured

**Benefits:**

- Free SSL certificates (Let's Encrypt)
- Automatic renewal (every 60 days)
- No manual configuration needed
- HTTPS redirect enabled
- A+ SSL rating

## SSL Certificate Types

Different SSL certificate types supported.

**Let's Encrypt (Recommended):**

- Free
- Auto-renewal
- Domain validated
- 90-day expiration
- Wildcard support

**Custom SSL:**

- Upload your own certificate
- Extended validation (EV)
- Organization validated (OV)
- Multi-year certificates
- Paid certificates

## SSL Renewal

Certificates automatically renewed before expiration.

**Renewal process:**

1. DevFlow checks expiration (30 days before)
2. New certificate requested
3. Certificate installed
4. Web server reloaded
5. Old certificate removed
6. Notification sent

**Manual renewal:**

- Click "Renew Now" button
- Force renewal even if not expired
- Useful after domain changes

## SSL Monitoring

Monitor SSL certificate health and expiration.

**Monitored:**

- Days until expiration
- Certificate validity
- Certificate chain
- Cipher strength
- SSL protocol version
- Certificate issuer

**Alerts:**

- Certificate expiring in 30 days
- Certificate expired
- Certificate invalid
- Weak cipher detected
- SSL grade downgrade

## Wildcard SSL

Secure all subdomains with a single certificate.

**Format:** *.myapp.com

**Covers:**

- myapp.com
- www.myapp.com
- api.myapp.com
- admin.myapp.com
- any-subdomain.myapp.com

**Setup:**

1. Add wildcard domain
2. Verify domain ownership (DNS)
3. Wildcard certificate requested
4. Certificate applied to all subdomains

## Custom SSL Upload

Use your own SSL certificate instead of Let's Encrypt.

**Required files:**

- Certificate file (.crt)
- Private key file (.key)
- Certificate chain (intermediate certificates)

**Steps:**

1. Navigate to domain SSL settings
2. Click "Upload Custom Certificate"
3. Upload certificate files
4. Verify certificate
5. Install and activate

**Use cases:**

- Extended validation certificates
- Organization validated certificates
- Paid certificates with longer expiration
- Certificates from corporate CA

## Force HTTPS

Automatically redirect HTTP to HTTPS.

**How it works:**

- All HTTP requests redirected to HTTPS
- 301 permanent redirect
- Ensures all traffic encrypted
- Improves SEO ranking

**Configuration:**

- Enabled by default
- Can be disabled per domain
- HSTS header option
- Mixed content warnings prevented

## SSL Troubleshooting

Common SSL issues and solutions.

### Certificate Not Valid

**Symptoms:** Browser shows "Not Secure" warning

**Solutions:**

- Check domain DNS points to correct server
- Verify domain ownership
- Renew certificate manually
- Check certificate expiration date

### Mixed Content Warnings

**Symptoms:** Some resources loaded over HTTP

**Solutions:**

- Update hardcoded HTTP URLs to HTTPS
- Use protocol-relative URLs (//)
- Check external resources
- Enable Content Security Policy

### Certificate Chain Issues

**Symptoms:** "Certificate chain incomplete"

**Solutions:**

- Install intermediate certificates
- Use full certificate chain
- Regenerate certificate
- Check certificate order

## SSL Best Practices

Recommendations for optimal SSL configuration.

**Security:**

- Always use HTTPS
- Enable HSTS (HTTP Strict Transport Security)
- Use TLS 1.2 or higher
- Disable weak ciphers
- Enable forward secrecy

**Performance:**

- Enable HTTP/2
- Use OCSP stapling
- Enable session resumption
- Use certificate caching

**Monitoring:**

- Set up expiration alerts
- Monitor SSL grades
- Check certificate regularly
- Test SSL configuration
