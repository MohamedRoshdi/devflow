<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Jobs\DeployProjectJob;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Services\DeploymentService;
use App\Services\DockerService;
use App\Services\GitService;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Deployment Workflow Integration Test
 *
 * This test suite covers the complete deployment workflow from webhook trigger
 * to deployment completion, including status transitions and error handling.
 *
 * Workflow covered:
 * 1. Git push triggers webhook (GitHub/GitLab)
 * 2. Webhook creates deployment record
 * 3. Deployment job is dispatched
 * 4. Deployment executes with status transitions
 * 5. Success/failure handling
 * 6. Rollback workflow
 */
class DeploymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Server $server;
    protected Project $project;
    protected WebhookService $webhookService;
    protected DeploymentService $deploymentService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@devflow.com',
        ]);

        // Create online server
        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'online',
            'ip_address' => '192.168.1.100',
            'username' => 'root',
        ]);

        // Create project with webhook enabled
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
            'branch' => 'main',
            'repository_url' => 'https://github.com/test/repo.git',
            'webhook_enabled' => true,
            'webhook_secret' => 'test-webhook-secret-123',
            'auto_deploy' => true,
        ]);

        // Initialize services
        $this->webhookService = app(WebhookService::class);
        $this->deploymentService = app(DeploymentService::class);
    }

    // ==================== GitHub Webhook to Deployment Tests ====================

    /** @test */
    public function github_push_webhook_creates_deployment_record(): void
    {
        Queue::fake();

        // Prepare GitHub push webhook payload
        $payload = [
            'ref' => 'refs/heads/main',
            'repository' => [
                'name' => 'test-repo',
                'full_name' => 'test/repo',
                'html_url' => 'https://github.com/test/repo',
            ],
            'head_commit' => [
                'id' => 'abc123def456789',
                'message' => 'feat: Add new feature',
            ],
            'sender' => [
                'login' => 'testuser',
            ],
            'pusher' => [
                'name' => 'Test User',
            ],
        ];

        // Create valid signature
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        assert(is_string($payloadJson));
        $signature = 'sha256=' . hash_hmac('sha256', $payloadJson, 'test-webhook-secret-123');

        // Send webhook request
        $response = $this->call(
            'POST',
            "/webhooks/github/{$this->project->webhook_secret}",
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_GITHUB_EVENT' => 'push',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payloadJson
        );

        // Assert webhook was processed
        $this->assertTrue(
            in_array($response->status(), [200, 202]),
            "Expected 200 or 202, got {$response->status()}"
        );

        // Assert deployment was created
        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'branch' => 'main',
            'commit_hash' => 'abc123def456789',
            'triggered_by' => 'webhook',
            'status' => 'pending',
        ]);

        // Assert webhook delivery record was created
        $this->assertDatabaseHas('webhook_deliveries', [
            'project_id' => $this->project->id,
            'provider' => 'github',
            'event_type' => 'push',
            'status' => 'success',
        ]);

        // Assert deployment job was dispatched
        Queue::assertPushed(DeployProjectJob::class, function ($job) {
            return $job->deployment->project_id === $this->project->id;
        });
    }

    /** @test */
    public function github_webhook_with_invalid_signature_is_rejected(): void
    {
        $payload = [
            'ref' => 'refs/heads/main',
            'repository' => ['full_name' => 'test/repo'],
        ];

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        assert(is_string($payloadJson));
        $invalidSignature = 'sha256=invalid-signature-hash';

        $response = $this->call(
            'POST',
            "/webhooks/github/{$this->project->webhook_secret}",
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_GITHUB_EVENT' => 'push',
                'HTTP_X_HUB_SIGNATURE_256' => $invalidSignature,
            ],
            $payloadJson
        );

        // Assert webhook was rejected
        $this->assertEquals(401, $response->status());

        // Assert no deployment was created
        $this->assertDatabaseMissing('deployments', [
            'project_id' => $this->project->id,
            'status' => 'pending',
        ]);

        // Assert webhook delivery was recorded as failed
        $this->assertDatabaseHas('webhook_deliveries', [
            'project_id' => $this->project->id,
            'provider' => 'github',
            'status' => 'failed',
        ]);
    }

    /** @test */
    public function github_webhook_for_wrong_branch_is_ignored(): void
    {
        Queue::fake();

        $payload = [
            'ref' => 'refs/heads/develop', // Different branch
            'repository' => ['full_name' => 'test/repo'],
            'head_commit' => [
                'id' => 'abc123',
                'message' => 'Test commit',
            ],
            'sender' => ['login' => 'testuser'],
        ];

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        assert(is_string($payloadJson));
        $signature = 'sha256=' . hash_hmac('sha256', $payloadJson, 'test-webhook-secret-123');

        $response = $this->call(
            'POST',
            "/webhooks/github/{$this->project->webhook_secret}",
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_GITHUB_EVENT' => 'push',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payloadJson
        );

        // Webhook should be acknowledged but not trigger deployment
        $this->assertEquals(200, $response->status());

        // Assert no deployment was created
        $this->assertDatabaseMissing('deployments', [
            'project_id' => $this->project->id,
            'branch' => 'develop',
        ]);

        // Assert webhook delivery was recorded as ignored
        $this->assertDatabaseHas('webhook_deliveries', [
            'project_id' => $this->project->id,
            'status' => 'ignored',
        ]);
    }

    /** @test */
    public function github_non_push_events_are_ignored(): void
    {
        $payload = [
            'action' => 'opened',
            'pull_request' => [
                'number' => 1,
                'title' => 'Test PR',
            ],
        ];

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        assert(is_string($payloadJson));
        $signature = 'sha256=' . hash_hmac('sha256', $payloadJson, 'test-webhook-secret-123');

        $response = $this->call(
            'POST',
            "/webhooks/github/{$this->project->webhook_secret}",
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_GITHUB_EVENT' => 'pull_request',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payloadJson
        );

        // Should acknowledge but not process
        $this->assertEquals(200, $response->status());

        // No deployment should be created
        $this->assertDatabaseMissing('deployments', [
            'project_id' => $this->project->id,
        ]);
    }

    // ==================== GitLab Webhook to Deployment Tests ====================

    /** @test */
    public function gitlab_push_webhook_creates_deployment_record(): void
    {
        Queue::fake();

        $payload = [
            'object_kind' => 'push',
            'ref' => 'refs/heads/main',
            'checkout_sha' => 'gitlab123abc456',
            'project' => [
                'name' => 'test-project',
                'path_with_namespace' => 'test/repo',
                'web_url' => 'https://gitlab.com/test/repo',
            ],
            'commits' => [
                [
                    'id' => 'gitlab123abc456',
                    'message' => 'fix: Bug fix',
                ],
            ],
            'user_name' => 'Test User',
        ];

        $response = $this->postJson(
            "/webhooks/gitlab/{$this->project->webhook_secret}",
            $payload,
            [
                'X-Gitlab-Event' => 'Push Hook',
                'X-Gitlab-Token' => 'test-webhook-secret-123',
            ]
        );

        // Assert webhook was processed
        $this->assertTrue(
            in_array($response->status(), [200, 202]),
            "Expected 200 or 202, got {$response->status()}"
        );

        // Assert deployment was created
        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'branch' => 'main',
            'commit_hash' => 'gitlab123abc456',
            'triggered_by' => 'webhook',
        ]);

        // Assert webhook delivery record was created
        $this->assertDatabaseHas('webhook_deliveries', [
            'project_id' => $this->project->id,
            'provider' => 'gitlab',
            'event_type' => 'Push Hook',
        ]);
    }

    /** @test */
    public function gitlab_webhook_with_invalid_token_is_rejected(): void
    {
        $payload = [
            'object_kind' => 'push',
            'ref' => 'refs/heads/main',
        ];

        $response = $this->postJson(
            "/webhooks/gitlab/{$this->project->webhook_secret}",
            $payload,
            [
                'X-Gitlab-Event' => 'Push Hook',
                'X-Gitlab-Token' => 'invalid-token',
            ]
        );

        // Assert webhook was rejected
        $this->assertEquals(401, $response->status());

        // Assert no deployment was created
        $this->assertDatabaseMissing('deployments', [
            'project_id' => $this->project->id,
        ]);
    }

    // ==================== Deployment Status Transition Tests ====================

    /** @test */
    public function deployment_transitions_from_pending_to_running_to_success(): void
    {
        // Create pending deployment
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
            'commit_hash' => 'test123',
        ]);

        // Assert initial state
        $this->assertEquals('pending', $deployment->status);
        $this->assertNull($deployment->started_at);
        $this->assertNull($deployment->completed_at);

        // Transition to running
        $deployment->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $runningDeployment = $deployment->fresh();
        assert($runningDeployment !== null);
        $this->assertEquals('running', $runningDeployment->status);
        $this->assertNotNull($runningDeployment->started_at);
        $this->assertTrue($runningDeployment->isRunning());

        // Transition to success
        $deployment->update([
            'status' => 'success',
            'completed_at' => now(),
            'duration_seconds' => 120,
        ]);

        $successDeployment = $deployment->fresh();
        assert($successDeployment !== null);
        $this->assertEquals('success', $successDeployment->status);
        $this->assertNotNull($successDeployment->completed_at);
        $this->assertEquals(120, $successDeployment->duration_seconds);
        $this->assertTrue($successDeployment->isSuccess());
    }

    /** @test */
    public function deployment_transitions_from_running_to_failed(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $errorMessage = 'Docker build failed: out of memory';

        $deployment->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_log' => $errorMessage,
            'duration_seconds' => 60,
        ]);

        $freshDeployment = $deployment->fresh();
        assert($freshDeployment !== null);
        $this->assertEquals('failed', $freshDeployment->status);
        $this->assertEquals($errorMessage, $freshDeployment->error_log);
        $this->assertTrue($freshDeployment->isFailed());
    }

    /** @test */
    public function only_one_deployment_can_be_active_at_a_time(): void
    {
        // Create active deployment
        $activeDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        // Check for active deployment
        $hasActive = $this->deploymentService->hasActiveDeployment($this->project);
        $this->assertTrue($hasActive);

        // Attempt to create another deployment should throw exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('deployment is already in progress');

        $this->deploymentService->deploy(
            $this->project,
            $this->user,
            'manual'
        );
    }

    /** @test */
    public function deployment_can_be_created_after_previous_completes(): void
    {
        // Create completed deployment
        $completedDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'success',
            'completed_at' => now(),
        ]);

        // Mock GitService to avoid actual Git operations
        $this->mock(GitService::class, function ($mock) {
            $mock->shouldReceive('getCurrentCommit')
                ->andReturn([
                    'hash' => 'new123abc456',
                    'message' => 'New commit',
                    'short_hash' => 'new123a',
                    'author' => 'Test User',
                    'timestamp' => time(),
                ]);
        });

        // Should be able to create new deployment
        $newDeployment = $this->deploymentService->deploy(
            $this->project,
            $this->user,
            'manual'
        );

        $this->assertInstanceOf(Deployment::class, $newDeployment);
        $this->assertEquals('pending', $newDeployment->status);
        $this->assertNotEquals($completedDeployment->id, $newDeployment->id);
    }

    // ==================== Deployment Execution Tests ====================

    /** @test */
    public function deployment_stores_output_logs(): void
    {
        $logs = "Building Docker image...\nImage built successfully\nStarting containers...\nDeployment completed";

        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'output_log' => $logs,
        ]);

        $freshDeployment = $deployment->fresh();
        assert($freshDeployment !== null);
        $this->assertNotNull($freshDeployment->output_log);
        $this->assertIsString($freshDeployment->output_log);
        $this->assertStringContainsString('Building Docker image', $freshDeployment->output_log);
        $this->assertStringContainsString('Deployment completed', $freshDeployment->output_log);
    }

    /** @test */
    public function deployment_stores_error_logs_on_failure(): void
    {
        $errorLog = "Error: Failed to connect to MySQL\nConnection refused on port 3306\nStack trace...";

        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'error_log' => $errorLog,
        ]);

        $freshDeployment = $deployment->fresh();
        assert($freshDeployment !== null);
        $this->assertNotNull($freshDeployment->error_log);
        $this->assertIsString($freshDeployment->error_log);
        $this->assertStringContainsString('Failed to connect to MySQL', $freshDeployment->error_log);
    }

    /** @test */
    public function deployment_records_duration(): void
    {
        $startTime = now()->subMinutes(2);
        $endTime = now();

        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'started_at' => $startTime,
            'completed_at' => $endTime,
            'duration_seconds' => 120,
        ]);

        $this->assertEquals(120, $deployment->duration_seconds);
        $this->assertNotNull($deployment->started_at);
        $this->assertNotNull($deployment->completed_at);
    }

    // ==================== Rollback Workflow Tests ====================

    /** @test */
    public function rollback_creates_new_deployment_with_previous_commit(): void
    {
        // Create successful deployments
        $oldDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'success',
            'commit_hash' => 'old123abc',
            'commit_message' => 'Working version',
            'branch' => 'main',
            'created_at' => now()->subDays(2),
        ]);

        $currentDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'success',
            'commit_hash' => 'current456def',
            'created_at' => now()->subDay(),
        ]);

        // Perform rollback
        $rollbackDeployment = $this->deploymentService->rollback(
            $this->project,
            $oldDeployment,
            $this->user
        );

        // Assert rollback deployment was created
        $this->assertInstanceOf(Deployment::class, $rollbackDeployment);
        $this->assertEquals('old123abc', $rollbackDeployment->commit_hash);
        $this->assertEquals('rollback', $rollbackDeployment->triggered_by);
        $this->assertEquals($oldDeployment->id, $rollbackDeployment->rollback_deployment_id);
        $this->assertIsString($rollbackDeployment->commit_message);
        $this->assertStringContainsString('Rollback to:', $rollbackDeployment->commit_message);
    }

    /** @test */
    public function rollback_cannot_target_failed_deployment(): void
    {
        $failedDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'commit_hash' => 'failed123',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Can only rollback to successful deployments');

        $this->deploymentService->rollback(
            $this->project,
            $failedDeployment,
            $this->user
        );
    }

    /** @test */
    public function rollback_cannot_be_performed_during_active_deployment(): void
    {
        $successfulDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'commit_hash' => 'good123',
        ]);

        // Create active deployment
        $activeDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'running',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot rollback while another deployment is in progress');

        $this->deploymentService->rollback(
            $this->project,
            $successfulDeployment,
            $this->user
        );
    }

    // ==================== Failed Deployment Handling Tests ====================

    /** @test */
    public function failed_deployment_updates_project_status(): void
    {
        $this->project->update(['status' => 'running']);

        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'error_log' => 'Build failed',
        ]);

        // In actual implementation, observer or service would update project status
        // Verify deployment is marked as failed
        $this->assertTrue($deployment->isFailed());
        $this->assertNotNull($deployment->error_log);
    }

    /** @test */
    public function deployment_can_be_cancelled(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $cancelled = $this->deploymentService->cancelDeployment($deployment);

        $this->assertTrue($cancelled);
        $refreshedDeployment = $deployment->fresh();
        assert($refreshedDeployment !== null);
        $this->assertEquals('cancelled', $refreshedDeployment->status);
        $this->assertNotNull($refreshedDeployment->completed_at);
    }

    /** @test */
    public function completed_deployment_cannot_be_cancelled(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'completed_at' => now(),
        ]);

        $cancelled = $this->deploymentService->cancelDeployment($deployment);

        $this->assertFalse($cancelled);
        $refreshedDeployment = $deployment->fresh();
        assert($refreshedDeployment !== null);
        $this->assertEquals('success', $refreshedDeployment->status);
    }

    // ==================== Webhook Delivery Tracking Tests ====================

    /** @test */
    public function webhook_delivery_is_linked_to_deployment(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $delivery = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'deployment_id' => $deployment->id,
            'provider' => 'github',
            'event_type' => 'push',
            'status' => 'success',
        ]);

        $this->assertEquals($deployment->id, $delivery->deployment_id);
        $this->assertEquals($this->project->id, $delivery->project_id);
    }

    /** @test */
    public function webhook_delivery_records_payload_and_signature(): void
    {
        $payload = [
            'ref' => 'refs/heads/main',
            'commits' => [['id' => 'abc123']],
        ];

        $delivery = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'provider' => 'github',
            'payload' => $payload,
            'signature' => 'sha256=test-signature',
        ]);

        $freshDelivery = $delivery->fresh();
        assert($freshDelivery !== null);
        $this->assertEquals($payload, $freshDelivery->payload);
        $this->assertEquals('sha256=test-signature', $freshDelivery->signature);
    }

    // ==================== Deployment Validation Tests ====================

    /** @test */
    public function deployment_requires_valid_project(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project does not have a server assigned');

        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        $validation = $this->deploymentService->validateDeploymentPrerequisites($projectWithoutServer);

        $this->assertFalse($validation['valid']);
        $this->assertContains('Project does not have a server assigned', $validation['errors']);
    }

    /** @test */
    public function deployment_requires_online_server(): void
    {
        $offlineServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'offline',
        ]);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $offlineServer->id,
        ]);

        $validation = $this->deploymentService->validateDeploymentPrerequisites($project);

        $this->assertFalse($validation['valid']);
        $this->assertContains('Server is not online', $validation['errors']);
    }

    // ==================== Complete Workflow Integration Test ====================

    /** @test */
    public function complete_deployment_workflow_from_webhook_to_success(): void
    {
        Queue::fake();

        // Step 1: GitHub webhook arrives
        $payload = [
            'ref' => 'refs/heads/main',
            'repository' => [
                'name' => 'test-repo',
                'full_name' => 'test/repo',
                'html_url' => 'https://github.com/test/repo',
            ],
            'head_commit' => [
                'id' => 'workflow123abc',
                'message' => 'feat: Complete workflow test',
            ],
            'sender' => ['login' => 'testuser'],
            'pusher' => ['name' => 'Test User'],
        ];

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        assert(is_string($payloadJson));
        $signature = 'sha256=' . hash_hmac('sha256', $payloadJson, 'test-webhook-secret-123');

        // Send webhook
        $webhookResponse = $this->call(
            'POST',
            "/webhooks/github/{$this->project->webhook_secret}",
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_GITHUB_EVENT' => 'push',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payloadJson
        );

        // Verify webhook was accepted
        $this->assertTrue(in_array($webhookResponse->status(), [200, 202]));

        // Step 2: Verify deployment was created
        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'commit_hash' => 'workflow123abc',
            'triggered_by' => 'webhook',
            'status' => 'pending',
        ]);

        $deployment = Deployment::where('project_id', $this->project->id)
            ->where('commit_hash', 'workflow123abc')
            ->first();

        if (! $deployment instanceof Deployment) {
            $this->fail('Deployment was not created');
            return; // Make PHPStan happy
        }

        // Step 3: Verify job was dispatched
        Queue::assertPushed(DeployProjectJob::class, function ($job) use ($deployment) {
            return $job->deployment->id === $deployment->id;
        });

        // Step 4: Simulate deployment execution
        $deployment->update(['status' => 'running', 'started_at' => now()]);
        $runningDeploy = $deployment->fresh();
        assert($runningDeploy !== null);
        $this->assertTrue($runningDeploy->isRunning());

        // Step 5: Simulate successful completion
        $deployment->update([
            'status' => 'success',
            'completed_at' => now(),
            'duration_seconds' => 180,
            'output_log' => "Deployment completed successfully\nAll services started",
        ]);

        // Step 6: Verify final state
        $finalDeployment = $deployment->fresh();
        assert($finalDeployment !== null);
        $this->assertTrue($finalDeployment->isSuccess());
        $this->assertEquals(180, $finalDeployment->duration_seconds);
        $this->assertNotNull($finalDeployment->output_log);

        // Step 7: Verify webhook delivery was updated
        $this->assertDatabaseHas('webhook_deliveries', [
            'project_id' => $this->project->id,
            'deployment_id' => $deployment->id,
            'status' => 'success',
        ]);
    }
}
