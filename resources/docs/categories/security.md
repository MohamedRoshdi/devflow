---
title: Security
description: Security features, 2FA, API tokens, and access control
---

# Security

Protect your infrastructure with DevFlow Pro's security features.

## Two-Factor Authentication (2FA)

Add extra security layer to your account.

**How to enable:**

1. Go to account settings
2. Click "Enable 2FA"
3. Scan QR code with authenticator app
4. Enter verification code
5. Save backup codes

**Authenticator apps:**

- Google Authenticator
- Authy
- 1Password
- Microsoft Authenticator

**Best practices:**

- Save backup codes securely
- Use 2FA on all accounts
- Don't share codes
- Re-enable if phone lost

## API Tokens

Create API tokens for programmatic access.

**Token types:**

- Read-only tokens
- Deploy tokens
- Admin tokens
- Custom scope tokens

**Creating tokens:**

1. Go to API tokens settings
2. Click "Create Token"
3. Enter token name
4. Select permissions
5. Copy token (shown once)

**Token permissions:**

- View projects
- Deploy projects
- Manage servers
- View logs
- Manage domains
- Full access

**Security:**

- Tokens shown only once
- Rotate tokens regularly
- Use minimal permissions
- Revoke unused tokens
- Monitor token usage

## Session Management

Manage active login sessions.

**Session information:**

- Device type
- Browser
- IP address
- Location
- Last active time

**Actions:**

- View all sessions
- Revoke individual session
- Revoke all other sessions
- Force re-authentication

**Security settings:**

- Session timeout
- Remember me duration
- Concurrent session limit
- IP whitelist

## IP Whitelist

Restrict access to specific IP addresses.

**Configuration:**

1. Enable IP whitelist
2. Add allowed IP addresses
3. Add IP ranges if needed
4. Save settings

**Use cases:**

- Office IP only
- VPN IP only
- Specific countries
- Known safe IPs

**Emergency access:**

- Bypass option available
- Email verification required
- Temporary access grant

## Audit Logs

Track all security-relevant activities.

**Logged events:**

- User login/logout
- Failed login attempts
- 2FA enabled/disabled
- API token created/revoked
- Permission changes
- Settings changes
- Deployment actions
- Server access

**Log retention:**

- Stored for 2 years
- Exportable for compliance
- Searchable and filterable
- Immutable records

## Role-Based Access Control

Assign roles and permissions to team members.

**Roles:**

- Owner: Full control
- Admin: Manage everything except billing
- Developer: Deploy and manage projects
- Viewer: Read-only access

**Permissions:**

- View projects
- Deploy projects
- Manage servers
- View logs
- Manage users
- Manage billing
- Delete projects

**Custom roles:**

- Create custom roles
- Define specific permissions
- Assign to users
- Role templates

## SSH Key Security

Secure SSH key management.

**Best practices:**

- Use ed25519 keys
- Add passphrase to keys
- Rotate keys every 90 days
- Never share private keys
- Store keys securely

**Key management:**

- Generate keys in DevFlow
- Upload existing keys
- Rotate keys easily
- Revoke compromised keys
- Audit key usage

## Secrets Management

Securely store and manage secrets.

**What to store:**

- Database passwords
- API keys
- OAuth tokens
- Encryption keys
- Third-party credentials

**Security features:**

- Encrypted at rest
- Encrypted in transit
- Access logging
- Version history
- Automatic rotation (optional)

## Security Scanning

Automated security vulnerability scanning.

**Scans performed:**

- Dependency vulnerabilities
- Outdated packages
- Known CVEs
- Configuration issues
- Security headers

**Scan frequency:**

- Daily automatic scans
- Manual scan on demand
- Post-deployment scans

**Alerts:**

- Critical vulnerabilities
- High-risk issues
- Security updates available

## Compliance

Meet security compliance requirements.

**Standards supported:**

- SOC 2
- ISO 27001
- GDPR
- HIPAA (with configuration)
- PCI DSS (for payment processing)

**Compliance features:**

- Audit logs
- Data encryption
- Access controls
- Security scanning
- Compliance reports

## Security Best Practices

Recommendations for optimal security.

**Account security:**

- Enable 2FA
- Use strong passwords
- Rotate passwords regularly
- Don't share credentials
- Use password manager

**Server security:**

- Keep software updated
- Use firewall
- Disable unused services
- Monitor logs
- Use fail2ban

**Application security:**

- Keep dependencies updated
- Use HTTPS everywhere
- Validate user input
- Use prepared statements
- Implement CSRF protection

**Access control:**

- Principle of least privilege
- Regular access reviews
- Remove unused accounts
- Monitor suspicious activity
