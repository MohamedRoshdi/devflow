<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerMetric;
use Illuminate\Http\Request;

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

    public function store(Request $request, Server $server)
    {
        $this->authorize('update', $server);

        $validated = $request->validate([
            'cpu_usage' => 'required|numeric|min:0|max:100',
            'memory_usage' => 'required|numeric|min:0|max:100',
            'disk_usage' => 'required|numeric|min:0|max:100',
            'network_in' => 'nullable|integer|min:0',
            'network_out' => 'nullable|integer|min:0',
            'load_average' => 'nullable|numeric|min:0',
            'active_connections' => 'nullable|integer|min:0',
        ]);

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
