<?php

namespace App\Livewire\Docker;

use App\Models\Server;
use App\Services\DockerService;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DockerDashboard extends Component
{
    use AuthorizesRequests;

    public Server $server;
    public $dockerInfo = null;
    public $diskUsage = null;
    public $volumes = [];
    public $networks = [];
    public $images = [];
    public $activeTab = 'overview';
    public $loading = false;
    public $error = null;

    protected DockerService $dockerService;

    public function boot(DockerService $dockerService)
    {
        $this->dockerService = $dockerService;
    }

    public function mount(Server $server)
    {
        // All servers are shared across all users
        $this->server = $server;
        $this->loadDockerInfo();
    }

    public function loadDockerInfo()
    {
        $this->loading = true;
        $this->error = null;

        try {
            // Get Docker system info
            $infoResult = $this->dockerService->getSystemInfo($this->server);
            if ($infoResult['success']) {
                $this->dockerInfo = $infoResult['info'];
            }

            // Get disk usage
            $diskResult = $this->dockerService->getDiskUsage($this->server);
            if ($diskResult['success']) {
                $this->diskUsage = $diskResult['usage'];
            }

            // Get volumes
            $volumesResult = $this->dockerService->listVolumes($this->server);
            if ($volumesResult['success']) {
                $this->volumes = $volumesResult['volumes'];
            }

            // Get networks
            $networksResult = $this->dockerService->listNetworks($this->server);
            if ($networksResult['success']) {
                $this->networks = $networksResult['networks'];
            }

            // Get images
            $imagesResult = $this->dockerService->listImages($this->server);
            if ($imagesResult['success']) {
                $this->images = $imagesResult['images'];
            }

        } catch (\Exception $e) {
            $this->error = 'Failed to load Docker information: ' . $e->getMessage();
        }

        $this->loading = false;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function pruneImages()
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->pruneImages($this->server, false);
            if ($result['success']) {
                session()->flash('message', 'Images pruned successfully! ' . $result['output']);
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to prune images: ' . ($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }
        $this->loading = false;
    }

    public function systemPrune()
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->systemPrune($this->server, false);
            if ($result['success']) {
                session()->flash('message', 'System cleaned up successfully! ' . $result['output']);
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to clean up system: ' . ($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }
        $this->loading = false;
    }

    public function deleteImage($imageId)
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->deleteImage($this->server, $imageId);
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

    public function deleteVolume($volumeName)
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->deleteVolume($this->server, $volumeName);
            if ($result['success']) {
                session()->flash('message', 'Volume deleted successfully!');
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to delete volume: ' . ($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }
        $this->loading = false;
    }

    public function deleteNetwork($networkName)
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->deleteNetwork($this->server, $networkName);
            if ($result['success']) {
                session()->flash('message', 'Network deleted successfully!');
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to delete network: ' . ($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.docker.docker-dashboard');
    }
}

