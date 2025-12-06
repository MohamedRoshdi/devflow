<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Request;
use Mockery;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AuditService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== LOG METHOD TESTS ====================

    /** @test */
    public function it_logs_action_with_authenticated_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Act
        $log = $this->service->log('project.created', $project);

        // Assert
        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('project.created', $log->action);
        $this->assertEquals(Project::class, $log->auditable_type);
        $this->assertEquals($project->id, $log->auditable_id);
        $this->assertEquals('192.168.1.1', $log->ip_address);
        $this->assertEquals('Mozilla/5.0', $log->user_agent);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'project.created',
            'auditable_type' => Project::class,
            'auditable_id' => $project->id,
        ]);
    }

    /** @test */
    public function it_logs_action_without_authenticated_user(): void
    {
        // Arrange
        $project = Project::factory()->create();

        Request::shouldReceive('ip')->andReturn('127.0.0.1');
        Request::shouldReceive('userAgent')->andReturn('CLI');

        // Act
        $log = $this->service->log('project.deleted', $project);

        // Assert
        $this->assertNull($log->user_id);
        $this->assertEquals('project.deleted', $log->action);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => null,
            'action' => 'project.deleted',
            'auditable_type' => Project::class,
        ]);
    }

    /** @test */
    public function it_logs_action_with_old_and_new_values(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        $oldValues = ['name' => 'Old Name', 'status' => 'inactive'];
        $newValues = ['name' => 'New Name', 'status' => 'active'];

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Act
        $log = $this->service->log('project.updated', $project, $oldValues, $newValues);

        // Assert
        $this->assertEquals($oldValues, $log->old_values);
        $this->assertEquals($newValues, $log->new_values);
    }

    /** @test */
    public function it_sanitizes_sensitive_data_when_logging(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $this->actingAs($user);

        $oldValues = [
            'name' => 'Server 1',
            'password' => 'secret123',
            'ssh_key' => 'private-key-content',
            'api_key' => 'api-key-123',
        ];

        $newValues = [
            'name' => 'Server Updated',
            'password' => 'newsecret456',
            'ssh_key' => 'new-private-key',
            'api_key' => 'new-api-key-456',
        ];

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Act
        $log = $this->service->log('server.updated', $server, $oldValues, $newValues);

        // Assert
        $this->assertEquals('***REDACTED***', $log->old_values['password']);
        $this->assertEquals('***REDACTED***', $log->old_values['ssh_key']);
        $this->assertEquals('***REDACTED***', $log->old_values['api_key']);
        $this->assertEquals('***REDACTED***', $log->new_values['password']);
        $this->assertEquals('***REDACTED***', $log->new_values['ssh_key']);
        $this->assertEquals('***REDACTED***', $log->new_values['api_key']);
        $this->assertEquals('Server 1', $log->old_values['name']);
        $this->assertEquals('Server Updated', $log->new_values['name']);
    }

    /** @test */
    public function it_sanitizes_password_confirmation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $values = [
            'email' => 'test@example.com',
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ];

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Act
        $log = $this->service->log('user.updated', $user, null, $values);

        // Assert
        $this->assertEquals('***REDACTED***', $log->new_values['password']);
        $this->assertEquals('***REDACTED***', $log->new_values['password_confirmation']);
        $this->assertEquals('test@example.com', $log->new_values['email']);
    }

    /** @test */
    public function it_sanitizes_all_sensitive_keys(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        $values = [
            'token' => 'bearer-token',
            'secret' => 'app-secret',
            'webhook_secret' => 'webhook-secret',
            'private_key' => 'private-key',
            'public_data' => 'visible',
        ];

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Act
        $log = $this->service->log('project.updated', $project, null, $values);

        // Assert
        $this->assertEquals('***REDACTED***', $log->new_values['token']);
        $this->assertEquals('***REDACTED***', $log->new_values['secret']);
        $this->assertEquals('***REDACTED***', $log->new_values['webhook_secret']);
        $this->assertEquals('***REDACTED***', $log->new_values['private_key']);
        $this->assertEquals('visible', $log->new_values['public_data']);
    }

    /** @test */
    public function it_handles_null_values_when_logging(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Act
        $log = $this->service->log('project.viewed', $project, null, null);

        // Assert
        $this->assertNull($log->old_values);
        $this->assertNull($log->new_values);
    }

    /** @test */
    public function it_logs_actions_for_different_model_types(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $deployment = Deployment::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Act
        $serverLog = $this->service->log('server.created', $server);
        $deploymentLog = $this->service->log('deployment.started', $deployment);

        // Assert
        $this->assertEquals(Server::class, $serverLog->auditable_type);
        $this->assertEquals(Deployment::class, $deploymentLog->auditable_type);
        $this->assertDatabaseCount('audit_logs', 2);
    }

    // ==================== GET LOGS FOR MODEL TESTS ====================

    /** @test */
    public function it_gets_logs_for_specific_model(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Create logs for specific project
        $this->actingAs($user);
        $this->service->log('project.created', $project);
        $this->service->log('project.updated', $project);
        $this->service->log('project.deployed', $project);

        // Create logs for other project
        $this->service->log('project.created', $otherProject);

        // Act
        $logs = $this->service->getLogsForModel($project);

        // Assert
        $this->assertCount(3, $logs);
        $this->assertTrue($logs->every(fn ($log) => $log->auditable_id === $project->id));
        $this->assertTrue($logs->every(fn ($log) => $log->auditable_type === Project::class));
    }

    /** @test */
    public function it_limits_logs_for_model(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Create 100 logs
        for ($i = 0; $i < 100; $i++) {
            $this->service->log('project.accessed', $project);
        }

        // Act
        $logs = $this->service->getLogsForModel($project, 25);

        // Assert
        $this->assertCount(25, $logs);
    }

    /** @test */
    public function it_returns_logs_with_user_relationship(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Test User']);
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->service->log('project.created', $project);

        // Act
        $logs = $this->service->getLogsForModel($project);

        // Assert
        $this->assertCount(1, $logs);
        $this->assertNotNull($logs->first()->user);
        $this->assertEquals('Test User', $logs->first()->user->name);
    }

    /** @test */
    public function it_returns_logs_in_latest_first_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Create logs with slight delays
        $log1 = $this->service->log('project.created', $project);
        sleep(1);
        $log2 = $this->service->log('project.updated', $project);
        sleep(1);
        $log3 = $this->service->log('project.deployed', $project);

        // Act
        $logs = $this->service->getLogsForModel($project);

        // Assert
        $this->assertEquals($log3->id, $logs[0]->id);
        $this->assertEquals($log2->id, $logs[1]->id);
        $this->assertEquals($log1->id, $logs[2]->id);
    }

    // ==================== GET LOGS BY USER TESTS ====================

    /** @test */
    public function it_gets_logs_by_user(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create();

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Create logs for user1
        $this->actingAs($user1);
        $this->service->log('project.created', $project);
        $this->service->log('project.updated', $project);

        // Create logs for user2
        $this->actingAs($user2);
        $this->service->log('project.deleted', $project);

        // Act
        $user1Logs = $this->service->getLogsByUser($user1);
        $user2Logs = $this->service->getLogsByUser($user2);

        // Assert
        $this->assertCount(2, $user1Logs);
        $this->assertCount(1, $user2Logs);
        $this->assertTrue($user1Logs->every(fn ($log) => $log->user_id === $user1->id));
        $this->assertTrue($user2Logs->every(fn ($log) => $log->user_id === $user2->id));
    }

    /** @test */
    public function it_limits_logs_by_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Create 150 logs
        for ($i = 0; $i < 150; $i++) {
            $this->service->log('project.accessed', $project);
        }

        // Act
        $logs = $this->service->getLogsByUser($user, 50);

        // Assert
        $this->assertCount(50, $logs);
    }

    /** @test */
    public function it_returns_logs_by_user_with_auditable_relationship(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Test Project']);
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->service->log('project.updated', $project);

        // Act
        $logs = $this->service->getLogsByUser($user);

        // Assert
        $this->assertCount(1, $logs);
        $this->assertNotNull($logs->first()->auditable);
        $this->assertEquals('Test Project', $logs->first()->auditable->name);
    }

    // ==================== GET LOGS BY ACTION TESTS ====================

    /** @test */
    public function it_gets_logs_by_action(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->service->log('project.created', $project1);
        $this->service->log('project.created', $project2);
        $this->service->log('project.updated', $project1);
        $this->service->log('project.deleted', $project2);

        // Act
        $createdLogs = $this->service->getLogsByAction('project.created');
        $updatedLogs = $this->service->getLogsByAction('project.updated');

        // Assert
        $this->assertCount(2, $createdLogs);
        $this->assertCount(1, $updatedLogs);
        $this->assertTrue($createdLogs->every(fn ($log) => $log->action === 'project.created'));
        $this->assertTrue($updatedLogs->every(fn ($log) => $log->action === 'project.updated'));
    }

    /** @test */
    public function it_limits_logs_by_action(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Create 75 deployment logs
        for ($i = 0; $i < 75; $i++) {
            $deployment = Deployment::factory()->create();
            $this->service->log('deployment.started', $deployment);
        }

        // Act
        $logs = $this->service->getLogsByAction('deployment.started', 30);

        // Assert
        $this->assertCount(30, $logs);
    }

    /** @test */
    public function it_returns_logs_by_action_with_relationships(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Action User']);
        $project = Project::factory()->create(['name' => 'Action Project']);
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->service->log('project.deployed', $project);

        // Act
        $logs = $this->service->getLogsByAction('project.deployed');

        // Assert
        $this->assertCount(1, $logs);
        $this->assertNotNull($logs->first()->user);
        $this->assertNotNull($logs->first()->auditable);
        $this->assertEquals('Action User', $logs->first()->user->name);
        $this->assertEquals('Action Project', $logs->first()->auditable->name);
    }

    // ==================== GET LOGS FILTERED TESTS ====================

    /** @test */
    public function it_filters_logs_by_user_id(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create();

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->actingAs($user1);
        $this->service->log('project.created', $project);

        $this->actingAs($user2);
        $this->service->log('project.updated', $project);

        // Act
        $logs = $this->service->getLogsFiltered(['user_id' => $user1->id]);

        // Assert
        $this->assertCount(1, $logs);
        $this->assertEquals($user1->id, $logs->first()->user_id);
    }

    /** @test */
    public function it_filters_logs_by_action_with_like(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->service->log('project.created', $project);
        $this->service->log('project.updated', $project);
        $this->service->log('server.created', Server::factory()->create());

        // Act
        $logs = $this->service->getLogsFiltered(['action' => 'created']);

        // Assert
        $this->assertCount(2, $logs);
        $this->assertTrue($logs->every(fn ($log) => str_contains($log->action, 'created')));
    }

    /** @test */
    public function it_filters_logs_by_action_category(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $server = Server::factory()->create();
        $deployment = Deployment::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->service->log('project.created', $project);
        $this->service->log('project.updated', $project);
        $this->service->log('server.created', $server);
        $this->service->log('deployment.started', $deployment);

        // Act
        $projectLogs = $this->service->getLogsFiltered(['action_category' => 'project']);
        $serverLogs = $this->service->getLogsFiltered(['action_category' => 'server']);

        // Assert
        $this->assertCount(2, $projectLogs);
        $this->assertCount(1, $serverLogs);
        $this->assertTrue($projectLogs->every(fn ($log) => str_starts_with($log->action, 'project.')));
        $this->assertTrue($serverLogs->every(fn ($log) => str_starts_with($log->action, 'server.')));
    }

    /** @test */
    public function it_filters_logs_by_model_type(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $server = Server::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->service->log('project.created', $project);
        $this->service->log('project.updated', $project);
        $this->service->log('server.created', $server);

        // Act
        $projectLogs = $this->service->getLogsFiltered(['model_type' => Project::class]);
        $serverLogs = $this->service->getLogsFiltered(['model_type' => Server::class]);

        // Assert
        $this->assertCount(2, $projectLogs);
        $this->assertCount(1, $serverLogs);
        $this->assertTrue($projectLogs->every(fn ($log) => $log->auditable_type === Project::class));
        $this->assertTrue($serverLogs->every(fn ($log) => $log->auditable_type === Server::class));
    }

    /** @test */
    public function it_filters_logs_by_date_range(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Create logs at different times
        $log1 = $this->service->log('project.created', $project);
        $log1->created_at = now()->subDays(5);
        $log1->save();

        $log2 = $this->service->log('project.updated', $project);
        $log2->created_at = now()->subDays(2);
        $log2->save();

        $log3 = $this->service->log('project.deployed', $project);
        $log3->created_at = now();
        $log3->save();

        // Act
        $logs = $this->service->getLogsFiltered([
            'from_date' => now()->subDays(3),
            'to_date' => now(),
        ]);

        // Assert
        $this->assertCount(2, $logs);
        $ids = $logs->pluck('id')->toArray();
        $this->assertContains($log2->id, $ids);
        $this->assertContains($log3->id, $ids);
        $this->assertNotContains($log1->id, $ids);
    }

    /** @test */
    public function it_filters_logs_by_from_date_only(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $oldLog = $this->service->log('project.created', $project);
        $oldLog->created_at = now()->subDays(10);
        $oldLog->save();

        $newLog = $this->service->log('project.updated', $project);

        // Act
        $logs = $this->service->getLogsFiltered(['from_date' => now()->subDays(1)]);

        // Assert
        $this->assertCount(1, $logs);
        $this->assertEquals($newLog->id, $logs->first()->id);
    }

    /** @test */
    public function it_filters_logs_by_to_date_only(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $oldLog = $this->service->log('project.created', $project);
        $oldLog->created_at = now()->subDays(5);
        $oldLog->save();

        $newLog = $this->service->log('project.updated', $project);

        // Act
        $logs = $this->service->getLogsFiltered(['to_date' => now()->subDays(3)]);

        // Assert
        $this->assertCount(1, $logs);
        $this->assertEquals($oldLog->id, $logs->first()->id);
    }

    /** @test */
    public function it_filters_logs_by_ip_address(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.100');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');
        $log1 = $this->service->log('project.created', $project);

        Request::shouldReceive('ip')->andReturn('10.0.0.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');
        $log2 = $this->service->log('project.updated', $project);

        // Act
        $logs = $this->service->getLogsFiltered(['ip_address' => '192.168.1.100']);

        // Assert
        $this->assertCount(1, $logs);
        $this->assertEquals($log1->id, $logs->first()->id);
        $this->assertEquals('192.168.1.100', $logs->first()->ip_address);
    }

    /** @test */
    public function it_applies_custom_limit_when_filtering(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Create 50 logs
        for ($i = 0; $i < 50; $i++) {
            $this->service->log('project.accessed', $project);
        }

        // Act
        $logs = $this->service->getLogsFiltered(['limit' => 15]);

        // Assert
        $this->assertCount(15, $logs);
    }

    /** @test */
    public function it_uses_default_limit_when_not_specified(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Create 150 logs
        for ($i = 0; $i < 150; $i++) {
            $this->service->log('project.accessed', $project);
        }

        // Act
        $logs = $this->service->getLogsFiltered([]);

        // Assert
        $this->assertCount(100, $logs); // Default limit is 100
    }

    /** @test */
    public function it_filters_logs_with_multiple_criteria(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create();
        $server = Server::factory()->create();

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->actingAs($user1);
        $targetLog = $this->service->log('project.created', $project);
        $targetLog->created_at = now()->subDays(1);
        $targetLog->save();

        $this->service->log('project.updated', $project);
        $this->service->log('server.created', $server);

        $this->actingAs($user2);
        $this->service->log('project.created', $project);

        // Act
        $logs = $this->service->getLogsFiltered([
            'user_id' => $user1->id,
            'action_category' => 'project',
            'model_type' => Project::class,
            'from_date' => now()->subDays(2),
        ]);

        // Assert
        $this->assertCount(2, $logs);
        $this->assertTrue($logs->every(fn ($log) => $log->user_id === $user1->id));
        $this->assertTrue($logs->every(fn ($log) => str_starts_with($log->action, 'project.')));
    }

    // ==================== GET ACTIVITY STATS TESTS ====================

    /** @test */
    public function it_calculates_activity_statistics(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create();
        $server = Server::factory()->create();

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->actingAs($user1);
        $this->service->log('project.created', $project);
        $this->service->log('project.updated', $project);
        $this->service->log('server.created', $server);

        $this->actingAs($user2);
        $this->service->log('project.created', $project);
        $this->service->log('project.deployed', $project);

        // Act
        $stats = $this->service->getActivityStats();

        // Assert
        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(2, $stats['by_action']['project.created']);
        $this->assertEquals(1, $stats['by_action']['project.updated']);
        $this->assertEquals(1, $stats['by_action']['server.created']);
        $this->assertEquals(1, $stats['by_action']['project.deployed']);
        $this->assertEquals(3, $stats['by_user'][$user1->id]);
        $this->assertEquals(2, $stats['by_user'][$user2->id]);
        $this->assertEquals(4, $stats['by_model_type'][Project::class]);
        $this->assertEquals(1, $stats['by_model_type'][Server::class]);
    }

    /** @test */
    public function it_calculates_stats_with_date_range_filter(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Old logs
        $oldLog1 = $this->service->log('project.created', $project);
        $oldLog1->created_at = now()->subDays(10);
        $oldLog1->save();

        $oldLog2 = $this->service->log('project.updated', $project);
        $oldLog2->created_at = now()->subDays(8);
        $oldLog2->save();

        // Recent logs
        $this->service->log('project.deployed', $project);
        $this->service->log('project.updated', $project);

        // Act
        $stats = $this->service->getActivityStats([
            'from_date' => now()->subDays(5),
        ]);

        // Assert
        $this->assertEquals(2, $stats['total']);
        $this->assertArrayHasKey('project.deployed', $stats['by_action']);
        $this->assertArrayHasKey('project.updated', $stats['by_action']);
    }

    /** @test */
    public function it_returns_empty_stats_when_no_logs(): void
    {
        // Act
        $stats = $this->service->getActivityStats();

        // Assert
        $this->assertEquals(0, $stats['total']);
        $this->assertEmpty($stats['by_action']);
        $this->assertEmpty($stats['by_user']);
        $this->assertEmpty($stats['by_model_type']);
    }

    /** @test */
    public function it_excludes_null_users_from_user_stats(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('CLI');

        // Log without user
        $this->service->log('project.created', $project);

        // Log with user
        $this->actingAs($user);
        $this->service->log('project.updated', $project);

        // Act
        $stats = $this->service->getActivityStats();

        // Assert
        $this->assertEquals(2, $stats['total']);
        $this->assertCount(1, $stats['by_user']); // Only user1, not null
        $this->assertEquals(1, $stats['by_user'][$user->id]);
    }

    // ==================== EXPORT TO CSV TESTS ====================

    /** @test */
    public function it_exports_logs_to_csv(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'John Doe']);
        $project = Project::factory()->create(['name' => 'Test Project']);
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->service->log('project.created', $project);

        // Act
        $csv = $this->service->exportToCsv();

        // Assert
        $this->assertStringContainsString('ID,User,Action,Model,Model ID,IP Address,Date,Changes', $csv);
        $this->assertStringContainsString('John Doe', $csv);
        $this->assertStringContainsString('project.created', $csv);
        $this->assertStringContainsString('Project', $csv);
        $this->assertStringContainsString('192.168.1.1', $csv);
    }

    /** @test */
    public function it_exports_logs_with_system_user_when_no_user(): void
    {
        // Arrange
        $project = Project::factory()->create();

        Request::shouldReceive('ip')->andReturn('127.0.0.1');
        Request::shouldReceive('userAgent')->andReturn('CLI');

        $this->service->log('project.created', $project);

        // Act
        $csv = $this->service->exportToCsv();

        // Assert
        $this->assertStringContainsString('System', $csv);
    }

    /** @test */
    public function it_exports_logs_with_na_for_null_ip(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn(null);
        Request::shouldReceive('userAgent')->andReturn('CLI');

        $this->service->log('project.created', $project);

        // Act
        $csv = $this->service->exportToCsv();

        // Assert
        $this->assertStringContainsString('N/A', $csv);
    }

    /** @test */
    public function it_exports_filtered_logs_to_csv(): void
    {
        // Arrange
        $user1 = User::factory()->create(['name' => 'User One']);
        $user2 = User::factory()->create(['name' => 'User Two']);
        $project = Project::factory()->create();

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->actingAs($user1);
        $this->service->log('project.created', $project);

        $this->actingAs($user2);
        $this->service->log('project.updated', $project);

        // Act
        $csv = $this->service->exportToCsv(['user_id' => $user1->id]);

        // Assert
        $this->assertStringContainsString('User One', $csv);
        $this->assertStringNotContainsString('User Two', $csv);
        $this->assertStringContainsString('project.created', $csv);
        $this->assertStringNotContainsString('project.updated', $csv);
    }

    /** @test */
    public function it_exports_multiple_logs_to_csv(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Test User']);
        $project1 = Project::factory()->create(['name' => 'Project 1']);
        $project2 = Project::factory()->create(['name' => 'Project 2']);
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $this->service->log('project.created', $project1);
        $this->service->log('project.created', $project2);

        // Act
        $csv = $this->service->exportToCsv();
        $lines = explode("\n", trim($csv));

        // Assert
        $this->assertCount(3, $lines); // Header + 2 data rows
        $this->assertStringContainsString('Project 1', $csv);
        $this->assertStringContainsString('Project 2', $csv);
    }

    /** @test */
    public function it_exports_csv_with_changes_summary(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        $oldValues = ['name' => 'Old Name', 'status' => 'inactive'];
        $newValues = ['name' => 'New Name', 'status' => 'active'];

        $this->service->log('project.updated', $project, $oldValues, $newValues);

        // Act
        $csv = $this->service->exportToCsv();

        // Assert
        $this->assertStringContainsString('project.updated', $csv);
        // Changes summary is JSON encoded in CSV
        $this->assertStringContainsString('name', $csv);
    }

    /** @test */
    public function it_returns_empty_csv_when_no_logs(): void
    {
        // Act
        $csv = $this->service->exportToCsv();

        // Assert
        $lines = explode("\n", trim($csv));
        $this->assertCount(1, $lines); // Only header
        $this->assertStringStartsWith('ID,User,Action,Model,Model ID,IP Address,Date,Changes', $csv);
    }
}
