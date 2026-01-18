<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\Commands\ScanServerThreatsCommand;
use App\Models\SecurityIncident;
use App\Models\Server;
use App\Services\Security\IncidentResponseService;
use App\Services\Security\ThreatDetectionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @method \Illuminate\Testing\PendingCommand artisan(string $command, array<string, mixed> $parameters = [])
 */
class ScanServerThreatsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        Log::spy();
    }

    // ==================== COMMAND SIGNATURE ====================

    #[Test]
    public function scan_threats_command_has_correct_signature(): void
    {
        $mockThreatService = Mockery::mock(ThreatDetectionService::class);
        $mockResponseService = Mockery::mock(IncidentResponseService::class);

        $command = new ScanServerThreatsCommand;

        $this->assertEquals('security:scan-threats', $command->getName());
        $this->assertStringContainsString('security threats', $command->getDescription());
    }

    #[Test]
    public function command_requires_server_id_or_all_option(): void
    {
        $this->artisan('security:scan-threats')
            ->expectsOutput('Please specify --server-id=<id> or --all to scan all servers')
            ->assertExitCode(1);
    }

    #[Test]
    public function command_handles_no_servers_found(): void
    {
        // Delete all servers to ensure none exist
        Server::query()->forceDelete();

        $this->artisan('security:scan-threats', ['--all' => true])
            ->expectsOutput('No servers found to scan.')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_handles_specific_server_not_found(): void
    {
        $this->artisan('security:scan-threats', ['--server-id' => 99999])
            ->expectsOutput('No servers found to scan.')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_scans_server_with_no_threats(): void
    {
        $server = Server::factory()->create(['status' => 'online']);

        $mockThreatService = Mockery::mock(ThreatDetectionService::class);
        $mockThreatService->shouldReceive('scanServer')
            ->once()
            ->andReturn([
                'threats' => [],
                'scan_time' => 5.0,
            ]);

        $this->app->instance(ThreatDetectionService::class, $mockThreatService);

        $this->artisan('security:scan-threats', ['--server-id' => $server->id])
            ->expectsOutput('Starting security threat scan...')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_scans_server_with_threats_found(): void
    {
        $server = Server::factory()->create(['status' => 'online']);

        $threats = [
            [
                'type' => 'backdoor_user',
                'severity' => 'critical',
                'title' => 'Backdoor user detected',
                'description' => 'User with UID 0 found',
            ],
        ];

        $incident = SecurityIncident::factory()->backdoorUser()->create([
            'server_id' => $server->id,
        ]);

        $mockThreatService = Mockery::mock(ThreatDetectionService::class);
        $mockThreatService->shouldReceive('scanServer')
            ->once()
            ->andReturn([
                'threats' => $threats,
                'scan_time' => 8.0,
            ]);

        $mockThreatService->shouldReceive('createIncidentsFromThreats')
            ->once()
            ->andReturn([$incident]);

        $this->app->instance(ThreatDetectionService::class, $mockThreatService);

        $this->artisan('security:scan-threats', ['--server-id' => $server->id])
            ->expectsOutput('Starting security threat scan...')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_scans_all_online_servers(): void
    {
        // Clean up all servers first
        Server::query()->forceDelete();

        // Create 2 online servers and 1 offline
        Server::factory()->count(2)->create(['status' => 'online']);
        Server::factory()->create(['status' => 'offline']);

        $mockThreatService = Mockery::mock(ThreatDetectionService::class);
        $mockThreatService->shouldReceive('scanServer')
            ->times(2)
            ->andReturn([
                'threats' => [],
                'scan_time' => 3.0,
            ]);

        $this->app->instance(ThreatDetectionService::class, $mockThreatService);

        $this->artisan('security:scan-threats', ['--all' => true])
            ->expectsOutput('Scanning 2 server(s) for threats...')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_handles_scan_error(): void
    {
        $server = Server::factory()->create(['status' => 'online']);

        $mockThreatService = Mockery::mock(ThreatDetectionService::class);
        $mockThreatService->shouldReceive('scanServer')
            ->once()
            ->andThrow(new \Exception('SSH connection failed'));

        $this->app->instance(ThreatDetectionService::class, $mockThreatService);

        $this->artisan('security:scan-threats', ['--server-id' => $server->id])
            ->assertExitCode(0); // Continues despite error
    }

    #[Test]
    public function command_with_auto_remediate_option(): void
    {
        $server = Server::factory()->create(['status' => 'online']);

        $threats = [
            [
                'type' => 'backdoor_user',
                'severity' => 'critical',
                'title' => 'Backdoor user detected',
            ],
        ];

        $incident = SecurityIncident::factory()->critical()->create([
            'server_id' => $server->id,
        ]);

        $mockThreatService = Mockery::mock(ThreatDetectionService::class);
        $mockThreatService->shouldReceive('scanServer')
            ->once()
            ->andReturn([
                'threats' => $threats,
                'scan_time' => 5.0,
            ]);

        $mockThreatService->shouldReceive('createIncidentsFromThreats')
            ->once()
            ->andReturn([$incident]);

        $this->app->instance(ThreatDetectionService::class, $mockThreatService);

        $mockResponseService = Mockery::mock(IncidentResponseService::class);
        $mockResponseService->shouldReceive('autoRemediate')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Remediation completed',
                'actions' => [
                    ['action' => 'kill_process', 'success' => true, 'message' => 'Process killed'],
                ],
            ]);

        $this->app->instance(IncidentResponseService::class, $mockResponseService);

        $this->artisan('security:scan-threats', [
            '--server-id' => $server->id,
            '--auto-remediate' => true,
        ])
            ->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
