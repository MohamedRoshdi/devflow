# DevFlow Pro - User Guide

## Table of Contents
1. [Getting Started](#getting-started)
2. [Dashboard Overview](#dashboard-overview)
3. [Project Management](#project-management)
4. [Deployment Operations](#deployment-operations)
5. [Docker Container Management](#docker-container-management)
6. [Domain & SSL Management](#domain--ssl-management)
7. [Multi-Tenant Operations](#multi-tenant-operations)
8. [Monitoring & Analytics](#monitoring--analytics)
9. [User Settings & Preferences](#user-settings--preferences)
10. [Advanced Features](#advanced-features)

---

## Getting Started

### First Login
1. Navigate to your DevFlow Pro instance (e.g., `https://devflow.yourdomain.com`)
2. Login with your credentials
3. You'll be directed to the main dashboard

### Initial Setup
```
Default Credentials:
- Email: admin@devflow.com
- Password: DevFlow@2025
```

**Important:** Change your password immediately after first login.

### User Interface Overview
- **Top Navigation Bar:** Quick access to projects, deployments, and settings
- **Sidebar:** Main navigation for all features
- **Dashboard:** Real-time overview of all projects
- **Notifications:** Real-time deployment and system alerts

---

## Dashboard Overview

### Main Dashboard Components

#### Project Status Cards
Shows real-time status of all your projects:
- **Green Badge:** Project is running
- **Yellow Badge:** Project is in maintenance
- **Red Badge:** Project has issues
- **Gray Badge:** Project is stopped

#### Recent Deployments
- Last 10 deployments across all projects
- Click on any deployment to view logs
- Color-coded status indicators

#### System Health Metrics
- **Server Resources:** CPU, Memory, Disk usage
- **Container Status:** Running/Stopped containers
- **SSL Certificates:** Expiration warnings
- **Storage Usage:** Per-project storage metrics

### Quick Actions
- **Deploy All:** Deploy updates to all active projects
- **Clear All Caches:** Clear caches for all projects
- **System Cleanup:** Remove old logs and temporary files

---

## Project Management

### Creating a New Project

1. **Navigate to Projects**
   - Click "Projects" in the sidebar
   - Click "Create New Project" button

2. **Fill Project Details**
   ```
   Required Fields:
   - Project Name: Display name for your project
   - Slug: URL-friendly identifier (auto-generated)
   - Repository URL: Git repository (GitHub/GitLab/Bitbucket)
   - Branch: Default deployment branch (usually 'main' or 'master')
   - Framework: Laravel/Symfony/WordPress/Custom
   - PHP Version: 8.1/8.2/8.3/8.4
   - Server: Select target server
   ```

3. **Configure Environment Variables**
   - Click "Add Environment Variable"
   - Enter key-value pairs
   - Sensitive values are encrypted

4. **Docker Configuration**
   - Default: `docker-compose.yml`
   - Custom path if your compose file is elsewhere
   - Enable/disable auto-deployment

5. **Save Project**
   - Click "Create Project"
   - Initial deployment will start automatically

### Managing Existing Projects

#### Project Overview Tab
- **Status:** Current project status
- **Last Deployment:** Time and status
- **Health Check:** Automated health monitoring
- **Quick Stats:** Deployments, uptime, storage

#### Docker Tab
- View all containers
- Start/Stop/Restart containers
- View container logs
- Resource usage per container

#### Environment Tab
- Edit environment variables
- Add new variables
- Remove unused variables
- Export/Import configurations

#### Git & Commits Tab
- Recent commits from repository
- Branch information
- Deploy specific commits
- View commit differences

#### Logs Tab
- Application logs (Laravel logs)
- Error logs
- Access logs
- Download log files

#### Deployments Tab
- Deployment history
- Rollback to previous deployments
- View deployment logs
- Cancel running deployments

### Deleting a Project
1. Go to Project Settings
2. Scroll to "Danger Zone"
3. Click "Delete Project"
4. Confirm by typing the project name
5. Click "Delete Permanently"

**Warning:** This action is irreversible and will delete all project data.

---

## Deployment Operations

### Manual Deployment

1. **From Project Page**
   - Navigate to your project
   - Click the "Deploy" button
   - Confirm deployment in the modal

2. **Deploy Specific Commit**
   - Go to "Git & Commits" tab
   - Find the commit you want
   - Click "Deploy" next to the commit

3. **Force Deployment**
   - Use when normal deployment fails
   - Click "Deploy" → "Force Deploy"
   - Rebuilds all containers

### Automatic Deployment

#### Setup Webhook (GitHub)
1. Go to Project Settings
2. Copy the webhook URL
3. In GitHub repository settings:
   - Go to Settings → Webhooks
   - Add webhook with the copied URL
   - Select "Push events"
   - Save webhook

#### Setup Webhook (GitLab)
1. Similar process as GitHub
2. Use "Push events" trigger

### Deployment Rollback

1. **Quick Rollback**
   - Go to Deployments tab
   - Find a successful previous deployment
   - Click "Rollback to this version"
   - Confirm rollback

2. **Emergency Rollback**
   - Use when current deployment fails
   - Automatically rolls back to last successful deployment
   - Available in deployment logs during failure

### Deployment Logs

#### Real-time Logs
- View logs as deployment progresses
- Color-coded output (info, warning, error)
- Auto-scroll to latest output

#### Log Actions
- **Download:** Save logs locally
- **Share:** Generate shareable link
- **Clear:** Remove old logs (admin only)

---

## Docker Container Management

### Container Operations

#### Start/Stop Containers
1. Go to project Docker tab
2. Select containers
3. Use bulk actions or individual controls

#### Restart Services
- **Soft Restart:** Graceful restart
- **Hard Restart:** Force restart
- **Rebuild:** Rebuild container from image

### Container Logs

#### Viewing Logs
1. Click on container name
2. Select log type:
   - stdout (standard output)
   - stderr (error output)
3. Use filters:
   - Time range
   - Search keywords
   - Log level

#### Following Logs
- Click "Follow Logs" for real-time updates
- Useful for debugging live issues

### Container Shell Access

1. Click "Terminal" on container
2. Opens web-based terminal
3. Run commands directly in container
4. Exit with `Ctrl+D` or type `exit`

### Resource Management

#### Setting Limits
```yaml
# In docker-compose.yml
services:
  app:
    mem_limit: 512m
    cpus: '0.5'
```

#### Monitoring Usage
- Real-time CPU usage
- Memory consumption
- Network I/O
- Disk usage

---

## Domain & SSL Management

### Adding a Domain

1. **Navigate to Domains**
   - Go to project settings
   - Click "Domains" tab
   - Click "Add Domain"

2. **Configure Domain**
   ```
   Fields:
   - Domain: yourdomain.com
   - Subdomain: app (optional)
   - SSL: Enable/Disable
   - Auto-renew SSL: Recommended
   ```

3. **DNS Configuration**
   - Add A record pointing to server IP
   - Wait for DNS propagation (5-30 minutes)

4. **Verify Domain**
   - Click "Verify Domain"
   - System checks DNS and accessibility

### SSL Certificate Management

#### Automatic SSL (Let's Encrypt)
1. Enable SSL when adding domain
2. System automatically obtains certificate
3. Auto-renewal 30 days before expiry

#### Manual SSL Upload
1. Click "Upload SSL Certificate"
2. Provide:
   - Certificate file (.crt)
   - Private key (.key)
   - CA bundle (optional)
3. Save configuration

#### SSL Monitoring
- Dashboard shows expiry warnings
- Email notifications 30, 14, 7 days before expiry
- Automatic renewal attempts

### Multiple Domains
- Add unlimited domains per project
- Set primary domain
- Configure redirects
- Domain-specific environment variables

---

## Multi-Tenant Operations

### Managing Tenants

#### Creating a Tenant
1. Go to Multi-Tenant Management
2. Click "Create Tenant"
3. Provide:
   - Tenant name
   - Subdomain
   - Database name
   - Initial admin user

#### Tenant Operations
- **Activate/Deactivate:** Control tenant access
- **Reset:** Reset tenant to initial state
- **Backup:** Create tenant-specific backup
- **Delete:** Remove tenant completely

### Tenant Deployments

#### Deploy to All Tenants
1. Click "Deploy to All Tenants"
2. Select deployment type:
   - Code only
   - Code + migrations
   - Full deployment
3. Monitor progress per tenant

#### Deploy to Specific Tenants
1. Select tenants from list
2. Click "Deploy to Selected"
3. Choose deployment options

### Tenant Isolation

#### Database Isolation
- Each tenant has separate database
- No cross-tenant data access
- Individual backup/restore

#### Storage Isolation
```
/storage
  /tenant-1
  /tenant-2
  /tenant-3
```

### Tenant Monitoring
- Individual resource usage
- Per-tenant error logs
- Usage analytics
- Billing metrics

---

## Monitoring & Analytics

### Project Analytics

#### Deployment Metrics
- Success/failure rates
- Average deployment time
- Deployment frequency
- Rollback statistics

#### Performance Metrics
- Response times
- Request rates
- Error rates
- Resource usage trends

### Alert Configuration

#### Setting Up Alerts
1. Go to Settings → Alerts
2. Configure thresholds:
   ```
   - CPU Usage > 80%
   - Memory Usage > 90%
   - Disk Usage > 85%
   - Error Rate > 5%
   - Response Time > 2s
   ```

3. Set notification channels:
   - Email
   - Slack
   - Discord
   - Webhooks

#### Alert History
- View past alerts
- Analyze patterns
- Export alert data
- Configure alert suppression

### Custom Dashboards

#### Creating Dashboards
1. Click "Create Dashboard"
2. Add widgets:
   - Charts
   - Metrics
   - Tables
   - Status indicators
3. Configure refresh rates
4. Save and share

---

## User Settings & Preferences

### Profile Management

#### Personal Information
- Update name and email
- Change password
- Set timezone
- Upload avatar

#### Security Settings
- Two-factor authentication (2FA)
- API tokens
- SSH keys
- Session management

### Notification Preferences

#### Email Notifications
Toggle notifications for:
- Deployment success/failure
- SSL expiry warnings
- System alerts
- Security notifications

#### Real-time Notifications
- Desktop notifications
- Sound alerts
- Browser notifications
- Mobile push (if app installed)

### Interface Customization

#### Theme Settings
- Light/Dark mode
- Auto (follows system)
- Custom accent colors

#### Dashboard Layout
- Rearrange widgets
- Hide/show sections
- Set default view
- Quick access shortcuts

---

## Advanced Features

### API Access

#### Generating API Token
1. Go to Settings → API Tokens
2. Click "Generate Token"
3. Set permissions and expiry
4. Copy token (shown once only)

#### Using API
```bash
# Example API call
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://devflow.yourdomain.com/api/projects
```

### Backup & Restore

#### Automated Backups
Configure in project settings:
- Frequency: Daily/Weekly/Monthly
- Retention: 7/30/90 days
- Storage: Local/S3/External

#### Manual Backup
1. Go to project settings
2. Click "Backup Now"
3. Select backup type:
   - Full (code + database + files)
   - Database only
   - Files only

#### Restore Process
1. Go to Backups section
2. Select backup to restore
3. Choose restore options:
   - Full restore
   - Database only
   - Files only
4. Confirm restore

### Scheduled Tasks

#### Creating Scheduled Tasks
1. Go to Automation → Scheduled Tasks
2. Create new task:
   ```
   - Name: Clear old logs
   - Schedule: 0 2 * * * (2 AM daily)
   - Command: cleanup:logs
   - Projects: All/Selected
   ```

#### Task Types
- Deployments
- Backups
- Cleanup
- Health checks
- Custom scripts

### Integration with CI/CD

#### GitHub Actions Integration
```yaml
# .github/workflows/deploy.yml
name: Deploy to DevFlow Pro
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Deploy via API
        run: |
          curl -X POST \
            -H "Authorization: Bearer ${{ secrets.DEVFLOW_TOKEN }}" \
            https://devflow.yourdomain.com/api/projects/${{ secrets.PROJECT_ID }}/deploy
```

#### GitLab CI Integration
```yaml
# .gitlab-ci.yml
deploy:
  stage: deploy
  script:
    - curl -X POST -H "Authorization: Bearer $DEVFLOW_TOKEN" $DEVFLOW_API/deploy
  only:
    - main
```

---

## Best Practices

### Deployment Best Practices
1. Always test in staging first
2. Use deployment windows for production
3. Have rollback plan ready
4. Monitor after deployment
5. Document deployment procedures

### Security Best Practices
1. Use strong passwords
2. Enable 2FA for all users
3. Regularly rotate API tokens
4. Keep SSL certificates updated
5. Monitor access logs

### Performance Best Practices
1. Regular cache clearing
2. Log rotation setup
3. Database optimization
4. Container resource limits
5. CDN for static assets

---

## Keyboard Shortcuts

### Global Shortcuts
- `Ctrl/Cmd + K` - Quick search
- `Ctrl/Cmd + D` - Deploy current project
- `Ctrl/Cmd + L` - View logs
- `Ctrl/Cmd + /` - Show shortcuts
- `Esc` - Close modals

### Project View Shortcuts
- `G then P` - Go to projects
- `G then D` - Go to deployments
- `G then S` - Go to settings
- `Tab` - Switch between tabs
- `R` - Refresh current view

---

## Getting Help

### Support Channels
- Documentation: docs.devflow.pro
- Email: support@devflow.pro
- Discord: discord.gg/devflow
- GitHub Issues: github.com/devflow-pro/issues

### Useful Resources
- Video tutorials
- Community forums
- Stack Overflow tag: `devflow-pro`
- Blog: blog.devflow.pro

---

## Appendix

### System Requirements
- PHP 8.4+
- MySQL 8.0+
- Docker 20.10+
- 2GB RAM minimum
- 20GB disk space

### Supported Frameworks
- Laravel 8/9/10/11/12
- Symfony 5/6/7
- WordPress 5.x/6.x
- Shopware 6
- Custom PHP applications

### Browser Compatibility
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers supported

---

*Last updated: November 2024*
*Version: 2.0.0*