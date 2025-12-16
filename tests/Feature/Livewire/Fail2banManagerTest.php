<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\Security\Fail2banManager;
use App\Models\Server;
use App\Models\User;
use App\Services\Security\Fail2banService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class Fail2banManagerTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['user_id' => $this->user->id]);
    }

    // ============================================================
    // Component Rendering Tests
    // ============================================================

    public function test_component_renders_successfully(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')->andReturn([
            'installed' => true,
            'enabled' => false,
            'jails' => [],
        ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    public function test_component_displays_view(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')->andReturn([
            'installed' => true,
            'enabled' => false,
            'jails' => [],
        ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertViewIs('livewire.servers.security.fail2ban-manager');
    }

    public function test_component_mounts_with_server(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')->andReturn([
            'installed' => true,
            'enabled' => false,
            'jails' => [],
        ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('server.id', $this->server->id);
    }

    // ============================================================
    // Authorization Tests
    // ============================================================

    public function test_unauthorized_user_cannot_access(): void
    {
        $otherUser = User::factory()->create();

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::actingAs($otherUser)
            ->test(Fail2banManager::class, ['server' => $this->server]);
    }

    // ============================================================
    // Load Status Tests
    // ============================================================

    public function test_loads_fail2ban_status_on_mount(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->once()
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd', 'nginx-http-auth'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->once()
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => ['192.168.1.100', '10.0.0.50']],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('fail2banInstalled', true)
            ->assertSet('fail2banEnabled', true)
            ->assertSet('jails', ['sshd', 'nginx-http-auth']);
    }

    public function test_shows_not_installed_status(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->once()
            ->andReturn([
                'installed' => false,
                'enabled' => false,
                'jails' => [],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('fail2banInstalled', false)
            ->assertSet('fail2banEnabled', false);
    }

    public function test_shows_installed_but_disabled_status(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->once()
            ->andReturn([
                'installed' => true,
                'enabled' => false,
                'jails' => [],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('fail2banInstalled', true)
            ->assertSet('fail2banEnabled', false);
    }

    public function test_handles_load_status_exception(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->once()
            ->andThrow(new \Exception('Connection failed'));
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed to load Fail2ban status: Connection failed');
    }

    // ============================================================
    // Jail Selection Tests
    // ============================================================

    public function test_can_select_jail(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd', 'nginx-http-auth'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['nginx-http-auth' => ['192.168.1.200']],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('selectJail', 'nginx-http-auth')
            ->assertSet('selectedJail', 'nginx-http-auth');
    }

    public function test_default_jail_is_sshd(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => false,
                'jails' => [],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('selectedJail', 'sshd');
    }

    public function test_selects_first_jail_if_sshd_not_available(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['nginx-http-auth', 'apache-auth'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['nginx-http-auth' => []],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('selectedJail', 'nginx-http-auth');
    }

    // ============================================================
    // Load Banned IPs Tests
    // ============================================================

    public function test_loads_banned_ips_for_selected_jail(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => ['192.168.1.100', '10.0.0.50']],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('bannedIPs', ['192.168.1.100', '10.0.0.50']);
    }

    public function test_handles_load_banned_ips_exception(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andThrow(new \Exception('Failed to get banned IPs'));
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('flashType', 'error');
    }

    // ============================================================
    // Unban IP Tests
    // ============================================================

    public function test_can_unban_ip(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => ['192.168.1.100']],
            ]);
        $serviceMock->shouldReceive('unbanIP')
            ->with($this->server, '192.168.1.100', 'sshd')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'IP 192.168.1.100 has been unbanned from sshd',
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('unbanIP', '192.168.1.100')
            ->assertSet('flashType', 'success')
            ->assertSet('flashMessage', 'IP 192.168.1.100 has been unbanned from sshd');
    }

    public function test_unban_ip_handles_failure(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => ['192.168.1.100']],
            ]);
        $serviceMock->shouldReceive('unbanIP')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'IP not found in ban list',
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('unbanIP', '192.168.1.100')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'IP not found in ban list');
    }

    public function test_unban_ip_handles_exception(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => []],
            ]);
        $serviceMock->shouldReceive('unbanIP')
            ->once()
            ->andThrow(new \Exception('SSH connection failed'));
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('unbanIP', '192.168.1.100')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed to unban IP: SSH connection failed');
    }

    // ============================================================
    // Start Fail2ban Tests
    // ============================================================

    public function test_can_start_fail2ban(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => false,
                'jails' => [],
            ]);
        $serviceMock->shouldReceive('startFail2ban')
            ->with($this->server)
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Fail2ban started successfully',
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('startFail2ban')
            ->assertSet('flashType', 'success')
            ->assertSet('flashMessage', 'Fail2ban started successfully');
    }

    public function test_start_fail2ban_handles_failure(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => false,
                'jails' => [],
            ]);
        $serviceMock->shouldReceive('startFail2ban')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Failed to start service',
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('startFail2ban')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed to start service');
    }

    public function test_start_fail2ban_handles_exception(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => false,
                'jails' => [],
            ]);
        $serviceMock->shouldReceive('startFail2ban')
            ->once()
            ->andThrow(new \Exception('Systemctl not found'));
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('startFail2ban')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed to start Fail2ban: Systemctl not found');
    }

    // ============================================================
    // Stop Fail2ban Tests
    // ============================================================

    public function test_can_stop_fail2ban(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => []],
            ]);
        $serviceMock->shouldReceive('stopFail2ban')
            ->with($this->server)
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Fail2ban stopped successfully',
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('stopFail2ban')
            ->assertSet('flashType', 'success')
            ->assertSet('flashMessage', 'Fail2ban stopped successfully');
    }

    public function test_stop_fail2ban_handles_failure(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => []],
            ]);
        $serviceMock->shouldReceive('stopFail2ban')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Service is in use',
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('stopFail2ban')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Service is in use');
    }

    public function test_stop_fail2ban_handles_exception(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => []],
            ]);
        $serviceMock->shouldReceive('stopFail2ban')
            ->once()
            ->andThrow(new \Exception('Permission denied'));
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('stopFail2ban')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed to stop Fail2ban: Permission denied');
    }

    // ============================================================
    // Install Fail2ban Tests
    // ============================================================

    public function test_can_install_fail2ban(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => false,
                'enabled' => false,
                'jails' => [],
            ]);
        $serviceMock->shouldReceive('installFail2ban')
            ->with($this->server)
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Fail2ban installed and started successfully',
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('installFail2ban')
            ->assertSet('flashType', 'success')
            ->assertSet('flashMessage', 'Fail2ban installed and started successfully');
    }

    public function test_install_fail2ban_handles_failure(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => false,
                'enabled' => false,
                'jails' => [],
            ]);
        $serviceMock->shouldReceive('installFail2ban')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Package not found in repository',
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('installFail2ban')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Package not found in repository');
    }

    public function test_install_fail2ban_handles_exception(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => false,
                'enabled' => false,
                'jails' => [],
            ]);
        $serviceMock->shouldReceive('installFail2ban')
            ->once()
            ->andThrow(new \Exception('apt-get locked by another process'));
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('installFail2ban')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed to install Fail2ban: apt-get locked by another process');
    }

    // ============================================================
    // Loading State Tests
    // ============================================================

    public function test_loading_state_is_false_by_default(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => false,
                'jails' => [],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('isLoading', false);
    }

    // ============================================================
    // Flash Message Tests
    // ============================================================

    public function test_flash_message_is_null_by_default(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => false,
                'jails' => [],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('flashMessage', null)
            ->assertSet('flashType', null);
    }

    // ============================================================
    // Refresh Status Tests
    // ============================================================

    public function test_reload_refreshes_status(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->twice()
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->twice()
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => []],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('loadFail2banStatus')
            ->assertStatus(200);
    }

    // ============================================================
    // Multiple Jails Tests
    // ============================================================

    public function test_displays_multiple_jails(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd', 'nginx-http-auth', 'apache-auth', 'postfix'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => []],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('jails', ['sshd', 'nginx-http-auth', 'apache-auth', 'postfix']);
    }

    // ============================================================
    // Edge Cases
    // ============================================================

    public function test_handles_empty_jails_list(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => [],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('jails', [])
            ->assertSet('bannedIPs', []);
    }

    public function test_handles_empty_banned_ips(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => []],
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->assertSet('bannedIPs', []);
    }

    public function test_unban_refreshes_banned_ips_list(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => ['192.168.1.100']],
            ]);
        $serviceMock->shouldReceive('unbanIP')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'IP unbanned',
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('unbanIP', '192.168.1.100')
            ->assertSet('flashType', 'success');
    }

    public function test_start_reloads_status_after_success(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => false,
                'jails' => [],
            ]);
        $serviceMock->shouldReceive('startFail2ban')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Started',
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('startFail2ban')
            ->assertSet('flashType', 'success');
    }

    public function test_stop_reloads_status_after_success(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd'],
            ]);
        $serviceMock->shouldReceive('getBannedIPs')
            ->andReturn([
                'success' => true,
                'banned_ips' => ['sshd' => []],
            ]);
        $serviceMock->shouldReceive('stopFail2ban')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Stopped',
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('stopFail2ban')
            ->assertSet('flashType', 'success');
    }

    public function test_install_reloads_status_after_success(): void
    {
        $serviceMock = Mockery::mock(Fail2banService::class);
        $serviceMock->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => false,
                'enabled' => false,
                'jails' => [],
            ]);
        $serviceMock->shouldReceive('installFail2ban')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Installed',
            ]);
        $this->app->instance(Fail2banService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(Fail2banManager::class, ['server' => $this->server])
            ->call('installFail2ban')
            ->assertSet('flashType', 'success');
    }
}
