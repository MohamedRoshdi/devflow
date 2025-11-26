<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\DockerService;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

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

    public function mount(Project $project): void
    {
        if ($project->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this project');
        }

        $this->projectId = $project->id;
        $this->loadLogs();
    }

    protected function getProject(): Project
    {
        return Project::findOrFail($this->projectId);
    }

    public function updatedLogType(): void
    {
        $this->loadLogs();
    }

    public function updatedLines($value): void
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
            $this->error = 'Failed to clear logs: ' . $e->getMessage();
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

    public function render()
    {
        return view('livewire.projects.project-logs');
    }
}
