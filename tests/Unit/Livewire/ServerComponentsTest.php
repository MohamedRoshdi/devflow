<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;

use App\Livewire\Servers\ResourceAlertManager;
use App\Livewire\Servers\Security\Fail2banManager;
use App\Livewire\Servers\Security\FirewallManager;
use App\Livewire\Servers\Security\SecurityScanDashboard;
use App\Livewire\Servers\Security\ServerSecurityDashboard;
use App\Livewire\Servers\Security\SSHSecurityManager;
use App\Livewire\Servers\ServerBackupManager;
use App\Livewire\Servers\ServerCreate;
use App\Livewire\Servers\ServerEdit;
use App\Livewire\Servers\ServerList;
use App\Livewire\Servers\ServerMetricsDashboard;
use App\Livewire\Servers\ServerProvisioning;
use App\Livewire\Servers\ServerShow;
use App\Livewire\Servers\ServerTagAssignment;
use App\Livewire\Servers\ServerTagManager;
use App\Livewire\Servers\SSHTerminal;
use App\Livewire\Servers\SSLManager;
use App\Models\ResourceAlert;
use App\Models\SecurityScan;
use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\ServerBackupSchedule;
use App\Models\ServerMetric;
use App\Models\ServerTag;
use App\Models\SSLCertificate;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\TestCase;

class ServerComponentsTest extends TestCase
{

