<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Project;
use App\Services\ProjectHealthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Async health check job for projects.
 *
 * Performs HTTP health checks in the background to avoid blocking
 * the main request. Results are cached for quick retrieval.
 */
class CheckProjectHealthJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $projectId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ProjectHealthService $healthService): void
    {
        $project = Project::with(['server', 'domains', 'deployments' => function ($query) {
            $query->latest()->limit(1);
        }])->find($this->projectId);

        if (!$project) {
            Log::warning("CheckProjectHealthJob: Project {$this->projectId} not found");
            return;
        }

        try {
            $healthData = $healthService->checkProject($project);

            // Cache the result for 5 minutes
            Cache::put(
                "project_health_{$this->projectId}",
                $healthData,
                now()->addMinutes(5)
            );

            Log::debug("CheckProjectHealthJob: Health check completed for project {$project->name}", [
                'project_id' => $this->projectId,
                'health_score' => $healthData['health_score'] ?? 0,
                'status' => $healthData['status'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            Log::error("CheckProjectHealthJob: Health check failed for project {$this->projectId}", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Dispatch health checks for all projects asynchronously.
     */
    public static function dispatchForAllProjects(): void
    {
        Project::where('status', 'running')
            ->select('id')
            ->chunk(50, function ($projects) {
                foreach ($projects as $project) {
                    self::dispatch($project->id)->onQueue('health-checks');
                }
            });
    }

    /**
     * Dispatch health check for a single project asynchronously.
     */
    public static function dispatchForProject(Project $project): void
    {
        self::dispatch($project->id)->onQueue('health-checks');
    }
}
