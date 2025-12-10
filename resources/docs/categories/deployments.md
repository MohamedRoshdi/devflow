---
title: Deployments
description: Deploy, rollback, and manage your applications
---

# Deployments

Deploy your applications with confidence using DevFlow Pro's powerful deployment features.

## Deploy Button

The deploy button triggers an immediate deployment of your project. When clicked, DevFlow Pro will:

1. SSH into your server
2. Pull the latest code from your Git repository
3. Install dependencies (composer, npm)
4. Run database migrations
5. Clear caches
6. Restart services if needed

**How to use:**

- Navigate to your project page
- Click the green "Deploy" button
- Monitor deployment progress in real-time
- View deployment logs for details

**Best practices:**

- Always test in staging before deploying to production
- Review recent commits before deploying
- Monitor application after deployment
- Keep deployment history for easy rollback

## Auto-Deploy

Auto-deploy automatically deploys your project when you push code to GitHub or GitLab.

**Setup steps:**

1. Enable auto-deploy in project settings
2. Copy the webhook URL
3. Add webhook to your GitHub/GitLab repository
4. Push code and watch it deploy automatically

**When it deploys:**

- On push to main/production branch (configurable)
- Only if previous deployment was successful
- Can be configured for specific branches

**Safety features:**

- Deployment queue (one at a time)
- Automatic rollback on failure (optional)
- Email notifications on success/failure
- Deployment history tracking

## Rollback

Instantly revert to a previous working deployment if something goes wrong.

**How it works:**

1. Navigate to deployment history
2. Find the last working deployment
3. Click "Rollback" button
4. Previous version restored in seconds

**What gets rolled back:**

- Application code
- Database migrations (if supported)
- Environment variables (optional)
- Asset files

**Important notes:**

- Database changes may not be reversible
- Always backup before major deployments
- Test rollback in staging first
- Last 10 deployments available for rollback

## Deployment History

View complete history of all deployments for your project.

**Information shown:**

- Deployment date and time
- Who triggered the deployment
- Git commit hash and message
- Deployment status (success/failed)
- Deployment duration
- Full deployment logs

**Filtering options:**

- Filter by date range
- Filter by status
- Filter by user
- Search by commit message

## Deployment Logs

Real-time logs showing exactly what happened during deployment.

**Log types:**

- Git operations (clone, pull, checkout)
- Dependency installation
- Database migrations
- Cache clearing
- Service restarts
- Error messages and stack traces

**Features:**

- Real-time streaming during deployment
- Download logs for offline analysis
- Search within logs
- Syntax highlighting for errors

## Manual Deployment

Manually trigger deployment with custom options.

**Options available:**

- Choose specific branch to deploy
- Skip database migrations
- Skip cache clearing
- Force deployment (bypass checks)
- Deploy to specific server

**Use cases:**

- Deploying hotfix from different branch
- Testing deployment process
- Deploying to staging environment
- Emergency deployments

## Scheduled Deployments

Deploy automatically at specific times.

**Configuration:**

- Daily at specific time (e.g., 2 AM)
- Weekly on specific day
- Monthly on specific date
- Cron expression for advanced scheduling

**Use cases:**

- Deploy during low-traffic hours
- Regular maintenance deployments
- Coordinated releases across time zones
- Content publishing schedules

## Deployment Approvals

Require manual approval before deployment proceeds.

**How it works:**

1. Deployment triggered
2. System pauses and sends notification
3. Designated approver reviews changes
4. Approver clicks "Approve" or "Reject"
5. Deployment proceeds or cancels

**Configuration:**

- Set required approvers
- Set approval timeout
- Configure approval notifications
- Set approval rules by environment

**Best for:**

- Production deployments
- Compliance requirements
- High-stakes releases
- Multi-step review process

## Zero-Downtime Deployment

Deploy without taking your application offline.

**Strategies:**

- Blue-green deployment
- Rolling deployment
- Canary deployment

**How it works:**

1. New version deployed to separate environment
2. Health checks verify new version works
3. Traffic gradually shifted to new version
4. Old version kept as backup
5. Old version removed after verification

**Requirements:**

- Load balancer or proxy
- Multiple server instances
- Health check endpoints
- Database backward compatibility

## Deployment Notifications

Get notified about deployment status.

**Notification channels:**

- Email
- Slack
- Discord
- Microsoft Teams
- SMS (if configured)
- Custom webhooks

**Notification triggers:**

- Deployment started
- Deployment succeeded
- Deployment failed
- Deployment requires approval
- Deployment rolled back

**Configuration:**

- Choose channels per project
- Set notification recipients
- Configure notification templates
- Set notification rules

## Environment-Specific Deployments

Deploy different configurations to different environments.

**Environments:**

- Development
- Staging
- Production
- Testing

**Per-environment settings:**

- Different branches
- Different environment variables
- Different deployment scripts
- Different approval requirements
- Different notification settings

## Deployment Queue

Multiple deployments are queued and processed one at a time.

**How it works:**

- Only one deployment per project at a time
- New deployments added to queue
- Queue processed in order (FIFO)
- View queue status in real-time

**Queue management:**

- View queued deployments
- Cancel queued deployment
- Reprioritize queue (admin)
- View queue history

## Deployment Metrics

Track deployment performance and success rate.

**Metrics tracked:**

- Total deployments
- Success rate
- Average deployment time
- Deployments per day/week/month
- Failure rate and reasons
- Most active projects
- Top deployers

**Reports:**

- Weekly deployment summary
- Monthly deployment report
- Failure analysis
- Performance trends

## Troubleshooting Deployments

Common deployment issues and solutions.

### Deployment Stuck

**Symptoms:** Deployment shows "Running" but no progress

**Solutions:**

- Check server SSH connection
- Check server disk space
- Check server memory
- Cancel and retry deployment
- Check deployment logs for errors

### Deployment Failed

**Symptoms:** Deployment shows "Failed" status

**Solutions:**

- Review deployment logs for error message
- Check Git repository is accessible
- Check database credentials
- Check file permissions on server
- Check server has required PHP extensions
- Rollback and try again

### Slow Deployments

**Symptoms:** Deployments taking longer than usual

**Solutions:**

- Check server resources (CPU, RAM)
- Check network speed
- Optimize composer install (use --no-dev)
- Enable composer cache
- Use local mirror for packages

### Permission Errors

**Symptoms:** "Permission denied" in deployment logs

**Solutions:**

- Check SSH key permissions
- Check file ownership on server
- Check directory permissions
- Run deployment as correct user
- Check sudo requirements
