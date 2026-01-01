<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Server;
use App\Models\ServerCommandHistory;
use App\Models\User;
use App\Services\CommandExecutorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class CommandExecutorServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommandExecutorService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CommandExecutorService();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_local_execution_for_current_server(): void
    {
        Process::fake([
            '*' => Process::result('test output', '', 0),
        ]);

        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'is_current_server' => true,
            'ip_address' => '127.0.0.1',
        ]);

        $history = $this->service->execute(
            $server,
            'echo "test"',
            'test_action'
        );

        $this->assertInstanceOf(ServerCommandHistory::class, $history);
        $this->assertEquals('local', $history->execution_type);
        $this->assertEquals('success', $history->status);
        $this->assertEquals($server->id, $history->server_id);
        $this->assertEquals($this->user->id, $history->user_id);
    }

    public function test_ssh_execution_for_remote_server(): void
    {
        Process::fake([
            '*' => Process::result('remote output', '', 0),
        ]);

        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'is_current_server' => false,
            'ip_address' => '192.168.1.100',
            'ssh_password' => null,
            'ssh_key' => 'test-key-content',
        ]);

        $history = $this->service->execute(
            $server,
            'echo "test"',
            'test_action'
        );

        $this->assertInstanceOf(ServerCommandHistory::class, $history);
        $this->assertEquals('ssh', $history->execution_type);
    }

    public function test_failed_command_is_logged(): void
    {
        Process::fake([
            '*' => Process::result('', 'error occurred', 1),
        ]);

        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'is_current_server' => true,
        ]);

        $history = $this->service->execute(
            $server,
            'failing_command',
            'test_action'
        );

        $this->assertEquals('failed', $history->status);
        $this->assertNotNull($history->error_output);
    }

    public function test_command_history_records_duration(): void
    {
        Process::fake([
            '*' => Process::result('output', '', 0),
        ]);

        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'is_current_server' => true,
        ]);

        $history = $this->service->execute(
            $server,
            'echo "test"',
            'test_action'
        );

        $this->assertNotNull($history->duration_ms);
        $this->assertNotNull($history->started_at);
        $this->assertNotNull($history->completed_at);
    }

    public function test_command_is_sanitized_for_logging(): void
    {
        Process::fake([
            '*' => Process::result('output', '', 0),
        ]);

        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'is_current_server' => true,
        ]);

        $history = $this->service->execute(
            $server,
            'mysql -p"secretpassword" -u root',
            'test_action'
        );

        $this->assertStringNotContainsString('secretpassword', $history->command ?? '');
        $this->assertStringContainsString('***', $history->command ?? '');
    }

    public function test_metadata_is_stored(): void
    {
        Process::fake([
            '*' => Process::result('output', '', 0),
        ]);

        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'is_current_server' => true,
        ]);

        $metadata = ['service' => 'nginx', 'reason' => 'scheduled restart'];

        $history = $this->service->execute(
            $server,
            'systemctl restart nginx',
            'restart_service',
            false,
            60,
            $metadata
        );

        $this->assertEquals($metadata, $history->metadata);
    }

    public function test_get_history_returns_recent_commands(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Create some command history
        ServerCommandHistory::factory()->count(5)->create([
            'server_id' => $server->id,
            'user_id' => $this->user->id,
        ]);

        $history = $this->service->getHistory($server, 10);

        $this->assertCount(5, $history);
    }

    public function test_server_should_execute_locally_for_localhost(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'is_current_server' => false,
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertTrue($server->shouldExecuteLocally());
    }

    public function test_server_should_execute_locally_when_marked_as_current(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'is_current_server' => true,
            'ip_address' => '192.168.1.100',
        ]);

        $this->assertTrue($server->shouldExecuteLocally());
    }

    public function test_server_should_not_execute_locally_for_remote(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'is_current_server' => false,
            'ip_address' => '192.168.1.100',
        ]);

        $this->assertFalse($server->shouldExecuteLocally());
    }
}
