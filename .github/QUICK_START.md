# CI/CD Quick Start Guide

## Getting Started in 5 Minutes

### Step 1: Configure GitHub Secrets

Go to your repository: **Settings → Secrets and variables → Actions → New repository secret**

Add these **required** secrets:

```
SSH_PRIVATE_KEY     = [Your SSH private key]
SSH_HOST            = [Your server IP or hostname]
SSH_USER            = [Your deployment user, e.g., deployer]
DEPLOY_PATH         = [Full path, e.g., /var/www/devflow-pro]
APP_URL             = [Your app URL, e.g., https://devflow.example.com]
```

**Optional but recommended:**

```
DISCORD_WEBHOOK     = [Discord webhook for notifications]
CODECOV_TOKEN       = [Codecov token for coverage reports]
```

### Step 2: Generate SSH Key

```bash
# On your local machine
ssh-keygen -t ed25519 -C "github-actions@devflow" -f ~/.ssh/github_actions_key

# Copy the private key
cat ~/.ssh/github_actions_key
# Add this to GitHub secret: SSH_PRIVATE_KEY

# Copy the public key to your server
ssh-copy-id -i ~/.ssh/github_actions_key.pub deployer@your-server.com
```

### Step 3: Prepare Your Server

```bash
# SSH into your server
ssh deployer@your-server.com

# Create deployment directory
sudo mkdir -p /var/www/devflow-pro
sudo chown deployer:www-data /var/www/devflow-pro

# Clone your repository (first time only)
cd /var/www/devflow-pro
git clone https://github.com/your-username/devflow-pro.git .

# Install dependencies
composer install --optimize-autoloader --no-dev
npm ci && npm run build

# Set permissions
sudo chown -R deployer:www-data /var/www/devflow-pro
sudo chmod -R 775 storage bootstrap/cache
```

### Step 4: Test CI/CD Workflows

1. **Test CI workflow:**
   ```bash
   # Make a small change and push
   git add .
   git commit -m "test: CI workflow"
   git push origin develop
   ```

2. **Monitor workflow:**
   - Go to GitHub **Actions** tab
   - Watch the CI workflow run
   - Ensure all tests pass

3. **Test deployment workflow (manual):**
   - Go to **Actions** → **Deploy** workflow
   - Click **Run workflow**
   - Select environment: **production**
   - Click **Run workflow**
   - Monitor the deployment

### Step 5: Create Your First Release

```bash
# Update CHANGELOG.md with release notes
# Commit and push
git add CHANGELOG.md
git commit -m "docs: update changelog for v1.0.0"
git push origin main

# Create and push tag
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0

# Watch the release workflow in Actions tab
```

## Available Workflows

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| **CI** | Push/PR to main/develop | Run tests, PHPStan, security checks |
| **Deploy** | Push to main/manual | Deploy to production/staging |
| **Code Quality** | Pull requests | Check code style, coverage, complexity |
| **Scheduled** | Daily/weekly | Security audits, dependency checks |
| **Release** | Version tags (v*.*.*) | Create releases, deploy stable versions |

## Composer Commands

```bash
# Run tests
composer test

# Run tests with coverage
composer test:coverage

# Run PHPStan analysis
composer analyse

# Check code style
composer lint

# Fix code style
composer lint:fix

# Run all CI checks
composer ci
```

## npm Commands

```bash
# Development server
npm run dev

# Build for production
npm run build

# Format code
npm run format

# Check formatting
npm run format:check
```

## Common Tasks

### Running Tests Locally

```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE devflow_test;"

# Run all tests
php artisan test

# Run specific test
php artisan test --filter=DeploymentTest

# With coverage
php artisan test --coverage
```

### Manual Deployment

```bash
# From Actions tab
1. Click "Actions"
2. Select "Deploy" workflow
3. Click "Run workflow"
4. Choose environment
5. Click "Run workflow" button
```

### Rolling Back a Deployment

```bash
# SSH to server
ssh deployer@your-server.com
cd /var/www/devflow-pro

# Rollback to previous commit
git log --oneline  # Find commit hash
git reset --hard <previous-commit-hash>

# Rebuild
composer install --optimize-autoloader --no-dev
npm ci && npm run build
php artisan migrate:rollback
php artisan cache:clear
php artisan queue:restart
```

### Checking Workflow Logs

1. Go to **Actions** tab
2. Click on the workflow run
3. Click on the job (e.g., "tests")
4. Click on step to see detailed logs

### Troubleshooting Failed Workflows

1. **Check error in logs** - Click failed job → View error
2. **Enable debug logging** - Add secret `ACTIONS_STEP_DEBUG = true`
3. **Re-run with debug** - Click "Re-run jobs" → "Re-run with debug"

## Status Badges

Add these to your README.md (replace `yourusername` with your GitHub username):

```markdown
![CI](https://github.com/yourusername/devflow-pro/actions/workflows/ci.yml/badge.svg)
![Deploy](https://github.com/yourusername/devflow-pro/actions/workflows/deploy.yml/badge.svg)
![Code Quality](https://github.com/yourusername/devflow-pro/actions/workflows/code-quality.yml/badge.svg)
```

## Need Help?

- **Full Documentation:** [CICD_SETUP.md](CICD_SETUP.md)
- **GitHub Actions Docs:** https://docs.github.com/en/actions
- **Laravel Docs:** https://laravel.com/docs
- **PHPStan Docs:** https://phpstan.org/

## Security Checklist

- [ ] Never commit secrets to repository
- [ ] Rotate SSH keys periodically
- [ ] Enable 2FA on GitHub account
- [ ] Use environment-specific secrets
- [ ] Review security audit results weekly
- [ ] Limit deployment user permissions
- [ ] Monitor deployment logs

## Next Steps

1. Set up branch protection rules
2. Configure Codecov for coverage visualization
3. Set up Discord/Slack notifications
4. Create staging environment
5. Configure automated backups
6. Set up monitoring (Sentry, New Relic)

---

**Ready to go!** Push your code and watch CI/CD magic happen. 🚀
