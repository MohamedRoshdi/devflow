# DevFlow Pro - Roadmap & Task Planning

> **Version:** 2.6.3 | **Last Updated:** 2025-11-28

---

## 📊 Project Status Overview

| Metric | Value |
|--------|-------|
| Current Version | v2.6.3 |
| Core Features | ✅ Complete |
| Test Coverage | ~60% |
| PHPStan Level | 8 |
| Production Status | 🟢 Live |

---

## 🎯 Roadmap Phases

### Phase 1: Quick Wins (v2.7.x)
**Target: 1-2 weeks per feature**

- [x] **Deployment Rollback UI** - One-click rollback to previous deployment ✅ (v2.7.0)
- [x] **Project Health Dashboard** - Aggregate health view of all projects ✅ (v2.7.0)
- [x] **Deployment Scheduling** - Schedule deployments for off-peak hours ✅ (v2.7.0)
- [x] **Project Templates** - Pre-configured templates for Laravel, Node, etc. ✅ (v2.7.0)
- [ ] **Server Monitoring Dashboard** - Real-time metrics charts for all servers
- [ ] **Server Groups/Tags** - Organize servers with tags and groups
- [ ] **Bulk Server Actions** - Execute actions on multiple servers at once
- [ ] **SSH Key Management UI** - Manage SSH keys from the interface

### Phase 2: Feature Expansion (v2.8.x)
**Target: 2-4 weeks per feature**

- [ ] **Webhook Deployments** - Auto-deploy on GitHub/GitLab push events
- [ ] **SSL Certificate Management** - Let's Encrypt auto-renewal integration
- [ ] **Database Backups** - Scheduled MySQL/PostgreSQL backups with S3 storage
- [ ] **Server Backups** - Full server backup management with scheduling
- [ ] **Log Aggregation** - Centralized log viewing with search/filter
- [ ] **Resource Alerts** - CPU/RAM/Disk threshold notifications
- [ ] **Automated Health Checks** - Scheduled health checks with email/Slack alerts

### Phase 3: Enterprise Features (v3.0.x)
**Target: 1-2 months per feature**

- [ ] **GitHub App Integration** - OAuth-based repo access, PR status checks
- [ ] **Team Collaboration** - Multiple users per project with roles
- [ ] **API v1** - RESTful API for external integrations
- [ ] **Mobile App** - React Native app for monitoring on-the-go

---

## 📋 Detailed Task Breakdown

### 🔥 High Priority Tasks

#### 1. Webhook Deployments
**Priority:** 🔴 High | **Effort:** Medium | **Version:** v2.8.0

**Description:** Enable automatic deployments triggered by GitHub/GitLab webhook events (push, merge).

**Tasks:**
- [ ] Create `WebhookController` for receiving payloads
- [ ] Add webhook secret token per project
- [ ] Implement payload signature verification (HMAC)
- [ ] Support GitHub push events
- [ ] Support GitLab push events
- [ ] Support Bitbucket push events
- [ ] Add webhook URL display in project settings
- [ ] Create webhook delivery log table
- [ ] Add retry mechanism for failed deployments
- [ ] Write tests for webhook handling

**Database Changes:**
```sql
ALTER TABLE projects ADD COLUMN webhook_secret VARCHAR(64) NULL;
ALTER TABLE projects ADD COLUMN webhook_enabled BOOLEAN DEFAULT FALSE;

CREATE TABLE webhook_deliveries (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    provider ENUM('github', 'gitlab', 'bitbucket', 'custom') NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    payload JSON NOT NULL,
    signature VARCHAR(255) NULL,
    status ENUM('pending', 'success', 'failed', 'ignored') DEFAULT 'pending',
    response TEXT NULL,
    deployment_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (deployment_id) REFERENCES deployments(id) ON DELETE SET NULL
);
```

**Files to Create/Modify:**
- `app/Http/Controllers/WebhookController.php` (new)
- `app/Services/WebhookService.php` (new)
- `database/migrations/xxxx_create_webhook_deliveries_table.php` (new)
- `routes/api.php` (add webhook routes)
- `resources/views/livewire/projects/project-settings.blade.php` (add webhook config)

---

#### 2. Deployment Rollback UI
**Priority:** 🔴 High | **Effort:** Low | **Version:** v2.7.0

