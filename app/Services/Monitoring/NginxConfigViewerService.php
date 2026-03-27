<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Support\Facades\Log;

/**
 * Reads and validates nginx site configuration on remote servers.
 *
 * Parses enabled sites from /etc/nginx/sites-enabled/ and extracts
 * server_name, listen, root, and SSL directives.
 */
final class NginxConfigViewerService
{
    use ExecutesRemoteCommands;

    /**
     * Get all enabled nginx site configs.
     *
     * @return array<int, array{name: string, server_names: array<int, string>, listen: array<int, string>, root: string|null, ssl: bool, config_preview: string}>
     */
    public function getEnabledSites(Server $server): array
    {
        try {
            $output = $this->getRemoteOutput($server, 'ls /etc/nginx/sites-enabled/ 2>/dev/null', false);

            if (empty(trim($output))) {
                return [];
            }

            $sites = [];

            foreach (explode("\n", trim($output)) as $file) {
                $file = trim($file);

                if ($file === '') {
                    continue;
                }

                $config = $this->getRemoteOutput($server, "cat /etc/nginx/sites-enabled/{$file} 2>/dev/null", false);

                if (empty(trim($config))) {
                    continue;
                }

                $sites[] = [
                    'name' => $file,
                    'server_names' => $this->parseServerNames($config),
                    'listen' => $this->parseListenDirectives($config),
                    'root' => $this->parseRoot($config),
                    'ssl' => str_contains($config, 'ssl'),
                    'config_preview' => substr($config, 0, 500),
                ];
            }

            return $sites;
        } catch (\Exception $e) {
            Log::warning('NginxConfigViewerService: failed to list enabled sites', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Validate the active nginx configuration via `nginx -t`.
     *
     * @return array{passed: bool, output: string}
     */
    public function validateConfig(Server $server): array
    {
        try {
            $output = $this->getRemoteOutput($server, 'sudo nginx -t 2>&1', false);
            $passed = str_contains($output, 'test is successful');

            return [
                'passed' => $passed,
                'output' => $output !== '' ? $output : 'No output',
            ];
        } catch (\Exception $e) {
            Log::warning('NginxConfigViewerService: failed to validate config', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage(),
            ]);

            return [
                'passed' => false,
                'output' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reload nginx to apply config changes.
     */
    public function reload(Server $server): bool
    {
        try {
            $this->executeRemoteCommand($server, 'sudo systemctl reload nginx 2>&1', false);

            Log::info('NginxConfigViewerService: nginx reloaded', [
                'server_id' => $server->id,
                'server_name' => $server->name,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('NginxConfigViewerService: failed to reload nginx', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    private function parseServerNames(string $config): array
    {
        preg_match_all('/server_name\s+([^;]+);/', $config, $matches);
        $names = [];

        foreach ($matches[1] ?? [] as $match) {
            $parts = preg_split('/\s+/', trim($match));

            foreach ($parts as $name) {
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @return array<int, string>
     */
    private function parseListenDirectives(string $config): array
    {
        preg_match_all('/listen\s+([^;]+);/', $config, $matches);

        return array_map('trim', $matches[1] ?? []);
    }

    private function parseRoot(string $config): ?string
    {
        preg_match('/root\s+([^;]+);/', $config, $match);

        return isset($match[1]) ? trim($match[1]) : null;
    }
}
