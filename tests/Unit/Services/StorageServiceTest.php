<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\Server;
use App\Services\StorageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class StorageServiceTest extends TestCase
{
    protected StorageService $storageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageService = new StorageService;
    }

    /** @test */
    public function it_calculates_project_storage_successfully(): void
    {
        $server = Server::factory()->create([
            'ip_address' => '192.168.1.100',
            'port' => 22,
            'username' => 'root',
        ]);
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result('1024'),
        ]);

        $result = $this->storageService->calculateProjectStorage($project);

        $this->assertEquals(1024, $result);
        $project->refresh();
        $this->assertEquals(1024, $project->storage_used_mb);

        // Assert that SSH command was executed
        Process::assertRan(function ($process) {
            return str_contains($process->command, 'ssh') &&
                   str_contains($process->command, 'du -sm');
        });
    }

    /** @test */
    public function it_returns_zero_when_project_has_no_server(): void
    {
        $project = Project::factory()->create(['server_id' => null]);

        $result = $this->storageService->calculateProjectStorage($project);

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_uses_correct_project_path_in_du_command(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'my-awesome-project',
        ]);

        Process::fake([
            '*' => Process::result('512'),
        ]);

        $result = $this->storageService->calculateProjectStorage($project);

        $this->assertEquals(512, $result);
        Process::assertRan(function ($process) {
            return str_contains($process->command, 'du -sm /var/www/my-awesome-project');
        });
    }

    /** @test */
    public function it_handles_failed_ssh_command_gracefully(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result('', 1), // Failed process
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to calculate project storage', \Mockery::type('array'));

        $result = $this->storageService->calculateProjectStorage($project);

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_handles_exception_during_storage_calculation(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::shouldReceive('fromShellCommandline')
            ->andThrow(new \RuntimeException('SSH connection failed'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to calculate project storage', \Mockery::on(function ($arg) {
                return isset($arg['project_id']) && isset($arg['error']);
            }));

        $result = $this->storageService->calculateProjectStorage($project);

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_updates_project_storage_used_mb_field(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
            'storage_used_mb' => 0,
        ]);

        Process::fake([
            '*du -sm*' => Process::result('2048'),
        ]);

        $this->storageService->calculateProjectStorage($project);

        $project->refresh();
        $this->assertEquals(2048, $project->storage_used_mb);
    }

    /** @test */
    public function it_trims_whitespace_from_du_output(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result("  3072  \n"),
        ]);

        $result = $this->storageService->calculateProjectStorage($project);

        $this->assertEquals(3072, $result);
    }

    /** @test */
    public function it_gets_total_storage_used_correctly(): void
    {
        Project::factory()->create(['storage_used_mb' => 1024]);
        Project::factory()->create(['storage_used_mb' => 2048]);
        Project::factory()->create(['storage_used_mb' => 512]);

        config(['app.max_storage_gb' => 100]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertEquals(3584, $result['used_mb']);
        $this->assertEquals(3.5, $result['used_gb']);
        $this->assertEquals(100, $result['max_gb']);
        $this->assertEquals(3.5, $result['percentage']);
    }

    /** @test */
    public function it_calculates_storage_percentage_correctly(): void
    {
        Project::factory()->create(['storage_used_mb' => 51200]); // 50 GB

        config(['app.max_storage_gb' => 100]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertEquals(51200, $result['used_mb']);
        $this->assertEquals(50.0, $result['used_gb']);
        $this->assertEquals(50.0, $result['percentage']);
    }

    /** @test */
    public function it_handles_zero_max_storage(): void
    {
        Project::factory()->create(['storage_used_mb' => 1024]);

        config(['app.max_storage_gb' => 0]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertEquals(0, $result['percentage']);
    }

    /** @test */
    public function it_handles_zero_projects(): void
    {
        config(['app.max_storage_gb' => 100]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertEquals(0, $result['used_mb']);
        $this->assertEquals(0, $result['used_gb']);
        $this->assertEquals(100, $result['max_gb']);
        $this->assertEquals(0, $result['percentage']);
    }

    /** @test */
    public function it_rounds_storage_values_correctly(): void
    {
        Project::factory()->create(['storage_used_mb' => 1536]); // 1.5 GB

        config(['app.max_storage_gb' => 100]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertEquals(1.5, $result['used_gb']);
        $this->assertEquals(1.5, $result['percentage']);
    }

    /** @test */
    public function it_uses_default_max_storage_when_not_configured(): void
    {
        Project::factory()->create(['storage_used_mb' => 10240]); // 10 GB

        config(['app.max_storage_gb' => null]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertEquals(100, $result['max_gb']); // Default value
    }

    /** @test */
    public function it_cleans_up_project_storage_successfully(): void
    {
        $server = Server::factory()->create([
            'ip_address' => '192.168.1.100',
            'port' => 22,
            'username' => 'root',
        ]);
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*rm -rf*' => Process::result(''),
            '*du -sm*' => Process::result('256'),
        ]);

        $result = $this->storageService->cleanupProjectStorage($project);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_executes_all_cleanup_commands(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result(''),
        ]);

        $this->storageService->cleanupProjectStorage($project);

        Process::assertRan(function ($process) {
            return str_contains($process->command, 'rm -rf /var/www/test-project/storage/logs/*');
        });

        Process::assertRan(function ($process) {
            return str_contains($process->command, 'rm -rf /var/www/test-project/storage/framework/cache/*');
        });

        Process::assertRan(function ($process) {
            return str_contains($process->command, 'rm -rf /var/www/test-project/storage/framework/sessions/*');
        });

        Process::assertRan(function ($process) {
            return str_contains($process->command, 'rm -rf /var/www/test-project/storage/framework/views/*');
        });
    }

    /** @test */
    public function it_recalculates_storage_after_cleanup(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
            'storage_used_mb' => 1024,
        ]);

        Process::fake([
            '*rm -rf*' => Process::result(''),
            '*du -sm*' => Process::result('512'),
        ]);

        $this->storageService->cleanupProjectStorage($project);

        $project->refresh();
        $this->assertEquals(512, $project->storage_used_mb);
    }

    /** @test */
    public function it_returns_false_when_cleanup_fails_due_to_no_server(): void
    {
        $project = Project::factory()->create(['server_id' => null]);

        $result = $this->storageService->cleanupProjectStorage($project);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_cleanup_exception_gracefully(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::shouldReceive('fromShellCommandline')
            ->andThrow(new \RuntimeException('Connection timeout'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to cleanup project storage', \Mockery::on(function ($arg) {
                return isset($arg['project_id']) && isset($arg['error']);
            }));

        $result = $this->storageService->cleanupProjectStorage($project);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_logs_error_with_project_id_on_cleanup_failure(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::shouldReceive('fromShellCommandline')
            ->andThrow(new \Exception('Test error'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to cleanup project storage', [
                'project_id' => $project->id,
                'error' => 'Test error',
            ]);

        $this->storageService->cleanupProjectStorage($project);
    }

    /** @test */
    public function it_builds_ssh_command_with_basic_options(): void
    {
        $server = Server::factory()->create([
            'ip_address' => '192.168.1.100',
            'port' => 22,
            'username' => 'deploy',
            'ssh_key' => null,
        ]);
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result('1024'),
        ]);

        $this->storageService->calculateProjectStorage($project);

        Process::assertRan(function ($process) {
            return str_contains($process->command, 'deploy@192.168.1.100') &&
                   str_contains($process->command, '-p 22') &&
                   str_contains($process->command, '-o StrictHostKeyChecking=no');
        });
    }

    /** @test */
    public function it_builds_ssh_command_with_custom_port(): void
    {
        $server = Server::factory()->create([
            'ip_address' => '10.0.0.50',
            'port' => 2222,
            'username' => 'admin',
        ]);
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result('512'),
        ]);

        $this->storageService->calculateProjectStorage($project);

        Process::assertRan(function ($process) {
            return str_contains($process->command, '-p 2222');
        });
    }

    /** @test */
    public function it_builds_ssh_command_with_ssh_key(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result('1024'),
        ]);

        $this->storageService->calculateProjectStorage($project);

        Process::assertRan(function ($process) {
            return str_contains($process->command, '-i ');
        });
    }

    /** @test */
    public function it_escapes_remote_command_properly(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result('1024'),
        ]);

        $this->storageService->calculateProjectStorage($project);

        Process::assertRan(function ($process) {
            return str_contains($process->command, 'du -sm /var/www/test-project');
        });
    }

    /** @test */
    public function it_handles_large_storage_values(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result('1048576'), // 1 TB
        ]);

        $result = $this->storageService->calculateProjectStorage($project);

        $this->assertEquals(1048576, $result);
    }

    /** @test */
    public function it_handles_zero_storage_value(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result('0'),
        ]);

        $result = $this->storageService->calculateProjectStorage($project);

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_calculates_percentage_over_100_when_exceeding_limit(): void
    {
        Project::factory()->create(['storage_used_mb' => 153600]); // 150 GB

        config(['app.max_storage_gb' => 100]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertEquals(150.0, $result['percentage']);
    }

    /** @test */
    public function it_includes_strict_host_checking_disabled(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result('1024'),
        ]);

        $this->storageService->calculateProjectStorage($project);

        Process::assertRan(function ($process) {
            return str_contains($process->command, '-o StrictHostKeyChecking=no') &&
                   str_contains($process->command, '-o UserKnownHostsFile=/dev/null');
        });
    }

    /** @test */
    public function it_handles_multiple_projects_storage_calculation(): void
    {
        $server = Server::factory()->create();
        $project1 = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'project-one',
        ]);
        $project2 = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'project-two',
        ]);

        $callCount = 0;
        Process::fake([
            '*' => function () use (&$callCount) {
                $callCount++;

                return Process::result($callCount === 1 ? '1024' : '2048');
            },
        ]);

        $result1 = $this->storageService->calculateProjectStorage($project1);
        $result2 = $this->storageService->calculateProjectStorage($project2);

        $this->assertEquals(1024, $result1);
        $this->assertEquals(2048, $result2);
    }

    /** @test */
    public function it_uses_cut_command_to_extract_size(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result('1024'),
        ]);

        $this->storageService->calculateProjectStorage($project);

        Process::assertRan(function ($process) {
            return str_contains($process->command, 'cut -f1');
        });
    }

    /** @test */
    public function it_handles_empty_process_output(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result(''),
        ]);

        $result = $this->storageService->calculateProjectStorage($project);

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_runs_cleanup_commands_in_correct_order(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        $executedCommands = [];
        Process::fake([
            '*' => function ($process) use (&$executedCommands) {
                $executedCommands[] = $process->command;

                return Process::result('');
            },
        ]);

        $this->storageService->cleanupProjectStorage($project);

        $this->assertGreaterThan(0, count($executedCommands));
    }

    /** @test */
    public function it_continues_cleanup_even_if_one_command_fails(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        $commandCount = 0;
        Process::fake([
            '*' => function ($process) use (&$commandCount) {
                $commandCount++;

                // First command fails, others succeed
                return $commandCount === 1 ? Process::result('', 1) : Process::result('256');
            },
        ]);

        $this->storageService->cleanupProjectStorage($project);

        // Should execute all cleanup commands (4) plus the recalculation (1) = 5
        $this->assertEquals(5, $commandCount);
    }

    /** @test */
    public function it_formats_total_storage_with_two_decimal_places(): void
    {
        Project::factory()->create(['storage_used_mb' => 1536]); // 1.5 GB
        Project::factory()->create(['storage_used_mb' => 512]);  // 0.5 GB

        config(['app.max_storage_gb' => 100]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertEquals(2.0, $result['used_gb']);
        $this->assertEquals(2.0, $result['percentage']);
    }

    /** @test */
    public function it_handles_non_numeric_du_output_gracefully(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::fake([
            '*' => Process::result('invalid'),
        ]);

        $result = $this->storageService->calculateProjectStorage($project);

        // When trimmed and cast to int, 'invalid' becomes 0
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_logs_error_with_correct_context_on_calculation_failure(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        Process::shouldReceive('fromShellCommandline')
            ->andThrow(new \Exception('Network error'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to calculate project storage', [
                'project_id' => $project->id,
                'error' => 'Network error',
            ]);

        $this->storageService->calculateProjectStorage($project);
    }

    /** @test */
    public function it_calculates_storage_for_multiple_projects_independently(): void
    {
        $projects = Project::factory()->count(3)->create([
            'storage_used_mb' => 500,
        ]);

        config(['app.max_storage_gb' => 10]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertEquals(1500, $result['used_mb']);
        $this->assertEquals(1.46, $result['used_gb']);
    }

    /** @test */
    public function it_handles_projects_with_null_storage_values(): void
    {
        Project::factory()->create(['storage_used_mb' => null]);
        Project::factory()->create(['storage_used_mb' => 1024]);

        config(['app.max_storage_gb' => 100]);

        $result = $this->storageService->getTotalStorageUsed();

        // Null values should be treated as 0
        $this->assertEquals(1024, $result['used_mb']);
    }

    /** @test */
    public function it_calculates_total_storage_with_fractional_percentages(): void
    {
        Project::factory()->create(['storage_used_mb' => 512]); // 0.5 GB

        config(['app.max_storage_gb' => 1000]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertEquals(0.5, $result['used_gb']);
        $this->assertEquals(0.05, $result['percentage']);
    }

    /** @test */
    public function it_returns_consistent_storage_structure(): void
    {
        Project::factory()->create(['storage_used_mb' => 1024]);

        config(['app.max_storage_gb' => 100]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('used_mb', $result);
        $this->assertArrayHasKey('used_gb', $result);
        $this->assertArrayHasKey('max_gb', $result);
        $this->assertArrayHasKey('percentage', $result);
    }

    /** @test */
    public function it_handles_very_small_storage_values(): void
    {
        Project::factory()->create(['storage_used_mb' => 1]);

        config(['app.max_storage_gb' => 100]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertEquals(1, $result['used_mb']);
        $this->assertEquals(0.0, $result['used_gb']);
        $this->assertEquals(0.0, $result['percentage']);
    }

    /** @test */
    public function it_maintains_precision_in_storage_calculations(): void
    {
        // 1536 MB = 1.5 GB exactly
        Project::factory()->create(['storage_used_mb' => 1536]);

        config(['app.max_storage_gb' => 10]);

        $result = $this->storageService->getTotalStorageUsed();

        $this->assertEquals(1536, $result['used_mb']);
        $this->assertEquals(1.5, $result['used_gb']);
        $this->assertEquals(15.0, $result['percentage']);
    }
}
