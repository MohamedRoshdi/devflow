<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDeploymentRequest;
use App\Http\Resources\DeploymentCollection;
use App\Http\Resources\DeploymentResource;
use App\Jobs\DeployProjectJob;
use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeploymentController extends Controller
{
    /**
     * Display a listing of deployments for a project.
     */
    public function index(Request $request, Project $project): DeploymentCollection
    {
        $this->authorize('view', $project);

        // Validate filter and sort parameters
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,running,success,failed,rolled_back',
            'branch' => 'sometimes|string|max:100',
            'triggered_by' => 'sometimes|in:manual,webhook,scheduled,rollback',
            'sort_by' => 'sometimes|in:id,created_at,started_at,completed_at,status,branch',
            'sort_order' => 'sometimes|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = $project->deployments()
            ->with(['user', 'server']);

        // Filtering
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['branch'])) {
            $query->where('branch', $validated['branch']);
        }

        if (isset($validated['triggered_by'])) {
            $query->where('triggered_by', $validated['triggered_by']);
        }

        // Sorting
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $validated['per_page'] ?? 15;
        $deployments = $query->paginate($perPage);

        return new DeploymentCollection($deployments);
    }

    /**
     * Display the specified deployment.
     */
    public function show(Deployment $deployment): DeploymentResource
    {
        $this->authorize('view', $deployment->project);

        $deployment->load(['project', 'server', 'user', 'rollbackOf']);

        return new DeploymentResource($deployment);
    }

    /**
     * Create a new deployment for a project.
     */
    public function store(StoreDeploymentRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validated();

        // Check if there's already a running deployment
        $runningDeployment = $project->deployments()
            ->where('status', 'running')
            ->exists();

        if ($runningDeployment) {
            return response()->json([
                'message' => 'A deployment is already in progress for this project',
                'error' => 'deployment_in_progress',
            ], 409);
        }

        try {
            $deployment = DB::transaction(function () use ($project, $validated) {
                return $project->deployments()->create([
                    'user_id' => auth()->id(),
                    'server_id' => $project->server_id,
                    'branch' => $validated['branch'] ?? $project->branch,
                    'commit_hash' => $validated['commit_hash'] ?? 'HEAD',
                    'commit_message' => $validated['commit_message'] ?? null,
                    'triggered_by' => 'manual',
                    'status' => 'pending',
                    'started_at' => now(),
                    'environment_snapshot' => $validated['environment_snapshot'] ?? [
                        'php_version' => $project->php_version,
                        'node_version' => $project->node_version,
                        'framework' => $project->framework,
                    ],
                ]);
            });

            // Dispatch deployment job to queue
            DeployProjectJob::dispatch($deployment);

            $deployment->load(['project', 'server', 'user']);

            return response()->json([
                'message' => 'Deployment created successfully',
                'data' => new DeploymentResource($deployment),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create deployment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rollback to a specific deployment.
     */
    public function rollback(Deployment $deployment): JsonResponse
    {
        $this->authorize('update', $deployment->project);

        // Verify deployment was successful
        if ($deployment->status !== 'success') {
            return response()->json([
                'message' => 'Can only rollback to successful deployments',
                'error' => 'invalid_deployment_status',
                'current_status' => $deployment->status,
            ], 422);
        }

        // Check if there's already a running deployment
        $project = $deployment->project;
        if ($project === null) {
            return response()->json([
                'message' => 'Project not found for this deployment',
                'error' => 'project_not_found',
            ], 404);
        }

        $runningDeployment = $project->deployments()
            ->where('status', 'running')
            ->exists();

        if ($runningDeployment) {
            return response()->json([
                'message' => 'A deployment is already in progress for this project',
                'error' => 'deployment_in_progress',
            ], 409);
        }

        try {
            $rollbackDeployment = DB::transaction(function () use ($deployment, $project) {
                return $project->deployments()->create([
                    'user_id' => auth()->id(),
                    'server_id' => $deployment->server_id,
                    'branch' => $deployment->branch,
                    'commit_hash' => $deployment->commit_hash,
                    'commit_message' => "Rollback to: {$deployment->commit_message}",
                    'triggered_by' => 'rollback',
                    'status' => 'pending',
                    'started_at' => now(),
                    'rollback_deployment_id' => $deployment->id,
                    'environment_snapshot' => $deployment->environment_snapshot,
                ]);
            });

            // Dispatch rollback deployment job to queue
            DeployProjectJob::dispatch($rollbackDeployment);

            $rollbackDeployment->load(['project', 'server', 'user', 'rollbackOf']);

            return response()->json([
                'message' => 'Rollback deployment initiated successfully',
                'data' => new DeploymentResource($rollbackDeployment),
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to initiate rollback',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
