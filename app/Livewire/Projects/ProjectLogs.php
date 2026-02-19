<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\DockerService;
use App\Services\LogManagerService;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectLogs extends Component
{
    #[Locked]
    public int $projectId;

    public string $logType = 'laravel';

    public int $lines = 200;

    public string $logs = '';

    public bool $loading = false;

    public ?string $error = null;

    public ?string $source = null;

    public bool $downloading = false;

    public bool $isLoading = false;

    public function mount(Project $project): void
    {
        // All projects are shared across all users
        $this->projectId = $project->id;
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->loadLogs();
        $this->isLoading = false;
    }

    protected function getProject(): Project
    {
        return Project::with(['server'])->findOrFail($this->projectId);
    }

    public function updatedLogType(): void
    {
        $this->loadLogs();
    }

    public function updatedLines(int|string $value): void
    {
        $value = (int) $value;
        if ($value < 50 || $value > 1000) {
            $this->lines = 200;
        }

        $this->loadLogs();
    }

    public function refreshLogs(): void
    {
        $this->loadLogs();
    }

    public function clearLogs(): void
    {
        $project = $this->getProject();
        $dockerService = app(DockerService::class);

        try {
            $result = $dockerService->clearLaravelLogs($project);

            if ($result['success'] ?? false) {
                session()->flash('message', 'Logs cleared successfully');
                $this->loadLogs();
            } else {
                $this->error = $result['error'] ?? 'Failed to clear logs';
            }
        } catch (\Throwable $e) {
            $this->error = 'Failed to clear logs: '.$e->getMessage();
        }
    }

    public function downloadLogs(): StreamedResponse
    {
        $this->downloading = true;
        $project = $this->getProject();
        $dockerService = app(DockerService::class);

        try {
            $result = $dockerService->downloadLaravelLogs($project);

            if (($result['success'] ?? false) && isset($result['content'], $result['filename'])) {
                $content = $result['content'];
                $filename = $result['filename'];

                return response()->streamDownload(function () use ($content) {
                    echo $content;
                }, $filename, [
                    'Content-Type' => 'text/plain',
                ]);
            }

            $this->error = $result['error'] ?? 'Failed to download logs';
            $this->downloading = false;

            // Return empty response with error
            return response()->streamDownload(function () {
                echo '';
            }, 'error.txt');
        } catch (\Throwable $e) {
            $this->error = 'Failed to download logs: '.$e->getMessage();
            $this->downloading = false;

            return response()->streamDownload(function () {
                echo '';
            }, 'error.txt');
        }
    }

    protected function loadLogs(): void
    {
        $this->loading = true;
        $this->error = null;
        $this->logs = '';
        $this->source = null;

        $project = $this->getProject();

        try {
            match ($this->logType) {
                'docker' => $this->loadDockerLogs($project),
                'deploy' => $this->loadDeployLogs($project),
                'nginx' => $this->loadNginxLogs($project),
                'supervisor' => $this->loadSupervisorLogs($project),
                default => $this->loadLaravelLogs($project),
            };
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }

        $this->loading = false;
    }

    protected function loadLaravelLogs(Project $project): void
    {
        $dockerService = app(DockerService::class);
        $result = $dockerService->getLaravelLogs($project, $this->lines);

        if (($result['success'] ?? false) === true) {
            $content = trim((string) ($result['logs'] ?? ''));
            $this->logs = $content === '' ? 'No log output available.' : $content;
            $this->source = $result['source'] ?? null;
        } else {
            $this->error = $result['error'] ?? 'Unable to load logs for this project.';
        }
    }

    protected function loadDockerLogs(Project $project): void
    {
        $dockerService = app(DockerService::class);
        $result = $dockerService->getContainerLogs($project, $this->lines);

        if (($result['success'] ?? false) === true) {
            $content = trim((string) ($result['logs'] ?? ''));
            $this->logs = $content === '' ? 'No log output available.' : $content;
            $this->source = $result['source'] ?? 'container';
        } else {
            $this->error = $result['error'] ?? 'Unable to load logs for this project.';
        }
    }

    protected function loadDeployLogs(Project $project): void
    {
        $logManager = app(LogManagerService::class);
        $deployments = $logManager->getDeploymentLogs($project, null, $this->lines);

        if ($deployments->isEmpty()) {
            $this->logs = 'No deployment logs available.';
            $this->source = 'deploy';

            return;
        }

        $output = '';
        foreach ($deployments as $entry) {
            $status = strtoupper((string) ($entry['status'] ?? 'unknown'));
            $hash = $entry['commit_hash'] ? substr((string) $entry['commit_hash'], 0, 8) : 'N/A';
            $time = $entry['started_at'] ? $entry['started_at']->format('Y-m-d H:i:s') : 'N/A';
            $output .= "=== Deployment #{$entry['deployment_id']} [{$status}] {$hash} @ {$time} ===\n";

            if ($entry['commit_message']) {
                $output .= "Commit: {$entry['commit_message']}\n";
            }

            if ($entry['output_log']) {
                $output .= $entry['output_log']."\n";
            }

            if ($entry['error_log']) {
                $output .= "--- ERRORS ---\n{$entry['error_log']}\n";
            }

            $output .= "\n";
        }

        $this->logs = trim($output);
        $this->source = 'deploy';
    }

    protected function loadNginxLogs(Project $project): void
    {
        $logManager = app(LogManagerService::class);
        $logs = $logManager->getProjectNginxLogs($project, $this->lines);

        $output = '';

        if (! empty($logs['access_log'])) {
            $output .= "=== Access Log ===\n{$logs['access_log']}\n\n";
        }

        if (! empty($logs['error_log'])) {
            $output .= "=== Error Log ===\n{$logs['error_log']}\n";
        }

        $this->logs = trim($output) ?: 'No nginx logs available.';
        $this->source = 'nginx';
    }

    protected function loadSupervisorLogs(Project $project): void
    {
        $logManager = app(LogManagerService::class);
        $logs = $logManager->getProjectSupervisorLogs($project, $this->lines);

        $content = trim($logs['worker_log'] ?? '');
        $this->logs = $content === '' ? 'No supervisor logs available.' : $content;
        $this->source = 'supervisor';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.project-logs');
    }
}
