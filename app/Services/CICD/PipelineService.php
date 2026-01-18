<?php

declare(strict_types=1);

namespace App\Services\CICD;

use App\Models\Pipeline;
use App\Models\PipelineRun;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * Main Pipeline service facade.
 *
 * This class acts as a unified interface to the pipeline subsystem,
 * delegating to specialized services for specific operations.
 *
 * Services:
 * - PipelineBuilderService: Pipeline configuration generation
 * - PipelineExecutorService: Pipeline execution and triggering
 * - PipelineWebhookService: Webhook setup and verification
 */
class PipelineService
{
    /** @var array<int, string> */
    protected array $supportedProviders = ['github', 'gitlab', 'bitbucket', 'jenkins', 'custom'];

    public function __construct(
        protected readonly PipelineBuilderService $builderService,
        protected readonly PipelineExecutorService $executorService,
        protected readonly PipelineWebhookService $webhookService,
    ) {}

    // =========================================================================
    // Direct Service Access
    // =========================================================================

    /**
     * Get the builder service for pipeline configuration generation
     */
    public function builder(): PipelineBuilderService
    {
        return $this->builderService;
    }

    /**
     * Get the executor service for pipeline execution
     */
    public function executor(): PipelineExecutorService
    {
        return $this->executorService;
    }

    /**
     * Get the webhook service for webhook management
     */
    public function webhook(): PipelineWebhookService
    {
        return $this->webhookService;
    }

    // =========================================================================
    // Pipeline Creation & Configuration (delegates to BuilderService)
    // =========================================================================

    /**
     * Create and configure CI/CD pipeline for a project
     *
     * @param  array<string, mixed>  $config
     */
    public function createPipeline(Project $project, array $config): Pipeline
    {
        $pipeline = Pipeline::create([
            'project_id' => $project->id,
            'name' => $config['name'] ?? "{$project->name} Pipeline",
            'provider' => $config['provider'] ?? 'github',
            'trigger_events' => $config['trigger_events'] ?? ['push', 'pull_request'],
            'branch_filters' => $config['branch_filters'] ?? ['main', 'develop'],
            'configuration' => $this->builderService->generatePipelineConfig($project, $config),
            'enabled' => $config['enabled'] ?? true,
        ]);

        // Set up webhook for automatic triggering
        $this->webhookService->setupWebhook($project);

        // Create pipeline file in repository
        $this->webhookService->createPipelineFile($pipeline);

        return $pipeline;
    }

    /**
     * Generate pipeline configuration based on project type
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generatePipelineConfig(Project $project, array $config): array
    {
        return $this->builderService->generatePipelineConfig($project, $config);
    }

    /**
     * Generate GitHub Actions workflow
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generateGitHubActionsConfig(Project $project, array $config): array
    {
        return $this->builderService->generateGitHubActionsConfig($project, $config);
    }

    /**
     * Generate GitLab CI configuration
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generateGitLabCIConfig(Project $project, array $config): array
    {
        return $this->builderService->generateGitLabCIConfig($project, $config);
    }

    /**
     * Generate Bitbucket Pipelines configuration
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generateBitbucketPipelinesConfig(Project $project, array $config): array
    {
        return $this->builderService->generateBitbucketPipelinesConfig($project, $config);
    }

    /**
     * Generate Jenkins Pipeline configuration
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generateJenkinsConfig(Project $project, array $config): array
    {
        return $this->builderService->generateJenkinsConfig($project, $config);
    }

    /**
     * Generate custom pipeline configuration
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generateCustomConfig(Project $project, array $config): array
    {
        return $this->builderService->generateCustomConfig($project, $config);
    }

    // =========================================================================
    // Pipeline Execution (delegates to ExecutorService)
    // =========================================================================

    /**
     * Execute pipeline run
     */
    public function executePipeline(Pipeline $pipeline, string $trigger = 'manual'): PipelineRun
    {
        return $this->executorService->executePipeline($pipeline, $trigger);
    }

    /**
     * Trigger GitHub Actions workflow
     */
    public function triggerGitHubActions(Pipeline $pipeline, PipelineRun $run): void
    {
        $this->executorService->triggerGitHubActions($pipeline, $run);
    }

