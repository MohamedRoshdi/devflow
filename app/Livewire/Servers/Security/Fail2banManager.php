<?php

declare(strict_types=1);

namespace App\Livewire\Servers\Security;

use App\Models\Server;
use App\Services\Security\Fail2banService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Fail2banManager extends Component
{
    use AuthorizesRequests;

    public Server $server;

    public bool $fail2banInstalled = false;

    public bool $fail2banEnabled = false;

    /** @var array<int, string> */
    public array $jails = [];

    /** @var array<int, array<string, mixed>> */
    public array $bannedIPs = [];

    /** @var array<int, string> */
    public array $whitelistedIPs = [];

    public string $selectedJail = 'sshd';

    public string $newWhitelistIP = '';

    public string $manualBanIP = '';

    public string $activeTab = 'banned'; // 'banned', 'whitelist', 'attackers', 'logins'

    public bool $isLoading = false;

    public ?string $flashMessage = null;

    public ?string $flashType = null;

    /** @var array<int, array{ip: string, attempts: int}> */
    public array $topAttackers = [];

    public int $totalAttacks = 0;

    /** @var array<int, array{timestamp: string, ip: string, user: string, type: string}> */
    public array $failedLogins = [];

    /** @var array<int, array{timestamp: string, ip: string, user: string, method: string}> */
    public array $successfulLogins = [];

    /** @var array<int, string> */
    public array $selectedAttackers = [];

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

            if ($this->fail2banEnabled && ! empty($this->jails)) {
                if (! in_array($this->selectedJail, $this->jails)) {
                    $this->selectedJail = $this->jails[0];
                }
                $this->loadBannedIPs();
                $this->loadWhitelistedIPs();
            }

            $this->server->refresh();
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to load Fail2ban status: '.$e->getMessage();
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
            $this->flashMessage = 'Failed to load banned IPs: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function loadWhitelistedIPs(): void
    {
        try {
            $service = app(Fail2banService::class);
            $result = $service->getWhitelistedIPs($this->server, $this->selectedJail);

            if ($result['success']) {
                $this->whitelistedIPs = $result['whitelisted_ips'] ?? [];
            } else {
                $this->whitelistedIPs = [];
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to load whitelisted IPs: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function selectJail(string $jail): void
    {
        $this->selectedJail = $jail;
        $this->loadBannedIPs();
        $this->loadWhitelistedIPs();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;

        // Load data for the selected tab
        if ($tab === 'attackers' && empty($this->topAttackers)) {
            $this->loadTopAttackers();
        } elseif ($tab === 'logins' && empty($this->failedLogins)) {
            $this->loadRecentLogins();
        }
    }

    public function loadTopAttackers(): void
    {
        try {
            $service = app(Fail2banService::class);
            $result = $service->getTopAttackingIPs($this->server);

            if ($result['success']) {
                $this->topAttackers = $result['attackers'];
                $this->totalAttacks = $result['total_attacks'];
            } else {
                $this->flashMessage = $result['error'] ?? 'Failed to load attackers';
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to load top attackers: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function loadRecentLogins(): void
    {
        try {
            $service = app(Fail2banService::class);

            $failedResult = $service->getRecentFailedLogins($this->server);
            if ($failedResult['success']) {
                $this->failedLogins = $failedResult['attempts'];
            }

            $successResult = $service->getRecentSuccessfulLogins($this->server);
            if ($successResult['success']) {
                $this->successfulLogins = $successResult['logins'];
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to load login history: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function banAttacker(string $ip): void
    {
        try {
            $service = app(Fail2banService::class);
            $result = $service->banIP($this->server, $ip, $this->selectedJail);

            if ($result['success']) {
                $this->flashMessage = "Banned IP {$ip}";
                $this->flashType = 'success';
                $this->loadTopAttackers();
                $this->loadBannedIPs();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to ban IP: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function manualBan(): void
    {
        if (empty($this->manualBanIP)) {
            $this->flashMessage = 'Please enter an IP address';
            $this->flashType = 'error';
            return;
        }

        if (! filter_var($this->manualBanIP, FILTER_VALIDATE_IP)) {
            $this->flashMessage = 'Invalid IP address format';
            $this->flashType = 'error';
            return;
        }

        $this->banAttacker($this->manualBanIP);
        $this->manualBanIP = '';
    }

    public function toggleAttackerSelection(string $ip): void
    {
        if (in_array($ip, $this->selectedAttackers)) {
            $this->selectedAttackers = array_values(array_diff($this->selectedAttackers, [$ip]));
        } else {
            $this->selectedAttackers[] = $ip;
        }
    }

    public function selectAllAttackers(): void
    {
        $this->selectedAttackers = array_column($this->topAttackers, 'ip');
    }

    public function deselectAllAttackers(): void
    {
        $this->selectedAttackers = [];
    }

    public function banSelectedAttackers(): void
    {
        if (empty($this->selectedAttackers)) {
            $this->flashMessage = 'No IPs selected';
            $this->flashType = 'error';
            return;
        }

        try {
            $service = app(Fail2banService::class);
            $result = $service->bulkBanIPs($this->server, $this->selectedAttackers, $this->selectedJail);

            $this->flashMessage = $result['message'];
            $this->flashType = $result['success'] ? 'success' : 'error';

            $this->selectedAttackers = [];
            $this->loadTopAttackers();
            $this->loadBannedIPs();
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to ban IPs: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function refreshAttackData(): void
    {
        $this->loadTopAttackers();
        $this->loadRecentLogins();
        $this->flashMessage = 'Attack data refreshed';
        $this->flashType = 'success';
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
            $this->flashMessage = 'Failed to unban IP: '.$e->getMessage();
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
            $this->flashMessage = 'Failed to start Fail2ban: '.$e->getMessage();
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
            $this->flashMessage = 'Failed to stop Fail2ban: '.$e->getMessage();
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
            $this->flashMessage = 'Failed to install Fail2ban: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function addToWhitelist(): void
    {
        if (empty($this->newWhitelistIP)) {
            $this->flashMessage = 'Please enter an IP address';
            $this->flashType = 'error';
            return;
        }

        try {
            $service = app(Fail2banService::class);
            $result = $service->addToWhitelist($this->server, $this->newWhitelistIP, $this->selectedJail);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->newWhitelistIP = '';
                $this->loadWhitelistedIPs();
                $this->loadBannedIPs();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to add IP to whitelist: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function removeFromWhitelist(string $ip): void
    {
        try {
            $service = app(Fail2banService::class);
            $result = $service->removeFromWhitelist($this->server, $ip, $this->selectedJail);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->loadWhitelistedIPs();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to remove IP from whitelist: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function transferToWhitelist(string $ip): void
    {
        try {
            $service = app(Fail2banService::class);
            $result = $service->transferToWhitelist($this->server, $ip, $this->selectedJail);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->loadBannedIPs();
                $this->loadWhitelistedIPs();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to transfer IP to whitelist: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function unbanAllIPs(): void
    {
        try {
            $service = app(Fail2banService::class);
            $result = $service->unbanAllIPs($this->server, $this->selectedJail);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->loadBannedIPs();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to unban all IPs: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.security.fail2ban-manager');
    }
}
