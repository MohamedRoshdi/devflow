<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Server;
use App\Services\MetricsCollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class MetricsCollectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private MetricsCollectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MetricsCollectionService();
    }

    // ==========================================
    // SERVER METRICS COLLECTION TESTS
    // ==========================================

    /** @test */
    public function it_collects_server_metrics(): void
    {
        $server = Server::factory()->create([
            'ip_address' => '192.168.1.100',
            'username' => 'root',
            'port' => 22,
        ]);

        Process::fake([
            '*top*' => Process::result(output: '25.5'),
            '*free*' => Process::result(output: 'Mem:     16000    8000    8000       0     100   14000'),
            '*df*' => Process::result(output: '/dev/sda1       100G   45G   55G  45% /'),
            '*proc/net/dev*' => Process::result(output: '1000000 2000000 500 1000'),
            '*uptime*load average*' => Process::result(output: '1.5 1.2 0.9'),
            '*uptime -p*' => Process::result(output: 'up 5 days, 3 hours'),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('cpu', $metrics);
        $this->assertArrayHasKey('memory', $metrics);
        $this->assertArrayHasKey('disk', $metrics);
        $this->assertArrayHasKey('network', $metrics);
        $this->assertArrayHasKey('load', $metrics);
        $this->assertArrayHasKey('uptime', $metrics);
    }

    /** @test */
    public function it_collects_metrics_from_all_online_servers(): void
    {
        Server::factory()->count(3)->create(['status' => 'online']);

        Process::fake([
            '*' => Process::result(output: '0'),
        ]);

        $metrics = $this->service->collectAllServerMetrics();

        $this->assertIsArray($metrics);
        $this->assertCount(3, $metrics);
    }

    // ==========================================
    // CPU USAGE TESTS
    // ==========================================

    /** @test */
    public function it_gets_cpu_usage_using_top(): void
    {
        $server = Server::factory()->create();

        Process::fake([
            '*top*' => Process::result(output: '35.2'),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertEquals(35.2, $metrics['cpu']);
    }

    /** @test */
    public function it_falls_back_to_mpstat_for_cpu(): void
    {
        Log::shouldReceive('warning')->times(5); // For other failed metrics

        $server = Server::factory()->create();

        Process::fake([
            '*top*' => Process::result(output: '', exitCode: 1),
            '*mpstat*' => Process::result(output: '28.5'),
            '*free*' => Process::result(output: '', exitCode: 1),
            '*df*' => Process::result(output: '', exitCode: 1),
            '*proc/net/dev*' => Process::result(output: '', exitCode: 1),
            '*uptime*load*' => Process::result(output: '', exitCode: 1),
            '*uptime -p*' => Process::result(output: '', exitCode: 1),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertEquals(28.5, $metrics['cpu']);
    }

    /** @test */
    public function it_handles_cpu_collection_failure(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $server = Server::factory()->create();

        Process::fake([
            '*top*' => Process::result(output: '', exitCode: 1),
            '*mpstat*' => Process::result(output: '', exitCode: 1),
            '*' => Process::result(output: '', exitCode: 1),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertNull($metrics['cpu']);
    }

    // ==========================================
    // MEMORY USAGE TESTS
    // ==========================================

    /** @test */
    public function it_gets_memory_usage(): void
    {
        $server = Server::factory()->create();

        Process::fake([
            '*top*' => Process::result(output: '0'),
            '*free*' => Process::result(output: 'Mem:     16000    12000    4000       0     100   14000'),
            '*df*' => Process::result(output: ''),
            '*proc/net/dev*' => Process::result(output: ''),
            '*uptime*load*' => Process::result(output: ''),
            '*uptime -p*' => Process::result(output: ''),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertIsArray($metrics['memory']);
        $this->assertEquals(75.0, $metrics['memory']['usage_percent']);
        $this->assertEquals(12000, $metrics['memory']['used_mb']);
        $this->assertEquals(16000, $metrics['memory']['total_mb']);
        $this->assertEquals(4000, $metrics['memory']['free_mb']);
    }

    /** @test */
    public function it_handles_memory_collection_failure(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $server = Server::factory()->create();

        Process::fake([
            '*free*' => Process::result(output: '', exitCode: 1),
            '*' => Process::result(output: '', exitCode: 1),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertNull($metrics['memory']['usage_percent']);
        $this->assertNull($metrics['memory']['used_mb']);
    }

    // ==========================================
    // DISK USAGE TESTS
    // ==========================================

    /** @test */
    public function it_gets_disk_usage(): void
    {
        $server = Server::factory()->create();

        Process::fake([
            '*top*' => Process::result(output: '0'),
            '*free*' => Process::result(output: ''),
            '*df*' => Process::result(output: '/dev/sda1       500G   350G   150G  70% /'),
            '*proc/net/dev*' => Process::result(output: ''),
            '*uptime*load*' => Process::result(output: ''),
            '*uptime -p*' => Process::result(output: ''),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertIsArray($metrics['disk']);
        $this->assertEquals(70.0, $metrics['disk']['usage_percent']);
        $this->assertEquals(350.0, $metrics['disk']['used_gb']);
        $this->assertEquals(500.0, $metrics['disk']['total_gb']);
        $this->assertEquals(150.0, $metrics['disk']['free_gb']);
        $this->assertEquals('/', $metrics['disk']['mount_point']);
    }

    /** @test */
    public function it_handles_disk_collection_failure(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $server = Server::factory()->create();

        Process::fake([
            '*df*' => Process::result(output: '', exitCode: 1),
            '*' => Process::result(output: '', exitCode: 1),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertNull($metrics['disk']['usage_percent']);
    }

    // ==========================================
    // NETWORK STATS TESTS
    // ==========================================

    /** @test */
    public function it_gets_network_statistics(): void
    {
        $server = Server::factory()->create();

        Process::fake([
            '*top*' => Process::result(output: '0'),
            '*free*' => Process::result(output: ''),
            '*df*' => Process::result(output: ''),
            '*proc/net/dev*' => Process::result(output: '1000000 2000000 500 1000'),
            '*uptime*load*' => Process::result(output: ''),
            '*uptime -p*' => Process::result(output: ''),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertIsArray($metrics['network']);
        $this->assertEquals(1000000, $metrics['network']['in_bytes']);
        $this->assertEquals(2000000, $metrics['network']['out_bytes']);
        $this->assertEquals(500, $metrics['network']['in_packets']);
        $this->assertEquals(1000, $metrics['network']['out_packets']);
    }

    /** @test */
    public function it_handles_network_collection_failure(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $server = Server::factory()->create();

        Process::fake([
            '*proc/net/dev*' => Process::result(output: '', exitCode: 1),
            '*' => Process::result(output: '', exitCode: 1),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertNull($metrics['network']['in_bytes']);
    }

    // ==========================================
    // LOAD AVERAGE TESTS
    // ==========================================

    /** @test */
    public function it_gets_load_average(): void
    {
        $server = Server::factory()->create();

        Process::fake([
            '*top*' => Process::result(output: '0'),
            '*free*' => Process::result(output: ''),
            '*df*' => Process::result(output: ''),
            '*proc/net/dev*' => Process::result(output: ''),
            '*uptime*load average*' => Process::result(output: '1.5 1.2 0.9'),
            '*uptime -p*' => Process::result(output: ''),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertIsArray($metrics['load']);
        $this->assertEquals(1.5, $metrics['load']['load_1']);
        $this->assertEquals(1.2, $metrics['load']['load_5']);
        $this->assertEquals(0.9, $metrics['load']['load_15']);
    }

    /** @test */
    public function it_handles_load_average_failure(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $server = Server::factory()->create();

        Process::fake([
            '*uptime*load*' => Process::result(output: '', exitCode: 1),
            '*' => Process::result(output: '', exitCode: 1),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertNull($metrics['load']['load_1']);
    }

    // ==========================================
    // UPTIME TESTS
    // ==========================================

    /** @test */
    public function it_gets_server_uptime(): void
    {
        $server = Server::factory()->create();

        Process::fake([
            '*top*' => Process::result(output: '0'),
            '*free*' => Process::result(output: ''),
            '*df*' => Process::result(output: ''),
            '*proc/net/dev*' => Process::result(output: ''),
            '*uptime*load*' => Process::result(output: ''),
            '*uptime -p*' => Process::result(output: 'up 5 days, 3 hours'),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertEquals('up 5 days, 3 hours', $metrics['uptime']);
    }

    /** @test */
    public function it_falls_back_to_basic_uptime(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $server = Server::factory()->create();

        Process::fake([
            '*uptime -p*' => Process::result(output: '', exitCode: 1),
            '*uptime*' => Process::result(output: '12:00:00 up 5 days'),
            '*' => Process::result(output: '', exitCode: 1),
        ]);

        $metrics = $this->service->collectServerMetrics($server);

        $this->assertIsString($metrics['uptime']);
    }

    // ==========================================
    // SERVER HEALTH METRICS TESTS
    // ==========================================

    /** @test */
    public function it_determines_healthy_server_status(): void
    {
        $server = Server::factory()->create();

        Process::fake([
            '*top*' => Process::result(output: '50.0'),
            '*free*' => Process::result(output: 'Mem:     16000    8000    8000       0     100   14000'),
            '*df*' => Process::result(output: '/dev/sda1       100G   40G   60G  40% /'),
            '*proc/net/dev*' => Process::result(output: ''),
            '*uptime*load*' => Process::result(output: '0.5 0.3 0.2'),
            '*uptime -p*' => Process::result(output: ''),
        ]);

        $health = $this->service->getServerHealthMetrics($server);

        $this->assertEquals('healthy', $health['status']);
        $this->assertEquals(50.0, $health['cpu']);
        $this->assertEquals(50.0, $health['memory']);
        $this->assertEquals(40.0, $health['disk']);
    }

    /** @test */
    public function it_detects_warning_server_status(): void
    {
        $server = Server::factory()->create();

        Process::fake([
            '*top*' => Process::result(output: '80.0'),
            '*free*' => Process::result(output: 'Mem:     16000    12800    3200       0     100   14000'),
            '*df*' => Process::result(output: '/dev/sda1       100G   78G   22G  78% /'),
            '*proc/net/dev*' => Process::result(output: ''),
            '*uptime*load*' => Process::result(output: '2.0 1.8 1.5'),
            '*uptime -p*' => Process::result(output: ''),
        ]);

        $health = $this->service->getServerHealthMetrics($server);

        $this->assertEquals('warning', $health['status']);
    }

    /** @test */
    public function it_detects_critical_server_status(): void
    {
        $server = Server::factory()->create();

        Process::fake([
            '*top*' => Process::result(output: '95.0'),
            '*free*' => Process::result(output: 'Mem:     16000    15000    1000       0     100   14000'),
            '*df*' => Process::result(output: '/dev/sda1       100G   92G   8G  92% /'),
            '*proc/net/dev*' => Process::result(output: ''),
            '*uptime*load*' => Process::result(output: '5.0 4.5 4.0'),
            '*uptime -p*' => Process::result(output: ''),
        ]);

        $health = $this->service->getServerHealthMetrics($server);

        $this->assertEquals('critical', $health['status']);
    }

    // ==========================================
    // FORMATTED METRICS TESTS
    // ==========================================

    /** @test */
    public function it_formats_metrics_for_dashboard(): void
    {
        Server::factory()->count(2)->create(['status' => 'online']);

        Process::fake([
            '*top*' => Process::result(output: '25.0'),
            '*free*' => Process::result(output: 'Mem:     16000    8000    8000       0     100   14000'),
            '*df*' => Process::result(output: '/dev/sda1       100G   45G   55G  45% /'),
            '*proc/net/dev*' => Process::result(output: '1000 2000 500 1000'),
            '*uptime*load*' => Process::result(output: '1.0 0.8 0.6'),
            '*uptime -p*' => Process::result(output: 'up 1 day'),
        ]);

        $formattedMetrics = $this->service->getFormattedMetricsForDashboard();

        $this->assertIsArray($formattedMetrics);
        $this->assertCount(2, $formattedMetrics);

        foreach ($formattedMetrics as $metric) {
            $this->assertArrayHasKey('server_id', $metric);
            $this->assertArrayHasKey('server_name', $metric);
            $this->assertArrayHasKey('cpu_usage', $metric);
            $this->assertArrayHasKey('memory_usage', $metric);
            $this->assertArrayHasKey('disk_usage', $metric);
            $this->assertArrayHasKey('load_average_1', $metric);
            $this->assertArrayHasKey('recorded_at', $metric);
        }
    }
}
