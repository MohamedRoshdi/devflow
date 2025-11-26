# DevFlow Pro - Advanced Features Guide v2.5

<div align="center">
  <h2>🚀 Enterprise-Grade DevOps Features</h2>
  <p>Kubernetes • CI/CD • Scripts • Notifications • Multi-Tenant</p>
</div>

---

## 📚 Table of Contents

1. [Overview](#overview)
2. [Accessing Advanced Features](#accessing-advanced-features)
3. [Kubernetes Integration](#kubernetes-integration)
4. [CI/CD Pipeline Automation](#cicd-pipeline-automation)
5. [Custom Deployment Scripts](#custom-deployment-scripts)
6. [Smart Notification System](#smart-notification-system)
7. [Multi-Tenant Management](#multi-tenant-management)
8. [API Reference](#api-reference)
9. [Best Practices](#best-practices)
10. [Troubleshooting](#troubleshooting)

---

## Overview

DevFlow Pro v2.5 introduces powerful enterprise features designed for modern DevOps workflows, enabling teams to manage complex deployments at scale.

### Key Benefits
- **🎯 Unified Control** - Manage K8s, CI/CD, and deployments from one platform
- **⚡ Automation** - Reduce manual tasks with intelligent automation
- **🔒 Security** - Enterprise-grade security with encrypted secrets
- **📊 Visibility** - Real-time monitoring and notifications
- **🏢 Scale** - Support for multi-tenant architectures

---

## Accessing Advanced Features

All advanced features are accessible through the **Advanced** dropdown menu in the DevFlow Pro admin panel.

**Access URL:** http://admin.nilestack.duckdns.org

### Navigation Structure
```
Advanced ▼
├── Kubernetes        - Container orchestration
├── CI/CD Pipelines   - Automated build & deploy
├── Deployment Scripts - Custom automation
├── Notifications     - Alert channels
├── Multi-Tenant      - Tenant management
└── System Admin      - System configuration
```

### Required Permissions
- **Admin**: Full access to all features
- **Manager**: Can view and execute, cannot delete
- **User**: Read-only access

---

## Kubernetes Integration

### 🎯 Overview
Deploy and manage containerized applications on Kubernetes clusters with enterprise features.

### 🚀 Quick Start

#### Step 1: Add a Kubernetes Cluster
```yaml
Name: production-k8s
API Server: https://k8s-api.example.com:6443
Namespace: default
Context: production-context
```

#### Step 2: Configure Kubeconfig
```bash
# Get your kubeconfig
kubectl config view --minify --flatten

# Paste in DevFlow Pro
# The content is encrypted before storage
```

#### Step 3: Deploy a Project
1. Select your project
2. Choose target cluster
3. Configure resources:
   ```yaml
   Replicas: 3
   CPU Request: 100m
   Memory Request: 128Mi
   CPU Limit: 500m
   Memory Limit: 512Mi
   ```
4. Click "Deploy to Kubernetes"

### 📦 Features

#### Multi-Cluster Management
- Development, staging, and production clusters
- Cross-cluster deployments
- Cluster health monitoring

#### Deployment Strategies
- **Rolling Update** - Zero-downtime deployments
- **Blue-Green** - Instant rollback capability
- **Canary** - Gradual rollout to subset

#### Pod Management
```bash
# Real-time pod status
kubectl get pods -n app-namespace

# View logs
kubectl logs pod-name -n app-namespace

# Execute commands
kubectl exec -it pod-name -- /bin/bash
```

#### Auto-Scaling
```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: app-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: app
  minReplicas: 2
  maxReplicas: 10
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
```

#### Helm Integration
```bash
# Deploy with Helm
helm install app ./chart --namespace production

# Upgrade deployment
helm upgrade app ./chart --reuse-values

# Rollback if needed
helm rollback app 1
```

### 🔐 Security
- **Encrypted Storage** - Kubeconfig encrypted with AES-256
- **RBAC Support** - Role-based access control
- **Secret Management** - K8s secrets integration
- **Network Policies** - Micro-segmentation support

---

## CI/CD Pipeline Automation

### 🎯 Overview
Visual pipeline builder with support for multiple CI/CD platforms.

### 🚀 Quick Start

#### Create Your First Pipeline
1. Navigate to **Advanced → CI/CD Pipelines**
2. Click "Create Pipeline"
3. Choose your provider:
   - GitHub Actions
   - GitLab CI/CD
   - Bitbucket Pipelines
   - Jenkins

### 📦 Pipeline Configuration

#### GitHub Actions Example
```yaml
name: Deploy to Production

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test

  build:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Build Docker Image
        run: |
          docker build -t app:${{ github.sha }} .
          docker push registry/app:${{ github.sha }}

  deploy:
    needs: build
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
      - name: Deploy to Production
        run: |
          curl -X POST https://devflow.pro/api/deploy \
            -H "Authorization: Bearer ${{ secrets.DEVFLOW_TOKEN }}" \
            -d '{"project_id": 1, "version": "${{ github.sha }}"}'
```

#### GitLab CI Example
```yaml
stages:
  - test
  - build
  - deploy

variables:
  DOCKER_DRIVER: overlay2
  DOCKER_TLS_CERTDIR: "/certs"

test:
  stage: test
  image: php:8.4
  script:
    - composer install
    - vendor/bin/phpunit
  only:
    - merge_requests
    - main

build:
  stage: build
  image: docker:latest
  services:
    - docker:dind
  script:
    - docker build -t $CI_REGISTRY_IMAGE:$CI_COMMIT_SHA .
    - docker push $CI_REGISTRY_IMAGE:$CI_COMMIT_SHA
  only:
    - main

deploy:
  stage: deploy
  script:
    - curl -X POST "${DEVFLOW_URL}/api/deploy"
      -H "Authorization: Bearer ${DEVFLOW_TOKEN}"
      -d "project_id=1&version=${CI_COMMIT_SHA}"
  only:
    - main
  environment:
    name: production
    url: https://app.example.com
```

### 🔄 Pipeline Features

#### Parallel Execution
```yaml
test:
  parallel:
    matrix:
      - PHP_VERSION: ['8.2', '8.3', '8.4']
        DB: ['mysql', 'postgres']
```

#### Artifacts
```yaml
build:
  artifacts:
    paths:
      - dist/
      - build/
    expire_in: 1 week
```

#### Environment Variables
```yaml
variables:
  APP_ENV: production
  APP_DEBUG: false
  DEPLOY_KEY: ${{ secrets.DEPLOY_KEY }}
```

#### Conditional Execution
```yaml
deploy:
  rules:
    - if: '$CI_COMMIT_BRANCH == "main"'
      when: manual
    - if: '$CI_COMMIT_TAG'
      when: always
```

---

## Custom Deployment Scripts

### 🎯 Overview
Execute custom scripts in multiple languages with template variables.

### 🚀 Quick Start

#### Create a Deployment Script
1. Go to **Advanced → Deployment Scripts**
2. Click "Create Script"
3. Configure:
   ```yaml
   Name: Laravel Production Deploy
   Language: Bash
   Timeout: 600 seconds
   Run As: www-data
   ```

### 📦 Script Templates

#### Laravel Deployment
```bash
#!/bin/bash
set -e

echo "🚀 Deploying @{{PROJECT_NAME}} to production"

# Variables
PROJECT_PATH="@{{PROJECT_PATH}}"
BRANCH="@{{BRANCH}}"
TIMESTAMP="@{{TIMESTAMP}}"

# Navigate to project
cd $PROJECT_PATH

# Enable maintenance mode
php artisan down --message="Upgrading application" --retry=60

# Pull latest changes
git fetch origin $BRANCH
git reset --hard origin/$BRANCH

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

# Disable maintenance mode
php artisan up

echo "✅ Deployment complete at $TIMESTAMP"
```

#### Node.js Deployment
```javascript
#!/usr/bin/env node

const { exec } = require('child_process');
const path = require('path');

const projectPath = '@{{PROJECT_PATH}}';
const branch = '@{{BRANCH}}';

async function deploy() {
    console.log(`🚀 Deploying ${projectPath}`);

    // Change to project directory
    process.chdir(projectPath);

    // Pull latest changes
    await execute(`git pull origin ${branch}`);

    // Install dependencies
    await execute('npm ci --production');

    // Build application
    await execute('npm run build');

    // Restart PM2 process
    await execute('pm2 restart app');

    console.log('✅ Deployment complete');
}

function execute(command) {
    return new Promise((resolve, reject) => {
        exec(command, (error, stdout, stderr) => {
            if (error) {
                console.error(`Error: ${stderr}`);
                reject(error);
            } else {
                console.log(stdout);
                resolve(stdout);
            }
        });
    });
}

deploy().catch(console.error);
```

#### Python Deployment
```python
#!/usr/bin/env python3

import os
import subprocess
import sys
from datetime import datetime

PROJECT_PATH = '@{{PROJECT_PATH}}'
BRANCH = '@{{BRANCH}}'
VENV_PATH = os.path.join(PROJECT_PATH, 'venv')

def run_command(command):
    """Execute shell command"""
    result = subprocess.run(command, shell=True, capture_output=True, text=True)
    if result.returncode != 0:
        print(f"Error: {result.stderr}")
        sys.exit(1)
    return result.stdout

def deploy():
    """Main deployment function"""
    print(f"🚀 Starting deployment at {datetime.now()}")

    # Change to project directory
    os.chdir(PROJECT_PATH)

    # Pull latest changes
    print("Pulling latest changes...")
    run_command(f"git pull origin {BRANCH}")

    # Activate virtual environment
    activate_script = os.path.join(VENV_PATH, 'bin', 'activate')

    # Install dependencies
    print("Installing dependencies...")
    run_command(f"source {activate_script} && pip install -r requirements.txt")

    # Run migrations
    print("Running migrations...")
    run_command(f"source {activate_script} && python manage.py migrate")

    # Collect static files
    print("Collecting static files...")
    run_command(f"source {activate_script} && python manage.py collectstatic --noinput")

    # Restart application
    print("Restarting application...")
    run_command("sudo systemctl restart gunicorn")

    print("✅ Deployment complete")

if __name__ == "__main__":
    deploy()
```

### 🔧 Available Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `@{{PROJECT_NAME}}` | Project name | My App |
| `@{{PROJECT_SLUG}}` | URL-safe name | my-app |
| `@{{PROJECT_PATH}}` | Directory path | /var/www/my-app |
| `@{{BRANCH}}` | Git branch | main |
| `@{{COMMIT_HASH}}` | Latest commit | a1b2c3d4 |
| `@{{TIMESTAMP}}` | Current time | 2025-11-25 10:30:00 |
| `@{{DOMAIN}}` | Primary domain | app.example.com |
| `@{{ENVIRONMENT}}` | Environment | production |
| `@{{SERVER_IP}}` | Server IP | 192.168.1.100 |
| `@{{USER}}` | Executing user | www-data |

---

## Smart Notification System

### 🎯 Overview
Real-time notifications for critical events across multiple channels.

### 🚀 Quick Start

#### Configure Slack Notifications
1. Navigate to **Advanced → Notifications**
2. Click "Add Channel"
3. Configure:
   ```yaml
   Name: Team Alerts
   Provider: Slack
   Webhook URL: https://hooks.slack.com/services/T00/B00/XXX
   ```
4. Select events:
   - ✅ Deployment Started
   - ✅ Deployment Completed
   - ✅ Deployment Failed
   - ✅ Health Check Failed
5. Test notification
6. Enable channel

### 📦 Channel Configurations

#### Slack
```json
{
  "webhook_url": "https://hooks.slack.com/services/XXX",
  "channel": "#deployments",
  "username": "DevFlow Bot",
  "icon_emoji": ":rocket:",
  "mention_users": ["@john", "@sarah"],
  "mention_on_failure": true
}
```

#### Discord
```json
{
  "webhook_url": "https://discord.com/api/webhooks/XXX",
  "username": "DevFlow",
  "avatar_url": "https://devflow.pro/logo.png",
  "role_mentions": ["@Developers"],
  "embed_color": 3447003
}
```

#### Microsoft Teams
```json
{
  "webhook_url": "https://outlook.office.com/webhook/XXX",
  "theme_color": "0076D7",
  "activity_title": "Deployment Status",
  "activity_subtitle": "Production Environment"
}
```

### 🔔 Event Types

| Event | Description | Severity |
|-------|-------------|----------|
| `deployment_started` | Deployment initiated | Info |
| `deployment_completed` | Successful deployment | Success |
| `deployment_failed` | Deployment error | Critical |
| `rollback_completed` | Rollback finished | Warning |
| `health_check_failed` | Service down | Critical |
| `ssl_expiring` | Certificate expiry warning | Warning |
| `storage_warning` | Low disk space | Warning |
| `security_alert` | Security issue detected | Critical |
| `backup_completed` | Backup successful | Info |
| `system_error` | System-level error | Critical |

### 📨 Message Templates

#### Success Template
```json
{
  "color": "good",
  "title": "✅ Deployment Successful",
  "text": "Project *{{project_name}}* deployed to *{{environment}}*",
  "fields": [
    {
      "title": "Version",
      "value": "{{version}}",
      "short": true
    },
    {
      "title": "Duration",
      "value": "{{duration}}",
      "short": true
    },
    {
      "title": "Deployed By",
      "value": "{{user}}",
      "short": true
    },
    {
      "title": "Commit",
      "value": "{{commit_message}}",
      "short": false
    }
  ],
  "footer": "DevFlow Pro",
  "ts": "{{timestamp}}"
}
```

#### Failure Template
```json
{
  "color": "danger",
  "title": "❌ Deployment Failed",
  "text": "@channel Deployment failed for *{{project_name}}*",
  "fields": [
    {
      "title": "Error",
      "value": "```{{error_message}}```",
      "short": false
    },
    {
      "title": "Stage",
      "value": "{{failed_stage}}",
      "short": true
    },
    {
      "title": "Action Required",
      "value": "Check logs and retry",
      "short": false
    }
  ],
  "actions": [
    {
      "type": "button",
      "text": "View Logs",
      "url": "{{log_url}}"
    },
    {
      "type": "button",
      "text": "Rollback",
      "url": "{{rollback_url}}",
      "style": "danger"
    }
  ]
}
```

---

## Multi-Tenant Management

### 🎯 Overview
Manage SaaS applications with isolated tenant deployments.

### 🚀 Quick Start

#### Setup Multi-Tenant Project
1. Create or edit project
2. Set Type: `Multi-Tenant`
3. Navigate to **Advanced → Multi-Tenant**
4. Select your project

#### Create Tenants
```yaml
Name: Acme Corporation
Subdomain: acme
Database: tenant_acme
Plan: Enterprise
Admin Email: admin@acme.com
Custom Config:
  storage_limit: 100GB
  user_limit: 500
  features:
    - api_access
    - custom_domain
    - white_label
```

### 📦 Tenant Operations

#### Bulk Deployment
```php
// Deploy to all tenants
$tenants = Tenant::where('status', 'active')->get();
foreach ($tenants as $tenant) {
    dispatch(new DeployToTenant($tenant, $deployment));
}

// Deploy to specific tenants
$selectedTenants = [1, 2, 3]; // Tenant IDs
$this->deployToTenants($selectedTenants, [
    'deployment_type' => 'code_and_migrations',
    'clear_cache' => true,
    'maintenance_mode' => true
]);
```

#### Tenant Isolation
```php
// Database isolation
'connections' => [
    'tenant' => [
        'driver' => 'mysql',
        'database' => 'tenant_' . $tenantId,
        'username' => 'tenant_' . $tenantId,
        'password' => encrypt($tenantPassword),
    ]
]

// Storage isolation
'disks' => [
    'tenant' => [
        'driver' => 'local',
        'root' => storage_path('app/tenants/' . $tenantId),
    ]
]
```

#### Migration Strategies
```bash
# Sequential migration (safe)
for tenant in tenants; do
  php artisan migrate --tenant=$tenant
done

# Parallel migration (fast)
parallel -j 10 php artisan migrate --tenant={} ::: "${tenants[@]}"

# Canary migration (test first)
php artisan migrate --tenant=canary-tenant
# If successful, migrate others
php artisan migrate --tenant=all --exclude=canary-tenant
```

### 🔧 Tenant Features

#### Resource Quotas
```yaml
quotas:
  cpu_limit: 2 cores
  memory_limit: 4GB
  storage_limit: 100GB
  bandwidth_limit: 1TB/month
  api_rate_limit: 10000/hour
```

#### Custom Domains
```nginx
server {
    server_name ~^(?<subdomain>.+)\.app\.com$;

    set $tenant_db "tenant_${subdomain}";

---

## 📍 Production Access URLs

### Live System URLs
- **Portfolio (Main):** http://nilestack.duckdns.org
- **DevFlow Admin:** http://admin.nilestack.duckdns.org
- **ATS Pro:** http://ats.nilestack.duckdns.org
- **Portainer:** https://nilestack.duckdns.org:9443

### Feature Access
All advanced features are available at: **http://admin.nilestack.duckdns.org**

Navigate to the **Advanced** dropdown menu to access:
- Kubernetes Management
- CI/CD Pipelines
- Deployment Scripts
- Notification System
- Multi-Tenant Management

---

## 📊 System Status (November 25, 2025)

### Infrastructure
- **Server:** 31.220.90.121 (Ubuntu 24.04.3)
- **Version:** DevFlow Pro v2.5.0
- **Status:** ✅ All Features Operational

### Active Features
- ✅ Kubernetes Integration
- ✅ CI/CD Automation
- ✅ Custom Scripts
- ✅ Notifications
- ✅ Multi-Tenant Support
- ✅ Docker Management
- ✅ Real-time Monitoring

---

**Last Updated:** November 25, 2025
**Version:** 2.5.0
**Documentation:** Complete
    set $tenant_path "/var/www/tenants/${subdomain}";

    root $tenant_path/public;

    location ~ \.php$ {
        fastcgi_param TENANT_ID $subdomain;
        fastcgi_param DB_DATABASE $tenant_db;
    }
}
```

#### Backup & Restore
```bash
# Backup tenant
mysqldump tenant_acme > backup_acme_$(date +%Y%m%d).sql
tar -czf tenant_acme_files.tar.gz /var/www/tenants/acme

# Restore tenant
mysql tenant_acme < backup_acme_20251125.sql
tar -xzf tenant_acme_files.tar.gz -C /
```

---

## API Reference

### Authentication
All API endpoints require Bearer token authentication:
```bash
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
     https://devflow.pro/api/endpoint
```

### Kubernetes Endpoints
```bash
# List clusters
GET /api/kubernetes/clusters

# Add cluster
POST /api/kubernetes/clusters
{
  "name": "production",
  "api_server_url": "https://k8s.example.com",
  "kubeconfig": "base64_encoded_content"
}

# Deploy to Kubernetes
POST /api/kubernetes/deploy
{
  "project_id": 1,
  "cluster_id": 1,
  "replicas": 3,
  "resources": {
    "cpu_request": "100m",
    "memory_request": "128Mi"
  }
}

# Get pod status
GET /api/kubernetes/clusters/{id}/pods
```

### Pipeline Endpoints
```bash
# List pipelines
GET /api/pipelines

# Create pipeline
POST /api/pipelines
{
  "name": "Deploy Pipeline",
  "provider": "github_actions",
  "configuration": {...},
  "triggers": ["push", "pr"]
}

# Execute pipeline
POST /api/pipelines/{id}/execute
{
  "branch": "main",
  "variables": {
    "ENVIRONMENT": "production"
  }
}

# Get pipeline status
GET /api/pipelines/{id}/runs/{runId}
```

### Script Endpoints
```bash
# List scripts
GET /api/scripts

# Create script
POST /api/scripts
{
  "name": "Deploy Script",
  "language": "bash",
  "content": "#!/bin/bash\n...",
  "timeout": 300
}

# Execute script
POST /api/scripts/{id}/execute
{
  "project_id": 1,
  "variables": {
    "BRANCH": "main"
  }
}
```

### Notification Endpoints
```bash
# List channels
GET /api/notifications/channels

# Add channel
POST /api/notifications/channels
{
  "name": "Slack",
  "provider": "slack",
  "webhook_url": "https://hooks.slack.com/...",
  "events": ["deployment_completed", "deployment_failed"]
}

# Test notification
POST /api/notifications/test
{
  "channel_id": 1,
  "message": "Test notification from DevFlow Pro"
}
```

### Tenant Endpoints
```bash
# List tenants
GET /api/tenants

# Create tenant
POST /api/tenants
{
  "project_id": 1,
  "name": "Customer A",
  "subdomain": "customer-a",
  "plan": "enterprise"
}

# Deploy to tenants
POST /api/tenants/deploy
{
  "tenant_ids": [1, 2, 3],
  "deployment_type": "code_and_migrations",
  "options": {
    "clear_cache": true,
    "maintenance_mode": true
  }
}
```

---

## Best Practices

### Security
- ✅ Always use encrypted connections (HTTPS/TLS)
- ✅ Rotate API tokens regularly
- ✅ Use least privilege principle for permissions
- ✅ Enable 2FA for admin accounts
- ✅ Audit log all critical operations

### Performance
- ✅ Use parallel deployments for multi-tenant
- ✅ Implement caching strategies
- ✅ Optimize container images
- ✅ Use CDN for static assets
- ✅ Monitor resource usage

### Reliability
- ✅ Implement health checks
- ✅ Set up automated backups
- ✅ Use blue-green deployments
- ✅ Configure monitoring alerts
- ✅ Test disaster recovery

### Scalability
- ✅ Use horizontal pod autoscaling
- ✅ Implement queue workers
- ✅ Database read replicas
- ✅ Load balancing
- ✅ Microservices architecture

---

## Troubleshooting

### Common Issues

#### Kubernetes Connection Failed
```bash
# Check cluster connectivity
kubectl cluster-info --kubeconfig=/path/to/kubeconfig

# Verify credentials
kubectl auth can-i create deployments

# Check network
telnet k8s-api.example.com 6443
```

#### Pipeline Execution Timeout
```yaml
# Increase timeout in pipeline config
timeout: 3600  # 1 hour

# Or split into smaller jobs
jobs:
  - build: { timeout: 600 }
  - test: { timeout: 300 }
  - deploy: { timeout: 900 }
```

#### Script Permission Denied
```bash
# Check file permissions
ls -la /path/to/script

# Make executable
chmod +x /path/to/script

# Check user permissions
sudo -u www-data /path/to/script
```

#### Notification Not Received
```bash
# Test webhook manually
curl -X POST https://hooks.slack.com/services/XXX \
  -H "Content-Type: application/json" \
  -d '{"text": "Test message"}'

# Check logs
tail -f storage/logs/notifications.log

# Verify events are enabled
SELECT * FROM notification_channels WHERE enabled = 1;
```

#### Tenant Migration Failed
```sql
-- Check tenant database
SHOW DATABASES LIKE 'tenant_%';

-- Verify permissions
SHOW GRANTS FOR 'tenant_user'@'localhost';

-- Check migration status
SELECT * FROM migrations WHERE batch = (SELECT MAX(batch) FROM migrations);
```

---

## 📞 Support

### Documentation
- [Main Documentation](DOCUMENTATION.md)
- [User Guide](docs/USER_GUIDE.md)
- [API Reference](docs/API_DOCUMENTATION.md)
- [Deployment Guide](docs/DEPLOYMENT_PLAYBOOKS.md)

### Community
- GitHub Issues: [Report bugs and request features](https://github.com/devflow/issues)
- Discord: [Join our community](https://discord.gg/devflow)
- Email: support@devflow.pro

### Professional Support
- Enterprise Support Plans
- Custom Development
- Training & Consultation
- 24/7 SLA Options

---

<div align="center">
  <strong>DevFlow Pro Advanced Features</strong><br>
  Version 2.5.0 | November 2025<br>
  <em>Enterprise-Grade DevOps Automation</em>
</div>