    protected User $user;

    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        Process::fake();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['user_id' => $this->user->id]);
    }

    // ==========================================
    // ServerList Component Tests
    // ==========================================

    /** @test */
    public function server_list_renders_for_authenticated_user(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function server_list_shows_all_servers(): void
    {
        $server1 = Server::factory()->create(['name' => 'Test Server 1']);
        $server2 = Server::factory()->create(['name' => 'Test Server 2']);

        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->assertSee('Test Server 1')
            ->assertSee('Test Server 2');
    }

    /** @test */
    public function server_list_can_filter_by_search(): void
    {
        Server::factory()->create(['name' => 'Production Server']);
        Server::factory()->create(['name' => 'Development Server']);

        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->set('search', 'Production')
            ->assertSee('Production Server')
            ->assertDontSee('Development Server');
    }

    /** @test */
    public function server_list_can_filter_by_status(): void
    {
        Server::factory()->create(['status' => 'online']);
        Server::factory()->create(['status' => 'offline']);

        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->set('statusFilter', 'online')
            ->assertPropertyWired('statusFilter');
    }

    /** @test */
    public function server_list_can_ping_single_server(): void
    {
        Process::fake([
            '*' => Process::result(output: 'online', exitCode: 0),
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->call('pingServer', $this->server->id)
            ->assertHasNoErrors();

        $this->server->refresh();
        $this->assertNotNull($this->server->last_ping_at);
    }

    /** @test */
    public function server_list_can_ping_all_servers(): void
    {
        Process::fake();

        Server::factory()->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->call('pingAllServers')
            ->assertSet('isPingingAll', false);
    }

    /** @test */
    public function server_list_can_delete_server(): void
    {
        $server = Server::factory()->create();
        $serverId = $server->id;

        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->call('deleteServer', $serverId);

        $this->assertDatabaseMissing('servers', ['id' => $serverId]);
    }

    /** @test */
    public function server_list_can_reboot_server(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->call('rebootServer', $this->server->id)
            ->assertHasNoErrors();
    }

    /** @test */
    public function server_list_can_add_current_server(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->call('addCurrentServer')
            ->assertDispatched('server-created');
    }

    /** @test */
    public function server_list_can_toggle_server_selection(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->call('toggleServerSelection', $this->server->id)
            ->assertSet('selectedServers', [$this->server->id]);
    }

    /** @test */
    public function server_list_can_toggle_select_all(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->call('toggleSelectAll')
            ->assertSet('selectAll', true);
    }

    /** @test */
    public function server_list_can_clear_selection(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->set('selectedServers', [$this->server->id])
            ->call('clearSelection')
            ->assertSet('selectedServers', [])
            ->assertSet('selectAll', false);
    }

    /** @test */
    public function server_list_can_bulk_ping(): void
    {
        Process::fake();

        $servers = Server::factory()->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->set('selectedServers', $servers->pluck('id')->toArray())
            ->call('bulkPing')
            ->assertSet('showResultsModal', true);
    }

    /** @test */
    public function server_list_bulk_ping_requires_selection(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->call('bulkPing')
            ->assertHasNoErrors();
    }

    /** @test */
    public function server_list_can_bulk_reboot(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->set('selectedServers', [$this->server->id])
            ->call('bulkReboot')
            ->assertSet('showResultsModal', true);
    }

    /** @test */
    public function server_list_can_bulk_install_docker(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->set('selectedServers', [$this->server->id])
            ->call('bulkInstallDocker')
            ->assertSet('showResultsModal', true);
    }

    /** @test */
    public function server_list_can_toggle_tag_filter(): void
    {
        $tag = ServerTag::factory()->create();

        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->call('toggleTagFilter', $tag->id)
            ->assertSet('tagFilter', [$tag->id]);
    }

    /** @test */
    public function server_list_can_close_results_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerList::class)
            ->set('showResultsModal', true)
            ->call('closeResultsModal')
            ->assertSet('showResultsModal', false);
    }

    // ==========================================
    // ServerShow Component Tests
    // ==========================================

    /** @test */
    public function server_show_renders_for_authorized_user(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function server_show_loads_metrics(): void
    {
        ServerMetric::factory()->count(5)->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertPropertyWired('recentMetrics');
    }

    /** @test */
    public function server_show_can_ping_server(): void
    {
        Process::fake([
            '*' => Process::result(output: 'online', exitCode: 0),
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('pingServer')
            ->assertHasNoErrors();

        $this->server->refresh();
        $this->assertNotNull($this->server->last_ping_at);
    }

    /** @test */
    public function server_show_can_check_docker_status(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('checkDockerStatus')
            ->assertHasNoErrors();
    }

    /** @test */
    public function server_show_can_install_docker(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('installDocker')
            ->assertSet('dockerInstalling', true);
    }

    /** @test */
    public function server_show_can_reboot_server(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('rebootServer')
            ->assertHasNoErrors();
    }

    /** @test */
    public function server_show_can_restart_service(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('restartService', 'nginx')
            ->assertHasNoErrors();
    }

    /** @test */
    public function server_show_can_clear_system_cache(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('clearSystemCache')
            ->assertHasNoErrors();
    }

    /** @test */
    public function server_show_checks_docker_install_progress(): void
    {
        Cache::put("docker_install_{$this->server->id}", [
            'status' => 'installing',
            'message' => 'Installing Docker...',
        ], 3600);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('checkDockerInstallProgress')
            ->assertSet('dockerInstalling', true);
    }

    /** @test */
    public function server_show_can_clear_docker_install_status(): void
    {
        Cache::put("docker_install_{$this->server->id}", [
            'status' => 'completed',
        ], 3600);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('clearDockerInstallStatus')
            ->assertSet('dockerInstalling', false);

        $this->assertFalse(Cache::has("docker_install_{$this->server->id}"));
    }

    // ==========================================
    // ServerCreate Component Tests
    // ==========================================

    /** @test */
    public function server_create_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->assertStatus(200);
    }

    /** @test */
    public function server_create_validates_required_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->call('createServer')
            ->assertHasErrors(['name', 'ip_address', 'port', 'username']);
    }

    /** @test */
    public function server_create_validates_ip_address(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('ip_address', 'invalid-ip')
            ->call('createServer')
            ->assertHasErrors(['ip_address']);
    }

    /** @test */
    public function server_create_can_create_server_with_password(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'New Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('auth_method', 'password')
            ->set('ssh_password', 'secret')
            ->call('createServer')
            ->assertRedirect();

        $this->assertDatabaseHas('servers', [
            'name' => 'New Server',
            'ip_address' => '192.168.1.100',
        ]);
    }

    /** @test */
    public function server_create_can_create_server_with_key(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'New Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('auth_method', 'key')
            ->set('ssh_key', 'ssh-rsa AAAAB3...')
            ->call('createServer')
            ->assertRedirect();
    }

    /** @test */
    public function server_create_can_test_connection(): void
    {
        Process::fake([
            '*' => Process::result(output: 'Connection successful', exitCode: 0),
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('auth_method', 'password')
            ->set('ssh_password', 'secret')
            ->call('testConnection')
            ->assertHasNoErrors();
    }

    /** @test */
    public function server_create_validates_auth_method_requirements(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('auth_method', 'password')
            ->set('name', 'Test')
            ->set('ip_address', '192.168.1.1')
            ->set('port', 22)
            ->set('username', 'root')
            ->call('createServer')
            ->assertHasErrors(['ssh_password']);
    }

    // ==========================================
    // ServerEdit Component Tests
    // ==========================================

    /** @test */
    public function server_edit_renders_for_authorized_user(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function server_edit_loads_server_data(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->assertSet('name', $this->server->name)
            ->assertSet('ip_address', $this->server->ip_address);
    }

    /** @test */
    public function server_edit_can_update_server(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('name', 'Updated Server Name')
            ->call('updateServer')
            ->assertRedirect();

        $this->server->refresh();
        $this->assertEquals('Updated Server Name', $this->server->name);
    }

    /** @test */
    public function server_edit_validates_required_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('name', '')
            ->call('updateServer')
            ->assertHasErrors(['name']);
    }

    /** @test */
    public function server_edit_can_test_connection(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->call('testConnection')
            ->assertHasNoErrors();
    }

    /** @test */
    public function server_edit_can_update_credentials(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('auth_method', 'password')
            ->set('ssh_password', 'new-password')
            ->call('updateServer')
            ->assertRedirect();
    }

    // ==========================================
    // ServerProvisioning Component Tests
    // ==========================================

    /** @test */
    public function server_provisioning_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function server_provisioning_has_default_options(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->assertSet('installNginx', true)
            ->assertSet('installPHP', true)
            ->assertSet('phpVersion', '8.4');
    }

    /** @test */
    public function server_provisioning_can_open_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->call('openProvisioningModal')
            ->assertSet('showProvisioningModal', true);
    }

    /** @test */
    public function server_provisioning_can_close_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('showProvisioningModal', true)
            ->call('closeProvisioningModal')
            ->assertSet('showProvisioningModal', false);
    }

    /** @test */
    public function server_provisioning_validates_options(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('phpVersion', 'invalid')
            ->call('startProvisioning')
            ->assertHasErrors(['phpVersion']);
    }

    /** @test */
    public function server_provisioning_can_start_provisioning(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->call('startProvisioning')
            ->assertDispatched('provisioning-started');
    }

    /** @test */
    public function server_provisioning_can_refresh_status(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->call('refreshServerStatus')
            ->assertHasNoErrors();
    }

    // ==========================================
    // ServerMetricsDashboard Component Tests
    // ==========================================

    /** @test */
    public function server_metrics_dashboard_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function server_metrics_dashboard_loads_metrics(): void
    {
        ServerMetric::factory()->count(10)->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertPropertyWired('metrics');
    }

    /** @test */
    public function server_metrics_dashboard_can_refresh_metrics(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->call('refreshMetrics')
            ->assertSet('isCollecting', false);
    }

    /** @test */
    public function server_metrics_dashboard_can_set_period(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->call('setPeriod', '24h')
            ->assertSet('period', '24h');
    }

    /** @test */
    public function server_metrics_dashboard_can_toggle_live_mode(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->set('liveMode', true)
            ->call('toggleLiveMode')
            ->assertSet('liveMode', false);
    }

    /** @test */
    public function server_metrics_dashboard_can_switch_process_view(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->call('switchProcessView', 'memory')
            ->assertSet('processView', 'memory');
    }

    /** @test */
    public function server_metrics_dashboard_can_refresh_processes(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->call('refreshProcesses')
            ->assertHasNoErrors();
    }

    /** @test */
    public function server_metrics_dashboard_computes_chart_data(): void
    {
        ServerMetric::factory()->count(5)->create(['server_id' => $this->server->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server]);

        $chartData = $component->get('chartData');
        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('cpu', $chartData);
        $this->assertArrayHasKey('memory', $chartData);
    }

    /** @test */
    public function server_metrics_dashboard_computes_alert_status(): void
    {
        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 95.0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server]);

        $alertStatus = $component->get('alertStatus');
        $this->assertIsArray($alertStatus);
        $this->assertArrayHasKey('status', $alertStatus);
        $this->assertArrayHasKey('alerts', $alertStatus);
    }

    // ==========================================
    // ServerBackupManager Component Tests
    // ==========================================

    /** @test */
    public function server_backup_manager_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function server_backup_manager_can_create_backup(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('backupType', 'full')
            ->set('storageDriver', 'local')
            ->call('createBackup')
            ->assertSet('showCreateModal', false);
    }

    /** @test */
    public function server_backup_manager_validates_backup_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('backupType', 'invalid')
            ->call('createBackup')
            ->assertHasErrors(['backupType']);
    }

    /** @test */
    public function server_backup_manager_can_delete_backup(): void
    {
        $backup = ServerBackup::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteBackup', $backup->id)
            ->assertHasNoErrors();
    }

    /** @test */
    public function server_backup_manager_can_restore_backup(): void
    {
        $backup = ServerBackup::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'completed',
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('restoreBackup', $backup->id)
            ->assertHasNoErrors();
    }

    /** @test */
    public function server_backup_manager_can_create_schedule(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleType', 'full')
            ->set('scheduleFrequency', 'daily')
            ->set('scheduleTime', '02:00')
            ->set('retentionDays', 30)
            ->set('scheduleStorageDriver', 'local')
            ->call('createSchedule')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('server_backup_schedules', [
            'server_id' => $this->server->id,
            'type' => 'full',
            'frequency' => 'daily',
        ]);
    }

    /** @test */
    public function server_backup_manager_can_toggle_schedule(): void
    {
        $schedule = ServerBackupSchedule::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('toggleSchedule', $schedule->id)
            ->assertHasNoErrors();

        $schedule->refresh();
        $this->assertFalse($schedule->is_active);
    }

    /** @test */
    public function server_backup_manager_can_delete_schedule(): void
    {
        $schedule = ServerBackupSchedule::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteSchedule', $schedule->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('server_backup_schedules', ['id' => $schedule->id]);
    }

    // ==========================================
    // ServerTagManager Component Tests
    // ==========================================

    /** @test */
    public function server_tag_manager_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertStatus(200);
    }

    /** @test */
    public function server_tag_manager_loads_tags(): void
    {
        ServerTag::factory()->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertPropertyWired('tags');
    }

    /** @test */
    public function server_tag_manager_can_create_tag(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Production')
            ->set('newTagColor', '#FF0000')
            ->call('createTag')
            ->assertDispatched('tag-updated');

        $this->assertDatabaseHas('server_tags', [
            'name' => 'Production',
            'color' => '#FF0000',
        ]);
    }

    /** @test */
    public function server_tag_manager_validates_tag_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', '')
            ->call('createTag')
            ->assertHasErrors(['newTagName']);
    }

    /** @test */
    public function server_tag_manager_validates_unique_tag_name(): void
    {
        ServerTag::factory()->create(['name' => 'Production']);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Production')
            ->set('newTagColor', '#FF0000')
            ->call('createTag')
            ->assertHasErrors(['newTagName']);
    }

    /** @test */
    public function server_tag_manager_can_edit_tag(): void
    {
        $tag = ServerTag::factory()->create();

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('editTag', $tag->id)
            ->assertSet('editingTag', $tag->id)
            ->assertSet('showEditModal', true);
    }

    /** @test */
    public function server_tag_manager_can_update_tag(): void
    {
        $tag = ServerTag::factory()->create(['name' => 'Old Name']);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('editTag', $tag->id)
            ->set('editTagName', 'New Name')
            ->call('updateTag')
            ->assertDispatched('tag-updated');

        $tag->refresh();
        $this->assertEquals('New Name', $tag->name);
    }

    /** @test */
    public function server_tag_manager_can_delete_tag(): void
    {
        $tag = ServerTag::factory()->create();

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('deleteTag', $tag->id)
            ->assertDispatched('tag-updated');

        $this->assertDatabaseMissing('server_tags', ['id' => $tag->id]);
    }

    /** @test */
    public function server_tag_manager_can_close_edit_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('showEditModal', true)
            ->call('closeEditModal')
            ->assertSet('showEditModal', false);
    }

    // ==========================================
    // ServerTagAssignment Component Tests
    // ==========================================

    /** @test */
    public function server_tag_assignment_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function server_tag_assignment_loads_available_tags(): void
    {
        ServerTag::factory()->count(3)->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertPropertyWired('availableTags');
    }

    /** @test */
    public function server_tag_assignment_can_toggle_tag(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->call('toggleTag', $tag->id)
            ->assertSet('selectedTags', [$tag->id]);
    }

    /** @test */
    public function server_tag_assignment_can_save_tags(): void
    {
        $tag1 = ServerTag::factory()->create(['user_id' => $this->user->id]);
        $tag2 = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->set('selectedTags', [$tag1->id, $tag2->id])
            ->call('saveTags')
            ->assertDispatched('tags-assigned');

        $this->assertEquals(2, $this->server->tags()->count());
    }

    // ==========================================
    // SSHTerminal Component Tests
    // ==========================================

    /** @test */
    public function ssh_terminal_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function ssh_terminal_can_execute_command(): void
    {
        Process::fake([
            '*' => Process::result(output: 'Command output', exitCode: 0),
        ]);

        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'ls -la')
            ->call('executeCommand')
            ->assertSet('command', '')
            ->assertSet('isExecuting', false);
    }

    /** @test */
    public function ssh_terminal_stores_command_history(): void
    {
        Process::fake();

        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'whoami')
            ->call('executeCommand');

        $history = $component->get('history');
        $this->assertNotEmpty($history);
        $this->assertEquals('whoami', $history[0]['command']);
    }

    /** @test */
    public function ssh_terminal_can_clear_history(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('history', [['command' => 'test']])
            ->call('clearHistory')
            ->assertSet('history', []);
    }

    /** @test */
    public function ssh_terminal_can_rerun_command(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('history', [
                ['command' => 'ls -la', 'output' => 'files', 'exit_code' => 0, 'success' => true],
            ])
            ->call('rerunCommand', 0)
            ->assertSet('command', 'ls -la');
    }

    // ==========================================
    // SSLManager Component Tests
    // ==========================================

    /** @test */
    public function ssl_manager_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSLManager::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function ssl_manager_can_open_issue_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSLManager::class, ['server' => $this->server])
            ->call('openIssueModal')
            ->assertSet('showIssueModal', true);
    }

    /** @test */
    public function ssl_manager_can_close_issue_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSLManager::class, ['server' => $this->server])
            ->set('showIssueModal', true)
            ->call('closeIssueModal')
            ->assertSet('showIssueModal', false);
    }

    /** @test */
    public function ssl_manager_validates_domain(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSLManager::class, ['server' => $this->server])
            ->set('newDomain', 'invalid domain')
            ->call('issueCertificate')
            ->assertHasErrors(['newDomain']);
    }

    /** @test */
    public function ssl_manager_validates_email(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSLManager::class, ['server' => $this->server])
            ->set('newDomain', 'example.com')
            ->set('newEmail', 'invalid-email')
            ->call('issueCertificate')
            ->assertHasErrors(['newEmail']);
    }

    /** @test */
    public function ssl_manager_can_install_certbot(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(SSLManager::class, ['server' => $this->server])
            ->call('installCertbot')
            ->assertSet('installingCertbot', false);
    }

    /** @test */
    public function ssl_manager_can_delete_certificate(): void
    {
        $certificate = SSLCertificate::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(SSLManager::class, ['server' => $this->server])
            ->call('deleteCertificate', $certificate->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('ssl_certificates', ['id' => $certificate->id]);
    }

    /** @test */
    public function ssl_manager_can_toggle_auto_renew(): void
    {
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $this->server->id,
            'auto_renew' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(SSLManager::class, ['server' => $this->server])
            ->call('toggleAutoRenew', $certificate->id)
            ->assertHasNoErrors();

        $certificate->refresh();
        $this->assertTrue($certificate->auto_renew);
    }

    /** @test */
    public function ssl_manager_can_setup_auto_renewal(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(SSLManager::class, ['server' => $this->server])
            ->call('setupAutoRenewal')
            ->assertHasNoErrors();
    }

    // ==========================================
    // ResourceAlertManager Component Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function resource_alert_manager_can_open_create_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true);
    }

    /** @test */
    public function resource_alert_manager_can_close_create_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('showCreateModal', true)
            ->call('closeCreateModal')
            ->assertSet('showCreateModal', false);
    }

    /** @test */
    public function resource_alert_manager_validates_alert_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'invalid')
            ->call('createAlert')
            ->assertHasErrors(['resource_type']);
    }

    /** @test */
    public function resource_alert_manager_can_create_alert(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'cpu')
            ->set('threshold_type', 'above')
            ->set('threshold_value', 80.0)
            ->set('cooldown_minutes', 15)
            ->call('createAlert')
            ->assertDispatched('alert-created');

        $this->assertDatabaseHas('resource_alerts', [
            'server_id' => $this->server->id,
            'resource_type' => 'cpu',
            'threshold_value' => 80.0,
        ]);
    }

    /** @test */
    public function resource_alert_manager_can_edit_alert(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingAlert.id', $alert->id);
    }

    /** @test */
    public function resource_alert_manager_can_update_alert(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'threshold_value' => 80.0,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->set('threshold_value', 90.0)
            ->call('updateAlert')
            ->assertDispatched('alert-updated');

        $alert->refresh();
        $this->assertEquals(90.0, $alert->threshold_value);
    }

    /** @test */
    public function resource_alert_manager_can_delete_alert(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('deleteAlert', $alert->id)
            ->assertDispatched('alert-deleted');

        $this->assertDatabaseMissing('resource_alerts', ['id' => $alert->id]);
    }

    /** @test */
    public function resource_alert_manager_can_toggle_alert(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('toggleAlert', $alert->id)
            ->assertHasNoErrors();

        $alert->refresh();
        $this->assertFalse($alert->is_active);
    }

    /** @test */
    public function resource_alert_manager_can_refresh_metrics(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('refreshMetrics')
            ->assertHasNoErrors();
    }

    // ==========================================
    // ServerSecurityDashboard Component Tests
    // ==========================================

    /** @test */
    public function server_security_dashboard_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerSecurityDashboard::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function server_security_dashboard_loads_security_status(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerSecurityDashboard::class, ['server' => $this->server])
            ->call('loadSecurityStatus')
            ->assertSet('isLoading', false);
    }

    /** @test */
    public function server_security_dashboard_can_run_security_scan(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerSecurityDashboard::class, ['server' => $this->server])
            ->call('runSecurityScan')
            ->assertSet('isScanning', false);
    }

    /** @test */
    public function server_security_dashboard_can_refresh_status(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(ServerSecurityDashboard::class, ['server' => $this->server])
            ->call('refreshStatus')
            ->assertHasNoErrors();
    }

    // ==========================================
    // FirewallManager Component Tests
    // ==========================================

    /** @test */
    public function firewall_manager_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function firewall_manager_loads_firewall_status(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('loadFirewallStatus')
            ->assertSet('isLoading', false);
    }

    /** @test */
    public function firewall_manager_can_enable_firewall(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('enableFirewall')
            ->assertHasNoErrors();
    }

    /** @test */
    public function firewall_manager_can_disable_firewall(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('disableFirewall')
            ->assertHasNoErrors();
    }

    /** @test */
    public function firewall_manager_can_open_add_rule_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('openAddRuleModal')
            ->assertSet('showAddRuleModal', true);
    }

    /** @test */
    public function firewall_manager_can_close_add_rule_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('showAddRuleModal', true)
            ->call('closeAddRuleModal')
            ->assertSet('showAddRuleModal', false);
    }

    /** @test */
    public function firewall_manager_validates_rule_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '')
            ->call('addRule')
            ->assertHasErrors(['rulePort']);
    }

    /** @test */
    public function firewall_manager_can_add_rule(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '8080')
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'allow')
            ->call('addRule')
            ->assertHasNoErrors();
    }

    /** @test */
    public function firewall_manager_can_delete_rule(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('deleteRule', 1)
            ->assertHasNoErrors();
    }

    /** @test */
    public function firewall_manager_can_install_ufw(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('installUfw')
            ->assertHasNoErrors();
    }

    /** @test */
    public function firewall_manager_shows_confirm_disable_dialog(): void
    {
        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('confirmDisableFirewall')
            ->assertSet('showConfirmDisable', true);
    }

    // ==========================================
    // Fail2banManager Component Tests
    // ==========================================

    /** @test */
    public function fail2ban_manager_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function fail2ban_manager_loads_status(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('loadFail2banStatus')
            ->assertSet('isLoading', false);
    }

    /** @test */
    public function fail2ban_manager_can_select_jail(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('selectJail', 'nginx')
            ->assertSet('selectedJail', 'nginx');
    }

    /** @test */
    public function fail2ban_manager_can_unban_ip(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('unbanIP', '192.168.1.100')
            ->assertHasNoErrors();
    }

    /** @test */
    public function fail2ban_manager_can_start_fail2ban(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('startFail2ban')
            ->assertHasNoErrors();
    }

    /** @test */
    public function fail2ban_manager_can_stop_fail2ban(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('stopFail2ban')
            ->assertHasNoErrors();
    }

    /** @test */
    public function fail2ban_manager_can_install_fail2ban(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('installFail2ban')
            ->assertHasNoErrors();
    }

    // ==========================================
    // SSHSecurityManager Component Tests
    // ==========================================

    /** @test */
    public function ssh_security_manager_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function ssh_security_manager_loads_ssh_config(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('loadSSHConfig')
            ->assertSet('isLoading', false);
    }

    /** @test */
    public function ssh_security_manager_can_toggle_root_login(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('toggleRootLogin')
            ->assertHasNoErrors();
    }

    /** @test */
    public function ssh_security_manager_can_toggle_password_auth(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('togglePasswordAuth')
            ->assertHasNoErrors();
    }

    /** @test */
    public function ssh_security_manager_can_change_port(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->set('port', 2222)
            ->call('changePort')
            ->assertHasNoErrors();
    }

    /** @test */
    public function ssh_security_manager_can_harden_ssh(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('hardenSSH')
            ->assertHasNoErrors();
    }

    /** @test */
    public function ssh_security_manager_can_restart_ssh(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('restartSSH')
            ->assertHasNoErrors();
    }

    // ==========================================
    // SecurityScanDashboard Component Tests
    // ==========================================

    /** @test */
    public function security_scan_dashboard_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function security_scan_dashboard_can_run_scan(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSet('isScanning', false);
    }

    /** @test */
    public function security_scan_dashboard_can_view_scan_details(): void
    {
        $scan = SecurityScan::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('viewScanDetails', $scan->id)
            ->assertSet('showDetails', true)
            ->assertSet('selectedScan.id', $scan->id);
    }

    /** @test */
    public function security_scan_dashboard_can_close_details(): void
    {
        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->set('showDetails', true)
            ->call('closeDetails')
            ->assertSet('showDetails', false);
    }
}
