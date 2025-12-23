<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\DockerInstallationLogs;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Feature tests for DockerInstallationLogs Livewire component
 *
 * Tests component rendering, log streaming, status updates, progress tracking,
 * and installation state management for Docker installation live streaming.
 */
#[CoversClass(DockerInstallationLogs::class)]
class DockerInstallationLogsTest extends TestCase
{
    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->online()->create([
            'name' => 'Test Docker Server',
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up cache keys
        Cache::forget("docker_install_{$this->server->id}");
        Cache::forget("docker_install_logs_{$this->server->id}");

        parent::tearDown();
    }

    /**
     * Test: Component renders successfully for authenticated user
     */
    public function test_component_renders_for_authenticated_user(): void
    {
        Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server])
            ->assertOk()
            ->assertViewIs('livewire.servers.docker-installation-logs')
            ->assertSet('server.id', $this->server->id)
            ->assertSet('isVisible', false)
            ->assertSet('status', 'idle');
    }

    /**
     * Test: Component shows when docker-installation-started event is dispatched
     */
    public function test_component_shows_on_docker_installation_started_event(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server]);

        // Initially not visible
        $component->assertSet('isVisible', false);

        // After show is called, isVisible should be true
        $component->call('show');

        $this->assertTrue($component->get('isVisible'));
    }

    /**
     * Test: Component hides when hide method is called
     */
    public function test_component_hides_on_hide_call(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server]);

        // First show it
        $component->call('show');
        $this->assertTrue($component->get('isVisible'));

        // Then hide it
        $component->call('hide');
        $this->assertFalse($component->get('isVisible'));
    }

    /**
     * Test: Component displays installation status from cache
     */
    public function test_component_displays_installation_status_from_cache(): void
    {
        // Set up cache with installation status
        $cacheKey = "docker_install_{$this->server->id}";
        Cache::put($cacheKey, [
            'status' => 'installing',
            'progress' => 45,
            'current_step' => 'Installing Docker packages...',
        ], 3600);

        Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server])
            ->assertSet('status', 'installing')
            ->assertSet('progress', 45)
            ->assertSet('currentStep', 'Installing Docker packages...')
            ->assertSet('isVisible', true);
    }

    /**
     * Test: Component displays logs from cache
     */
    public function test_component_displays_logs_from_cache(): void
    {
        // Set up cache with logs
        $logsKey = "docker_install_logs_{$this->server->id}";
        $logs = [
            '=== Docker Installation Started ===',
            'Server: Test Server (192.168.1.1)',
            'Step 1/6: Updating package index...',
            'Step 2/6: Installing prerequisites...',
        ];
        Cache::put($logsKey, $logs, 3600);

        Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server])
            ->assertSet('logs', $logs);
    }

    /**
     * Test: Poll logs updates status from cache
     */
    public function test_poll_logs_updates_status_from_cache(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        $logsKey = "docker_install_logs_{$this->server->id}";

        $component = Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server]);

        // Initially idle
        $component->assertSet('status', 'idle');

        // Update cache to simulate installation progress
        Cache::put($cacheKey, [
            'status' => 'installing',
            'progress' => 60,
            'current_step' => 'Starting Docker service...',
        ], 3600);
        Cache::put($logsKey, ['Step 5/6: Starting Docker service...'], 3600);

        // Poll should update component state
        $component->call('pollLogs')
            ->assertSet('status', 'installing')
            ->assertSet('progress', 60)
            ->assertSet('currentStep', 'Starting Docker service...');
    }

    /**
     * Test: Component shows completed status
     */
    public function test_component_shows_completed_status(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        Cache::put($cacheKey, [
            'status' => 'completed',
            'progress' => 100,
            'current_step' => 'Installation complete!',
        ], 3600);

        Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server])
            ->assertSet('status', 'completed')
            ->assertSet('progress', 100);
    }

    /**
     * Test: Component shows failed status with error message
     */
    public function test_component_shows_failed_status_with_error(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        Cache::put($cacheKey, [
            'status' => 'failed',
            'progress' => 0,
            'current_step' => 'Installation failed',
            'error' => 'Connection timeout',
        ], 3600);

        Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server])
            ->assertSet('status', 'failed')
            ->assertSet('errorMessage', 'Connection timeout');
    }

    /**
     * Test: Clear and close removes cache and resets state
     */
    public function test_clear_and_close_removes_cache_and_resets_state(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        $logsKey = "docker_install_logs_{$this->server->id}";

        // Set up cache
        Cache::put($cacheKey, [
            'status' => 'completed',
            'progress' => 100,
        ], 3600);
        Cache::put($logsKey, ['Some logs'], 3600);

        $component = Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server]);

        // Show the component
        $component->call('show');
        $this->assertTrue($component->get('isVisible'));

        // Clear and close
        $component->call('clearAndClose');

        // Verify state was reset
        $this->assertFalse($component->get('isVisible'));
        $this->assertEquals('idle', $component->get('status'));
        $this->assertEquals(0, $component->get('progress'));
        $this->assertEquals([], $component->get('logs'));
        $component->assertDispatched('docker-installation-cleared');

        // Verify cache was cleared
        $this->assertNull(Cache::get($cacheKey));
        $this->assertNull(Cache::get($logsKey));
    }

    /**
     * Test: Component does not auto-show when status is idle
     */
    public function test_component_does_not_auto_show_when_idle(): void
    {
        // No cache set - status should be idle
        Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server])
            ->assertSet('isVisible', false)
            ->assertSet('status', 'idle');
    }

    /**
     * Test: Component auto-shows when installation is in progress
     */
    public function test_component_auto_shows_when_installation_in_progress(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        Cache::put($cacheKey, [
            'status' => 'installing',
            'progress' => 25,
            'current_step' => 'Installing prerequisites...',
        ], 3600);

        Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server])
            ->assertSet('isVisible', true)
            ->assertSet('status', 'installing');
    }

    /**
     * Test: Progress is bounded between 0 and 100
     */
    public function test_progress_is_bounded(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";

        // Test with 0 progress
        Cache::put($cacheKey, [
            'status' => 'failed',
            'progress' => 0,
        ], 3600);

        $component = Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server])
            ->assertSet('progress', 0);

        // Update to 100
        Cache::put($cacheKey, [
            'status' => 'completed',
            'progress' => 100,
        ], 3600);

        $component->call('refreshStatus')
            ->assertSet('progress', 100);
    }

    /**
     * Test: Empty logs array when no logs in cache
     */
    public function test_empty_logs_when_no_cache(): void
    {
        Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server])
            ->assertSet('logs', []);
    }

    /**
     * Test: Current step defaults to empty string when not in cache
     */
    public function test_current_step_defaults_to_empty_string(): void
    {
        Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server])
            ->assertSet('currentStep', '');
    }

    /**
     * Test: Error message is null when no error
     */
    public function test_error_message_is_null_when_no_error(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        Cache::put($cacheKey, [
            'status' => 'installing',
            'progress' => 50,
        ], 3600);

        Livewire::actingAs($this->user)
            ->test(DockerInstallationLogs::class, ['server' => $this->server])
            ->assertSet('errorMessage', null);
    }
}
