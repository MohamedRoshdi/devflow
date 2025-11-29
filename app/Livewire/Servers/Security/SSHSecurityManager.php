<?php

declare(strict_types=1);

namespace App\Livewire\Servers\Security;

use Livewire\Component;
use App\Models\Server;
use App\Services\Security\SSHSecurityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SSHSecurityManager extends Component
{
    use AuthorizesRequests;

    public Server $server;
    public bool $isLoading = false;
    public bool $showConfigModal = false;
    public bool $showHardenConfirm = false;

    // SSH Configuration
    public int $port = 22;
    public bool $rootLoginEnabled = true;
    public bool $passwordAuthEnabled = true;
    public bool $pubkeyAuthEnabled = true;
    public int $maxAuthTries = 6;

    public ?string $flashMessage = null;
    public ?string $flashType = null;

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
        $this->loadSSHConfig();
    }

    public function loadSSHConfig(): void
    {
        $this->isLoading = true;

        try {
            $service = app(SSHSecurityService::class);
            $result = $service->getCurrentConfig($this->server);

            if ($result['success']) {
                $config = $result['config'];
                $this->port = $config['port'];
                $this->rootLoginEnabled = $config['root_login_enabled'];
                $this->passwordAuthEnabled = $config['password_auth_enabled'];
                $this->pubkeyAuthEnabled = $config['pubkey_auth_enabled'];
                $this->maxAuthTries = $config['max_auth_tries'];
            } else {
                $this->flashMessage = 'Failed to load SSH configuration';
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed to load SSH config: ' . $e->getMessage();
            $this->flashType = 'error';
        }

        $this->isLoading = false;
    }

    public function toggleRootLogin(): void
    {
        try {
            $service = app(SSHSecurityService::class);
            $result = $service->toggleRootLogin($this->server, !$this->rootLoginEnabled);

            if ($result['success']) {
                $this->rootLoginEnabled = !$this->rootLoginEnabled;
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed: ' . $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function togglePasswordAuth(): void
    {
        try {
            $service = app(SSHSecurityService::class);
            $result = $service->togglePasswordAuth($this->server, !$this->passwordAuthEnabled);

            if ($result['success']) {
                $this->passwordAuthEnabled = !$this->passwordAuthEnabled;
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed: ' . $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function changePort(): void
    {
        try {
            $service = app(SSHSecurityService::class);
            $result = $service->changePort($this->server, $this->port);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed: ' . $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function hardenSSH(): void
    {
        $this->showHardenConfirm = false;

        try {
            $service = app(SSHSecurityService::class);
            $result = $service->hardenSSH($this->server);

            if ($result['success']) {
                $this->flashMessage = $result['message'] . ' ' . ($result['warning'] ?? '');
                $this->flashType = 'success';
                $this->loadSSHConfig();
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed: ' . $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function restartSSH(): void
    {
        try {
            $service = app(SSHSecurityService::class);
            $result = $service->restartSSHService($this->server);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
            } else {
                $this->flashMessage = $result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Failed: ' . $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function render()
    {
        return view('livewire.servers.security.s-s-h-security-manager');
    }
}
