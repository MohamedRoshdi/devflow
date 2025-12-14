<?php

use App\Http\Controllers\DocsController;
use App\Http\Controllers\GitHubAuthController;
use App\Http\Controllers\TeamInvitationController;
use App\Livewire\Admin\ProjectTemplateManager;
use App\Livewire\Admin\SystemAdmin;
use App\Livewire\Analytics\AnalyticsDashboard;
use App\Livewire\Dashboard;
use App\Livewire\Dashboard\HealthDashboard;
use App\Livewire\Deployments\DeploymentList;
use App\Livewire\Deployments\DeploymentShow;
use App\Livewire\Docker\DockerDashboard;
use App\Livewire\Home\HomePublic;
use App\Livewire\Projects\ProjectCreate;
use App\Livewire\Projects\ProjectList;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\Servers\ProvisioningLogs;
use App\Livewire\Servers\ServerCreate;
use App\Livewire\Servers\ServerEdit;
use App\Livewire\Servers\ServerList;
use App\Livewire\Servers\ServerMetricsDashboard;
use App\Livewire\Servers\ServerShow;
use App\Livewire\Servers\ServerTagManager;
use App\Livewire\Settings\GitHubSettings;
use App\Livewire\Teams\TeamList;
use App\Livewire\Teams\TeamSettings;
use Illuminate\Support\Facades\Route;

// Public Pages - Rate limited to prevent abuse
Route::middleware('throttle:public')->group(function () {
    // Public Home Page - Shows all projects
    Route::get('/', HomePublic::class)->name('home');

    // Public Project Detail Page
    Route::get('/project/{slug}', \App\Livewire\Home\ProjectDetail::class)->name('project.public');

    // Team Invitation (requires auth to accept)
    Route::get('/invitations/{token}', [TeamInvitationController::class, 'show'])->name('invitations.show');
});

Route::post('/invitations/{token}/accept', [TeamInvitationController::class, 'accept'])
    ->name('invitations.accept')
    ->middleware(['auth', 'throttle:web']);

