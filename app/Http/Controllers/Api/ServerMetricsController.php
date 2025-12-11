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

    public function store(StoreServerMetricRequest $request, Server $server)
    {
        $this->authorize('update', $server);

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
