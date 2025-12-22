<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\InstallDockerJob;
use App\Models\Server;
use App\Services\DockerInstallationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for InstallDockerJob
 *
 * Tests job execution, progress updates, log streaming,
 * success/failure handling, and cache management.
 */
#[CoversClass(InstallDockerJob::class)]
class InstallDockerJobTest extends TestCase
{
    private Server $server;

    private DockerInstallationService|MockInterface $installationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = Server::factory()->online()->create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.100',
            'username' => 'testuser',
        ]);

        $this->installationService = Mockery::mock(DockerInstallationService::class);
    }

    protected function tearDown(): void
    {
        // Clean up cache keys
        Cache::forget("docker_install_{$this->server->id}");
        Cache::forget("docker_install_logs_{$this->server->id}");

        parent::tearDown();
    }

    /**
     * Test: Job has correct timeout setting
     */
    public function test_job_has_correct_timeout(): void
    {
        $job = new InstallDockerJob($this->server);

        $this->assertEquals(600, $job->timeout);
    }

    /**
     * Test: Job has single try
     */
    public function test_job_has_single_try(): void
    {
        $job = new InstallDockerJob($this->server);

        $this->assertEquals(1, $job->tries);
    }

    /**
     * Test: Job initializes cache on start
     */
    public function test_job_initializes_cache_on_start(): void
    {
        $this->installationService
            ->shouldReceive('installDockerWithStreaming')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Docker installed successfully!',
                'version' => '24.0.7',
            ]);

        $this->app->instance(DockerInstallationService::class, $this->installationService);

        $job = new InstallDockerJob($this->server);
        $job->handle($this->installationService);

        // Verify logs cache was initialized
        $logsKey = "docker_install_logs_{$this->server->id}";
        $logs = Cache::get($logsKey);

        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);
    }

    /**
     * Test: Job updates status on successful installation
     */
    public function test_job_updates_status_on_success(): void
    {
        $this->installationService
            ->shouldReceive('installDockerWithStreaming')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Docker installed successfully!',
                'version' => '24.0.7',
            ]);

        $this->app->instance(DockerInstallationService::class, $this->installationService);

        $job = new InstallDockerJob($this->server);
        $job->handle($this->installationService);

        $cacheKey = "docker_install_{$this->server->id}";
        $status = Cache::get($cacheKey);

        $this->assertEquals('completed', $status['status']);
        $this->assertEquals(100, $status['progress']);
        $this->assertEquals('24.0.7', $status['version']);
    }

    /**
     * Test: Job updates status on failed installation
     */
    public function test_job_updates_status_on_failure(): void
    {
        $this->installationService
            ->shouldReceive('installDockerWithStreaming')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Installation failed: Connection timeout',
                'error' => 'Connection timeout',
            ]);

        $this->app->instance(DockerInstallationService::class, $this->installationService);

        $job = new InstallDockerJob($this->server);
        $job->handle($this->installationService);

        $cacheKey = "docker_install_{$this->server->id}";
        $status = Cache::get($cacheKey);

        $this->assertEquals('failed', $status['status']);
        $this->assertEquals(0, $status['progress']);
        $this->assertEquals('Installation failed', $status['current_step']);
    }

    /**
     * Test: Job appends logs during installation
     */
    public function test_job_appends_logs_during_installation(): void
    {
        $this->installationService
            ->shouldReceive('installDockerWithStreaming')
            ->once()
            ->withArgs(function ($server, $callback) {
                // Simulate streaming output
                if (is_callable($callback)) {
                    $callback('Step 1/6: Updating package index...', 20, 'Updating packages');
                    $callback('Step 2/6: Installing prerequisites...', 30, 'Installing prerequisites');
                }

                return true;
            })
            ->andReturn([
                'success' => true,
                'message' => 'Docker installed successfully!',
                'version' => '24.0.7',
            ]);

        $this->app->instance(DockerInstallationService::class, $this->installationService);

        $job = new InstallDockerJob($this->server);
        $job->handle($this->installationService);

        $logsKey = "docker_install_logs_{$this->server->id}";
        $logs = Cache::get($logsKey);

        // Should contain streamed logs
        $logsString = implode("\n", $logs);
        $this->assertStringContainsString('Step 1/6:', $logsString);
        $this->assertStringContainsString('Step 2/6:', $logsString);
    }

    /**
     * Test: Job logs info message on start
     */
    public function test_job_logs_info_on_start(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Docker installation job started', ['server_id' => $this->server->id]);

        Log::shouldReceive('info')
            ->once()
            ->with('Docker installation completed', Mockery::any());

        $this->installationService
            ->shouldReceive('installDockerWithStreaming')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Docker installed successfully!',
                'version' => '24.0.7',
            ]);

        $this->app->instance(DockerInstallationService::class, $this->installationService);

        $job = new InstallDockerJob($this->server);
        $job->handle($this->installationService);
    }

    /**
     * Test: Job logs error on failure
     */
    public function test_job_logs_error_on_failure(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Docker installation job started', ['server_id' => $this->server->id]);

        Log::shouldReceive('error')
            ->once()
            ->with('Docker installation failed', Mockery::any());

        $this->installationService
            ->shouldReceive('installDockerWithStreaming')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Installation failed',
            ]);

        $this->app->instance(DockerInstallationService::class, $this->installationService);

        $job = new InstallDockerJob($this->server);
        $job->handle($this->installationService);
    }

    /**
     * Test: Job handles exception gracefully
     */
    public function test_job_handles_exception_gracefully(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Docker installation job started', ['server_id' => $this->server->id]);

        Log::shouldReceive('error')
            ->once()
            ->with('Docker installation exception', Mockery::any());

        $exception = new \RuntimeException('SSH connection failed');

        $this->installationService
            ->shouldReceive('installDockerWithStreaming')
            ->once()
            ->andThrow($exception);

        $this->app->instance(DockerInstallationService::class, $this->installationService);

        $job = new InstallDockerJob($this->server);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SSH connection failed');

        $job->handle($this->installationService);

        // Verify status was updated to failed
        $cacheKey = "docker_install_{$this->server->id}";
        $status = Cache::get($cacheKey);

        $this->assertEquals('failed', $status['status']);
        $this->assertStringContainsString('SSH connection failed', $status['message']);
    }

    /**
     * Test: Failed callback updates status
     */
    public function test_failed_callback_updates_status(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Docker installation job failed', Mockery::any());

        $exception = new \Exception('Queue processing failed');

        $job = new InstallDockerJob($this->server);
        $job->failed($exception);

        $cacheKey = "docker_install_{$this->server->id}";
        $status = Cache::get($cacheKey);

        $this->assertEquals('failed', $status['status']);
        $this->assertEquals(0, $status['progress']);
        $this->assertStringContainsString('Queue processing failed', $status['message']);
    }

    /**
     * Test: Job includes installation header logs
     */
    public function test_job_includes_installation_header_logs(): void
    {
        $this->installationService
            ->shouldReceive('installDockerWithStreaming')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Docker installed successfully!',
                'version' => '24.0.7',
            ]);

        $this->app->instance(DockerInstallationService::class, $this->installationService);

        $job = new InstallDockerJob($this->server);
        $job->handle($this->installationService);

        $logsKey = "docker_install_logs_{$this->server->id}";
        $logs = Cache::get($logsKey);
        $logsString = implode("\n", $logs);

        $this->assertStringContainsString('=== Docker Installation Started ===', $logsString);
        $this->assertStringContainsString('Server:', $logsString);
        $this->assertStringContainsString('Username:', $logsString);
    }

    /**
     * Test: Successful installation includes completion log
     */
    public function test_successful_installation_includes_completion_log(): void
    {
        $this->installationService
            ->shouldReceive('installDockerWithStreaming')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Docker installed successfully!',
                'version' => '24.0.7',
            ]);

        $this->app->instance(DockerInstallationService::class, $this->installationService);

        $job = new InstallDockerJob($this->server);
        $job->handle($this->installationService);

        $logsKey = "docker_install_logs_{$this->server->id}";
        $logs = Cache::get($logsKey);
        $logsString = implode("\n", $logs);

        $this->assertStringContainsString('=== Installation Completed Successfully ===', $logsString);
        $this->assertStringContainsString('Docker Version: 24.0.7', $logsString);
    }

    /**
     * Test: Failed installation includes error log
     */
    public function test_failed_installation_includes_error_log(): void
    {
        $this->installationService
            ->shouldReceive('installDockerWithStreaming')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Permission denied',
            ]);

        $this->app->instance(DockerInstallationService::class, $this->installationService);

        $job = new InstallDockerJob($this->server);
        $job->handle($this->installationService);

        $logsKey = "docker_install_logs_{$this->server->id}";
        $logs = Cache::get($logsKey);
        $logsString = implode("\n", $logs);

        $this->assertStringContainsString('=== Installation Failed ===', $logsString);
        $this->assertStringContainsString('ERROR: Permission denied', $logsString);
    }

    /**
     * Test: Progress updates are stored in cache
     */
    public function test_progress_updates_are_stored_in_cache(): void
    {
        $progressValues = [];

        $this->installationService
            ->shouldReceive('installDockerWithStreaming')
            ->once()
            ->withArgs(function ($server, $callback) use (&$progressValues) {
                // Simulate streaming output with progress updates
                if (is_callable($callback)) {
                    $callback('Step 1/6', 20, 'Step 1');
                    $cacheKey = "docker_install_{$server->id}";
                    $progressValues[] = Cache::get($cacheKey)['progress'] ?? 0;

                    $callback('Step 3/6', 50, 'Step 3');
                    $progressValues[] = Cache::get($cacheKey)['progress'] ?? 0;
                }

                return true;
            })
            ->andReturn([
                'success' => true,
                'message' => 'Docker installed successfully!',
                'version' => '24.0.7',
            ]);

        $this->app->instance(DockerInstallationService::class, $this->installationService);

        $job = new InstallDockerJob($this->server);
        $job->handle($this->installationService);

        // Progress should have been updated during execution
        $this->assertNotEmpty($progressValues);
        $this->assertEquals(20, $progressValues[0]);
        $this->assertEquals(50, $progressValues[1]);
    }
}
