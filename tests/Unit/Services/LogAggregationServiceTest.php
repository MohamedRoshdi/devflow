<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\LogEntry;
use App\Models\LogSource;
use App\Models\Project;
use App\Models\Server;
use App\Services\LogAggregationService;
use App\Services\ServerConnectivityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class LogAggregationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LogAggregationService $service;

    protected ServerConnectivityService $connectivityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectivityService = Mockery::mock(ServerConnectivityService::class);
        $this->service = new LogAggregationService($this->connectivityService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // SYNC LOGS TESTS
    // ==========================================

    /** @test */
    public function it_syncs_logs_successfully_from_multiple_sources(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $source1 = LogSource::create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'name' => 'Laravel Logs',
            'type' => 'file',
            'path' => '/var/www/storage/logs/laravel.log',
            'is_active' => true,
        ]);

        $source2 = LogSource::create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'name' => 'Nginx Logs',
            'type' => 'file',
            'path' => '/var/log/nginx/error.log',
            'is_active' => true,
        ]);

        $this->connectivityService->shouldReceive('executeCommand')
            ->times(2)
            ->andReturn([
                'output' => '[2025-11-28 10:30:45] local.ERROR: Test error',
                'exit_code' => 0,
            ]);

        // Act
        $result = $this->service->syncLogs($server);

        // Assert
        $this->assertEquals(2, $result['success']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(2, $result['total_entries']);
        $this->assertEmpty($result['errors']);

        $source1->refresh();
        $source2->refresh();
        $this->assertNotNull($source1->last_synced_at);
        $this->assertNotNull($source2->last_synced_at);
    }

    /** @test */
    public function it_handles_sync_errors_gracefully(): void
    {
        // Arrange
        Log::shouldReceive('error')->once();

        $server = Server::factory()->online()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $source = LogSource::create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'name' => 'Broken Source',
            'type' => 'file',
            'path' => '/invalid/path.log',
            'is_active' => true,
        ]);

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andThrow(new \Exception('Connection failed'));

        // Act
        $result = $this->service->syncLogs($server);

        // Assert
        $this->assertEquals(0, $result['success']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals('Broken Source', $result['errors'][0]['source']);
        $this->assertEquals('Connection failed', $result['errors'][0]['error']);
    }

    /** @test */
    public function it_skips_inactive_log_sources(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();

        LogSource::create([
            'server_id' => $server->id,
            'name' => 'Inactive Source',
            'type' => 'file',
            'path' => '/var/log/test.log',
            'is_active' => false,
        ]);

        $this->connectivityService->shouldReceive('executeCommand')->never();

        // Act
        $result = $this->service->syncLogs($server);

        // Assert
        $this->assertEquals(0, $result['success']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['total_entries']);
    }

    /** @test */
    public function it_handles_empty_log_content(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();

        LogSource::create([
            'server_id' => $server->id,
            'name' => 'Empty Source',
            'type' => 'file',
            'path' => '/var/log/empty.log',
            'is_active' => true,
        ]);

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andReturn(['output' => '', 'exit_code' => 0]);

        // Act
        $result = $this->service->syncLogs($server);

        // Assert
        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['total_entries']);
    }

    // ==========================================
    // FETCH LOG FILE TESTS
    // ==========================================

    /** @test */
    public function it_fetches_log_file_successfully(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $path = '/var/log/test.log';
        $expectedOutput = 'Log line 1\nLog line 2\nLog line 3';

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->with($server, "tail -n 100 {$path} 2>/dev/null || echo ''")
            ->andReturn(['output' => $expectedOutput, 'exit_code' => 0]);

        // Act
        $result = $this->service->fetchLogFile($server, $path, 100);

        // Assert
        $this->assertEquals($expectedOutput, $result);
    }

    /** @test */
    public function it_fetches_log_file_with_custom_line_count(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $path = '/var/log/test.log';
        $lines = 500;

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->with($server, "tail -n {$lines} {$path} 2>/dev/null || echo ''")
            ->andReturn(['output' => 'test output', 'exit_code' => 0]);

        // Act
        $result = $this->service->fetchLogFile($server, $path, $lines);

        // Assert
        $this->assertNotEmpty($result);
    }

    /** @test */
    public function it_handles_fetch_log_file_failure(): void
    {
        // Arrange
        Log::shouldReceive('warning')->once();

        $server = Server::factory()->online()->create();
        $path = '/var/log/nonexistent.log';

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andThrow(new \Exception('File not found'));

        // Act
        $result = $this->service->fetchLogFile($server, $path);

        // Assert
        $this->assertEquals('', $result);
    }

    // ==========================================
    // NGINX LOG PARSING TESTS
    // ==========================================

    /** @test */
    public function it_parses_nginx_error_log(): void
    {
        // Arrange
        $content = '2025/11/28 10:30:45 [error] 123#123: *456 connect() failed';

        // Act
        $result = $this->service->parseNginxLog($content);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('nginx', $result[0]['source']);
        $this->assertEquals('error', $result[0]['level']);
        $this->assertEquals('connect() failed', $result[0]['message']);
        $this->assertInstanceOf(Carbon::class, $result[0]['logged_at']);
    }

    /** @test */
    public function it_parses_nginx_access_log(): void
    {
        // Arrange
        $content = '192.168.1.1 - - [28/Nov/2025:10:30:45 +0000] "GET / HTTP/1.1" 200 1234';

        // Act
        $result = $this->service->parseNginxLog($content);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('nginx', $result[0]['source']);
        $this->assertEquals('info', $result[0]['level']);
        $this->assertStringContainsString('GET /', $result[0]['message']);
    }

    /** @test */
    public function it_parses_multiple_nginx_error_levels(): void
    {
        // Arrange
        $content = "2025/11/28 10:30:45 [warn] 123#123: warning message\n";
        $content .= "2025/11/28 10:30:46 [error] 123#123: error message\n";
        $content .= '2025/11/28 10:30:47 [crit] 123#123: critical message';

        // Act
        $result = $this->service->parseNginxLog($content);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals('warning', $result[0]['level']);
        $this->assertEquals('error', $result[1]['level']);
        $this->assertEquals('critical', $result[2]['level']);
    }

    // ==========================================
    // LARAVEL LOG PARSING TESTS
    // ==========================================

    /** @test */
    public function it_parses_laravel_log_entry(): void
    {
        // Arrange
        $content = '[2025-11-28 10:30:45] local.ERROR: Division by zero';

        // Act
        $result = $this->service->parseLaravelLog($content);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('laravel', $result[0]['source']);
        $this->assertEquals('error', $result[0]['level']);
        $this->assertEquals('Division by zero', $result[0]['message']);
        $this->assertInstanceOf(Carbon::class, $result[0]['logged_at']);
    }

    /** @test */
    public function it_parses_laravel_log_with_stack_trace(): void
    {
        // Arrange
        $content = "[2025-11-28 10:30:45] local.ERROR: Division by zero\n";
        $content .= "#0 /var/www/app/Controllers/TestController.php(42): calculate()\n";
        $content .= '#1 /var/www/vendor/laravel/framework/...';

        // Act
        $result = $this->service->parseLaravelLog($content);

        // Assert
        $this->assertCount(1, $result);
        $this->assertStringContainsString('Division by zero', $result[0]['message']);
        $this->assertStringContainsString('#0 /var/www/app', $result[0]['message']);
        $this->assertEquals('/var/www/app/Controllers/TestController.php', $result[0]['file_path']);
        $this->assertEquals(42, $result[0]['line_number']);
    }

    /** @test */
    public function it_parses_multiple_laravel_log_entries(): void
    {
        // Arrange
        $content = "[2025-11-28 10:30:45] local.ERROR: First error\n";
        $content .= "[2025-11-28 10:30:46] local.WARNING: First warning\n";
        $content .= '[2025-11-28 10:30:47] local.INFO: First info';

        // Act
        $result = $this->service->parseLaravelLog($content);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals('error', $result[0]['level']);
        $this->assertEquals('warning', $result[1]['level']);
        $this->assertEquals('info', $result[2]['level']);
    }

    // ==========================================
    // PHP LOG PARSING TESTS
    // ==========================================

    /** @test */
    public function it_parses_php_error_log_with_file_info(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $source = LogSource::create([
            'server_id' => $server->id,
            'name' => 'PHP Logs',
            'type' => 'file',
            'path' => '/var/log/php-fpm.log',
            'is_active' => true,
        ]);

        $content = '[28-Nov-2025 10:30:45 UTC] PHP Warning: Undefined variable in /var/www/test.php on line 123';

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andReturn(['output' => $content, 'exit_code' => 0]);

        // Act
        $this->service->syncLogs($server);

        // Assert
        $entry = LogEntry::first();
        $this->assertNotNull($entry);
        $this->assertEquals('php', $entry->source);
        $this->assertEquals('warning', $entry->level);
        $this->assertEquals('/var/www/test.php', $entry->file_path);
        $this->assertEquals(123, $entry->line_number);
    }

    /** @test */
    public function it_parses_php_generic_error_log(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $source = LogSource::create([
            'server_id' => $server->id,
            'name' => 'PHP Logs',
            'type' => 'file',
            'path' => '/var/log/php.log',
            'is_active' => true,
        ]);

        $content = '[28-Nov-2025 10:30:45 UTC] Generic PHP error message';

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andReturn(['output' => $content, 'exit_code' => 0]);

        // Act
        $this->service->syncLogs($server);

        // Assert
        $entry = LogEntry::first();
        $this->assertEquals('php', $entry->source);
        $this->assertEquals('error', $entry->level);
        $this->assertEquals('Generic PHP error message', $entry->message);
    }

    // ==========================================
    // MYSQL LOG PARSING TESTS
    // ==========================================

    /** @test */
    public function it_parses_mysql_error_log(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $source = LogSource::create([
            'server_id' => $server->id,
            'name' => 'MySQL Logs',
            'type' => 'file',
            'path' => '/var/log/mysql/error.log',
            'is_active' => true,
        ]);

        $content = '2025-11-28T10:30:45.123456Z 123 [ERROR] InnoDB: Cannot allocate memory';

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andReturn(['output' => $content, 'exit_code' => 0]);

        // Act
        $this->service->syncLogs($server);

        // Assert
        $entry = LogEntry::first();
        $this->assertEquals('mysql', $entry->source);
        $this->assertEquals('error', $entry->level);
        $this->assertStringContainsString('Cannot allocate memory', $entry->message);
    }

    // ==========================================
    // SYSTEM LOG PARSING TESTS
    // ==========================================

    /** @test */
    public function it_parses_system_log(): void
    {
        // Arrange
        $content = 'Nov 28 10:30:45 server1 systemd[1]: Started nginx.service';

        // Act
        $result = $this->service->parseSystemLog($content);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('system', $result[0]['source']);
        $this->assertEquals('info', $result[0]['level']);
        $this->assertStringContainsString('Started nginx.service', $result[0]['message']);
        $this->assertEquals('server1', $result[0]['context']['hostname']);
        $this->assertEquals('systemd[1]', $result[0]['context']['service']);
    }

    // ==========================================
    // DOCKER LOG PARSING TESTS
    // ==========================================

    /** @test */
    public function it_parses_docker_log_with_timestamp(): void
    {
        // Arrange
        $content = '2025-11-28T10:30:45.123456Z Application started successfully';

        // Act
        $result = $this->service->parseDockerLog('app-container', $content);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('docker', $result[0]['source']);
        $this->assertEquals('info', $result[0]['level']);
        $this->assertEquals('Application started successfully', $result[0]['message']);
        $this->assertInstanceOf(Carbon::class, $result[0]['logged_at']);
    }

    /** @test */
    public function it_parses_docker_log_without_timestamp(): void
    {
        // Arrange
        $content = 'Simple log message without timestamp';

        // Act
        $result = $this->service->parseDockerLog('app-container', $content);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('docker', $result[0]['source']);
        $this->assertEquals('Simple log message without timestamp', $result[0]['message']);
    }

    // ==========================================
    // SEARCH LOGS TESTS
    // ==========================================

    /** @test */
    public function it_searches_logs_by_server_id(): void
    {
        // Arrange
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        LogEntry::create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Test error',
            'logged_at' => now(),
        ]);

        LogEntry::create([
            'server_id' => Server::factory()->create()->id,
            'source' => 'nginx',
            'level' => 'error',
            'message' => 'Other server error',
            'logged_at' => now(),
        ]);

        // Act
        $result = $this->service->searchLogs(['server_id' => $server->id]);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($server->id, $result->first()->server_id);
    }

    /** @test */
    public function it_searches_logs_by_project_id(): void
    {
        // Arrange
        $project = Project::factory()->create();

        LogEntry::create([
            'project_id' => $project->id,
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Project error',
            'logged_at' => now(),
        ]);

        LogEntry::create([
            'project_id' => Project::factory()->create()->id,
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Other project error',
            'logged_at' => now(),
        ]);

        // Act
        $result = $this->service->searchLogs(['project_id' => $project->id]);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($project->id, $result->first()->project_id);
    }

    /** @test */
    public function it_searches_logs_by_source(): void
    {
        // Arrange
        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Laravel error',
            'logged_at' => now(),
        ]);

        LogEntry::create([
            'source' => 'nginx',
            'level' => 'error',
            'message' => 'Nginx error',
            'logged_at' => now(),
        ]);

        // Act
        $result = $this->service->searchLogs(['source' => 'laravel']);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('laravel', $result->first()->source);
    }

    /** @test */
    public function it_searches_logs_by_level(): void
    {
        // Arrange
        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Error message',
            'logged_at' => now(),
        ]);

        LogEntry::create([
            'source' => 'laravel',
            'level' => 'warning',
            'message' => 'Warning message',
            'logged_at' => now(),
        ]);

        // Act
        $result = $this->service->searchLogs(['level' => 'error']);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('error', $result->first()->level);
    }

    /** @test */
    public function it_searches_logs_by_search_term(): void
    {
        // Arrange
        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Database connection failed',
            'logged_at' => now(),
        ]);

        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'File not found',
            'logged_at' => now(),
        ]);

        // Act
        $result = $this->service->searchLogs(['search' => 'Database']);

        // Assert
        $this->assertCount(1, $result);
        $this->assertStringContainsString('Database', $result->first()->message);
    }

    /** @test */
    public function it_searches_logs_by_date_range(): void
    {
        // Arrange
        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Old error',
            'logged_at' => now()->subDays(10),
        ]);

        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Recent error',
            'logged_at' => now()->subDay(),
        ]);

        // Act
        $result = $this->service->searchLogs([
            'date_from' => now()->subDays(2),
            'date_to' => now(),
        ]);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Recent error', $result->first()->message);
    }

    /** @test */
    public function it_searches_logs_with_multiple_filters(): void
    {
        // Arrange
        $server = Server::factory()->create();

        LogEntry::create([
            'server_id' => $server->id,
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Database error',
            'logged_at' => now(),
        ]);

        LogEntry::create([
            'server_id' => $server->id,
            'source' => 'nginx',
            'level' => 'error',
            'message' => 'Database error',
            'logged_at' => now(),
        ]);

        LogEntry::create([
            'server_id' => $server->id,
            'source' => 'laravel',
            'level' => 'warning',
            'message' => 'Database warning',
            'logged_at' => now(),
        ]);

        // Act
        $result = $this->service->searchLogs([
            'server_id' => $server->id,
            'source' => 'laravel',
            'level' => 'error',
            'search' => 'Database',
        ]);

        // Assert
        $this->assertCount(1, $result);
        $entry = $result->first();
        $this->assertEquals($server->id, $entry->server_id);
        $this->assertEquals('laravel', $entry->source);
        $this->assertEquals('error', $entry->level);
        $this->assertStringContainsString('Database', $entry->message);
    }

    /** @test */
    public function it_returns_logs_in_recent_order(): void
    {
        // Arrange
        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'First error',
            'logged_at' => now()->subHours(2),
        ]);

        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Second error',
            'logged_at' => now()->subHour(),
        ]);

        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Third error',
            'logged_at' => now(),
        ]);

        // Act
        $result = $this->service->searchLogs([]);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals('Third error', $result[0]->message);
        $this->assertEquals('Second error', $result[1]->message);
        $this->assertEquals('First error', $result[2]->message);
    }

    // ==========================================
    // CLEAN OLD LOGS TESTS
    // ==========================================

    /** @test */
    public function it_cleans_old_logs(): void
    {
        // Arrange
        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Old error',
            'logged_at' => now()->subDays(35),
        ]);

        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Recent error',
            'logged_at' => now()->subDays(10),
        ]);

        // Act
        $deleted = $this->service->cleanOldLogs(30);

        // Assert
        $this->assertEquals(1, $deleted);
        $this->assertEquals(1, LogEntry::count());
        $this->assertEquals('Recent error', LogEntry::first()->message);
    }

    /** @test */
    public function it_cleans_old_logs_with_custom_retention(): void
    {
        // Arrange
        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Very old error',
            'logged_at' => now()->subDays(100),
        ]);

        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Old error',
            'logged_at' => now()->subDays(80),
        ]);

        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Recent error',
            'logged_at' => now()->subDays(50),
        ]);

        // Act
        $deleted = $this->service->cleanOldLogs(60);

        // Assert
        $this->assertEquals(2, $deleted);
        $this->assertEquals(1, LogEntry::count());
    }

    /** @test */
    public function it_does_not_delete_recent_logs(): void
    {
        // Arrange
        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Recent error 1',
            'logged_at' => now()->subDays(10),
        ]);

        LogEntry::create([
            'source' => 'laravel',
            'level' => 'error',
            'message' => 'Recent error 2',
            'logged_at' => now()->subDays(5),
        ]);

        // Act
        $deleted = $this->service->cleanOldLogs(30);

        // Assert
        $this->assertEquals(0, $deleted);
        $this->assertEquals(2, LogEntry::count());
    }

    // ==========================================
    // DOCKER LOGS TESTS
    // ==========================================

    /** @test */
    public function it_fetches_docker_logs(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $containerName = 'app-container';
        $expectedOutput = 'Container log output';

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->with($server, "docker logs --tail 100 {$containerName} 2>&1 || echo ''")
            ->andReturn(['output' => $expectedOutput, 'exit_code' => 0]);

        // Access private method using reflection
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('fetchDockerLogs');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->service, $server, $containerName, 100);

        // Assert
        $this->assertEquals($expectedOutput, $result);
    }

    /** @test */
    public function it_handles_docker_logs_fetch_failure(): void
    {
        // Arrange
        Log::shouldReceive('warning')->once();

        $server = Server::factory()->online()->create();
        $containerName = 'nonexistent-container';

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andThrow(new \Exception('Container not found'));

        // Access private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('fetchDockerLogs');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->service, $server, $containerName, 100);

        // Assert
        $this->assertEquals('', $result);
    }

    // ==========================================
    // JOURNALD LOGS TESTS
    // ==========================================

    /** @test */
    public function it_fetches_journald_logs(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $unit = 'nginx.service';
        $expectedOutput = 'Journald log output';

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->with($server, "journalctl -u {$unit} -n 100 --no-pager 2>/dev/null || echo ''")
            ->andReturn(['output' => $expectedOutput, 'exit_code' => 0]);

        // Access private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('fetchJournaldLogs');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->service, $server, $unit, 100);

        // Assert
        $this->assertEquals($expectedOutput, $result);
    }

    /** @test */
    public function it_handles_journald_logs_fetch_failure(): void
    {
        // Arrange
        Log::shouldReceive('warning')->once();

        $server = Server::factory()->online()->create();
        $unit = 'invalid.service';

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andThrow(new \Exception('Unit not found'));

        // Access private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('fetchJournaldLogs');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->service, $server, $unit, 100);

        // Assert
        $this->assertEquals('', $result);
    }

    // ==========================================
    // LOG SOURCE TYPE TESTS
    // ==========================================

    /** @test */
    public function it_syncs_file_type_log_source(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $source = LogSource::create([
            'server_id' => $server->id,
            'name' => 'File Logs',
            'type' => 'file',
            'path' => '/var/log/test.log',
            'is_active' => true,
        ]);

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andReturn(['output' => 'Test log line', 'exit_code' => 0]);

        // Act
        $result = $this->service->syncLogs($server);

        // Assert
        $this->assertEquals(1, $result['success']);
        $this->assertEquals(1, $result['total_entries']);
    }

    /** @test */
    public function it_syncs_docker_type_log_source(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $source = LogSource::create([
            'server_id' => $server->id,
            'name' => 'Docker Container',
            'type' => 'docker',
            'path' => 'app-container',
            'is_active' => true,
        ]);

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andReturn(['output' => 'Container log', 'exit_code' => 0]);

        // Act
        $result = $this->service->syncLogs($server);

        // Assert
        $this->assertEquals(1, $result['success']);
    }

    /** @test */
    public function it_syncs_journald_type_log_source(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $source = LogSource::create([
            'server_id' => $server->id,
            'name' => 'Systemd Service',
            'type' => 'journald',
            'path' => 'nginx.service',
            'is_active' => true,
        ]);

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andReturn(['output' => 'Nov 28 10:30:45 server nginx[123]: Started', 'exit_code' => 0]);

        // Act
        $result = $this->service->syncLogs($server);

        // Assert
        $this->assertEquals(1, $result['success']);
    }

    /** @test */
    public function it_throws_exception_for_unsupported_log_type(): void
    {
        // Arrange
        Log::shouldReceive('error')->once();

        $server = Server::factory()->online()->create();
        $source = LogSource::create([
            'server_id' => $server->id,
            'name' => 'Invalid Source',
            'type' => 'unsupported_type',
            'path' => '/var/log/test.log',
            'is_active' => true,
        ]);

        // Act
        $result = $this->service->syncLogs($server);

        // Assert
        $this->assertEquals(0, $result['success']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
    }

    // ==========================================
    // LEVEL NORMALIZATION TESTS
    // ==========================================

    /** @test */
    public function it_normalizes_warning_levels(): void
    {
        // Arrange
        $content1 = '2025/11/28 10:30:45 [warn] 123#123: warning message';
        $content2 = '2025/11/28 10:30:45 [warning] 123#123: warning message';

        // Act
        $result1 = $this->service->parseNginxLog($content1);
        $result2 = $this->service->parseNginxLog($content2);

        // Assert
        $this->assertEquals('warning', $result1[0]['level']);
        $this->assertEquals('warning', $result2[0]['level']);
    }

    /** @test */
    public function it_normalizes_error_levels(): void
    {
        // Arrange
        $content1 = '2025/11/28 10:30:45 [err] 123#123: error message';
        $content2 = '2025/11/28 10:30:45 [error] 123#123: error message';

        // Act
        $result1 = $this->service->parseNginxLog($content1);
        $result2 = $this->service->parseNginxLog($content2);

        // Assert
        $this->assertEquals('error', $result1[0]['level']);
        $this->assertEquals('error', $result2[0]['level']);
    }

    /** @test */
    public function it_normalizes_critical_levels(): void
    {
        // Arrange
        $content1 = '2025/11/28 10:30:45 [crit] 123#123: critical message';
        $content2 = '2025/11/28 10:30:45 [critical] 123#123: critical message';

        // Act
        $result1 = $this->service->parseNginxLog($content1);
        $result2 = $this->service->parseNginxLog($content2);

        // Assert
        $this->assertEquals('critical', $result1[0]['level']);
        $this->assertEquals('critical', $result2[0]['level']);
    }

    /** @test */
    public function it_defaults_unknown_levels_to_info(): void
    {
        // Arrange
        $content = '2025/11/28 10:30:45 [unknown] 123#123: some message';

        // Act
        $result = $this->service->parseNginxLog($content);

        // Assert
        $this->assertEquals('info', $result[0]['level']);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /** @test */
    public function it_handles_malformed_log_lines(): void
    {
        // Arrange
        $content = "Not a valid log format\nAnother invalid line\n123456789";

        // Act
        $result = $this->service->parseNginxLog($content);

        // Assert
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_creates_log_entries_with_server_and_project_associations(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $source = LogSource::create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'name' => 'Test Source',
            'type' => 'file',
            'path' => '/var/log/test.log',
            'is_active' => true,
        ]);

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andReturn(['output' => '[2025-11-28 10:30:45] local.ERROR: Test', 'exit_code' => 0]);

        // Act
        $this->service->syncLogs($server);

        // Assert
        $entry = LogEntry::first();
        $this->assertEquals($server->id, $entry->server_id);
        $this->assertEquals($project->id, $entry->project_id);
    }

    /** @test */
    public function it_handles_multiline_log_messages(): void
    {
        // Arrange
        $content = "[2025-11-28 10:30:45] local.ERROR: Exception message\n";
        $content .= "   Line 2 of stack trace\n";
        $content .= '   Line 3 of stack trace';

        // Act
        $result = $this->service->parseLaravelLog($content);

        // Assert
        $this->assertCount(1, $result);
        $this->assertStringContainsString('Exception message', $result[0]['message']);
        $this->assertStringContainsString('Line 2 of stack trace', $result[0]['message']);
        $this->assertStringContainsString('Line 3 of stack trace', $result[0]['message']);
    }

    /** @test */
    public function it_filters_empty_log_lines(): void
    {
        // Arrange
        $server = Server::factory()->online()->create();
        $source = LogSource::create([
            'server_id' => $server->id,
            'name' => 'Test Source',
            'type' => 'file',
            'path' => '/var/log/test.log',
            'is_active' => true,
        ]);

        $content = "Log line 1\n\n\nLog line 2\n   \n";

        $this->connectivityService->shouldReceive('executeCommand')
            ->once()
            ->andReturn(['output' => $content, 'exit_code' => 0]);

        // Act
        $this->service->syncLogs($server);

        // Assert
        $this->assertEquals(2, LogEntry::count());
    }
}
