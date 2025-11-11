<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\Home\HomePublic;
use App\Livewire\Servers\ServerList;
use App\Livewire\Servers\ServerCreate;
use App\Livewire\Servers\ServerShow;
use App\Livewire\Projects\ProjectList;
use App\Livewire\Projects\ProjectCreate;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\Deployments\DeploymentList;
use App\Livewire\Deployments\DeploymentShow;
use App\Livewire\Analytics\AnalyticsDashboard;
use App\Livewire\Docker\DockerDashboard;

// Public Home Page - Shows all projects
Route::get('/', HomePublic::class)->name('home');

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Servers
    Route::get('/servers', ServerList::class)->name('servers.index');
    Route::get('/servers/create', ServerCreate::class)->name('servers.create');
    Route::get('/servers/{server}', ServerShow::class)->name('servers.show');

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

    // Users Management
    Route::get('/users', \App\Livewire\Users\UserList::class)->name('users.index');

    // Docker Management
    Route::get('/servers/{server}/docker', DockerDashboard::class)->name('docker.dashboard');
});

require __DIR__.'/auth.php';

