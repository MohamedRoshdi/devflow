<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Server;
use App\Models\ServerMetric;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServerMetricsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Server $server;

    public ServerMetric $metric;

    /**
     * Create a new event instance.
     */
    public function __construct(Server $server, ServerMetric $metric)
    {
        $this->server = $server;
        $this->metric = $metric;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('server-metrics.'.$this->server->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'server_id' => $this->server->id,
            'server_name' => $this->server->name,
            'metrics' => [
                'cpu_usage' => round((float) $this->metric->cpu_usage, 1),
                'memory_usage' => round((float) $this->metric->memory_usage, 1),
                'memory_used_mb' => $this->metric->memory_used_mb,
                'memory_total_mb' => $this->metric->memory_total_mb,
                'disk_usage' => round((float) $this->metric->disk_usage, 1),
                'disk_used_gb' => $this->metric->disk_used_gb,
                'disk_total_gb' => $this->metric->disk_total_gb,
                'load_average_1' => $this->metric->load_average_1,
                'load_average_5' => $this->metric->load_average_5,
                'load_average_15' => $this->metric->load_average_15,
                'network_in_bytes' => $this->metric->network_in_bytes,
                'network_out_bytes' => $this->metric->network_out_bytes,
            ],
            'recorded_at' => $this->metric->recorded_at->toIso8601String(),
            'alerts' => $this->checkAlertThresholds(),
        ];
    }

    /**
     * Check if any metrics exceed alert thresholds
     */
    protected function checkAlertThresholds(): array
    {
        $alerts = [];

        if ($this->metric->cpu_usage >= 90) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'cpu',
                'message' => "CPU usage critical: {$this->metric->cpu_usage}%",
            ];
        } elseif ($this->metric->cpu_usage >= 80) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'cpu',
                'message' => "CPU usage high: {$this->metric->cpu_usage}%",
            ];
        }

        if ($this->metric->memory_usage >= 85) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'memory',
                'message' => "Memory usage critical: {$this->metric->memory_usage}%",
            ];
        } elseif ($this->metric->memory_usage >= 75) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'memory',
                'message' => "Memory usage high: {$this->metric->memory_usage}%",
            ];
        }

        if ($this->metric->disk_usage >= 90) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'disk',
                'message' => "Disk usage critical: {$this->metric->disk_usage}%",
            ];
        } elseif ($this->metric->disk_usage >= 80) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'disk',
                'message' => "Disk usage high: {$this->metric->disk_usage}%",
            ];
        }

        return $alerts;
    }
}
