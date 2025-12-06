<?php

use App\Http\Controllers\Api\DeploymentWebhookController;
use App\Http\Controllers\Api\ServerMetricsController;
use App\Http\Controllers\Api\V1\DeploymentController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ServerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API Version 1
Route::prefix('v1')->name('api.v1.')->middleware('api.auth')->group(function () {
    // Projects
    Route::apiResource('projects', ProjectController::class)->parameters(['projects' => 'project:slug']);
    Route::post('projects/{project:slug}/deploy', [ProjectController::class, 'deploy'])->name('projects.deploy');

    // Servers
    Route::apiResource('servers', ServerController::class);
    Route::get('servers/{server}/metrics', [ServerController::class, 'metrics'])->name('servers.metrics');

    // Deployments
    Route::get('projects/{project:slug}/deployments', [DeploymentController::class, 'index'])->name('projects.deployments.index');
    Route::post('projects/{project:slug}/deployments', [DeploymentController::class, 'store'])->name('projects.deployments.store');
    Route::get('deployments/{deployment}', [DeploymentController::class, 'show'])->name('deployments.show');
    Route::post('deployments/{deployment}/rollback', [DeploymentController::class, 'rollback'])->name('deployments.rollback');
});

// Legacy routes (auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Server Metrics
    Route::post('/servers/{server}/metrics', [ServerMetricsController::class, 'store']);
    Route::get('/servers/{server}/metrics', [ServerMetricsController::class, 'index']);
});

// Public Webhook Endpoints
Route::post('/webhooks/deploy/{token}', [DeploymentWebhookController::class, 'handle']);
