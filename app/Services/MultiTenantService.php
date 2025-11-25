<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Tenant;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MultiTenantService
{
    private DockerService $dockerService;
    private array $tenantCache = [];

    public function __construct(DockerService $dockerService)
    {
        $this->dockerService = $dockerService;
    }

    /**
     * Deploy to all tenants or specific ones
     */
    public function deployToTenants(Project $project, array $tenantIds = [], array $options = []): array
    {
        if ($project->project_type !== 'multi_tenant') {
            throw new \InvalidArgumentException('Project is not multi-tenant');
        }

        $tenants = empty($tenantIds)
            ? $this->getAllTenants($project)
            : $this->getTenantsByIds($project, $tenantIds);

        $results = [
            'total' => count($tenants),
            'successful' => 0,
            'failed' => 0,
            'deployments' => []
        ];

        foreach ($tenants as $tenant) {
            try {
                $deploymentResult = $this->deployToSingleTenant($project, $tenant, $options);

                if ($deploymentResult['success']) {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                }

                $results['deployments'][] = [
                    'tenant_id' => $tenant['id'],
                    'tenant_name' => $tenant['name'],
                    'status' => $deploymentResult['success'] ? 'success' : 'failed',
                    'message' => $deploymentResult['message'] ?? null,
                    'duration' => $deploymentResult['duration'] ?? null,
                ];

            } catch (\Exception $e) {
                $results['failed']++;
                $results['deployments'][] = [
                    'tenant_id' => $tenant['id'],
                    'tenant_name' => $tenant['name'],
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];

                Log::error("Failed to deploy to tenant {$tenant['id']}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Deploy to a single tenant
     */
    private function deployToSingleTenant(Project $project, array $tenant, array $options = []): array
    {
        $startTime = microtime(true);
        $projectPath = config('devflow.projects_path') . '/' . $project->slug;

        try {
            // Step 1: Set tenant context
            $this->setTenantContext($project, $tenant);

            // Step 2: Run migrations for tenant database
            if ($options['run_migrations'] ?? true) {
                $this->runTenantMigrations($project, $tenant);
            }

            // Step 3: Clear tenant-specific caches
            if ($options['clear_cache'] ?? true) {
                $this->clearTenantCache($project, $tenant);
            }

            // Step 4: Update tenant configuration
            if ($options['update_config'] ?? false) {
                $this->updateTenantConfig($project, $tenant, $options['config'] ?? []);
            }

            // Step 5: Restart tenant services if needed
            if ($options['restart_services'] ?? false) {
                $this->restartTenantServices($project, $tenant);
            }

            // Step 6: Run health check
            if ($options['health_check'] ?? true) {
                $healthCheck = $this->checkTenantHealth($project, $tenant);
                if (!$healthCheck['healthy']) {
                    throw new \Exception("Health check failed: " . $healthCheck['error']);
                }
            }

            $duration = round(microtime(true) - $startTime, 2);

            return [
                'success' => true,
                'message' => "Successfully deployed to tenant {$tenant['name']}",
                'duration' => $duration,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2),
            ];
        }
    }

    /**
     * Set tenant context for operations
     */
    private function setTenantContext(Project $project, array $tenant): void
    {
        $projectPath = config('devflow.projects_path') . '/' . $project->slug;

        // Set tenant environment variable
        $envCommand = "cd {$projectPath} && docker-compose exec -T app php artisan tenant:switch {$tenant['id']}";
        $result = Process::run($envCommand);

        if (!$result->successful()) {
            throw new \Exception("Failed to switch tenant context: " . $result->errorOutput());
        }
    }

    /**
     * Run migrations for a specific tenant
     */
    private function runTenantMigrations(Project $project, array $tenant): void
    {
        $projectPath = config('devflow.projects_path') . '/' . $project->slug;

        // Run tenant-specific migrations
        $migrationCommand = "cd {$projectPath} && docker-compose exec -T app php artisan migrate --force --database=tenant_{$tenant['id']}";
        $result = Process::timeout(120)->run($migrationCommand);

        if (!$result->successful()) {
            throw new \Exception("Migration failed for tenant {$tenant['id']}: " . $result->errorOutput());
        }
    }

    /**
     * Clear tenant-specific caches
     */
    private function clearTenantCache(Project $project, array $tenant): void
    {
        $projectPath = config('devflow.projects_path') . '/' . $project->slug;

        $commands = [
            "cd {$projectPath} && docker-compose exec -T app php artisan cache:clear --tenant={$tenant['id']}",
            "cd {$projectPath} && docker-compose exec -T app php artisan config:clear --tenant={$tenant['id']}",
            "cd {$projectPath} && docker-compose exec -T app php artisan view:clear --tenant={$tenant['id']}",
        ];

        foreach ($commands as $command) {
            Process::run($command);
        }

        // Clear Redis cache for tenant if applicable
        if ($tenant['redis_db'] ?? null) {
            $redisCommand = "cd {$projectPath} && docker-compose exec -T redis redis-cli -n {$tenant['redis_db']} FLUSHDB";
            Process::run($redisCommand);
        }
    }

    /**
     * Update tenant configuration
     */
    private function updateTenantConfig(Project $project, array $tenant, array $config): void
    {
        // Update tenant configuration in database
        DB::table('tenant_configurations')
            ->where('tenant_id', $tenant['id'])
            ->update([
                'config' => json_encode($config),
                'updated_at' => now(),
            ]);

        // Refresh configuration cache
        $this->clearTenantCache($project, $tenant);
    }

    /**
     * Restart tenant-specific services
     */
    private function restartTenantServices(Project $project, array $tenant): void
    {
        $projectPath = config('devflow.projects_path') . '/' . $project->slug;

        // If tenant has dedicated queue workers
        if ($tenant['has_dedicated_queue'] ?? false) {
            $queueCommand = "cd {$projectPath} && docker-compose restart queue-tenant-{$tenant['id']}";
            Process::run($queueCommand);
        }

        // If tenant has dedicated services
        if ($tenant['services'] ?? null) {
            foreach ($tenant['services'] as $service) {
                $serviceCommand = "cd {$projectPath} && docker-compose restart {$service}";
                Process::run($serviceCommand);
            }
        }
    }

    /**
     * Check tenant health
     */
    private function checkTenantHealth(Project $project, array $tenant): array
    {
        try {
            // Check database connection
            $dbCheck = $this->checkTenantDatabase($project, $tenant);
            if (!$dbCheck['success']) {
                return ['healthy' => false, 'error' => 'Database connection failed'];
            }

            // Check tenant-specific endpoint if available
            if ($tenant['health_check_url'] ?? null) {
                $ch = curl_init($tenant['health_check_url']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'X-Tenant-Id: ' . $tenant['id'],
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200) {
                    return ['healthy' => false, 'error' => "Health check returned HTTP {$httpCode}"];
                }
            }

            return ['healthy' => true];

        } catch (\Exception $e) {
            return ['healthy' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check tenant database connection
     */
    private function checkTenantDatabase(Project $project, array $tenant): array
    {
        try {
            $projectPath = config('devflow.projects_path') . '/' . $project->slug;
            $command = "cd {$projectPath} && docker-compose exec -T app php artisan tenant:db:check {$tenant['id']}";
            $result = Process::timeout(10)->run($command);

            return ['success' => $result->successful()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get all tenants for a project
     */
    public function getAllTenants(Project $project): array
    {
        $cacheKey = "project_{$project->id}_tenants";

        if (isset($this->tenantCache[$cacheKey])) {
            return $this->tenantCache[$cacheKey];
        }

        $projectPath = config('devflow.projects_path') . '/' . $project->slug;
        $command = "cd {$projectPath} && docker-compose exec -T app php artisan tenant:list --json";
        $result = Process::run($command);

        if ($result->successful()) {
            $tenants = json_decode($result->output(), true) ?? [];
            $this->tenantCache[$cacheKey] = $tenants;
            return $tenants;
        }

        return [];
    }

    /**
     * Get specific tenants by IDs
     */
    private function getTenantsByIds(Project $project, array $tenantIds): array
    {
        $allTenants = $this->getAllTenants($project);

        return array_filter($allTenants, function ($tenant) use ($tenantIds) {
            return in_array($tenant['id'], $tenantIds);
        });
    }

    /**
     * Get tenant statistics
     */
    public function getTenantStats(Project $project): array
    {
        $tenants = $this->getAllTenants($project);

        $stats = [
            'total' => count($tenants),
            'active' => 0,
            'inactive' => 0,
            'suspended' => 0,
            'storage_usage' => 0,
            'database_size' => 0,
        ];

        foreach ($tenants as $tenant) {
            $status = $tenant['status'] ?? 'active';
            if ($status === 'active') {
                $stats['active']++;
            } elseif ($status === 'inactive') {
                $stats['inactive']++;
            } elseif ($status === 'suspended') {
                $stats['suspended']++;
            }

            $stats['storage_usage'] += $tenant['storage_usage'] ?? 0;
            $stats['database_size'] += $tenant['database_size'] ?? 0;
        }

        return $stats;
    }

    /**
     * Create a new tenant
     */
    public function createTenant(Project $project, array $tenantData): array
    {
        try {
            $projectPath = config('devflow.projects_path') . '/' . $project->slug;

            // Create tenant via artisan command
            $createCommand = sprintf(
                "cd %s && docker-compose exec -T app php artisan tenant:create --name='%s' --domain='%s' --email='%s'",
                $projectPath,
                $tenantData['name'],
                $tenantData['domain'],
                $tenantData['email']
            );

            $result = Process::timeout(60)->run($createCommand);

            if (!$result->successful()) {
                throw new \Exception("Failed to create tenant: " . $result->errorOutput());
            }

            // Parse the tenant ID from output
            preg_match('/Tenant created with ID: (\d+)/', $result->output(), $matches);
            $tenantId = $matches[1] ?? null;

            if (!$tenantId) {
                throw new \Exception("Failed to retrieve tenant ID");
            }

            // Run initial setup for the tenant
            $this->initializeTenant($project, $tenantId, $tenantData);

            return [
                'success' => true,
                'tenant_id' => $tenantId,
                'message' => "Tenant created successfully",
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initialize a new tenant
     */
    private function initializeTenant(Project $project, string $tenantId, array $tenantData): void
    {
        $projectPath = config('devflow.projects_path') . '/' . $project->slug;

        // Run tenant migrations
        Process::run("cd {$projectPath} && docker-compose exec -T app php artisan tenant:migrate {$tenantId}");

        // Seed initial data if needed
        if ($tenantData['seed_data'] ?? false) {
            Process::run("cd {$projectPath} && docker-compose exec -T app php artisan tenant:seed {$tenantId}");
        }

        // Configure tenant domain
        if ($tenantData['domain'] ?? null) {
            Process::run("cd {$projectPath} && docker-compose exec -T app php artisan tenant:domain {$tenantId} {$tenantData['domain']}");
        }
    }

    /**
     * Suspend or activate a tenant
     */
    public function updateTenantStatus(Project $project, string $tenantId, string $status): array
    {
        try {
            $projectPath = config('devflow.projects_path') . '/' . $project->slug;
            $command = "cd {$projectPath} && docker-compose exec -T app php artisan tenant:status {$tenantId} {$status}";

            $result = Process::run($command);

            if ($result->successful()) {
                return ['success' => true, 'message' => "Tenant status updated to {$status}"];
            }

            return ['success' => false, 'error' => $result->errorOutput()];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}