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
    Route::apiResource('projects', ProjectController::class)
        ->parameters(['projects' => 'project:slug'])
        ->middleware('throttle:60,1');

    // Project Deployments - Restrictive rate limit (10 requests per minute for deploy operations)
    Route::post('projects/{project:slug}/deploy', [ProjectController::class, 'deploy'])
        ->middleware('throttle:10,1')
        ->name('projects.deploy');

    // Servers - Server operations rate limit (60 requests per minute for read operations)
    Route::apiResource('servers', ServerController::class)
        ->middleware('throttle:60,1');

    // Server Metrics - Read operations (60 requests per minute)
    Route::get('servers/{server}/metrics', [ServerController::class, 'metrics'])
        ->middleware('throttle:60,1')
        ->name('servers.metrics');

    // Deployments - Read operations use standard API rate limit (60 requests per minute)
    Route::get('projects/{project:slug}/deployments', [DeploymentController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('projects.deployments.index');

    // Deployment Store - Restrictive rate limit (10 requests per minute)
    Route::post('projects/{project:slug}/deployments', [DeploymentController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('projects.deployments.store');

    // Deployment Show - Standard rate limit (60 requests per minute)
    Route::get('deployments/{deployment}', [DeploymentController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('deployments.show');

    // Deployment Rollback - Restrictive rate limit (10 requests per minute)
    Route::post('deployments/{deployment}/rollback', [DeploymentController::class, 'rollback'])
        ->middleware('throttle:10,1')
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
