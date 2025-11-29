<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\Server;
use App\Services\ServerMetricsService;
use Livewire\Attributes\On;

class ServerMetricsDashboard extends Component
{
    public Server $server;
    public string $period = '24h';
    public $metrics = [];
    public $latestMetric = null;
    public bool $isCollecting = false;

    protected ServerMetricsService $metricsService;

    public function boot(ServerMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    public function mount(Server $server)
    {
        // All servers are shared across all users
        $this->server = $server;
        $this->loadMetrics();
    }

    public function loadMetrics()
    {
        $this->metrics = $this->metricsService->getMetricsHistory($this->server, $this->period);
        $this->latestMetric = $this->metricsService->getLatestMetrics($this->server);
    }

    public function refreshMetrics()
    {
        $this->isCollecting = true;

        try {
            $metric = $this->metricsService->collectMetrics($this->server);

            if ($metric) {
                session()->flash('message', 'Metrics collected successfully!');
                $this->loadMetrics();
                $this->dispatch('metrics-updated');
            } else {
                session()->flash('error', 'Failed to collect metrics. Check server connectivity.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to collect metrics: ' . $e->getMessage());
        }

        $this->isCollecting = false;
    }

    public function setPeriod(string $period)
    {
        $this->period = $period;
        $this->loadMetrics();
    }

    #[On('metrics-updated')]
    public function onMetricsUpdated()
    {
        $this->loadMetrics();
    }

    public function render()
    {
        return view('livewire.servers.server-metrics-dashboard');
    }
}