**Description:** Allow users to rollback to any previous successful deployment with one click.

**Tasks:**
- [ ] Add "Rollback" button to deployment history
- [ ] Create `RollbackService` for handling rollback logic
- [ ] Store deployment snapshots (commit hash, env vars)
- [ ] Implement git checkout to specific commit
- [ ] Run post-deployment commands after rollback
- [ ] Add rollback confirmation modal
- [ ] Track rollback in deployment history
- [ ] Add rollback status indicator
- [ ] Write tests for rollback functionality

**Files to Create/Modify:**
- `app/Services/RollbackService.php` (enhance existing)
- `app/Livewire/Deployments/DeploymentRollback.php` (enhance)
- `resources/views/livewire/deployments/deployment-history.blade.php`

---

#### 3. SSL Certificate Management
**Priority:** 🔴 High | **Effort:** Medium | **Version:** v2.8.0

**Description:** Integrate Let's Encrypt for automatic SSL certificate provisioning and renewal.

**Tasks:**
- [ ] Install acme.sh or certbot on servers
- [ ] Create `SSLService` for certificate management
- [ ] Add SSL status to domain model
- [ ] Implement certificate issuance flow
- [ ] Add auto-renewal cron job
- [ ] Create SSL dashboard showing expiry dates
- [ ] Add renewal notifications (7 days before expiry)
- [ ] Support wildcard certificates
- [ ] Handle DNS-01 challenge for wildcards
- [ ] Write tests for SSL operations

**Database Changes:**
```sql
ALTER TABLE domains ADD COLUMN ssl_provider ENUM('letsencrypt', 'custom', 'none') DEFAULT 'none';
ALTER TABLE domains ADD COLUMN ssl_issued_at TIMESTAMP NULL;
ALTER TABLE domains ADD COLUMN ssl_expires_at TIMESTAMP NULL;
ALTER TABLE domains ADD COLUMN ssl_auto_renew BOOLEAN DEFAULT TRUE;
```

---

#### 4. Project Health Dashboard
**Priority:** 🟡 Medium | **Effort:** Low | **Version:** v2.7.0

**Description:** Aggregate dashboard showing health status of all projects at a glance.

**Tasks:**
- [ ] Create `HealthDashboard` Livewire component
- [ ] Add health check endpoint per project
- [ ] Implement periodic health polling
- [ ] Show uptime percentage
- [ ] Display response times
- [ ] Add status indicators (green/yellow/red)
- [ ] Create health history chart
- [ ] Add filtering by status
- [ ] Write tests for health checks

**Files to Create:**
- `app/Livewire/Dashboard/HealthDashboard.php`
- `app/Services/HealthCheckService.php`
- `resources/views/livewire/dashboard/health-dashboard.blade.php`

---

#### 5. Server Monitoring Dashboard
**Priority:** 🔴 High | **Effort:** Medium | **Version:** v2.7.1

**Description:** Real-time server metrics dashboard with charts and historical data.

**Tasks:**
- [ ] Create `ServerMetricsDashboard` Livewire component
- [ ] Add real-time CPU usage chart (Chart.js/ApexCharts)
- [ ] Add real-time Memory usage chart
- [ ] Add real-time Disk I/O chart
- [ ] Add Network I/O chart
- [ ] Implement WebSocket for live updates
- [ ] Store metrics history in database
- [ ] Add time range selector (1h, 6h, 24h, 7d, 30d)
- [ ] Create aggregate view for all servers
- [ ] Add export metrics to CSV
- [ ] Write tests for metrics collection

**Database Changes:**
```sql
CREATE TABLE server_metrics (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    server_id BIGINT UNSIGNED NOT NULL,
    cpu_usage DECIMAL(5,2) NOT NULL,
    memory_usage DECIMAL(5,2) NOT NULL,
    memory_used_mb INT NOT NULL,
    memory_total_mb INT NOT NULL,
    disk_usage DECIMAL(5,2) NOT NULL,
    disk_used_gb INT NOT NULL,
    disk_total_gb INT NOT NULL,
    load_average_1 DECIMAL(5,2) NULL,
    load_average_5 DECIMAL(5,2) NULL,
    load_average_15 DECIMAL(5,2) NULL,
    network_in_bytes BIGINT UNSIGNED NULL,
    network_out_bytes BIGINT UNSIGNED NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_server_time (server_id, recorded_at),
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
);
```

