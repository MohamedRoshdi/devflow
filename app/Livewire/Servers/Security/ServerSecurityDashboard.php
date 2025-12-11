<?php

declare(strict_types=1);

namespace App\Livewire\Servers\Security;

use App\Models\Server;
use App\Services\Security\SecurityScoreService;
use App\Services\Security\ServerSecurityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ServerSecurityDashboard extends Component
{
    use AuthorizesRequests;

    public Server $server;

    /** @var array<string, mixed>|null */
    public ?array $securityOverview = null;

    public bool $isLoading = false;

    public bool $isScanning = false;

    public ?string $flashMessage = null;

    public ?string $flashType = null;

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
        $this->loadSecurityStatus();
    }

    public function loadSecurityStatus(): void
    {
        $this->isLoading = true;

        try {
            $service = app(ServerSecurityService::class);
            $this->securityOverview = $service->getSecurityOverview($this->server);
            $this->server->refresh();
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to load security status: '.$e->getMessage();
            $this->flashType = 'error';
        }

        $this->isLoading = false;
    }

    public function runSecurityScan(): void
    {
        $this->isScanning = true;

        try {
            $scoreService = app(SecurityScoreService::class);
            $scan = $scoreService->runSecurityScan($this->server);

            $this->server->refresh();
            $this->loadSecurityStatus();

            $this->flashMessage = "Security scan completed. Score: {$scan->score}/100";
            $this->flashType = 'success';
        } catch (\Exception $e) {
            $this->flashMessage = 'Security scan failed: '.$e->getMessage();
            $this->flashType = 'error';
        }

        $this->isScanning = false;
    }

    public function refreshStatus(): void
    {
        $this->loadSecurityStatus();
        $this->flashMessage = 'Security status refreshed';
        $this->flashType = 'success';
    }

    public function getRecentEventsProperty()
    {
        return $this->server->securityEvents()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.security.server-security-dashboard');
    }
}
