<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\Server;
use App\Services\DockerService;
use App\Services\LogAggregationService;
use App\Services\LogManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class LogManagerServiceTest extends TestCase
{
    use RefreshDatabase;

    private LogManagerService $service;
    private DockerService $dockerService;
    private LogAggregationService $logAggregationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dockerService = Mockery::mock(DockerService::class);
        $this->logAggregationService = Mockery::mock(LogAggregationService::class);

        $this->service = new LogManagerService(
            $this->dockerService,
            $this->logAggregationService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // GET RECENT ERRORS TESTS
    // ==========================================

    /** @test */
    public function it_retrieves_recent_errors_for_laravel_project(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
            'slug' => 'test-project',
        ]);

        $logContent = "[2025-01-01 10:00:00] local.ERROR: Database connection failed\n[2025-01-01 10:05:00] local.CRITICAL: Out of memory";

        $this->logAggregationService->shouldReceive('fetchLogFile')
            ->once()
            ->andReturn($logContent);

        $this->logAggregationService->shouldReceive('parseLaravelLog')
            ->once()
            ->with($logContent)
            ->andReturn([
                [
                    'source' => 'laravel',
                    'level' => 'error',
                    'message' => 'Database connection failed',
                    'logged_at' => now(),
                ],
                [
                    'source' => 'laravel',
                    'level' => 'critical',
                    'message' => 'Out of memory',
                    'logged_at' => now(),
                ],
            ]);

        $errors = $this->service->getRecentErrors($project, 50);

        $this->assertInstanceOf(Collection::class, $errors);
        $this->assertCount(2, $errors);
    }

    /** @test */
    public function it_filters_errors_by_level(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
        ]);

        $this->logAggregationService->shouldReceive('fetchLogFile')
            ->andReturn('log content');

        $this->logAggregationService->shouldReceive('parseLaravelLog')
            ->andReturn([
                ['source' => 'laravel', 'level' => 'error', 'message' => 'Error 1', 'logged_at' => now()],
                ['source' => 'laravel', 'level' => 'warning', 'message' => 'Warning 1', 'logged_at' => now()],
                ['source' => 'laravel', 'level' => 'critical', 'message' => 'Critical 1', 'logged_at' => now()],
                ['source' => 'laravel', 'level' => 'info', 'message' => 'Info 1', 'logged_at' => now()],
            ]);

        $errors = $this->service->getRecentErrors($project, 50);

        $this->assertCount(3, $errors); // Only error, warning, critical
    }

    /** @test */
    public function it_handles_empty_logs(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
        ]);

        $this->logAggregationService->shouldReceive('fetchLogFile')
            ->andReturn('');

        $errors = $this->service->getRecentErrors($project);

        $this->assertInstanceOf(Collection::class, $errors);
        $this->assertCount(0, $errors);
    }

    /** @test */
    public function it_handles_log_fetch_exception(): void
    {
        Log::shouldReceive('error')->once();

        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
        ]);

        $this->logAggregationService->shouldReceive('fetchLogFile')
            ->andThrow(new \Exception('Connection failed'));

        $errors = $this->service->getRecentErrors($project);

        $this->assertCount(0, $errors);
    }

    // ==========================================
    // LOG ROTATION TESTS
    // ==========================================

    /** @test */
    public function it_rotates_laravel_logs(): void
    {
        $server = Server::factory()->create(['username' => 'root']);
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
            'slug' => 'test-project',
        ]);

        $result = $this->service->rotateLogs($project);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('rotated', $result);
        $this->assertArrayHasKey('archived', $result);
    }

    /** @test */
    public function it_handles_rotation_failure(): void
    {
        Log::shouldReceive('error')->once();

        $project = Project::factory()->create([
            'server_id' => null, // Missing server will cause failure
        ]);

        $result = $this->service->rotateLogs($project);

        $this->assertEquals(0, $result['rotated']);
        $this->assertNull($result['archived']);
        $this->assertArrayHasKey('error', $result);
    }

    // ==========================================
    // LOG STATS TESTS
    // ==========================================

    /** @test */
    public function it_gets_log_statistics(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
        ]);

        $stats = $this->service->getLogStats($project);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_size_bytes', $stats);
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('files', $stats);
        $this->assertArrayHasKey('error_count_by_level', $stats);
    }

    // ==========================================
    // LOG SEARCH TESTS
    // ==========================================

    /** @test */
    public function it_searches_logs_for_pattern(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
        ]);

        $this->logAggregationService->shouldReceive('parseLaravelLog')
            ->once()
            ->andReturn([
                [
                    'source' => 'laravel',
                    'level' => 'error',
                    'message' => 'Database connection timeout',
                    'logged_at' => now(),
                ],
            ]);

        $results = $this->service->searchLogs($project, 'database');

        $this->assertInstanceOf(Collection::class, $results);
    }

    /** @test */
    public function it_filters_search_results_by_level(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
        ]);

        $this->logAggregationService->shouldReceive('parseLaravelLog')
            ->andReturn([
                ['source' => 'laravel', 'level' => 'error', 'message' => 'Error message', 'logged_at' => now()],
                ['source' => 'laravel', 'level' => 'warning', 'message' => 'Warning message', 'logged_at' => now()],
            ]);

        $results = $this->service->searchLogs($project, 'message', 'error');

        $this->assertCount(1, $results);
        $this->assertEquals('error', $results->first()['level']);
    }

    /** @test */
    public function it_handles_search_exception(): void
    {
        Log::shouldReceive('error')->once();

        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
        ]);

        $this->logAggregationService->shouldReceive('parseLaravelLog')
            ->andThrow(new \Exception('Parse error'));

        $results = $this->service->searchLogs($project, 'pattern');

        $this->assertCount(0, $results);
    }

    // ==========================================
    // LOG EXPORT TESTS
    // ==========================================

    /** @test */
    public function it_exports_logs_as_archive(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
            'slug' => 'test-project',
        ]);

        $archivePath = $this->service->exportLogs($project);

        $this->assertIsString($archivePath);
        $this->assertStringContainsString('test-project_logs', $archivePath);
    }

    /** @test */
    public function it_exports_logs_with_date_filter(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-project',
        ]);

        $from = now()->subDays(7);
        $to = now();

        $archivePath = $this->service->exportLogs($project, $from, $to);

        $this->assertIsString($archivePath);
    }

    /** @test */
    public function it_handles_export_failure(): void
    {
        $project = Project::factory()->create([
            'server_id' => null,
        ]);

        $this->expectException(\RuntimeException::class);

        $this->service->exportLogs($project);
    }

    // ==========================================
    // LOG CLEARING TESTS
    // ==========================================

    /** @test */
    public function it_clears_laravel_logs(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
        ]);

        $this->dockerService->shouldReceive('clearLaravelLogs')
            ->once()
            ->with($project)
            ->andReturn(['success' => true]);

        $result = $this->service->clearLogs($project);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_archives_before_clearing(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'symfony',
        ]);

        $result = $this->service->clearLogs($project);

        $this->assertIsBool($result);
    }

    /** @test */
    public function it_handles_clear_failure(): void
    {
        Log::shouldReceive('error')->once();
        Log::shouldReceive('warning')->once();

        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
        ]);

        $this->dockerService->shouldReceive('clearLaravelLogs')
            ->andThrow(new \Exception('Clear failed'));

        $result = $this->service->clearLogs($project);

        $this->assertFalse($result);
    }

    // ==========================================
    // LOG TAIL TESTS
    // ==========================================

    /** @test */
    public function it_tails_laravel_logs(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
        ]);

        $this->dockerService->shouldReceive('getLaravelLogs')
            ->once()
            ->with($project, 100)
            ->andReturn([
                'success' => true,
                'logs' => 'Log line 1\nLog line 2',
            ]);

        $result = $this->service->tailLogs($project, 100);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Log line 1', $result['logs']);
    }

    /** @test */
    public function it_tails_non_laravel_logs(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'symfony',
        ]);

        $this->logAggregationService->shouldReceive('fetchLogFile')
            ->once()
            ->andReturn('Symfony log content');

        $result = $this->service->tailLogs($project, 50);

        $this->assertTrue($result['success']);
        $this->assertEquals('Symfony log content', $result['logs']);
    }

    /** @test */
    public function it_handles_empty_tail_logs(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'symfony',
        ]);

        $this->logAggregationService->shouldReceive('fetchLogFile')
            ->andReturn('');

        $result = $this->service->tailLogs($project);

        $this->assertTrue($result['success']);
        $this->assertEquals('No logs available', $result['logs']);
    }

    /** @test */
    public function it_handles_tail_failure(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
        ]);

        $this->dockerService->shouldReceive('getLaravelLogs')
            ->andThrow(new \Exception('Tail failed'));

        $result = $this->service->tailLogs($project);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // ==========================================
    // GENERIC LOG PARSING TESTS
    // ==========================================

    /** @test */
    public function it_parses_generic_error_log_format(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'custom',
        ]);

        $logContent = "[2025-01-01 10:00:00] Error occurred\n[2025-01-01 10:05:00] Warning: deprecated function";

        $this->logAggregationService->shouldReceive('fetchLogFile')
            ->andReturn($logContent);

        $errors = $this->service->getRecentErrors($project, 50);

        $this->assertInstanceOf(Collection::class, $errors);
    }
}