**Files to Create:**
- `app/Livewire/Servers/ServerMetricsDashboard.php`
- `app/Services/ServerMetricsService.php`
- `resources/views/livewire/servers/server-metrics-dashboard.blade.php`
- `app/Console/Commands/CollectServerMetrics.php`

---

#### 6. Server Groups/Tags
**Priority:** 🟡 Medium | **Effort:** Low | **Version:** v2.7.1

**Description:** Organize servers with tags and groups for better management.

**Tasks:**
- [ ] Create `ServerTag` model
- [ ] Create `ServerGroup` model
- [ ] Add tag management UI
- [ ] Add group management UI
- [ ] Implement tag filtering on server list
- [ ] Implement group filtering on server list
- [ ] Add color picker for tags
- [ ] Show tags on server cards
- [ ] Add bulk tag assignment
- [ ] Write tests for tags/groups

**Database Changes:**
```sql
CREATE TABLE server_tags (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#6366f1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_tag (user_id, name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE server_tag_pivot (
    server_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (server_id, tag_id),
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES server_tags(id) ON DELETE CASCADE
);

CREATE TABLE server_groups (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE servers ADD COLUMN group_id BIGINT UNSIGNED NULL;
ALTER TABLE servers ADD FOREIGN KEY (group_id) REFERENCES server_groups(id) ON DELETE SET NULL;
```

---

#### 7. Bulk Server Actions
**Priority:** 🟡 Medium | **Effort:** Low | **Version:** v2.7.1

**Description:** Execute actions on multiple servers simultaneously.

**Tasks:**
- [ ] Add checkbox selection to server list
- [ ] Create bulk action dropdown (Ping All, Reboot All, etc.)
- [ ] Implement parallel SSH execution
- [ ] Add progress indicator for bulk operations
- [ ] Show results summary after completion
- [ ] Add confirmation modal for destructive actions
- [ ] Support bulk Docker installation
- [ ] Support bulk service restart
- [ ] Add bulk action history log
- [ ] Write tests for bulk operations

**Files to Create:**
- `app/Livewire/Servers/BulkServerActions.php`
- `app/Services/BulkServerActionService.php`
- `resources/views/livewire/servers/bulk-server-actions.blade.php`

---

#### 8. SSH Key Management UI
**Priority:** 🟡 Medium | **Effort:** Medium | **Version:** v2.7.1

**Description:** Manage SSH keys from the web interface instead of manually.

**Tasks:**
- [ ] Create `SSHKey` model
- [ ] Add SSH key generation (RSA, Ed25519)
- [ ] Add SSH key import from file
- [ ] Add SSH key export/download
- [ ] Show public key for copying
- [ ] Add key deployment to servers
- [ ] Create key rotation workflow
- [ ] Add key expiry tracking
- [ ] Show which servers use which keys
- [ ] Write tests for SSH key management

**Database Changes:**
```sql
CREATE TABLE ssh_keys (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('rsa', 'ed25519', 'ecdsa') DEFAULT 'ed25519',
    public_key TEXT NOT NULL,
    private_key_encrypted TEXT NOT NULL,
    fingerprint VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE server_ssh_key (
    server_id BIGINT UNSIGNED NOT NULL,
    ssh_key_id BIGINT UNSIGNED NOT NULL,
    deployed_at TIMESTAMP NULL,
    PRIMARY KEY (server_id, ssh_key_id),
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    FOREIGN KEY (ssh_key_id) REFERENCES ssh_keys(id) ON DELETE CASCADE
);
```

**Files to Create:**
- `app/Models/SSHKey.php`
- `app/Livewire/Settings/SSHKeyManager.php`
- `app/Services/SSHKeyService.php`
- `resources/views/livewire/settings/ssh-key-manager.blade.php`

---

### 🚀 Medium Priority Tasks

#### 9. Database Backups
**Priority:** 🟡 Medium | **Effort:** Medium | **Version:** v2.8.0

**Description:** Scheduled database backups with cloud storage support.

