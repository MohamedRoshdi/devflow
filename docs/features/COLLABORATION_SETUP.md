# Team Collaboration Features - Quick Setup Guide

## Quick Start

### 1. Run Migrations

```bash
php artisan migrate
```

This creates:
- `deployment_approvals`
- `deployment_comments`
- `audit_logs`
- Updates `notification_channels` and `projects` tables

### 2. Seed Permissions

```bash
php artisan db:seed --class=CollaborationPermissionsSeeder
```

Creates permissions and roles:
- **Admin** - All permissions
- **Manager** - Approvals, notifications, logs
- **Developer** - Comments, request approvals
- **Viewer** - View logs, comments

### 3. Register Audit Service Provider

Edit `/bootstrap/providers.php` or `/config/app.php` (Laravel 11+):

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuditServiceProvider::class, // Add this
];
```

### 4. Assign Roles to Users

```bash
php artisan tinker
```

```php
$user = User::find(1);
$user->assignRole('admin');
```

### 5. Configure Queue (Required for Notifications)

```bash
# Start queue worker
php artisan queue:work

# Or use Supervisor for production
# See COLLABORATION_FEATURES.md for Supervisor config
```

## File Structure

### New Database Migrations
```
/database/migrations/
├── 2025_12_03_100001_create_deployment_approvals_table.php
├── 2025_12_03_100002_create_deployment_comments_table.php
├── 2025_12_03_100003_create_audit_logs_table.php
├── 2025_12_03_100004_update_notification_channels_table.php
└── 2025_12_03_100005_add_requires_approval_to_projects_table.php
```

### New Models
```
/app/Models/
├── DeploymentApproval.php
├── DeploymentComment.php
├── AuditLog.php
├── NotificationChannel.php (updated)
└── NotificationLog.php (updated)
```

### New Services
```
/app/Services/
├── DeploymentApprovalService.php
├── AuditService.php
└── NotificationService.php (enhanced)
```

### New Livewire Components
```
/app/Livewire/
├── Deployments/
│   ├── DeploymentApprovals.php
│   └── DeploymentComments.php
├── Admin/
│   └── AuditLogViewer.php
└── Notifications/
    └── NotificationChannelManager.php (enhanced)
```

### New Providers & Observers
```
/app/
├── Providers/
│   └── AuditServiceProvider.php
├── Observers/
│   └── AuditObserver.php
└── Notifications/
    ├── DeploymentApprovalRequested.php
    └── UserMentionedInComment.php
```

### Database Seeders
```
/database/seeders/
└── CollaborationPermissionsSeeder.php
```

## Usage Examples

### 1. Enable Approval for a Project

```php
$project = Project::find(1);
$project->update([
    'requires_approval' => true,
    'approval_settings' => [
        'environments' => ['production'],
        'branches' => ['main', 'master'],
    ]
]);
```

### 2. Request Deployment Approval

```php
use App\Services\DeploymentApprovalService;

$approvalService = app(DeploymentApprovalService::class);

if ($approvalService->requiresApproval($deployment)) {
    $approval = $approvalService->requestApproval($deployment, auth()->user());
}
```

### 3. Approve/Reject Deployment

```php
// Approve
$approvalService->approve($approval, auth()->user(), 'LGTM!');

// Reject
$approvalService->reject($approval, auth()->user(), 'Needs more testing');
```

### 4. Add Deployment Comment

```php
DeploymentComment::create([
    'deployment_id' => $deployment->id,
    'user_id' => auth()->id(),
    'content' => 'Great work! @JohnDoe please review the logs',
]);
```

### 5. Create Notification Channel

```php
NotificationChannel::create([
    'name' => 'Production Slack',
    'type' => 'slack',
    'project_id' => $project->id, // null for global
    'config' => [
        'webhook_url' => 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL',
    ],
    'events' => [
        'deployment.success',
        'deployment.failed',
        'deployment.approved',
    ],
    'enabled' => true,
]);
```

### 6. Query Audit Logs

```php
use App\Services\AuditService;

$auditService = app(AuditService::class);

// Get logs for a model
$logs = $auditService->getLogsForModel($deployment);

// Get logs by user
$logs = $auditService->getLogsByUser($user);

// Get logs with filters
$logs = $auditService->getLogsFiltered([
    'action_category' => 'deployment',
    'from_date' => now()->subDays(7),
    'to_date' => now(),
]);

// Export to CSV
$csv = $auditService->exportToCsv($filters);
```

### 7. Send Deployment Notifications

```php
use App\Services\NotificationService;

$notificationService = app(NotificationService::class);

// Automatically notify on deployment events
$notificationService->notifyDeploymentEvent($deployment, 'deployment.success');
```

## Frontend Routes

Add these routes to `/routes/web.php`:

```php
Route::middleware(['auth'])->group(function () {
    // Deployment Approvals
    Route::get('/approvals', function () {
        return view('pages.approvals');
    })->name('approvals.index');

    // Audit Logs
    Route::get('/audit-logs', function () {
        return view('pages.audit-logs');
    })->name('audit-logs.index')->can('view_audit_logs');

    // Notification Channels
    Route::get('/notifications/channels', function () {
        return view('pages.notification-channels');
    })->name('notifications.channels')->can('view_notification_channels');
});
```

## Blade Views (Create These)

### /resources/views/pages/approvals.blade.php
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Deployment Approvals</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:deployments.deployment-approvals />
        </div>
    </div>
</x-app-layout>
```

### /resources/views/pages/audit-logs.blade.php
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Audit Logs</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:admin.audit-log-viewer />
        </div>
    </div>
</x-app-layout>
```

## Environment Variables

Add to `.env` if using email notifications:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@devflow.pro"
MAIL_FROM_NAME="${APP_NAME}"
```

## Testing Checklist

- [ ] Migrations run successfully
- [ ] Permissions seeded
- [ ] AuditServiceProvider registered
- [ ] Queue worker running
- [ ] Can create approval request
- [ ] Can approve/reject deployment
- [ ] Notifications sent to approvers
- [ ] Comments work with mentions
- [ ] Audit logs recording events
- [ ] Can filter and export audit logs
- [ ] Notification channels working
- [ ] Test notifications successful
- [ ] Slack/Discord integration working

## Common Issues

### Issue: Notifications not sending
**Solution:** Ensure queue worker is running: `php artisan queue:work`

### Issue: Permission denied errors
**Solution:** Run seeder and assign roles: `php artisan db:seed --class=CollaborationPermissionsSeeder`

### Issue: Audit logs not recording
**Solution:** Verify AuditServiceProvider is registered in app config

### Issue: Slack/Discord notifications failing
**Solution:**
- Verify webhook URL is correct
- Test with the "Test" button in channel manager
- Check `notification_logs` table for error messages

## Next Steps

1. Create blade view files for the Livewire components
2. Add navigation links to your menu
3. Configure notification channels
4. Assign roles to your team members
5. Enable approvals for production projects
6. Review audit logs regularly

For detailed documentation, see `COLLABORATION_FEATURES.md`.
