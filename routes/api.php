<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ServerMetricsController;
use App\Http\Controllers\Api\DeploymentWebhookController;

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

