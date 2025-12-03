<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\Server;
use App\Services\ServerMetricsService;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class ServerMetricsDashboard extends Component
{
    public Server $server;
    public string $period = '1h';
    public $metrics = [];
    public $latestMetric = null;
    public bool $isCollecting = false;
    public bool $liveMode = true;
    public string $processView = 'cpu'; // 'cpu' or 'memory'
    public array $topProcesses = [];
    public bool $isLoadingProcesses = false;

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
        $this->loadTopProcesses();
    }

    public function loadMetrics()
    {
        $this->metrics = $this->metricsService->getMetricsHistory($this->server, $this->period);
        $this->latestMetric = $this->metricsService->getLatestMetrics($this->server);
    }

    #[Computed]
    public function chartData(): array
    {
        $metrics = $this->metrics->sortBy('recorded_at')->values();

        return [
            'labels' => $metrics->map(fn($m) => $m->recorded_at->format('H:i'))->toArray(),
            'cpu' => $metrics->map(fn($m) => round($m->cpu_usage, 1))->toArray(),
            'memory' => $metrics->map(fn($m) => round($m->memory_usage, 1))->toArray(),
            'disk' => $metrics->map(fn($m) => round($m->disk_usage, 1))->toArray(),
            'load' => $metrics->map(fn($m) => round($m->load_average_1, 2))->toArray(),
        ];
    }

    #[Computed]
    public function alertStatus(): array
    {
        if (!$this->latestMetric) {
            return ['status' => 'unknown', 'alerts' => []];
        }

        $alerts = [];
        $status = 'healthy';

        if ($this->latestMetric->cpu_usage >= 90) {
            $alerts[] = ['type' => 'critical', 'metric' => 'CPU', 'value' => $this->latestMetric->cpu_usage];
            $status = 'critical';
        } elseif ($this->latestMetric->cpu_usage >= 80) {
            $alerts[] = ['type' => 'warning', 'metric' => 'CPU', 'value' => $this->latestMetric->cpu_usage];
            if ($status !== 'critical') $status = 'warning';
        }

        if ($this->latestMetric->memory_usage >= 85) {
            $alerts[] = ['type' => 'critical', 'metric' => 'Memory', 'value' => $this->latestMetric->memory_usage];
            $status = 'critical';
        } elseif ($this->latestMetric->memory_usage >= 75) {
            $alerts[] = ['type' => 'warning', 'metric' => 'Memory', 'value' => $this->latestMetric->memory_usage];
            if ($status !== 'critical') $status = 'warning';
        }

        if ($this->latestMetric->disk_usage >= 90) {
            $alerts[] = ['type' => 'critical', 'metric' => 'Disk', 'value' => $this->latestMetric->disk_usage];
            $status = 'critical';
        } elseif ($this->latestMetric->disk_usage >= 80) {
            $alerts[] = ['type' => 'warning', 'metric' => 'Disk', 'value' => $this->latestMetric->disk_usage];
            if ($status !== 'critical') $status = 'warning';
        }

        return ['status' => $status, 'alerts' => $alerts];
    }

    public function refreshMetrics()
    {
        $this->isCollecting = true;

        try {
            $metric = $this->metricsService->collectMetrics($this->server);

            if ($metric) {
                $this->dispatch('notification', type: 'success', message: 'Metrics collected successfully!');
                $this->loadMetrics();
                $this->dispatch('metrics-chart-update', data: $this->chartData);
            } else {
                $this->dispatch('notification', type: 'error', message: 'Failed to collect metrics. Check server connectivity.');
            }
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to collect metrics: ' . $e->getMessage());
        }

        $this->isCollecting = false;
    }

    public function setPeriod(string $period)
    {
        $this->period = $period;
        $this->loadMetrics();
        $this->dispatch('metrics-chart-update', data: $this->chartData);
    }

    public function toggleLiveMode()
    {
        $this->liveMode = !$this->liveMode;
    }

    public function loadTopProcesses()
    {
        $this->isLoadingProcesses = true;

        try {
            if ($this->processView === 'cpu') {
                $this->topProcesses = $this->metricsService->getTopProcessesByCPU($this->server, 10);
            } else {
                $this->topProcesses = $this->metricsService->getTopProcessesByMemory($this->server, 10);
            }
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to load processes: ' . $e->getMessage());
            $this->topProcesses = [];
        }

        $this->isLoadingProcesses = false;
    }

    public function switchProcessView(string $view)
    {
        if (in_array($view, ['cpu', 'memory'])) {
            $this->processView = $view;
            $this->loadTopProcesses();
        }
    }

    public function refreshProcesses()
    {
        $this->loadTopProcesses();
        $this->dispatch('notification', type: 'success', message: 'Process list refreshed!');
    }

    #[On('metrics-updated')]
    public function onMetricsUpdated()
    {
        $this->loadMetrics();
        $this->dispatch('metrics-chart-update', data: $this->chartData);
    }

    #[On('refresh-processes')]
    public function onRefreshProcesses()
    {
        $this->loadTopProcesses();
    }

    public function getListeners(): array
    {
        return [
            "echo:server-metrics.{$this->server->id},ServerMetricsUpdated" => 'handleRealtimeMetrics',
        ];
    }

    public function handleRealtimeMetrics($event)
    {
        // Reload metrics when real-time update arrives
        $this->loadMetrics();
        $this->dispatch('metrics-chart-update', data: $this->chartData);

        // Show alerts if any
        if (!empty($event['alerts'])) {
            foreach ($event['alerts'] as $alert) {
                $type = $alert['type'] === 'critical' ? 'error' : 'warning';
                $this->dispatch('notification', type: $type, message: $alert['message']);
            }
        }
    }

    public function render()
    {
        return view('livewire.servers.server-metrics-dashboard');
    }
}
