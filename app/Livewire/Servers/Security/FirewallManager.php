<?php

declare(strict_types=1);

namespace App\Livewire\Servers\Security;

use App\Models\Server;
use App\Services\Security\FirewallService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class FirewallManager extends Component
{
    use AuthorizesRequests;

    public Server $server;

    public bool $ufwInstalled = false;

    public bool $ufwEnabled = false;

    /** @var array<int, array<string, mixed>> */
    public array $rules = [];

    public bool $isLoading = false;

    public bool $showAddRuleModal = false;

    public bool $showConfirmDisable = false;

    // Add rule form
    public string $rulePort = '';

    public string $ruleProtocol = 'tcp';

    public string $ruleAction = 'allow';

    public string $ruleFromIp = '';

    public string $ruleDescription = '';

    public ?string $flashMessage = null;

    public ?string $flashType = null;

    // Debug info
    public ?string $rawOutput = null;

    public ?string $statusMessage = null;

    /** @var array<string, string> */
    protected $rules_validation = [
        'rulePort' => 'required|string|max:20',
        'ruleProtocol' => 'required|in:tcp,udp,any',
        'ruleAction' => 'required|in:allow,deny,reject,limit',
        'ruleFromIp' => 'nullable|string|max:45',
        'ruleDescription' => 'nullable|string|max:255',
    ];

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
        $this->loadFirewallStatus();
    }

    public function loadFirewallStatus(): void
    {
        $this->isLoading = true;

        try {
            $service = app(FirewallService::class);
            $status = $service->getUfwStatus($this->server);

            $this->ufwInstalled = $status['installed'] ?? false;
            $this->ufwEnabled = $status['enabled'] ?? false;
            $this->rawOutput = $status['raw_output'] ?? null;
            $this->statusMessage = $status['message'] ?? null;

            // Get numbered rules for deletion
            if ($this->ufwEnabled) {
                $rulesResult = $service->getRulesNumbered($this->server);
                $this->rules = $rulesResult['rules'] ?? [];
            } else {
                $this->rules = [];
            }

            // If there's an error, show it
            if (isset($status['error'])) {
                $this->flashMessage = 'Connection issue: '.$status['error'];
                $this->flashType = 'error';
            }

            $this->server->refresh();
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to load firewall status: '.$e->getMessage();
            $this->flashType = 'error';
        }

        $this->isLoading = false;
    }

    public function enableFirewall(): void
    {
        try {
            $service = app(FirewallService::class);
            $result = $service->enableUfw($this->server);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->loadFirewallStatus();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to enable firewall: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function confirmDisableFirewall(): void
    {
        $this->showConfirmDisable = true;
    }

    public function disableFirewall(): void
    {
        $this->showConfirmDisable = false;

        try {
            $service = app(FirewallService::class);
            $result = $service->disableUfw($this->server);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->loadFirewallStatus();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to disable firewall: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function openAddRuleModal(): void
    {
        $this->resetRuleForm();
        $this->showAddRuleModal = true;
    }

    public function closeAddRuleModal(): void
    {
        $this->showAddRuleModal = false;
        $this->resetRuleForm();
    }

    public function addRule(): void
    {
        $this->validate($this->rules_validation);

        try {
            $service = app(FirewallService::class);
            $result = $service->addRule(
                $this->server,
                $this->rulePort,
                $this->ruleProtocol,
                $this->ruleAction,
                $this->ruleFromIp ?: null,
                $this->ruleDescription ?: null
            );

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->closeAddRuleModal();
                $this->loadFirewallStatus();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to add rule: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function deleteRule(int $ruleNumber): void
    {
        try {
            $service = app(FirewallService::class);
            $result = $service->deleteRule($this->server, $ruleNumber);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->loadFirewallStatus();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to delete rule: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function installUfw(): void
    {
        try {
            $service = app(FirewallService::class);
            $result = $service->installUfw($this->server);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
                $this->loadFirewallStatus();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to install UFW: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    protected function resetRuleForm(): void
    {
        $this->rulePort = '';
        $this->ruleProtocol = 'tcp';
        $this->ruleAction = 'allow';
        $this->ruleFromIp = '';
        $this->ruleDescription = '';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.security.firewall-manager');
    }
}
