<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Test;
use App\Models\Project;
use App\Services\SupervisorConfigService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;
use Tests\Traits\CreatesServers;
use Tests\Traits\MocksSSH;

class SupervisorConfigServiceTest extends TestCase
{
    use CreatesServers, MocksSSH;

    protected SupervisorConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SupervisorConfigService;
    }

    #[Test]
    public function it_generates_config_with_default_options(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'worker-app',
        ]);

        $config = $this->service->generateConfig($project);

        $this->assertStringContainsString('[program:worker-app-worker]', $config);
        $this->assertStringContainsString('--queue=default', $config);
        $this->assertStringContainsString('numprocs=2', $config);
        $this->assertStringContainsString('--tries=3', $config);
        $this->assertStringContainsString('--memory=128', $config);
        $this->assertStringContainsString('user=www-data', $config);
    }

    #[Test]
    public function it_generates_config_with_custom_options(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'custom-worker',
        ]);

        $config = $this->service->generateConfig($project, [
            'queue_names' => 'emails,notifications',
            'num_workers' => 4,
            'max_tries' => 5,
            'max_time' => 7200,
            'memory_limit' => 256,
        ]);

        $this->assertStringContainsString('--queue=emails,notifications', $config);
        $this->assertStringContainsString('numprocs=4', $config);
        $this->assertStringContainsString('--tries=5', $config);
        $this->assertStringContainsString('--max-time=7200', $config);
        $this->assertStringContainsString('--memory=256', $config);
    }

    #[Test]
    public function it_generates_config_with_correct_artisan_path(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'path-test',
        ]);

        $config = $this->service->generateConfig($project);

        $this->assertStringContainsString('php /var/www/path-test/artisan queue:work', $config);
    }

    #[Test]
    public function it_installs_config_on_server(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'install-sv',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        $result = $this->service->installConfig($server, $project);

        $this->assertTrue($result);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'tee /etc/supervisor/conf.d/install-sv-worker.conf');
        });
    }

    #[Test]
    public function it_installs_config_runs_supervisorctl_reread_update(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'reread-test',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        $this->service->installConfig($server, $project);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'supervisorctl reread') && str_contains($command, 'supervisorctl update');
        });
    }

    #[Test]
    public function it_removes_config_stops_workers_first(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'stop-first',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        Log::spy();

        $result = $this->service->removeConfig($server, $project);

        $this->assertTrue($result);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'supervisorctl stop stop-first-worker:*');
        });
    }

    #[Test]
    public function it_removes_config_runs_supervisorctl_update(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'update-after-rm',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        Log::spy();

        $this->service->removeConfig($server, $project);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'rm -f /etc/supervisor/conf.d/update-after-rm-worker.conf');
        });

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'supervisorctl reread') && str_contains($command, 'supervisorctl update');
        });
    }

    #[Test]
    public function it_restarts_workers(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'restart-test',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        Log::spy();

        $result = $this->service->restartWorkers($server, $project);

        $this->assertTrue($result);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'php artisan queue:restart');
        });

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'supervisorctl restart restart-test-worker:*');
        });
    }

    #[Test]
    public function it_gets_worker_status_parses_running(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'status-test',
        ]);

        Process::fake([
            '*supervisorctl status*' => Process::result(
                output: "status-test-worker:status-test-worker_00 RUNNING pid 1234, uptime 1:23:45\nstatus-test-worker:status-test-worker_01 RUNNING pid 1235, uptime 1:23:44"
            ),
            '*' => Process::result(output: ''),
        ]);

        $workers = $this->service->getWorkerStatus($server, $project);

        $this->assertCount(2, $workers);
        $this->assertEquals('RUNNING', $workers[0]['status']);
        $this->assertEquals('RUNNING', $workers[1]['status']);
    }

    #[Test]
    public function it_gets_worker_status_handles_empty_output(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'empty-status',
        ]);

        Process::fake([
            '*supervisorctl status*' => Process::result(output: ''),
            '*' => Process::result(output: ''),
        ]);

        $workers = $this->service->getWorkerStatus($server, $project);

        $this->assertCount(0, $workers);
    }
}
