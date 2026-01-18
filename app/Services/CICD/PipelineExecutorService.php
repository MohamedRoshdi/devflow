<?php

declare(strict_types=1);

namespace App\Services\CICD;

use App\Models\Pipeline;
use App\Models\PipelineRun;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Handles CI/CD pipeline execution operations.
 *
 * Responsible for triggering pipelines on various providers
 * (GitHub Actions, GitLab CI, Jenkins, custom) and managing
 * pipeline run execution.
 */
class PipelineExecutorService
{
    /**
     * Execute pipeline run
     */
    public function executePipeline(Pipeline $pipeline, string $trigger = 'manual'): PipelineRun
    {
        $run = PipelineRun::create([
            'pipeline_id' => $pipeline->id,
            'status' => 'queued',
            'trigger' => $trigger,
            'commit_hash' => $this->getCurrentCommitHash($pipeline->project),
            'branch' => $pipeline->project->branch,
            'started_at' => now(),
        ]);

        // Execute pipeline based on provider
        switch ($pipeline->provider) {
            case 'github':
                $this->triggerGitHubActions($pipeline, $run);
                break;

            case 'gitlab':
                $this->triggerGitLabPipeline($pipeline, $run);
                break;

            case 'jenkins':
                $this->triggerJenkinsBuild($pipeline, $run);
                break;

            default:
                $this->executeCustomPipeline($pipeline, $run);
        }

        return $run;
    }

    /**
     * Trigger GitHub Actions workflow
     */
    public function triggerGitHubActions(Pipeline $pipeline, PipelineRun $run): void
    {
        $project = $pipeline->project;
        $repoOwner = $this->extractGitHubOwner($project->repository_url);
        $repoName = $this->extractGitHubRepo($project->repository_url);

        $response = Http::withToken(config('services.github.token'))
            ->post("https://api.github.com/repos/{$repoOwner}/{$repoName}/actions/workflows/devflow.yml/dispatches", [
                'ref' => $project->branch,
                'inputs' => [
                    'pipeline_run_id' => $run->id,
                ],
            ]);

        if ($response->successful()) {
            $run->update(['status' => 'running']);
        } else {
            $run->update([
                'status' => 'failed',
                'error' => $response->body(),
            ]);
        }
    }

