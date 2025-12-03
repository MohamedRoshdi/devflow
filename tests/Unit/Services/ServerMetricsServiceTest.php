<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\{CreatesServers, MocksSSH};
use App\Services\ServerMetricsService;
use App\Models\{Server, ServerMetric};
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServerMetricsServiceTest extends TestCase
{
    use RefreshDatabase, CreatesServers, MocksSSH;

    protected ServerMetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ServerMetricsService();
    }

    /** @test */
    public function it_collects_metrics_from_online_server(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockServerMetrics();

        // Act
        $metric = $this->service->collectMetrics($server);

        // Assert
        $this->assertInstanceOf(ServerMetric::class, $metric);
        $this->assertEquals($server->id, $metric->server_id);
        $this->assertNotNull($metric->cpu_usage);
        $this->assertNotNull($metric->memory_usage);
        $this->assertNotNull($metric->disk_usage);
    }

    /** @test */
    public function it_returns_null_on_metrics_collection_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockSshFailure();

        // Act
        $metric = $this->service->collectMetrics($server);

        // Assert
        $this->assertNull($metric);
    }

    /** @test */
    public function it_retrieves_metrics_history(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        ServerMetric::factory()->count(5)->create([
            'server_id' => $server->id,
            'recorded_at' => now()->subHours(2),
        ]);

        // Act
        $history = $this->service->getMetricsHistory($server, '24h');

        // Assert
        $this->assertCount(5, $history);
    }

    /** @test */
    public function it_filters_metrics_history_by_period(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        // Create metrics outside the period
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'recorded_at' => now()->subDays(2),
        ]);

        // Create metrics within the period
        ServerMetric::factory()->count(3)->create([
            'server_id' => $server->id,
            'recorded_at' => now()->subMinutes(30),
        ]);

        // Act
        $history = $this->service->getMetricsHistory($server, '1h');

        // Assert
        $this->assertCount(3, $history);
    }

    /** @test */
    public function it_gets_latest_metrics(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'recorded_at' => now()->subHours(2),
            'cpu_usage' => 50.0,
        ]);

        $latestMetric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'recorded_at' => now(),
            'cpu_usage' => 75.0,
        ]);

        // Act
        $result = $this->service->getLatestMetrics($server);

        // Assert
        $this->assertEquals($latestMetric->id, $result->id);
        $this->assertEquals(75.0, $result->cpu_usage);
    }

    /** @test */
    public function it_sanitizes_decimal_values(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockServerMetrics([
            'cpu' => '150.5', // Over 100
            'memory' => '-10.0 2048 4096', // Negative
        ]);

        // Act
        $metric = $this->service->collectMetrics($server);

        // Assert
        $this->assertEquals(100.0, $metric->cpu_usage); // Clamped to 100
        $this->assertEquals(0.0, $metric->memory_usage); // Clamped to 0
    }

    /** @test */
    public function it_parses_process_output_correctly(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        \Illuminate\Support\Facades\Process::fake([
            '*ps aux*' => \Illuminate\Support\Facades\Process::result(
                output: "USER       PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND\n" .
                        "root         1  0.5  0.2 169564 10952 ?        Ss   Jan01   1:23 /sbin/init\n" .
                        "www-data  1234 15.0  5.5 456789 123456 ?       Ssl  10:00   0:45 php-fpm: pool www"
            ),
        ]);

        // Act
        $processes = $this->service->getTopProcessesByCPU($server, 10);

        // Assert
        $this->assertCount(2, $processes);
        $this->assertEquals('www-data', $processes[0]['user']);
        $this->assertEquals(15.0, $processes[0]['cpu']);
        $this->assertEquals(5.5, $processes[0]['mem']);
    }

    /** @test */
    public function it_gets_top_processes_by_memory(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        \Illuminate\Support\Facades\Process::fake([
            '*ps aux*' => \Illuminate\Support\Facades\Process::result(
                output: "USER       PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND\n" .
                        "mysql      500  5.0 25.0 123456 678910 ?       Ssl  Jan01  10:00 mysqld\n" .
                        "www-data  1234  2.0  8.5 456789 123456 ?       Ssl  10:00   0:45 php-fpm"
            ),
        ]);

        // Act
        $processes = $this->service->getTopProcessesByMemory($server, 10);

        // Assert
        $this->assertCount(2, $processes);
        $this->assertEquals('mysql', $processes[0]['user']);
        $this->assertEquals(25.0, $processes[0]['mem']);
    }

    /** @test */
    public function it_handles_localhost_detection(): void
    {
        // Arrange
        $localServer = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $this->mockServerMetrics();

        // Act
        $metric = $this->service->collectMetrics($localServer);

        // Assert
        $this->assertInstanceOf(ServerMetric::class, $metric);
    }

    /** @test */
    public function it_truncates_long_command_strings(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $longCommand = str_repeat('a', 200);

        \Illuminate\Support\Facades\Process::fake([
            '*ps aux*' => \Illuminate\Support\Facades\Process::result(
                output: "USER PID %CPU %MEM VSZ RSS TTY STAT START TIME COMMAND\nroot 1 0.1 0.1 1000 500 ? Ss Jan01 0:00 {$longCommand}"
            ),
        ]);

        // Act
        $processes = $this->service->getTopProcessesByCPU($server, 10);

        // Assert
        $this->assertCount(1, $processes);
        $this->assertLessThanOrEqual(80, strlen($processes[0]['command']));
        $this->assertEquals(200, strlen($processes[0]['full_command']));
    }
}
