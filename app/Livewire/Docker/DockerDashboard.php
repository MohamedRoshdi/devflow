<?php

declare(strict_types=1);

namespace App\Livewire\Docker;

use App\Models\Server;
use App\Services\DockerService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class DockerDashboard extends Component
{
    use AuthorizesRequests;

    public Server $server;

    /** @var array<string, mixed>|null */
    public ?array $dockerInfo = null;

    /** @var array<string, mixed>|null */
    public ?array $diskUsage = null;

    /** @var array<int, array<string, mixed>> */
    public array $volumes = [];

    /** @var array<int, array<string, mixed>> */
    public array $networks = [];

    /** @var array<int, array<string, mixed>> */
    public array $images = [];

    public string $activeTab = 'overview';

    public bool $loading = false;

    public bool $isLoading = false;

    public ?string $error = null;

    protected DockerService $dockerService;

    public function boot(DockerService $dockerService): void
    {
        $this->dockerService = $dockerService;
    }

    public function mount(Server $server): void
    {
        // Ensure user is authenticated before loading Docker data
        if (! auth()->check()) {
            abort(401);
        }

        // All servers are shared across all users
        $this->server = $server;
        $this->loadInitialData();
    }

    /**
     * Lazy load Docker data - called via wire:init
     */
    public function loadInitialData(): void
    {
        $this->loadDockerInfo();
        $this->isLoading = false;
    }

    public function loadDockerInfo(): void
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
            $this->error = 'Failed to load Docker information: '.$e->getMessage();
        }

        $this->loading = false;
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function pruneImages(): void
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->pruneImages($this->server, false);
            if ($result['success']) {
                session()->flash('message', 'Images pruned successfully! '.$result['output']);
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to prune images: '.($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: '.$e->getMessage();
        }
        $this->loading = false;
    }

    public function systemPrune(): void
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->systemPrune($this->server, false);
            if ($result['success']) {
                session()->flash('message', 'System cleaned up successfully! '.$result['output']);
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to clean up system: '.($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: '.$e->getMessage();
        }
        $this->loading = false;
    }

    public function deleteImage(string $imageId): void
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->deleteImage($this->server, $imageId);
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

    public function deleteVolume(string $volumeName): void
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->deleteVolume($this->server, $volumeName);
            if ($result['success']) {
                session()->flash('message', 'Volume deleted successfully!');
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to delete volume: '.($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: '.$e->getMessage();
        }
        $this->loading = false;
    }

    public function deleteNetwork(string $networkName): void
    {
        $this->loading = true;
        try {
            $result = $this->dockerService->deleteNetwork($this->server, $networkName);
            if ($result['success']) {
                session()->flash('message', 'Network deleted successfully!');
                $this->loadDockerInfo();
            } else {
                $this->error = 'Failed to delete network: '.($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error = 'Error: '.$e->getMessage();
        }
        $this->loading = false;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.docker.docker-dashboard');
    }
}
