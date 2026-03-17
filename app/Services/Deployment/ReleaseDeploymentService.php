<?php

declare(strict_types=1);

namespace App\Services\Deployment;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use App\Services\SupervisorConfigService;
use Illuminate\Support\Facades\Log;

class ReleaseDeploymentService
{
    use ExecutesRemoteCommands;

    public function __construct(
        private readonly SupervisorConfigService $supervisorConfigService
    ) {}

    /**
     * Execute a symlink-based zero-downtime deployment.
     *
     * Directory layout (base = getBasePath()):
     *   {base}                            -> symlink to current release
     *   {base}/../releases/{slug}/{ts}/   -> each release's code
     *   {base}/../shared/{slug}/.env      -> shared env file
     *   {base}/../shared/{slug}/storage/  -> shared storage dir
     *
     * @throws \RuntimeException
     */
    public function deploy(Project $project, Deployment $deployment): void
    {
        $server = $project->server;
        if (! $server) {
            throw new \RuntimeException('Project does not have an associated server');
        }

        $slug = $project->validated_slug;
        $basePath = $this->getBasePath($project);
        $parentDir = dirname($basePath);
        $timestamp = now()->format('YmdHis');
        $releasePath = "{$parentDir}/releases/{$slug}/{$timestamp}";

        // Record release_path immediately so rollback works even on partial failure
        $deployment->update(['release_path' => $releasePath]);

        Log::info('Starting release deployment', [
            'project' => $slug,
            'base_path' => $basePath,
            'release' => $releasePath,
        ]);

        $this->prepareDirectoryStructure($server, $slug, $parentDir);
        $this->cloneToRelease($server, $project, $releasePath);
        $this->linkSharedResources($server, $slug, $releasePath, $parentDir);
        $this->runProjectCommands($server, $project, $releasePath);
        $this->atomicSymlinkSwap($server, $basePath, $releasePath);
        $this->reloadRuntime($server, $project, $basePath);
        $this->restartWorkers($server, $project);
        $this->cleanupOldReleases($server, $slug, $parentDir);

        Log::info('Release deployment completed', [
            'project' => $slug,
            'release' => $releasePath,
        ]);
    }

    /**
     * Rollback to a previous release by swapping the symlink.
     *
     * @throws \RuntimeException
     */
    public function rollbackToRelease(Project $project, Deployment $targetDeployment): void
    {
        $server = $project->server;
        if (! $server) {
            throw new \RuntimeException('Project does not have an associated server');
        }

        $slug = $project->validated_slug;
        $basePath = $this->getBasePath($project);
        $releasePath = $targetDeployment->release_path;

        if (! $releasePath) {
            throw new \RuntimeException('Target deployment does not have a release path');
        }

        // Verify the release directory still exists on the server
        $checkResult = $this->executeRemoteCommand(
            $server,
            "test -d {$releasePath} && echo 'exists' || echo 'missing'",
            false
        );

        if (trim($checkResult->output()) !== 'exists') {
            throw new \RuntimeException("Release directory no longer exists: {$releasePath}");
        }

        Log::info('Rolling back to release', [
            'project' => $slug,
            'base_path' => $basePath,
            'release' => $releasePath,
        ]);

        $this->atomicSymlinkSwap($server, $basePath, $releasePath);
        $this->reloadRuntime($server, $project, $basePath);
        $this->restartWorkers($server, $project);

        Log::info('Rollback completed', [
            'project' => $slug,
            'release' => $releasePath,
        ]);
    }

    /**
     * Resolve the deployment base path for a project.
     *
     * Uses the project's configured deploy_path when set, otherwise
     * falls back to /var/www/{slug}.
     */
    private function getBasePath(Project $project): string
    {
        $slug = $project->validated_slug;

        return $project->deploy_path ?? "/var/www/{$slug}";
    }

    /**
     * Create shared and release directory structure.
     */
    private function prepareDirectoryStructure(Server $server, string $slug, string $parentDir): void
    {
        $sharedDirs = [
            "{$parentDir}/releases/{$slug}",
            "{$parentDir}/shared/{$slug}/storage/app/public",
            "{$parentDir}/shared/{$slug}/storage/framework/cache",
            "{$parentDir}/shared/{$slug}/storage/framework/sessions",
            "{$parentDir}/shared/{$slug}/storage/framework/views",
            "{$parentDir}/shared/{$slug}/storage/logs",
        ];

        $mkdirCmd = 'mkdir -p '.implode(' ', $sharedDirs);
        $this->executeRemoteCommand($server, $mkdirCmd);

        // Ensure shared storage has proper ownership
        $this->executeRemoteCommand(
            $server,
            "chown -R www-data:www-data {$parentDir}/shared/{$slug}",
            false
        );
    }

    /**
     * Clone the repository into the release directory.
     */
    private function cloneToRelease(Server $server, Project $project, string $releasePath): void
    {
        $branch = $project->branch ?? 'main';
        $repoUrl = $project->repository_url;

        if (! $repoUrl) {
            throw new \RuntimeException('Project does not have a repository URL');
        }

        $escapedBranch = escapeshellarg($branch);
        $escapedRepo = escapeshellarg($repoUrl);

        $this->executeRemoteCommandWithTimeout(
            $server,
            "git clone --depth 1 -b {$escapedBranch} {$escapedRepo} {$releasePath}",
            300
        );
    }

