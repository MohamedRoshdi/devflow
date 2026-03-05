<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\CronConfigService;
use App\Services\NginxConfigService;
use App\Services\PhpFpmPoolService;
use App\Services\SupervisorConfigService;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class InfrastructureManager extends Component
{
    #[Locked]
    public int $projectId;

    public Project $project;

    public bool $nginxInstalled = false;

    public bool $phpFpmInstalled = false;

    public bool $supervisorInstalled = false;

    public bool $cronInstalled = false;

    public bool $statusLoaded = false;

    /** @var array<int, array{name: string, status: string, pid: string, uptime: string}> */
    public array $supervisorWorkers = [];

    protected NginxConfigService $nginxService;

    protected PhpFpmPoolService $phpFpmService;

    protected SupervisorConfigService $supervisorService;

    protected CronConfigService $cronService;

    public function boot(
        NginxConfigService $nginxService,
        PhpFpmPoolService $phpFpmService,
        SupervisorConfigService $supervisorService,
        CronConfigService $cronService,
    ): void {
        $this->nginxService = $nginxService;
        $this->phpFpmService = $phpFpmService;
        $this->supervisorService = $supervisorService;
        $this->cronService = $cronService;
    }

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->projectId = $project->id;
    }

    /**
     * Load the installation status for all infrastructure services.
     */
    public function loadStatus(): void
    {
        $server = $this->project->server;

        if ($server === null) {
            $this->statusLoaded = true;

            return;
        }

        try {
            $this->phpFpmInstalled = $this->phpFpmService->isInstalled($server, $this->project);
        } catch (\Throwable) {
            $this->phpFpmInstalled = false;
        }

        try {
            $this->cronInstalled = $this->cronService->isInstalled($server, $this->project);
        } catch (\Throwable) {
            $this->cronInstalled = false;
        }

        try {
            $this->supervisorInstalled = $this->supervisorService->isInstalled($server, $this->project);
            if ($this->supervisorInstalled) {
                $this->supervisorWorkers = $this->supervisorService->getWorkerStatus($server, $this->project);
            }
        } catch (\Throwable) {
            $this->supervisorInstalled = false;
            $this->supervisorWorkers = [];
        }

        try {
            $this->nginxInstalled = $this->nginxService->isInstalled($server, $this->project);
        } catch (\Throwable) {
            $this->nginxInstalled = false;
        }

        $this->statusLoaded = true;
    }

    /**
     * Install the Nginx vhost for the project's primary domain.
     */
    public function installNginx(): void
    {
        $server = $this->project->server;

        if ($server === null) {
            $this->dispatch('notification', type: 'error', message: 'No server assigned to this project.');

            return;
        }

        $domain = $this->project->domains()->first();

        if ($domain === null) {
            $this->dispatch('notification', type: 'error', message: 'No domain configured for this project. Please add a domain first.');

            return;
        }

        try {
            $this->nginxService->installVhost($server, $this->project, $domain);
            $this->nginxInstalled = true;
            $this->dispatch('notification', type: 'success', message: 'Nginx vhost installed successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to install Nginx vhost: '.$e->getMessage());
        }
    }

    /**
     * Remove the Nginx vhost for the project.
     */
    public function removeNginx(): void
    {
        $server = $this->project->server;

        if ($server === null) {
            $this->dispatch('notification', type: 'error', message: 'No server assigned to this project.');

            return;
        }

        $domain = $this->project->domains()->first();

        if ($domain === null) {
            $this->dispatch('notification', type: 'error', message: 'No domain found for this project.');

            return;
        }

        try {
            $this->nginxService->removeVhost($server, $domain);
            $this->nginxInstalled = false;
            $this->dispatch('notification', type: 'success', message: 'Nginx vhost removed successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to remove Nginx vhost: '.$e->getMessage());
        }
    }

    /**
     * Install the PHP-FPM pool for the project.
     */
    public function installPhpFpm(): void
    {
        $server = $this->project->server;

        if ($server === null) {
            $this->dispatch('notification', type: 'error', message: 'No server assigned to this project.');

            return;
        }

        try {
            $this->phpFpmService->installPool($server, $this->project);
            $this->phpFpmInstalled = true;
            $this->dispatch('notification', type: 'success', message: 'PHP-FPM pool installed successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to install PHP-FPM pool: '.$e->getMessage());
        }
    }

    /**
     * Remove the PHP-FPM pool for the project.
     */
    public function removePhpFpm(): void
    {
        $server = $this->project->server;

        if ($server === null) {
            $this->dispatch('notification', type: 'error', message: 'No server assigned to this project.');

            return;
        }

        try {
            $this->phpFpmService->removePool($server, $this->project);
            $this->phpFpmInstalled = false;
            $this->dispatch('notification', type: 'success', message: 'PHP-FPM pool removed successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to remove PHP-FPM pool: '.$e->getMessage());
        }
    }

    /**
     * Install the Supervisor worker config for the project.
     */
    public function installSupervisor(): void
    {
        $server = $this->project->server;

        if ($server === null) {
            $this->dispatch('notification', type: 'error', message: 'No server assigned to this project.');

            return;
        }

        try {
            $this->supervisorService->installConfig($server, $this->project);
            $this->supervisorInstalled = true;
            $this->supervisorWorkers = $this->supervisorService->getWorkerStatus($server, $this->project);
            $this->dispatch('notification', type: 'success', message: 'Supervisor workers installed successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to install Supervisor config: '.$e->getMessage());
        }
    }

    /**
     * Remove the Supervisor worker config for the project.
     */
    public function removeSupervisor(): void
    {
        $server = $this->project->server;

        if ($server === null) {
            $this->dispatch('notification', type: 'error', message: 'No server assigned to this project.');

            return;
        }

        try {
            $this->supervisorService->removeConfig($server, $this->project);
            $this->supervisorInstalled = false;
            $this->supervisorWorkers = [];
            $this->dispatch('notification', type: 'success', message: 'Supervisor workers removed successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to remove Supervisor config: '.$e->getMessage());
        }
    }

    /**
     * Restart the Supervisor workers for the project.
     */
    public function restartSupervisor(): void
    {
        $server = $this->project->server;

        if ($server === null) {
            $this->dispatch('notification', type: 'error', message: 'No server assigned to this project.');

            return;
        }

        try {
            $this->supervisorService->restartWorkers($server, $this->project);
            $this->supervisorWorkers = $this->supervisorService->getWorkerStatus($server, $this->project);
            $this->dispatch('notification', type: 'success', message: 'Supervisor workers restarted successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to restart Supervisor workers: '.$e->getMessage());
        }
    }

    /**
     * Install the Cron scheduler config for the project.
     */
    public function installCron(): void
    {
        $server = $this->project->server;

        if ($server === null) {
            $this->dispatch('notification', type: 'error', message: 'No server assigned to this project.');

            return;
        }

        try {
            $this->cronService->installConfig($server, $this->project);
            $this->cronInstalled = true;
            $this->dispatch('notification', type: 'success', message: 'Cron scheduler installed successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to install Cron config: '.$e->getMessage());
        }
    }

    /**
     * Remove the Cron scheduler config for the project.
     */
    public function removeCron(): void
    {
        $server = $this->project->server;

        if ($server === null) {
            $this->dispatch('notification', type: 'error', message: 'No server assigned to this project.');

            return;
        }

        try {
            $this->cronService->removeConfig($server, $this->project);
            $this->cronInstalled = false;
            $this->dispatch('notification', type: 'success', message: 'Cron scheduler removed successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to remove Cron config: '.$e->getMessage());
        }
    }

    /**
     * Reload PHP-FPM service on the server.
     */
    public function reloadPhpFpm(): void
    {
        $server = $this->project->server;

        if ($server === null) {
            $this->dispatch('notification', type: 'error', message: 'No server assigned to this project.');

            return;
        }

        try {
            $this->phpFpmService->reloadFpm($server, $this->project);
            $this->dispatch('notification', type: 'success', message: 'PHP-FPM reloaded successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to reload PHP-FPM: '.$e->getMessage());
        }
    }

    /**
     * Test the Nginx configuration on the server.
     */
    public function testNginxConfig(): void
    {
        $server = $this->project->server;

        if ($server === null) {
            $this->dispatch('notification', type: 'error', message: 'No server assigned to this project.');

            return;
        }

        try {
            $valid = $this->nginxService->testConfig($server);

            if ($valid) {
                $this->dispatch('notification', type: 'success', message: 'Nginx configuration test passed.');
            } else {
                $this->dispatch('notification', type: 'error', message: 'Nginx configuration test failed. Check the server logs for details.');
            }
        } catch (\Throwable $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to test Nginx config: '.$e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.projects.infrastructure-manager');
    }
}
