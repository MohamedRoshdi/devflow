<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Test;
use App\Models\Domain;
use App\Models\Project;
use App\Services\NginxConfigService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;
use Tests\Traits\CreatesServers;
use Tests\Traits\MocksSSH;

class NginxConfigServiceTest extends TestCase
{
    use CreatesServers, MocksSSH;

    protected NginxConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NginxConfigService;
    }

    #[Test]
    public function it_generates_vhost_with_project_specific_fpm_socket(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'my-project',
        ]);

        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'ssl_enabled' => false,
        ]);

        $vhost = $this->service->generateVhost($project, $domain);

        $this->assertStringContainsString('fastcgi_pass unix:/run/php/my-project.sock', $vhost);
        $this->assertStringContainsString('root /var/www/my-project/public', $vhost);
    }

    #[Test]
    public function it_generates_vhost_without_ssl(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'nossl.com',
            'ssl_enabled' => false,
        ]);

        $vhost = $this->service->generateVhost($project, $domain);

        $this->assertStringContainsString('listen 80', $vhost);
        $this->assertStringNotContainsString('listen 443', $vhost);
        $this->assertStringNotContainsString('ssl_certificate', $vhost);
    }

    #[Test]
    public function it_generates_vhost_with_letsencrypt_ssl(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'secure.com',
            'ssl_enabled' => true,
            'ssl_provider' => 'letsencrypt',
        ]);

        $vhost = $this->service->generateVhost($project, $domain);

        $this->assertStringContainsString('listen 443 ssl http2', $vhost);
        $this->assertStringContainsString('/etc/letsencrypt/live/secure.com/fullchain.pem', $vhost);
        $this->assertStringContainsString('/etc/letsencrypt/live/secure.com/privkey.pem', $vhost);
    }

    #[Test]
    public function it_generates_vhost_with_cloudflare_ssl(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'cf.com',
            'ssl_enabled' => true,
            'ssl_provider' => 'cloudflare',
        ]);

        $vhost = $this->service->generateVhost($project, $domain);

        $this->assertStringContainsString('/etc/ssl/cloudflare/cf.com.pem', $vhost);
        $this->assertStringContainsString('/etc/ssl/cloudflare/cf.com.key', $vhost);
    }

    #[Test]
    public function it_generates_vhost_with_custom_ssl_paths(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'custom.com',
            'ssl_enabled' => true,
            'ssl_provider' => 'custom',
            'ssl_certificate' => '/custom/path/cert.pem',
            'ssl_private_key' => '/custom/path/key.pem',
        ]);

        $vhost = $this->service->generateVhost($project, $domain);

        $this->assertStringContainsString('/custom/path/cert.pem', $vhost);
        $this->assertStringContainsString('/custom/path/key.pem', $vhost);
    }

    #[Test]
    public function it_generates_vhost_with_security_headers(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'headers.com',
            'ssl_enabled' => false,
        ]);

        $vhost = $this->service->generateVhost($project, $domain);

        $this->assertStringContainsString('X-Frame-Options', $vhost);
        $this->assertStringContainsString('X-Content-Type-Options', $vhost);
        $this->assertStringContainsString('X-XSS-Protection', $vhost);
        $this->assertStringContainsString('Referrer-Policy', $vhost);
    }

    #[Test]
    public function it_installs_vhost_on_server(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'install-test',
        ]);

        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'install.com',
            'ssl_enabled' => false,
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        $result = $this->service->installVhost($server, $project, $domain);

        $this->assertTrue($result);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'tee /etc/nginx/sites-available/install-test');
        });

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'ln -sfn');
        });

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'nginx -t');
        });

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'systemctl reload nginx');
        });
    }

    #[Test]
    public function it_throws_on_nginx_test_failure(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'broken-config',
        ]);

        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'broken.com',
            'ssl_enabled' => false,
        ]);

        Process::fake([
            '*nginx -t*' => Process::result(
                output: 'nginx: configuration file test failed',
                exitCode: 1
            ),
            '*' => Process::result(output: 'Success'),
        ]);

        Log::spy();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nginx config test failed');

        $this->service->installVhost($server, $project, $domain);
    }

    #[Test]
    public function it_removes_vhost_from_server(): void
    {
        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'remove-vhost',
        ]);

        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'remove.com',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        Log::spy();

        $result = $this->service->removeVhost($server, $domain);

        $this->assertTrue($result);

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'rm -f /etc/nginx/sites-enabled/remove-vhost');
        });

        Process::assertRan(function ($process): bool {
            $command = (string) $process->command;
            return str_contains($command, 'rm -f /etc/nginx/sites-available/remove-vhost');
        });
    }

    #[Test]
    public function it_tests_nginx_config(): void
    {
        $server = $this->createOnlineServer();

        Process::fake([
            '*nginx -t*' => Process::result(output: 'nginx: configuration file syntax is ok'),
        ]);

        $result = $this->service->testConfig($server);

        $this->assertTrue($result);
    }
}
