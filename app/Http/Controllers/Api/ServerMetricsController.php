<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreServerMetricRequest;
use App\Models\Server;
use App\Models\ServerMetric;

class ServerMetricsController extends Controller
{
    public function index(Server $server)
    {
        $this->authorize('view', $server);

        $metrics = ServerMetric::where('server_id', $server->id)
            ->latest('recorded_at')
            ->take(100)
            ->get();

        return response()->json([
            'data' => $metrics,
        ]);
    }

    /**
     * Store server metrics.
     *
     * This endpoint is designed for automated metric collection from servers.
     * Authentication should be done via API tokens (Sanctum) with 'server:report-metrics' ability.
     *
     * @param StoreServerMetricRequest $request
     * @param Server $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreServerMetricRequest $request, Server $server)
    {
        // Verify the authenticated user has permission to update this server
        $this->authorize('update', $server);

        // Additional security: Check if using API token (recommended for automated metric collection)
        if ($request->user() && $request->user()->currentAccessToken()) {
            $token = $request->user()->currentAccessToken();
            // Verify token has the required ability for metric reporting
            if (! $token->can('server:report-metrics')) {
                return response()->json([
                    'message' => 'This API token does not have permission to report server metrics.',
                    'error' => 'insufficient_token_permissions',
                    'required_ability' => 'server:report-metrics',
                ], 403);
            }
        }

        $validated = $request->validated();

        $metric = ServerMetric::create([
            'server_id' => $server->id,
            'cpu_usage' => $validated['cpu_usage'],
            'memory_usage' => $validated['memory_usage'],
            'disk_usage' => $validated['disk_usage'],
            'network_in' => $validated['network_in'] ?? 0,
            'network_out' => $validated['network_out'] ?? 0,
            'load_average' => $validated['load_average'] ?? 0,
            'active_connections' => $validated['active_connections'] ?? 0,
            'recorded_at' => now(),
        ]);

        $server->update([
            'status' => 'online',
            'last_ping_at' => now(),
        ]);

        return response()->json([
            'message' => 'Metrics stored successfully',
            'data' => $metric,
        ], 201);
    }
}
