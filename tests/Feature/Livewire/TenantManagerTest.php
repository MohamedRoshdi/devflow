<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;


use PHPUnit\Framework\Attributes\Test;
use App\Livewire\MultiTenant\TenantManager;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MultiTenant\MultiTenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class TenantManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $multiTenantProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->multiTenantProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Multi-Tenant App',
            'project_type' => 'multi_tenant',
        ]);
    }

    // ===== COMPONENT RENDERING =====

    public function test_component_renders(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.multi-tenant.tenant-manager');
    }

    public function test_component_renders_with_project_parameter(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->assertStatus(200)
            ->assertSet('selectedProject', $this->multiTenantProject->id);
    }

    public function test_component_shows_multi_tenant_projects_only(): void
    {
        $regularProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Regular Project',
            'project_type' => 'single_tenant',
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(TenantManager::class);

        $projects = $component->viewData('projects');
        $this->assertCount(1, $projects);
        $this->assertEquals($this->multiTenantProject->id, $projects->first()->id);
    }

    // ===== MOUNT =====

    public function test_mount_without_project(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class)
            ->assertSet('selectedProject', null);
    }

    public function test_mount_with_project_id(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->assertSet('selectedProject', $this->multiTenantProject->id);
    }

    // ===== SELECT PROJECT =====

    public function test_can_select_project(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class)
            ->call('selectProject', $this->multiTenantProject->id)
            ->assertSet('selectedProject', $this->multiTenantProject->id);
    }

    public function test_selecting_project_shows_tenants(): void
    {
        Tenant::factory()->count(3)->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(TenantManager::class)
            ->call('selectProject', $this->multiTenantProject->id);

        $tenants = $component->viewData('tenants');
        $this->assertCount(3, $tenants);
    }

    // ===== CREATE TENANT MODAL =====

    public function test_can_open_create_tenant_modal(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('createTenant')
            ->assertSet('showCreateModal', true);
    }

    public function test_create_tenant_modal_resets_form(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'Old Name')
            ->set('subdomain', 'old-subdomain')
            ->call('createTenant')
            ->assertSet('tenantName', '')
            ->assertSet('subdomain', '');
    }

    // ===== SAVE TENANT =====

    public function test_can_save_tenant(): void
    {
        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTenant')
                ->once()
                ->andReturn(Tenant::factory()->make([
                    'project_id' => $this->multiTenantProject->id,
                ]));
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'New Tenant')
            ->set('subdomain', 'new-tenant')
            ->set('adminEmail', 'admin@tenant.com')
            ->set('adminPassword', 'password123')
            ->set('plan', 'basic')
            ->call('saveTenant')
            ->assertDispatched('notify')
            ->assertSet('showCreateModal', false);
    }

    public function test_save_tenant_validates_required_fields(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', '')
            ->set('subdomain', '')
            ->set('adminEmail', '')
            ->set('adminPassword', '')
            ->call('saveTenant')
            ->assertHasErrors(['tenantName', 'subdomain', 'adminEmail', 'adminPassword']);
    }

    public function test_save_tenant_validates_subdomain_format(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'Test Tenant')
            ->set('subdomain', 'Invalid Subdomain!')
            ->set('adminEmail', 'admin@test.com')
            ->set('adminPassword', 'password123')
            ->call('saveTenant')
            ->assertHasErrors(['subdomain']);
    }

    public function test_save_tenant_validates_email_format(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'Test Tenant')
            ->set('subdomain', 'test-tenant')
            ->set('adminEmail', 'invalid-email')
            ->set('adminPassword', 'password123')
            ->call('saveTenant')
            ->assertHasErrors(['adminEmail']);
    }

    public function test_save_tenant_validates_password_length(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'Test Tenant')
            ->set('subdomain', 'test-tenant')
            ->set('adminEmail', 'admin@test.com')
            ->set('adminPassword', 'short')
            ->call('saveTenant')
            ->assertHasErrors(['adminPassword']);
    }

    public function test_save_tenant_validates_plan(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'Test Tenant')
            ->set('subdomain', 'test-tenant')
            ->set('adminEmail', 'admin@test.com')
            ->set('adminPassword', 'password123')
            ->set('plan', 'invalid_plan')
            ->call('saveTenant')
            ->assertHasErrors(['plan']);
    }

    public function test_save_tenant_handles_service_exception(): void
    {
        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTenant')
                ->once()
                ->andThrow(new \Exception('Failed to create database'));
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'New Tenant')
            ->set('subdomain', 'new-tenant')
            ->set('adminEmail', 'admin@tenant.com')
            ->set('adminPassword', 'password123')
            ->call('saveTenant')
            ->assertDispatched('notify', fn (string $type, string $message): bool => $type === 'error' && str_contains($message, 'Failed'));
    }

    // ===== EDIT TENANT =====

    public function test_can_edit_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
            'name' => 'Test Tenant',
            'subdomain' => 'test-tenant',
            'database' => 'tenant_test_tenant',
            'admin_email' => 'admin@test.com',
            'plan' => 'pro',
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('editTenant', $tenant)
            ->assertSet('editingTenant.id', $tenant->id)
            ->assertSet('tenantName', 'Test Tenant')
            ->assertSet('subdomain', 'test-tenant')
            ->assertSet('plan', 'pro')
            ->assertSet('showCreateModal', true);
    }

    // ===== DELETE TENANT =====

    public function test_can_delete_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('deleteTenant')
                ->once()
                ->andReturn(true);
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('deleteTenant', $tenant)
            ->assertDispatched('notify', fn (string $type): bool => $type === 'success');
    }

    public function test_delete_tenant_handles_exception(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('deleteTenant')
                ->once()
                ->andThrow(new \Exception('Database in use'));
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('deleteTenant', $tenant)
            ->assertDispatched('notify', fn (string $type): bool => $type === 'error');
    }

    // ===== TOGGLE TENANT STATUS =====

    public function test_can_toggle_tenant_status_to_suspended(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('toggleTenantStatus', $tenant)
            ->assertDispatched('notify');

        $tenant->refresh();
        $this->assertEquals('suspended', $tenant->status);
    }

    public function test_can_toggle_tenant_status_to_active(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
            'status' => 'suspended',
        ]);

        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('toggleTenantStatus', $tenant)
            ->assertDispatched('notify');

        $tenant->refresh();
        $this->assertEquals('active', $tenant->status);
    }

    // ===== DEPLOY MODAL =====

    public function test_can_open_deploy_modal(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('showDeployToTenants')
            ->assertSet('showDeployModal', true)
            ->assertSet('selectedTenants', []);
    }

    // ===== DEPLOY TO SELECTED TENANTS =====

    public function test_can_deploy_to_selected_tenants(): void
    {
        $tenants = Tenant::factory()->count(2)->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('deployToTenants')
                ->once()
                ->andReturn([
                    1 => ['status' => 'success'],
                    2 => ['status' => 'success'],
                ]);
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('selectedTenants', $tenants->pluck('id')->toArray())
            ->set('deploymentType', 'code_and_migrations')
            ->call('deployToSelectedTenants')
            ->assertDispatched('notify')
            ->assertSet('showDeployModal', false);
    }

    public function test_deploy_requires_selected_tenants(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('selectedTenants', [])
            ->call('deployToSelectedTenants')
            ->assertHasErrors(['selectedTenants']);
    }

    public function test_deploy_handles_partial_failure(): void
    {
        $tenants = Tenant::factory()->count(2)->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('deployToTenants')
                ->once()
                ->andReturn([
                    1 => ['status' => 'success'],
                    2 => ['status' => 'failed'],
                ]);
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('selectedTenants', $tenants->pluck('id')->toArray())
            ->call('deployToSelectedTenants')
            ->assertDispatched('notify', fn (string $type, string $message): bool => str_contains($message, '1 successful') && str_contains($message, '1 failed'));
    }

    public function test_deploy_handles_service_exception(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('deployToTenants')
                ->once()
                ->andThrow(new \Exception('Deployment failed'));
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('selectedTenants', [$tenant->id])
            ->call('deployToSelectedTenants')
            ->assertDispatched('notify', fn (string $type): bool => $type === 'error');
    }

    // ===== TENANT SELECTION =====

    public function test_can_toggle_tenant_selection(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('toggleTenantSelection', $tenant->id)
            ->assertSet('selectedTenants', [$tenant->id]);
    }

    public function test_can_toggle_tenant_selection_off(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('selectedTenants', [$tenant->id])
            ->call('toggleTenantSelection', $tenant->id)
            ->assertSet('selectedTenants', []);
    }

    public function test_can_select_all_tenants(): void
    {
        $tenants = Tenant::factory()->count(3)->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('selectAllTenants');

        $selectedTenants = $component->get('selectedTenants');
        $this->assertCount(3, $selectedTenants);
    }

    public function test_can_clear_selection(): void
    {
        $tenants = Tenant::factory()->count(2)->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('selectedTenants', $tenants->pluck('id')->toArray())
            ->call('clearSelection')
            ->assertSet('selectedTenants', []);
    }

    // ===== TENANT DETAILS =====

    public function test_can_show_tenant_details(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('showTenantDetails', $tenant)
            ->assertSet('editingTenant.id', $tenant->id)
            ->assertSet('showDetailsModal', true);
    }

    // ===== RESET TENANT DATA =====

    public function test_can_reset_tenant_data(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resetTenant')
                ->once()
                ->andReturn(true);
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('resetTenantData', $tenant)
            ->assertDispatched('notify', fn (string $type): bool => $type === 'success');
    }

    public function test_reset_tenant_handles_exception(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resetTenant')
                ->once()
                ->andThrow(new \Exception('Reset failed'));
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('resetTenantData', $tenant)
            ->assertDispatched('notify', fn (string $type): bool => $type === 'error');
    }

    // ===== BACKUP TENANT =====

    public function test_can_backup_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('backupTenant')
                ->once()
                ->andReturn('/path/to/backup.sql');
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('backupTenant', $tenant)
            ->assertDispatched('notify', fn (string $type): bool => $type === 'success');
    }

    public function test_backup_tenant_handles_exception(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('backupTenant')
                ->once()
                ->andThrow(new \Exception('Backup failed'));
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->call('backupTenant', $tenant)
            ->assertDispatched('notify', fn (string $type): bool => $type === 'error');
    }

    // ===== PAGINATION =====

    public function test_tenants_are_paginated(): void
    {
        Tenant::factory()->count(15)->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id]);

        $tenants = $component->viewData('tenants');
        $this->assertEquals(10, $tenants->count()); // Default pagination is 10
    }

    // ===== FORM VALIDATION EDGE CASES =====

    public function test_database_name_validation_allows_valid_format(): void
    {
        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTenant')
                ->once()
                ->andReturn(Tenant::factory()->make([
                    'project_id' => $this->multiTenantProject->id,
                ]));
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'Test Tenant')
            ->set('subdomain', 'test-tenant')
            ->set('database', 'tenant_test_db')
            ->set('adminEmail', 'admin@test.com')
            ->set('adminPassword', 'password123')
            ->call('saveTenant')
            ->assertHasNoErrors(['database']);
    }

    public function test_subdomain_allows_lowercase_and_numbers(): void
    {
        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTenant')
                ->once()
                ->andReturn(Tenant::factory()->make([
                    'project_id' => $this->multiTenantProject->id,
                ]));
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'Test Tenant')
            ->set('subdomain', 'tenant123')
            ->set('adminEmail', 'admin@test.com')
            ->set('adminPassword', 'password123')
            ->call('saveTenant')
            ->assertHasNoErrors(['subdomain']);
    }

    public function test_subdomain_allows_hyphens(): void
    {
        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTenant')
                ->once()
                ->andReturn(Tenant::factory()->make([
                    'project_id' => $this->multiTenantProject->id,
                ]));
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'Test Tenant')
            ->set('subdomain', 'test-tenant-123')
            ->set('adminEmail', 'admin@test.com')
            ->set('adminPassword', 'password123')
            ->call('saveTenant')
            ->assertHasNoErrors(['subdomain']);
    }

    // ===== DEPLOYMENT OPTIONS =====

    public function test_deploy_with_maintenance_mode(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('deployToTenants')
                ->once()
                ->with(
                    \Mockery::type(Project::class),
                    [$tenant->id],
                    \Mockery::on(fn ($options) => $options['maintenance_mode'] === true)
                )
                ->andReturn([1 => ['status' => 'success']]);
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('selectedTenants', [$tenant->id])
            ->set('maintenanceMode', true)
            ->call('deployToSelectedTenants');
    }

    public function test_deploy_with_clear_cache(): void
    {
        $tenant = Tenant::factory()->create([
            'project_id' => $this->multiTenantProject->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('deployToTenants')
                ->once()
                ->with(
                    \Mockery::type(Project::class),
                    [$tenant->id],
                    \Mockery::on(fn ($options) => $options['clear_cache'] === true)
                )
                ->andReturn([1 => ['status' => 'success']]);
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('selectedTenants', [$tenant->id])
            ->set('clearCache', true)
            ->call('deployToSelectedTenants');
    }

    // ===== PLAN TYPES =====

    public function test_can_create_tenant_with_basic_plan(): void
    {
        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTenant')
                ->once()
                ->andReturn(Tenant::factory()->make());
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'Basic Tenant')
            ->set('subdomain', 'basic-tenant')
            ->set('adminEmail', 'admin@test.com')
            ->set('adminPassword', 'password123')
            ->set('plan', 'basic')
            ->call('saveTenant')
            ->assertHasNoErrors(['plan']);
    }

    public function test_can_create_tenant_with_pro_plan(): void
    {
        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTenant')
                ->once()
                ->andReturn(Tenant::factory()->make());
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'Pro Tenant')
            ->set('subdomain', 'pro-tenant')
            ->set('adminEmail', 'admin@test.com')
            ->set('adminPassword', 'password123')
            ->set('plan', 'pro')
            ->call('saveTenant')
            ->assertHasNoErrors(['plan']);
    }

    public function test_can_create_tenant_with_enterprise_plan(): void
    {
        $this->actingAs($this->user);

        $this->mock(MultiTenantService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTenant')
                ->once()
                ->andReturn(Tenant::factory()->make());
        });

        Livewire::test(TenantManager::class, ['project' => $this->multiTenantProject->id])
            ->set('tenantName', 'Enterprise Tenant')
            ->set('subdomain', 'enterprise-tenant')
            ->set('adminEmail', 'admin@test.com')
            ->set('adminPassword', 'password123')
            ->set('plan', 'enterprise')
            ->call('saveTenant')
            ->assertHasNoErrors(['plan']);
    }

    // ===== EMPTY STATES =====

    public function test_shows_no_tenants_without_project_selection(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TenantManager::class);

        $tenants = $component->viewData('tenants');
        $this->assertNull($tenants);
    }

    public function test_shows_empty_tenants_for_new_project(): void
    {
        $newProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'project_type' => 'multi_tenant',
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(TenantManager::class, ['project' => $newProject->id]);

        $tenants = $component->viewData('tenants');
        $this->assertCount(0, $tenants);
    }
}
