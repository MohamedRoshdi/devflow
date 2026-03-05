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
     * Directory layout:
     *   /var/www/{slug}                         -> symlink to current release
     *   /var/www/releases/{slug}/{timestamp}/   -> each release's code
     *   /var/www/shared/{slug}/.env             -> shared env file
     *   /var/www/shared/{slug}/storage/         -> shared storage dir
     *
     * @param Project $project
     * @param Deployment $deployment
     * @return void
     * @throws \RuntimeException
     */
    public function deploy(Project $project, Deployment $deployment): void
    {
        $server = $project->server;
        if (! $server) {
            throw new \RuntimeException('Project does not have an associated server');
        }

        $slug = $project->validated_slug;
        $timestamp = now()->format('YmdHis');
        $releasePath = "/var/www/releases/{$slug}/{$timestamp}";

        // Record release_path immediately so rollback works even on partial failure
        $deployment->update(['release_path' => $releasePath]);

        Log::info('Starting release deployment', [
            'project' => $slug,
            'release' => $releasePath,
        ]);

        $this->prepareDirectoryStructure($server, $slug);
        $this->cloneToRelease($server, $project, $releasePath);
        $this->linkSharedResources($server, $slug, $releasePath);
        $this->runProjectCommands($server, $project, $releasePath);
        $this->atomicSymlinkSwap($server, $slug, $releasePath);
        $this->reloadPhpFpm($server, $project);
        $this->restartWorkers($server, $project);
        $this->cleanupOldReleases($server, $slug);

        Log::info('Release deployment completed', [
            'project' => $slug,
            'release' => $releasePath,
        ]);
    }

    /**
     * Rollback to a previous release by swapping the symlink.
     *
     * @param Project $project
     * @param Deployment $targetDeployment
     * @return void
     * @throws \RuntimeException
     */
    public function rollbackToRelease(Project $project, Deployment $targetDeployment): void
    {
        $server = $project->server;
        if (! $server) {
            throw new \RuntimeException('Project does not have an associated server');
        }

        $slug = $project->validated_slug;
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
            'release' => $releasePath,
        ]);

        $this->atomicSymlinkSwap($server, $slug, $releasePath);
        $this->reloadPhpFpm($server, $project);
        $this->restartWorkers($server, $project);

        Log::info('Rollback completed', [
            'project' => $slug,
            'release' => $releasePath,
        ]);
    }

    /**
     * Create shared and release directory structure.
     */
    private function prepareDirectoryStructure(Server $server, string $slug): void
    {
        $sharedDirs = [
            "/var/www/releases/{$slug}",
            "/var/www/shared/{$slug}/storage/app/public",
            "/var/www/shared/{$slug}/storage/framework/cache",
            "/var/www/shared/{$slug}/storage/framework/sessions",
            "/var/www/shared/{$slug}/storage/framework/views",
            "/var/www/shared/{$slug}/storage/logs",
        ];

        $mkdirCmd = 'mkdir -p ' . implode(' ', $sharedDirs);
        $this->executeRemoteCommand($server, $mkdirCmd);

        // Ensure shared storage has proper ownership
        $this->executeRemoteCommand(
            $server,
            "chown -R www-data:www-data /var/www/shared/{$slug}",
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
    private function linkSharedResources(Server $server, string $slug, string $releasePath): void
    {
        // Remove release's own storage directory and link shared one
        $this->executeRemoteCommand(
            $server,
            "rm -rf {$releasePath}/storage && ln -sfn /var/www/shared/{$slug}/storage {$releasePath}/storage"
        );

        // Link shared .env file
        $this->executeRemoteCommand(
            $server,
            "ln -sfn /var/www/shared/{$slug}/.env {$releasePath}/.env"
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
    private function atomicSymlinkSwap(Server $server, string $slug, string $releasePath): void
    {
        $this->executeRemoteCommand(
            $server,
            "ln -sfn {$releasePath} /var/www/{$slug}"
        );
    }

    /**
     * Reload PHP-FPM to pick up new code paths.
     */
    private function reloadPhpFpm(Server $server, Project $project): void
    {
        $phpVersion = $project->php_version ?? '8.4';

        $this->executeRemoteCommand(
            $server,
            "systemctl reload php{$phpVersion}-fpm",
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
    private function cleanupOldReleases(Server $server, string $slug): void
    {
        $this->executeRemoteCommand(
            $server,
            "ls -dt /var/www/releases/{$slug}/*/ 2>/dev/null | tail -n +6 | xargs -r rm -rf",
            false
        );
    }
}
