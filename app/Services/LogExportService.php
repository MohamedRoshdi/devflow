<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogExportService
{
    /**
     * Export logs to CSV format.
     *
     * @param Collection<int, SystemLog> $logs
     */
    public function exportToCsv(Collection $logs): StreamedResponse
    {
        $filename = 'system-logs-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, [
                'ID',
                'Server',
                'Log Type',
                'Level',
                'Source',
                'Message',
                'IP Address',
                'Logged At',
                'Created At',
            ]);

            // Data rows
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->server?->name ?? 'N/A',
                    $log->log_type,
                    $log->level,
                    $log->source ?? 'N/A',
                    $log->message,
                    $log->ip_address ?? 'N/A',
                    $log->logged_at?->toDateTimeString() ?? 'N/A',
                    $log->created_at?->toDateTimeString() ?? 'N/A',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Export logs to JSON format.
     *
     * @param Collection<int, SystemLog> $logs
     */
    public function exportToJson(Collection $logs): StreamedResponse
    {
        $filename = 'system-logs-' . now()->format('Y-m-d-His') . '.json';

        $data = $logs->map(function (SystemLog $log) {
            return [
                'id' => $log->id,
                'server' => [
                    'id' => $log->server?->id,
                    'name' => $log->server?->name,
                ],
                'user' => [
                    'id' => $log->user?->id,
                    'name' => $log->user?->name,
                ],
                'log_type' => $log->log_type,
                'level' => $log->level,
                'source' => $log->source,
                'message' => $log->message,
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'logged_at' => $log->logged_at?->toIso8601String(),
                'created_at' => $log->created_at?->toIso8601String(),
            ];
        });

        return response()->streamDownload(function () use ($data) {
            echo json_encode([
                'exported_at' => now()->toIso8601String(),
                'total_logs' => $data->count(),
                'logs' => $data->values(),
            ], JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Export logs to plain text format.
     *
     * @param Collection<int, SystemLog> $logs
     */
    public function exportToText(Collection $logs): StreamedResponse
    {
        $filename = 'system-logs-' . now()->format('Y-m-d-His') . '.txt';

        return response()->streamDownload(function () use ($logs) {
            echo "System Logs Export\n";
            echo "Generated: " . now()->toDateTimeString() . "\n";
            echo "Total Logs: " . $logs->count() . "\n";
            echo str_repeat('=', 80) . "\n\n";

            foreach ($logs as $log) {
                $loggedAt = $log->logged_at?->toDateTimeString() ?? 'N/A';
                echo "[{$loggedAt}] ";
                echo "[{$log->level}] ";
                echo "[{$log->log_type}] ";

                if ($log->server) {
                    echo "[Server: {$log->server->name}] ";
                }

                if ($log->source) {
                    echo "[{$log->source}] ";
                }

                echo $log->message . "\n";

                if ($log->ip_address) {
                    echo "  IP: {$log->ip_address}\n";
                }

                if ($log->metadata) {
                    echo "  Metadata: " . json_encode($log->metadata) . "\n";
                }

                echo "\n";
            }
        }, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Export logs based on format.
     *
     * @param Collection<int, SystemLog> $logs
     */
    public function export(Collection $logs, string $format = 'csv'): StreamedResponse
    {
        return match ($format) {
            'json' => $this->exportToJson($logs),
            'txt', 'text' => $this->exportToText($logs),
            default => $this->exportToCsv($logs),
        };
    }
}
