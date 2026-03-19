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
     */
    public function generateVhost(Project $project, Domain $domain): string
    {
        $slug = $project->validated_slug;
        $phpVersion = $project->php_version ?? '8.4';
        $domainName = $domain->domain;
        $deployPath = $project->deploy_path ?? ((string) config('devflow.projects_path', '/var/www'))."/{$slug}";
        $rootPath = "{$deployPath}/public";
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
                'Nginx config test failed: '.($testResult->errorOutput() ?: $testResult->output())
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
     * Generate a wildcard Nginx config for Octane/FrankenPHP subdomain routing.
     *
     * Produces two server blocks:
     *  - HTTP → HTTPS redirect (port 80)
     *  - HTTPS reverse proxy to Octane on $octanePort (port 443, Cloudflare origin cert)
     *
     * Includes security headers, gzip, WebSocket upgrade (for Livewire),
     * static-asset caching, and a custom-domain catch-all block.
     *
     * @param  array{domain: string, project_path: string, octane_port: int, ssl_certificate?: string, ssl_certificate_key?: string}  $options
     */
    public function generateWildcardOctaneConfig(array $options): string
    {
        $domain = $options['domain'];
        $projectPath = rtrim($options['project_path'], '/');
        $octanePort = $options['octane_port'] ?? 8090;
        $sslCert = $options['ssl_certificate'] ?? '/etc/ssl/certs/cloudflare-origin.pem';
        $sslKey = $options['ssl_certificate_key'] ?? '/etc/ssl/private/cloudflare-origin.key';
        $publicPath = "{$projectPath}/public";

        return <<<NGINX
# HTTP → HTTPS redirect for wildcard and apex domain
server {
    listen 80;
    listen [::]:80;
    server_name {$domain} *.{$domain};
    return 301 https://\$host\$request_uri;
}

# HTTPS — Wildcard Octane reverse proxy (Cloudflare Full SSL)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name {$domain} *.{$domain};

    ssl_certificate {$sslCert};
    ssl_certificate_key {$sslKey};
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    root {$publicPath};
    index index.php;

    charset utf-8;
    client_max_body_size 100M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/xml+rss application/atom+xml image/svg+xml;

    location / {
        proxy_pass http://127.0.0.1:{$octanePort};
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        # WebSocket support (required for Livewire)
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_read_timeout 300s;
        proxy_connect_timeout 60s;
        proxy_send_timeout 300s;
    }

    location /build/ {
        alias {$publicPath}/build/;
        expires 1y;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    location ~* \.(ico|css|js|gif|jpe?g|png|webp|woff2?|svg)$ {
        expires 30d;
        access_log off;
        add_header Cache-Control "public";
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Custom domain catch-all — resolves stores by Host header via application middleware
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name _;

    ssl_certificate {$sslCert};
    ssl_certificate_key {$sslKey};
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root {$publicPath};
    index index.php;

    charset utf-8;
    client_max_body_size 100M;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript image/svg+xml;

    location / {
        proxy_pass http://127.0.0.1:{$octanePort};
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_read_timeout 300s;
    }

    location /build/ {
        alias {$publicPath}/build/;
        expires 1y;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    location ~* \.(ico|css|js|gif|jpe?g|png|webp|woff2?|svg)$ {
        expires 30d;
        access_log off;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX;
    }

    /**
     * Install the wildcard Octane Nginx config on a remote server.
     *
     * @param  array{domain: string, project_path: string, octane_port: int, ssl_certificate?: string, ssl_certificate_key?: string}  $options
     *
     * @throws \RuntimeException When nginx config test fails
     */
    public function installWildcardOctaneConfig(Server $server, array $options): bool
    {
        $domain = $options['domain'];
        $configName = 'wildcard-'.str_replace('.', '-', $domain);
        $availablePath = "/etc/nginx/sites-available/{$configName}";
        $enabledPath = "/etc/nginx/sites-enabled/{$configName}";
        $content = $this->generateWildcardOctaneConfig($options);

        Log::info('Installing wildcard Octane Nginx config', [
            'server' => $server->name,
            'domain' => $domain,
            'octane_port' => $options['octane_port'] ?? 8090,
        ]);

        $this->executeRemoteCommandWithInput(
            $server,
            "tee {$availablePath} > /dev/null",
            $content
        );

        $this->executeRemoteCommand($server, "ln -sfn {$availablePath} {$enabledPath}");

        $testResult = $this->executeRemoteCommand($server, 'nginx -t 2>&1', false);

        if (! $testResult->successful()) {
            $this->executeRemoteCommand($server, "rm -f {$enabledPath}", false);

            Log::error('Wildcard Octane Nginx config test failed', [
                'domain' => $domain,
                'error' => $testResult->errorOutput() ?: $testResult->output(),
            ]);

            throw new \RuntimeException(
                'Nginx config test failed: '.($testResult->errorOutput() ?: $testResult->output())
            );
        }

        $this->executeRemoteCommand($server, 'systemctl reload nginx');

        Log::info('Wildcard Octane Nginx config installed', [
            'domain' => $domain,
            'config' => $availablePath,
        ]);

        return true;
    }

    /**
     * Test nginx configuration on remote server.
     */
    public function testConfig(Server $server): bool
    {
        $result = $this->executeRemoteCommand($server, 'nginx -t 2>&1', false);

        return $result->successful();
    }

    /**
     * Check if an Nginx vhost config is installed for a project.
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
