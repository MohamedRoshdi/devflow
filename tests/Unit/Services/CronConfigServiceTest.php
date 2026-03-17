<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Services\CronConfigService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesServers;
use Tests\Traits\MocksSSH;

class CronConfigServiceTest extends TestCase
{
    use CreatesServers, MocksSSH;

    protected CronConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CronConfigService;
    }

    #[Test]
    public function it_generates_config_with_correct_slug_and_user(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'cron-app',
        ]);

        $config = $this->service->generateConfig($project);

        $this->assertStringContainsString('www-data', $config);
        $this->assertStringContainsString('/var/www/cron-app', $config);
        $this->assertStringContainsString('cron-app', $config);
    }

    #[Test]
    public function it_generates_config_with_schedule_run_command(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'schedule-app',
        ]);

        $config = $this->service->generateConfig($project);

        $this->assertStringContainsString('artisan schedule:run', $config);
        $this->assertStringContainsString('* * * * *', $config);
    }

    #[Test]
    public function it_generates_config_using_deploy_path_when_set(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'deploy-cron-test',
            'deploy_path' => '/opt/apps/my-cron-app',
        ]);

        $config = $this->service->generateConfig($project);

        $this->assertStringContainsString('php /opt/apps/my-cron-app/artisan schedule:run', $config);
        $this->assertStringNotContainsString('/var/www/', $config);
    }

    #[Test]
    public function it_installs_config_on_server(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'install-cron',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        $result = $this->service->installConfig($server, $project);

        $this->assertTrue($result);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;

            return str_contains($command, 'tee /etc/cron.d/install-cron-scheduler');
        });

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;

            return str_contains($command, 'chmod 644 /etc/cron.d/install-cron-scheduler');
        });
    }

    #[Test]
    public function it_removes_config_from_server(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'remove-cron',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        Log::spy();

        $result = $this->service->removeConfig($server, $project);

        $this->assertTrue($result);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;

            return str_contains($command, 'rm -f /etc/cron.d/remove-cron-scheduler');
        });
    }

    #[Test]
    public function it_checks_installed_returns_true(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'exists-cron',
        ]);

        Process::fake([
            '*test -f*' => Process::result(output: 'exists'),
            '*' => Process::result(output: ''),
        ]);

        $this->assertTrue($this->service->isInstalled($server, $project));
    }

    #[Test]
    public function it_checks_installed_returns_false(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'missing-cron',
        ]);

        Process::fake([
            '*test -f*' => Process::result(output: '', exitCode: 1),
            '*' => Process::result(output: ''),
        ]);

        $this->assertFalse($this->service->isInstalled($server, $project));
    }

    #[Test]
    public function it_installs_config_logs_activity(): void
    {
        Log::spy();

        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'logged-cron',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        $this->service->installConfig($server, $project);

        Log::shouldHaveReceived('info') // @phpstan-ignore staticMethod.notFound
            ->withArgs(fn (string $message): bool => str_contains($message, 'Installing cron config'))
            ->once();

        Log::shouldHaveReceived('info') // @phpstan-ignore staticMethod.notFound
            ->withArgs(fn (string $message): bool => str_contains($message, 'Cron config installed'))
            ->once();

        $this->assertTrue(true); // @phpstan-ignore method.alreadyNarrowedType
    }
}
