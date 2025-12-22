<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Server;
use App\Services\DockerInstallationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InstallDockerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes

    public int $tries = 1;

    public function __construct(
        public Server $server
    ) {}

    public function handle(DockerInstallationService $installationService): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        $logsKey = "docker_install_logs_{$this->server->id}";

        try {
            // Initialize logs
            Cache::put($logsKey, [], 3600);

            // Mark as in progress
            $this->updateStatus($cacheKey, [
                'status' => 'installing',
                'message' => 'Installing Docker... This may take several minutes.',
                'progress' => 5,
                'current_step' => 'Connecting to server...',
                'started_at' => now()->toISOString(),
            ]);

            $this->appendLog($logsKey, '=== Docker Installation Started ===');
            $this->appendLog($logsKey, 'Server: '.$this->server->name.' ('.$this->server->ip_address.')');
            $this->appendLog($logsKey, 'Username: '.$this->server->username);
            $this->appendLog($logsKey, 'Time: '.now()->format('Y-m-d H:i:s'));
            $this->appendLog($logsKey, '');

            Log::info('Docker installation job started', ['server_id' => $this->server->id]);

            // Run the installation with streaming callback
            $result = $installationService->installDockerWithStreaming(
                $this->server,
                function (string $line, int $progress, string $step) use ($cacheKey, $logsKey) {
                    $this->appendLog($logsKey, $line);
                    $this->updateStatus($cacheKey, [
                        'status' => 'installing',
                        'message' => 'Installing Docker...',
                        'progress' => $progress,
                        'current_step' => $step,
                    ]);
                }
            );

            if ($result['success']) {
                $this->appendLog($logsKey, '');
                $this->appendLog($logsKey, '=== Installation Completed Successfully ===');
                $this->appendLog($logsKey, 'Docker Version: '.($result['version'] ?? 'unknown'));

                $this->updateStatus($cacheKey, [
                    'status' => 'completed',
                    'message' => $result['message'],
                    'version' => $result['version'] ?? null,
                    'progress' => 100,
                    'current_step' => 'Installation complete!',
                    'completed_at' => now()->toISOString(),
                ]);

                Log::info('Docker installation completed', [
                    'server_id' => $this->server->id,
                    'version' => $result['version'] ?? 'unknown',
                ]);
            } else {
                $this->appendLog($logsKey, '');
                $this->appendLog($logsKey, '=== Installation Failed ===');
                $this->appendLog($logsKey, 'ERROR: '.$result['message']);

                $this->updateStatus($cacheKey, [
                    'status' => 'failed',
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null,
                    'progress' => 0,
                    'current_step' => 'Installation failed',
                    'completed_at' => now()->toISOString(),
                ]);

                Log::error('Docker installation failed', [
                    'server_id' => $this->server->id,
                    'error' => $result['message'],
                ]);
            }

        } catch (\Exception $e) {
            $this->appendLog($logsKey, '');
            $this->appendLog($logsKey, '=== Installation Exception ===');
            $this->appendLog($logsKey, 'ERROR: '.$e->getMessage());

            $this->updateStatus($cacheKey, [
                'status' => 'failed',
                'message' => 'Installation failed: '.$e->getMessage(),
                'error' => $e->getMessage(),
                'progress' => 0,
                'current_step' => 'Installation failed',
                'completed_at' => now()->toISOString(),
            ]);

            Log::error('Docker installation exception', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update installation status in cache
     *
     * @param array<string, mixed> $data
     */
    private function updateStatus(string $cacheKey, array $data): void
    {
        $existing = Cache::get($cacheKey, []);
        Cache::put($cacheKey, array_merge($existing, $data), 3600);
    }

    /**
     * Append a log line to the logs cache
     */
    private function appendLog(string $logsKey, string $line): void
    {
        /** @var array<int, string> $logs */
        $logs = Cache::get($logsKey, []);
        $logs[] = $line;

        // Keep only last 500 lines to prevent memory issues
        if (count($logs) > 500) {
            $logs = array_slice($logs, -500);
        }

        Cache::put($logsKey, $logs, 3600);
    }

    public function failed(\Throwable $exception): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        $logsKey = "docker_install_logs_{$this->server->id}";

        $this->appendLog($logsKey, '');
        $this->appendLog($logsKey, '=== Job Failed ===');
        $this->appendLog($logsKey, 'ERROR: '.$exception->getMessage());

        Cache::put($cacheKey, [
            'status' => 'failed',
            'message' => 'Installation failed: '.$exception->getMessage(),
            'error' => $exception->getMessage(),
            'progress' => 0,
            'current_step' => 'Job failed',
            'completed_at' => now()->toISOString(),
        ], 3600);

        Log::error('Docker installation job failed', [
            'server_id' => $this->server->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