    /**
     * Trigger GitLab Pipeline
     */
    public function triggerGitLabPipeline(Pipeline $pipeline, PipelineRun $run): void
    {
        $project = $pipeline->project;
        $gitlabProjectId = $this->extractGitLabProjectId($project->repository_url);
        $gitlabToken = config('services.gitlab.token');
        $gitlabUrl = config('services.gitlab.url', 'https://gitlab.com');

        if (empty($gitlabToken)) {
            $run->update([
                'status' => 'failed',
                'error' => 'GitLab API token not configured. Set GITLAB_TOKEN in .env',
            ]);
            Log::error('GitLab pipeline trigger failed: Missing API token', [
                'pipeline_id' => $pipeline->id,
                'project_id' => $project->id,
            ]);

            return;
        }

        if (empty($gitlabProjectId)) {
            $run->update([
                'status' => 'failed',
                'error' => 'Could not extract GitLab project ID from repository URL',
            ]);

            return;
        }

        try {
            $response = Http::withToken($gitlabToken)
                ->timeout(30)
                ->post("{$gitlabUrl}/api/v4/projects/{$gitlabProjectId}/pipeline", [
                    'ref' => $project->branch,
                    'variables' => [
                        [
                            'key' => 'DEVFLOW_PIPELINE_RUN_ID',
                            'value' => (string) $run->id,
                        ],
                        [
                            'key' => 'DEVFLOW_TRIGGERED',
                            'value' => 'true',
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $pipelineData = $response->json();
                $run->update([
                    'status' => 'running',
                    'external_id' => $pipelineData['id'] ?? null,
                    'external_url' => $pipelineData['web_url'] ?? null,
                ]);

                Log::info('GitLab pipeline triggered successfully', [
                    'pipeline_id' => $pipeline->id,
                    'gitlab_pipeline_id' => $pipelineData['id'] ?? null,
                ]);
            } else {
                $error = $response->json('message') ?? $response->body();
                $run->update([
                    'status' => 'failed',
                    'error' => "GitLab API error: {$error}",
                ]);

                Log::error('GitLab pipeline trigger failed', [
                    'pipeline_id' => $pipeline->id,
                    'status_code' => $response->status(),
                    'error' => $error,
                ]);
            }
        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'error' => "GitLab API exception: {$e->getMessage()}",
            ]);

            Log::error('GitLab pipeline trigger exception', [
                'pipeline_id' => $pipeline->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Trigger Jenkins Build
     */
    public function triggerJenkinsBuild(Pipeline $pipeline, PipelineRun $run): void
    {
        $project = $pipeline->project;
        $jenkinsUrl = config('services.jenkins.url');
        $jenkinsUser = config('services.jenkins.user');
        $jenkinsToken = config('services.jenkins.token');
        $jobName = $pipeline->configuration['jenkins_job_name'] ?? $project->slug;

        if (empty($jenkinsUrl) || empty($jenkinsUser) || empty($jenkinsToken)) {
            $run->update([
                'status' => 'failed',
                'error' => 'Jenkins configuration incomplete. Set JENKINS_URL, JENKINS_USER, and JENKINS_TOKEN in .env',
            ]);
            Log::error('Jenkins build trigger failed: Missing configuration', [
                'pipeline_id' => $pipeline->id,
                'project_id' => $project->id,
            ]);

            return;
        }

        try {
            // Jenkins uses Basic Auth with user:token
            $response = Http::withBasicAuth($jenkinsUser, $jenkinsToken)
                ->timeout(30)
                ->post("{$jenkinsUrl}/job/{$jobName}/buildWithParameters", [
                    'BRANCH' => $project->branch,
                    'COMMIT_HASH' => $run->commit_hash,
                    'DEVFLOW_PIPELINE_RUN_ID' => $run->id,
                    'DEVFLOW_PROJECT_ID' => $project->id,
                ]);

            // Jenkins returns 201 for successful build trigger
            if ($response->status() === 201 || $response->successful()) {
                // Try to get the queue item location from headers
                $queueLocation = $response->header('Location');

                $run->update([
                    'status' => 'running',
                    'external_url' => $queueLocation ?? "{$jenkinsUrl}/job/{$jobName}",
                ]);

                Log::info('Jenkins build triggered successfully', [
                    'pipeline_id' => $pipeline->id,
                    'jenkins_job' => $jobName,
                    'queue_location' => $queueLocation,
                ]);

                // Optionally poll for the build number
                if ($queueLocation) {
                    $this->pollJenkinsQueueForBuildNumber($run, $queueLocation, $jenkinsUser, $jenkinsToken);
                }
            } else {
                $run->update([
                    'status' => 'failed',
                    'error' => "Jenkins API error: HTTP {$response->status()} - {$response->body()}",
                ]);

                Log::error('Jenkins build trigger failed', [
                    'pipeline_id' => $pipeline->id,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'error' => "Jenkins API exception: {$e->getMessage()}",
            ]);

            Log::error('Jenkins build trigger exception', [
                'pipeline_id' => $pipeline->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Poll Jenkins queue to get the actual build number
     */
    protected function pollJenkinsQueueForBuildNumber(PipelineRun $run, string $queueLocation, string $user, string $token): void
    {
        // Poll up to 10 times with 2 second intervals
        for ($i = 0; $i < 10; $i++) {
            sleep(2);

            try {
                $response = Http::withBasicAuth($user, $token)
                    ->timeout(10)
                    ->get("{$queueLocation}api/json");

                if ($response->successful()) {
                    $data = $response->json();

                    // Check if build has started (executable will be present)
                    if (isset($data['executable']['number'])) {
                        $buildNumber = $data['executable']['number'];
                        $buildUrl = $data['executable']['url'] ?? null;

                        $run->update([
                            'external_id' => (string) $buildNumber,
                            'external_url' => $buildUrl,
                        ]);

                        Log::info('Jenkins build number retrieved', [
                            'run_id' => $run->id,
                            'build_number' => $buildNumber,
                        ]);

                        return;
                    }

                    // If cancelled
                    if (isset($data['cancelled']) && $data['cancelled']) {
                        $run->update([
                            'status' => 'failed',
                            'error' => 'Jenkins build was cancelled in queue',
                        ]);

                        return;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to poll Jenkins queue', [
                    'run_id' => $run->id,
                    'attempt' => $i + 1,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Execute custom pipeline
     */
    public function executeCustomPipeline(Pipeline $pipeline, PipelineRun $run): void
    {
        $run->update(['status' => 'running']);

        try {
            $stages = $pipeline->configuration['stages'] ?? [];

            foreach ($stages as $stage) {
                $this->executeStage($pipeline, $run, $stage);
            }

            $run->update([
                'status' => 'success',
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Execute pipeline stage
     *
     * @param  array<string, mixed>  $stage
     */
    public function executeStage(Pipeline $pipeline, PipelineRun $run, array $stage): void
    {
        $project = $pipeline->project;
        $projectPath = "/opt/devflow/projects/{$project->slug}";

        foreach ($stage['steps'] ?? [] as $step) {
            $command = $step['run'] ?? '';

            if (empty($command)) {
                continue;
            }

            $result = Process::path($projectPath)
                ->timeout(600)
                ->run($command);

            if (! $result->successful()) {
                throw new \Exception("Step failed: {$step['name']} - {$result->errorOutput()}");
            }

            // Log step output
            $run->logs()->create([
                'stage' => $stage['name'],
                'step' => $step['name'],
                'output' => $result->output(),
                'error' => $result->errorOutput(),
                'exit_code' => $result->exitCode(),
            ]);
        }
    }

    /**
     * Cancel a running pipeline
     *
     * @return array<string, mixed>
     */
    public function cancelPipeline(Pipeline $pipeline, PipelineRun $run): array
    {
        try {
            $result = match ($pipeline->provider) {
                'github' => $this->cancelGitHubWorkflow($pipeline, $run),
                'gitlab' => $this->cancelGitLabPipeline($pipeline, $run),
                'jenkins' => $this->cancelJenkinsBuild($pipeline, $run),
                default => $this->cancelCustomPipeline($run),
            };

            if ($result['success']) {
                $run->update([
                    'status' => 'cancelled',
                    'completed_at' => now(),
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to cancel pipeline', [
                'pipeline_id' => $pipeline->id,
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel GitHub Actions workflow
     *
     * @return array<string, mixed>
     */
    protected function cancelGitHubWorkflow(Pipeline $pipeline, PipelineRun $run): array
    {
        if (! $run->external_id) {
            return ['success' => false, 'error' => 'No external run ID available'];
        }

        $project = $pipeline->project;
        $owner = $this->extractGitHubOwner($project->repository_url);
        $repo = $this->extractGitHubRepo($project->repository_url);

        $response = Http::withToken(config('services.github.token'))
            ->timeout(30)
            ->post("https://api.github.com/repos/{$owner}/{$repo}/actions/runs/{$run->external_id}/cancel");

        return [
            'success' => $response->successful(),
            'error' => $response->successful() ? null : $response->body(),
        ];
    }

    /**
     * Cancel GitLab pipeline
     *
     * @return array<string, mixed>
     */
    protected function cancelGitLabPipeline(Pipeline $pipeline, PipelineRun $run): array
    {
        if (! $run->external_id) {
            return ['success' => false, 'error' => 'No external pipeline ID available'];
        }

        $project = $pipeline->project;
        $projectId = $this->extractGitLabProjectId($project->repository_url);
        $gitlabUrl = config('services.gitlab.url', 'https://gitlab.com');

        $response = Http::withToken(config('services.gitlab.token'))
            ->timeout(30)
            ->post("{$gitlabUrl}/api/v4/projects/{$projectId}/pipelines/{$run->external_id}/cancel");

        return [
            'success' => $response->successful(),
            'error' => $response->successful() ? null : $response->body(),
        ];
    }

    /**
     * Cancel Jenkins build
     *
     * @return array<string, mixed>
     */
    protected function cancelJenkinsBuild(Pipeline $pipeline, PipelineRun $run): array
    {
        if (! $run->external_id) {
            return ['success' => false, 'error' => 'No external build ID available'];
        }

        $jenkinsUrl = config('services.jenkins.url');
        $jenkinsUser = config('services.jenkins.user');
        $jenkinsToken = config('services.jenkins.token');
        $jobName = $pipeline->configuration['jenkins_job_name'] ?? $pipeline->project->slug;

        $response = Http::withBasicAuth($jenkinsUser, $jenkinsToken)
            ->timeout(30)
            ->post("{$jenkinsUrl}/job/{$jobName}/{$run->external_id}/stop");

        return [
            'success' => $response->successful(),
            'error' => $response->successful() ? null : $response->body(),
        ];
    }

    /**
     * Cancel custom pipeline
     *
     * @return array<string, mixed>
     */
    protected function cancelCustomPipeline(PipelineRun $run): array
    {
        // For custom pipelines, we just mark as cancelled
        // In a real implementation, this might need to kill processes
        return ['success' => true];
    }

    /**
     * Retry a failed pipeline run
     */
    public function retryPipeline(Pipeline $pipeline, PipelineRun $failedRun): PipelineRun
    {
        return $this->executePipeline($pipeline, 'retry');
    }

    /**
     * Get pipeline run status from external provider
     *
     * @return array<string, mixed>
     */
    public function getExternalRunStatus(Pipeline $pipeline, PipelineRun $run): array
    {
        if (! $run->external_id) {
            return [
                'status' => $run->status,
                'source' => 'local',
            ];
        }

        try {
            return match ($pipeline->provider) {
                'github' => $this->getGitHubWorkflowStatus($pipeline, $run),
                'gitlab' => $this->getGitLabPipelineStatus($pipeline, $run),
                'jenkins' => $this->getJenkinsBuildStatus($pipeline, $run),
                default => ['status' => $run->status, 'source' => 'local'],
            };
        } catch (\Exception $e) {
            Log::warning('Failed to get external run status', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => $run->status,
                'source' => 'local',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get GitHub workflow run status
     *
     * @return array<string, mixed>
     */
    protected function getGitHubWorkflowStatus(Pipeline $pipeline, PipelineRun $run): array
    {
        $project = $pipeline->project;
        $owner = $this->extractGitHubOwner($project->repository_url);
        $repo = $this->extractGitHubRepo($project->repository_url);

        $response = Http::withToken(config('services.github.token'))
            ->timeout(30)
            ->get("https://api.github.com/repos/{$owner}/{$repo}/actions/runs/{$run->external_id}");

        if ($response->successful()) {
            $data = $response->json();

            return [
                'status' => $this->mapGitHubStatus($data['status'], $data['conclusion'] ?? null),
                'source' => 'github',
                'external_status' => $data['status'],
                'conclusion' => $data['conclusion'] ?? null,
                'url' => $data['html_url'] ?? null,
            ];
        }

        return [
            'status' => $run->status,
            'source' => 'local',
            'error' => $response->body(),
        ];
    }

    /**
     * Get GitLab pipeline status
     *
     * @return array<string, mixed>
     */
    protected function getGitLabPipelineStatus(Pipeline $pipeline, PipelineRun $run): array
    {
        $project = $pipeline->project;
        $projectId = $this->extractGitLabProjectId($project->repository_url);
        $gitlabUrl = config('services.gitlab.url', 'https://gitlab.com');

        $response = Http::withToken(config('services.gitlab.token'))
            ->timeout(30)
            ->get("{$gitlabUrl}/api/v4/projects/{$projectId}/pipelines/{$run->external_id}");

        if ($response->successful()) {
            $data = $response->json();

            return [
                'status' => $this->mapGitLabStatus($data['status']),
                'source' => 'gitlab',
                'external_status' => $data['status'],
                'url' => $data['web_url'] ?? null,
            ];
        }

        return [
            'status' => $run->status,
            'source' => 'local',
            'error' => $response->body(),
        ];
    }

    /**
     * Get Jenkins build status
     *
     * @return array<string, mixed>
     */
    protected function getJenkinsBuildStatus(Pipeline $pipeline, PipelineRun $run): array
    {
        $jenkinsUrl = config('services.jenkins.url');
        $jenkinsUser = config('services.jenkins.user');
        $jenkinsToken = config('services.jenkins.token');
        $jobName = $pipeline->configuration['jenkins_job_name'] ?? $pipeline->project->slug;

        $response = Http::withBasicAuth($jenkinsUser, $jenkinsToken)
            ->timeout(30)
            ->get("{$jenkinsUrl}/job/{$jobName}/{$run->external_id}/api/json");

        if ($response->successful()) {
            $data = $response->json();

            return [
                'status' => $this->mapJenkinsStatus($data['result'] ?? null, $data['building'] ?? false),
                'source' => 'jenkins',
                'external_status' => $data['result'] ?? 'BUILDING',
                'building' => $data['building'] ?? false,
                'url' => $data['url'] ?? null,
            ];
        }

        return [
            'status' => $run->status,
            'source' => 'local',
            'error' => $response->body(),
        ];
    }

    /**
     * Map GitHub status to internal status
     */
    protected function mapGitHubStatus(string $status, ?string $conclusion): string
    {
        if ($status === 'completed') {
            return match ($conclusion) {
                'success' => 'success',
                'failure' => 'failed',
                'cancelled' => 'cancelled',
                'skipped' => 'skipped',
                default => 'failed',
            };
        }

        return match ($status) {
            'queued' => 'queued',
            'in_progress' => 'running',
            'waiting' => 'pending',
            default => 'running',
        };
    }

    /**
     * Map GitLab status to internal status
     */
    protected function mapGitLabStatus(string $status): string
    {
        return match ($status) {
            'success' => 'success',
            'failed' => 'failed',
            'canceled' => 'cancelled',
            'skipped' => 'skipped',
            'pending' => 'queued',
            'running' => 'running',
            'created', 'waiting_for_resource', 'preparing' => 'pending',
            default => 'running',
        };
    }

    /**
     * Map Jenkins status to internal status
     */
    protected function mapJenkinsStatus(?string $result, bool $building): string
    {
        if ($building) {
            return 'running';
        }

        return match ($result) {
            'SUCCESS' => 'success',
            'FAILURE' => 'failed',
            'ABORTED' => 'cancelled',
            'UNSTABLE' => 'failed',
            'NOT_BUILT' => 'skipped',
            null => 'queued',
            default => 'failed',
        };
    }

    /**
     * Get current commit hash
     */
    public function getCurrentCommitHash(Project $project): string
    {
        $projectPath = "/opt/devflow/projects/{$project->slug}";
        $result = Process::path($projectPath)->run('git rev-parse HEAD');

        return trim($result->output());
    }

    /**
     * Extract GitHub owner from repository URL
     */
    public function extractGitHubOwner(string $url): string
    {
        if (preg_match('/github\.com[\/:]([^\/]+)\//', $url, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Extract GitHub repository name from URL
     */
    public function extractGitHubRepo(string $url): string
    {
        if (preg_match('/github\.com[\/:]([^\/]+)\/([^\.]+)/', $url, $matches)) {
            return $matches[2];
        }

        return '';
    }

    /**
     * Extract GitLab project ID from repository URL
     * Supports both numeric IDs and URL-encoded paths
     */
    public function extractGitLabProjectId(string $url): string
    {
        // Handle SSH URLs: git@gitlab.com:group/project.git
        if (preg_match('/git@[^:]+:(.+?)(?:\.git)?$/', $url, $matches)) {
            return urlencode($matches[1]);
        }

        // Handle HTTPS URLs: https://gitlab.com/group/project.git
        if (preg_match('/gitlab\.[^\/]+\/(.+?)(?:\.git)?$/', $url, $matches)) {
            return urlencode($matches[1]);
        }

        // Handle self-hosted GitLab URLs
        if (preg_match('/\/\/[^\/]+\/(.+?)(?:\.git)?$/', $url, $matches)) {
            return urlencode($matches[1]);
        }

        return '';
    }
}
