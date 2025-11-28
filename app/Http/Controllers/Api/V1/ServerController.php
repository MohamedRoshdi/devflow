<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreServerRequest;
use App\Http\Requests\Api\UpdateServerRequest;
use App\Http\Resources\ServerResource;
use App\Http\Resources\ServerCollection;
use App\Http\Resources\ServerMetricResource;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    /**
     * Display a listing of servers.
     */
    public function index(Request $request): ServerCollection
    {
        $query = Server::query()
            ->where('user_id', auth()->id());

        // Filtering
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('docker_installed')) {
            $query->where('docker_installed', $request->boolean('docker_installed'));
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('hostname', 'like', "%{$request->search}%")
                  ->orWhere('ip_address', 'like', "%{$request->search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'updated_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Load project count
        $query->withCount('projects');

        $perPage = min($request->get('per_page', 15), 100);
        $servers = $query->paginate($perPage);

        return new ServerCollection($servers);
    }

    /**
     * Store a newly created server.
     */
    public function store(StoreServerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $data['status'] = 'offline'; // Default status until first ping

        $server = Server::create($data);

        return response()->json([
            'message' => 'Server created successfully',
            'data' => new ServerResource($server),
        ], 201);
    }

    /**
     * Display the specified server.
     */
    public function show(Server $server): ServerResource
    {
        $this->authorize('view', $server);

        $server->load(['projects']);
        $server->loadCount('projects');

        return new ServerResource($server);
    }

    /**
     * Update the specified server.
     */
    public function update(UpdateServerRequest $request, Server $server): JsonResponse
    {
        $this->authorize('update', $server);

        $data = $request->validated();
        $server->update($data);

        return response()->json([
            'message' => 'Server updated successfully',
            'data' => new ServerResource($server),
        ]);
    }

    /**
     * Remove the specified server.
     */
    public function destroy(Server $server): JsonResponse
    {
        $this->authorize('delete', $server);

        // Check if server has active projects
        if ($server->projects()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete server with active projects',
                'error' => 'server_has_projects',
                'projects_count' => $server->projects()->count(),
            ], 409);
        }

        $server->delete();

        return response()->json([
            'message' => 'Server deleted successfully',
        ], 204);
    }

    /**
     * Get current server metrics.
     */
    public function metrics(Request $request, Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        // Get metrics based on time range
        $range = $request->get('range', '1h'); // 1h, 24h, 7d, 30d
        $limit = match($range) {
            '1h' => 60,
            '24h' => 288,
            '7d' => 168,
            '30d' => 720,
            default => 60,
        };

        $metrics = $server->metrics()
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        // Calculate aggregates
        $aggregates = [
            'avg_cpu' => $metrics->avg('cpu_usage'),
            'avg_memory' => $metrics->avg('memory_usage'),
            'avg_disk' => $metrics->avg('disk_usage'),
            'max_cpu' => $metrics->max('cpu_usage'),
            'max_memory' => $metrics->max('memory_usage'),
            'max_disk' => $metrics->max('disk_usage'),
        ];

        return response()->json([
            'data' => [
                'metrics' => ServerMetricResource::collection($metrics),
                'aggregates' => $aggregates,
                'range' => $range,
                'count' => $metrics->count(),
            ],
        ]);
    }
}
