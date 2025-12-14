<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\Security\SSHSecurityManager;
use App\Models\Server;
use App\Models\User;
use App\Services\Security\SSHSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class SSHSecurityManagerTest extends TestCase
{
    use RefreshDatabase;

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
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    public function test_component_displays_view(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertViewIs('livewire.servers.security.s-s-h-security-manager');
    }

    public function test_component_mounts_with_server(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
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
            ->test(SSHSecurityManager::class, ['server' => $this->server]);
    }

    // ============================================================
    // Load SSH Config Tests
    // ============================================================

    public function test_loads_ssh_config_on_mount(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')
            ->once()
            ->andReturn([
                'success' => true,
                'config' => [
                    'port' => 2222,
                    'root_login_enabled' => false,
                    'password_auth_enabled' => false,
                    'pubkey_auth_enabled' => true,
                    'max_auth_tries' => 3,
                ],
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertSet('port', 2222)
            ->assertSet('rootLoginEnabled', false)
            ->assertSet('passwordAuthEnabled', false)
            ->assertSet('pubkeyAuthEnabled', true)
            ->assertSet('maxAuthTries', 3);
    }

    public function test_handles_load_config_failure(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Unable to connect',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed to load SSH configuration');
    }

    public function test_handles_load_config_exception(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')
            ->once()
            ->andThrow(new \Exception('Connection timeout'));
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed to load SSH config: Connection timeout');
    }

    // ============================================================
    // Toggle Root Login Tests
    // ============================================================

    public function test_can_disable_root_login(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('toggleRootLogin')
            ->with($this->server, false)
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Root login disabled. Restart SSH to apply.',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('toggleRootLogin')
            ->assertSet('rootLoginEnabled', false)
            ->assertSet('flashType', 'success');
    }

    public function test_can_enable_root_login(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => false,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('toggleRootLogin')
            ->with($this->server, true)
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Root login enabled. Restart SSH to apply.',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('toggleRootLogin')
            ->assertSet('rootLoginEnabled', true)
            ->assertSet('flashType', 'success');
    }

    public function test_toggle_root_login_handles_failure(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('toggleRootLogin')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Permission denied',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('toggleRootLogin')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Permission denied');
    }

    public function test_toggle_root_login_handles_exception(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('toggleRootLogin')
            ->once()
            ->andThrow(new \Exception('SSH connection failed'));
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('toggleRootLogin')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed: SSH connection failed');
    }

    // ============================================================
    // Toggle Password Auth Tests
    // ============================================================

    public function test_can_disable_password_auth(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('togglePasswordAuth')
            ->with($this->server, false)
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Password authentication disabled. Restart SSH to apply.',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('togglePasswordAuth')
            ->assertSet('passwordAuthEnabled', false)
            ->assertSet('flashType', 'success');
    }

    public function test_can_enable_password_auth(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => false,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('togglePasswordAuth')
            ->with($this->server, true)
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Password authentication enabled. Restart SSH to apply.',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('togglePasswordAuth')
            ->assertSet('passwordAuthEnabled', true)
            ->assertSet('flashType', 'success');
    }

    public function test_toggle_password_auth_handles_failure(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('togglePasswordAuth')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Failed to update configuration',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('togglePasswordAuth')
            ->assertSet('flashType', 'error');
    }

    public function test_toggle_password_auth_handles_exception(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('togglePasswordAuth')
            ->once()
            ->andThrow(new \Exception('Config file not writable'));
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('togglePasswordAuth')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed: Config file not writable');
    }

    // ============================================================
    // Change Port Tests
    // ============================================================

    public function test_can_change_port(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('changePort')
            ->with($this->server, 2222)
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'SSH port changed to 2222. Remember to update firewall rules and restart SSH.',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->set('port', 2222)
            ->call('changePort')
            ->assertSet('flashType', 'success');
    }

    public function test_change_port_handles_failure(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('changePort')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Port must be between 1 and 65535',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->set('port', 99999)
            ->call('changePort')
            ->assertSet('flashType', 'error');
    }

    public function test_change_port_handles_exception(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('changePort')
            ->once()
            ->andThrow(new \Exception('Failed to update port'));
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->set('port', 2222)
            ->call('changePort')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed: Failed to update port');
    }

    // ============================================================
    // Harden SSH Tests
    // ============================================================

    public function test_can_harden_ssh(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('hardenSSH')
            ->with($this->server)
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'SSH hardening applied. Restart SSH service to apply changes.',
                'warning' => 'Make sure you have SSH key access before restarting SSH!',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->set('showHardenConfirm', true)
            ->call('hardenSSH')
            ->assertSet('showHardenConfirm', false)
            ->assertSet('flashType', 'success');
    }

    public function test_harden_ssh_handles_failure(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('hardenSSH')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Unable to modify configuration',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('hardenSSH')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Unable to modify configuration');
    }

    public function test_harden_ssh_handles_exception(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('hardenSSH')
            ->once()
            ->andThrow(new \Exception('Hardening failed'));
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('hardenSSH')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed: Hardening failed');
    }

    public function test_harden_reloads_config_after_success(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')
            ->twice()
            ->andReturn([
                'success' => true,
                'config' => [
                    'port' => 22,
                    'root_login_enabled' => false,
                    'password_auth_enabled' => false,
                    'pubkey_auth_enabled' => true,
                    'max_auth_tries' => 3,
                ],
            ]);
        $serviceMock->shouldReceive('hardenSSH')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Hardened',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('hardenSSH')
            ->assertSet('flashType', 'success');
    }

    // ============================================================
    // Restart SSH Tests
    // ============================================================

    public function test_can_restart_ssh(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('restartSSHService')
            ->with($this->server)
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'SSH service restarted successfully',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('restartSSH')
            ->assertSet('flashType', 'success')
            ->assertSet('flashMessage', 'SSH service restarted successfully');
    }

    public function test_restart_ssh_handles_failure(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('restartSSHService')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Configuration validation failed',
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('restartSSH')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Configuration validation failed');
    }

    public function test_restart_ssh_handles_exception(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $serviceMock->shouldReceive('restartSSHService')
            ->once()
            ->andThrow(new \Exception('Systemctl not found'));
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('restartSSH')
            ->assertSet('flashType', 'error')
            ->assertSet('flashMessage', 'Failed: Systemctl not found');
    }

    // ============================================================
    // Loading State Tests
    // ============================================================

    public function test_loading_state_is_false_after_mount(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertSet('isLoading', false);
    }

    // ============================================================
    // Default Values Tests
    // ============================================================

    public function test_default_values(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => false,
        ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertSet('port', 22)
            ->assertSet('rootLoginEnabled', true)
            ->assertSet('passwordAuthEnabled', true)
            ->assertSet('pubkeyAuthEnabled', true)
            ->assertSet('maxAuthTries', 6);
    }

    // ============================================================
    // Modal State Tests
    // ============================================================

    public function test_config_modal_is_closed_by_default(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertSet('showConfigModal', false);
    }

    public function test_harden_confirm_is_closed_by_default(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertSet('showHardenConfirm', false);
    }

    // ============================================================
    // Flash Message Tests
    // ============================================================

    public function test_flash_message_is_null_by_default(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
            ],
        ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertSet('flashMessage', null)
            ->assertSet('flashType', null);
    }

    // ============================================================
    // Edge Cases
    // ============================================================

    public function test_handles_custom_port(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 49152,
                'root_login_enabled' => false,
                'password_auth_enabled' => false,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 3,
            ],
        ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertSet('port', 49152);
    }

    public function test_handles_all_auth_disabled(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')->andReturn([
            'success' => true,
            'config' => [
                'port' => 22,
                'root_login_enabled' => false,
                'password_auth_enabled' => false,
                'pubkey_auth_enabled' => false,
                'max_auth_tries' => 1,
            ],
        ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->assertSet('rootLoginEnabled', false)
            ->assertSet('passwordAuthEnabled', false)
            ->assertSet('pubkeyAuthEnabled', false);
    }

    public function test_reload_ssh_config(): void
    {
        $serviceMock = Mockery::mock(SSHSecurityService::class);
        $serviceMock->shouldReceive('getCurrentConfig')
            ->twice()
            ->andReturn([
                'success' => true,
                'config' => [
                    'port' => 22,
                    'root_login_enabled' => true,
                    'password_auth_enabled' => true,
                    'pubkey_auth_enabled' => true,
                    'max_auth_tries' => 6,
                ],
            ]);
        $this->app->instance(SSHSecurityService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHSecurityManager::class, ['server' => $this->server])
            ->call('loadSSHConfig')
            ->assertSet('isLoading', false);
    }
}
