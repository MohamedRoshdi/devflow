<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ServerMetricsUpdated;
use App\Services\AuditService;
use App\Services\ResourceAlertService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ServerMetricsUpdatedListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected AuditService $auditService,
        protected ResourceAlertService $alertService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ServerMetricsUpdated $event): void
    {
        $server = $event->server;
        $metric = $event->metric;

        try {
            // Check if any alerts are present in the broadcast data
            $alerts = $event->broadcastWith()['alerts'] ?? [];

            // If critical or warning alerts exist, log them in audit
            if (! empty($alerts)) {
                $criticalAlerts = array_filter($alerts, fn ($alert) => $alert['type'] === 'critical');
                $warningAlerts = array_filter($alerts, fn ($alert) => $alert['type'] === 'warning');

                if (! empty($criticalAlerts)) {
                    $this->auditService->log(
                        action: 'server.metrics.critical_threshold',
                        model: $server,
                        oldValues: null,
                        newValues: [
                            'server_id' => $server->id,
                            'server_name' => $server->name,
                            'cpu_usage' => $metric->cpu_usage,
                            'memory_usage' => $metric->memory_usage,
                            'disk_usage' => $metric->disk_usage,
                            'alerts' => $criticalAlerts,
                            'recorded_at' => $metric->recorded_at->toIso8601String(),
                        ]
                    );

                    Log::critical('Server resource critical threshold exceeded', [
                        'server_id' => $server->id,
                        'server_name' => $server->name,
                        'alerts' => $criticalAlerts,
                    ]);
                }

                if (! empty($warningAlerts)) {
                    $this->auditService->log(
                        action: 'server.metrics.warning_threshold',
                        model: $server,
                        oldValues: null,
                        newValues: [
                            'server_id' => $server->id,
                            'server_name' => $server->name,
                            'cpu_usage' => $metric->cpu_usage,
                            'memory_usage' => $metric->memory_usage,
                            'disk_usage' => $metric->disk_usage,
                            'alerts' => $warningAlerts,
                            'recorded_at' => $metric->recorded_at->toIso8601String(),
                        ]
                    );

                    Log::warning('Server resource warning threshold exceeded', [
                        'server_id' => $server->id,
                        'server_name' => $server->name,
                        'alerts' => $warningAlerts,
                    ]);
                }
            }

            // Evaluate all configured resource alerts for this server
            $alertResults = $this->alertService->evaluateAlerts($server);

            // Log alert evaluation results if any alerts were triggered or resolved
            if ($alertResults['triggered'] > 0 || $alertResults['resolved'] > 0) {
                Log::info('Server resource alerts evaluated', [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'checked' => $alertResults['checked'],
                    'triggered' => $alertResults['triggered'],
                    'resolved' => $alertResults['resolved'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle ServerMetricsUpdated event', [
                'server_id' => $server->id,
                'metric_id' => $metric->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ServerMetricsUpdated $event, \Throwable $exception): void
    {
        Log::error('ServerMetricsUpdatedListener failed', [
            'server_id' => $event->server->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
