<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\ServerProvisioning;
use App\Models\ProvisioningLog;
use App\Models\Server;
use App\Models\User;
use App\Services\ServerProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class ServerProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
            'provision_status' => 'pending',
        ]);
    }

    // ==================== COMPONENT RENDERING ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->assertStatus(200)
            ->assertViewIs('livewire.servers.server-provisioning');
    }

    public function test_component_initializes_with_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->assertSet('installNginx', true)
            ->assertSet('installMySQL', false)
            ->assertSet('installPHP', true)
            ->assertSet('installComposer', true)
            ->assertSet('installNodeJS', true)
            ->assertSet('configureFirewall', true)
            ->assertSet('setupSwap', true)
            ->assertSet('secureSSH', true)
            ->assertSet('phpVersion', '8.4')
            ->assertSet('nodeVersion', '20')
            ->assertSet('swapSizeGB', 2)
            ->assertSet('showProvisioningModal', false)
            ->assertSet('isProvisioning', false);
    }

    public function test_component_generates_mysql_password_on_mount(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $mysqlPassword = $component->get('mysqlPassword');
        $this->assertNotEmpty($mysqlPassword);
        $this->assertEquals(32, strlen($mysqlPassword)); // 16 bytes = 32 hex chars
    }

    public function test_component_initializes_firewall_ports(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->assertSet('firewallPorts', [22, 80, 443]);
    }

    // ==================== MODAL MANAGEMENT ====================

    public function test_open_provisioning_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->call('openProvisioningModal')
            ->assertSet('showProvisioningModal', true);
    }

    public function test_close_provisioning_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->call('openProvisioningModal')
            ->assertSet('showProvisioningModal', true)
            ->call('closeProvisioningModal')
            ->assertSet('showProvisioningModal', false);
    }

    // ==================== START PROVISIONING - VALIDATION ====================

    public function test_start_provisioning_validates_php_version(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('phpVersion', 'invalid')
            ->call('startProvisioning')
            ->assertHasErrors(['phpVersion']);
    }

    public function test_start_provisioning_validates_node_version(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('nodeVersion', 'invalid')
            ->call('startProvisioning')
            ->assertHasErrors(['nodeVersion']);
    }

    public function test_start_provisioning_validates_mysql_password_when_mysql_enabled(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('installMySQL', true)
            ->set('mysqlPassword', 'short')
            ->call('startProvisioning')
            ->assertHasErrors(['mysqlPassword']);
    }

    public function test_start_provisioning_mysql_password_not_required_when_disabled(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('installMySQL', false)
            ->set('mysqlPassword', '')
            ->call('startProvisioning')
            ->assertHasNoErrors(['mysqlPassword']);
    }

    public function test_start_provisioning_validates_swap_size_min(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('swapSizeGB', 0)
            ->call('startProvisioning')
            ->assertHasErrors(['swapSizeGB']);
    }

    public function test_start_provisioning_validates_swap_size_max(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('swapSizeGB', 64)
            ->call('startProvisioning')
            ->assertHasErrors(['swapSizeGB']);
    }

    public function test_start_provisioning_validates_firewall_ports_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('firewallPorts', [])
            ->call('startProvisioning')
            ->assertHasErrors(['firewallPorts']);
    }

    // ==================== START PROVISIONING - SUCCESS ====================

    public function test_start_provisioning_success(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('showProvisioningModal', true)
            ->set('phpVersion', '8.4')
            ->set('nodeVersion', '20')
            ->set('swapSizeGB', 2)
            ->set('firewallPorts', [22, 80, 443])
            ->call('startProvisioning')
            ->assertSet('showProvisioningModal', false)
            ->assertDispatched('provisioning-started')
            ->assertDispatched('notification');
    }

    public function test_start_provisioning_with_all_packages(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('installNginx', true)
            ->set('installMySQL', true)
            ->set('installPHP', true)
            ->set('installComposer', true)
            ->set('installNodeJS', true)
            ->set('configureFirewall', true)
            ->set('setupSwap', true)
            ->set('secureSSH', true)
            ->set('mysqlPassword', 'securepassword123')
            ->call('startProvisioning')
            ->assertHasNoErrors()
            ->assertDispatched('provisioning-started');
    }

    public function test_start_provisioning_with_minimal_packages(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('installNginx', false)
            ->set('installMySQL', false)
            ->set('installPHP', false)
            ->set('installComposer', false)
            ->set('installNodeJS', false)
            ->set('configureFirewall', false)
            ->set('setupSwap', false)
            ->set('secureSSH', false)
            ->call('startProvisioning')
            ->assertHasNoErrors()
            ->assertDispatched('provisioning-started');
    }

    public function test_start_provisioning_dispatches_refresh_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->call('startProvisioning')
            ->assertDispatched('refresh-server-status');
    }

    // ==================== PHP VERSION OPTIONS ====================

    public function test_php_version_81_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('phpVersion', '8.1')
            ->call('startProvisioning')
            ->assertHasNoErrors(['phpVersion']);
    }

    public function test_php_version_82_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('phpVersion', '8.2')
            ->call('startProvisioning')
            ->assertHasNoErrors(['phpVersion']);
    }

    public function test_php_version_83_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('phpVersion', '8.3')
            ->call('startProvisioning')
            ->assertHasNoErrors(['phpVersion']);
    }

    public function test_php_version_84_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('phpVersion', '8.4')
            ->call('startProvisioning')
            ->assertHasNoErrors(['phpVersion']);
    }

    // ==================== NODE VERSION OPTIONS ====================

    public function test_node_version_18_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('nodeVersion', '18')
            ->call('startProvisioning')
            ->assertHasNoErrors(['nodeVersion']);
    }

    public function test_node_version_20_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('nodeVersion', '20')
            ->call('startProvisioning')
            ->assertHasNoErrors(['nodeVersion']);
    }

    public function test_node_version_22_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('nodeVersion', '22')
            ->call('startProvisioning')
            ->assertHasNoErrors(['nodeVersion']);
    }

    // ==================== DOWNLOAD PROVISIONING SCRIPT ====================

    public function test_download_provisioning_script_success(): void
    {
        $this->mock(ServerProvisioningService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getProvisioningScript')
                ->once()
                ->andReturn('#!/bin/bash\necho "Provisioning..."');
        });

        $response = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->call('downloadProvisioningScript');

        $this->assertNotNull($response->effects['returns'][0]);
    }

    public function test_download_provisioning_script_failure(): void
    {
        $this->mock(ServerProvisioningService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getProvisioningScript')
                ->once()
                ->andThrow(new \Exception('Script generation failed'));
        });

        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->call('downloadProvisioningScript')
            ->assertDispatched('notification');
    }

    // ==================== REFRESH SERVER STATUS ====================

    public function test_refresh_server_status(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->call('refreshServerStatus')
            ->assertStatus(200);
    }

    // ==================== PROVISIONING LOGS COMPUTED PROPERTY ====================

    public function test_provisioning_logs_returns_logs(): void
    {
        ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'Update System',
            'status' => 'completed',
        ]);

        ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'Install Nginx',
            'status' => 'running',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $logs = $component->viewData('provisioningLogs');
        $this->assertCount(2, $logs);
    }

    public function test_provisioning_logs_ordered_by_latest(): void
    {
        $oldLog = ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'Old Task',
            'status' => 'completed',
            'created_at' => now()->subHour(),
        ]);

        $newLog = ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'New Task',
            'status' => 'running',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $logs = $component->viewData('provisioningLogs');
        $this->assertEquals($newLog->id, $logs->first()->id);
    }

    public function test_provisioning_logs_limited_to_50(): void
    {
        for ($i = 0; $i < 60; $i++) {
            ProvisioningLog::create([
                'server_id' => $this->server->id,
                'task' => "Task {$i}",
                'status' => 'completed',
            ]);
        }

        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $logs = $component->viewData('provisioningLogs');
        $this->assertCount(50, $logs);
    }

    // ==================== LATEST LOG COMPUTED PROPERTY ====================

    public function test_latest_log_returns_most_recent(): void
    {
        ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'First Task',
            'status' => 'completed',
            'created_at' => now()->subHour(),
        ]);

        $latestLog = ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'Latest Task',
            'status' => 'running',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $latest = $component->viewData('latestLog');
        $this->assertEquals($latestLog->id, $latest->id);
    }

    public function test_latest_log_returns_null_when_no_logs(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $latest = $component->viewData('latestLog');
        $this->assertNull($latest);
    }

    // ==================== PROVISIONING PROGRESS COMPUTED PROPERTY ====================

    public function test_provisioning_progress_when_pending(): void
    {
        $this->server->update(['provision_status' => 'pending']);

        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $progress = $component->viewData('provisioningProgress');
        $this->assertEquals(0, $progress['percentage']);
        $this->assertEquals(0, $progress['current_step']);
        $this->assertEquals(0, $progress['total_steps']);
    }

    public function test_provisioning_progress_when_completed(): void
    {
        $this->server->update(['provision_status' => 'completed']);

        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $progress = $component->viewData('provisioningProgress');
        $this->assertEquals(100, $progress['percentage']);
    }

    public function test_provisioning_progress_during_provisioning(): void
    {
        $this->server->update(['provision_status' => 'provisioning']);

        // Create some logs
        ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'Task 1',
            'status' => 'completed',
            'duration_seconds' => 30,
        ]);

        ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'Task 2',
            'status' => 'completed',
            'duration_seconds' => 30,
        ]);

        ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'Task 3',
            'status' => 'running',
        ]);

        ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'Task 4',
            'status' => 'pending',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $progress = $component->viewData('provisioningProgress');
        $this->assertEquals(4, $progress['total_steps']);
        $this->assertEquals(3, $progress['current_step']);
        $this->assertEquals('Task 3', $progress['current_task']);
    }

    public function test_provisioning_progress_with_failed_steps(): void
    {
        $this->server->update(['provision_status' => 'provisioning']);

        ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'Task 1',
            'status' => 'completed',
        ]);

        ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'Task 2',
            'status' => 'failed',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $progress = $component->viewData('provisioningProgress');
        $this->assertEquals(2, $progress['current_step']);
    }

    // ==================== PACKAGE SELECTION TOGGLES ====================

    public function test_toggle_install_nginx(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('installNginx', false)
            ->assertSet('installNginx', false)
            ->set('installNginx', true)
            ->assertSet('installNginx', true);
    }

    public function test_toggle_install_mysql(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('installMySQL', true)
            ->assertSet('installMySQL', true)
            ->set('installMySQL', false)
            ->assertSet('installMySQL', false);
    }

    public function test_toggle_install_php(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('installPHP', false)
            ->assertSet('installPHP', false);
    }

    public function test_toggle_install_composer(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('installComposer', false)
            ->assertSet('installComposer', false);
    }

    public function test_toggle_install_nodejs(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('installNodeJS', false)
            ->assertSet('installNodeJS', false);
    }

    public function test_toggle_configure_firewall(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('configureFirewall', false)
            ->assertSet('configureFirewall', false);
    }

    public function test_toggle_setup_swap(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('setupSwap', false)
            ->assertSet('setupSwap', false);
    }

    public function test_toggle_secure_ssh(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('secureSSH', false)
            ->assertSet('secureSSH', false);
    }

    // ==================== EDGE CASES ====================

    public function test_swap_size_boundary_values(): void
    {
        // Test minimum valid value
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('swapSizeGB', 1)
            ->call('startProvisioning')
            ->assertHasNoErrors(['swapSizeGB']);

        // Test maximum valid value
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('swapSizeGB', 32)
            ->call('startProvisioning')
            ->assertHasNoErrors(['swapSizeGB']);
    }

    public function test_multiple_firewall_ports(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('firewallPorts', [22, 80, 443, 3306, 6379])
            ->call('startProvisioning')
            ->assertHasNoErrors(['firewallPorts']);
    }

    public function test_single_firewall_port(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('firewallPorts', [22])
            ->call('startProvisioning')
            ->assertHasNoErrors(['firewallPorts']);
    }

    public function test_mysql_password_exactly_8_chars(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('installMySQL', true)
            ->set('mysqlPassword', '12345678')
            ->call('startProvisioning')
            ->assertHasNoErrors(['mysqlPassword']);
    }

    public function test_provisioning_status_isolation(): void
    {
        $otherServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'provision_status' => 'provisioning',
        ]);

        // Create logs for other server
        ProvisioningLog::create([
            'server_id' => $otherServer->id,
            'task' => 'Other Server Task',
            'status' => 'running',
        ]);

        // Our server should have no logs
        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $logs = $component->viewData('provisioningLogs');
        $this->assertCount(0, $logs);
    }

    public function test_handles_empty_provisioning_logs(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $logs = $component->viewData('provisioningLogs');
        $this->assertCount(0, $logs);
    }

    public function test_configuration_combinations(): void
    {
        // Test with PHP and Composer but no Nginx
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('installNginx', false)
            ->set('installPHP', true)
            ->set('installComposer', true)
            ->call('startProvisioning')
            ->assertHasNoErrors();

        // Test with MySQL but no PHP
        Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server])
            ->set('installPHP', false)
            ->set('installMySQL', true)
            ->set('mysqlPassword', 'securepassword')
            ->call('startProvisioning')
            ->assertHasNoErrors();
    }

    public function test_provisioning_progress_percentage_capped_at_100(): void
    {
        $this->server->update(['provision_status' => 'provisioning']);

        // All tasks completed
        ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'Task 1',
            'status' => 'completed',
        ]);

        ProvisioningLog::create([
            'server_id' => $this->server->id,
            'task' => 'Task 2',
            'status' => 'completed',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerProvisioning::class, ['server' => $this->server]);

        $progress = $component->viewData('provisioningProgress');
        $this->assertLessThanOrEqual(100, $progress['percentage']);
    }
}
