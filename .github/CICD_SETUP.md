# CI/CD Setup Guide for DevFlow Pro

## Overview

This document provides comprehensive instructions for setting up GitHub Actions CI/CD workflows for DevFlow Pro.

## Workflow Files

### Main Workflows

1. **`.github/workflows/ci.yml`** - Continuous Integration
   - Runs tests on PHP 8.2, 8.3, 8.4
   - MySQL and Redis services
   - PHPStan static analysis
   - Code coverage reporting
   - Triggered on: push to main/master/develop, pull requests

2. **`.github/workflows/deploy.yml`** - Deployment
   - Automated deployment to production/staging
   - Runs tests before deploying
   - SSH-based deployment
   - Health checks after deployment
   - Rollback on failure
   - Discord notifications
   - Triggered on: push to main/master, manual dispatch

3. **`.github/workflows/code-quality.yml`** - Code Quality
   - PHPStan analysis (Level 5)
   - Laravel Pint (code style)
   - Prettier (JS/CSS/Vue formatting)
   - Code complexity analysis
   - Test coverage reports
   - Triggered on: pull requests

4. **`.github/workflows/scheduled.yml`** - Scheduled Tasks
   - Daily security audits (2 AM UTC)
   - Weekly dependency updates (Monday 3 AM UTC)
   - Artifact cleanup
   - Database backup verification
   - Triggered on: schedule, manual dispatch

5. **`.github/workflows/release.yml`** - Release Management
   - Create GitHub releases
   - Build production archives
   - Deploy to production
   - Release notifications
   - Triggered on: version tags (v*.*.*)

## Required GitHub Secrets

Configure these secrets in your GitHub repository settings:
**Settings → Secrets and variables → Actions → New repository secret**

### SSH Deployment Secrets

```
SSH_PRIVATE_KEY
Description: Private SSH key for server access
How to generate:
  ssh-keygen -t ed25519 -C "github-actions@devflow"
  # Add public key to server: ~/.ssh/authorized_keys
  # Copy private key content to this secret

SSH_HOST
Description: Server hostname or IP address
Example: 198.51.100.42 or server.example.com

SSH_USER
Description: SSH username for deployment
Example: deployer or root

SSH_PORT (optional)
Description: SSH port
Default: 22
```

### Deployment Configuration

```
DEPLOY_PATH
Description: Absolute path to application on server
Example: /var/www/devflow-pro

APP_URL
Description: Application URL for health checks
Example: https://devflow.example.com
```

### Notifications

```
DISCORD_WEBHOOK
Description: Discord webhook URL for notifications
How to get:
  1. Go to Discord Server Settings → Integrations → Webhooks
  2. Create new webhook
  3. Copy webhook URL
Example: https://discord.com/api/webhooks/123456789/abcdefg...

SLACK_WEBHOOK (optional)
Description: Slack webhook URL for notifications
```

### Optional Secrets

```
CODECOV_TOKEN
Description: Codecov token for coverage reporting
How to get:
  1. Sign up at https://codecov.io
  2. Add your repository
  3. Copy the upload token

SENTRY_DSN (if using Sentry)
Description: Sentry DSN for error tracking
```

## Environment Setup

### 1. Repository Settings

**Branch Protection Rules** (Settings → Branches):

```yaml
Branch: main
Require pull request reviews: ✓
Require status checks to pass: ✓
  - tests (PHP 8.4)
  - lint
  - security
Require branches to be up to date: ✓
```

### 2. GitHub Environments

**Create Production Environment** (Settings → Environments → New environment):

```
Environment name: production
Environment protection rules:
  - Required reviewers: [Select reviewers]
  - Wait timer: 0 minutes (or as needed)
Environment secrets:
  - All deployment secrets above
```

**Create Staging Environment** (optional):

```
Environment name: staging
Different SSH_HOST and DEPLOY_PATH
```

### 3. Server Setup

#### Prepare Server for CI/CD Deployments

```bash
# Create deployment user
sudo adduser deployer
sudo usermod -aG sudo deployer
sudo usermod -aG www-data deployer

# Switch to deployer user
su - deployer

# Create .ssh directory
mkdir -p ~/.ssh
chmod 700 ~/.ssh

# Add GitHub Actions public key to authorized_keys
nano ~/.ssh/authorized_keys
# Paste your public key generated earlier
chmod 600 ~/.ssh/authorized_keys

# Create application directory
sudo mkdir -p /var/www/devflow-pro
sudo chown deployer:www-data /var/www/devflow-pro
sudo chmod 775 /var/www/devflow-pro

# Clone repository (initial setup)
cd /var/www/devflow-pro
git clone https://github.com/your-username/devflow-pro.git .

# Install dependencies
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# Setup permissions
sudo chown -R deployer:www-data /var/www/devflow-pro
sudo chmod -R 775 storage bootstrap/cache
```

