<?php

use App\Http\Controllers\Api\DeploymentWebhookController;
use App\Http\Controllers\Api\ServerMetricsController;
use App\Http\Controllers\Api\V1\DeploymentController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ServerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API Version 1 - Protected with authentication and rate limiting
Route::prefix('v1')->name('api.v1.')->middleware(['api.auth', 'throttle:api'])->group(function () {
    // Projects - Standard API rate limit
    Route::apiResource('projects', ProjectController::class)->parameters(['projects' => 'project:slug']);

    // Project Deployments - Special deployment rate limit
    Route::post('projects/{project:slug}/deploy', [ProjectController::class, 'deploy'])
        ->middleware('throttle:deployments')
        ->withoutMiddleware('throttle:api')
        ->name('projects.deploy');

    // Servers - Server operations rate limit for intensive operations
    Route::apiResource('servers', ServerController::class);
    Route::get('servers/{server}/metrics', [ServerController::class, 'metrics'])->name('servers.metrics');

    // Deployments - Read operations use standard API rate limit, write operations use deployment-specific rate limit
    Route::get('projects/{project:slug}/deployments', [DeploymentController::class, 'index'])->name('projects.deployments.index');
    Route::post('projects/{project:slug}/deployments', [DeploymentController::class, 'store'])
        ->middleware('throttle:deployments')
        ->withoutMiddleware('throttle:api')
        ->name('projects.deployments.store');
    Route::get('deployments/{deployment}', [DeploymentController::class, 'show'])->name('deployments.show');
    Route::post('deployments/{deployment}/rollback', [DeploymentController::class, 'rollback'])
        ->middleware('throttle:deployments')
        ->withoutMiddleware('throttle:api')
        ->name('deployments.rollback');
});

// Legacy routes (auth:sanctum) - Protected with API rate limiting
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Server Metrics - More restrictive rate limit for write operations
    Route::post('/servers/{server}/metrics', [ServerMetricsController::class, 'store'])
        ->middleware('throttle:server-operations')
        ->withoutMiddleware('throttle:api');
    Route::get('/servers/{server}/metrics', [ServerMetricsController::class, 'index']);
});

// Public Webhook Endpoints - Protected with webhook-specific rate limiting
Route::middleware('throttle:webhooks')->group(function () {
    Route::post('/webhooks/deploy/{token}', [DeploymentWebhookController::class, 'handle']);
});
