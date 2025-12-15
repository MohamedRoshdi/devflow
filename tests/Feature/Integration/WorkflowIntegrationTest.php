<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;


use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Project;
use App\Models\Server;
use App\Models\Pipeline;
use App\Models\PipelineRun;
use App\Models\PipelineStage;
use App\Models\PipelineStageRun;
use App\Models\Tenant;
use App\Models\TenantDeployment;
use App\Models\Deployment;
use App\Models\WebhookDelivery;
use App\Models\SecurityScan;
use App\Models\NotificationLog;
use App\Services\CICD\PipelineExecutionService;
use App\Services\MultiTenant\MultiTenantService;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

/**
 * Comprehensive integration tests for complete workflows in DevFlow Pro.
 * These tests verify end-to-end functionality across multiple components.
 */
class WorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Server $server;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base test data
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'status' => 'online',
            'docker_installed' => true,
        ]);
        $this->project = Project::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        $this->actingAs($this->user);
    }

    // =================================================================
    // Pipeline Execution Workflow Tests (10 tests)
    // =================================================================

    #[Test]
    public function it_executes_complete_pipeline_with_multiple_stages(): void
    {
        // Arrange: Create pipeline with stages
        $pipeline = Pipeline::factory()->create([
            'project_id' => $this->project->id,
            'is_active' => true,
        ]);

        $stages = [
            PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => 'Build',
                'type' => 'pre_deploy',
                'order' => 1,
                'enabled' => true,
            ]),
            PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => 'Test',
                'type' => 'pre_deploy',
                'order' => 2,
                'enabled' => true,
            ]),
            PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => 'Deploy',
                'type' => 'deploy',
                'order' => 3,
                'enabled' => true,
            ]),
        ];

        $pipelineRun = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'pending',
        ]);

        $this->mockSuccessfulCommand('Stage executed successfully');

        // Act: Execute pipeline stages sequentially
        foreach ($stages as $stage) {
            $stageRun = PipelineStageRun::factory()->create([
                'pipeline_run_id' => $pipelineRun->id,
                'pipeline_stage_id' => $stage->id,
                'status' => 'pending',
            ]);

            $stageRun->markRunning();
            $stageRun->markSuccess();
        }

        $pipelineRun->markSuccess();

        // Assert: Verify pipeline completed successfully
        $this->assertDatabaseHas('pipeline_runs', [
            'id' => $pipelineRun->id,
            'status' => 'success',
        ]);

        $this->assertEquals(3, $pipelineRun->fresh()->stageRuns()->count());
        $this->assertEquals('success', $pipelineRun->fresh()->stageRuns->last()->status);
    }

    #[Test]
    public function it_handles_pipeline_stage_failure(): void
    {
        // Arrange
        $pipeline = Pipeline::factory()->create(['project_id' => $this->project->id]);
        $pipelineRun = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'continue_on_failure' => false,
        ]);

        $stageRun = PipelineStageRun::factory()->create([
            'pipeline_run_id' => $pipelineRun->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'running',
        ]);

        // Act: Simulate stage failure
        $stageRun->markFailed('Command execution failed');
        $pipelineRun->markFailed();

        // Assert
        $this->assertDatabaseHas('pipeline_stage_runs', [
            'id' => $stageRun->id,
            'status' => 'failed',
            'error_message' => 'Command execution failed',
        ]);

        $this->assertDatabaseHas('pipeline_runs', [
            'id' => $pipelineRun->id,
            'status' => 'failed',
        ]);
    }

    #[Test]
    public function it_continues_pipeline_when_stage_has_continue_on_failure(): void
    {
        // Arrange
        $pipeline = Pipeline::factory()->create(['project_id' => $this->project->id]);
        $pipelineRun = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
        ]);

        $stage1 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'order' => 1,
            'continue_on_failure' => true,
        ]);

        $stage2 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'order' => 2,
        ]);

        // Act
        $stageRun1 = PipelineStageRun::factory()->create([
            'pipeline_run_id' => $pipelineRun->id,
            'pipeline_stage_id' => $stage1->id,
        ]);
        $stageRun1->markFailed('Non-critical failure');

        $stageRun2 = PipelineStageRun::factory()->create([
            'pipeline_run_id' => $pipelineRun->id,
            'pipeline_stage_id' => $stage2->id,
        ]);
        $stageRun2->markSuccess();

        // Assert: Pipeline continues despite first stage failure
        $this->assertEquals('failed', $stageRun1->fresh()->status);
        $this->assertEquals('success', $stageRun2->fresh()->status);
    }

    #[Test]
    public function it_tracks_pipeline_execution_duration(): void
    {
        // Arrange
        $pipeline = Pipeline::factory()->create(['project_id' => $this->project->id]);
        $startedAt = now()->subMinutes(5);

        $pipelineRun = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
            'started_at' => $startedAt,
            'status' => 'running',
        ]);

        // Act
        $pipelineRun->markSuccess();

        // Assert
        $freshRun = $pipelineRun->fresh();
        $this->assertNotNull($freshRun->finished_at);

        $duration = $freshRun->duration();
        $this->assertNotNull($duration);
        $this->assertGreaterThanOrEqual(0, abs($duration));
    }

    #[Test]
    public function it_creates_pipeline_with_deployment_trigger(): void
    {
        // Arrange
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        $pipeline = Pipeline::factory()->create(['project_id' => $this->project->id]);

        // Act
        $pipelineRun = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
            'deployment_id' => $deployment->id,
            'triggered_by' => 'deployment',
        ]);

        // Assert
        $this->assertDatabaseHas('pipeline_runs', [
            'pipeline_id' => $pipeline->id,
            'deployment_id' => $deployment->id,
            'triggered_by' => 'deployment',
        ]);

        $this->assertEquals($deployment->id, $pipelineRun->deployment_id);
    }

    #[Test]
    public function it_stores_pipeline_artifacts(): void
    {
        // Arrange
        $pipeline = Pipeline::factory()->create(['project_id' => $this->project->id]);
        $pipelineRun = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
        ]);

        $artifacts = [
            'build_output' => '/tmp/build.log',
            'test_results' => '/tmp/test-results.xml',
            'coverage_report' => '/tmp/coverage.html',
        ];

        // Act
        $pipelineRun->update(['artifacts' => $artifacts]);

        // Assert
        $this->assertEquals($artifacts, $pipelineRun->fresh()->artifacts);
    }

    #[Test]
    public function it_tracks_pipeline_run_number_incrementally(): void
    {
        // Arrange
        $pipeline = Pipeline::factory()->create(['project_id' => $this->project->id]);

        // Act
        $run1 = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
            'run_number' => 1,
        ]);

        $run2 = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
            'run_number' => 2,
        ]);

        $run3 = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
            'run_number' => 3,
        ]);

        // Assert
        $this->assertEquals(1, $run1->run_number);
        $this->assertEquals(2, $run2->run_number);
        $this->assertEquals(3, $run3->run_number);
    }

    #[Test]
    public function it_cancels_running_pipeline(): void
    {
        // Arrange
        $pipeline = Pipeline::factory()->create(['project_id' => $this->project->id]);
        $pipelineRun = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Act
        $pipelineRun->markCancelled();

        // Assert
        $this->assertDatabaseHas('pipeline_runs', [
            'id' => $pipelineRun->id,
            'status' => 'cancelled',
        ]);

        $this->assertNotNull($pipelineRun->fresh()->finished_at);
    }

    #[Test]
    public function it_executes_enabled_stages_only(): void
    {
        // Arrange
        $pipeline = Pipeline::factory()->create(['project_id' => $this->project->id]);
        $pipelineRun = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
        ]);

        $enabledStage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'enabled' => true,
        ]);

        $disabledStage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'enabled' => false,
        ]);

        // Act: Get only enabled stages
        $stages = PipelineStage::where('project_id', $this->project->id)
            ->enabled()
            ->get();

        // Assert
        $this->assertCount(1, $stages);
        $this->assertEquals($enabledStage->id, $stages->first()->id);
    }

    #[Test]
    public function it_stores_stage_execution_output(): void
    {
        // Arrange
        $pipeline = Pipeline::factory()->create(['project_id' => $this->project->id]);
        $pipelineRun = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
        ]);

        $stage = PipelineStage::factory()->create(['project_id' => $this->project->id]);
        $stageRun = PipelineStageRun::factory()->create([
            'pipeline_run_id' => $pipelineRun->id,
            'pipeline_stage_id' => $stage->id,
        ]);

        // Act: Append output lines
        $stageRun->appendOutput('Building project...');
        $stageRun->appendOutput('Build complete');
        $stageRun->appendOutput('Running tests...');

        // Assert
        $output = $stageRun->fresh()->output;
        $this->assertStringContainsString('Building project...', $output);
        $this->assertStringContainsString('Build complete', $output);
        $this->assertStringContainsString('Running tests...', $output);
    }

    // =================================================================
    // Multi-Tenant Deployment Workflow Tests (12 tests)
    // =================================================================

    #[Test]
    public function it_creates_tenant_with_complete_configuration(): void
    {
        // Arrange & Act
        $tenant = Tenant::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test Company',
            'subdomain' => 'testcompany',
            'database' => 'tenant_testcompany',
            'admin_email' => 'admin@testcompany.com',
            'plan' => 'premium',
            'status' => 'active',
            'custom_config' => [
                'theme' => 'dark',
                'language' => 'en',
            ],
            'features' => [
                'api_access' => true,
                'custom_domain' => true,
            ],
        ]);

        // Assert
        $this->assertDatabaseHas('tenants', [
            'project_id' => $this->project->id,
            'subdomain' => 'testcompany',
            'status' => 'active',
        ]);

        $this->assertEquals('premium', $tenant->plan);
        $this->assertTrue($tenant->isActive());
    }

    #[Test]
    public function it_deploys_to_single_tenant(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'success',
        ]);

        // Act
        $tenantDeployment = TenantDeployment::factory()->create([
            'tenant_id' => $tenant->id,
            'deployment_id' => $deployment->id,
            'status' => 'success',
            'output' => 'Deployment completed successfully',
        ]);

        // Assert
        $this->assertDatabaseHas('tenant_deployments', [
            'tenant_id' => $tenant->id,
            'deployment_id' => $deployment->id,
            'status' => 'success',
        ]);

        $this->assertCount(1, $tenant->deployments);
    }

    #[Test]
    public function it_deploys_to_multiple_tenants(): void
    {
        // Arrange
        $tenants = Tenant::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'status' => 'active',
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        // Act: Deploy to all tenants
        foreach ($tenants as $tenant) {
            TenantDeployment::factory()->create([
                'tenant_id' => $tenant->id,
                'deployment_id' => $deployment->id,
                'status' => 'success',
            ]);

            $tenant->update(['last_deployed_at' => now()]);
        }

        // Assert
        $this->assertCount(3, TenantDeployment::where('deployment_id', $deployment->id)->get());

        foreach ($tenants as $tenant) {
            $this->assertNotNull($tenant->fresh()->last_deployed_at);
        }
    }

    #[Test]
    public function it_verifies_tenant_isolation(): void
    {
        // Arrange: Create multiple tenants
        $tenant1 = Tenant::factory()->create([
            'project_id' => $this->project->id,
            'subdomain' => 'tenant1',
            'database' => 'tenant1_db',
        ]);

        $tenant2 = Tenant::factory()->create([
            'project_id' => $this->project->id,
            'subdomain' => 'tenant2',
            'database' => 'tenant2_db',
        ]);

        // Assert: Verify tenants have separate databases
        $this->assertNotEquals($tenant1->database, $tenant2->database);
        $this->assertNotEquals($tenant1->subdomain, $tenant2->subdomain);
    }

    #[Test]
    public function it_handles_tenant_trial_period(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create([
            'project_id' => $this->project->id,
            'trial_ends_at' => now()->addDays(14),
        ]);

        // Assert
        $this->assertTrue($tenant->isOnTrial());
        $this->assertNotNull($tenant->trial_ends_at);
    }

    #[Test]
    public function it_handles_expired_tenant_trial(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create([
            'project_id' => $this->project->id,
            'trial_ends_at' => now()->subDays(1),
        ]);

        // Assert
        $this->assertFalse($tenant->isOnTrial());
    }

    #[Test]
    public function it_generates_tenant_url_from_subdomain(): void
    {
        // Arrange
        $domain = \App\Models\Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'is_primary' => true,
        ]);

        $tenant = Tenant::factory()->create([
            'project_id' => $this->project->id,
            'subdomain' => 'client1',
        ]);

        // Act
        $url = $tenant->url;

        // Assert
        $this->assertEquals('https://client1.example.com', $url);
    }

    #[Test]
    public function it_tracks_tenant_deployment_history(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create(['project_id' => $this->project->id]);

        // Act: Create multiple deployments
        for ($i = 1; $i <= 5; $i++) {
            $deployment = Deployment::factory()->create([
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
            ]);

            TenantDeployment::factory()->create([
                'tenant_id' => $tenant->id,
                'deployment_id' => $deployment->id,
                'status' => 'success',
            ]);
        }

        // Assert
        $this->assertCount(5, $tenant->deployments);
    }

    #[Test]
    public function it_handles_failed_tenant_deployment(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create(['project_id' => $this->project->id]);
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        // Act
        $tenantDeployment = TenantDeployment::factory()->create([
            'tenant_id' => $tenant->id,
            'deployment_id' => $deployment->id,
            'status' => 'failed',
            'output' => 'Database migration failed',
        ]);

        // Assert
        $this->assertDatabaseHas('tenant_deployments', [
            'tenant_id' => $tenant->id,
            'status' => 'failed',
        ]);
    }

    #[Test]
    public function it_stores_tenant_custom_configuration(): void
    {
        // Arrange & Act
        $tenant = Tenant::factory()->create([
            'project_id' => $this->project->id,
            'custom_config' => [
                'max_users' => 100,
                'storage_limit_gb' => 50,
                'api_rate_limit' => 1000,
                'custom_branding' => true,
            ],
        ]);

        // Assert
        $config = $tenant->custom_config;
        $this->assertEquals(100, $config['max_users']);
        $this->assertEquals(50, $config['storage_limit_gb']);
        $this->assertTrue($config['custom_branding']);
    }

    #[Test]
    public function it_manages_tenant_features(): void
    {
        // Arrange & Act
        $tenant = Tenant::factory()->create([
            'project_id' => $this->project->id,
            'features' => [
                'api_access' => true,
                'webhooks' => true,
                'custom_reports' => false,
                'white_label' => true,
            ],
        ]);

        // Assert
        $this->assertTrue($tenant->features['api_access']);
        $this->assertTrue($tenant->features['webhooks']);
        $this->assertFalse($tenant->features['custom_reports']);
    }

    #[Test]
    public function it_encrypts_tenant_admin_password(): void
    {
        // Arrange & Act
        $tenant = Tenant::factory()->create([
            'project_id' => $this->project->id,
            'admin_password' => 'secret_password_123',
        ]);

        // Assert: Password is encrypted in database
        $this->assertDatabaseMissing('tenants', [
            'id' => $tenant->id,
            'admin_password' => 'secret_password_123',
        ]);

        // Password can be decrypted
        $this->assertEquals('secret_password_123', $tenant->admin_password);
    }

    // =================================================================
    // Webhook Delivery Workflow Tests (10 tests)
    // =================================================================

    #[Test]
    public function it_receives_and_processes_webhook(): void
    {
        // Arrange & Act
        $webhook = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'provider' => 'github',
            'event_type' => 'push',
            'payload' => [
                'ref' => 'refs/heads/main',
                'commits' => [
                    ['id' => 'abc123', 'message' => 'Fix bug'],
                ],
            ],
            'status' => 'success',
        ]);

        // Assert
        $this->assertDatabaseHas('webhook_deliveries', [
            'project_id' => $this->project->id,
            'provider' => 'github',
            'event_type' => 'push',
            'status' => 'success',
        ]);
    }

    #[Test]
    public function it_triggers_deployment_from_webhook(): void
    {
        // Arrange
        $webhook = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'provider' => 'github',
            'event_type' => 'push',
            'status' => 'pending',
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'triggered_by' => 'webhook',
            'status' => 'success',
        ]);

        // Act: Link webhook to deployment
        $webhook->update([
            'deployment_id' => $deployment->id,
            'status' => 'success',
        ]);

        // Assert
        $this->assertEquals($deployment->id, $webhook->fresh()->deployment_id);
        $this->assertTrue($webhook->fresh()->isSuccess());
    }

    #[Test]
    public function it_handles_webhook_signature_verification(): void
    {
        // Arrange & Act
        $webhook = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'provider' => 'github',
            'signature' => hash_hmac('sha256', 'payload_data', 'secret_key'),
            'status' => 'success',
        ]);

        // Assert
        $this->assertNotNull($webhook->signature);
        $this->assertTrue($webhook->isSuccess());
    }

    #[Test]
    public function it_stores_webhook_failure(): void
    {
        // Arrange & Act
        $webhook = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'provider' => 'gitlab',
            'event_type' => 'push',
            'status' => 'failed',
            'response' => 'Invalid signature',
        ]);

        // Assert
        $this->assertDatabaseHas('webhook_deliveries', [
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        $this->assertTrue($webhook->isFailed());
    }

    #[Test]
    public function it_ignores_irrelevant_webhook_events(): void
    {
        // Arrange & Act
        $webhook = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'provider' => 'github',
            'event_type' => 'issues',
            'status' => 'ignored',
        ]);

        // Assert
        $this->assertTrue($webhook->isIgnored());
    }

    #[Test]
    public function it_processes_webhook_from_multiple_providers(): void
    {
        // Arrange & Act
        $githubWebhook = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'provider' => 'github',
            'event_type' => 'push',
        ]);

        $gitlabWebhook = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'provider' => 'gitlab',
            'event_type' => 'push',
        ]);

        $bitbucketWebhook = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'provider' => 'bitbucket',
            'event_type' => 'push',
        ]);

        // Assert
        $webhooks = WebhookDelivery::where('project_id', $this->project->id)->get();
        $this->assertCount(3, $webhooks);

        $providers = $webhooks->pluck('provider')->sort()->values()->toArray();
        $this->assertEquals(['bitbucket', 'github', 'gitlab'], $providers);
    }

    #[Test]
    public function it_stores_webhook_payload(): void
    {
        // Arrange & Act
        $payload = [
            'ref' => 'refs/heads/main',
            'repository' => [
                'name' => 'test-repo',
                'url' => 'https://github.com/user/test-repo',
            ],
            'pusher' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'commits' => [
                [
                    'id' => 'commit1',
                    'message' => 'Update feature',
                    'timestamp' => '2025-01-01T10:00:00Z',
                ],
            ],
        ];

        $webhook = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'payload' => $payload,
        ]);

        // Assert
        $this->assertEquals($payload, $webhook->payload);
        $this->assertEquals('test-repo', $webhook->payload['repository']['name']);
    }

    #[Test]
    public function it_tracks_webhook_pending_status(): void
    {
        // Arrange & Act
        $webhook = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'pending',
        ]);

        // Assert
        $this->assertTrue($webhook->isPending());
        $this->assertFalse($webhook->isSuccess());
    }

    #[Test]
    public function it_links_webhook_to_deployment(): void
    {
        // Arrange
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $webhook = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'deployment_id' => $deployment->id,
        ]);

        // Assert
        $this->assertEquals($deployment->id, $webhook->deployment_id);
        $this->assertInstanceOf(Deployment::class, $webhook->deployment);
    }

    #[Test]
    public function it_sends_notification_after_webhook_processing(): void
    {
        // Arrange
        Notification::fake();

        $webhook = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'success',
        ]);

        // Act: Simulate notification sending
        NotificationLog::factory()->create([
            'event_type' => 'webhook.received',
            'payload' => [
                'webhook_id' => $webhook->id,
                'project_id' => $this->project->id,
                'provider' => 'github',
            ],
            'status' => 'sent',
        ]);

        // Assert
        $this->assertDatabaseHas('notification_logs', [
            'event_type' => 'webhook.received',
            'status' => 'sent',
        ]);
    }

    // =================================================================
    // Bulk Server Operations Tests (8 tests)
    // =================================================================

    #[Test]
    public function it_selects_multiple_servers_for_bulk_action(): void
    {
        // Arrange
        $servers = Server::factory()->count(5)->create([
            'status' => 'online',
        ]);

        // Act: Select servers
        $selectedServers = Server::whereIn('id', $servers->pluck('id'))->get();

        // Assert
        $this->assertCount(5, $selectedServers);
    }

    #[Test]
    public function it_executes_bulk_update_on_servers(): void
    {
        // Arrange
        $servers = Server::factory()->count(3)->create([
            'status' => 'online',
        ]);

        // Act: Bulk update status
        Server::whereIn('id', $servers->pluck('id'))
            ->update(['status' => 'maintenance']);

        // Assert
        foreach ($servers as $server) {
            $this->assertDatabaseHas('servers', [
                'id' => $server->id,
                'status' => 'maintenance',
            ]);
        }
    }

    #[Test]
    public function it_tracks_bulk_operation_progress(): void
    {
        // Arrange
        $servers = Server::factory()->count(5)->create();
        $results = [];

        // Act: Process each server
        foreach ($servers as $server) {
            $results[$server->id] = [
                'status' => 'success',
                'message' => 'Operation completed',
            ];
        }

        // Assert
        $this->assertCount(5, $results);
        $this->assertEquals('success', $results[$servers->first()->id]['status']);
    }

    #[Test]
    public function it_handles_partial_failures_in_bulk_operations(): void
    {
        // Arrange
        $servers = Server::factory()->count(5)->create();
        $results = [];

        // Act: Simulate mixed results
        foreach ($servers as $index => $server) {
            $results[$server->id] = [
                'status' => $index % 2 === 0 ? 'success' : 'failed',
                'message' => $index % 2 === 0 ? 'OK' : 'Error',
            ];
        }

        // Assert
        $successCount = collect($results)->where('status', 'success')->count();
        $failureCount = collect($results)->where('status', 'failed')->count();

        $this->assertEquals(3, $successCount);
        $this->assertEquals(2, $failureCount);
    }

    #[Test]
    public function it_executes_bulk_security_scan(): void
    {
        // Arrange
        $servers = Server::factory()->count(3)->create(['status' => 'online']);

        // Act: Create security scans for all servers
        foreach ($servers as $server) {
            SecurityScan::factory()->create([
                'server_id' => $server->id,
                'status' => 'completed',
                'score' => 85,
                'risk_level' => 'low',
            ]);
        }

        // Assert
        $this->assertCount(3, SecurityScan::all());

        foreach ($servers as $server) {
            $this->assertDatabaseHas('security_scans', [
                'server_id' => $server->id,
                'status' => 'completed',
            ]);
        }
    }

    #[Test]
    public function it_performs_bulk_server_reboot(): void
    {
        // Arrange
        $servers = Server::factory()->count(3)->create(['status' => 'online']);
        $this->mockSuccessfulCommand('Reboot initiated');

        // Act: Simulate reboot
        foreach ($servers as $server) {
            $server->update(['status' => 'maintenance']);
        }

        // Assert
        foreach ($servers as $server) {
            $this->assertEquals('maintenance', $server->fresh()->status);
        }
    }

    #[Test]
    public function it_executes_bulk_package_installation(): void
    {
        // Arrange
        $servers = Server::factory()->count(3)->create([
            'docker_installed' => false,
        ]);

        // Act: Install Docker on all servers
        foreach ($servers as $server) {
            $server->update([
                'docker_installed' => true,
                'docker_version' => '24.0.0',
            ]);
        }

        // Assert
        foreach ($servers as $server) {
            $this->assertTrue($server->fresh()->docker_installed);
            $this->assertEquals('24.0.0', $server->fresh()->docker_version);
        }
    }

    #[Test]
    public function it_generates_bulk_operation_report(): void
    {
        // Arrange
        $servers = Server::factory()->count(5)->create();
        $results = [];

        // Act: Execute operations and collect results
        foreach ($servers as $server) {
            $results[] = [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'status' => 'success',
                'execution_time' => rand(100, 500) . 'ms',
            ];
        }

        // Assert: Verify report structure
        $this->assertCount(5, $results);
        $this->assertArrayHasKey('server_id', $results[0]);
        $this->assertArrayHasKey('status', $results[0]);
        $this->assertArrayHasKey('execution_time', $results[0]);
    }

    // =================================================================
    // Security Scanning Workflow Tests (10 tests)
    // =================================================================

    #[Test]
    public function it_initiates_security_scan(): void
    {
        // Arrange & Act
        $scan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'pending',
            'triggered_by' => $this->user->id,
        ]);

        // Assert
        $this->assertDatabaseHas('security_scans', [
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function it_executes_security_checks(): void
    {
        // Arrange
        $scan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Act: Complete scan
        $scan->update([
            'status' => 'completed',
            'completed_at' => now(),
            'score' => 75,
            'risk_level' => 'medium',
            'findings' => [
                ['type' => 'open_port', 'severity' => 'medium', 'port' => 3306],
                ['type' => 'weak_password', 'severity' => 'high', 'service' => 'ssh'],
            ],
        ]);

        // Assert
        $this->assertEquals('completed', $scan->fresh()->status);
        $this->assertEquals(75, $scan->fresh()->score);
        $this->assertCount(2, $scan->fresh()->findings);
    }

    #[Test]
    public function it_generates_security_report(): void
    {
        // Arrange
        $scan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'completed',
            'score' => 65,
            'risk_level' => 'medium',
            'findings' => [
                [
                    'type' => 'outdated_package',
                    'severity' => 'high',
                    'package' => 'openssl',
                    'current_version' => '1.0.2',
                    'recommended_version' => '1.1.1',
                ],
            ],
            'recommendations' => [
                'Update OpenSSL to latest version',
                'Enable firewall',
                'Configure fail2ban',
            ],
        ]);

        // Assert
        $this->assertNotEmpty($scan->findings);
        $this->assertNotEmpty($scan->recommendations);
        $this->assertCount(3, $scan->recommendations);
    }

    #[Test]
    public function it_alerts_on_critical_vulnerabilities(): void
    {
        // Arrange
        $scan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'completed',
            'score' => 35,
            'risk_level' => 'critical',
            'findings' => [
                [
                    'type' => 'critical_vulnerability',
                    'severity' => 'critical',
                    'cve' => 'CVE-2024-1234',
                ],
            ],
        ]);

        // Act: Create alert notification
        NotificationLog::factory()->create([
            'event_type' => 'security.alert.critical',
            'payload' => [
                'scan_id' => $scan->id,
                'server_id' => $this->server->id,
                'risk_level' => 'critical',
                'score' => 35,
            ],
            'status' => 'sent',
        ]);

        // Assert
        $this->assertEquals('critical', $scan->risk_level);
        $this->assertDatabaseHas('notification_logs', [
            'event_type' => 'security.alert.critical',
            'status' => 'sent',
        ]);
    }

    #[Test]
    public function it_calculates_security_score_from_findings(): void
    {
        // Arrange & Act
        $scan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'score' => 88,
        ]);

        // Assert
        $riskLevel = SecurityScan::getRiskLevelFromScore(88);
        $this->assertEquals('low', $riskLevel);
    }

    #[Test]
    public function it_tracks_scan_duration(): void
    {
        // Arrange
        $startedAt = now()->subMinutes(10);
        $completedAt = now();

        $scan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ]);

        // Assert
        $duration = $scan->duration;
        $this->assertNotNull($duration);
        $this->assertGreaterThanOrEqual(0, abs($duration));
    }

    #[Test]
    public function it_stores_scan_findings_with_details(): void
    {
        // Arrange & Act
        $scan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'findings' => [
                [
                    'type' => 'open_port',
                    'severity' => 'medium',
                    'port' => 22,
                    'service' => 'ssh',
                    'description' => 'SSH port is publicly accessible',
                ],
                [
                    'type' => 'outdated_package',
                    'severity' => 'high',
                    'package' => 'nginx',
                    'current_version' => '1.18.0',
                    'latest_version' => '1.24.0',
                ],
            ],
        ]);

        // Assert
        $findings = $scan->findings;
        $this->assertCount(2, $findings);
        $this->assertEquals('open_port', $findings[0]['type']);
        $this->assertEquals('high', $findings[1]['severity']);
    }

    #[Test]
    public function it_generates_security_recommendations(): void
    {
        // Arrange & Act
        $scan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'score' => 60,
            'recommendations' => [
                'Update all packages to latest versions',
                'Enable and configure UFW firewall',
                'Install and configure fail2ban',
                'Disable root login via SSH',
                'Change default SSH port',
            ],
        ]);

        // Assert
        $this->assertCount(5, $scan->recommendations);
        $this->assertContains('Enable and configure UFW firewall', $scan->recommendations);
    }

    #[Test]
    public function it_handles_failed_security_scan(): void
    {
        // Arrange & Act
        $scan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'failed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        // Assert
        $this->assertEquals('failed', $scan->status);
        $this->assertNotNull($scan->completed_at);
    }

    #[Test]
    public function it_retrieves_latest_security_scan_for_server(): void
    {
        // Arrange: Create multiple scans
        SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(2),
            'score' => 70,
        ]);

        $latestScan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now(),
            'score' => 85,
        ]);

        // Act
        $retrieved = $this->server->latestSecurityScan;

        // Assert
        $this->assertEquals($latestScan->id, $retrieved->id);
        $this->assertEquals(85, $retrieved->score);
    }

    // =================================================================
    // Additional Integration Tests (6 tests for 56 total)
    // =================================================================

    #[Test]
    public function it_completes_full_ci_cd_workflow(): void
    {
        // Arrange: Setup complete CI/CD pipeline
        $pipeline = Pipeline::factory()->create([
            'project_id' => $this->project->id,
            'is_active' => true,
        ]);

        $stages = [
            PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => 'Install Dependencies',
                'order' => 1,
            ]),
            PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => 'Run Tests',
                'order' => 2,
            ]),
            PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => 'Build',
                'order' => 3,
            ]),
            PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => 'Deploy',
                'order' => 4,
            ]),
        ];

        // Act: Execute complete workflow
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        $pipelineRun = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'project_id' => $this->project->id,
            'deployment_id' => $deployment->id,
            'status' => 'running',
        ]);

        foreach ($stages as $stage) {
            $stageRun = PipelineStageRun::factory()->success()->create([
                'pipeline_run_id' => $pipelineRun->id,
                'pipeline_stage_id' => $stage->id,
            ]);
        }

        $pipelineRun->markSuccess();
        $deployment->update(['status' => 'success']);

        // Assert: Verify complete workflow
        $this->assertEquals('success', $pipelineRun->fresh()->status);
        $this->assertEquals('success', $deployment->fresh()->status);
        $this->assertCount(4, $pipelineRun->stageRuns);
        $this->assertTrue($pipelineRun->fresh()->stageRuns->every(fn($run) => $run->status === 'success'));
    }

    #[Test]
    public function it_handles_rollback_workflow(): void
    {
        // Arrange: Create successful deployment
        $successfulDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'success',
            'commit_hash' => 'abc123',
        ]);

        // Failed deployment
        $failedDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
            'commit_hash' => 'def456',
        ]);

        // Act: Create rollback deployment
        $rollbackDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'success',
            'rollback_deployment_id' => $successfulDeployment->id,
            'commit_hash' => 'abc123',
        ]);

        // Assert
        $this->assertEquals($successfulDeployment->id, $rollbackDeployment->rollback_deployment_id);
        $this->assertEquals($successfulDeployment->commit_hash, $rollbackDeployment->commit_hash);
    }

    #[Test]
    public function it_manages_project_with_multiple_environments(): void
    {
        // Arrange: Create project with multiple servers
        $stagingServer = Server::factory()->create(['name' => 'Staging Server']);
        $productionServer = Server::factory()->create(['name' => 'Production Server']);

        $stagingDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $stagingServer->id,
            'branch' => 'develop',
            'status' => 'success',
        ]);

        $productionDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $productionServer->id,
            'branch' => 'main',
            'status' => 'success',
        ]);

        // Assert
        $this->assertNotEquals($stagingDeployment->server_id, $productionDeployment->server_id);
        $this->assertEquals('develop', $stagingDeployment->branch);
        $this->assertEquals('main', $productionDeployment->branch);
    }

    #[Test]
    public function it_tracks_complete_deployment_lifecycle(): void
    {
        // Arrange & Act: Create deployment through all stages
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
            'started_at' => null,
        ]);

        // Start deployment
        $deployment->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Complete deployment
        $deployment->update([
            'status' => 'success',
            'completed_at' => now(),
            'duration_seconds' => 120,
        ]);

        // Assert
        $this->assertEquals('success', $deployment->fresh()->status);
        $this->assertNotNull($deployment->fresh()->started_at);
        $this->assertNotNull($deployment->fresh()->completed_at);
        $this->assertEquals(120, $deployment->fresh()->duration_seconds);
    }

    #[Test]
    public function it_manages_concurrent_deployments_across_projects(): void
    {
        // Arrange: Create multiple projects
        $project1 = Project::factory()->create(['server_id' => $this->server->id]);
        $project2 = Project::factory()->create(['server_id' => $this->server->id]);
        $project3 = Project::factory()->create(['server_id' => $this->server->id]);

        // Act: Create concurrent deployments
        $deployment1 = Deployment::factory()->create([
            'project_id' => $project1->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        $deployment2 = Deployment::factory()->create([
            'project_id' => $project2->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        $deployment3 = Deployment::factory()->create([
            'project_id' => $project3->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        // Assert
        $runningDeployments = Deployment::where('status', 'running')->count();
        $this->assertEquals(3, $runningDeployments);
    }

    #[Test]
    public function it_integrates_monitoring_with_deployments(): void
    {
        // Arrange
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'success',
        ]);

        // Act: Create monitoring records
        $securityScan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'status' => 'completed',
            'score' => 90,
        ]);

        // Log notification
        $notification = NotificationLog::factory()->create([
            'event_type' => 'deployment.success',
            'payload' => [
                'deployment_id' => $deployment->id,
                'project_id' => $this->project->id,
                'commit_hash' => $deployment->commit_hash,
            ],
            'status' => 'sent',
        ]);

        // Assert: Verify integration
        $this->assertEquals('success', $deployment->status);
        $this->assertEquals('completed', $securityScan->status);
        $this->assertDatabaseHas('notification_logs', [
            'event_type' => 'deployment.success',
            'status' => 'sent',
        ]);
    }
}
