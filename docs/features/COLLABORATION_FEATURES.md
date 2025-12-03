# Team Collaboration Features - DevFlow Pro

## Overview

This document describes the comprehensive team collaboration features implemented in DevFlow Pro, including deployment approvals, audit logging, deployment comments, and enhanced notification channels.

## Features Implemented

### 1. Deployment Approvals

Deployment approvals allow teams to enforce a review process before deployments go live, particularly for production environments.

#### Database Schema

- **Table:** `deployment_approvals`
- **Fields:**
  - `id` - Primary key
  - `deployment_id` - Foreign key to deployments
  - `requested_by` - User who requested approval
  - `approved_by` - User who approved/rejected (nullable)
  - `status` - enum: pending, approved, rejected
  - `notes` - Optional notes for approval/rejection
  - `requested_at` - When approval was requested
  - `responded_at` - When approval was processed

#### Models

- `/app/Models/DeploymentApproval.php`

#### Services

- `/app/Services/DeploymentApprovalService.php`
  - `requiresApproval()` - Check if deployment requires approval
  - `requestApproval()` - Create approval request
  - `approve()` - Approve deployment
  - `reject()` - Reject deployment
  - `getPendingApprovals()` - Get approvals for user
  - `getApprovalStats()` - Get approval statistics

#### Livewire Components

- `/app/Livewire/Deployments/DeploymentApprovals.php`
  - List pending approvals
  - Approve/Reject functionality
  - Filter by project/status
  - Real-time updates

#### Usage

```php
// Check if deployment requires approval
if ($approvalService->requiresApproval($deployment)) {
    // Request approval
    $approval = $approvalService->requestApproval($deployment, auth()->user());
}

// Approve deployment
$approvalService->approve($approval, auth()->user(), 'Looks good!');

// Reject deployment
$approvalService->reject($approval, auth()->user(), 'Needs more testing');
```

#### Project Configuration

Projects can be configured to require approval:

```php
$project->update([
    'requires_approval' => true,
    'approval_settings' => [
        'environments' => ['production', 'staging'],
        'branches' => ['main', 'master', 'production'],
    ]
]);
```

---

### 2. Deployment Comments

Real-time commenting system for deployments with user mentions support.

#### Database Schema

- **Table:** `deployment_comments`
- **Fields:**
  - `id` - Primary key
  - `deployment_id` - Foreign key to deployments
  - `user_id` - Comment author
  - `content` - Comment text
  - `mentions` - JSON array of mentioned user IDs
  - `created_at`, `updated_at`

#### Models

- `/app/Models/DeploymentComment.php`
  - `extractMentions()` - Extract @mentions from content
  - `getFormattedContentAttribute()` - Format content with clickable mentions

#### Livewire Components

- `/app/Livewire/Deployments/DeploymentComments.php`
  - Add, edit, delete comments
  - Real-time comment updates
  - User mentions with @ symbol
  - Notifications for mentions

#### Usage

```php
// Add comment
DeploymentComment::create([
    'deployment_id' => $deployment->id,
    'user_id' => auth()->id(),
    'content' => 'Deployment looks good! @JohnDoe please review',
]);

// Extract mentions
$comment->extractMentions(); // Returns array of user IDs
```

---

### 3. Audit Logging

Comprehensive audit trail for all system activities.

#### Database Schema

- **Table:** `audit_logs`
- **Fields:**
  - `id` - Primary key
  - `user_id` - User who performed action (nullable)
  - `action` - Action identifier (e.g., 'deployment.created')
  - `auditable_type` - Model class
  - `auditable_id` - Model ID
  - `old_values` - JSON of old values
  - `new_values` - JSON of new values
  - `ip_address` - User's IP address
  - `user_agent` - Browser user agent
  - `created_at` - Timestamp

#### Models

- `/app/Models/AuditLog.php`

#### Services

- `/app/Services/AuditService.php`
  - `log()` - Log an action
  - `getLogsForModel()` - Get logs for specific model
  - `getLogsByUser()` - Get logs by user
  - `getLogsByAction()` - Get logs by action type
  - `getLogsFiltered()` - Get logs with complex filters
  - `getActivityStats()` - Get statistics
  - `exportToCsv()` - Export logs to CSV

#### Observers

- `/app/Observers/AuditObserver.php` - Automatically logs model changes
- `/app/Providers/AuditServiceProvider.php` - Registers observers and auth events

#### Livewire Components

- `/app/Livewire/Admin/AuditLogViewer.php`
  - View and filter audit logs
  - Search by user, action, model, date
  - Expandable details with JSON diff view
  - Export to CSV
  - Activity statistics

#### Usage

