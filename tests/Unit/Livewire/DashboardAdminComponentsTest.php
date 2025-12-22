<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;

use App\Livewire\Admin\AuditLogViewer;
use App\Livewire\Admin\SystemAdmin;
use App\Livewire\Analytics\AnalyticsDashboard;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard;
use App\Livewire\Dashboard\HealthDashboard;
use App\Livewire\Home\HomePublic;
use App\Livewire\Home\ProjectDetail;
use Spatie\Permission\Models\Permission;
use App\Models\Deployment;
use App\Models\Domain;
use App\Models\HealthCheck;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\SSLCertificate;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\WithPermissions;

class DashboardAdminComponentsTest extends TestCase
{
    use WithPermissions;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Process facade to prevent actual SSH calls
        Process::fake();

        // Clear dashboard caches to ensure fresh data for each test
        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_ssl_stats');
        Cache::forget('dashboard_health_stats');
        Cache::forget('dashboard_queue_stats');
    }

    /**
     * Create a user with all permissions for admin tests.
     */
    protected function createAdminUserForTest(): User
    {
        return $this->createUserWithAllPermissions();
    }

    // ============================================
    // Dashboard Component Tests
    // ============================================

    #[Test]
    public function dashboard_component_renders_correctly(): void
    {
        $user = $this->createAdminUserForTest();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.dashboard');
    }

    #[Test]
    public function dashboard_stats_loads_stats_correctly(): void
    {
        $user = $this->createAdminUserForTest();
        $onlineServers = Server::factory()->count(5)->create(['status' => 'online']);
        $offlineServers = Server::factory()->count(2)->create(['status' => 'offline']);

        $projects = Project::factory()->count(3)->create([
            'status' => 'running',
            'server_id' => $onlineServers->first()->id,
            'user_id' => $user->id,
        ]);

        Deployment::factory()->count(10)->create([
            'status' => 'success',
            'server_id' => $onlineServers->first()->id,
            'project_id' => $projects->first()->id,
            'user_id' => $user->id,
        ]);
        Deployment::factory()->count(2)->create([
            'status' => 'failed',
            'server_id' => $onlineServers->first()->id,
            'project_id' => $projects->first()->id,
            'user_id' => $user->id,
        ]);

        // Stats are now in DashboardStats child component
        Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardStats::class)
            ->assertSet('stats.total_servers', 7)
            ->assertSet('stats.online_servers', 5)
            ->assertSet('stats.total_projects', 3)
            ->assertSet('stats.running_projects', 3)
            ->assertSet('stats.total_deployments', 12)
            ->assertSet('stats.successful_deployments', 10)
            ->assertSet('stats.failed_deployments', 2);
    }

    #[Test]
    public function dashboard_recent_activity_loads_recent_deployments(): void
    {
        $user = $this->createAdminUserForTest();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'user_id' => $user->id,
        ]);
        Deployment::factory()->count(15)->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $user->id,
        ]);

        // Recent activity is now in DashboardRecentActivity child component
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardRecentActivity::class);

        $this->assertNotEmpty($component->get('recentActivity'));
    }

    #[Test]
    public function dashboard_stats_loads_ssl_stats(): void
    {
        $user = $this->createAdminUserForTest();
        $server = Server::factory()->create();
        $domain = Domain::factory()->create();

        // Create SSL certificates
        SSLCertificate::factory()->count(5)->create([
            'server_id' => $server->id,
            'domain_id' => $domain->id,
            'status' => 'issued',
            'expires_at' => now()->addDays(30),
        ]);

        SSLCertificate::factory()->count(2)->create([
            'server_id' => $server->id,
            'domain_id' => $domain->id,
            'status' => 'issued',
            'expires_at' => now()->addDays(5), // Expiring soon
        ]);

        SSLCertificate::factory()->count(1)->create([
            'server_id' => $server->id,
            'domain_id' => $domain->id,
            'status' => 'issued',
            'expires_at' => now()->subDays(5), // Expired
        ]);

        // SSL stats are now in DashboardStats child component
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardStats::class);

        $sslStats = $component->get('sslStats');
        $this->assertEquals(8, $sslStats['total_certificates']);
        $this->assertEquals(7, $sslStats['active_certificates']);
        $this->assertEquals(2, $sslStats['expiring_soon']);
        $this->assertEquals(1, $sslStats['expired']);
    }

    #[Test]
    public function dashboard_stats_loads_health_check_stats(): void
    {
        $user = $this->createAdminUserForTest();
        $project = Project::factory()->create();
        $server = Server::factory()->create();

        HealthCheck::factory()->count(3)->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'status' => 'healthy',
            'is_active' => true,
        ]);

        HealthCheck::factory()->count(2)->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'status' => 'degraded',
            'is_active' => true,
        ]);

        HealthCheck::factory()->count(1)->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'status' => 'down',
            'is_active' => true,
        ]);

        // Health check stats are now in DashboardStats child component
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardStats::class);

        $healthCheckStats = $component->get('healthCheckStats');
        $this->assertEquals(6, $healthCheckStats['total_checks']);
        $this->assertEquals(6, $healthCheckStats['active_checks']);
        $this->assertEquals(3, $healthCheckStats['healthy']);
        $this->assertEquals(2, $healthCheckStats['degraded']);
        $this->assertEquals(1, $healthCheckStats['down']);
    }

    #[Test]
    public function dashboard_stats_loads_deployments_today(): void
    {
        $user = $this->createAdminUserForTest();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'user_id' => $user->id,
        ]);
        Deployment::factory()->count(5)->create([
            'created_at' => now(),
            'server_id' => $server->id,
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        Deployment::factory()->count(3)->create([
            'created_at' => now()->subDays(2),
            'server_id' => $server->id,
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        // Deployments today is now in DashboardStats child component
        Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardStats::class)
            ->assertSet('deploymentsToday', 5);
    }

    #[Test]
    public function dashboard_recent_activity_loads_activity(): void
    {
        $user = $this->createAdminUserForTest();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
        ]);

        Deployment::factory()->count(3)->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $user->id,
        ]);

        // Recent activity is now in DashboardRecentActivity child component
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardRecentActivity::class);

        $this->assertNotEmpty($component->get('recentActivity'));
    }

    #[Test]
    public function dashboard_server_health_loads_health(): void
    {
        // Clear cache before test to ensure fresh data
        \Illuminate\Support\Facades\Cache::forget('dashboard_server_health');

        $user = $this->createAdminUserForTest();
        $server = Server::factory()->create(['status' => 'online']);

        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 45.5,
            'memory_usage' => 60.2,
            'disk_usage' => 70.3,
            'recorded_at' => now(),
        ]);

        // Server health is now in DashboardServerHealth child component
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardServerHealth::class);

        $serverHealth = $component->get('serverHealth');
        $this->assertNotEmpty($serverHealth);
        // Find the server we created by ID
        $ourServer = collect($serverHealth)->firstWhere('server_id', $server->id);
        $this->assertNotNull($ourServer, 'Our test server should be in the health list');
        $this->assertEquals(45.5, $ourServer['cpu_usage']);
    }

    #[Test]
    public function dashboard_stats_loads_queue_stats(): void
    {
        $user = $this->createAdminUserForTest();

        // Create jobs table entries
        DB::table('jobs')->insert(['queue' => 'default', 'payload' => '', 'attempts' => 0, 'reserved_at' => null, 'available_at' => now()->timestamp, 'created_at' => now()->timestamp]);

        // Queue stats are now in DashboardStats child component
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardStats::class);

        $queueStats = $component->get('queueStats');
        $this->assertArrayHasKey('pending', $queueStats);
        $this->assertArrayHasKey('failed', $queueStats);
    }

    #[Test]
    public function dashboard_stats_loads_security_score(): void
    {
        $user = $this->createAdminUserForTest();
        Server::factory()->create(['status' => 'online', 'security_score' => 85]);
        Server::factory()->create(['status' => 'online', 'security_score' => 90]);

        // Security score is now in DashboardStats child component
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardStats::class);

        $this->assertGreaterThan(0, $component->get('overallSecurityScore'));
    }

    #[Test]
    public function dashboard_loads_active_deployments(): void
    {
        $user = $this->createAdminUserForTest();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'user_id' => $user->id,
        ]);
        Deployment::factory()->count(2)->create([
            'status' => 'pending',
            'server_id' => $server->id,
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        Deployment::factory()->count(3)->create([
            'status' => 'running',
            'server_id' => $server->id,
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        Deployment::factory()->count(5)->create([
            'status' => 'success',
            'server_id' => $server->id,
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        // Dashboard still tracks activeDeployments (pending + running) in mount()
        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSet('activeDeployments', 5);
    }

    #[Test]
    public function dashboard_loads_deployment_timeline(): void
    {
        $user = $this->createAdminUserForTest();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'user_id' => $user->id,
        ]);

        // Create deployments over the last 7 days
        for ($i = 0; $i < 7; $i++) {
            Deployment::factory()->count(2)->create([
                'created_at' => now()->subDays($i),
                'status' => 'success',
                'server_id' => $server->id,
                'project_id' => $project->id,
                'user_id' => $user->id,
            ]);
        }

        // Dashboard now loads data in mount(), no need to call loadDashboardData
        $component = Livewire::actingAs($user)
            ->test(Dashboard::class);

        $this->assertCount(7, $component->get('deploymentTimeline'));
    }

    #[Test]
    public function dashboard_can_toggle_section(): void
    {
        $user = $this->createAdminUserForTest();

        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('toggleSection', 'stats_cards');

        $this->assertContains('stats_cards', $component->collapsedSections);
    }

    #[Test]
    public function dashboard_quick_actions_can_clear_all_caches(): void
    {
        $user = $this->createAdminUserForTest();

        // clearAllCaches is now in DashboardQuickActions component
        Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardQuickActions::class)
            ->call('clearAllCaches')
            ->assertDispatched('notification');
    }

    #[Test]
    public function dashboard_can_refresh_dashboard(): void
    {
        $user = $this->createAdminUserForTest();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('refreshDashboard')
            ->assertStatus(200);
    }

    #[Test]
    public function dashboard_quick_actions_can_deploy_all_projects(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $user = $this->createAdminUserForTest();
        $server = Server::factory()->create();
        Project::factory()->count(3)->create([
            'status' => 'running',
            'server_id' => $server->id,
        ]);

        // deployAll is now in DashboardQuickActions child component
        Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardQuickActions::class)
            ->call('deployAll')
            ->assertDispatched('notification');
    }

    #[Test]
    public function dashboard_handles_deployment_completed_event(): void
    {
        $user = $this->createAdminUserForTest();

        Cache::put('dashboard_stats', ['test' => 'data'], 60);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->dispatch('deployment-completed')
            ->assertStatus(200);
    }

    #[Test]
    public function dashboard_loads_user_preferences(): void
    {
        $user = $this->createAdminUserForTest();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSet('widgetOrder', Dashboard::DEFAULT_WIDGET_ORDER);
    }

    #[Test]
    public function dashboard_can_update_widget_order(): void
    {
        $user = $this->createAdminUserForTest();

        // New order must include all widgets from DEFAULT_WIDGET_ORDER
        $newOrder = ['deployment_timeline', 'stats_cards', 'quick_actions', 'activity_server_grid', 'getting_started'];

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->dispatch('widget-order-updated', order: $newOrder)
            ->assertSet('widgetOrder', $newOrder)
            ->assertDispatched('notification');
    }

    #[Test]
    public function dashboard_can_toggle_edit_mode(): void
    {
        $user = $this->createAdminUserForTest();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('toggleEditMode')
            ->assertSet('editMode', true)
            ->call('toggleEditMode')
            ->assertSet('editMode', false);
    }

    #[Test]
    public function dashboard_can_reset_widget_order(): void
    {
        $user = $this->createAdminUserForTest();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('resetWidgetOrder')
            ->assertSet('widgetOrder', Dashboard::DEFAULT_WIDGET_ORDER)
            ->assertDispatched('notification');
    }

    #[Test]
    public function dashboard_recent_activity_can_load_more_activity(): void
    {
        $user = $this->createAdminUserForTest();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'user_id' => $user->id,
        ]);

        Deployment::factory()->count(20)->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $user->id,
        ]);

        // loadMoreActivity is now in DashboardRecentActivity child component
        Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardRecentActivity::class)
            ->call('loadMoreActivity')
            ->assertSet('loadingMoreActivity', false);
    }

    // ============================================
    // HealthDashboard Component Tests
    // ============================================

    #[Test]
    public function health_dashboard_component_renders_correctly(): void
    {
        Permission::findOrCreate('view-health-checks', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-health-checks');

        Livewire::actingAs($user)
            ->test(HealthDashboard::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.dashboard.health-dashboard');
    }

    #[Test]
    public function health_dashboard_loads_projects_health(): void
    {
        Permission::findOrCreate('view-health-checks', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-health-checks');
        $project = Project::factory()->create(['status' => 'running']);
        $server = Server::factory()->create();

        Livewire::actingAs($user)
            ->test(HealthDashboard::class)
            ->assertSet('isLoading', false);
    }

    #[Test]
    public function health_dashboard_loads_servers_health(): void
    {
        Permission::findOrCreate('view-health-checks', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-health-checks');
        Server::factory()->count(3)->create(['status' => 'online']);

        $component = Livewire::actingAs($user)
            ->test(HealthDashboard::class);

        $this->assertCount(3, $component->get('serversHealth'));
    }

    #[Test]
    public function health_dashboard_can_refresh_health(): void
    {
        Permission::findOrCreate('view-health-checks', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-health-checks');

        Livewire::actingAs($user)
            ->test(HealthDashboard::class)
            ->call('refreshHealth')
            ->assertSet('isLoading', false);
    }

    #[Test]
    public function health_dashboard_filters_projects_by_status(): void
    {
        Permission::findOrCreate('view-health-checks', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-health-checks');

        Livewire::actingAs($user)
            ->test(HealthDashboard::class)
            ->set('filterStatus', 'healthy')
            ->assertStatus(200);
    }

    #[Test]
    public function health_dashboard_calculates_overall_stats(): void
    {
        Permission::findOrCreate('view-health-checks', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-health-checks');

        Livewire::actingAs($user)
            ->test(HealthDashboard::class)
            ->assertStatus(200);
    }

    #[Test]
    public function health_dashboard_checks_http_health(): void
    {
        Permission::findOrCreate('view-health-checks', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-health-checks');

        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        Livewire::actingAs($user)
            ->test(HealthDashboard::class)
            ->assertStatus(200);
    }

    // ============================================
    // SystemAdmin Component Tests
    // ============================================

    #[Test]
    public function system_admin_component_renders_correctly(): void
    {
        $user = $this->createAdminUserForTest();

        Livewire::actingAs($user)
            ->test(SystemAdmin::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.system-admin');
    }

    #[Test]
    public function system_admin_has_default_active_tab(): void
    {
        $user = $this->createAdminUserForTest();

        Livewire::actingAs($user)
            ->test(SystemAdmin::class)
            ->assertSet('activeTab', 'overview');
    }

    #[Test]
    public function system_admin_can_load_backup_stats(): void
    {
        $user = $this->createAdminUserForTest();

        Process::fake();

        Livewire::actingAs($user)
            ->test(SystemAdmin::class)
            ->call('loadBackupStats')
            ->assertStatus(200);
    }

    #[Test]
    public function system_admin_can_load_system_metrics(): void
    {
        $user = $this->createAdminUserForTest();

        Process::fake();

        Livewire::actingAs($user)
            ->test(SystemAdmin::class)
            ->call('loadSystemMetrics')
            ->assertStatus(200);
    }

    #[Test]
    public function system_admin_can_load_recent_alerts(): void
    {
        $user = $this->createAdminUserForTest();

        Process::fake();

        Livewire::actingAs($user)
            ->test(SystemAdmin::class)
            ->call('loadRecentAlerts')
            ->assertStatus(200);
    }

    #[Test]
    public function system_admin_can_run_backup_now(): void
    {
        $user = $this->createAdminUserForTest();

        Process::fake();

        Livewire::actingAs($user)
            ->test(SystemAdmin::class)
            ->call('runBackupNow')
            ->assertStatus(200);
    }

    #[Test]
    public function system_admin_can_run_optimization_now(): void
    {
        $user = $this->createAdminUserForTest();

        Process::fake();

        Livewire::actingAs($user)
            ->test(SystemAdmin::class)
            ->call('runOptimizationNow')
            ->assertStatus(200);
    }

    #[Test]
    public function system_admin_can_view_backup_logs(): void
    {
        $user = $this->createAdminUserForTest();

        Livewire::actingAs($user)
            ->test(SystemAdmin::class)
            ->call('viewBackupLogs')
            ->assertSet('activeTab', 'backup-logs');
    }

    #[Test]
    public function system_admin_can_view_monitoring_logs(): void
    {
        $user = $this->createAdminUserForTest();

        Process::fake();

        Livewire::actingAs($user)
            ->test(SystemAdmin::class)
            ->call('viewMonitoringLogs')
            ->assertSet('activeTab', 'monitoring-logs');
    }

    #[Test]
    public function system_admin_can_view_optimization_logs(): void
    {
        $user = $this->createAdminUserForTest();

        Process::fake();

        Livewire::actingAs($user)
            ->test(SystemAdmin::class)
            ->call('viewOptimizationLogs')
            ->assertSet('activeTab', 'optimization-logs');
    }

    // ============================================
    // AuditLogViewer Component Tests
    // ============================================

    #[Test]
    public function audit_log_viewer_component_renders_correctly(): void
    {
        Permission::findOrCreate('view-audit-logs', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-audit-logs');

        Livewire::actingAs($user)
            ->test(AuditLogViewer::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.audit-log-viewer');
    }

    #[Test]
    public function audit_log_viewer_can_search_logs(): void
    {
        Permission::findOrCreate('view-audit-logs', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-audit-logs');

        Livewire::actingAs($user)
            ->test(AuditLogViewer::class)
            ->set('search', 'deployment')
            ->assertSet('search', 'deployment');
    }

    #[Test]
    public function audit_log_viewer_can_filter_by_user(): void
    {
        Permission::findOrCreate('view-audit-logs', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-audit-logs');

        Livewire::actingAs($user)
            ->test(AuditLogViewer::class)
            ->set('userId', $user->id)
            ->assertSet('userId', $user->id);
    }

    #[Test]
    public function audit_log_viewer_can_filter_by_action(): void
    {
        Permission::findOrCreate('view-audit-logs', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-audit-logs');

        Livewire::actingAs($user)
            ->test(AuditLogViewer::class)
            ->set('action', 'created')
            ->assertSet('action', 'created');
    }

    #[Test]
    public function audit_log_viewer_can_filter_by_model_type(): void
    {
        Permission::findOrCreate('view-audit-logs', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-audit-logs');

        Livewire::actingAs($user)
            ->test(AuditLogViewer::class)
            ->set('modelType', 'Project')
            ->assertSet('modelType', 'Project');
    }

    #[Test]
    public function audit_log_viewer_can_filter_by_date_range(): void
    {
        Permission::findOrCreate('view-audit-logs', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-audit-logs');

        Livewire::actingAs($user)
            ->test(AuditLogViewer::class)
            ->set('fromDate', '2025-01-01')
            ->set('toDate', '2025-01-31')
            ->assertSet('fromDate', '2025-01-01')
            ->assertSet('toDate', '2025-01-31');
    }

    #[Test]
    public function audit_log_viewer_can_toggle_expand_log(): void
    {
        Permission::findOrCreate('view-audit-logs', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-audit-logs');

        Livewire::actingAs($user)
            ->test(AuditLogViewer::class)
            ->call('toggleExpand', 1)
            ->assertSet('expandedLogId', 1)
            ->call('toggleExpand', 1)
            ->assertSet('expandedLogId', null);
    }

    #[Test]
    public function audit_log_viewer_can_clear_filters(): void
    {
        Permission::findOrCreate('view-audit-logs', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-audit-logs');

        Livewire::actingAs($user)
            ->test(AuditLogViewer::class)
            ->set('search', 'test')
            ->set('userId', 1)
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('userId', null);
    }

    #[Test]
    public function audit_log_viewer_resets_page_on_search_update(): void
    {
        Permission::findOrCreate('view-audit-logs', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-audit-logs');

        $component = Livewire::actingAs($user)
            ->test(AuditLogViewer::class);

        $component->set('search', 'new search');

        $this->assertEquals('new search', $component->get('search'));
    }

    // ============================================
    // Login Component Tests
    // ============================================

    #[Test]
    public function login_component_renders_correctly(): void
    {
        Livewire::test(Login::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.auth.login');
    }

    #[Test]
    public function login_validates_email_required(): void
    {
        Livewire::test(Login::class)
            ->set('email', '')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email' => 'required']);
    }

    #[Test]
    public function login_validates_email_format(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'invalid-email')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email' => 'email']);
    }

    #[Test]
    public function login_validates_password_required(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['password' => 'required']);
    }

    #[Test]
    public function login_successful_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect('/dashboard');

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    #[Test]
    public function login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrongpassword')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertFalse(Auth::check());
    }

    #[Test]
    public function login_updates_last_login_timestamp(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'last_login_at' => null,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('login');

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    #[Test]
    public function login_remembers_user_when_remember_is_checked(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('remember', true)
            ->call('login');

        $this->assertTrue(Auth::check());
    }

    // ============================================
    // Register Component Tests
    // ============================================

    #[Test]
    public function register_component_renders_correctly(): void
    {
        Livewire::test(Register::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.auth.register');
    }

    #[Test]
    public function register_validates_name_required(): void
    {
        Livewire::test(Register::class)
            ->set('name', '')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['name' => 'required']);
    }

    #[Test]
    public function register_validates_email_required(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', '')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email' => 'required']);
    }

    #[Test]
    public function register_validates_email_format(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'invalid-email')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email' => 'email']);
    }

    #[Test]
    public function register_validates_email_unique(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email' => 'unique']);
    }

    #[Test]
    public function register_validates_password_required(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('register')
            ->assertHasErrors(['password' => 'required']);
    }

    #[Test]
    public function register_validates_password_minimum_length(): void
    {
        // Password uses Password::min(8)->mixedCase()->numbers()->symbols() rule
        // A short password will fail validation (error key varies based on Laravel version)
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('register')
            ->assertHasErrors('password');
    }

    #[Test]
    public function register_validates_password_confirmation(): void
    {
        // Use a password that meets complexity requirements to isolate the confirmation test
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'DifferentPass123!')
            ->call('register')
            ->assertHasErrors(['password' => 'confirmed']);
    }

    #[Test]
    public function register_successful_with_valid_data(): void
    {
        // Fake HTTP to bypass HaveIBeenPwned API check in Password::uncompromised()
        Http::fake(['*' => Http::response('', 200)]);

        // Password must meet: min 8 chars, mixed case, numbers, symbols
        $password = 'Xkj@92!mZqLp#45';

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', $password)
            ->set('password_confirmation', $password)
            ->call('register')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertTrue(Auth::check());
    }

    #[Test]
    public function register_hashes_password(): void
    {
        // Fake HTTP to bypass HaveIBeenPwned API check in Password::uncompromised()
        Http::fake(['*' => Http::response('', 200)]);

        // Password must meet: min 8 chars, mixed case, numbers, symbols
        $password = 'Xkj@92!mZqLp#45';

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', $password)
            ->set('password_confirmation', $password)
            ->call('register');

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue(Hash::check($password, $user->password));
    }

    // ============================================
    // ForgotPassword Component Tests
    // ============================================

    #[Test]
    public function forgot_password_component_renders_correctly(): void
    {
        Livewire::test(ForgotPassword::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.auth.forgot-password');
    }

    #[Test]
    public function forgot_password_validates_email_required(): void
    {
        Livewire::test(ForgotPassword::class)
            ->set('email', '')
            ->call('sendResetLink')
            ->assertHasErrors(['email' => 'required']);
    }

    #[Test]
    public function forgot_password_validates_email_format(): void
    {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'invalid-email')
            ->call('sendResetLink')
            ->assertHasErrors(['email' => 'email']);
    }

    #[Test]
    public function forgot_password_sends_reset_link(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'test@example.com'])
            ->andReturn(Password::RESET_LINK_SENT);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'test@example.com')
            ->call('sendResetLink')
            ->assertSet('emailSent', true);
    }

    #[Test]
    public function forgot_password_handles_invalid_email(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::INVALID_USER);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'nonexistent@example.com')
            ->call('sendResetLink')
            ->assertHasErrors('email');
    }

    // ============================================
    // HomePublic Component Tests
    // ============================================

    #[Test]
    public function home_public_component_renders_correctly(): void
    {
        Livewire::test(HomePublic::class)
            ->assertStatus(200);
    }

    // Note: HomePublic project listing/search/filter tests removed - HomePublic is a static marketing page

    // ============================================
    // ProjectDetail Component Tests
    // ============================================

    #[Test]
    public function project_detail_component_renders_correctly(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project->id,
            'is_primary' => true,
            'domain' => 'example.com',
        ]);

        Livewire::test(ProjectDetail::class, ['slug' => 'test-project'])
            ->assertStatus(200);
    }

    #[Test]
    public function project_detail_displays_project_with_valid_slug(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project->id,
            'is_primary' => true,
            'domain' => 'example.com',
        ]);

        Livewire::test(ProjectDetail::class, ['slug' => 'test-project'])
            ->assertSet('notFound', false)
            ->assertSet('project.slug', 'test-project');
    }

    #[Test]
    public function project_detail_handles_invalid_slug(): void
    {
        Livewire::test(ProjectDetail::class, ['slug' => 'non-existent'])
            ->assertSet('notFound', true)
            ->assertSet('project', null);
    }

    #[Test]
    public function project_detail_only_shows_running_projects(): void
    {
        $project = Project::factory()->create([
            'slug' => 'stopped-project',
            'status' => 'stopped',
        ]);
        Domain::factory()->create([
            'project_id' => $project->id,
            'is_primary' => true,
            'domain' => 'example.com',
        ]);

        Livewire::test(ProjectDetail::class, ['slug' => 'stopped-project'])
            ->assertSet('notFound', true)
            ->assertSet('project', null);
    }

    // ============================================
    // AnalyticsDashboard Component Tests
    // ============================================

    #[Test]
    public function analytics_dashboard_component_renders_correctly(): void
    {
        Permission::findOrCreate('view-analytics', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-analytics');

        Livewire::actingAs($user)
            ->test(AnalyticsDashboard::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.analytics.analytics-dashboard');
    }

    #[Test]
    public function analytics_dashboard_has_default_period(): void
    {
        Permission::findOrCreate('view-analytics', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-analytics');

        Livewire::actingAs($user)
            ->test(AnalyticsDashboard::class)
            ->assertSet('selectedPeriod', '7days');
    }

    #[Test]
    public function analytics_dashboard_can_change_period(): void
    {
        Permission::findOrCreate('view-analytics', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-analytics');

        Livewire::actingAs($user)
            ->test(AnalyticsDashboard::class)
            ->set('selectedPeriod', '30days')
            ->assertSet('selectedPeriod', '30days');
    }

    #[Test]
    public function analytics_dashboard_loads_deployment_stats(): void
    {
        Permission::findOrCreate('view-analytics', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-analytics');
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'user_id' => $user->id,
        ]);
        Deployment::factory()->count(10)->create([
            'status' => 'success',
            'created_at' => now()->subDays(3),
            'server_id' => $server->id,
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        Deployment::factory()->count(2)->create([
            'status' => 'failed',
            'created_at' => now()->subDays(3),
            'server_id' => $server->id,
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(AnalyticsDashboard::class)
            ->assertStatus(200)
            ->assertViewHas('deploymentStats');
    }

    #[Test]
    public function analytics_dashboard_loads_server_metrics(): void
    {
        Permission::findOrCreate('view-analytics', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-analytics');
        $server = Server::factory()->create();

        ServerMetric::factory()->count(5)->create([
            'server_id' => $server->id,
            'cpu_usage' => 50,
            'memory_usage' => 60,
            'disk_usage' => 70,
            'recorded_at' => now()->subDays(2),
        ]);

        Livewire::actingAs($user)
            ->test(AnalyticsDashboard::class)
            ->assertStatus(200);
    }

    #[Test]
    public function analytics_dashboard_loads_project_analytics(): void
    {
        Permission::findOrCreate('view-analytics', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-analytics');
        $server = Server::factory()->create();
        Project::factory()->count(5)->create([
            'status' => 'running',
            'server_id' => $server->id,
        ]);
        Project::factory()->count(2)->create([
            'status' => 'stopped',
            'server_id' => $server->id,
        ]);

        Livewire::actingAs($user)
            ->test(AnalyticsDashboard::class)
            ->assertStatus(200)
            ->assertViewHas('projectAnalytics');
    }

    #[Test]
    public function analytics_dashboard_can_filter_by_project(): void
    {
        Permission::findOrCreate('view-analytics', 'web');
        $user = $this->createAdminUserForTest();
        $user->givePermissionTo('view-analytics');
        $project = Project::factory()->create();

        Livewire::actingAs($user)
            ->test(AnalyticsDashboard::class)
            ->set('selectedProject', (string) $project->id)
            ->assertSet('selectedProject', (string) $project->id);
    }
}
