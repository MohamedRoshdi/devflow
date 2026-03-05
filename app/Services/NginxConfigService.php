<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Support\Facades\Log;

class NginxConfigService
{
    use ExecutesRemoteCommands;

    /**
     * Generate nginx vhost config for a project domain.
     *
     * @param Project $project
     * @param Domain $domain
     * @return string
     */
    public function generateVhost(Project $project, Domain $domain): string
    {
        $slug = $project->validated_slug;
        $phpVersion = $project->php_version ?? '8.4';
        $domainName = $domain->domain;
        $rootPath = "/var/www/{$slug}/public";
        $fpmSocket = "/run/php/{$slug}.sock";

        $sslBlock = '';
        if ($domain->ssl_enabled) {
            $sslPaths = $this->resolveSSLPaths($domain);
            $sslBlock = <<<NGINX

    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    ssl_certificate {$sslPaths['certificate']};
    ssl_certificate_key {$sslPaths['private_key']};
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
NGINX;
        }

        return <<<NGINX
server {
    listen 80;
    listen [::]:80;{$sslBlock}
    server_name {$domainName};
    root {$rootPath};

    index index.php index.html;

    charset utf-8;
    client_max_body_size 100M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:{$fpmSocket};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/xml+rss application/atom+xml image/svg+xml;
}
NGINX;
    }

    /**
     * Install nginx vhost on remote server.
     *
     * @param Server $server
     * @param Project $project
     * @param Domain $domain
     * @return bool
     * @throws \RuntimeException When nginx config test fails
     */
    public function installVhost(Server $server, Project $project, Domain $domain): bool
    {
        $slug = $project->validated_slug;
        $availablePath = "/etc/nginx/sites-available/{$slug}";
        $enabledPath = "/etc/nginx/sites-enabled/{$slug}";
        $content = $this->generateVhost($project, $domain);

        Log::info('Installing nginx vhost', [
            'server' => $server->name,
            'project' => $slug,
            'domain' => $domain->domain,
        ]);

        // Write config via tee
        $this->executeRemoteCommandWithInput(
            $server,
            "tee {$availablePath} > /dev/null",
            $content
        );

        // Create symlink to sites-enabled
        $this->executeRemoteCommand($server, "ln -sfn {$availablePath} {$enabledPath}");

        // Test nginx config
        $testResult = $this->executeRemoteCommand($server, 'nginx -t 2>&1', false);

        if (! $testResult->successful()) {
            // Remove broken symlink on test failure
            $this->executeRemoteCommand($server, "rm -f {$enabledPath}", false);

            Log::error('Nginx config test failed', [
                'project' => $slug,
                'error' => $testResult->errorOutput() ?: $testResult->output(),
            ]);

            throw new \RuntimeException(
                'Nginx config test failed: ' . ($testResult->errorOutput() ?: $testResult->output())
            );
        }

        // Reload nginx
        $this->executeRemoteCommand($server, 'systemctl reload nginx');

        Log::info('Nginx vhost installed', [
            'project' => $slug,
            'domain' => $domain->domain,
        ]);

        return true;
    }

    /**
     * Remove nginx vhost from remote server.
     *
     * @param Server $server
     * @param Domain $domain
     * @return bool
     */
    public function removeVhost(Server $server, Domain $domain): bool
    {
        $project = $domain->project;
        $slug = $project?->validated_slug;

        if (! $slug) {
            return false;
        }

        $availablePath = "/etc/nginx/sites-available/{$slug}";
        $enabledPath = "/etc/nginx/sites-enabled/{$slug}";

        Log::info('Removing nginx vhost', [
            'server' => $server->name,
            'domain' => $domain->domain,
        ]);

        // Remove symlink and config
        $this->executeRemoteCommand($server, "rm -f {$enabledPath}", false);
        $this->executeRemoteCommand($server, "rm -f {$availablePath}", false);

        // Reload nginx (non-throwing)
        $this->executeRemoteCommand($server, 'systemctl reload nginx', false);

        Log::info('Nginx vhost removed', ['domain' => $domain->domain]);

        return true;
    }

    /**
     * Test nginx configuration on remote server.
     *
     * @param Server $server
     * @return bool
     */
    public function testConfig(Server $server): bool
    {
        $result = $this->executeRemoteCommand($server, 'nginx -t 2>&1', false);

        return $result->successful();
    }

    /**
     * Check if an Nginx vhost config is installed for a project.
     *
     * @param Server $server
     * @param Project $project
     * @return bool
     */
    public function isInstalled(Server $server, Project $project): bool
    {
        $slug = $project->validated_slug;
        $filePath = "/etc/nginx/sites-available/{$slug}";

        $result = $this->executeRemoteCommand($server, "test -f {$filePath} && echo 'exists'", false);

        return str_contains($result->output(), 'exists');
    }

    /**
     * Resolve SSL certificate and key paths based on the domain's ssl_provider.
     *
     * @param Domain $domain
     * @return array{certificate: string, private_key: string}
     */
    private function resolveSSLPaths(Domain $domain): array
    {
        $domainName = $domain->domain;

        return match ($domain->ssl_provider) {
            'cloudflare' => [
                'certificate' => "/etc/ssl/cloudflare/{$domainName}.pem",
                'private_key' => "/etc/ssl/cloudflare/{$domainName}.key",
            ],
            'custom' => [
                'certificate' => $domain->ssl_certificate ?? "/etc/ssl/certs/{$domainName}.pem",
                'private_key' => $domain->ssl_private_key ?? "/etc/ssl/private/{$domainName}.key",
            ],
            default => [ // letsencrypt
                'certificate' => "/etc/letsencrypt/live/{$domainName}/fullchain.pem",
                'private_key' => "/etc/letsencrypt/live/{$domainName}/privkey.pem",
            ],
        };
    }
}
