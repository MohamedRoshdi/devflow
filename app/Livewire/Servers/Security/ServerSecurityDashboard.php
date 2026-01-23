<?php

declare(strict_types=1);

namespace App\Livewire\Servers\Security;

use App\Models\Server;
use App\Services\Security\SecurityScoreService;
use App\Services\Security\ServerSecurityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class ServerSecurityDashboard extends Component
{
    use AuthorizesRequests;

    public Server $server;

    /** @var array<string, mixed>|null */
    public ?array $securityOverview = null;

    public bool $isLoading = true;

    public bool $isScanning = false;

    public ?string $flashMessage = null;

    public ?string $flashType = null;

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
        $this->loadSecurityStatus();
    }

    /**
     * Placeholder shown while lazy component loads
     */
    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="flex items-center justify-center min-h-[400px]">
            <div class="flex flex-col items-center gap-4">
                <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-500 dark:text-gray-400">Loading security status...</span>
            </div>
        </div>
        HTML;
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