    /**
     * Link shared .env and storage into the release directory.
     */
    private function linkSharedResources(Server $server, string $slug, string $releasePath, string $parentDir): void
    {
        // Remove release's own storage directory and link shared one
        $this->executeRemoteCommand(
            $server,
            "rm -rf {$releasePath}/storage && ln -sfn {$parentDir}/shared/{$slug}/storage {$releasePath}/storage"
        );

        // Link shared .env file
        $this->executeRemoteCommand(
            $server,
            "ln -sfn {$parentDir}/shared/{$slug}/.env {$releasePath}/.env"
        );

        // Create .env from .env.example if the shared .env doesn't exist yet
        $this->executeRemoteCommand(
            $server,
            "test -f {$parentDir}/shared/{$slug}/.env || (test -f {$releasePath}/.env.example && cp {$releasePath}/.env.example {$parentDir}/shared/{$slug}/.env && php {$releasePath}/artisan key:generate --no-interaction 2>/dev/null) || true",
            false
        );
    }

    /**
     * Run the project's stored install, build, and post-deploy commands.
     * Falls back to sensible defaults if no commands are stored.
     */
    private function runProjectCommands(Server $server, Project $project, string $releasePath): void
    {
        $installCommands = $project->install_commands ?? [];
        $buildCommands = $project->build_commands ?? [];
        $postDeployCommands = $project->post_deploy_commands ?? [];

        // Fallback to defaults if project has no stored commands
        if (empty($installCommands) && empty($buildCommands) && empty($postDeployCommands)) {
            $installCommands = ['composer install --optimize-autoloader --no-dev --no-interaction'];
            $buildCommands = ['npm run build'];
            $postDeployCommands = [
                'php artisan migrate --force',
                'php artisan config:cache',
                'php artisan route:cache',
                'php artisan view:cache',
                'php artisan event:cache',
                'php artisan storage:link',
            ];
        }

        // Install dependencies (with longer timeout)
        foreach ($installCommands as $cmd) {
            $this->executeRemoteCommandWithTimeout(
                $server,
                "cd {$releasePath} && {$cmd}",
                300,
                false
            );
        }

        // Build assets
        foreach ($buildCommands as $cmd) {
            $this->executeRemoteCommandWithTimeout(
                $server,
                "cd {$releasePath} && {$cmd}",
                300,
                false
            );
        }

        // Post-deploy (artisan commands, etc.)
        foreach ($postDeployCommands as $cmd) {
            // Prefix artisan commands with the release path for proper execution
            $finalCmd = str_starts_with($cmd, 'php artisan')
                ? str_replace('php artisan', "php {$releasePath}/artisan", $cmd)
                : "cd {$releasePath} && {$cmd}";

            $this->executeRemoteCommand($server, $finalCmd, false);
        }
    }

    /**
     * Atomic symlink swap — the zero-downtime moment.
     */
    private function atomicSymlinkSwap(Server $server, string $basePath, string $releasePath): void
    {
        $this->executeRemoteCommand(
            $server,
            "ln -sfn {$releasePath} {$basePath}"
        );
    }

    /**
     * Reload the PHP runtime to pick up the new code.
     *
     * When the project uses Octane, sends an octane:reload signal so the
     * long-lived workers pick up the new files without a full restart.
     * Otherwise reloads PHP-FPM (graceful, zero-downtime).
     */
    private function reloadRuntime(Server $server, Project $project, string $basePath): void
    {
        if ($project->use_octane) {
            $this->reloadOctane($server, $basePath);
        } else {
            $this->reloadPhpFpm($server, $project);
        }
    }

    /**
     * Reload PHP-FPM to pick up new code paths.
     *
     * Tries the versioned service name first (php8.4-fpm), then the generic
     * php-fpm alias, and silently succeeds if neither is available.
     */
    private function reloadPhpFpm(Server $server, Project $project): void
    {
        $phpVersion = $project->php_version ?? '8.4';

        $this->executeRemoteCommand(
            $server,
            "sudo -n systemctl reload php{$phpVersion}-fpm 2>/dev/null || sudo -n systemctl reload php-fpm 2>/dev/null || true",
            false
        );
    }

    /**
     * Signal Laravel Octane to reload workers so they pick up the new release.
     */
    private function reloadOctane(Server $server, string $basePath): void
    {
        $this->executeRemoteCommand(
            $server,
            "php {$basePath}/artisan octane:reload 2>/dev/null || true",
            false
        );
    }

    /**
     * Restart queue workers via supervisor.
     */
    private function restartWorkers(Server $server, Project $project): void
    {
        $this->supervisorConfigService->restartWorkers($server, $project);
    }

    /**
     * Keep only the last 5 releases, delete the rest.
     */
    private function cleanupOldReleases(Server $server, string $slug, string $parentDir): void
    {
        $this->executeRemoteCommand(
            $server,
            "ls -dt {$parentDir}/releases/{$slug}/*/ 2>/dev/null | tail -n +6 | xargs -r rm -rf",
            false
        );
    }
}
