<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Test;
use App\Models\Project;
use App\Services\PhpFpmPoolService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;
use Tests\Traits\CreatesServers;
use Tests\Traits\MocksSSH;

class PhpFpmPoolServiceTest extends TestCase
{
    use CreatesServers, MocksSSH;

    protected PhpFpmPoolService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PhpFpmPoolService;
    }

    #[Test]
    public function it_generates_pool_config_with_correct_slug(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'my-app',
        ]);

        $config = $this->service->generatePoolConfig($project);

        $this->assertStringContainsString('[my-app]', $config);
        $this->assertStringContainsString('/run/php/my-app.sock', $config);
        $this->assertStringContainsString('/var/log/php/my-app-error.log', $config);
    }

    #[Test]
    public function it_generates_pool_config_with_default_pm_settings(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $config = $this->service->generatePoolConfig($project);

        $this->assertStringContainsString('pm.max_children = 10', $config);
        $this->assertStringContainsString('pm.start_servers = 2', $config);
        $this->assertStringContainsString('pm.min_spare_servers = 1', $config);
        $this->assertStringContainsString('pm.max_spare_servers = 3', $config);
        $this->assertStringContainsString('pm.max_requests = 500', $config);
        $this->assertStringContainsString('pm = dynamic', $config);
    }

    #[Test]
    public function it_installs_pool_on_server(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-pool',
            'php_version' => '8.4',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        $result = $this->service->installPool($server, $project);

        $this->assertTrue($result);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'tee /etc/php/8.4/fpm/pool.d/test-pool.conf');
        });
    }

    #[Test]
    public function it_installs_pool_with_correct_php_version_path(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'versioned-app',
            'php_version' => '8.3',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        $this->service->installPool($server, $project);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, '/etc/php/8.3/fpm/pool.d/versioned-app.conf');
        });
    }

    #[Test]
    public function it_removes_pool_from_server(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'remove-me',
            'php_version' => '8.4',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        $result = $this->service->removePool($server, $project);

        $this->assertTrue($result);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'rm -f /etc/php/8.4/fpm/pool.d/remove-me.conf');
        });
    }

    #[Test]
    public function it_checks_if_pool_is_installed_returns_true(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'installed-app',
            'php_version' => '8.4',
        ]);

        Process::fake([
            '*test -f*' => Process::result(output: 'exists'),
            '*' => Process::result(output: ''),
        ]);

        $this->assertTrue($this->service->isInstalled($server, $project));
    }

    #[Test]
    public function it_checks_if_pool_is_installed_returns_false(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'missing-app',
            'php_version' => '8.4',
        ]);

        Process::fake([
            '*test -f*' => Process::result(output: '', exitCode: 1),
            '*' => Process::result(output: ''),
        ]);

        $this->assertFalse($this->service->isInstalled($server, $project));
    }

    #[Test]
    public function it_reloads_fpm_with_correct_service_name(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'php_version' => '8.4',
        ]);

        Process::fake([
            '*systemctl reload*' => Process::result(output: 'Reloaded'),
            '*' => Process::result(output: ''),
        ]);

        $result = $this->service->reloadFpm($server, $project);

        $this->assertTrue($result);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'systemctl reload php84-fpm')
                || str_contains($command, 'systemctl reload php8.4-fpm');
        });
    }

    #[Test]
    public function it_creates_log_directory_on_install(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'log-dir-test',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        $this->service->installPool($server, $project);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'mkdir -p /var/log/php');
        });
    }

    #[Test]
    public function it_logs_install_activity(): void
    {
        Log::spy();

        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'logged-app',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        $this->service->installPool($server, $project);

        Log::shouldHaveReceived('info') // @phpstan-ignore staticMethod.notFound
            ->withArgs(fn (string $message): bool => str_contains($message, 'Installing PHP-FPM pool'))
            ->once();

        Log::shouldHaveReceived('info') // @phpstan-ignore staticMethod.notFound
            ->withArgs(fn (string $message): bool => str_contains($message, 'PHP-FPM pool installed'))
            ->once();

        $this->assertTrue(true); // @phpstan-ignore method.alreadyNarrowedType
    }
}
