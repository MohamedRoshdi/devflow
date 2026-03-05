<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\RepositoryAnalysis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class RepositoryAnalyzerService
{
    private const CLONE_TIMEOUT = 30;

    public function analyze(string $repositoryUrl, string $branch = 'main'): RepositoryAnalysis
    {
        $tmpDir = sys_get_temp_dir().'/devflow_repo_'.md5($repositoryUrl.$branch).'_'.time();

        try {
            $this->shallowClone($repositoryUrl, $branch, $tmpDir);

            return $this->scanRepository($tmpDir);
        } catch (\Throwable $e) {
            Log::warning('Repository analysis failed', [
                'url' => $repositoryUrl,
                'branch' => $branch,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            $this->cleanup($tmpDir);
        }
    }

    private function shallowClone(string $url, string $branch, string $tmpDir): void
    {
        // Try multiple URL variants: original, HTTPS conversion, SSH conversion
        $urlsToTry = $this->buildUrlVariants($url);
        $branches = array_unique([$branch, 'main', 'master']);

        foreach ($urlsToTry as $tryUrl) {
            foreach ($branches as $tryBranch) {
                $this->cleanup($tmpDir);

                if ($this->attemptClone($tryUrl, $tryBranch, $tmpDir)) {
                    return;
                }
            }
        }

        throw new \RuntimeException('Could not clone repository. Check the URL and ensure the repository is accessible.');
    }

    private function attemptClone(string $url, string $branch, string $tmpDir): bool
    {
        $escapedUrl = escapeshellarg($url);
        $escapedBranch = escapeshellarg($branch);
        $escapedDir = escapeshellarg($tmpDir);

        $env = $this->buildGitEnv();
        $envPrefix = $env !== '' ? $env.' ' : '';

        $result = Process::timeout(self::CLONE_TIMEOUT)->run(
            "{$envPrefix}git clone --depth 1 --single-branch --branch {$escapedBranch} {$escapedUrl} {$escapedDir} 2>&1"
        );

        return $result->successful();
    }

    /**
     * Build URL variants to try: original URL + converted counterpart.
     * SSH urls (git@host:user/repo.git) get an HTTPS variant and vice versa.
     *
     * @return array<int, string>
     */
    private function buildUrlVariants(string $url): array
    {
        $variants = [$url];

        // SSH -> HTTPS: git@github.com:user/repo.git -> https://github.com/user/repo.git
        if (preg_match('#^git@([^:]+):(.+)$#', $url, $m)) {
            $variants[] = 'https://'.$m[1].'/'.$m[2];
        }

        // HTTPS -> SSH: https://github.com/user/repo.git -> git@github.com:user/repo.git
        if (preg_match('#^https?://([^/]+)/(.+)$#', $url, $m)) {
            $variants[] = 'git@'.$m[1].':'.$m[2];
        }

        return $variants;
    }

    /**
     * Build environment variable prefix for git commands so SSH agent is available.
     */
    private function buildGitEnv(): string
    {
        $parts = [];

        // Forward SSH agent socket so private repos work from web processes
        $sshSock = getenv('SSH_AUTH_SOCK');
        if ($sshSock !== false && $sshSock !== '') {
            $parts[] = 'SSH_AUTH_SOCK='.escapeshellarg($sshSock);
        }

        // Try common socket paths when the env var is missing (web server context)
        if ($sshSock === false || $sshSock === '') {
            $user = getenv('USER') ?: (getenv('SUDO_USER') ?: posix_getpwuid(posix_getuid())['name'] ?? '');
            $candidates = [
                "/run/user/".(string) posix_getuid()."/keyring/ssh",
                "/tmp/ssh-agent-{$user}.sock",
            ];
            // Also check /tmp for ssh-* agent sockets
            foreach (glob('/tmp/ssh-*/agent.*') ?: [] as $sock) {
                $candidates[] = $sock;
            }
            foreach ($candidates as $candidate) {
                if (file_exists($candidate)) {
                    $parts[] = 'SSH_AUTH_SOCK='.escapeshellarg($candidate);
                    break;
                }
            }
        }

        // Detect home directory for SSH key lookup
        $home = getenv('HOME');
        if ($home === false || $home === '') {
            $info = posix_getpwuid(posix_getuid());
            $home = $info['dir'] ?? '';
        }
        if ($home !== '' && is_dir($home.'/.ssh')) {
            $parts[] = 'HOME='.escapeshellarg($home);
            // Disable strict host key checking for non-interactive clone
            $parts[] = 'GIT_SSH_COMMAND='.escapeshellarg('ssh -o StrictHostKeyChecking=accept-new -o BatchMode=yes');
        }

        return implode(' ', $parts);
    }

    private function scanRepository(string $dir): RepositoryAnalysis
    {
        $detectedFiles = [];
        $sources = [];
        $framework = null;
        $phpVersion = null;
        $nodeVersion = null;
        $hasDockerfile = false;
        $hasDockerCompose = false;
        $buildCommand = null;
        $startCommand = null;

        // 1. Check composer.json
        $composerPath = $dir.'/composer.json';
        if (file_exists($composerPath)) {
            $detectedFiles[] = 'composer.json';
            $composer = $this->readJson($composerPath);

            if ($composer !== null) {
                // Detect Laravel
                if (isset($composer['require']['laravel/framework'])) {
                    $framework = 'laravel';
                    $sources['framework'] = 'composer.json (laravel/framework)';
                }

                // Detect PHP version from require.php constraint
                if (isset($composer['require']['php'])) {
                    $phpVersion = $this->parsePhpVersionConstraint((string) $composer['require']['php']);
                    if ($phpVersion !== null) {
                        $sources['phpVersion'] = 'composer.json (require.php)';
                    }
                }
            }
        }

        // 2. Check package.json
        $packagePath = $dir.'/package.json';
        if (file_exists($packagePath)) {
            $detectedFiles[] = 'package.json';
            $package = $this->readJson($packagePath);

            if ($package !== null) {
                // Detect Node-based frameworks (only if no PHP framework found)
                if ($framework === null) {
                    $framework = $this->detectNodeFramework($package);
                    if ($framework !== null) {
                        $sources['framework'] = 'package.json (dependencies)';
                    }
                }

                // Detect Node version from engines
                if (isset($package['engines']['node'])) {
                    $nodeVersion = $this->parseNodeVersionConstraint((string) $package['engines']['node']);
                    if ($nodeVersion !== null) {
                        $sources['nodeVersion'] = 'package.json (engines.node)';
                    }
                }

                // Detect build/start commands
                if (isset($package['scripts']['build'])) {
                    $buildCommand = 'npm run build';
                    $sources['buildCommand'] = 'package.json (scripts.build)';
                }
                if (isset($package['scripts']['start'])) {
                    $startCommand = 'npm start';
                    $sources['startCommand'] = 'package.json (scripts.start)';
                }
            }
        }

        // If Laravel detected and has package.json with build script, set build command
        if ($framework === 'laravel' && $buildCommand === null && in_array('package.json', $detectedFiles, true)) {
            $package = $this->readJson($packagePath);
            if ($package !== null && isset($package['scripts']['build'])) {
                $buildCommand = 'npm run build';
                $sources['buildCommand'] = 'package.json (scripts.build)';
            }
        }

        // 3. Check .nvmrc / .node-version for Node version override
        foreach (['.nvmrc', '.node-version'] as $nodeFile) {
            $nodeFilePath = $dir.'/'.$nodeFile;
            if (file_exists($nodeFilePath)) {
                $detectedFiles[] = $nodeFile;
                $content = trim(file_get_contents($nodeFilePath) ?: '');
                $parsedVersion = $this->parseNodeVersionString($content);
                if ($parsedVersion !== null) {
                    $nodeVersion = $parsedVersion;
                    $sources['nodeVersion'] = $nodeFile;
                }
            }
        }

        // 4. Check Dockerfile
        $dockerfilePath = $dir.'/Dockerfile';
        if (file_exists($dockerfilePath)) {
            $detectedFiles[] = 'Dockerfile';
            $hasDockerfile = true;
            $dockerfileContent = file_get_contents($dockerfilePath) ?: '';

            // Parse FROM for PHP/Node versions
            if (preg_match('/^FROM\s+php:(\d+\.\d+)/m', $dockerfileContent, $matches)) {
                if ($phpVersion === null) {
                    $phpVersion = $matches[1];
                    $sources['phpVersion'] = 'Dockerfile (FROM php)';
                }
            }
            if (preg_match('/^FROM\s+node:(\d+)/m', $dockerfileContent, $matches)) {
                if ($nodeVersion === null) {
                    $nodeVersion = $matches[1];
                    $sources['nodeVersion'] = 'Dockerfile (FROM node)';
                }
            }
        }

        // 5. Check docker-compose files
        foreach (['docker-compose.yml', 'docker-compose.yaml'] as $composeFile) {
            if (file_exists($dir.'/'.$composeFile)) {
                $detectedFiles[] = $composeFile;
                $hasDockerCompose = true;
            }
        }

        // 6. Check for nginx/vhost configs (hint towards bare metal)
        foreach (['nginx.conf', 'vhost.conf'] as $webserverFile) {
            if (file_exists($dir.'/'.$webserverFile)) {
                $detectedFiles[] = $webserverFile;
            }
        }

        // 7. Determine static site if nothing else detected
        if ($framework === null && ! in_array('composer.json', $detectedFiles, true) && ! in_array('package.json', $detectedFiles, true)) {
            if (file_exists($dir.'/index.html') || file_exists($dir.'/index.htm')) {
                $framework = 'static';
                $sources['framework'] = 'index.html detected';
            }
        }

        // 8. Determine deployment method suggestion
        $suggestedDeploymentMethod = $this->suggestDeploymentMethod($framework, $hasDockerfile, $hasDockerCompose);
        if ($suggestedDeploymentMethod !== null) {
            $sources['suggestedDeploymentMethod'] = match (true) {
                $hasDockerfile || $hasDockerCompose => 'Docker files detected',
                $framework === 'laravel' => 'Laravel project (Bare Metal)',
                default => 'Framework default',
            };
        }

        // 9. Generate install/build/post-deploy commands
        $hasPackageJson = in_array('package.json', $detectedFiles, true);
        $hasComposerJson = in_array('composer.json', $detectedFiles, true);
        [$installCommands, $buildCommands, $postDeployCommands] = $this->generateDeployCommands(
            $framework, $hasComposerJson, $hasPackageJson
        );

        // 10. Determine confidence
        $confidence = $this->calculateConfidence($framework, $detectedFiles, $sources);

        return new RepositoryAnalysis(
            framework: $framework,
            phpVersion: $phpVersion,
            nodeVersion: $nodeVersion,
            hasDockerfile: $hasDockerfile,
            hasDockerCompose: $hasDockerCompose,
            suggestedDeploymentMethod: $suggestedDeploymentMethod,
            buildCommand: $buildCommand,
            startCommand: $startCommand,
            installCommands: $installCommands,
            buildCommands: $buildCommands,
            postDeployCommands: $postDeployCommands,
            detectedFiles: $detectedFiles,
            confidence: $confidence,
            sources: $sources,
        );
    }

    /**
     * Generate install, build, and post-deploy command lists based on framework.
     *
     * @return array{0: array<int, string>, 1: array<int, string>, 2: array<int, string>}
     */
    private function generateDeployCommands(?string $framework, bool $hasComposer, bool $hasPackageJson): array
    {
        $install = [];
        $build = [];
        $postDeploy = [];

        if ($framework === 'laravel') {
            if ($hasComposer) {
                $install[] = 'composer install --optimize-autoloader --no-dev --no-interaction';
            }
            if ($hasPackageJson) {
                $install[] = 'npm ci --no-audit --no-fund';
                $build[] = 'npm run build';
            }
            $postDeploy[] = 'php artisan migrate --force';
            $postDeploy[] = 'php artisan config:cache';
            $postDeploy[] = 'php artisan route:cache';
            $postDeploy[] = 'php artisan view:cache';
            $postDeploy[] = 'php artisan event:cache';
            $postDeploy[] = 'php artisan storage:link';
        } elseif (in_array($framework, ['react', 'vue', 'nextjs', 'nuxt', 'nodejs'], true)) {
            if ($hasPackageJson) {
                $install[] = 'npm ci --no-audit --no-fund';
            }
            if (in_array($framework, ['react', 'vue', 'nextjs', 'nuxt'], true) && $hasPackageJson) {
                $build[] = 'npm run build';
            }
        } else {
            // Unknown framework — add what we can detect
            if ($hasComposer) {
                $install[] = 'composer install --optimize-autoloader --no-dev --no-interaction';
            }
            if ($hasPackageJson) {
                $install[] = 'npm ci --no-audit --no-fund';
                $build[] = 'npm run build';
            }
        }

        return [$install, $build, $postDeploy];
    }

    /**
     * @param array<string, mixed> $package
     */
    private function detectNodeFramework(array $package): ?string
    {
        $deps = array_merge(
            (array) ($package['dependencies'] ?? []),
            (array) ($package['devDependencies'] ?? [])
        );

        // Priority: Next.js > Nuxt > React > Vue > Node.js
        if (isset($deps['next'])) {
            return 'nextjs';
        }
        if (isset($deps['nuxt']) || isset($deps['nuxt3'])) {
            return 'nuxt';
        }
        if (isset($deps['react'])) {
            return 'react';
        }
        if (isset($deps['vue'])) {
            return 'vue';
        }

        // Generic Node.js if has scripts but no framework
        if (isset($package['scripts']['start'])) {
            return 'nodejs';
        }

        return null;
    }

    private function parsePhpVersionConstraint(string $constraint): ?string
    {
        // Match patterns like ^8.2, >=8.3, ~8.1, 8.4.*, 8.2
        if (preg_match('/(\d+\.\d+)/', $constraint, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function parseNodeVersionConstraint(string $constraint): ?string
    {
        // Match patterns like >=18, ^20, ~22, 20.x, 20
        if (preg_match('/(\d+)/', $constraint, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function parseNodeVersionString(string $content): ?string
    {
        // Handle "v20.11.0", "20", "lts/iron", etc.
        $content = ltrim($content, 'v');

        if (preg_match('/^(\d+)/', $content, $matches)) {
            return $matches[1];
        }

        // Handle LTS codenames
        $ltsMap = [
            'lts/iron' => '20',
            'lts/hydrogen' => '18',
            'lts/*' => '20',
        ];

        return $ltsMap[strtolower($content)] ?? null;
    }

    private function suggestDeploymentMethod(?string $framework, bool $hasDockerfile, bool $hasDockerCompose): ?string
    {
        if ($hasDockerfile || $hasDockerCompose) {
            return 'docker';
        }

        if ($framework === 'laravel') {
            return 'standard';
        }

        if (in_array($framework, ['react', 'vue', 'nextjs', 'nuxt', 'nodejs'], true)) {
            return 'docker';
        }

        return null;
    }

    /**
     * @param array<int, string> $detectedFiles
     * @param array<string, string> $sources
     */
    private function calculateConfidence(?string $framework, array $detectedFiles, array $sources): string
    {
        $score = 0;

        if ($framework !== null) {
            $score += 2;
        }
        if (count($detectedFiles) >= 3) {
            $score += 2;
        } elseif (count($detectedFiles) >= 1) {
            $score += 1;
        }
        if (count($sources) >= 4) {
            $score += 1;
        }

        if ($score >= 4) {
            return 'high';
        }
        if ($score >= 2) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJson(string $path): ?array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function cleanup(string $dir): void
    {
        if ($dir === '' || ! is_dir($dir)) {
            return;
        }

        Process::run('rm -rf '.escapeshellarg($dir));
    }
}
