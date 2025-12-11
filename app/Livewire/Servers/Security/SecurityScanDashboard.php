<?php

declare(strict_types=1);

namespace App\Livewire\Servers\Security;

use App\Models\SecurityScan;
use App\Models\Server;
use App\Services\Security\SecurityScoreService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class SecurityScanDashboard extends Component
{
    use AuthorizesRequests, WithPagination;

    public Server $server;

    public ?SecurityScan $selectedScan = null;

    public bool $isScanning = false;

    public bool $showDetails = false;

    public ?string $flashMessage = null;

    public ?string $flashType = null;

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
    }

    public function runScan(): void
    {
        $this->isScanning = true;

        try {
            $service = app(SecurityScoreService::class);
            $scan = $service->runSecurityScan($this->server);

            $this->flashMessage = "Security scan completed. Score: {$scan->score}/100";
            $this->flashType = 'success';
            $this->server->refresh();
        } catch (\Exception $e) {
            $this->flashMessage = 'Scan failed: '.$e->getMessage();
            $this->flashType = 'error';
        }

        $this->isScanning = false;
    }

    public function viewScanDetails(int $scanId): void
    {
        $this->selectedScan = SecurityScan::find($scanId);
        $this->showDetails = true;
    }

    public function closeDetails(): void
    {
        $this->selectedScan = null;
        $this->showDetails = false;
    }

    public function getScansProperty()
    {
        return $this->server->securityScans()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function getLatestScanProperty()
    {
        return $this->server->latestSecurityScan;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.security.security-scan-dashboard');
    }
}