**Tasks:**
- [ ] Create `BackupService` for database dumps
- [ ] Support MySQL and PostgreSQL
- [ ] Implement S3 storage integration
- [ ] Add backup scheduling (daily, weekly, monthly)
- [ ] Create backup retention policies
- [ ] Add backup restore functionality
- [ ] Show backup history with download links
- [ ] Add backup notifications
- [ ] Implement backup encryption
- [ ] Write tests for backup operations

**Database Changes:**
```sql
CREATE TABLE backups (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    type ENUM('database', 'files', 'full') NOT NULL,
    storage_driver ENUM('local', 's3', 'gcs', 'dropbox') DEFAULT 'local',
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT UNSIGNED NULL,
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE backup_schedules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    frequency ENUM('hourly', 'daily', 'weekly', 'monthly') NOT NULL,
    time TIME DEFAULT '02:00:00',
    day_of_week TINYINT NULL,
    day_of_month TINYINT NULL,
    retention_days INT DEFAULT 30,
    is_active BOOLEAN DEFAULT TRUE,
    last_run_at TIMESTAMP NULL,
    next_run_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

---

#### 6. Log Aggregation
**Priority:** 🟡 Medium | **Effort:** Medium | **Version:** v2.8.0

**Description:** Centralized log viewing with search, filter, and real-time streaming.

**Tasks:**
- [ ] Create `LogAggregatorService`
- [ ] Implement log file parsing
- [ ] Add log level filtering (error, warning, info, debug)
- [ ] Create full-text search functionality
- [ ] Implement real-time log streaming (WebSocket)
- [ ] Add log retention settings
- [ ] Create log export functionality
- [ ] Add log pattern alerts
- [ ] Support multiple log formats (Laravel, Apache, Nginx)
- [ ] Write tests for log operations

---

#### 7. Resource Alerts
**Priority:** 🟡 Medium | **Effort:** Medium | **Version:** v2.8.0

**Description:** Configurable alerts when server resources exceed thresholds.

**Tasks:**
- [ ] Create `AlertService` for monitoring
- [ ] Add threshold configuration per server
- [ ] Implement CPU usage monitoring
- [ ] Implement RAM usage monitoring
- [ ] Implement Disk usage monitoring
- [ ] Add notification channels (email, Slack, Discord)
- [ ] Create alert history log
- [ ] Add alert acknowledgment feature
- [ ] Implement alert escalation
- [ ] Write tests for alert system

**Database Changes:**
```sql
CREATE TABLE alert_rules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    server_id BIGINT UNSIGNED NOT NULL,
    metric ENUM('cpu', 'ram', 'disk', 'load', 'network') NOT NULL,
    operator ENUM('gt', 'lt', 'eq', 'gte', 'lte') NOT NULL,
    threshold DECIMAL(10,2) NOT NULL,
    duration_minutes INT DEFAULT 5,
    notification_channels JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
);

