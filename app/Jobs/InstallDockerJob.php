<?php

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

        try {
            // Mark as in progress
            Cache::put($cacheKey, [
                'status' => 'installing',
                'message' => 'Installing Docker... This may take several minutes.',
                'progress' => 10,
                'started_at' => now()->toISOString(),
            ], 3600);

            Log::info('Docker installation job started', ['server_id' => $this->server->id]);

            // Run the installation
            $result = $installationService->installDocker($this->server);

            if ($result['success']) {
                Cache::put($cacheKey, [
                    'status' => 'completed',
                    'message' => $result['message'],
                    'version' => $result['version'] ?? null,
                    'progress' => 100,
                    'completed_at' => now()->toISOString(),
                ], 3600);

                Log::info('Docker installation completed', [
                    'server_id' => $this->server->id,
                    'version' => $result['version'] ?? 'unknown',
                ]);
            } else {
                Cache::put($cacheKey, [
                    'status' => 'failed',
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null,
                    'progress' => 0,
                    'completed_at' => now()->toISOString(),
                ], 3600);

                Log::error('Docker installation failed', [
                    'server_id' => $this->server->id,
                    'error' => $result['message'],
                ]);
            }

        } catch (\Exception $e) {
            Cache::put($cacheKey, [
                'status' => 'failed',
                'message' => 'Installation failed: '.$e->getMessage(),
                'error' => $e->getMessage(),
                'progress' => 0,
                'completed_at' => now()->toISOString(),
            ], 3600);

            Log::error('Docker installation exception', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $cacheKey = "docker_install_{$this->server->id}";

        Cache::put($cacheKey, [
            'status' => 'failed',
            'message' => 'Installation failed: '.$exception->getMessage(),
            'error' => $exception->getMessage(),
            'progress' => 0,
            'completed_at' => now()->toISOString(),
        ], 3600);

        Log::error('Docker installation job failed', [
            'server_id' => $this->server->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
