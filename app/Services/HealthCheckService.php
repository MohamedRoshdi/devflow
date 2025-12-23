<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HealthCheck;
use App\Models\HealthCheckResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class HealthCheckService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function runCheck(HealthCheck $check): HealthCheckResult
    {
        $result = match ($check->check_type) {
            'http' => $this->performHttpCheck($check),
            'tcp' => $this->performTcpCheck($check),
            'ping' => $this->performPingCheck($check),
            'ssl_expiry' => $this->performSSLExpiryCheck($check),
            default => throw new \InvalidArgumentException("Unknown check type: {$check->check_type}"),
        };

        $this->recordResult($check, $result['status'], $result['response_time'] ?? null, $result['error'] ?? null, $result['status_code'] ?? null);
        $this->updateHealthCheckStatus($check);

        $latestResult = $check->results()->latest('checked_at')->first();
        if ($latestResult === null) {
            throw new \RuntimeException('Failed to retrieve health check result after execution');
        }

        return $latestResult;
    }

    public function performHttpCheck(HealthCheck $check): array
    {
        $startTime = microtime(true);

        // Validate URL before making request
        if (empty($check->target_url)) {
            return [
                'status' => 'failure',
                'response_time' => 0,
                'status_code' => 0,
                'error' => 'No target URL configured for health check',
            ];
        }

        try {
            $response = Http::timeout($check->timeout_seconds)
                ->get($check->target_url);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();

            if ($statusCode === $check->expected_status) {
                return [
                    'status' => 'success',
                    'response_time' => $responseTime,
                    'status_code' => $statusCode,
                ];
            }

            return [
                'status' => 'failure',
                'response_time' => $responseTime,
                'status_code' => $statusCode,
                'error' => "Expected status {$check->expected_status}, got {$statusCode}",
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($responseTime >= $check->timeout_seconds * 1000) {
                return [
                    'status' => 'timeout',
                    'response_time' => $responseTime,
                    'error' => 'Request timed out',
                ];
            }

            return [
                'status' => 'failure',
                'response_time' => $responseTime,
                'error' => 'Connection failed: '.$e->getMessage(),
            ];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'status' => 'failure',
                'response_time' => $responseTime,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function performTcpCheck(HealthCheck $check): array
    {
        $startTime = microtime(true);

        try {
            $url = parse_url($check->target_url);
            $host = $url['host'] ?? $check->target_url;
            $port = $url['port'] ?? 80;

            $socket = @fsockopen($host, $port, $errno, $errstr, $check->timeout_seconds);
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($socket === false) {
                return [
                    'status' => 'failure',
                    'response_time' => $responseTime,
                    'error' => "TCP connection failed: {$errstr} (Error {$errno})",
                ];
            }

            fclose($socket);

            return [
                'status' => 'success',
                'response_time' => $responseTime,
            ];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'status' => 'failure',
                'response_time' => $responseTime,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function performPingCheck(HealthCheck $check): array
    {
        $startTime = microtime(true);

        try {
            $url = parse_url($check->target_url);
            $host = $url['host'] ?? $check->target_url;

            $result = Process::timeout($check->timeout_seconds)
                ->run("ping -c 1 -W {$check->timeout_seconds} {$host}");

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($result->successful()) {
                preg_match('/time=(\d+\.?\d*)\s*ms/', $result->output(), $matches);
                $pingTime = isset($matches[1]) ? (int) floatval($matches[1]) : $responseTime;

                return [
                    'status' => 'success',
                    'response_time' => $pingTime,
                ];
            }

            return [
                'status' => 'failure',
                'response_time' => $responseTime,
                'error' => 'Ping failed: Host unreachable',
            ];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'status' => 'failure',
                'response_time' => $responseTime,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function performSSLExpiryCheck(HealthCheck $check): array
    {
        $startTime = microtime(true);

        try {
            $url = parse_url($check->target_url);
            $host = $url['host'] ?? $check->target_url;
            $port = $url['port'] ?? 443;

            $streamContext = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $socket = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                $check->timeout_seconds,
                STREAM_CLIENT_CONNECT,
                $streamContext
            );

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($socket === false) {
                return [
                    'status' => 'failure',
                    'response_time' => $responseTime,
                    'error' => "SSL connection failed: {$errstr} (Error {$errno})",
                ];
            }

            $params = stream_context_get_params($socket);
            $cert = $params['options']['ssl']['peer_certificate'];
            $certInfo = openssl_x509_parse($cert);

            fclose($socket);

            if (! isset($certInfo['validTo_time_t'])) {
                return [
                    'status' => 'failure',
                    'response_time' => $responseTime,
                    'error' => 'Could not parse SSL certificate',
                ];
            }

            $expiryDate = $certInfo['validTo_time_t'];
            $daysUntilExpiry = (int) (($expiryDate - time()) / 86400);

            if ($daysUntilExpiry < 0) {
                return [
                    'status' => 'failure',
                    'response_time' => $responseTime,
                    'error' => 'SSL certificate expired '.abs($daysUntilExpiry).' days ago',
                ];
            }

            if ($daysUntilExpiry < 30) {
                return [
                    'status' => 'failure',
                    'response_time' => $responseTime,
                    'error' => "SSL certificate expires in {$daysUntilExpiry} days",
                ];
            }

            return [
                'status' => 'success',
                'response_time' => $responseTime,
                'status_code' => $daysUntilExpiry,
            ];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'status' => 'failure',
                'response_time' => $responseTime,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function recordResult(
        HealthCheck $check,
        string $status,
        ?int $responseTime = null,
        ?string $error = null,
        ?int $statusCode = null
    ): void {
        HealthCheckResult::create([
            'health_check_id' => $check->id,
            'status' => $status,
            'response_time_ms' => $responseTime,
            'status_code' => $statusCode,
            'error_message' => $error,
            'checked_at' => now(),
        ]);

        $check->update(['last_check_at' => now()]);
    }

    public function updateHealthCheckStatus(HealthCheck $check): void
    {
        $latestResult = $check->results()->latest('checked_at')->first();

        if (! $latestResult) {
            return;
        }

        $wasHealthy = in_array($check->status, ['healthy', 'unknown']);
        $previousStatus = $check->status;

        if ($latestResult->isSuccess()) {
            $check->update([
                'status' => 'healthy',
                'consecutive_failures' => 0,
                'last_success_at' => now(),
            ]);

            if (! $wasHealthy && $this->shouldNotify($check, 'recovery')) {
                $this->notificationService->notifyHealthCheckRecovery($check);
            }
        } else {
            $consecutiveFailures = $check->consecutive_failures + 1;

            $status = match (true) {
                $consecutiveFailures >= 5 => 'down',
                $consecutiveFailures >= 2 => 'degraded',
                default => 'degraded',
            };

            $check->update([
                'status' => $status,
                'consecutive_failures' => $consecutiveFailures,
                'last_failure_at' => now(),
            ]);

            if ($this->shouldNotify($check, 'failure')) {
                $this->notificationService->notifyHealthCheckFailure($check, $latestResult);
            }
        }

        $freshCheck = $check->fresh();
        if ($freshCheck !== null && $previousStatus !== $freshCheck->status) {
            Log::info('Health check status changed', [
                'check_id' => $check->id,
                'check_name' => $check->display_name,
                'previous_status' => $previousStatus,
                'new_status' => $freshCheck->status,
            ]);
        }
    }

    public function shouldNotify(HealthCheck $check, string $event): bool
    {
        $channels = $check->notificationChannels()
            ->where('is_active', true)
            ->get();

        foreach ($channels as $channel) {
            $pivot = $channel->pivot;

            if ($event === 'failure' && $pivot->notify_on_failure) {
                return true;
            }

            if ($event === 'recovery' && $pivot->notify_on_recovery) {
                return true;
            }
        }

        return false;
    }

    public function runDueChecks(): int
    {
        $checks = HealthCheck::where('is_active', true)->get();
        $runCount = 0;

        foreach ($checks as $check) {
            if ($check->isDue()) {
                try {
                    $this->runCheck($check);
                    $runCount++;
                } catch (\Exception $e) {
                    Log::error('Health check failed', [
                        'check_id' => $check->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $runCount;
    }
}
