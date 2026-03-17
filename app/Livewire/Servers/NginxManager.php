<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Livewire\Concerns\WithPasswordConfirmation;
use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;

class NginxManager extends Component
{
    use AuthorizesRequests;
    use ExecutesRemoteCommands;
    use WithPasswordConfirmation;

    #[Locked]
    public Server $server;

    /** @var list<string> */
    public array $sites = [];

    /** @var list<string> */
    public array $enabledSites = [];

    public string $selectedSite = '';

    public string $siteConfig = '';

    public string $nginxStatus = 'unknown';

    public string $configTestResult = '';

    public bool $configTestPassed = false;

    public bool $showConfigModal = false;

    public string $siteToDelete = '';

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);

        $this->server = $server;
        $this->loadSites();
        $this->detectNginxStatus();
    }

    public function loadSites(): void
    {
        try {
            $availableOutput = $this->getRemoteOutput(
                $this->server,
                'ls /etc/nginx/sites-available/ 2>/dev/null || echo ""',
                false
            );

            $enabledOutput = $this->getRemoteOutput(
                $this->server,
                'ls /etc/nginx/sites-enabled/ 2>/dev/null || echo ""',
                false
            );

            $this->sites = array_values(array_filter(
                array_map('trim', explode("\n", trim($availableOutput))),
                fn (string $s) => $s !== ''
            ));

            $this->enabledSites = array_values(array_filter(
                array_map('trim', explode("\n", trim($enabledOutput))),
                fn (string $s) => $s !== ''
            ));
        } catch (\Exception $e) {
            Log::error('Failed to load Nginx sites', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load sites: '.$e->getMessage());
        }
    }

    private function detectNginxStatus(): void
    {
        try {
            $result = $this->executeRemoteCommand(
                $this->server,
                'systemctl is-active nginx 2>/dev/null || service nginx status 2>/dev/null | grep -q running && echo active || echo inactive',
                false
            );

            $output = trim($result->output());
            $this->nginxStatus = str_contains($output, 'active') ? 'active' : 'inactive';
        } catch (\Exception $e) {
            $this->nginxStatus = 'unknown';
        }
    }

    public function viewConfig(string $site): void
    {
        // Validate site name — only allow filesystem-safe names
        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $site)) {
            session()->flash('error', 'Invalid site name.');

            return;
        }

        try {
            $config = $this->getRemoteOutput(
                $this->server,
                'cat /etc/nginx/sites-available/'.escapeshellarg($site).' 2>/dev/null',
                false
            );

            $this->selectedSite = $site;
            $this->siteConfig = $config ?: '(empty or unreadable)';
            $this->showConfigModal = true;
        } catch (\Exception $e) {
            Log::error('Failed to read Nginx site config', [
                'server_id' => $this->server->id,
                'site' => $site,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to read config for '.$site.': '.$e->getMessage());
        }
    }

    public function closeConfigModal(): void
    {
        $this->showConfigModal = false;
        $this->selectedSite = '';
        $this->siteConfig = '';
    }

    public function testConfig(): void
    {
        $this->authorize('update', $this->server);

        try {
            $result = $this->executeRemoteCommand(
                $this->server,
                'sudo nginx -t 2>&1',
                false
            );

            $output = trim($result->output().' '.$result->errorOutput());
            $this->configTestPassed = $result->successful() || str_contains($output, 'test is successful');
            $this->configTestResult = $output ?: ($this->configTestPassed ? 'Configuration OK' : 'Test failed');
        } catch (\Exception $e) {
            $this->configTestPassed = false;
            $this->configTestResult = $e->getMessage();
        }
    }

    public function reloadNginx(): void
    {
        $this->authorize('update', $this->server);

        try {
            $this->executeRemoteCommand($this->server, 'sudo systemctl reload nginx');
            session()->flash('message', 'Nginx reloaded successfully.');
            $this->detectNginxStatus();
        } catch (\Exception $e) {
            Log::error('Failed to reload Nginx', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to reload Nginx: '.$e->getMessage());
        }
    }

    public function restartNginx(): void
    {
        $this->authorize('update', $this->server);

        try {
            $this->executeRemoteCommand($this->server, 'sudo systemctl restart nginx');
            session()->flash('message', 'Nginx restarted successfully.');
            $this->detectNginxStatus();
        } catch (\Exception $e) {
            Log::error('Failed to restart Nginx', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to restart Nginx: '.$e->getMessage());
        }
    }

    public function enableSite(string $site): void
    {
        $this->authorize('update', $this->server);

        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $site)) {
            session()->flash('error', 'Invalid site name.');

            return;
        }

        try {
            $available = '/etc/nginx/sites-available/'.escapeshellarg($site);
            $enabled = '/etc/nginx/sites-enabled/'.escapeshellarg($site);

            $this->executeRemoteCommand(
                $this->server,
                "sudo ln -sf {$available} {$enabled}"
            );

            session()->flash('message', "Site '{$site}' enabled.");
            $this->loadSites();
        } catch (\Exception $e) {
            Log::error('Failed to enable Nginx site', [
                'server_id' => $this->server->id,
                'site' => $site,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', "Failed to enable '{$site}': ".$e->getMessage());
        }
    }

    public function disableSite(string $site): void
    {
        $this->authorize('update', $this->server);

        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $site)) {
            session()->flash('error', 'Invalid site name.');

            return;
        }

        try {
            $enabled = '/etc/nginx/sites-enabled/'.escapeshellarg($site);

            $this->executeRemoteCommand(
                $this->server,
                "sudo rm -f {$enabled}"
            );

            session()->flash('message', "Site '{$site}' disabled.");
            $this->loadSites();
        } catch (\Exception $e) {
            Log::error('Failed to disable Nginx site', [
                'server_id' => $this->server->id,
                'site' => $site,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', "Failed to disable '{$site}': ".$e->getMessage());
        }
    }

    public function confirmDeleteSite(string $site): void
    {
        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $site)) {
            session()->flash('error', 'Invalid site name.');

            return;
        }

        $this->siteToDelete = $site;
        $this->confirmDestructiveAction('deleteSite', $site, "Permanently delete the Nginx site config for '{$site}' from sites-available and sites-enabled.");
    }

    public function deleteSite(string $site = ''): void
    {
        $this->authorize('update', $this->server);

        if ($site === '') {
            $site = $this->siteToDelete;
        }

        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $site)) {
            session()->flash('error', 'Invalid site name.');

            return;
        }

        try {
            $available = '/etc/nginx/sites-available/'.escapeshellarg($site);
            $enabled = '/etc/nginx/sites-enabled/'.escapeshellarg($site);

            $this->executeRemoteCommand($this->server, "sudo rm -f {$enabled}", false);
            $this->executeRemoteCommand($this->server, "sudo rm -f {$available}");

            session()->flash('message', "Site '{$site}' deleted.");
            $this->siteToDelete = '';
            $this->loadSites();
        } catch (\Exception $e) {
            Log::error('Failed to delete Nginx site', [
                'server_id' => $this->server->id,
                'site' => $site,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', "Failed to delete '{$site}': ".$e->getMessage());
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.nginx-manager')->layout('layouts.app');
    }
}
