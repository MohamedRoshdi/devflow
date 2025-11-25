# DevFlow Pro - Deployment Playbooks

## Table of Contents
1. [Standard Deployment Playbook](#standard-deployment-playbook)
2. [Emergency Rollback Playbook](#emergency-rollback-playbook)
3. [Multi-Tenant Deployment Playbook](#multi-tenant-deployment-playbook)
4. [Zero-Downtime Deployment Playbook](#zero-downtime-deployment-playbook)
5. [Database Migration Playbook](#database-migration-playbook)
6. [Disaster Recovery Playbook](#disaster-recovery-playbook)
7. [Performance Optimization Deployment](#performance-optimization-deployment)
8. [Security Patch Deployment](#security-patch-deployment)

---

## Standard Deployment Playbook

### Prerequisites Checklist
- [ ] Code reviewed and approved
- [ ] Tests passing in CI/CD
- [ ] Backup created
- [ ] Team notified of deployment window
- [ ] Monitoring dashboard open

### Step-by-Step Process

#### 1. Pre-Deployment Validation
```bash
# Check current system status
curl https://devflow.yourdomain.com/api/v1/projects/{project_id}/health

# Verify repository access
git ls-remote https://github.com/your-repo/project.git HEAD

# Check disk space
df -h /opt/devflow/projects

# Verify Docker status
docker ps
docker system df
```

#### 2. Create Backup
```bash
# Automated backup via DevFlow
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/backup \
  -H "Authorization: Bearer YOUR_TOKEN"

# Manual backup (if needed)
cd /opt/devflow/projects/your-project
tar -czf backup-$(date +%Y%m%d-%H%M%S).tar.gz .
mysqldump -u root -p database_name > db-backup-$(date +%Y%m%d-%H%M%S).sql
```

#### 3. Enable Maintenance Mode
```bash
# Via DevFlow API
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/maintenance/enable \
  -H "Authorization: Bearer YOUR_TOKEN"

# Manual (Laravel)
docker-compose exec app php artisan down --message="Scheduled maintenance" --retry=60
```

#### 4. Deploy Application
```bash
# Trigger deployment via DevFlow
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/deploy \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"branch": "main", "clear_cache": true}'
```

#### 5. Run Migrations
```bash
# Automated in deployment process, or manual:
docker-compose exec app php artisan migrate --force
```

#### 6. Clear Caches
```bash
# Via DevFlow
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/cache/clear \
  -H "Authorization: Bearer YOUR_TOKEN"

# Manual
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

#### 7. Health Checks
```bash
# Automated health check
curl https://devflow.yourdomain.com/api/v1/projects/{project_id}/health

# Manual checks
curl -I https://your-domain.com
curl https://your-domain.com/health
```

#### 8. Disable Maintenance Mode
```bash
# Via DevFlow
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/maintenance/disable \
  -H "Authorization: Bearer YOUR_TOKEN"

# Manual
docker-compose exec app php artisan up
```

### Post-Deployment Verification
- [ ] Application accessible
- [ ] All services running
- [ ] No error spikes in logs
- [ ] Performance metrics normal
- [ ] User reports checked

### Deployment Log Template
```markdown
## Deployment Log - [Date]

**Project:** [Project Name]
**Version:** [Commit Hash]
**Deployed By:** [Name]
**Start Time:** [Time]
**End Time:** [Time]

### Changes Deployed
- Feature: [Description]
- Bug Fix: [Description]

### Issues Encountered
- None / [Description of issues]

### Resolution
- N/A / [How issues were resolved]

### Verification
- [ ] Health check passed
- [ ] Smoke tests completed
- [ ] Performance normal

**Status:** SUCCESS / FAILED / ROLLED BACK
```

---

## Emergency Rollback Playbook

### When to Use
- Application errors after deployment
- Performance degradation
- Critical bug discovered
- Security vulnerability

### Immediate Actions (< 2 minutes)

#### 1. Alert Team
```bash
# Send emergency notification
echo "EMERGENCY: Rolling back deployment on [Project]" | \
  mail -s "Rollback Alert" team@company.com
```

#### 2. Enable Maintenance Mode
```bash
# Immediate maintenance mode
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/maintenance/enable \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"message": "Emergency maintenance"}'
```

#### 3. Initiate Rollback
```bash
# Quick rollback to last successful deployment
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/rollback \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"target_deployment_id": "last_successful"}'
```

### Rollback Process (2-10 minutes)

#### 1. Stop Current Services
```bash
cd /opt/devflow/projects/your-project
docker-compose down
```

#### 2. Restore Previous Version
```bash
# Git rollback
git fetch origin
git reset --hard [previous_commit_hash]

# Or restore from backup
tar -xzf backup-[timestamp].tar.gz
```

#### 3. Restore Database (if needed)
```bash
# Only if schema changes were made
mysql -u root -p database_name < db-backup-[timestamp].sql
```

#### 4. Rebuild and Start Services
```bash
docker-compose build --no-cache
docker-compose up -d
```

#### 5. Verify Rollback
```bash
# Check application version
docker-compose exec app php artisan about

# Health check
curl https://your-domain.com/health
```

#### 6. Disable Maintenance Mode
```bash
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/maintenance/disable \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Post-Rollback Actions
- [ ] Create incident report
- [ ] Analyze failure cause
- [ ] Update deployment procedures
- [ ] Schedule fix deployment
- [ ] Notify stakeholders

### Incident Report Template
```markdown
## Incident Report - [Date/Time]

### Summary
**Severity:** Critical/High/Medium/Low
**Duration:** [Minutes]
**Impact:** [Number] users affected

### Timeline
- [Time]: Deployment started
- [Time]: Issue detected
- [Time]: Rollback initiated
- [Time]: Service restored

### Root Cause
[Description of what went wrong]

### Resolution
[How the issue was resolved]

### Action Items
- [ ] Fix identified bug
- [ ] Add test coverage
- [ ] Update monitoring

### Lessons Learned
[What can be improved]
```

---

## Multi-Tenant Deployment Playbook

### Pre-Deployment Planning

#### 1. Identify Affected Tenants
```bash
# List all tenants
curl https://devflow.yourdomain.com/api/v1/projects/{project_id}/tenants \
  -H "Authorization: Bearer YOUR_TOKEN"

# Check tenant statuses
for tenant in tenant1 tenant2 tenant3; do
  echo "Checking $tenant..."
  curl https://$tenant.yourdomain.com/health
done
```

#### 2. Schedule Maintenance Windows
```yaml
Deployment Schedule:
  Batch 1 (Low Priority):
    - Tenants: trial accounts
    - Time: 02:00 - 03:00 UTC

  Batch 2 (Medium Priority):
    - Tenants: standard accounts
    - Time: 03:00 - 04:00 UTC

  Batch 3 (High Priority):
    - Tenants: premium accounts
    - Time: 04:00 - 05:00 UTC
```

### Deployment Process

#### 1. Deploy to Test Tenant
```bash
# Deploy to test tenant first
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/tenants/deploy \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"tenant_ids": ["test-tenant"], "deployment_type": "full"}'

# Verify test tenant
curl https://test-tenant.yourdomain.com/health
```

#### 2. Batch Deployment
```bash
# Deploy to batch of tenants
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/tenants/deploy \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "tenant_ids": ["tenant1", "tenant2", "tenant3"],
    "deployment_type": "code_and_migrations",
    "maintenance_mode": true,
    "parallel": false
  }'
```

#### 3. Monitor Progress
```bash
# Check deployment status
curl https://devflow.yourdomain.com/api/v1/deployments/{deployment_id}/status \
  -H "Authorization: Bearer YOUR_TOKEN"

# Monitor tenant health
while true; do
  for tenant in $(cat tenants.txt); do
    status=$(curl -s https://$tenant.yourdomain.com/health | jq -r '.status')
    echo "$tenant: $status"
  done
  sleep 30
done
```

### Tenant-Specific Operations

#### Database Migrations
```bash
# Run migrations per tenant
for tenant in $(cat tenants.txt); do
  echo "Migrating $tenant..."
  docker-compose exec app php artisan migrate \
    --force \
    --database=tenant_$tenant \
    --path=database/migrations/tenant
done
```

#### Cache Clearing
```bash
# Clear cache per tenant
for tenant in $(cat tenants.txt); do
  docker-compose exec app php artisan cache:clear --tenant=$tenant
  docker-compose exec app redis-cli -n $tenant_redis_db FLUSHDB
done
```

### Rollback Strategy
```bash
# Rollback specific tenants
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/tenants/rollback \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "tenant_ids": ["affected-tenant1", "affected-tenant2"],
    "target_version": "previous"
  }'
```

---

## Zero-Downtime Deployment Playbook

### Blue-Green Deployment Strategy

#### 1. Prepare Green Environment
```bash
# Clone current (blue) environment
cd /opt/devflow/projects
cp -r project-blue project-green

# Update green environment
cd project-green
git pull origin main
docker-compose -f docker-compose.green.yml build
```

#### 2. Start Green Environment
```bash
# Start on different ports
docker-compose -f docker-compose.green.yml up -d

# Verify green environment
curl http://localhost:8081/health  # Green port
```

#### 3. Run Migrations
```bash
# Run migrations on green database
docker-compose -f docker-compose.green.yml exec app \
  php artisan migrate --force
```

#### 4. Switch Traffic
```nginx
# Update nginx configuration
upstream app {
    # Old (comment out)
    # server blue-app:80;

    # New
    server green-app:80;
}

# Reload nginx
nginx -s reload
```

#### 5. Verify Switch
```bash
# Check active version
curl https://your-domain.com/version

# Monitor for errors
tail -f /var/log/nginx/error.log
```

#### 6. Cleanup Old Environment
```bash
# After verification period (e.g., 1 hour)
docker-compose -f docker-compose.blue.yml down
```

### Rolling Deployment Strategy

#### 1. Deploy to Subset
```bash
# Deploy to 25% of containers
docker-compose scale app=12  # Assuming 12 total

# Update first 3 containers
for i in 1 2 3; do
  docker-compose exec app_$i git pull
  docker-compose restart app_$i
  sleep 30  # Wait between restarts
done
```

#### 2. Monitor Health
```bash
# Check container health
for i in 1 2 3; do
  docker inspect app_$i --format='{{.State.Health.Status}}'
done
```

#### 3. Continue Rollout
```bash
# If healthy, continue to remaining containers
for i in 4 5 6 7 8 9 10 11 12; do
  docker-compose exec app_$i git pull
  docker-compose restart app_$i
  sleep 30
done
```

---

## Database Migration Playbook

### Safe Migration Strategy

#### 1. Pre-Migration Backup
```bash
# Full database backup
mysqldump -u root -p \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  database_name > pre-migration-backup.sql

# Verify backup
mysql -u root -p test_db < pre-migration-backup.sql
```

#### 2. Test Migration
```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE migration_test"

# Restore production data
mysql -u root -p migration_test < pre-migration-backup.sql

# Run migration on test
docker-compose exec app php artisan migrate \
  --database=migration_test \
  --pretend  # Dry run first
```

#### 3. Execute Migration
```bash
# Enable maintenance mode
php artisan down --message="Database upgrade in progress"

# Run migration
php artisan migrate --force --step  # One migration at a time

# Verify migration
php artisan migrate:status
```

#### 4. Rollback if Needed
```bash
# Rollback last batch
php artisan migrate:rollback --step=1

# Or restore from backup
mysql -u root -p database_name < pre-migration-backup.sql
```

### Large Table Migration

#### Strategy for Tables > 1GB
```sql
-- Create new table structure
CREATE TABLE users_new LIKE users;

-- Add new columns/indexes
ALTER TABLE users_new ADD COLUMN new_field VARCHAR(255);

-- Copy data in batches
INSERT INTO users_new SELECT *, NULL FROM users
  WHERE id BETWEEN 1 AND 100000;

-- Continue in batches...

-- Swap tables
RENAME TABLE users TO users_old, users_new TO users;

-- Drop old table after verification
DROP TABLE users_old;
```

---

## Disaster Recovery Playbook

### Scenario: Complete System Failure

#### 1. Immediate Response (0-5 minutes)
```bash
# Activate disaster recovery team
./notify-team.sh "DISASTER RECOVERY INITIATED"

# Switch to backup server
dns-update.sh your-domain.com BACKUP_SERVER_IP
```

#### 2. Assess Damage (5-15 minutes)
```bash
# Check server status
ping production-server.com
ssh root@production-server "systemctl status"

# Check backup availability
aws s3 ls s3://backup-bucket/latest/
```

#### 3. Restore Services (15-60 minutes)

##### Option A: Restore on New Server
```bash
# Provision new server
terraform apply -var="instance_type=t3.large"

# Install DevFlow Pro
curl -sSL https://devflow.pro/install.sh | bash

# Restore from backup
aws s3 sync s3://backup-bucket/latest/ /opt/devflow/
mysql -u root -p < database-backup.sql
```

##### Option B: Failover to Standby
```bash
# Activate standby server
ssh standby-server "systemctl start devflow"

# Sync latest data
rsync -avz /opt/devflow/ standby:/opt/devflow/

# Update DNS
cloudflare-api update-dns your-domain.com STANDBY_IP
```

#### 4. Verify Recovery
```bash
# System checks
curl https://your-domain.com/health
mysql -u root -p -e "SELECT COUNT(*) FROM projects"
docker ps

# User verification
echo "Please verify: https://your-domain.com" | mail -s "DR Complete" team@company.com
```

### Scenario: Data Corruption

#### 1. Identify Corruption
```sql
-- Check table integrity
CHECK TABLE users, projects, deployments;

-- Find corrupt records
SELECT * FROM deployments WHERE
  created_at > updated_at OR
  status NOT IN ('pending','running','success','failed');
```

#### 2. Restore Clean Data
```bash
# Find last known good backup
for backup in /backups/*.sql; do
  echo "Checking $backup..."
  mysql test_db < $backup
  if mysql test_db -e "CHECK TABLE users"; then
    echo "Good backup: $backup"
    break
  fi
done

# Restore from clean backup
mysql -u root -p production_db < good-backup.sql
```

---

## Performance Optimization Deployment

### Pre-Deployment Performance Baseline

#### 1. Capture Current Metrics
```bash
# Response times
ab -n 1000 -c 10 https://your-domain.com/ > baseline-performance.txt

# Database performance
mysql -u root -p -e "SHOW STATUS" > baseline-db.txt

# Resource usage
top -b -n 1 > baseline-resources.txt
```

#### 2. Deploy Optimizations
```bash
# Deploy with performance flags
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/deploy \
  -d '{
    "optimization_level": "aggressive",
    "opcache_reset": true,
    "compile_assets": true
  }'
```

#### 3. Post-Deployment Verification
```bash
# Compare performance
ab -n 1000 -c 10 https://your-domain.com/ > new-performance.txt
diff baseline-performance.txt new-performance.txt

# Check optimization status
docker-compose exec app php -i | grep opcache
docker-compose exec app php artisan optimize:status
```

### Cache Warming Strategy
```bash
# Pre-warm application cache
curl https://your-domain.com/sitemap.xml | \
  grep -oP '(?<=<loc>)[^<]+' | \
  xargs -n1 -P10 curl -s -o /dev/null -w "%{url_effective}: %{time_total}s\n"

# Pre-warm database cache
mysql -u root -p -e "SELECT SQL_CACHE * FROM frequently_used_table"
```

---

## Security Patch Deployment

### Critical Security Update Process

#### 1. Assess Vulnerability
```bash
# Check affected components
composer audit
npm audit

# Scan for vulnerabilities
docker scan your-image:latest
```

#### 2. Prepare Patch
```bash
# Create security branch
git checkout -b security-patch-CVE-2024-XXXX

# Apply patches
composer update vulnerable-package
npm update vulnerable-package

# Test patches
phpunit --testsuite=security
```

#### 3. Emergency Deployment
```bash
# Fast-track deployment
curl -X POST https://devflow.yourdomain.com/api/v1/projects/{project_id}/deploy \
  -d '{
    "branch": "security-patch-CVE-2024-XXXX",
    "priority": "critical",
    "skip_tests": false,
    "notify_users": false
  }'
```

#### 4. Verify Patch
```bash
# Confirm vulnerability fixed
composer show vulnerable-package | grep version
docker scan your-image:patched

# Security scan
nikto -h https://your-domain.com
nmap -sV your-domain.com
```

### Post-Patch Actions
- [ ] Update security documentation
- [ ] Notify stakeholders
- [ ] Schedule full security audit
- [ ] Update WAF rules
- [ ] Review similar vulnerabilities

---

## Deployment Automation Scripts

### Auto-Deployment Script
```bash
#!/bin/bash
# auto-deploy.sh

set -e

PROJECT_ID=$1
BRANCH=${2:-main}
API_TOKEN="YOUR_TOKEN"
API_URL="https://devflow.yourdomain.com/api/v1"

# Function to check deployment status
check_status() {
    local deployment_id=$1
    local status=$(curl -s -H "Authorization: Bearer $API_TOKEN" \
        "$API_URL/deployments/$deployment_id" | jq -r '.data.status')
    echo $status
}

# Create backup
echo "Creating backup..."
curl -X POST -H "Authorization: Bearer $API_TOKEN" \
    "$API_URL/projects/$PROJECT_ID/backup"

# Start deployment
echo "Starting deployment..."
response=$(curl -s -X POST -H "Authorization: Bearer $API_TOKEN" \
    -H "Content-Type: application/json" \
    -d "{\"branch\": \"$BRANCH\"}" \
    "$API_URL/projects/$PROJECT_ID/deploy")

deployment_id=$(echo $response | jq -r '.data.deployment_id')
echo "Deployment ID: $deployment_id"

# Monitor deployment
while true; do
    status=$(check_status $deployment_id)
    echo "Status: $status"

    if [[ $status == "success" ]]; then
        echo "Deployment completed successfully!"
        exit 0
    elif [[ $status == "failed" ]]; then
        echo "Deployment failed!"
        exit 1
    fi

    sleep 10
done
```

### Health Check Script
```bash
#!/bin/bash
# health-check.sh

ENDPOINTS=(
    "https://app1.domain.com/health"
    "https://app2.domain.com/health"
    "https://app3.domain.com/health"
)

for endpoint in "${ENDPOINTS[@]}"; do
    response=$(curl -s -o /dev/null -w "%{http_code}" $endpoint)
    if [[ $response -eq 200 ]]; then
        echo "✓ $endpoint is healthy"
    else
        echo "✗ $endpoint is unhealthy (HTTP $response)"
        # Send alert
        mail -s "Health check failed: $endpoint" ops@company.com
    fi
done
```

---

*Last Updated: November 2024*
*Version: 1.0.0*