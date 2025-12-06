<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\DockerService;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectDockerManagement extends Component
{
    #[Locked]
    public int $projectId;

    /** @var array<int, array<string, mixed>> */
    public array $images = [];

    /** @var array<string, mixed>|null */
    public ?array $containerInfo = null;

    /** @var array<string, mixed>|null */
    public ?array $containerStats = null;

    public string $containerLogs = '';

    public string $activeTab = 'overview';

    public bool $loading = false;

    public ?string $error = null;

    public bool $showLogs = false;

    public int $logLines = 100;

    public bool $initialized = false;

    public function mount(Project $project): void
    {
        // All projects are shared across all users
        $this->projectId = $project->id;
    }

    /**
     * @param  array<string, mixed>|int|null  $payload
     */
    #[On('init-docker')]
    public function handleInitDocker(array|int|null $payload = null): void
    {
        $id = is_array($payload) ? ($payload['projectId'] ?? null) : $payload;
        if ($id !== null && (int) $id !== $this->projectId) {
            return;
        }

        $this->initDocker();
    }

    public function initDocker(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->loadDockerInfo();
        $this->initialized = true;
    }

    protected function getProject(): Project
    {
        return Project::findOrFail($this->projectId);
    }

    public function loadDockerInfo(): void
    {
        $this->loading = true;
        $this->error = null;

        try {
            $project = $this->getProject();
            $dockerService = app(DockerService::class);

            // Get project-specific images
            $imagesResult = $dockerService->listProjectImages($project);
            if ($imagesResult['success']) {
                $this->images = $imagesResult['images'];
            }

            // Get container status
            $statusResult = $dockerService->getContainerStatus($project);
            if ($statusResult['success'] && $statusResult['exists']) {
                $this->containerInfo = $statusResult['container'];

                // Get container stats if running
                if (isset($this->containerInfo['State']) && is_string($this->containerInfo['State']) && stripos($this->containerInfo['State'], 'running') !== false) {
                    $statsResult = $dockerService->getContainerStats($project);
                    if ($statsResult['success']) {
                        $this->containerStats = $statsResult['stats'];
                    }
                }
            } else {
                $this->containerInfo = null;
                $this->containerStats = null;
            }

        } catch (\Exception $e) {
            $this->error = 'Failed to load Docker information: '.$e->getMessage();
        }

        $this->loading = false;
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;

        if ($tab === 'logs' && ! $this->containerLogs) {
            $this->loadLogs();
        }
    }

    public function loadLogs(): void
    {
        $this->loading = true;
        try {
            $project = $this->getProject();
            $dockerService = app(DockerService::class);
            $result = $dockerService->getContainerLogs($project, $this->logLines);
            if ($result['success']) {
                $this->containerLogs = $result['logs'];
            } else {
                $this->error = 'Failed to load logs: '.($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error loading logs: '.$e->getMessage();
        }
        $this->loading = false;
    }

    public function refreshLogs(): void
    {
        $this->loadLogs();
        session()->flash('message', 'Logs refreshed successfully!');
    }

    public function buildImage(): void
    {
        $this->loading = true;
        try {
            $project = $this->getProject();
            $dockerService = app(DockerService::class);
            $result = $dockerService->buildContainer($project);
            if ($result['success']) {
                session()->flash('message', 'Docker image built successfully!');
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to build image: '.($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: '.$e->getMessage();
        }
        $this->loading = false;
    }

    public function startContainer(): void
    {
        $this->loading = true;
        try {
            $project = $this->getProject();
            $dockerService = app(DockerService::class);
            $result = $dockerService->startContainer($project);
            if ($result['success']) {
                $project->update(['status' => 'running']);
                session()->flash('message', 'Container started successfully!');
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to start container: '.($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: '.$e->getMessage();
        }
        $this->loading = false;
    }

    public function stopContainer(): void
    {
        $this->loading = true;
        try {
            $project = $this->getProject();
            $dockerService = app(DockerService::class);
            $result = $dockerService->stopContainer($project);
            if ($result['success']) {
                $project->update(['status' => 'stopped']);
                session()->flash('message', 'Container stopped successfully!');
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to stop container: '.($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: '.$e->getMessage();
        }
        $this->loading = false;
    }

    public function restartContainer(): void
    {
        $this->stopContainer();
        sleep(2); // Wait a bit before restarting
        $this->startContainer();
    }

    public function deleteImage(string $imageId): void
    {
        $this->loading = true;
        try {
            $project = $this->getProject();
            $dockerService = app(DockerService::class);
            $result = $dockerService->deleteImage($project->server, $imageId);
            if ($result['success']) {
                session()->flash('message', 'Image deleted successfully!');
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to delete image: '.($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: '.$e->getMessage();
        }
        $this->loading = false;
    }

    public function exportContainer(): void
    {
        $this->loading = true;
        try {
            $project = $this->getProject();
            $dockerService = app(DockerService::class);
            $result = $dockerService->exportContainer($project);
            if ($result['success']) {
                session()->flash('message', 'Container exported as backup: '.$result['backup_name']);
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to export container: '.($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: '.$e->getMessage();
        }
        $this->loading = false;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $project = $this->getProject();

        return view('livewire.projects.project-docker-management', [
            'project' => $project,
        ]);
    }
}
