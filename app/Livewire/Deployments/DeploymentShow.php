<?php

declare(strict_types=1);

namespace App\Livewire\Deployments;

use App\Jobs\DeployProjectJob;
use App\Models\Deployment;
use App\Services\RollbackService;
use Livewire\Attributes\On;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeploymentShow extends Component
{
    public Deployment $deployment;

    public string $currentStep = '';

    public int $progress = 0;

    /** @var array<int, array{line: string, level: string, timestamp: string}> */
    public array $liveLogs = [];

    public bool $showRollbackConfirm = false;

    public bool $rollbackInProgress = false;

    public string $logSearch = '';

    public function mount(Deployment $deployment): void
    {
        // Authorization check - only allow viewing deployments for projects the user has access to
        $this->authorize('view', $deployment);

        $this->deployment = $deployment;
        $this->deployment->load(['project.domains', 'project.server:id,name,ip_address']);
        $this->analyzeProgress();

        // Initialize live logs with existing logs if deployment is complete
        if (in_array($this->deployment->status, ['success', 'failed'])) {
            $this->initializeLiveLogs();
        }
    }

    public function canRollback(): bool
    {
        if ($this->deployment->status !== 'success') {
            return false;
        }

        if (empty($this->deployment->commit_hash)) {
            return false;
        }

        // Can't rollback to the latest deployment
        $project = $this->deployment->project;
        if ($project === null) {
            return false;
        }

        $latestDeployment = $project->deployments()
            ->where('status', 'success')
            ->latest()
            ->first();

        return $latestDeployment === null || $latestDeployment->id !== $this->deployment->id;
    }

    public function initiateRollback(): void
    {
        if (! $this->canRollback()) {
            return;
        }

        $this->showRollbackConfirm = true;
    }

    public function confirmRollback(): void
    {
        $this->authorize('rollback', $this->deployment);

        if (! $this->canRollback()) {
            $this->showRollbackConfirm = false;

            return;
        }

        $this->rollbackInProgress = true;
        $this->showRollbackConfirm = false;

        $rollbackService = app(RollbackService::class);

        try {
            if ($this->deployment->release_path) {
                $result = $rollbackService->rollbackToRelease($this->deployment);
            } else {
                $result = $rollbackService->rollbackToDeployment($this->deployment);
            }

            if ($result['success'] ?? false) {
                /** @var Deployment $newDeployment */
                $newDeployment = $result['deployment'];
                $this->redirect(route('deployments.show', $newDeployment), navigate: true);
            } else {
                session()->flash('error', $result['error'] ?? 'Rollback failed.');
                $this->rollbackInProgress = false;
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Rollback failed: '.$e->getMessage());
            $this->rollbackInProgress = false;
        }
    }

    public function cancelRollback(): void
    {
        $this->showRollbackConfirm = false;
        $this->rollbackInProgress = false;
    }

    /**
     * Initialize live logs from stored output_log for completed deployments
     */
    protected function initializeLiveLogs(): void
    {
        if ($this->deployment->output_log) {
            $lines = explode("\n", $this->deployment->output_log);
            foreach ($lines as $line) {
                $this->liveLogs[] = [
                    'line' => strip_tags($line),
                    'level' => $this->detectLogLevel($line),
                    'timestamp' => now()->toIso8601String(),
                ];
            }
        }
    }

    /**
     * Detect log level based on content
     */
    protected function detectLogLevel(string $line): string
    {
        $lowerLine = strtolower($line);

        // Check for error patterns
        if (preg_match('/^(error|fatal|failed)/i', $line) ||
            str_contains($lowerLine, 'exception') ||
            str_contains($lowerLine, 'fatal error') ||
            str_contains($lowerLine, 'failed')) {
            return 'error';
        }

        // Check for warning patterns
        if (preg_match('/^(warning|warn|notice)/i', $line) ||
            str_contains($lowerLine, 'deprecated') ||
            str_contains($lowerLine, 'skipped')) {
            return 'warning';
        }

        // Everything else is info
        return 'info';
    }

    /**
     * Listen for new log lines broadcasted via WebSocket
     *
     * @param  array{line: string, level: string, timestamp: string}  $event
     */
    #[On('echo:deployment-logs.{deployment.id},DeploymentLogUpdated')]
    public function onLogUpdated(array $event): void
    {
        $this->liveLogs[] = [
            'line' => strip_tags($event['line']),
            'level' => in_array($event['level'], ['error', 'warning', 'info'], true) ? $event['level'] : 'info',
            'timestamp' => $event['timestamp'],
        ];

        // Also update the deployment model to keep in sync
        $this->deployment->refresh();
        $this->analyzeProgress();
    }

    /**
     * Listen for deployment status updates via WebSocket
     *
     * @param  array<string, mixed>  $event
     */
    #[On('echo-private:deployment.{deployment.id},.deployment.status.updated')]
    public function onStatusUpdated(array $event): void
    {
        $this->deployment->refresh();
        $this->analyzeProgress();

        // Initialize logs if deployment just completed
        if (in_array($this->deployment->status, ['success', 'failed']) && empty($this->liveLogs)) {
            $this->initializeLiveLogs();
        }
    }

    public function refresh(): void
    {
        $freshDeployment = $this->deployment->fresh();
        if ($freshDeployment !== null) {
            $this->deployment = $freshDeployment;
        }
        $this->analyzeProgress();
    }

    protected function analyzeProgress(): void
    {
        $logs = $this->deployment->output_log ?? '';

        // Determine current step and progress from logs
        if (str_contains($logs, '=== Cloning Repository ===')) {
            $this->currentStep = 'Cloning repository';
            $this->progress = 10;
        }

        if (str_contains($logs, '✓ Repository cloned successfully')) {
            $this->currentStep = 'Recording commit information';
            $this->progress = 20;
        }

        if (str_contains($logs, '✓ Commit information recorded')) {
            $this->currentStep = 'Building Docker container';
            $this->progress = 25;
        }

        if (str_contains($logs, 'Building Docker Container')) {
            $this->currentStep = 'Installing system packages';
            $this->progress = 30;
        }

        if (str_contains($logs, 'Installing shared extensions')) {
            $this->currentStep = 'Installing PHP extensions';
            $this->progress = 40;
        }

        if (str_contains($logs, 'Installing dependencies from lock file')) {
            $this->currentStep = 'Installing Composer dependencies';
            $this->progress = 50;
        }

        if (str_contains($logs, 'npm install') || str_contains($logs, 'Installing node modules')) {
            $this->currentStep = 'Installing Node dependencies';
            $this->progress = 60;
        }

        if (str_contains($logs, 'npm run build') || str_contains($logs, 'vite build')) {
            $this->currentStep = 'Building frontend assets';
            $this->progress = 75;
        }

        if (str_contains($logs, 'Laravel optimization') || str_contains($logs, 'config:cache')) {
            $this->currentStep = 'Optimizing Laravel';
            $this->progress = 85;
        }

        if (str_contains($logs, '✓ Build successful') || str_contains($logs, 'Build complete')) {
            $this->currentStep = 'Starting container';
            $this->progress = 90;
        }

        if (str_contains($logs, 'Container started')) {
            $this->currentStep = 'Deployment complete';
            $this->progress = 100;
        }

        if ($this->deployment->status === 'success') {
            $this->currentStep = 'Deployment successful';
            $this->progress = 100;
        }

        if ($this->deployment->status === 'failed') {
            $this->currentStep = 'Deployment failed';
            $this->progress = 0;
        }
    }

    public function retryDeployment(): void
    {
        if ($this->deployment->status !== 'failed') {
            return;
        }

        $this->authorize('create', Deployment::class);

        $newDeployment = Deployment::create([
            'user_id' => auth()->id(),
            'project_id' => $this->deployment->project_id,
            'server_id' => $this->deployment->server_id,
            'branch' => $this->deployment->branch,
            'commit_hash' => $this->deployment->commit_hash,
            'commit_message' => 'Retry of deployment #'.$this->deployment->id,
            'triggered_by' => 'manual',
            'status' => 'pending',
        ]);

        DeployProjectJob::dispatch($newDeployment);

        $this->redirect(route('deployments.show', $newDeployment), navigate: true);
    }

    public function cancelDeployment(): void
    {
        if (! in_array($this->deployment->status, ['pending', 'running'])) {
            return;
        }

        $this->deployment->update([
            'status' => 'failed',
            'output_log' => ($this->deployment->output_log ?? '') . "\n\n--- Deployment cancelled by user at " . now()->toDateTimeString() . " ---",
            'completed_at' => now(),
        ]);

        $this->deployment->refresh();
        $this->analyzeProgress();
        $this->initializeLiveLogs();

        session()->flash('message', 'Deployment cancelled.');
    }

    public function exportLogs(): StreamedResponse
    {
        $filename = 'deployment-' . $this->deployment->id . '-logs.txt';
        $content = $this->deployment->output_log ?? 'No logs available.';

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.deployments.deployment-show');
    }
}
