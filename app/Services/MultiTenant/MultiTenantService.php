<?php

declare(strict_types=1);

namespace App\Services\MultiTenant;

use App\Models\Project;
use App\Models\Tenant;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class MultiTenantService
{
    /**
     * Create a new tenant for a project
     */
    public function createTenant(Project $project, array $data): Tenant
    {
        return DB::transaction(function () use ($project, $data) {
            $tenant = Tenant::create([
                'project_id' => $project->id,
                'name' => $data['name'],
                'subdomain' => $data['subdomain'],
                'database' => $data['database'],
                'admin_email' => $data['admin_email'],
                'admin_password' => $data['admin_password'],
                'plan' => $data['plan'],
                'status' => $data['status'] ?? 'active',
                'custom_config' => $data['custom_config'] ?? [],
            ]);

            // Create tenant database
            $this->createTenantDatabase($tenant);

            // Run tenant migrations
            $this->runTenantMigrations($project, $tenant);

            return $tenant;
        });
    }

    /**
     * Delete a tenant and all associated data
     */
    public function deleteTenant(Tenant $tenant): bool
    {
        $result = DB::transaction(function () use ($tenant) {
            // Drop tenant database
            $this->dropTenantDatabase($tenant);

            // Delete tenant record
            return $tenant->delete();
        });

        return $result ?? false;
    }

    /**
     * Deploy updates to multiple tenants
     */
    public function deployToTenants(Project $project, array $tenantIds, array $options = []): array
    {
        $results = [];

        foreach ($tenantIds as $tenantId) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                $results[$tenantId] = ['status' => 'failed', 'error' => 'Tenant not found'];

                continue;
            }

            try {
                $result = $this->deployToTenant($project, $tenant, $options);
                $results[$tenantId] = ['status' => 'success', 'result' => $result];
            } catch (Exception $e) {
                $results[$tenantId] = ['status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Deploy to a single tenant
     */
    protected function deployToTenant(Project $project, Tenant $tenant, array $options): array
    {
        $projectPath = config('devflow.projects_path', '/opt/devflow/projects').'/'.$project->slug;

        // Run migrations for the tenant
        if ($options['deployment_type'] === 'code_and_migrations' || $options['deployment_type'] === 'migrations_only') {
            $this->runTenantMigrations($project, $tenant);
        }

        // Clear tenant cache if requested
        if ($options['clear_cache'] ?? false) {
            $this->clearTenantCache($project, $tenant);
        }

        // Update last deployed timestamp
        $tenant->update(['last_deployed_at' => now()]);

        return [
            'tenant_id' => $tenant->id,
            'deployed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Reset tenant data
     */
    public function resetTenant(Tenant $tenant): bool
    {
        return DB::transaction(function () use ($tenant) {
            // Drop and recreate database
            $this->dropTenantDatabase($tenant);
            $this->createTenantDatabase($tenant);

            // Run fresh migrations
            $this->runTenantMigrations($tenant->project, $tenant);

            return true;
        });
    }

    /**
     * Backup tenant data
     */
    public function backupTenant(Tenant $tenant): string
    {
        $backupPath = storage_path("backups/tenants/{$tenant->subdomain}");

        if (! file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $backupFile = $backupPath.'/'.date('Y-m-d-His').'.sql';

        // Create database backup
        $command = sprintf(
            'mysqldump -u %s -p%s %s > %s',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            $tenant->database,
            $backupFile
        );

        Process::run($command);

        return $backupFile;
    }

    /**
     * Get all tenants for a project
     */
    protected function getAllTenants(Project $project): array
    {
        return $project->tenants()->pluck('id')->toArray();
    }

    /**
     * Create tenant database
     */
    protected function createTenantDatabase(Tenant $tenant): void
    {
        // Validate database name to prevent SQL injection
        $databaseName = $this->sanitizeDatabaseName($tenant->database);

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Drop tenant database
     */
    protected function dropTenantDatabase(Tenant $tenant): void
    {
        // Validate database name to prevent SQL injection
        $databaseName = $this->sanitizeDatabaseName($tenant->database);

        DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
    }

    /**
     * Sanitize database name to prevent SQL injection
     * Only allows alphanumeric characters, underscores, and hyphens
     */
    protected function sanitizeDatabaseName(string $databaseName): string
    {
        // Remove any characters that are not alphanumeric, underscore, or hyphen
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $databaseName);

        if ($sanitized === null || $sanitized === '') {
            throw new \InvalidArgumentException('Invalid database name: Database name must contain only alphanumeric characters, underscores, and hyphens');
        }

        // Ensure database name doesn't start with a number (MySQL requirement)
        if (is_numeric($sanitized[0])) {
            throw new \InvalidArgumentException('Invalid database name: Database name cannot start with a number');
        }

        // Limit length to MySQL's maximum database name length (64 characters)
        if (strlen($sanitized) > 64) {
            throw new \InvalidArgumentException('Invalid database name: Database name cannot exceed 64 characters');
        }

        return $sanitized;
    }

    /**
     * Run migrations for a tenant
     */
    protected function runTenantMigrations(Project $project, Tenant $tenant): void
    {
        $projectPath = config('devflow.projects_path', '/opt/devflow/projects').'/'.$project->slug;

        $command = sprintf(
            'cd %s && php artisan migrate --database=mysql --force --path=database/migrations/tenant',
            $projectPath
        );

        // Set the database for this tenant
        config(['database.connections.mysql.database' => $tenant->database]);

        Process::run($command);

        // Reset to default database
        config(['database.connections.mysql.database' => config('database.connections.mysql.database')]);
    }

    /**
     * Clear cache for a tenant
     */
    protected function clearTenantCache(Project $project, Tenant $tenant): void
    {
        $projectPath = config('devflow.projects_path', '/opt/devflow/projects').'/'.$project->slug;

        $command = sprintf(
            'cd %s && php artisan cache:clear --tenant=%s',
            $projectPath,
            $tenant->id
        );

        Process::run($command);
    }
}
