<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\DockerService;
use Livewire\Component;

class ProjectDockerManagement extends Component
{
    public Project $project;
    public $images = [];
    public $containerInfo = null;
    public $containerStats = null;
    public $containerLogs = '';
    public $activeTab = 'overview';
    public $loading = false;
    public $error = null;
    public $showLogs = false;
    public $logLines = 100;

    protected DockerService $dockerService;

    public function boot(DockerService $dockerService)
    {
        $this->dockerService = $dockerService;
    }

    public function mount(Project $project)
    {
        // Check if project belongs to current user
        if ($project->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this project.');
        }

        $this->project = $project;
        $this->loadDockerInfo();
    }

    public function loadDockerInfo()
    {
        $this->loading = true;
        $this->error = null;

        try {
            // Get project-specific images
            $imagesResult = $this->dockerService->listProjectImages($this->project);
            if ($imagesResult['success']) {
                $this->images = $imagesResult['images'];
            }

            // Get container status
            $statusResult = $this->dockerService->getContainerStatus($this->project);
            if ($statusResult['success'] && $statusResult['exists']) {
                $this->containerInfo = $statusResult['container'];
                
                // Get container stats if running
                if (isset($this->containerInfo['State']) && stripos($this->containerInfo['State'], 'running') !== false) {
                    $statsResult = $this->dockerService->getContainerStats($this->project);
                    if ($statsResult['success']) {
                        $this->containerStats = $statsResult['stats'];
                    }
                }
            } else {
                $this->containerInfo = null;
                $this->containerStats = null;
            }

        } catch (\Exception $e) {
            $this->error = 'Failed to load Docker information: ' . $e->getMessage();
        }

        $this->loading = false;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        
        if ($tab === 'logs' && !$this->containerLogs) {
            $this->loadLogs();
        }
    }

    public function loadLogs()
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->getContainerLogs($this->project, $this->logLines);
            if ($result['success']) {
                $this->containerLogs = $result['logs'];
            } else {
                $this->error = 'Failed to load logs: ' . ($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error loading logs: ' . $e->getMessage();
        }
        $this->loading = false;
    }

    public function refreshLogs()
    {
        $this->loadLogs();
        session()->flash('message', 'Logs refreshed successfully!');
    }

    public function buildImage()
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->buildContainer($this->project);
            if ($result['success']) {
                session()->flash('message', 'Docker image built successfully!');
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to build image: ' . ($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }
        $this->loading = false;
    }

    public function startContainer()
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->startContainer($this->project);
            if ($result['success']) {
                $this->project->update(['status' => 'running']);
                session()->flash('message', 'Container started successfully!');
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to start container: ' . ($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }
        $this->loading = false;
    }

    public function stopContainer()
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->stopContainer($this->project);
            if ($result['success']) {
                $this->project->update(['status' => 'stopped']);
                session()->flash('message', 'Container stopped successfully!');
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to stop container: ' . ($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }
        $this->loading = false;
    }

    public function restartContainer()
    {
        $this->stopContainer();
        sleep(2); // Wait a bit before restarting
        $this->startContainer();
    }

    public function deleteImage($imageId)
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->deleteImage($this->project->server, $imageId);
            if ($result['success']) {
                session()->flash('message', 'Image deleted successfully!');
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to delete image: ' . ($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }
        $this->loading = false;
    }

    public function exportContainer()
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->exportContainer($this->project);
            if ($result['success']) {
                session()->flash('message', 'Container exported as backup: ' . $result['backup_name']);
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to export container: ' . ($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.projects.project-docker-management');
    }
}

