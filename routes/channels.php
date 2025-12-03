<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('server.{serverId}', function ($user, $serverId) {
    return $user->servers()->where('id', $serverId)->exists();
});

Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    return $user->projects()->where('id', $projectId)->exists();
});

Broadcast::channel('deployment.{deploymentId}', function ($user, $deploymentId) {
    return $user->deployments()->where('id', $deploymentId)->exists();
});

// Public channel for server metrics - no auth required for real-time updates
Broadcast::channel('server-metrics.{serverId}', function () {
    return true; // Allow all authenticated users to listen
});

