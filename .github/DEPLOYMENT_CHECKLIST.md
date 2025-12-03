# CI/CD Deployment Checklist

## Pre-Deployment Setup

### 1. GitHub Repository Configuration

- [ ] Repository created on GitHub
- [ ] Local repository connected to GitHub remote
- [ ] Main/master branch exists and is up to date

### 2. GitHub Secrets Configuration

Navigate to: **Settings → Secrets and variables → Actions → New repository secret**

#### Required Secrets
- [ ] `SSH_PRIVATE_KEY` - SSH private key for server access
- [ ] `SSH_HOST` - Server IP address or hostname
- [ ] `SSH_USER` - Deployment user (e.g., deployer)
- [ ] `DEPLOY_PATH` - Full deployment path (e.g., /var/www/devflow-pro)
- [ ] `APP_URL` - Application URL for health checks

#### Optional Secrets (Recommended)
- [ ] `DISCORD_WEBHOOK` - Discord webhook for notifications
- [ ] `CODECOV_TOKEN` - Codecov token for coverage reports
- [ ] `SLACK_WEBHOOK` - Slack webhook for notifications
- [ ] `SENTRY_DSN` - Sentry DSN for error tracking (if applicable)

### 3. SSH Key Generation

```bash
# Generate SSH key pair
ssh-keygen -t ed25519 -C "github-actions@devflow" -f ~/.ssh/github_actions_key

# Copy public key to server
ssh-copy-id -i ~/.ssh/github_actions_key.pub deployer@your-server.com

# Test connection
ssh -i ~/.ssh/github_actions_key deployer@your-server.com

# Copy private key to GitHub secrets
cat ~/.ssh/github_actions_key
```

- [ ] SSH key pair generated
- [ ] Public key added to server
- [ ] Private key added to GitHub secrets
- [ ] SSH connection tested successfully

### 4. Server Preparation

#### User Setup
```bash
# Create deployment user
sudo adduser deployer
sudo usermod -aG sudo deployer
sudo usermod -aG www-data deployer
```

- [ ] Deployment user created
- [ ] User added to appropriate groups
- [ ] SSH access configured for user

#### Directory Setup
```bash
# Create application directory
sudo mkdir -p /var/www/devflow-pro
sudo chown deployer:www-data /var/www/devflow-pro
sudo chmod 775 /var/www/devflow-pro
```

- [ ] Application directory created
- [ ] Correct ownership set
- [ ] Proper permissions configured

#### SSH Configuration
```bash
# Edit SSH config
sudo nano /etc/ssh/sshd_config

# Ensure these settings:
# PubkeyAuthentication yes
# PasswordAuthentication no
# PermitRootLogin no

# Restart SSH
sudo systemctl restart sshd
```

- [ ] SSH config updated
- [ ] Public key authentication enabled
- [ ] Password authentication disabled
- [ ] SSH service restarted

### 5. Application Setup on Server

```bash
# Clone repository
cd /var/www/devflow-pro
git clone https://github.com/your-username/devflow-pro.git .

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node.js dependencies
npm ci

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Build assets
npm run build

# Set permissions
sudo chown -R deployer:www-data /var/www/devflow-pro
sudo chmod -R 775 storage bootstrap/cache

# Run migrations
php artisan migrate --force
```

- [ ] Repository cloned
- [ ] Composer dependencies installed
- [ ] Node.js dependencies installed
- [ ] Environment file configured
- [ ] Application key generated
- [ ] Assets built
- [ ] Permissions set correctly
- [ ] Database migrations run

### 6. Health Check Endpoint

Add to `routes/web.php`:
```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'version' => config('app.version'),
    ]);
})->name('health');
```

Test:
```bash
curl https://your-domain.com/health
```

- [ ] Health check route added
- [ ] Health check endpoint tested
- [ ] Returns 200 OK status

### 7. Branch Protection Rules

Navigate to: **Settings → Branches → Add rule**

```
Branch name pattern: main (or master)
□ Require pull request reviews before merging
  ☑ Require approvals: 1
□ Require status checks to pass before merging
  ☑ Require branches to be up to date before merging
  Status checks:
    ☑ tests (PHP 8.4)
    ☑ lint
    ☑ security
□ Include administrators
```

- [ ] Branch protection rule created
- [ ] Required status checks configured
- [ ] PR reviews required

### 8. GitHub Environments

Navigate to: **Settings → Environments → New environment**

