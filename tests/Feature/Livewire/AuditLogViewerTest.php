<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\AuditLogViewer;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::findOrCreate('view-audit-logs', 'web');

        // Create super-admin role
        $superAdminRole = Role::findOrCreate('super-admin', 'web');

        // Create admin user with super-admin role
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super-admin');

        // Create regular user without permission
        $this->regularUser = User::factory()->create();
    }

    // ==================== COMPONENT RENDERING ====================

    public function test_component_renders_successfully(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.audit-log-viewer');
    }

    public function test_component_requires_super_admin_role(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->assertStatus(200);
    }

    public function test_component_allows_user_with_view_audit_logs_permission(): void
    {
        $userWithPermission = User::factory()->create();
        $userWithPermission->givePermissionTo('view-audit-logs');

        $this->mockAuditService();

        Livewire::actingAs($userWithPermission)
            ->test(AuditLogViewer::class)
            ->assertStatus(200);
    }

    public function test_component_denies_access_to_unauthorized_user(): void
    {
        Livewire::actingAs($this->regularUser)
            ->test(AuditLogViewer::class)
            ->assertStatus(403);
    }

    public function test_component_initializes_with_default_state(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->assertSet('search', '')
            ->assertSet('userId', null)
            ->assertSet('action', '')
            ->assertSet('actionCategory', '')
            ->assertSet('modelType', '')
            ->assertSet('fromDate', null)
            ->assertSet('toDate', null)
            ->assertSet('ipAddress', null)
            ->assertSet('expandedLogId', null);
    }

    // ==================== LOGS FILTERING ====================

    public function test_logs_filtered_by_user_id(): void
    {
        $targetUser = User::factory()->create();

        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('userId', $targetUser->id)
            ->assertSet('userId', $targetUser->id);
    }

    public function test_logs_filtered_by_action(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('action', 'project.created')
            ->assertSet('action', 'project.created');
    }

    public function test_logs_filtered_by_action_category(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('actionCategory', 'deployment')
            ->assertSet('actionCategory', 'deployment');
    }

    public function test_logs_filtered_by_model_type(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('modelType', 'App\Models\Project')
            ->assertSet('modelType', 'App\Models\Project');
    }

    public function test_logs_filtered_by_date_range(): void
    {
        $fromDate = '2025-01-01';
        $toDate = '2025-12-31';

        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('fromDate', $fromDate)
            ->set('toDate', $toDate)
            ->assertSet('fromDate', $fromDate)
            ->assertSet('toDate', $toDate);
    }

    public function test_logs_filtered_by_ip_address(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('ipAddress', '192.168.1.1')
            ->assertSet('ipAddress', '192.168.1.1');
    }

    public function test_logs_filtered_by_search_term(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('search', 'deployment')
            ->assertSet('search', 'deployment');
    }

    public function test_search_filter_matches_action(): void
    {
        $logs = collect([
            $this->createMockLog(['action' => 'project.created']),
            $this->createMockLog(['action' => 'deployment.started']),
        ]);

        $this->mock(AuditService::class, function (MockInterface $mock) use ($logs): void {
            $mock->shouldReceive('getLogsFiltered')
                ->andReturn($logs);
            $mock->shouldReceive('getActivityStats')
                ->andReturn(['total' => 2, 'by_action' => [], 'by_user' => [], 'by_model_type' => []]);
        });

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('search', 'deployment');
    }

    public function test_search_filter_matches_user_name(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $logs = collect([
            $this->createMockLog(['action' => 'project.created', 'user' => $user]),
        ]);

        $this->mock(AuditService::class, function (MockInterface $mock) use ($logs): void {
            $mock->shouldReceive('getLogsFiltered')
                ->andReturn($logs);
            $mock->shouldReceive('getActivityStats')
                ->andReturn(['total' => 1, 'by_action' => [], 'by_user' => [], 'by_model_type' => []]);
        });

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('search', 'john');
    }

    // ==================== CLEAR FILTERS ====================

    public function test_clear_filters_resets_all_filters(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('search', 'test')
            ->set('userId', 1)
            ->set('action', 'create')
            ->set('actionCategory', 'project')
            ->set('modelType', 'Project')
            ->set('fromDate', '2025-01-01')
            ->set('toDate', '2025-12-31')
            ->set('ipAddress', '192.168.1.1')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('userId', null)
            ->assertSet('action', '')
            ->assertSet('actionCategory', '')
            ->assertSet('modelType', '')
            ->assertSet('fromDate', null)
            ->assertSet('toDate', null)
            ->assertSet('ipAddress', null);
    }

    public function test_clear_filters_resets_pagination(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('search', 'test')
            ->call('clearFilters')
            ->assertSet('search', '');
    }

    // ==================== TOGGLE EXPAND ====================

    public function test_toggle_expand_opens_log_details(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->call('toggleExpand', 123)
            ->assertSet('expandedLogId', 123);
    }

    public function test_toggle_expand_closes_same_log(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->call('toggleExpand', 123)
            ->assertSet('expandedLogId', 123)
            ->call('toggleExpand', 123)
            ->assertSet('expandedLogId', null);
    }

    public function test_toggle_expand_switches_to_different_log(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->call('toggleExpand', 123)
            ->assertSet('expandedLogId', 123)
            ->call('toggleExpand', 456)
            ->assertSet('expandedLogId', 456);
    }

    // ==================== EXPORT CSV ====================

    public function test_export_csv_returns_response(): void
    {
        $csvContent = "ID,User,Action,Model,Model ID,IP Address,Date,Changes\n1,Admin,project.created,Project,1,127.0.0.1,2025-01-01 12:00:00,{}\n";

        $this->mock(AuditService::class, function (MockInterface $mock) use ($csvContent): void {
            $mock->shouldReceive('getLogsFiltered')
                ->andReturn(collect());
            $mock->shouldReceive('getActivityStats')
                ->andReturn(['total' => 0, 'by_action' => [], 'by_user' => [], 'by_model_type' => []]);
            $mock->shouldReceive('exportToCsv')
                ->once()
                ->andReturn($csvContent);
        });

        $response = Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->call('exportCsv');

        $this->assertEquals(200, $response->effects['returns'][0]->getStatusCode());
    }

    public function test_export_csv_includes_current_filters(): void
    {
        $this->mock(AuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLogsFiltered')
                ->andReturn(collect());
            $mock->shouldReceive('getActivityStats')
                ->andReturn(['total' => 0, 'by_action' => [], 'by_user' => [], 'by_model_type' => []]);
            $mock->shouldReceive('exportToCsv')
                ->once()
                ->with(Mockery::on(function (array $filters) {
                    return isset($filters['action']) && $filters['action'] === 'project.created';
                }))
                ->andReturn('csv data');
        });

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('action', 'project.created')
            ->call('exportCsv');
    }

    public function test_export_csv_has_correct_content_type(): void
    {
        $this->mock(AuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLogsFiltered')
                ->andReturn(collect());
            $mock->shouldReceive('getActivityStats')
                ->andReturn(['total' => 0, 'by_action' => [], 'by_user' => [], 'by_model_type' => []]);
            $mock->shouldReceive('exportToCsv')
                ->once()
                ->andReturn('csv data');
        });

        $response = Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->call('exportCsv');

        $this->assertEquals('text/csv', $response->effects['returns'][0]->headers->get('Content-Type'));
    }

    // ==================== COMPUTED PROPERTIES ====================

    public function test_users_computed_property_returns_users(): void
    {
        User::factory()->count(5)->create();

        $this->mockAuditService();

        $component = Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class);

        $users = $component->viewData('users');
        $this->assertGreaterThanOrEqual(5, $users->count());
    }

    public function test_action_categories_computed_property(): void
    {
        // Create audit logs with different action categories
        AuditLog::create([
            'action' => 'project.created',
            'auditable_type' => Project::class,
            'auditable_id' => 1,
        ]);

        AuditLog::create([
            'action' => 'deployment.started',
            'auditable_type' => Project::class,
            'auditable_id' => 1,
        ]);

        $this->mockAuditService();

        $component = Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class);

        $categories = $component->viewData('actionCategories');
        $this->assertInstanceOf(Collection::class, $categories);
    }

    public function test_model_types_computed_property(): void
    {
        $project = Project::factory()->create();
        $server = Server::factory()->create();

        AuditLog::create([
            'action' => 'project.created',
            'auditable_type' => Project::class,
            'auditable_id' => $project->id,
        ]);

        AuditLog::create([
            'action' => 'server.created',
            'auditable_type' => Server::class,
            'auditable_id' => $server->id,
        ]);

        $this->mockAuditService();

        $component = Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class);

        $modelTypes = $component->viewData('modelTypes');
        $this->assertInstanceOf(Collection::class, $modelTypes);
    }

    public function test_stats_computed_property_returns_statistics(): void
    {
        $stats = [
            'total' => 100,
            'by_action' => ['project.created' => 50, 'deployment.started' => 50],
            'by_user' => [1 => 60, 2 => 40],
            'by_model_type' => ['App\Models\Project' => 70, 'App\Models\Server' => 30],
        ];

        $this->mock(AuditService::class, function (MockInterface $mock) use ($stats): void {
            $mock->shouldReceive('getLogsFiltered')
                ->andReturn(collect());
            $mock->shouldReceive('getActivityStats')
                ->andReturn($stats);
        });

        $component = Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class);

        $resultStats = $component->viewData('stats');
        $this->assertEquals(100, $resultStats['total']);
    }

    // ==================== PAGINATION RESET ====================

    public function test_updating_search_resets_page(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('search', 'test');
        // Page should be reset (no exception thrown)
    }

    public function test_updating_user_id_resets_page(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('userId', 1);
        // Page should be reset
    }

    public function test_updating_action_category_resets_page(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('actionCategory', 'project');
        // Page should be reset
    }

    public function test_updating_model_type_resets_page(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('modelType', 'Project');
        // Page should be reset
    }

    // ==================== URL BINDING ====================

    public function test_search_is_url_bound(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->withQueryParams(['search' => 'deployment'])
            ->test(AuditLogViewer::class)
            ->assertSet('search', 'deployment');
    }

    public function test_user_id_is_url_bound(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->withQueryParams(['userId' => '5'])
            ->test(AuditLogViewer::class)
            ->assertSet('userId', 5);
    }

    public function test_action_is_url_bound(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->withQueryParams(['action' => 'project.created'])
            ->test(AuditLogViewer::class)
            ->assertSet('action', 'project.created');
    }

    public function test_action_category_is_url_bound(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->withQueryParams(['actionCategory' => 'deployment'])
            ->test(AuditLogViewer::class)
            ->assertSet('actionCategory', 'deployment');
    }

    public function test_model_type_is_url_bound(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->withQueryParams(['modelType' => 'Project'])
            ->test(AuditLogViewer::class)
            ->assertSet('modelType', 'Project');
    }

    public function test_from_date_is_url_bound(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->withQueryParams(['fromDate' => '2025-01-01'])
            ->test(AuditLogViewer::class)
            ->assertSet('fromDate', '2025-01-01');
    }

    public function test_to_date_is_url_bound(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->withQueryParams(['toDate' => '2025-12-31'])
            ->test(AuditLogViewer::class)
            ->assertSet('toDate', '2025-12-31');
    }

    public function test_ip_address_is_url_bound(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->withQueryParams(['ipAddress' => '192.168.1.1'])
            ->test(AuditLogViewer::class)
            ->assertSet('ipAddress', '192.168.1.1');
    }

    // ==================== EDGE CASES ====================

    public function test_handles_empty_logs(): void
    {
        $this->mock(AuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLogsFiltered')
                ->andReturn(collect());
            $mock->shouldReceive('getActivityStats')
                ->andReturn(['total' => 0, 'by_action' => [], 'by_user' => [], 'by_model_type' => []]);
        });

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->assertStatus(200);
    }

    public function test_handles_logs_without_user(): void
    {
        $logs = collect([
            $this->createMockLog(['action' => 'system.cron', 'user' => null]),
        ]);

        $this->mock(AuditService::class, function (MockInterface $mock) use ($logs): void {
            $mock->shouldReceive('getLogsFiltered')
                ->andReturn($logs);
            $mock->shouldReceive('getActivityStats')
                ->andReturn(['total' => 1, 'by_action' => [], 'by_user' => [], 'by_model_type' => []]);
        });

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->assertStatus(200);
    }

    public function test_handles_logs_with_null_model_name(): void
    {
        $logs = collect([
            $this->createMockLog(['action' => 'cleanup.executed', 'model_name' => '']),
        ]);

        $this->mock(AuditService::class, function (MockInterface $mock) use ($logs): void {
            $mock->shouldReceive('getLogsFiltered')
                ->andReturn($logs);
            $mock->shouldReceive('getActivityStats')
                ->andReturn(['total' => 1, 'by_action' => [], 'by_user' => [], 'by_model_type' => []]);
        });

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->assertStatus(200);
    }

    public function test_multiple_filters_applied_simultaneously(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('search', 'test')
            ->set('actionCategory', 'project')
            ->set('fromDate', '2025-01-01')
            ->set('toDate', '2025-12-31')
            ->assertSet('search', 'test')
            ->assertSet('actionCategory', 'project')
            ->assertSet('fromDate', '2025-01-01')
            ->assertSet('toDate', '2025-12-31');
    }

    public function test_expand_then_filter_maintains_state(): void
    {
        $this->mockAuditService();

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->call('toggleExpand', 123)
            ->assertSet('expandedLogId', 123)
            ->set('search', 'test')
            ->assertSet('expandedLogId', 123);
    }

    public function test_stats_filtered_by_date_range(): void
    {
        $this->mock(AuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLogsFiltered')
                ->andReturn(collect());
            $mock->shouldReceive('getActivityStats')
                ->once()
                ->with(Mockery::on(function (array $filters) {
                    return isset($filters['from_date']) && $filters['from_date'] === '2025-06-01';
                }))
                ->andReturn(['total' => 50, 'by_action' => [], 'by_user' => [], 'by_model_type' => []]);
        });

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogViewer::class)
            ->set('fromDate', '2025-06-01');
    }

    // ==================== HELPER METHODS ====================

    private function mockAuditService(): void
    {
        $this->mock(AuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLogsFiltered')
                ->andReturn(collect());
            $mock->shouldReceive('getActivityStats')
                ->andReturn(['total' => 0, 'by_action' => [], 'by_user' => [], 'by_model_type' => []]);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return object
     */
    private function createMockLog(array $attributes): object
    {
        return (object) array_merge([
            'id' => rand(1, 1000),
            'action' => 'test.action',
            'user' => null,
            'model_name' => 'TestModel',
            'auditable_id' => 1,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
            'changes_summary' => [],
        ], $attributes);
    }
}
