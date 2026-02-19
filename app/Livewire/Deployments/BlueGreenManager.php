<?php

declare(strict_types=1);

namespace App\Livewire\Deployments;

use App\Models\BlueGreenEnvironment;
use App\Models\Project;
use App\Services\BlueGreen\BlueGreenDeploymentService;
use App\Services\BlueGreen\BlueGreenHealthCheckService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BlueGreenManager extends Component
{
    public Project $project;

    public bool $showConfigModal = false;

    public string $statusMessage = '';

    public string $statusType = 'info';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function initialize(): void
    {
        try {
            /** @var BlueGreenDeploymentService $service */
            $service = app(BlueGreenDeploymentService::class);
            $service->initialize($this->project);
            $this->project->refresh();
            $this->statusMessage = 'Blue-green environments initialized successfully.';
            $this->statusType = 'success';
            $this->dispatch('notification', type: 'success', message: 'Blue-green environments initialized.');
        } catch (\Exception $e) {
            $this->statusMessage = 'Failed to initialize: ' . $e->getMessage();
            $this->statusType = 'error';
            $this->dispatch('notification', type: 'error', message: 'Initialization failed: ' . $e->getMessage());
        }
    }

    public function switchTraffic(): void
    {
        try {
            /** @var BlueGreenDeploymentService $service */
            $service = app(BlueGreenDeploymentService::class);
            $result = $service->switchTraffic($this->project);
            $this->project->refresh();
            $this->statusMessage = "Traffic switched to {$result->environment} environment.";
            $this->statusType = 'success';
            $this->dispatch('notification', type: 'success', message: "Traffic switched to {$result->environment}.");
        } catch (\Exception $e) {
            $this->statusMessage = 'Switch failed: ' . $e->getMessage();
            $this->statusType = 'error';
            $this->dispatch('notification', type: 'error', message: 'Traffic switch failed: ' . $e->getMessage());
        }
    }

    public function rollback(): void
    {
        try {
            /** @var BlueGreenDeploymentService $service */
            $service = app(BlueGreenDeploymentService::class);
            $result = $service->rollback($this->project);
            $this->project->refresh();
            $this->statusMessage = "Rolled back to {$result->environment} environment.";
            $this->statusType = 'success';
            $this->dispatch('notification', type: 'success', message: "Rolled back to {$result->environment}.");
        } catch (\Exception $e) {
            $this->statusMessage = 'Rollback failed: ' . $e->getMessage();
            $this->statusType = 'error';
            $this->dispatch('notification', type: 'error', message: 'Rollback failed: ' . $e->getMessage());
        }
    }

    public function checkHealth(string $environment): void
    {
        try {
            /** @var BlueGreenHealthCheckService $healthService */
            $healthService = app(BlueGreenHealthCheckService::class);

            /** @var BlueGreenEnvironment $env */
            $env = $this->project->blueGreenEnvironments()->where('environment', $environment)->firstOrFail();
            $result = $healthService->checkHealth($this->project, $env);

            $status = $result['healthy'] ? 'healthy' : 'unhealthy';
            $this->dispatch(
                'notification',
                type: $result['healthy'] ? 'success' : 'warning',
                message: "{$environment} environment is {$status}: {$result['message']}"
            );
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Health check failed: ' . $e->getMessage());
        }
    }

    public function disable(): void
    {
        try {
            /** @var BlueGreenDeploymentService $service */
            $service = app(BlueGreenDeploymentService::class);
            $service->disable($this->project);
            $this->project->refresh();
            $this->statusMessage = 'Blue-green deployment disabled.';
            $this->statusType = 'info';
            $this->dispatch('notification', type: 'info', message: 'Blue-green deployment disabled.');
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to disable: ' . $e->getMessage());
        }
    }

    /**
     * @return array{blue: BlueGreenEnvironment|null, green: BlueGreenEnvironment|null, active: string|null}
     */
    public function getEnvironmentStatusProperty(): array
    {
        if ($this->project->deployment_strategy !== 'blue_green') {
            return ['blue' => null, 'green' => null, 'active' => null];
        }

        /** @var BlueGreenDeploymentService $service */
        $service = app(BlueGreenDeploymentService::class);

        return $service->getStatus($this->project);
    }

    public function render(): View
    {
        return view('livewire.deployments.blue-green-manager', [
            'environmentStatus' => $this->getEnvironmentStatusProperty(),
            'isEnabled' => $this->project->deployment_strategy === 'blue_green',
        ]);
    }
}