Route::middleware(['auth', 'throttle:web'])->group(function () {
    // Dashboard
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Servers
    Route::get('/servers', ServerList::class)->name('servers.index');
    Route::get('/servers/create', ServerCreate::class)->name('servers.create');
    Route::get('/servers/tags', ServerTagManager::class)->name('servers.tags');
    Route::get('/servers/{server}', ServerShow::class)->name('servers.show');
    Route::get('/servers/{server}/edit', ServerEdit::class)->name('servers.edit');
    Route::get('/servers/{server}/metrics', ServerMetricsDashboard::class)->name('servers.metrics');
    Route::get('/servers/{server}/ssl', \App\Livewire\Servers\SSLManager::class)->name('servers.ssl');
    Route::get('/servers/{server}/alerts', \App\Livewire\Servers\ResourceAlertManager::class)->name('servers.alerts');
    Route::get('/servers/{server}/backups', \App\Livewire\Servers\ServerBackupManager::class)->name('servers.backups');
    Route::get('/servers/{server}/provisioning', ProvisioningLogs::class)->name('servers.provisioning');
    Route::get('/servers/{server}/terminal', \App\Livewire\Servers\SSHTerminal::class)->name('servers.terminal');

    // SSH Terminal (Standalone - Simple)
    Route::get('/terminal', \App\Livewire\Servers\SSHTerminalSelector::class)->name('terminal');

    // Web Terminal (xterm.js - Professional)
    Route::get('/web-terminal', \App\Livewire\Servers\WebTerminalSelector::class)->name('web-terminal');
    Route::get('/servers/{server}/web-terminal', \App\Livewire\Servers\WebTerminal::class)->name('servers.web-terminal');

    // Server Security Management
    Route::get('/servers/{server}/security', \App\Livewire\Servers\Security\ServerSecurityDashboard::class)->name('servers.security');
    Route::get('/servers/{server}/security/firewall', \App\Livewire\Servers\Security\FirewallManager::class)->name('servers.security.firewall');
    Route::get('/servers/{server}/security/fail2ban', \App\Livewire\Servers\Security\Fail2banManager::class)->name('servers.security.fail2ban');
    Route::get('/servers/{server}/security/ssh', \App\Livewire\Servers\Security\SSHSecurityManager::class)->name('servers.security.ssh');
    Route::get('/servers/{server}/security/scan', \App\Livewire\Servers\Security\SecurityScanDashboard::class)->name('servers.security.scan');

    // Projects
    Route::get('/projects', ProjectList::class)->name('projects.index');
    Route::get('/projects/create', ProjectCreate::class)->name('projects.create');
    Route::get('/projects/devflow', \App\Livewire\Projects\DevFlowSelfManagement::class)->name('projects.devflow');
    Route::get('/projects/devflow/logs/{filename}', function (string $filename) {
        // Security: Only allow laravel log files
        if (!str_starts_with($filename, 'laravel') || !str_ends_with($filename, '.log')) {
            abort(403, 'Invalid log file');
        }

        $logPath = storage_path('logs/' . basename($filename));

        if (!file_exists($logPath)) {
            abort(404, 'Log file not found');
        }

        return response()->download($logPath, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    })->name('projects.devflow.logs.download');
    Route::get('/projects/{project}', ProjectShow::class)->name('projects.show');
    Route::get('/projects/{project}/edit', \App\Livewire\Projects\ProjectEdit::class)->name('projects.edit');
    Route::get('/projects/{project}/configuration', \App\Livewire\Projects\ProjectConfiguration::class)->name('projects.configuration');
    Route::get('/projects/{project}/pipeline', \App\Livewire\Projects\PipelineSettings::class)->name('projects.pipeline');
    Route::get('/projects/{project}/backups', \App\Livewire\Projects\DatabaseBackupManager::class)->name('projects.backups');

    // Project Domains
    Route::post('/projects/{project}/domains', [\App\Http\Controllers\DomainController::class, 'store'])->name('projects.domains.store');
    Route::put('/projects/{project}/domains/{domain}', [\App\Http\Controllers\DomainController::class, 'update'])->name('projects.domains.update');
    Route::delete('/projects/{project}/domains/{domain}', [\App\Http\Controllers\DomainController::class, 'destroy'])->name('projects.domains.destroy');

    // Deployments
    Route::get('/deployments', DeploymentList::class)->name('deployments.index');
    Route::get('/deployments/approvals', \App\Livewire\Deployments\DeploymentApprovals::class)->name('deployments.approvals');
    Route::get('/deployments/scheduled', \App\Livewire\Deployments\ScheduledDeployments::class)->name('deployments.scheduled');
    Route::get('/deployments/{deployment}', DeploymentShow::class)->name('deployments.show');

    // Analytics
    Route::get('/analytics', AnalyticsDashboard::class)->name('analytics');

    // Health Dashboard
    Route::get('/health', HealthDashboard::class)->name('health.dashboard');

    // Users Management
    Route::get('/users', \App\Livewire\Users\UserList::class)->name('users.index');

    // Team Management
    Route::get('/teams', TeamList::class)->name('teams.index');
    Route::get('/teams/{team}/settings', TeamSettings::class)->name('teams.settings');

    // Docker Management
    Route::get('/servers/{server}/docker', DockerDashboard::class)->name('docker.dashboard');

    // System Administration
    Route::get('/admin/system', SystemAdmin::class)->name('admin.system');
    Route::get('/admin/audit-logs', \App\Livewire\Admin\AuditLogViewer::class)->name('admin.audit-logs');
    Route::get('/admin/help-content', \App\Livewire\Admin\HelpContentManager::class)->name('admin.help-content');
    Route::get('/admin/templates', ProjectTemplateManager::class)->name('admin.templates');

    // ============ ADVANCED FEATURES ============

    // Kubernetes Management
    Route::get('/kubernetes', \App\Livewire\Kubernetes\ClusterManager::class)->name('kubernetes.index');

    // CI/CD Pipelines
    Route::get('/pipelines', fn() => \Livewire\Livewire::mount(\App\Livewire\CICD\PipelineBuilder::class))->name('pipelines.index');
    Route::get('/projects/{project}/pipeline', \App\Livewire\CICD\PipelineBuilder::class)->name('projects.pipeline');

    // Deployment Scripts
    Route::get('/scripts', \App\Livewire\Scripts\ScriptManager::class)->name('scripts.index');

    // Notification Channels
    Route::get('/notifications', \App\Livewire\Notifications\NotificationChannelManager::class)->name('notifications.index');

    // Multi-Tenant Management
    Route::get('/tenants', \App\Livewire\MultiTenant\TenantManager::class)->name('tenants.index');
    Route::get('/tenants/{project}', \App\Livewire\MultiTenant\TenantManager::class)->name('tenants.project');

    // Settings
    Route::get('/settings/ssh-keys', \App\Livewire\Settings\SSHKeyManager::class)->name('settings.ssh-keys');
    Route::get('/settings/health-checks', \App\Livewire\Settings\HealthCheckManager::class)->name('settings.health-checks');
    Route::get('/settings/github', GitHubSettings::class)->name('settings.github');
    Route::get('/settings/api-tokens', \App\Livewire\Settings\ApiTokenManager::class)->name('settings.api-tokens');
    Route::get('/settings/queue-monitor', \App\Livewire\Settings\QueueMonitor::class)->name('settings.queue-monitor');
    Route::get('/settings/preferences', \App\Livewire\Settings\DefaultSetupPreferences::class)->name('settings.preferences');
    Route::get('/settings/storage', \App\Livewire\Settings\StorageSettings::class)->name('settings.storage');
    Route::get('/settings/roles-permissions', \App\Livewire\Settings\RolesPermissions::class)->name('settings.roles-permissions');

    // Documentation
    Route::get('/docs/api', \App\Livewire\Docs\ApiDocumentation::class)->name('docs.api');
    Route::get('/docs/features', \App\Livewire\Docs\FeaturesGuide::class)->name('docs.features');

    // Inline Help Documentation
    Route::get('/docs/search', [App\Http\Controllers\DocsController::class, 'search'])->name('docs.search');
    Route::get('/docs/{category?}/{page?}', [App\Http\Controllers\DocsController::class, 'show'])->name('docs.show');

    // GitHub OAuth
    Route::get('/auth/github', [GitHubAuthController::class, 'redirect'])->name('github.redirect');
    Route::get('/auth/github/callback', [GitHubAuthController::class, 'callback'])->name('github.callback');
    Route::get('/auth/github/disconnect', [GitHubAuthController::class, 'disconnect'])->name('github.disconnect');

    // Log Aggregation
    Route::get('/logs', \App\Livewire\Logs\LogViewer::class)->name('logs.index');
    Route::get('/logs/notifications', \App\Livewire\Logs\NotificationLogs::class)->name('logs.notifications');
    Route::get('/logs/webhooks', \App\Livewire\Logs\WebhookLogs::class)->name('logs.webhooks');
    Route::get('/logs/security', \App\Livewire\Logs\SecurityAuditLog::class)->name('logs.security');
    Route::get('/servers/{server}/log-sources', \App\Livewire\Logs\LogSourceManager::class)->name('servers.log-sources');

    // System Status
    Route::get('/settings/system-status', \App\Livewire\Settings\SystemStatus::class)->name('settings.system-status');

    // System Settings (Admin)
    Route::get('/settings/system', \App\Livewire\Settings\SystemSettings::class)->name('settings.system');
});

// Webhook endpoints (public, no auth required) - Protected with webhook rate limiting
Route::middleware('throttle:webhooks')->group(function () {
    Route::post('/webhooks/github/{secret}', [App\Http\Controllers\WebhookController::class, 'handleGitHub'])->name('webhooks.github');
    Route::post('/webhooks/gitlab/{secret}', [App\Http\Controllers\WebhookController::class, 'handleGitLab'])->name('webhooks.gitlab');
});

require __DIR__.'/auth.php';
