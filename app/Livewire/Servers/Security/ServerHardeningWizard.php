<?php

declare(strict_types=1);

namespace App\Livewire\Servers\Security;

use App\Models\Server;
use App\Services\Security\ServerHardeningService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

class ServerHardeningWizard extends Component
{
    use AuthorizesRequests;

    public Server $server;

    public int $currentStep = 1;

    public int $totalSteps = 4;

    // Step 1: SSH Hardening
    public bool $changeSSHPort = false;

    public int $newSSHPort = 2222;

    public bool $hardenSSHConfig = true;

    // Step 2: Firewall
    public bool $setupFirewall = true;

    // Step 3: Fail2ban
    public bool $installFail2ban = true;

    public int $fail2banMaxRetry = 3;

    public int $fail2banBanTime = 86400;

    // Step 4: Kernel Hardening
    public bool $hardenSysctl = true;

    public bool $disableUnused = true;

    // Results
    public bool $isHardening = false;

    /** @var array<string, array<string, mixed>> */
    public array $results = [];

    public ?string $flashMessage = null;

    public ?string $flashType = null;

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
    }

    public function nextStep(): void
    {
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            $this->currentStep = $step;
        }
    }

    public function applyHardening(): void
    {
        $this->isHardening = true;
        $this->results = [];

        try {
            $service = app(ServerHardeningService::class);

            $options = [
                'firewall' => $this->setupFirewall,
                'fail2ban' => $this->installFail2ban,
                'sysctl' => $this->hardenSysctl,
                'disable_unused' => $this->disableUnused,
            ];

            if ($this->changeSSHPort) {
                $options['ssh_port'] = $this->newSSHPort;
            }

            if ($this->installFail2ban) {
                $options['fail2ban_maxretry'] = $this->fail2banMaxRetry;
                $options['fail2ban_bantime'] = $this->fail2banBanTime;
            }

            $this->results = $service->hardenServer($this->server, $options);

            $allSuccess = collect($this->results)->every(fn (array $r): bool => $r['success'] ?? false);

            $this->flashMessage = $allSuccess
                ? 'Server hardened successfully!'
                : 'Hardening completed with some errors. Review results.';
            $this->flashType = $allSuccess ? 'success' : 'warning';

            $this->server->refresh();
        } catch (\Exception $e) {
            $this->flashMessage = 'Hardening failed: '.$e->getMessage();
            $this->flashType = 'error';
        }

        $this->isHardening = false;
    }

    /**
     * @return array<string, mixed>
     */
    public function getHardeningStatusProperty(): array
    {
        try {
            $service = app(ServerHardeningService::class);

            return $service->getHardeningStatus($this->server);
        } catch (\Exception) {
            return [];
        }
    }

    public function render(): View
    {
        return view('livewire.servers.security.server-hardening-wizard');
    }
}
