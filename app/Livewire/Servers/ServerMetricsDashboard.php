<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\Server;
use App\Models\ServerMetric;
use App\Services\ServerMetricsService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ServerMetricsDashboard extends Component
{
    public Server $server;

    public string $period = '1h';

    /** @var Collection<int, ServerMetric> */
    public Collection $metrics;

    public ?ServerMetric $latestMetric = null;

    public bool $isCollecting = false;

    public bool $liveMode = true;

    public string $processView = 'cpu'; // 'cpu' or 'memory'

    /** @var array<int, array<string, mixed>> */
    public array $topProcesses = [];

    public bool $isLoadingProcesses = false;

    protected ServerMetricsService $metricsService;

    public function boot(ServerMetricsService $metricsService): void
    {
        $this->metricsService = $metricsService;
    }

    public function mount(Server $server): void
    {
        // All servers are shared across all users
        $this->server = $server;
        $this->metrics = new Collection;
        $this->loadMetrics();
        $this->loadTopProcesses();
    }

    public function loadMetrics(): void
    {
        $this->metrics = $this->metricsService->getMetricsHistory($this->server, $this->period);
        $this->latestMetric = $this->metricsService->getLatestMetrics($this->server);
    }

    /**
     * @return array{labels: array<int, string>, cpu: array<int, float>, memory: array<int, float>, disk: array<int, float>, load: array<int, float>}
     */
    #[Computed]
    public function chartData(): array
    {
        /** @var Collection<int, ServerMetric> $metrics */
        $metrics = $this->metrics->sortBy('recorded_at')->values();

        return [
            'labels' => $metrics->map(fn (ServerMetric $m): string => $m->recorded_at->format('H:i'))->toArray(),
            'cpu' => $metrics->map(fn (ServerMetric $m): float => round((float) $m->cpu_usage, 1))->toArray(),
            'memory' => $metrics->map(fn (ServerMetric $m): float => round((float) $m->memory_usage, 1))->toArray(),
            'disk' => $metrics->map(fn (ServerMetric $m): float => round((float) $m->disk_usage, 1))->toArray(),
            'load' => $metrics->map(fn (ServerMetric $m): float => round((float) $m->load_average_1, 2))->toArray(),
        ];
    }

    /**
     * @return array{status: string, alerts: array<int, array{type: string, metric: string, value: float}>}
     */
    #[Computed]
    public function alertStatus(): array
    {
        if (! $this->latestMetric) {
            return ['status' => 'unknown', 'alerts' => []];
        }

        /** @var array<int, array{type: string, metric: string, value: float}> $alerts */
        $alerts = [];
        $status = 'healthy';

        if ($this->latestMetric->cpu_usage >= 90) {
            $alerts[] = ['type' => 'critical', 'metric' => 'CPU', 'value' => $this->latestMetric->cpu_usage];
            $status = 'critical';
        } elseif ($this->latestMetric->cpu_usage >= 80) {
            $alerts[] = ['type' => 'warning', 'metric' => 'CPU', 'value' => $this->latestMetric->cpu_usage];
            $status = 'warning';
        }

        if ($this->latestMetric->memory_usage >= 85) {
            $alerts[] = ['type' => 'critical', 'metric' => 'Memory', 'value' => $this->latestMetric->memory_usage];
            $status = 'critical';
        } elseif ($this->latestMetric->memory_usage >= 75 && $status !== 'critical') {
            $alerts[] = ['type' => 'warning', 'metric' => 'Memory', 'value' => $this->latestMetric->memory_usage];
            $status = 'warning';
        }

        if ($this->latestMetric->disk_usage >= 90) {
            $alerts[] = ['type' => 'critical', 'metric' => 'Disk', 'value' => $this->latestMetric->disk_usage];
            $status = 'critical';
        } elseif ($this->latestMetric->disk_usage >= 80 && $status !== 'critical') {
            $alerts[] = ['type' => 'warning', 'metric' => 'Disk', 'value' => $this->latestMetric->disk_usage];
            $status = 'warning';
        }

        return ['status' => $status, 'alerts' => $alerts];
    }

    public function refreshMetrics(): void
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
            $this->dispatch('notification', type: 'error', message: 'Failed to collect metrics: '.$e->getMessage());
        }

        $this->isCollecting = false;
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->loadMetrics();
        $this->dispatch('metrics-chart-update', data: $this->chartData);
    }

    public function toggleLiveMode(): void
    {
        $this->liveMode = ! $this->liveMode;
    }

    public function loadTopProcesses(): void
    {
        $this->isLoadingProcesses = true;

        try {
            if ($this->processView === 'cpu') {
                $this->topProcesses = $this->metricsService->getTopProcessesByCPU($this->server, 10);
            } else {
                $this->topProcesses = $this->metricsService->getTopProcessesByMemory($this->server, 10);
            }
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to load processes: '.$e->getMessage());
            $this->topProcesses = [];
        }

        $this->isLoadingProcesses = false;
    }

    public function switchProcessView(string $view): void
    {
        if (in_array($view, ['cpu', 'memory'])) {
            $this->processView = $view;
            $this->loadTopProcesses();
        }
    }

    public function refreshProcesses(): void
    {
        $this->loadTopProcesses();
        $this->dispatch('notification', type: 'success', message: 'Process list refreshed!');
    }

    #[On('metrics-updated')]
    public function onMetricsUpdated(): void
    {
        $this->loadMetrics();
        $this->dispatch('metrics-chart-update', data: $this->chartData);
    }

    #[On('refresh-processes')]
    public function onRefreshProcesses(): void
    {
        $this->loadTopProcesses();
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            "echo:server-metrics.{$this->server->id},ServerMetricsUpdated" => 'handleRealtimeMetrics',
        ];
    }

    /**
     * @param  array{alerts?: array<int, array{type: string, message: string}>}  $event
     */
    public function handleRealtimeMetrics(array $event): void
    {
        // Reload metrics when real-time update arrives
        $this->loadMetrics();
        $this->dispatch('metrics-chart-update', data: $this->chartData);

        // Show alerts if any
        if (! empty($event['alerts'])) {
            foreach ($event['alerts'] as $alert) {
                $type = $alert['type'] === 'critical' ? 'error' : 'warning';
                $this->dispatch('notification', type: $type, message: $alert['message']);
            }
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.server-metrics-dashboard');
    }
}
