<?php

use App\Http\Controllers\Api\DeploymentWebhookController;
use App\Http\Controllers\Api\ServerMetricsController;
use App\Http\Controllers\Api\V1\DeploymentController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ServerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API Version 1 - Protected with authentication and rate limiting
Route::prefix('v1')->name('api.v1.')->middleware(['api.auth'])->group(function () {
    // Projects - Standard API rate limit (60 requests per minute for read operations)
    // With ability-based authorization for Sanctum tokens
    Route::get('projects', [ProjectController::class, 'index'])
        ->middleware(['throttle:60,1', 'abilities:projects,read'])
        ->name('projects.index');

    Route::post('projects', [ProjectController::class, 'store'])
        ->middleware(['throttle:60,1', 'abilities:projects,create'])
        ->name('projects.store');

    Route::get('projects/{project:slug}', [ProjectController::class, 'show'])
        ->middleware(['throttle:60,1', 'abilities:projects,read'])
        ->name('projects.show');

    Route::put('projects/{project:slug}', [ProjectController::class, 'update'])
        ->middleware(['throttle:60,1', 'abilities:projects,update'])
        ->name('projects.update');

    Route::patch('projects/{project:slug}', [ProjectController::class, 'update'])
        ->middleware(['throttle:60,1', 'abilities:projects,update']);

    Route::delete('projects/{project:slug}', [ProjectController::class, 'destroy'])
        ->middleware(['throttle:60,1', 'abilities:projects,delete'])
        ->name('projects.destroy');

    // Project Deployments - Restrictive rate limit (10 requests per minute for deploy operations)
    Route::post('projects/{project:slug}/deploy', [ProjectController::class, 'deploy'])
        ->middleware(['throttle:10,1', 'abilities:projects,update'])
        ->name('projects.deploy');

    // Servers - Server operations with ability-based authorization
    Route::get('servers', [ServerController::class, 'index'])
        ->middleware(['throttle:60,1', 'abilities:servers,read'])
        ->name('servers.index');

    Route::post('servers', [ServerController::class, 'store'])
        ->middleware(['throttle:60,1', 'abilities:servers,create'])
        ->name('servers.store');

    Route::get('servers/{server}', [ServerController::class, 'show'])
        ->middleware(['throttle:60,1', 'abilities:servers,read'])
        ->name('servers.show');

    Route::put('servers/{server}', [ServerController::class, 'update'])
        ->middleware(['throttle:60,1', 'abilities:servers,update'])
        ->name('servers.update');

    Route::patch('servers/{server}', [ServerController::class, 'update'])
        ->middleware(['throttle:60,1', 'abilities:servers,update']);

    Route::delete('servers/{server}', [ServerController::class, 'destroy'])
        ->middleware(['throttle:60,1', 'abilities:servers,delete'])
        ->name('servers.destroy');

    // Server Metrics - Read operations (60 requests per minute)
    Route::get('servers/{server}/metrics', [ServerController::class, 'metrics'])
        ->middleware(['throttle:60,1', 'abilities:servers,read'])
        ->name('servers.metrics');

    // Deployments - Read operations use standard API rate limit (60 requests per minute)
    Route::get('projects/{project:slug}/deployments', [DeploymentController::class, 'index'])
        ->middleware(['throttle:60,1', 'abilities:deployments,read'])
        ->name('projects.deployments.index');

    // Deployment Store - Restrictive rate limit (10 requests per minute)
    Route::post('projects/{project:slug}/deployments', [DeploymentController::class, 'store'])
        ->middleware(['throttle:10,1', 'abilities:deployments,create'])
        ->name('projects.deployments.store');

    // Deployment Show - Standard rate limit (60 requests per minute)
    Route::get('deployments/{deployment}', [DeploymentController::class, 'show'])
        ->middleware(['throttle:60,1', 'abilities:deployments,read'])
        ->name('deployments.show');

    // Deployment Rollback - Restrictive rate limit (10 requests per minute)
    Route::post('deployments/{deployment}/rollback', [DeploymentController::class, 'rollback'])
        ->middleware(['throttle:10,1', 'abilities:deployments,update'])
        ->name('deployments.rollback');
});

// Legacy routes (auth:sanctum) - Protected with API rate limiting
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Server Metrics - More restrictive rate limit for write operations (10 requests per minute)
    Route::post('/servers/{server}/metrics', [ServerMetricsController::class, 'store'])
        ->middleware('throttle:10,1')
        ->withoutMiddleware('throttle:60,1');

    // Server Metrics - Read operations (60 requests per minute)
    Route::get('/servers/{server}/metrics', [ServerMetricsController::class, 'index']);
});

// Public Webhook Endpoints - Protected with webhook-specific rate limiting
Route::middleware('throttle:webhooks')->group(function () {
    Route::post('/webhooks/deploy/{token}', [DeploymentWebhookController::class, 'handle']);
});