CREATE TABLE alerts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    alert_rule_id BIGINT UNSIGNED NOT NULL,
    server_id BIGINT UNSIGNED NOT NULL,
    metric VARCHAR(50) NOT NULL,
    current_value DECIMAL(10,2) NOT NULL,
    threshold_value DECIMAL(10,2) NOT NULL,
    status ENUM('triggered', 'acknowledged', 'resolved') DEFAULT 'triggered',
    triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP NULL,
    acknowledged_by BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (alert_rule_id) REFERENCES alert_rules(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
);
```

---

#### 8. Deployment Scheduling
**Priority:** 🟢 Low | **Effort:** Low | **Version:** v2.7.0

**Description:** Schedule deployments for specific times (maintenance windows).

**Tasks:**
- [ ] Add scheduled deployment option to deploy modal
- [ ] Create `ScheduledDeployment` model
- [ ] Implement Laravel scheduler integration
- [ ] Add timezone support
- [ ] Create scheduled deployment list view
- [ ] Add cancel/edit scheduled deployment
- [ ] Send reminder notifications before deployment
- [ ] Write tests for scheduling

---

#### 9. Project Templates
**Priority:** 🟢 Low | **Effort:** Low | **Version:** v2.7.0

**Description:** Pre-configured project templates for common frameworks.

**Tasks:**
- [ ] Create template seeder with common configurations
- [ ] Add template selection to project creation
- [ ] Include Laravel template (with standard commands)
- [ ] Include Node.js/Express template
- [ ] Include Next.js template
- [ ] Include static site template
- [ ] Allow custom template creation
- [ ] Write tests for template application

---

### 🏗️ Large Initiatives

#### 10. GitHub App Integration
**Priority:** 🟢 Low | **Effort:** High | **Version:** v3.0.0

**Description:** Full GitHub App integration with OAuth, PR status checks, and enhanced repository access.

**Tasks:**
- [ ] Register GitHub App
- [ ] Implement OAuth2 flow
- [ ] Add installation webhook handling
- [ ] Implement repository listing from GitHub
- [ ] Add PR status check updates
- [ ] Create deployment status notifications
- [ ] Support GitHub Actions integration
- [ ] Add repository permissions management
- [ ] Write comprehensive tests

---

#### 11. Team Collaboration
**Priority:** 🟢 Low | **Effort:** High | **Version:** v3.0.0

**Description:** Multi-user access per project with granular permissions.

**Tasks:**
- [ ] Create `Team` model
- [ ] Create `TeamMember` model with roles
- [ ] Implement permission system (view, deploy, edit, admin)
- [ ] Add team invitation system
- [ ] Create team management UI
- [ ] Add activity log per team
- [ ] Implement project sharing between teams
- [ ] Add team billing (if needed)
- [ ] Write tests for team features

---

#### 12. API v1
**Priority:** 🟢 Low | **Effort:** High | **Version:** v3.0.0

**Description:** RESTful API for external integrations and automation.

**Tasks:**
- [ ] Design API structure (OpenAPI spec)
- [ ] Implement API authentication (tokens)
- [ ] Create project endpoints (CRUD)
- [ ] Create deployment endpoints
- [ ] Create server endpoints
- [ ] Add rate limiting
- [ ] Generate API documentation
- [ ] Create API versioning strategy
- [ ] Add webhook event system
- [ ] Write API tests

---

## 🛠️ Technical Improvements

### Code Quality
- [ ] Increase PHPStan to Level 9
- [ ] Add Rector for automated refactoring
- [ ] Implement Laravel Pint for code style
- [ ] Add pre-commit hooks

### Testing
- [ ] Increase test coverage to 80%+
- [ ] Add E2E tests with Laravel Dusk
- [ ] Add performance benchmarks
- [ ] Implement mutation testing

### Performance
- [ ] Audit and fix N+1 queries
- [ ] Implement Redis caching strategy
- [ ] Add database query logging in dev
- [ ] Optimize Livewire component rendering

### Documentation
- [ ] Create API documentation (Swagger/OpenAPI)
- [ ] Add inline code documentation
- [ ] Create video tutorials
- [ ] Write contributor guide

---

## 📅 Release Schedule

| Version | Target Date | Focus |
|---------|-------------|-------|
| v2.7.0 | Dec 2025 | Rollback UI, Health Dashboard, Scheduling, Templates |
| v2.8.0 | Jan 2026 | Webhooks, SSL, Backups, Log Aggregation, Alerts |
| v3.0.0 | Mar 2026 | GitHub App, Teams, API v1 |

---

## 🏷️ Version History

| Version | Date | Highlights |
|---------|------|------------|
| v2.6.3 | 2025-11-28 | Server Quick Actions, Auto-ping, Docker sudo fix, UI redesign |
| v2.6.2 | 2025-11-28 | Git auto-refresh, SSH command fix |
| v2.6.1 | 2025-11-27 | SSH terminal enhancements, sudo support |
| v2.6.0 | 2025-11-27 | Loading states, Docker multi-OS support |
| v2.5.x | 2025-11 | Kubernetes, CI/CD, Notifications, Multi-tenant |
| v2.4.x | 2025-11 | Modern UI, Tabbed interface, Environment management |

---

## 📝 Contributing

When working on tasks:

1. Create a feature branch: `feature/webhook-deployments`
2. Update this ROADMAP.md with progress
3. Write tests for new functionality
4. Update CHANGELOG.md
5. Create PR with description referencing this roadmap

---

*Last updated: 2025-11-28 by DevFlow Pro Team*