    /**
     * Trigger GitLab Pipeline
     */
    public function triggerGitLabPipeline(Pipeline $pipeline, PipelineRun $run): void
    {
        $this->executorService->triggerGitLabPipeline($pipeline, $run);
    }

    /**
     * Trigger Jenkins Build
     */
    public function triggerJenkinsBuild(Pipeline $pipeline, PipelineRun $run): void
    {
        $this->executorService->triggerJenkinsBuild($pipeline, $run);
    }

    /**
     * Execute custom pipeline
     */
    public function executeCustomPipeline(Pipeline $pipeline, PipelineRun $run): void
    {
        $this->executorService->executeCustomPipeline($pipeline, $run);
    }

    /**
     * Execute pipeline stage
     *
     * @param  array<string, mixed>  $stage
     */
    public function executeStage(Pipeline $pipeline, PipelineRun $run, array $stage): void
    {
        $this->executorService->executeStage($pipeline, $run, $stage);
    }

    /**
     * Cancel a running pipeline
     *
     * @return array<string, mixed>
     */
    public function cancelPipeline(Pipeline $pipeline, PipelineRun $run): array
    {
        return $this->executorService->cancelPipeline($pipeline, $run);
    }

    /**
     * Retry a failed pipeline run
     */
    public function retryPipeline(Pipeline $pipeline, PipelineRun $failedRun): PipelineRun
    {
        return $this->executorService->retryPipeline($pipeline, $failedRun);
    }

    /**
     * Get pipeline run status from external provider
     *
     * @return array<string, mixed>
     */
    public function getExternalRunStatus(Pipeline $pipeline, PipelineRun $run): array
    {
        return $this->executorService->getExternalRunStatus($pipeline, $run);
    }

    /**
     * Get current commit hash
     */
    public function getCurrentCommitHash(Project $project): string
    {
        return $this->executorService->getCurrentCommitHash($project);
    }

    // =========================================================================
    // Webhook Management (delegates to WebhookService)
    // =========================================================================

    /**
     * Setup webhook for automatic pipeline triggering
     *
     * @return array{success: bool, webhook_id?: string, webhook_url?: string, error?: string}
     */
    public function setupWebhook(Project $project): array
    {
        return $this->webhookService->setupWebhook($project);
    }

    /**
     * Delete webhook from Git provider
     */
    public function deleteWebhook(Project $project): bool
    {
        return $this->webhookService->deleteWebhook($project);
    }

    /**
     * Verify webhook signature from incoming request
     */
    public function verifyWebhookSignature(Request $request, Project $project): bool
    {
        return $this->webhookService->verifyWebhookSignature($request, $project);
    }

    /**
     * Detect Git provider from repository URL
     */
    public function detectGitProvider(string $url): string
    {
        return $this->webhookService->detectGitProvider($url);
    }

    /**
     * Create pipeline file in repository
     */
    public function createPipelineFile(Pipeline $pipeline): void
    {
        $this->webhookService->createPipelineFile($pipeline);
    }

    /**
     * Update existing webhook configuration
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function updateWebhook(Project $project, array $options = []): array
    {
        return $this->webhookService->updateWebhook($project, $options);
    }

    /**
     * Test webhook connectivity
     *
     * @return array<string, mixed>
     */
    public function testWebhook(Project $project): array
    {
        return $this->webhookService->testWebhook($project);
    }

    /**
     * Get webhook delivery history
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWebhookDeliveries(Project $project, int $limit = 10): array
    {
        return $this->webhookService->getWebhookDeliveries($project, $limit);
    }

    // =========================================================================
    // Utility Methods (delegates to appropriate services)
    // =========================================================================

    /**
     * Extract GitHub owner from repository URL
     */
    public function extractGitHubOwner(string $url): string
    {
        return $this->executorService->extractGitHubOwner($url);
    }

    /**
     * Extract GitHub repository name from URL
     */
    public function extractGitHubRepo(string $url): string
    {
        return $this->executorService->extractGitHubRepo($url);
    }

    /**
     * Extract GitLab project ID from repository URL
     */
    public function extractGitLabProjectId(string $url): string
    {
        return $this->executorService->extractGitLabProjectId($url);
    }

    /**
     * Get supported CI/CD providers
     *
     * @return array<int, string>
     */
    public function getSupportedProviders(): array
    {
        return $this->supportedProviders;
    }
}
