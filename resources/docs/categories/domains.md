---
title: Domain Management
description: Manage domains, DNS, and SSL certificates
---

# Domain Management

Manage your project domains with ease using DevFlow Pro's domain management features.

## Add Domain

Add a custom domain to your project.

**Steps:**

1. Navigate to project settings
2. Click "Add Domain"
3. Enter your domain name (e.g., myapp.com)
4. Configure DNS settings
5. SSL certificate generated automatically
6. Domain becomes active

**Requirements:**

- Domain must be registered
- DNS must point to your server IP
- A record or CNAME configured

**What DevFlow does:**

- Creates Nginx/Apache virtual host
- Generates SSL certificate (Let's Encrypt)
- Configures HTTPS redirect
- Sets up www redirect if needed

## Primary Domain

Set which domain is the "main" one.

**Purpose:**

- All other domains redirect to primary
- Canonical URL for SEO
- Default domain for email notifications

**How to set:**

1. Go to project domains
2. Click "Set as Primary" on desired domain
3. Other domains automatically redirect

**Example:**

- myapp.com (primary)
- www.myapp.com → redirects to myapp.com
- myapp.net → redirects to myapp.com

## Domain Redirects

Configure automatic redirects between domains.

**Redirect types:**

- www to non-www (or vice versa)
- HTTP to HTTPS (always recommended)
- Old domain to new domain
- Custom redirect rules

**Configuration:**

1. Select source domain
2. Select target domain
3. Choose redirect type (301, 302)
4. Save and test

## Subdomain Management

Create and manage subdomains for your projects.

**Common subdomains:**

- api.myapp.com
- admin.myapp.com
- staging.myapp.com
- blog.myapp.com

**Setup:**

1. Add subdomain in domain settings
2. Configure DNS A record
3. SSL certificate auto-generated
4. Point to specific project or path

## Domain Verification

Verify domain ownership before SSL certificate issuance.

**Verification methods:**

- DNS TXT record
- HTTP file upload
- Email verification
- Automatic (if DNS already configured)

**Steps:**

1. Add domain to project
2. Choose verification method
3. Complete verification steps
4. DevFlow verifies automatically
5. SSL certificate issued

## DNS Management

View and configure DNS settings.

**DNS records shown:**

- A record (points to IP)
- AAAA record (IPv6)
- CNAME record (alias)
- MX record (email)
- TXT record (verification)

**Features:**

- Check DNS propagation
- Test DNS resolution
- View current DNS configuration
- DNS troubleshooting tools

## Custom Domain Configuration

Advanced domain configuration options.

**Options:**

- Custom document root
- Custom PHP version per domain
- Custom Nginx configuration
- Custom headers
- CORS settings
- Rate limiting per domain

## Domain Health Checks

Monitor domain accessibility and SSL status.

**Checks performed:**

- Domain resolves correctly
- SSL certificate valid
- Response time
- HTTP status codes
- SSL expiration date

**Alerts:**

- SSL expiring in 30 days
- Domain not resolving
- SSL certificate invalid
- Slow response time

## Wildcard Domains

Configure wildcard domains for multi-tenant apps.

**Format:** *.myapp.com

**Use cases:**

- tenant1.myapp.com
- tenant2.myapp.com
- customer-name.myapp.com

**Requirements:**

- Wildcard SSL certificate
- DNS wildcard A record
- Application wildcard routing

## Domain Transfer

Transfer domain between projects.

**Steps:**

1. Select domain to transfer
2. Choose target project
3. Confirm transfer
4. DNS and SSL updated automatically

**What happens:**

- Nginx config updated
- SSL certificate moved
- DNS records unchanged
- Domain reassigned to new project
