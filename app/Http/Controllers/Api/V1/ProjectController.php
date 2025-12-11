<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProjectRequest;
use App\Http\Requests\Api\UpdateProjectRequest;
use App\Http\Resources\ProjectCollection;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request): ProjectCollection
    {
        $query = Project::query()
            ->where('user_id', auth()->id())
            ->with(['server', 'domains']);

        // Filtering
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('framework')) {
            $query->where('framework', $request->framework);
        }

        if ($request->has('server_id')) {
            $query->where('server_id', $request->server_id);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('slug', 'like', "%{$request->search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'updated_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Load deployment count
        $query->withCount('deployments');

        $perPage = min($request->input('per_page', 15), 100);
        $projects = $query->paginate($perPage);

        return new ProjectCollection($projects);
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // Generate webhook secret if webhook is enabled
        if ($data['webhook_enabled'] ?? false) {
            $project = new Project;
            $data['webhook_secret'] = $project->generateWebhookSecret();
        }

        $project = Project::create($data);
        $project->load(['server', 'domains']);

        return response()->json([
            'message' => 'Project created successfully',
            'data' => new ProjectResource($project),
        ], 201);
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        $project->load(['server', 'domains', 'latestDeployment']);
        $project->loadCount('deployments');

        return new ProjectResource($project);
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validated();

        // Regenerate webhook secret if changing webhook status to enabled
        if (isset($data['webhook_enabled']) && $data['webhook_enabled'] && ! $project->webhook_enabled) {
            $data['webhook_secret'] = $project->generateWebhookSecret();
        }

        $project->update($data);
        $project->load(['server', 'domains']);

        return response()->json([
            'message' => 'Project updated successfully',
            'data' => new ProjectResource($project),
        ]);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully',
        ], 204);
    }

    /**
     * Deploy the specified project.
     */
    public function deploy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

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
            $deployment = DB::transaction(function () use ($project, $request) {
                return $project->deployments()->create([
                    'user_id' => auth()->id(),
                    'server_id' => $project->server_id,
                    'branch' => $request->input('branch', $project->branch),
                    'commit_hash' => $request->input('commit_hash', 'HEAD'),
                    'commit_message' => $request->input('commit_message'),
                    'triggered_by' => 'manual',
                    'status' => 'pending',
                    'started_at' => now(),
                ]);
            });

            // Dispatch deployment job (you'll need to create this)
            // DeployProjectJob::dispatch($deployment);

            return response()->json([
                'message' => 'Deployment initiated successfully',
                'data' => [
                    'deployment_id' => $deployment->id,
                    'status' => $deployment->status,
                ],
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to initiate deployment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