#### Configure SSH for Passwordless Deployment

```bash
# On server, edit SSH config
sudo nano /etc/ssh/sshd_config

# Ensure these settings:
PubkeyAuthentication yes
PasswordAuthentication no
PermitRootLogin no

# Restart SSH
sudo systemctl restart sshd
```

### 4. Health Check Endpoint

Add a health check route to your application:

**File: `routes/web.php`**

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'version' => config('app.version'),
    ]);
})->name('health');
```

## Composer Scripts

The following scripts are available:

```bash
# Run all tests
composer test

# Run tests with HTML coverage report
composer test:coverage

# Run tests with Clover coverage (for CI)
composer test:coverage-clover

# Run PHPStan analysis
composer analyse

# Generate PHPStan baseline
composer analyse:baseline

# Check code style (dry run)
composer lint

# Fix code style issues
composer lint:fix

# Run all CI checks (lint, analyse, test)
composer ci
```

## npm Scripts

```bash
# Development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Format code
npm run format

# Check formatting
npm run format:check
```

## Usage Examples

### Running Tests Locally

```bash
# Create testing database
mysql -u root -p -e "CREATE DATABASE devflow_test;"

# Run tests
php artisan test

# With coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=DeploymentTest
```

### Triggering Manual Deployment

1. Go to **Actions** tab in GitHub
2. Select **Deploy** workflow
3. Click **Run workflow**
4. Select environment (production/staging)
5. Click **Run workflow**

### Creating a Release

```bash
# Ensure CHANGELOG.md is updated
# Create and push a version tag
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0

# GitHub Actions will automatically:
# - Run all tests
# - Create GitHub release
# - Build production archive
# - Deploy to production (for stable releases)
```

## Status Badges

Add these badges to your `README.md`:

```markdown
![CI Status](https://github.com/your-username/devflow-pro/actions/workflows/ci.yml/badge.svg)
![Deploy Status](https://github.com/your-username/devflow-pro/actions/workflows/deploy.yml/badge.svg)
![Code Quality](https://github.com/your-username/devflow-pro/actions/workflows/code-quality.yml/badge.svg)
[![codecov](https://codecov.io/gh/your-username/devflow-pro/branch/main/graph/badge.svg)](https://codecov.io/gh/your-username/devflow-pro)
```

## Troubleshooting

### Common Issues

#### 1. SSH Connection Failed

```bash
# Test SSH connection manually
ssh -i ~/.ssh/id_ed25519 deployer@your-server.com

# Check server logs
sudo tail -f /var/log/auth.log
```

#### 2. Permission Denied Errors

```bash
# Fix Laravel storage permissions
sudo chown -R deployer:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 3. Database Migration Fails

```bash
# Check database connection
php artisan tinker
DB::connection()->getPdo();

# Run migrations manually
php artisan migrate --force
```

#### 4. Failed Tests in CI

```bash
# Check if .env.testing exists
# Verify database connection in CI
# Check MySQL service status in workflow logs
```

### Debugging Workflow Failures

1. **Check workflow logs**: Actions tab → Failed workflow → View logs
2. **Enable debug logging**: Repository Settings → Secrets → Add `ACTIONS_STEP_DEBUG = true`
3. **Re-run with debug**: Actions tab → Failed workflow → Re-run jobs → Re-run with debug

## Security Best Practices

1. **Never commit secrets** to the repository
2. **Use environment-specific secrets** for staging/production
3. **Rotate SSH keys** periodically
4. **Enable 2FA** on GitHub accounts
5. **Review deployment logs** regularly
6. **Use signed commits** for production deployments
7. **Limit SSH access** to deployment user only
8. **Monitor security audit** results from scheduled workflows

## Continuous Improvement

### Monitoring CI/CD Performance

- Track workflow execution times
- Optimize caching strategies
- Review and update dependencies regularly
- Monitor test coverage trends
- Analyze failed deployment patterns

### Recommended Next Steps

1. Set up **Codecov** for coverage visualization
2. Configure **Sentry** for error tracking
3. Add **performance monitoring** (New Relic, DataDog)
4. Implement **blue-green deployments** for zero-downtime
5. Set up **automated rollback** triggers
6. Create **staging environment** for pre-production testing

## Support

For issues related to CI/CD setup:
1. Check GitHub Actions documentation
2. Review workflow logs
3. Open an issue in the repository
4. Contact the DevOps team

---

**Last Updated:** 2025-12-03
**Maintained By:** DevFlow Pro Team
