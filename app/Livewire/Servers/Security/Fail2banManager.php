<?php

declare(strict_types=1);

namespace App\Livewire\Servers\Security;

use Livewire\Component;
use App\Models\Server;
use App\Services\Security\Fail2banService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Fail2banManager extends Component
{
    use AuthorizesRequests;

    public Server $server;
    public bool $fail2banInstalled = false;
    public bool $fail2banEnabled = false;
    public array $jails = [];
    public array $bannedIPs = [];
    public string $selectedJail = 'sshd';
    public bool $isLoading = false;

    public ?string $flashMessage = null;
    public ?string $flashType = null;

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
        $this->loadFail2banStatus();
    }

    public function loadFail2banStatus(): void
    {
        $this->isLoading = true;

        try {
            $service = app(Fail2banService::class);
            $status = $service->getFail2banStatus($this->server);

            $this->fail2banInstalled = $status['installed'] ?? false;
            $this->fail2banEnabled = $status['enabled'] ?? false;
            $this->jails = $status['jails'] ?? [];

            if ($this->fail2banEnabled && !empty($this->jails)) {
                if (!in_array($this->selectedJail, $this->jails)) {
                    $this->selectedJail = $this->jails[0];
                }
                $this->loadBannedIPs();
            }

            $this->server->refresh();
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to load Fail2ban status: ' . $e->getMessage();
            $this->flashType = 'error';
        }

        $this->isLoading = false;
    }

    public function loadBannedIPs(): void
    {
        try {
            $service = app(Fail2banService::class);
            $result = $service->getBannedIPs($this->server, $this->selectedJail);

            if ($result['success']) {
                $this->bannedIPs = $result['banned_ips'][$this->selectedJail] ?? [];
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to load banned IPs: ' . $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function selectJail(string $jail): void
    {
        $this->selectedJail = $jail;
        $this->loadBannedIPs();
    }

    public function unbanIP(string $ip): void
    {
        try {
            $service = app(Fail2banService::class);
            $result = $service->unbanIP($this->server, $ip, $this->selectedJail);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->loadBannedIPs();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to unban IP: ' . $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function startFail2ban(): void
    {
        try {
            $service = app(Fail2banService::class);
            $result = $service->startFail2ban($this->server);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->loadFail2banStatus();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to start Fail2ban: ' . $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function stopFail2ban(): void
    {
        try {
            $service = app(Fail2banService::class);
            $result = $service->stopFail2ban($this->server);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->loadFail2banStatus();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to stop Fail2ban: ' . $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function installFail2ban(): void
    {
        try {
            $service = app(Fail2banService::class);
            $result = $service->installFail2ban($this->server);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->loadFail2banStatus();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to install Fail2ban: ' . $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function render()
    {
        return view('livewire.servers.security.fail2ban-manager');
    }
}
