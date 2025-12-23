<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\SSHTerminal;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SSHTerminalTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
            'ip_address' => '127.0.0.1',
            'port' => 22,
            'username' => 'testuser',
        ]);
    }

    // ==================== COMPONENT RENDERING ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->assertStatus(200)
            ->assertViewIs('livewire.servers.s-s-h-terminal');
    }

    public function test_component_initializes_with_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->assertSet('command', '')
            ->assertSet('history', [])
            ->assertSet('historyIndex', -1)
            ->assertSet('isExecuting', false);
    }

    public function test_component_loads_history_from_session(): void
    {
        $history = [
            [
                'command' => 'ls -la',
                'output' => 'file1.txt file2.txt',
                'exit_code' => 0,
                'success' => true,
                'timestamp' => now()->toDateTimeString(),
            ],
        ];

        session(['ssh_history_'.$this->server->id => $history]);

        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->assertSet('history', $history);
    }

    public function test_component_displays_quick_commands(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server]);

        $quickCommands = $component->viewData('quickCommands');
        $this->assertIsArray($quickCommands);
        $this->assertArrayHasKey('System Info', $quickCommands);
        $this->assertArrayHasKey('Docker', $quickCommands);
        $this->assertArrayHasKey('Web Services', $quickCommands);
    }

    // ==================== EXECUTE COMMAND ====================

    public function test_execute_command_with_empty_command_does_nothing(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', '')
            ->call('executeCommand')
            ->assertSet('history', []);
    }

    public function test_execute_command_with_whitespace_only_does_nothing(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', '   ')
            ->call('executeCommand')
            ->assertSet('history', []);
    }

    public function test_execute_command_clears_command_input(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'echo test')
            ->call('executeCommand')
            ->assertSet('command', '');
    }

    public function test_execute_command_resets_history_index(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('historyIndex', 5)
            ->set('command', 'echo test')
            ->call('executeCommand')
            ->assertSet('historyIndex', -1);
    }

    public function test_execute_command_adds_to_history(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'echo test')
            ->call('executeCommand');

        $history = $component->get('history');
        $this->assertCount(1, $history);
        $this->assertEquals('echo test', $history[0]['command']);
    }

    public function test_execute_command_history_has_required_fields(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'whoami')
            ->call('executeCommand');

        $history = $component->get('history');
        $this->assertArrayHasKey('command', $history[0]);
        $this->assertArrayHasKey('output', $history[0]);
        $this->assertArrayHasKey('exit_code', $history[0]);
        $this->assertArrayHasKey('success', $history[0]);
        $this->assertArrayHasKey('timestamp', $history[0]);
    }

    public function test_execute_command_sets_is_executing_false_after_completion(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'echo test')
            ->call('executeCommand')
            ->assertSet('isExecuting', false);
    }

    public function test_execute_command_prepends_to_history(): void
    {
        $existingHistory = [
            [
                'command' => 'first command',
                'output' => 'output 1',
                'exit_code' => 0,
                'success' => true,
                'timestamp' => now()->subMinute()->toDateTimeString(),
            ],
        ];

        session(['ssh_history_'.$this->server->id => $existingHistory]);

        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'second command')
            ->call('executeCommand');

        $history = $component->get('history');
        $this->assertEquals('second command', $history[0]['command']);
        $this->assertEquals('first command', $history[1]['command']);
    }

    public function test_execute_command_limits_history_to_50(): void
    {
        $existingHistory = [];
        for ($i = 0; $i < 50; $i++) {
            $existingHistory[] = [
                'command' => "command $i",
                'output' => "output $i",
                'exit_code' => 0,
                'success' => true,
                'timestamp' => now()->toDateTimeString(),
            ];
        }

        session(['ssh_history_'.$this->server->id => $existingHistory]);

        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'new command')
            ->call('executeCommand');

        $history = $component->get('history');
        $this->assertCount(50, $history);
        $this->assertEquals('new command', $history[0]['command']);
    }

    // ==================== CLEAR HISTORY ====================

    public function test_clear_history_empties_history_array(): void
    {
        $history = [
            [
                'command' => 'ls',
                'output' => 'files',
                'exit_code' => 0,
                'success' => true,
                'timestamp' => now()->toDateTimeString(),
            ],
        ];

        session(['ssh_history_'.$this->server->id => $history]);

        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->call('clearHistory')
            ->assertSet('history', []);
    }

    public function test_clear_history_removes_session_data(): void
    {
        $history = [
            [
                'command' => 'pwd',
                'output' => '/home/user',
                'exit_code' => 0,
                'success' => true,
                'timestamp' => now()->toDateTimeString(),
            ],
        ];

        session(['ssh_history_'.$this->server->id => $history]);

        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->call('clearHistory');

        $this->assertNull(session('ssh_history_'.$this->server->id));
    }

    public function test_clear_history_on_empty_history(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->call('clearHistory')
            ->assertSet('history', []);
    }

    // ==================== RERUN COMMAND ====================

    public function test_rerun_command_sets_command_from_history(): void
    {
        $history = [
            [
                'command' => 'ls -la',
                'output' => 'files',
                'exit_code' => 0,
                'success' => true,
                'timestamp' => now()->toDateTimeString(),
            ],
            [
                'command' => 'pwd',
                'output' => '/home',
                'exit_code' => 0,
                'success' => true,
                'timestamp' => now()->toDateTimeString(),
            ],
        ];

        session(['ssh_history_'.$this->server->id => $history]);

        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->call('rerunCommand', 0)
            ->assertSet('command', 'ls -la');
    }

    public function test_rerun_command_with_second_index(): void
    {
        $history = [
            [
                'command' => 'first',
                'output' => 'output1',
                'exit_code' => 0,
                'success' => true,
                'timestamp' => now()->toDateTimeString(),
            ],
            [
                'command' => 'second',
                'output' => 'output2',
                'exit_code' => 0,
                'success' => true,
                'timestamp' => now()->toDateTimeString(),
            ],
        ];

        session(['ssh_history_'.$this->server->id => $history]);

        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->call('rerunCommand', 1)
            ->assertSet('command', 'second');
    }

    public function test_rerun_command_with_invalid_index_does_nothing(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'existing')
            ->call('rerunCommand', 99)
            ->assertSet('command', 'existing');
    }

    public function test_rerun_command_with_negative_index_does_nothing(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'existing')
            ->call('rerunCommand', -1)
            ->assertSet('command', 'existing');
    }

    // ==================== QUICK COMMANDS ====================

    public function test_quick_commands_has_system_info_category(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server]);

        $quickCommands = $component->viewData('quickCommands');
        $this->assertArrayHasKey('System Info', $quickCommands);
        $this->assertArrayHasKey('uname -a', $quickCommands['System Info']);
        $this->assertArrayHasKey('df -h', $quickCommands['System Info']);
        $this->assertArrayHasKey('free -h', $quickCommands['System Info']);
    }

    public function test_quick_commands_has_explore_system_category(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server]);

        $quickCommands = $component->viewData('quickCommands');
        $this->assertArrayHasKey('Explore System', $quickCommands);
        $this->assertArrayHasKey('ls -la ~', $quickCommands['Explore System']);
        $this->assertArrayHasKey('pwd', $quickCommands['Explore System']);
    }

    public function test_quick_commands_has_process_services_category(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server]);

        $quickCommands = $component->viewData('quickCommands');
        $this->assertArrayHasKey('Process & Services', $quickCommands);
    }

    public function test_quick_commands_has_docker_category(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server]);

        $quickCommands = $component->viewData('quickCommands');
        $this->assertArrayHasKey('Docker', $quickCommands);
        $this->assertArrayHasKey('docker ps', $quickCommands['Docker']);
        $this->assertArrayHasKey('docker images', $quickCommands['Docker']);
    }

    public function test_quick_commands_has_web_services_category(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server]);

        $quickCommands = $component->viewData('quickCommands');
        $this->assertArrayHasKey('Web Services', $quickCommands);
    }

    public function test_quick_commands_has_logs_category(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server]);

        $quickCommands = $component->viewData('quickCommands');
        $this->assertArrayHasKey('Logs', $quickCommands);
    }

    // ==================== COMMAND INPUT ====================

    public function test_command_input_binding(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'test command')
            ->assertSet('command', 'test command');
    }

    public function test_command_input_with_special_characters(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'echo "hello world" | grep world')
            ->assertSet('command', 'echo "hello world" | grep world');
    }

    public function test_command_input_with_quotes(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', "echo 'single quotes'")
            ->assertSet('command', "echo 'single quotes'");
    }

    // ==================== HISTORY INDEX ====================

    public function test_history_index_binding(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('historyIndex', 5)
            ->assertSet('historyIndex', 5);
    }

    public function test_history_index_starts_at_negative_one(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->assertSet('historyIndex', -1);
    }

    // ==================== SERVER ISOLATION ====================

    public function test_history_is_isolated_per_server(): void
    {
        $otherServer = Server::factory()->create(['user_id' => $this->user->id]);

        $history1 = [
            [
                'command' => 'server1 command',
                'output' => 'output1',
                'exit_code' => 0,
                'success' => true,
                'timestamp' => now()->toDateTimeString(),
            ],
        ];

        $history2 = [
            [
                'command' => 'server2 command',
                'output' => 'output2',
                'exit_code' => 0,
                'success' => true,
                'timestamp' => now()->toDateTimeString(),
            ],
        ];

        session(['ssh_history_'.$this->server->id => $history1]);
        session(['ssh_history_'.$otherServer->id => $history2]);

        $component1 = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server]);

        $component2 = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $otherServer]);

        $this->assertEquals('server1 command', $component1->get('history')[0]['command']);
        $this->assertEquals('server2 command', $component2->get('history')[0]['command']);
    }

    public function test_clear_history_only_affects_current_server(): void
    {
        $otherServer = Server::factory()->create(['user_id' => $this->user->id]);

        $history = [
            [
                'command' => 'test',
                'output' => 'output',
                'exit_code' => 0,
                'success' => true,
                'timestamp' => now()->toDateTimeString(),
            ],
        ];

        session(['ssh_history_'.$this->server->id => $history]);
        session(['ssh_history_'.$otherServer->id => $history]);

        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->call('clearHistory');

        $this->assertNull(session('ssh_history_'.$this->server->id));
        $this->assertNotNull(session('ssh_history_'.$otherServer->id));
    }

    // ==================== EDGE CASES ====================

    public function test_handles_empty_history_array(): void
    {
        session(['ssh_history_'.$this->server->id => []]);

        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->assertSet('history', []);
    }

    public function test_handles_no_session_history(): void
    {
        session()->forget('ssh_history_'.$this->server->id);

        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->assertSet('history', []);
    }

    public function test_command_with_newlines(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', "echo line1\necho line2")
            ->assertSet('command', "echo line1\necho line2");
    }

    public function test_command_with_tabs(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', "echo\ttab")
            ->assertSet('command', "echo\ttab");
    }

    public function test_multiple_commands_execution(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'first')
            ->call('executeCommand')
            ->set('command', 'second')
            ->call('executeCommand')
            ->set('command', 'third')
            ->call('executeCommand');

        $history = $component->get('history');
        $this->assertCount(3, $history);
        $this->assertEquals('third', $history[0]['command']);
        $this->assertEquals('second', $history[1]['command']);
        $this->assertEquals('first', $history[2]['command']);
    }

    public function test_execute_then_rerun(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->set('command', 'original')
            ->call('executeCommand')
            ->call('rerunCommand', 0);

        $this->assertEquals('original', $component->get('command'));
    }

    public function test_quick_commands_count(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server]);

        $quickCommands = $component->viewData('quickCommands');
        $this->assertCount(6, $quickCommands);
    }

    public function test_server_property_accessible(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminal::class, ['server' => $this->server]);

        $this->assertEquals($this->server->id, $component->get('server.id'));
    }
}