```php
// Manual logging
$auditService->log(
    'deployment.started',
    $deployment,
    null,
    ['status' => 'running']
);

// Automatic logging via Observer
// Just update models normally, changes are logged automatically
$deployment->update(['status' => 'success']);

// Query logs
$logs = $auditService->getLogsFiltered([
    'user_id' => 1,
    'action_category' => 'deployment',
    'from_date' => now()->subDays(7),
]);
```

#### Automatic Logging

The system automatically logs:
- Model creation, updates, deletions
- User login/logout events
- Failed login attempts
- All sensitive data is redacted in logs

---

### 4. Enhanced Notification Channels

Advanced notification system with support for Slack, Discord, Email, and custom webhooks.

#### Database Schema

- **Table:** `notification_channels` (updated)
- **New Fields:**
  - `project_id` - Link channel to specific project (nullable for global)
  - `type` - Channel type (slack, discord, email, webhook)
  - `config` - Encrypted JSON configuration

#### Models

- `/app/Models/NotificationChannel.php` - Enhanced with project relationship
- `/app/Models/NotificationLog.php` - Log of sent notifications

#### Services

- `/app/Services/NotificationService.php` - Enhanced with:
  - `sendToSlack()` - Rich Slack messages using Block Kit
  - `sendToDiscord()` - Discord embeds with colors
  - `sendWebhook()` - Custom webhooks with HMAC signatures
  - `notifyDeploymentEvent()` - Automatic deployment notifications

#### Livewire Components

- `/app/Livewire/Notifications/NotificationChannelManager.php`
  - Create/edit/delete channels
  - Project-specific or global channels
  - Event selection
  - Test notification button
  - Enable/disable channels

#### Supported Events

```php
'deployment.started'
'deployment.success'
'deployment.failed'
'deployment.approved'
'deployment.rejected'
'deployment.rolled_back'
'server.down'
'server.recovered'
'health_check.failed'
'health_check.recovered'
'ssl.expiring_soon'
'ssl.expired'
'storage.warning'
'backup.completed'
'backup.failed'
```

#### Usage

```php
// Create notification channel
NotificationChannel::create([
    'name' => 'Production Alerts',
    'type' => 'slack',
    'project_id' => $project->id, // null for global
    'config' => ['webhook_url' => 'https://hooks.slack.com/...'],
    'events' => ['deployment.success', 'deployment.failed'],
    'enabled' => true,
]);

// Send deployment notification (automatic)
$notificationService->notifyDeploymentEvent($deployment, 'deployment.success');
```

#### Slack Block Kit Example

Messages are sent using Slack's Block Kit for rich formatting:
- Colored status indicators
- Structured fields (Branch, Status, Duration)
- Action buttons (optional)

#### Discord Embeds

Discord messages use embeds with:
- Color-coded based on status (Green=success, Red=failed)
- Embedded fields for details
- Timestamps

#### Webhooks with HMAC

Custom webhooks include HMAC-SHA256 signatures:
```
X-Webhook-Signature: <hmac_signature>
```

---

## Permissions

### Available Permissions

Created via `/database/seeders/CollaborationPermissionsSeeder.php`:

#### Deployment Approvals
- `approve_deployments` - Approve deployments for owned projects
- `approve_all_deployments` - Approve any deployment
- `request_deployment_approval` - Request approvals

#### Audit Logs
- `view_audit_logs` - View audit logs
- `view_all_audit_logs` - View all audit logs
- `export_audit_logs` - Export to CSV

#### Comments
- `add_deployment_comments` - Add comments
- `edit_own_comments` - Edit own comments
- `delete_own_comments` - Delete own comments
- `manage_all_comments` - Manage all comments

#### Notification Channels
- `manage_notification_channels` - Create/edit channels
- `view_notification_channels` - View channels
- `test_notification_channels` - Send test notifications

### Role Assignments

- **Admin**: All permissions
- **Manager**: Approve deployments, manage notifications, view logs
- **Developer**: Request approvals, comment, view logs
- **Viewer**: View logs, comment

---

## Installation & Setup

### 1. Run Migrations

```bash
php artisan migrate
```

This will create:
- `deployment_approvals` table
- `deployment_comments` table
- `audit_logs` table
- Update `notification_channels` table
- Update `projects` table with approval settings

### 2. Seed Permissions

```bash
php artisan db:seed --class=CollaborationPermissionsSeeder
```

### 3. Register Service Provider

Add to `/config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\AuditServiceProvider::class,
],
```

Or if using Laravel 11+ auto-discovery, it will be registered automatically.

### 4. Assign Roles to Users

```php
// Assign role to user
$user->assignRole('manager');

// Or assign specific permissions
$user->givePermissionTo('approve_deployments');

// Check permissions
if ($user->can('approve_deployments')) {
    // User can approve
}
```

---

## API Integration

### Webhook Payload Example

When sending to custom webhooks, the payload structure is:

