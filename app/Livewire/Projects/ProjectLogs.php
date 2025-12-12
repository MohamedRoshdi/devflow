<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\DockerService;
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

    public bool $isLoading = true;

    public function mount(Project $project): void
    {
        // All projects are shared across all users
        $this->projectId = $project->id;
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

            if ($result['success'] ?? false) {
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
        $dockerService = app(DockerService::class);

        try {
            if ($this->logType === 'docker') {
                $result = $dockerService->getContainerLogs($project, $this->lines);
            } else {
                $result = $dockerService->getLaravelLogs($project, $this->lines);
            }

            if (($result['success'] ?? false) === true) {
                $content = trim((string) ($result['logs'] ?? ''));
                $this->logs = $content === '' ? 'No log output available.' : $content;
                $this->source = $result['source'] ?? ($this->logType === 'docker' ? 'container' : null);
            } else {
                $this->error = $result['error'] ?? 'Unable to load logs for this project.';
            }
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }

        $this->loading = false;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.project-logs');
    }
}
