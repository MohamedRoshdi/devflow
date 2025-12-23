<?php

declare(strict_types=1);

namespace App\Livewire\Deployments;

use App\Models\Deployment;
use Livewire\Attributes\On;
use Livewire\Component;

class DeploymentShow extends Component
{
    public Deployment $deployment;

    public string $currentStep = '';

    public int $progress = 0;

    /** @var array<int, array{line: string, level: string, timestamp: string}> */
    public array $liveLogs = [];

    public function mount(Deployment $deployment): void
    {
        // Authorization check - only allow viewing deployments for projects the user has access to
        $this->authorize('view', $deployment);

        $this->deployment = $deployment;
        $this->analyzeProgress();

        // Initialize live logs with existing logs if deployment is complete
        if (in_array($this->deployment->status, ['success', 'failed'])) {
            $this->initializeLiveLogs();
        }
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

    public function render(): \Illuminate\View\View
    {
        return view('livewire.deployments.deployment-show');
    }
}