#### Production Environment
```
Environment name: production
Environment protection rules:
  ☑ Required reviewers (select reviewers)
  ☑ Wait timer: 0 minutes

Environment secrets:
  - SSH_PRIVATE_KEY
  - SSH_HOST
  - SSH_USER
  - DEPLOY_PATH
  - APP_URL
```

- [ ] Production environment created
- [ ] Protection rules configured
- [ ] Environment secrets added

#### Staging Environment (Optional)
- [ ] Staging environment created
- [ ] Separate secrets configured
- [ ] Different deployment path set

### 9. Local Development Setup

```bash
# Install dependencies
npm install

# Install Prettier (new)
npm install --save-dev prettier@^3.1.0

# Verify commands work
composer test
composer analyse
composer lint
npm run format:check
```

- [ ] npm packages installed
- [ ] Prettier installed
- [ ] Composer scripts working
- [ ] npm scripts working

## Post-Deployment Verification

### 1. Initial Push Test

```bash
# Make a small change
echo "# CI/CD Test" >> TESTING.md
git add TESTING.md
git commit -m "test: CI/CD workflows"
git push origin develop
```

- [ ] Changes pushed to develop branch
- [ ] CI workflow triggered
- [ ] All tests passed
- [ ] No errors in workflow logs

### 2. Pull Request Test

```bash
# Create feature branch
git checkout -b feature/test-cicd
echo "Testing PR" >> TESTING.md
git add TESTING.md
git commit -m "test: PR workflow"
git push origin feature/test-cicd

# Create PR on GitHub
```

- [ ] Feature branch created and pushed
- [ ] Pull request created
- [ ] Code quality checks triggered
- [ ] All checks passed
- [ ] Status badges appear in PR

### 3. Deployment Test

```bash
# Merge to main
git checkout main
git merge develop
git push origin main
```

- [ ] Changes merged to main
- [ ] Deploy workflow triggered
- [ ] Deployment successful
- [ ] Health check passed
- [ ] Notification received (if configured)

### 4. Release Test

```bash
# Update changelog
# Edit CHANGELOG.md

# Commit and tag
git add CHANGELOG.md
git commit -m "docs: update changelog for v1.0.0"
git push origin main

# Create tag
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

- [ ] Changelog updated
- [ ] Tag created and pushed
- [ ] Release workflow triggered
- [ ] GitHub release created
- [ ] Production deployment successful

## Monitoring & Maintenance

### Daily Tasks
- [ ] Check Actions tab for failed workflows
- [ ] Review security audit results (automated)
- [ ] Monitor application logs

### Weekly Tasks
- [ ] Review dependency update issues (automated)
- [ ] Check test coverage trends
- [ ] Review deployment logs

### Monthly Tasks
- [ ] Rotate SSH keys
- [ ] Review and update secrets
- [ ] Check workflow performance
- [ ] Update documentation if needed

## Troubleshooting Checklist

### Workflow Fails
- [ ] Check workflow logs in Actions tab
- [ ] Verify all secrets are correctly set
- [ ] Test SSH connection manually
- [ ] Check server disk space
- [ ] Review application logs

### Deployment Fails
- [ ] Verify DEPLOY_PATH exists
- [ ] Check file permissions on server
- [ ] Test git pull manually on server
- [ ] Check composer/npm install logs
- [ ] Verify database connection

### Tests Fail
- [ ] Run tests locally
- [ ] Check .env.testing configuration
- [ ] Verify database credentials
- [ ] Check PHPStan configuration
- [ ] Review test logs

## Documentation Updated

- [ ] README.md badges added
- [ ] CICD_SETUP.md reviewed
- [ ] QUICK_START.md reviewed
- [ ] Team notified of new CI/CD process
- [ ] Deployment process documented

## Final Verification

- [ ] All workflows exist and are valid YAML
- [ ] All secrets configured correctly
- [ ] Server properly configured
- [ ] Branch protection enabled
- [ ] Environments configured
- [ ] Health check working
- [ ] Status badges displaying
- [ ] Notifications working
- [ ] Documentation complete
- [ ] Team trained on new process

## Sign-Off

**Implemented By:** _____________________
**Date:** _____________________
**Reviewed By:** _____________________
**Production Ready:** YES ☐ NO ☐

## Additional Notes

```
[Add any additional notes or special considerations here]






```

---

**Version:** 1.0.0
**Last Updated:** 2025-12-03
**Next Review:** [Date]
