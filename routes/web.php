<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\Home\HomePublic;
use App\Livewire\Servers\ServerList;
use App\Livewire\Servers\ServerCreate;
use App\Livewire\Servers\ServerShow;
use App\Livewire\Servers\ServerMetricsDashboard;
use App\Livewire\Servers\ServerTagManager;
use App\Livewire\Projects\ProjectList;
use App\Livewire\Projects\ProjectCreate;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\Deployments\DeploymentList;
use App\Livewire\Deployments\DeploymentShow;
use App\Livewire\Analytics\AnalyticsDashboard;
use App\Livewire\Docker\DockerDashboard;
use App\Livewire\Admin\SystemAdmin;
use App\Livewire\Dashboard\HealthDashboard;

// Public Home Page - Shows all projects
Route::get('/', HomePublic::class)->name('home');

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Servers
    Route::get('/servers', ServerList::class)->name('servers.index');
    Route::get('/servers/create', ServerCreate::class)->name('servers.create');
    Route::get('/servers/tags', ServerTagManager::class)->name('servers.tags');
    Route::get('/servers/{server}', ServerShow::class)->name('servers.show');
    Route::get('/servers/{server}/metrics', ServerMetricsDashboard::class)->name('servers.metrics');

    // Projects
    Route::get('/projects', ProjectList::class)->name('projects.index');
    Route::get('/projects/create', ProjectCreate::class)->name('projects.create');
    Route::get('/projects/{project}', ProjectShow::class)->name('projects.show');
    Route::get('/projects/{project}/edit', \App\Livewire\Projects\ProjectEdit::class)->name('projects.edit');

    // Deployments
    Route::get('/deployments', DeploymentList::class)->name('deployments.index');
    Route::get('/deployments/{deployment}', DeploymentShow::class)->name('deployments.show');

    // Analytics
    Route::get('/analytics', AnalyticsDashboard::class)->name('analytics');

    // Health Dashboard
    Route::get('/health', HealthDashboard::class)->name('health.dashboard');

    // Users Management
    Route::get('/users', \App\Livewire\Users\UserList::class)->name('users.index');

    // Docker Management
    Route::get('/servers/{server}/docker', DockerDashboard::class)->name('docker.dashboard');

    // System Administration
    Route::get('/admin/system', SystemAdmin::class)->name('admin.system');

    // ============ ADVANCED FEATURES ============

    // Kubernetes Management
    Route::get('/kubernetes', \App\Livewire\Kubernetes\ClusterManager::class)->name('kubernetes.index');

    // CI/CD Pipelines
    Route::get('/pipelines', \App\Livewire\CICD\PipelineBuilder::class)->name('pipelines.index');

    // Deployment Scripts
    Route::get('/scripts', \App\Livewire\Scripts\ScriptManager::class)->name('scripts.index');

    // Notification Channels
    Route::get('/notifications', \App\Livewire\Notifications\NotificationChannelManager::class)->name('notifications.index');

    // Multi-Tenant Management
    Route::get('/tenants', \App\Livewire\MultiTenant\TenantManager::class)->name('tenants.index');
    Route::get('/tenants/{project}', \App\Livewire\MultiTenant\TenantManager::class)->name('tenants.project');

    // SSH Key Management
    Route::get('/settings/ssh-keys', \App\Livewire\Settings\SSHKeyManager::class)->name('settings.ssh-keys');
});

require __DIR__.'/auth.php';

