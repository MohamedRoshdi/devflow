<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BulkServerActionService
{
    public function __construct(
        private readonly ServerConnectivityService $connectivityService,
        private readonly DockerInstallationService $dockerService
    ) {}

    /**
     * Ping multiple servers in parallel
     *
     * @param Collection $servers Collection of Server models
     * @return array Array with server_id => result mapping
     */
    public function pingServers(Collection $servers): array
    {
        $results = [];

        foreach ($servers as $server) {
            try {
                $result = $this->connectivityService->testConnection($server);

                // Update server status
                $server->update([
                    'status' => $result['reachable'] ? 'online' : 'offline',
                    'last_ping_at' => now(),
                ]);

                $results[$server->id] = [
                    'success' => $result['reachable'],
                    'server_name' => $server->name,
                    'message' => $result['message'],
                    'latency_ms' => $result['latency_ms'] ?? null,
                ];

            } catch (\Exception $e) {
                Log::error('Bulk ping failed for server', [
                    'server_id' => $server->id,
                    'error' => $e->getMessage(),
                ]);

                $results[$server->id] = [
                    'success' => false,
                    'server_name' => $server->name,
                    'message' => 'Error: ' . $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Reboot multiple servers
     *
     * @param Collection $servers Collection of Server models
     * @return array Array with server_id => result mapping
     */
    public function rebootServers(Collection $servers): array
    {
        $results = [];

        foreach ($servers as $server) {
            try {
                $result = $this->connectivityService->rebootServer($server);

                $results[$server->id] = [
                    'success' => $result['success'],
                    'server_name' => $server->name,
                    'message' => $result['message'],
                ];

                if ($result['success']) {
                    Log::info('Bulk reboot successful', ['server_id' => $server->id]);
                }

            } catch (\Exception $e) {
                Log::error('Bulk reboot failed for server', [
                    'server_id' => $server->id,
                    'error' => $e->getMessage(),
                ]);

                $results[$server->id] = [
                    'success' => false,
                    'server_name' => $server->name,
                    'message' => 'Error: ' . $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Install Docker on multiple servers
     *
     * @param Collection $servers Collection of Server models
     * @return array Array with server_id => result mapping
     */
    public function installDockerOnServers(Collection $servers): array
    {
        $results = [];

        foreach ($servers as $server) {
            try {
                // Check if Docker is already installed
                $verifyResult = $this->dockerService->verifyDockerInstallation($server);

                if ($verifyResult['installed']) {
                    $results[$server->id] = [
                        'success' => true,
                        'server_name' => $server->name,
                        'message' => 'Docker is already installed (version: ' . ($verifyResult['version'] ?? 'unknown') . ')',
                        'already_installed' => true,
                    ];
                    continue;
                }

                // Install Docker
                $result = $this->dockerService->installDocker($server);

                $results[$server->id] = [
                    'success' => $result['success'],
                    'server_name' => $server->name,
                    'message' => $result['message'],
                    'version' => $result['version'] ?? null,
                    'already_installed' => false,
                ];

                if ($result['success']) {
                    Log::info('Bulk Docker installation successful', [
                        'server_id' => $server->id,
                        'version' => $result['version'] ?? null,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Bulk Docker installation failed for server', [
                    'server_id' => $server->id,
                    'error' => $e->getMessage(),
                ]);

                $results[$server->id] = [
                    'success' => false,
                    'server_name' => $server->name,
                    'message' => 'Error: ' . $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Restart a specific service on multiple servers
     *
     * @param Collection $servers Collection of Server models
     * @param string $service Service name (e.g., nginx, mysql, redis, php-fpm)
     * @return array Array with server_id => result mapping
     */
    public function restartServiceOnServers(Collection $servers, string $service): array
    {
        $results = [];

        foreach ($servers as $server) {
            try {
                $result = $this->connectivityService->restartService($server, $service);

                $results[$server->id] = [
                    'success' => $result['success'],
                    'server_name' => $server->name,
                    'message' => $result['message'],
                    'service' => $service,
                ];

                if ($result['success']) {
                    Log::info('Bulk service restart successful', [
                        'server_id' => $server->id,
                        'service' => $service,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Bulk service restart failed for server', [
                    'server_id' => $server->id,
                    'service' => $service,
                    'error' => $e->getMessage(),
                ]);

                $results[$server->id] = [
                    'success' => false,
                    'server_name' => $server->name,
                    'message' => 'Error: ' . $e->getMessage(),
                    'service' => $service,
                ];
            }
        }

        return $results;
    }

    /**
     * Get summary statistics from bulk operation results
     *
     * @param array $results Results array from any bulk operation
     * @return array Summary with success/failure counts
     */
    public function getSummaryStats(array $results): array
    {
        $successful = 0;
        $failed = 0;

        foreach ($results as $result) {
            if ($result['success']) {
                $successful++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => count($results),
            'successful' => $successful,
            'failed' => $failed,
        ];
    }
}
