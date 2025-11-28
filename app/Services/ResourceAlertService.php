<?php

namespace App\Services;

use App\Models\Server;
use App\Models\ResourceAlert;
use App\Models\AlertHistory;
use Illuminate\Support\Facades\Log;

class ResourceAlertService
{
    public function __construct(
        protected ServerMetricsService $metricsService,
        protected AlertNotificationService $notificationService
    ) {}

    /**
     * Check server resources and return current metrics
     */
    public function checkServerResources(Server $server): array
    {
        try {
            $metrics = $this->metricsService->getLatestMetrics($server);

            if (!$metrics) {
                // Try to collect fresh metrics
                $metrics = $this->metricsService->collectMetrics($server);
            }

            if (!$metrics) {
                return [
                    'success' => false,
                    'message' => 'Failed to collect server metrics',
                ];
            }

            return [
                'success' => true,
                'cpu' => $metrics->cpu_usage,
                'memory' => $metrics->memory_usage,
                'disk' => $metrics->disk_usage,
                'load' => $metrics->load_average_1,
                'metrics' => $metrics,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check server resources', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Evaluate all active alerts for a server
     */
    public function evaluateAlerts(Server $server): array
    {
        $resources = $this->checkServerResources($server);

        if (!$resources['success']) {
            return [
                'checked' => 0,
                'triggered' => 0,
                'resolved' => 0,
                'error' => $resources['message'] ?? 'Failed to check resources',
            ];
        }

        $alerts = ResourceAlert::forServer($server->id)
            ->active()
            ->get();

        $triggeredCount = 0;
        $resolvedCount = 0;

        foreach ($alerts as $alert) {
            $currentValue = $resources[$alert->resource_type] ?? null;

            if ($currentValue === null) {
                continue;
            }

            $shouldTrigger = $this->shouldTriggerAlert($alert, $currentValue);
            $wasTriggered = $this->wasAlertTriggered($alert);

            if ($shouldTrigger && $this->canTrigger($alert)) {
                // Alert condition met - trigger it
                $this->triggerAlert($alert, $currentValue);
                $triggeredCount++;
            } elseif (!$shouldTrigger && $wasTriggered) {
                // Alert condition resolved
                $this->resolveAlert($alert, $currentValue);
                $resolvedCount++;
            }
        }

        return [
            'checked' => $alerts->count(),
            'triggered' => $triggeredCount,
            'resolved' => $resolvedCount,
        ];
    }

    /**
     * Trigger an alert
     */
    public function triggerAlert(ResourceAlert $alert, float $currentValue): AlertHistory
    {
        $history = AlertHistory::create([
            'resource_alert_id' => $alert->id,
            'server_id' => $alert->server_id,
            'resource_type' => $alert->resource_type,
            'current_value' => $currentValue,
            'threshold_value' => $alert->threshold_value,
            'status' => 'triggered',
            'message' => $this->buildAlertMessage($alert, $currentValue, 'triggered'),
            'notified_at' => now(),
        ]);

        // Update alert last triggered timestamp
        $alert->update([
            'last_triggered_at' => now(),
        ]);

        // Send notifications
        try {
            $this->notificationService->send($alert, $history);
        } catch (\Exception $e) {
            Log::error('Failed to send alert notification', [
                'alert_id' => $alert->id,
                'history_id' => $history->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Resource alert triggered', [
            'alert_id' => $alert->id,
            'server_id' => $alert->server_id,
            'resource_type' => $alert->resource_type,
            'current_value' => $currentValue,
            'threshold_value' => $alert->threshold_value,
        ]);

        return $history;
    }

    /**
     * Resolve an alert
     */
    public function resolveAlert(ResourceAlert $alert, float $currentValue): AlertHistory
    {
        $history = AlertHistory::create([
            'resource_alert_id' => $alert->id,
            'server_id' => $alert->server_id,
            'resource_type' => $alert->resource_type,
            'current_value' => $currentValue,
            'threshold_value' => $alert->threshold_value,
            'status' => 'resolved',
            'message' => $this->buildAlertMessage($alert, $currentValue, 'resolved'),
            'notified_at' => now(),
        ]);

        // Send resolution notification
        try {
            $this->notificationService->send($alert, $history);
        } catch (\Exception $e) {
            Log::error('Failed to send resolution notification', [
                'alert_id' => $alert->id,
                'history_id' => $history->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Resource alert resolved', [
            'alert_id' => $alert->id,
            'server_id' => $alert->server_id,
            'resource_type' => $alert->resource_type,
            'current_value' => $currentValue,
            'threshold_value' => $alert->threshold_value,
        ]);

        return $history;
    }

    /**
     * Check if alert should trigger based on threshold
     */
    protected function shouldTriggerAlert(ResourceAlert $alert, float $currentValue): bool
    {
        if ($alert->threshold_type === 'above') {
            return $currentValue > $alert->threshold_value;
        }

        return $currentValue < $alert->threshold_value;
    }

    /**
     * Check if alert can trigger (respects cooldown)
     */
    public function canTrigger(ResourceAlert $alert): bool
    {
        return !$alert->isInCooldown();
    }

    /**
     * Check if alert was previously triggered
     */
    protected function wasAlertTriggered(ResourceAlert $alert): bool
    {
        return AlertHistory::where('resource_alert_id', $alert->id)
            ->where('status', 'triggered')
            ->latest()
            ->exists();
    }

    /**
     * Build alert message
     */
    protected function buildAlertMessage(ResourceAlert $alert, float $currentValue, string $status): string
    {
        $server = $alert->server;
        $resourceLabel = $alert->resource_type_label;
        $unit = in_array($alert->resource_type, ['cpu', 'memory', 'disk']) ? '%' : '';

        if ($status === 'triggered') {
            return sprintf(
                '%s on %s is %s %.2f%s (threshold: %s %.2f%s)',
                $resourceLabel,
                $server->name,
                $alert->threshold_type === 'above' ? 'above' : 'below',
                $currentValue,
                $unit,
                $alert->threshold_type === 'above' ? '>' : '<',
                $alert->threshold_value,
                $unit
            );
        }

        return sprintf(
            '%s on %s has returned to normal: %.2f%s (threshold: %s %.2f%s)',
            $resourceLabel,
            $server->name,
            $currentValue,
            $unit,
            $alert->threshold_type === 'above' ? '>' : '<',
            $alert->threshold_value,
            $unit
        );
    }

    /**
     * Test an alert by sending a test notification
     */
    public function testAlert(ResourceAlert $alert): array
    {
        try {
            $resources = $this->checkServerResources($alert->server);

            if (!$resources['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to get server metrics: ' . ($resources['message'] ?? 'Unknown error'),
                ];
            }

            $currentValue = $resources[$alert->resource_type] ?? 0;

            $testHistory = new AlertHistory([
                'resource_alert_id' => $alert->id,
                'server_id' => $alert->server_id,
                'resource_type' => $alert->resource_type,
                'current_value' => $currentValue,
                'threshold_value' => $alert->threshold_value,
                'status' => 'triggered',
                'message' => '[TEST] ' . $this->buildAlertMessage($alert, $currentValue, 'triggered'),
                'notified_at' => now(),
            ]);

            $this->notificationService->send($alert, $testHistory);

            return [
                'success' => true,
                'message' => 'Test notification sent successfully',
                'current_value' => $currentValue,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to test alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send test notification: ' . $e->getMessage(),
            ];
        }
    }
}
