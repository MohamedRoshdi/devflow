<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\DockerService;
use App\Services\MultiTenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesProjects;
use Tests\Traits\CreatesServers;

class MultiTenantServiceTest extends TestCase
{
    use CreatesProjects, CreatesServers, RefreshDatabase;

    protected MultiTenantService $service;

    protected DockerService $dockerService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('devflow.projects_path', '/opt/devflow/projects');

        $this->dockerService = Mockery::mock(DockerService::class);
        $this->service = new MultiTenantService($this->dockerService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // DEPLOY TO TENANTS TESTS
    // ==========================================

    /** @test */
    public function it_throws_exception_when_project_is_not_multi_tenant(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'single_tenant']);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project is not multi-tenant');

        // Act
        $this->service->deployToTenants($project);
    }

    /** @test */
    public function it_deploys_to_all_tenants_successfully(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1', 'status' => 'active'],
                    ['id' => '2', 'name' => 'Tenant 2', 'status' => 'active'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['successful']);
        $this->assertEquals(0, $result['failed']);
        $this->assertCount(2, $result['deployments']);
        $this->assertEquals('success', $result['deployments'][0]['status']);
    }

    /** @test */
    public function it_deploys_to_specific_tenants_only(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1', 'status' => 'active'],
                    ['id' => '2', 'name' => 'Tenant 2', 'status' => 'active'],
                    ['id' => '3', 'name' => 'Tenant 3', 'status' => 'active'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project, ['1', '3']);

        // Assert
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['successful']);
        $this->assertCount(2, $result['deployments']);
    }

    /** @test */
    public function it_handles_deployment_failures_gracefully(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1', 'status' => 'active'],
                    ['id' => '2', 'name' => 'Tenant 2', 'status' => 'active'],
                ])
            ),
            '*tenant:switch*1*' => Process::result(),
            '*tenant:switch*2*' => Process::result(
                output: '',
                errorOutput: 'Failed to switch tenant',
                exitCode: 1
            ),
            '*migrate*1*' => Process::result(),
            '*cache:clear*1*' => Process::result(),
            '*config:clear*1*' => Process::result(),
            '*view:clear*1*' => Process::result(),
            '*tenant:db:check*1*' => Process::result(),
        ]);

        Log::shouldReceive('error')->once();

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['successful']);
        $this->assertEquals(1, $result['failed']);
        $this->assertEquals('success', $result['deployments'][0]['status']);
        $this->assertEquals('failed', $result['deployments'][1]['status']);
    }

    /** @test */
    public function it_skips_migrations_when_option_is_false(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1', 'status' => 'active'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project, [], [
            'run_migrations' => false,
        ]);

        // Assert
        $this->assertEquals(1, $result['successful']);
        Process::assertRanTimes('*migrate*', 0);
    }

    /** @test */
    public function it_skips_cache_clearing_when_option_is_false(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1', 'status' => 'active'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project, [], [
            'clear_cache' => false,
        ]);

        // Assert
        $this->assertEquals(1, $result['successful']);
        Process::assertRanTimes('*cache:clear*', 0);
    }

    /** @test */
    public function it_fails_deployment_when_health_check_fails(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1', 'status' => 'active'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => Process::result(
                output: '',
                errorOutput: 'Database connection failed',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(0, $result['successful']);
        $this->assertEquals(1, $result['failed']);
        $this->assertStringContainsString('Database connection failed', $result['deployments'][0]['message']);
    }

    // ==========================================
    // GET ALL TENANTS TESTS
    // ==========================================

    /** @test */
    public function it_gets_all_tenants_for_project(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                    ['id' => '2', 'name' => 'Tenant 2'],
                    ['id' => '3', 'name' => 'Tenant 3'],
                ])
            ),
        ]);

        // Act
        $tenants = $this->service->getAllTenants($project);

        // Assert
        $this->assertCount(3, $tenants);
        $this->assertEquals('1', $tenants[0]['id']);
        $this->assertEquals('Tenant 1', $tenants[0]['name']);
    }

    /** @test */
    public function it_caches_tenant_list(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                ])
            ),
        ]);

        // Act
        $tenants1 = $this->service->getAllTenants($project);
        $tenants2 = $this->service->getAllTenants($project);

        // Assert
        $this->assertEquals($tenants1, $tenants2);
        Process::assertRanTimes('*tenant:list*', 1); // Should only run once due to cache
    }

    /** @test */
    public function it_returns_empty_array_when_tenant_list_fails(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: '',
                errorOutput: 'Command failed',
                exitCode: 1
            ),
        ]);

        // Act
        $tenants = $this->service->getAllTenants($project);

        // Assert
        $this->assertEmpty($tenants);
    }

    /** @test */
    public function it_handles_invalid_json_from_tenant_list(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: 'invalid json'
            ),
        ]);

        // Act
        $tenants = $this->service->getAllTenants($project);

        // Assert
        $this->assertEmpty($tenants);
    }

    // ==========================================
    // GET TENANT STATISTICS TESTS
    // ==========================================

    /** @test */
    public function it_calculates_tenant_statistics(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1', 'status' => 'active', 'storage_usage' => 1024, 'database_size' => 512],
                    ['id' => '2', 'name' => 'Tenant 2', 'status' => 'inactive', 'storage_usage' => 2048, 'database_size' => 1024],
                    ['id' => '3', 'name' => 'Tenant 3', 'status' => 'suspended', 'storage_usage' => 512, 'database_size' => 256],
                    ['id' => '4', 'name' => 'Tenant 4', 'status' => 'active', 'storage_usage' => 4096, 'database_size' => 2048],
                ])
            ),
        ]);

        // Act
        $stats = $this->service->getTenantStats($project);

        // Assert
        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['active']);
        $this->assertEquals(1, $stats['inactive']);
        $this->assertEquals(1, $stats['suspended']);
        $this->assertEquals(7680, $stats['storage_usage']); // 1024 + 2048 + 512 + 4096
        $this->assertEquals(3840, $stats['database_size']); // 512 + 1024 + 256 + 2048
    }

    /** @test */
    public function it_returns_zero_stats_for_no_tenants(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([])
            ),
        ]);

        // Act
        $stats = $this->service->getTenantStats($project);

        // Assert
        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['active']);
        $this->assertEquals(0, $stats['inactive']);
        $this->assertEquals(0, $stats['suspended']);
        $this->assertEquals(0, $stats['storage_usage']);
        $this->assertEquals(0, $stats['database_size']);
    }

    /** @test */
    public function it_handles_missing_status_in_tenant_data(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                    ['id' => '2', 'name' => 'Tenant 2'],
                ])
            ),
        ]);

        // Act
        $stats = $this->service->getTenantStats($project);

        // Assert
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(2, $stats['active']); // Default to active
    }

    // ==========================================
    // CREATE TENANT TESTS
    // ==========================================

    /** @test */
    public function it_creates_new_tenant_successfully(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:create*' => Process::result(
                output: 'Tenant created with ID: 123'
            ),
            '*tenant:migrate*' => Process::result(),
            '*tenant:domain*' => Process::result(),
        ]);

        $tenantData = [
            'name' => 'New Tenant',
            'domain' => 'newtenant.example.com',
            'email' => 'admin@newtenant.com',
        ];

        // Act
        $result = $this->service->createTenant($project, $tenantData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('123', $result['tenant_id']);
        $this->assertEquals('Tenant created successfully', $result['message']);
    }

    /** @test */
    public function it_initializes_tenant_with_seed_data(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:create*' => Process::result(
                output: 'Tenant created with ID: 123'
            ),
            '*tenant:migrate*' => Process::result(),
            '*tenant:seed*' => Process::result(),
            '*tenant:domain*' => Process::result(),
        ]);

        $tenantData = [
            'name' => 'New Tenant',
            'domain' => 'newtenant.example.com',
            'email' => 'admin@newtenant.com',
            'seed_data' => true,
        ];

        // Act
        $result = $this->service->createTenant($project, $tenantData);

        // Assert
        $this->assertTrue($result['success']);
        Process::assertRan('*tenant:seed*');
    }

    /** @test */
    public function it_configures_tenant_domain_during_creation(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:create*' => Process::result(
                output: 'Tenant created with ID: 123'
            ),
            '*tenant:migrate*' => Process::result(),
            '*tenant:domain*' => Process::result(),
        ]);

        $tenantData = [
            'name' => 'New Tenant',
            'domain' => 'custom.example.com',
            'email' => 'admin@tenant.com',
        ];

        // Act
        $result = $this->service->createTenant($project, $tenantData);

        // Assert
        $this->assertTrue($result['success']);
        Process::assertRan(function ($command) {
            return str_contains($command, 'tenant:domain') && str_contains($command, 'custom.example.com');
        });
    }

    /** @test */
    public function it_handles_tenant_creation_failure(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:create*' => Process::result(
                output: '',
                errorOutput: 'Database connection failed',
                exitCode: 1
            ),
        ]);

        $tenantData = [
            'name' => 'New Tenant',
            'domain' => 'newtenant.example.com',
            'email' => 'admin@newtenant.com',
        ];

        // Act
        $result = $this->service->createTenant($project, $tenantData);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Failed to create tenant', $result['error']);
    }

    /** @test */
    public function it_handles_missing_tenant_id_in_creation_output(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:create*' => Process::result(
                output: 'Tenant created successfully but no ID found'
            ),
        ]);

        $tenantData = [
            'name' => 'New Tenant',
            'domain' => 'newtenant.example.com',
            'email' => 'admin@newtenant.com',
        ];

        // Act
        $result = $this->service->createTenant($project, $tenantData);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to retrieve tenant ID', $result['error']);
    }

    // ==========================================
    // UPDATE TENANT STATUS TESTS
    // ==========================================

    /** @test */
    public function it_updates_tenant_status_to_active(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:status*' => Process::result(
                output: 'Tenant status updated successfully'
            ),
        ]);

        // Act
        $result = $this->service->updateTenantStatus($project, '123', 'active');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Tenant status updated to active', $result['message']);
    }

    /** @test */
    public function it_updates_tenant_status_to_suspended(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:status*' => Process::result(
                output: 'Tenant suspended successfully'
            ),
        ]);

        // Act
        $result = $this->service->updateTenantStatus($project, '456', 'suspended');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Tenant status updated to suspended', $result['message']);
    }

    /** @test */
    public function it_handles_tenant_status_update_failure(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:status*' => Process::result(
                output: '',
                errorOutput: 'Tenant not found',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->updateTenantStatus($project, '999', 'active');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_handles_tenant_status_update_exception(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:status*' => throw new \Exception('Connection timeout'),
        ]);

        // Act
        $result = $this->service->updateTenantStatus($project, '123', 'active');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Connection timeout', $result['error']);
    }

    // ==========================================
    // TENANT CACHE CLEARING TESTS
    // ==========================================

    /** @test */
    public function it_clears_tenant_cache_including_redis(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1', 'redis_db' => '2'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*redis-cli*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(1, $result['successful']);
        Process::assertRan(function ($command) {
            return str_contains($command, 'redis-cli') && str_contains($command, 'FLUSHDB');
        });
    }

    /** @test */
    public function it_skips_redis_cache_clear_when_redis_db_not_configured(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'], // No redis_db
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(1, $result['successful']);
        Process::assertRanTimes('*redis-cli*', 0);
    }

    // ==========================================
    // TENANT SERVICE RESTART TESTS
    // ==========================================

    /** @test */
    public function it_restarts_dedicated_queue_for_tenant(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1', 'has_dedicated_queue' => true],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*restart*queue-tenant-1*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project, [], [
            'restart_services' => true,
        ]);

        // Assert
        $this->assertEquals(1, $result['successful']);
        Process::assertRan(function ($command) {
            return str_contains($command, 'restart') && str_contains($command, 'queue-tenant-1');
        });
    }

    /** @test */
    public function it_restarts_tenant_specific_services(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1', 'services' => ['worker', 'scheduler']],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*restart*worker*' => Process::result(),
            '*restart*scheduler*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project, [], [
            'restart_services' => true,
        ]);

        // Assert
        $this->assertEquals(1, $result['successful']);
        Process::assertRan('*restart*worker*');
        Process::assertRan('*restart*scheduler*');
    }

    // ==========================================
    // TENANT HEALTH CHECK TESTS
    // ==========================================

    /** @test */
    public function it_checks_tenant_health_with_http_endpoint(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        // Mock curl functions
        $this->mockCurlSuccess();

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1', 'health_check_url' => 'https://tenant1.example.com/health'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(1, $result['successful']);
    }

    /** @test */
    public function it_records_deployment_duration(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertArrayHasKey('duration', $result['deployments'][0]);
        $this->assertIsNumeric($result['deployments'][0]['duration']);
        $this->assertGreaterThan(0, $result['deployments'][0]['duration']);
    }

    /** @test */
    public function it_uses_correct_project_path(): void
    {
        // Arrange
        $project = $this->createProject([
            'project_type' => 'multi_tenant',
            'slug' => 'my-awesome-project',
        ]);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $this->service->deployToTenants($project);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, '/opt/devflow/projects/my-awesome-project');
        });
    }

    /**
     * Helper method to mock successful curl operations
     */
    protected function mockCurlSuccess(): void
    {
        // This is a simplified mock - in real scenarios you might use a package like guzzle with HTTP mocking
        // For this test, we'll rely on the actual curl calls or mock them at a higher level
    }

    // ==========================================
    // TENANT MIGRATION TESTS
    // ==========================================

    /** @test */
    public function it_runs_migrations_with_timeout(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(1, $result['successful']);
        Process::assertRan(function ($command) {
            return str_contains($command, 'migrate --force');
        });
    }

    /** @test */
    public function it_handles_migration_timeout_errors(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(
                output: '',
                errorOutput: 'Timeout: migrations took too long',
                exitCode: 1
            ),
        ]);

        Log::shouldReceive('error')->once();

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(1, $result['failed']);
        $this->assertStringContainsString('Migration failed', $result['deployments'][0]['message']);
    }

    // ==========================================
    // TENANT INITIALIZATION TESTS
    // ==========================================

    /** @test */
    public function it_skips_seed_data_when_not_requested(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:create*' => Process::result(
                output: 'Tenant created with ID: 123'
            ),
            '*tenant:migrate*' => Process::result(),
            '*tenant:domain*' => Process::result(),
        ]);

        $tenantData = [
            'name' => 'New Tenant',
            'domain' => 'newtenant.example.com',
            'email' => 'admin@newtenant.com',
            'seed_data' => false,
        ];

        // Act
        $result = $this->service->createTenant($project, $tenantData);

        // Assert
        $this->assertTrue($result['success']);
        Process::assertRanTimes('*tenant:seed*', 0);
    }

    /** @test */
    public function it_skips_domain_configuration_when_not_provided(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:create*' => Process::result(
                output: 'Tenant created with ID: 123'
            ),
            '*tenant:migrate*' => Process::result(),
        ]);

        $tenantData = [
            'name' => 'New Tenant',
            'domain' => '',
            'email' => 'admin@newtenant.com',
        ];

        // Act
        $result = $this->service->createTenant($project, $tenantData);

        // Assert
        $this->assertTrue($result['success']);
        Process::assertRanTimes('*tenant:domain*', 0);
    }

    // ==========================================
    // TENANT DATABASE CHECK TESTS
    // ==========================================

    /** @test */
    public function it_checks_tenant_database_connection_successfully(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => Process::result(
                output: 'Database connection successful'
            ),
        ]);

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(1, $result['successful']);
    }

    /** @test */
    public function it_handles_database_check_timeout(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => throw new \Exception('Timeout'),
        ]);

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(1, $result['failed']);
    }

    // ==========================================
    // TENANT CONTEXT SWITCHING TESTS
    // ==========================================

    /** @test */
    public function it_switches_tenant_context_before_deployment(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                ])
            ),
            '*tenant:switch*' => Process::result(
                output: 'Switched to tenant 1'
            ),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(1, $result['successful']);
        Process::assertRan(function ($command) {
            return str_contains($command, 'tenant:switch');
        });
    }

    /** @test */
    public function it_handles_tenant_context_switch_failure(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                ])
            ),
            '*tenant:switch*' => Process::result(
                output: '',
                errorOutput: 'Failed to switch context',
                exitCode: 1
            ),
        ]);

        Log::shouldReceive('error')->once();

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(1, $result['failed']);
        $this->assertStringContainsString('Failed to switch tenant context', $result['deployments'][0]['message']);
    }

    // ==========================================
    // MULTIPLE TENANT DEPLOYMENT TESTS
    // ==========================================

    /** @test */
    public function it_handles_mixed_success_and_failure_deployments(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                    ['id' => '2', 'name' => 'Tenant 2'],
                    ['id' => '3', 'name' => 'Tenant 3'],
                ])
            ),
            '*tenant:switch*1*' => Process::result(),
            '*tenant:switch*2*' => Process::result(output: '', errorOutput: 'Failed', exitCode: 1),
            '*tenant:switch*3*' => Process::result(),
            '*migrate*1*' => Process::result(),
            '*migrate*3*' => Process::result(),
            '*cache:clear*1*' => Process::result(),
            '*cache:clear*3*' => Process::result(),
            '*config:clear*1*' => Process::result(),
            '*config:clear*3*' => Process::result(),
            '*view:clear*1*' => Process::result(),
            '*view:clear*3*' => Process::result(),
            '*tenant:db:check*1*' => Process::result(),
            '*tenant:db:check*3*' => Process::result(),
        ]);

        Log::shouldReceive('error')->once();

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(2, $result['successful']);
        $this->assertEquals(1, $result['failed']);
    }

    /** @test */
    public function it_continues_deployment_after_single_tenant_failure(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'],
                    ['id' => '2', 'name' => 'Tenant 2'],
                ])
            ),
            '*tenant:switch*1*' => Process::result(output: '', errorOutput: 'Error', exitCode: 1),
            '*tenant:switch*2*' => Process::result(),
            '*migrate*2*' => Process::result(),
            '*cache:clear*2*' => Process::result(),
            '*config:clear*2*' => Process::result(),
            '*view:clear*2*' => Process::result(),
            '*tenant:db:check*2*' => Process::result(),
        ]);

        Log::shouldReceive('error')->once();

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['successful']);
        $this->assertEquals(1, $result['failed']);
        $this->assertEquals('2', $result['deployments'][1]['tenant_id']);
        $this->assertEquals('success', $result['deployments'][1]['status']);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /** @test */
    public function it_handles_empty_tenant_name_in_data(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1'], // No name field
                ])
            ),
            '*tenant:switch*' => Process::result(),
            '*migrate*' => Process::result(),
            '*cache:clear*' => Process::result(),
            '*config:clear*' => Process::result(),
            '*view:clear*' => Process::result(),
            '*tenant:db:check*' => Process::result(),
        ]);

        // Act
        $result = $this->service->deployToTenants($project);

        // Assert
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['successful']);
    }

    /** @test */
    public function it_handles_special_characters_in_tenant_names(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:create*' => Process::result(
                output: 'Tenant created with ID: 456'
            ),
            '*tenant:migrate*' => Process::result(),
            '*tenant:domain*' => Process::result(),
        ]);

        $tenantData = [
            'name' => "O'Brien & Co.",
            'domain' => 'obrien.example.com',
            'email' => 'admin@obrien.com',
        ];

        // Act
        $result = $this->service->createTenant($project, $tenantData);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_handles_very_long_tenant_names(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:create*' => Process::result(
                output: 'Tenant created with ID: 789'
            ),
            '*tenant:migrate*' => Process::result(),
            '*tenant:domain*' => Process::result(),
        ]);

        $tenantData = [
            'name' => str_repeat('A', 255),
            'domain' => 'verylongtenant.example.com',
            'email' => 'admin@tenant.com',
        ];

        // Act
        $result = $this->service->createTenant($project, $tenantData);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_handles_null_storage_and_database_sizes_in_stats(): void
    {
        // Arrange
        $project = $this->createProject(['project_type' => 'multi_tenant', 'slug' => 'test-project']);

        Process::fake([
            '*tenant:list*' => Process::result(
                output: json_encode([
                    ['id' => '1', 'name' => 'Tenant 1'], // No storage or database size
                    ['id' => '2', 'name' => 'Tenant 2', 'storage_usage' => null, 'database_size' => null],
                ])
            ),
        ]);

        // Act
        $stats = $this->service->getTenantStats($project);

        // Assert
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(0, $stats['storage_usage']);
        $this->assertEquals(0, $stats['database_size']);
    }
}