```json
{
  "event": "deployment.success",
  "subject": "Deployment Success: My Project",
  "message": "✅ **Deployment Success**\n\n**Project:** My Project\n...",
  "deployment": {
    "id": 123,
    "project_id": 45,
    "project_name": "My Project",
    "status": "success",
    "branch": "main",
    "commit_hash": "abc123...",
    "commit_message": "Fix bug",
    "user_name": "John Doe",
    "started_at": "2025-12-03T10:00:00Z",
    "completed_at": "2025-12-03T10:05:00Z",
    "duration_seconds": 300
  }
}
```

### Webhook Signature Verification

Verify webhook signatures:

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$secret = 'your_webhook_secret';

$expected = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    die('Invalid signature');
}
```

---

## Frontend Integration

### Livewire Components Usage

#### Deployment Approvals

```html
<livewire:deployments.deployment-approvals />
```

Features:
- Filterable list of approvals
- Status badges
- Approve/Reject modals
- Real-time updates

#### Deployment Comments

```html
<livewire:deployments.deployment-comments :deployment="$deployment" />
```

Features:
- Chat-style interface
- User mentions with @
- Edit/delete own comments
- Real-time updates

#### Audit Log Viewer

```html
<livewire:admin.audit-log-viewer />
```

Features:
- Advanced filtering
- Expandable log details
- JSON diff viewer
- Export to CSV

#### Notification Channel Manager

```html
<livewire:notifications.notification-channel-manager />
```

Features:
- Channel CRUD operations
- Test notification button
- Event configuration
- Enable/disable toggle

---

## Queue Configuration

Notifications are queued for performance. Ensure queue worker is running:

```bash
php artisan queue:work
```

Or use Supervisor (recommended for production):

```ini
[program:devflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/devflow/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
```

---

## Testing

### Manual Testing

1. **Deployment Approvals:**
   - Create a project with `requires_approval = true`
   - Trigger a deployment
   - Check that approval is required
   - Approve/reject from approvals page

2. **Comments:**
   - Go to a deployment
   - Add a comment with @mention
   - Verify mentioned user receives notification
   - Edit/delete your comment

3. **Audit Logs:**
   - Perform various actions (create project, update server, etc.)
   - View audit log viewer
   - Filter by user, action, date
   - Export to CSV

4. **Notifications:**
   - Create a Slack/Discord channel
   - Configure webhook URL
   - Select events
   - Test notification
   - Trigger actual event (e.g., deployment)

### Unit Testing

```php
// Test deployment approval
public function test_deployment_approval_workflow()
{
    $deployment = Deployment::factory()->create();
    $user = User::factory()->create();

    $approval = $this->approvalService->requestApproval($deployment, $user);

    $this->assertEquals('pending', $approval->status);

    $approver = User::factory()->create();
    $approver->givePermissionTo('approve_deployments');

    $this->approvalService->approve($approval, $approver);

    $approval->refresh();
    $this->assertEquals('approved', $approval->status);
}
```

---

## Troubleshooting

### Notifications Not Sending

1. Check queue is running: `php artisan queue:work`
2. Check notification channel is enabled
3. Check events are configured correctly
4. Check webhook URL is accessible
5. View logs in `notification_logs` table

### Audit Logs Not Recording

1. Ensure `AuditServiceProvider` is registered
2. Check observers are attached to models
3. Verify database permissions
4. Check for exceptions in logs

### Approvals Not Working

1. Verify user has `approve_deployments` permission
2. Check project has `requires_approval = true`
3. Verify approval settings are configured
4. Check deployment status

---

## Security Considerations

1. **Sensitive Data Redaction**: Passwords, tokens, and secrets are automatically redacted from audit logs
2. **Webhook Signatures**: Use HMAC signatures to verify webhook authenticity
3. **Encrypted Config**: Notification channel configurations are encrypted
4. **Permission Checks**: All actions check user permissions
5. **IP Tracking**: Audit logs include IP addresses for security analysis

---

## Future Enhancements

Potential improvements:
- Multi-level approval workflows
- Approval groups/teams
- Comment reactions
- Rich text comments with Markdown
- Inline code review comments
- Deployment change previews
- Microsoft Teams integration
- PagerDuty integration
- Approval templates

---

## Support

For issues or questions:
- Check application logs: `storage/logs/laravel.log`
- Review notification logs: `notification_logs` table
- Check audit logs for troubleshooting
- Enable debug mode for detailed errors

---

## Changelog

### Version 1.0.0 (2025-12-03)
- Initial implementation
- Deployment approvals
- Deployment comments with mentions
- Comprehensive audit logging
- Enhanced notification channels
- Slack Block Kit support
- Discord embeds
- Webhook HMAC signatures
- Permission system integration
