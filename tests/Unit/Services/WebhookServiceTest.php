<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\PipelineConfig;
use App\Models\Project;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{

    protected WebhookService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new WebhookService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_verifies_github_signature_successfully(): void
    {
        // Arrange
        $payload = '{"ref":"refs/heads/main","commits":[]}';
        $secret = 'test-secret-key';
        $hash = hash_hmac('sha256', $payload, $secret);
        $signature = 'sha256='.$hash;

        // Act
        $result = $this->service->verifyGitHubSignature($payload, $signature, $secret);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_rejects_invalid_github_signature(): void
    {
        // Arrange
        $payload = '{"ref":"refs/heads/main"}';
        $secret = 'test-secret-key';
        $signature = 'sha256=invalid_hash';

        // Act
        $result = $this->service->verifyGitHubSignature($payload, $signature, $secret);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_rejects_github_signature_without_sha256_prefix(): void
    {
        // Arrange
        $payload = '{"ref":"refs/heads/main"}';
        $secret = 'test-secret';
        $hash = hash_hmac('sha256', $payload, $secret);
        $signature = $hash; // Missing 'sha256=' prefix

        // Act
        $result = $this->service->verifyGitHubSignature($payload, $signature, $secret);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_rejects_empty_github_signature(): void
    {
        // Arrange
        $payload = '{"ref":"refs/heads/main"}';
        $secret = 'test-secret';

        // Act
        $result = $this->service->verifyGitHubSignature($payload, '', $secret);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_uses_timing_safe_comparison_for_github_signature(): void
    {
        // Arrange - Create signature with different secret
        $payload = '{"ref":"refs/heads/main"}';
        $correctSecret = 'correct-secret';
        $wrongSecret = 'wrong-secret';

        $correctHash = hash_hmac('sha256', $payload, $correctSecret);
        $signature = 'sha256='.$correctHash;

        // Act - Try to verify with wrong secret
        $result = $this->service->verifyGitHubSignature($payload, $signature, $wrongSecret);

        // Assert - Should fail due to hash_equals timing-safe comparison
        $this->assertFalse($result);
    }

    #[Test]
    public function it_verifies_gitlab_token_successfully(): void
    {
        // Arrange
        $token = 'gitlab-secret-token';
        $secret = 'gitlab-secret-token';

        // Act
        $result = $this->service->verifyGitLabToken($token, $secret);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_rejects_invalid_gitlab_token(): void
    {
        // Arrange
        $token = 'wrong-token';
        $secret = 'correct-token';

        // Act
        $result = $this->service->verifyGitLabToken($token, $secret);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_uses_timing_safe_comparison_for_gitlab_token(): void
    {
        // Arrange - Use similar but different tokens to test timing-safe comparison
        $token = 'secret-token-1';
        $secret = 'secret-token-2';

        // Act
        $result = $this->service->verifyGitLabToken($token, $secret);

        // Assert - Should fail due to hash_equals timing-safe comparison
        $this->assertFalse($result);
    }

    #[Test]
    public function it_parses_github_push_payload_successfully(): void
    {
        // Arrange
        $payload = [
            'ref' => 'refs/heads/main',
            'head_commit' => [
                'id' => 'abc123def456',
                'message' => 'Fix critical bug',
            ],
            'repository' => [
                'name' => 'my-repo',
                'full_name' => 'user/my-repo',
                'html_url' => 'https://github.com/user/my-repo',
            ],
            'sender' => [
                'login' => 'johndoe',
            ],
            'pusher' => [
                'name' => 'John Doe',
            ],
        ];

        // Act
        $result = $this->service->parseGitHubPayload($payload);

        // Assert
        $this->assertEquals('main', $result['branch']);
        $this->assertEquals('abc123def456', $result['commit']);
        $this->assertEquals('Fix critical bug', $result['commit_message']);
        $this->assertEquals('my-repo', $result['repository']['name']);
        $this->assertEquals('user/my-repo', $result['repository']['full_name']);
        $this->assertEquals('https://github.com/user/my-repo', $result['repository']['url']);
        $this->assertEquals('johndoe', $result['sender']);
        $this->assertEquals('John Doe', $result['pusher']);
    }

    #[Test]
    public function it_parses_github_payload_with_after_commit_fallback(): void
    {
        // Arrange - Payload without head_commit but with after
        $payload = [
            'ref' => 'refs/heads/develop',
            'after' => 'xyz789abc012',
            'repository' => [
                'name' => 'test-repo',
            ],
            'sender' => [
                'login' => 'janedoe',
            ],
        ];

        // Act
        $result = $this->service->parseGitHubPayload($payload);

        // Assert
        $this->assertEquals('develop', $result['branch']);
        $this->assertEquals('xyz789abc012', $result['commit']);
        $this->assertNull($result['commit_message']);
    }

    #[Test]
    public function it_parses_github_payload_with_missing_optional_fields(): void
    {
        // Arrange - Minimal payload
        $payload = [
            'ref' => 'refs/heads/feature/test',
        ];

        // Act
        $result = $this->service->parseGitHubPayload($payload);

        // Assert
        $this->assertEquals('feature/test', $result['branch']);
        $this->assertNull($result['commit']);
        $this->assertNull($result['commit_message']);
        $this->assertNull($result['repository']);
        $this->assertEquals('unknown', $result['sender']);
        $this->assertNull($result['pusher']);
    }

    #[Test]
    public function it_extracts_branch_from_github_ref_correctly(): void
    {
        // Arrange
        $payload = [
            'ref' => 'refs/heads/feature/user-authentication',
        ];

        // Act
        $result = $this->service->parseGitHubPayload($payload);

        // Assert
        $this->assertEquals('feature/user-authentication', $result['branch']);
    }

    #[Test]
    public function it_parses_gitlab_push_payload_successfully(): void
    {
        // Arrange
        $payload = [
            'ref' => 'refs/heads/main',
            'checkout_sha' => 'def456abc789',
            'commits' => [
                [
                    'id' => 'first-commit',
                    'message' => 'First commit',
                ],
                [
                    'id' => 'latest-commit-123',
                    'message' => 'Latest commit message',
                ],
            ],
            'project' => [
                'name' => 'gitlab-project',
                'path_with_namespace' => 'team/gitlab-project',
                'web_url' => 'https://gitlab.com/team/gitlab-project',
            ],
            'user_name' => 'Alice Smith',
        ];

        // Act
        $result = $this->service->parseGitLabPayload($payload);

        // Assert
        $this->assertEquals('main', $result['branch']);
        $this->assertEquals('latest-commit-123', $result['commit']);
        $this->assertEquals('Latest commit message', $result['commit_message']);
        $this->assertEquals('gitlab-project', $result['repository']['name']);
        $this->assertEquals('team/gitlab-project', $result['repository']['full_name']);
        $this->assertEquals('https://gitlab.com/team/gitlab-project', $result['repository']['url']);
        $this->assertEquals('Alice Smith', $result['sender']);
        $this->assertEquals('Alice Smith', $result['pusher']);
    }

    #[Test]
    public function it_parses_gitlab_payload_with_checkout_sha_only(): void
    {
        // Arrange
        $payload = [
            'ref' => 'refs/heads/develop',
            'checkout_sha' => 'checkout-sha-456',
            'commits' => [],
            'project' => [
                'name' => 'test-project',
            ],
        ];

        // Act
        $result = $this->service->parseGitLabPayload($payload);

        // Assert
        $this->assertEquals('develop', $result['branch']);
        $this->assertEquals('checkout-sha-456', $result['commit']);
        $this->assertNull($result['commit_message']);
    }

    #[Test]
    public function it_parses_gitlab_payload_with_repository_fallback(): void
    {
        // Arrange - Using repository instead of project
        $payload = [
            'ref' => 'refs/heads/main',
            'repository' => [
                'name' => 'legacy-repo',
                'homepage' => 'https://gitlab.com/legacy-repo',
            ],
            'user' => [
                'name' => 'Bob Builder',
            ],
        ];

        // Act
        $result = $this->service->parseGitLabPayload($payload);

        // Assert
        $this->assertEquals('main', $result['branch']);
        $this->assertEquals('legacy-repo', $result['repository']['name']);
        $this->assertEquals('https://gitlab.com/legacy-repo', $result['repository']['url']);
        $this->assertEquals('Bob Builder', $result['sender']);
    }

    #[Test]
    public function it_parses_gitlab_payload_with_missing_optional_fields(): void
    {
        // Arrange - Minimal payload
        $payload = [
            'ref' => 'refs/heads/hotfix',
        ];

        // Act
        $result = $this->service->parseGitLabPayload($payload);

        // Assert
        $this->assertEquals('hotfix', $result['branch']);
        $this->assertNull($result['commit']);
        $this->assertNull($result['commit_message']);
        $this->assertNull($result['repository']);
        $this->assertEquals('unknown', $result['sender']);
        $this->assertNull($result['pusher']);
    }

    #[Test]
    public function it_triggers_deployment_when_conditions_are_met(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'webhook_enabled' => true,
            'branch' => 'main',
        ]);

        // Act
        $result = $this->service->shouldTriggerDeployment($project, 'main', 'Deploy to production');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_does_not_trigger_deployment_when_webhooks_disabled(): void
    {
        // Arrange
        Log::spy();
        $project = Project::factory()->create([
            'webhook_enabled' => false,
            'branch' => 'main',
        ]);

        // Act
        $result = $this->service->shouldTriggerDeployment($project, 'main');

        // Assert
        $this->assertFalse($result);
        /** @phpstan-ignore-next-line */
        Log::shouldHaveReceived('info')
            ->once()
            ->with(Mockery::on(function ($message) use ($project) {
                return str_contains($message, 'Webhook ignored') &&
                       str_contains($message, $project->slug);
            }));
    }

    #[Test]
    public function it_does_not_trigger_deployment_when_branch_does_not_match(): void
    {
        // Arrange
        Log::spy();
        $project = Project::factory()->create([
            'webhook_enabled' => true,
            'branch' => 'main',
        ]);

        // Act
        $result = $this->service->shouldTriggerDeployment($project, 'develop');

        // Assert
        $this->assertFalse($result);
        /** @phpstan-ignore-next-line */
        Log::shouldHaveReceived('info')
            ->once()
            ->with(Mockery::on(function ($message) {
                return str_contains($message, 'branch mismatch');
            }));
    }

    #[Test]
    public function it_uses_pipeline_config_when_enabled(): void
    {
        // Arrange
        Log::spy();
        $project = Project::factory()->create([
            'webhook_enabled' => true,
            'branch' => 'main',
        ]);

        // Create pipeline config
        /** @phpstan-ignore-next-line */
        $pipelineConfig = Mockery::mock(PipelineConfig::class)->makePartial();
        $pipelineConfig->enabled = true;
        /** @phpstan-ignore-next-line */
        $pipelineConfig->shouldReceive('shouldDeploy')
            ->once()
            ->with('develop', 'Test commit')
            ->andReturn(false);
        /** @phpstan-ignore-next-line */
        $pipelineConfig->shouldReceive('setAttribute')->andReturnSelf();
        /** @phpstan-ignore-next-line */
        $pipelineConfig->shouldReceive('getAttribute')->andReturnUsing(function ($key) {
            if ($key === 'enabled') {
                return true;
            }

            return null;
        });

        $project->setRelation('pipelineConfig', $pipelineConfig);

        // Act
        $result = $this->service->shouldTriggerDeployment($project, 'develop', 'Test commit');

        // Assert
        $this->assertFalse($result);
        /** @phpstan-ignore-next-line */
        Log::shouldHaveReceived('info')
            ->once()
            ->with(Mockery::on(function ($message) {
                return str_contains($message, 'pipeline config conditions not met');
            }), Mockery::type('array'));
    }

    #[Test]
    public function it_triggers_deployment_when_pipeline_config_conditions_met(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'webhook_enabled' => true,
            'branch' => 'main',
        ]);

        /** @phpstan-ignore-next-line */
        $pipelineConfig = Mockery::mock(PipelineConfig::class)->makePartial();
        $pipelineConfig->enabled = true;
        /** @phpstan-ignore-next-line */
        $pipelineConfig->shouldReceive('shouldDeploy')
            ->once()
            ->with('main', 'Deploy: production')
            ->andReturn(true);
        /** @phpstan-ignore-next-line */
        $pipelineConfig->shouldReceive('setAttribute')->andReturnSelf();
        /** @phpstan-ignore-next-line */
        $pipelineConfig->shouldReceive('getAttribute')->andReturnUsing(function ($key) {
            if ($key === 'enabled') {
                return true;
            }

            return null;
        });

        $project->setRelation('pipelineConfig', $pipelineConfig);

        // Act
        $result = $this->service->shouldTriggerDeployment($project, 'main', 'Deploy: production');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_falls_back_to_branch_matching_when_pipeline_config_disabled(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'webhook_enabled' => true,
            'branch' => 'production',
        ]);

        /** @phpstan-ignore-next-line */
        $pipelineConfig = Mockery::mock(PipelineConfig::class)->makePartial();
        $pipelineConfig->enabled = false;
        /** @phpstan-ignore-next-line */
        $pipelineConfig->shouldReceive('setAttribute')->andReturnSelf();
        /** @phpstan-ignore-next-line */
        $pipelineConfig->shouldReceive('getAttribute')->andReturnUsing(function ($key) {
            if ($key === 'enabled') {
                return false;
            }

            return null;
        });
        $project->setRelation('pipelineConfig', $pipelineConfig);

        // Act
        $result = $this->service->shouldTriggerDeployment($project, 'production');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_creates_webhook_delivery_record_successfully(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $payload = [
            'ref' => 'refs/heads/main',
            'commits' => [],
        ];
        $signature = 'sha256=abc123';

        // Act
        $delivery = $this->service->createDeliveryRecord(
            $project,
            'github',
            'push',
            $payload,
            $signature
        );

        // Assert
        $this->assertInstanceOf(WebhookDelivery::class, $delivery);
        $this->assertEquals($project->id, $delivery->project_id);
        $this->assertEquals('github', $delivery->provider);
        $this->assertEquals('push', $delivery->event_type);
        $this->assertEquals($payload, $delivery->payload);
        $this->assertEquals($signature, $delivery->signature);
        $this->assertEquals('pending', $delivery->status);
        $this->assertDatabaseHas('webhook_deliveries', [
            'project_id' => $project->id,
            'provider' => 'github',
            'event_type' => 'push',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function it_creates_webhook_delivery_record_without_signature(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $payload = ['event' => 'test'];

        // Act
        $delivery = $this->service->createDeliveryRecord(
            $project,
            'gitlab',
            'Push Hook',
            $payload,
            null
        );

        // Assert
        $this->assertNull($delivery->signature);
        $this->assertDatabaseHas('webhook_deliveries', [
            'project_id' => $project->id,
            'provider' => 'gitlab',
            'event_type' => 'Push Hook',
            'signature' => null,
        ]);
    }

    #[Test]
    public function it_updates_delivery_status_to_success(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $delivery = WebhookDelivery::create([
            'project_id' => $project->id,
            'provider' => 'github',
            'event_type' => 'push',
            'payload' => [],
            'status' => 'pending',
        ]);

        // Create a simple deployment manually to avoid factory issues
        $deployment = \App\Models\Deployment::create([
            'project_id' => $project->id,
            'user_id' => \App\Models\User::factory()->create()->id,
            'server_id' => $project->server_id,
            'branch' => 'main',
            'commit_hash' => 'abc123',
            'status' => 'success',
            'triggered_by' => 'webhook',
        ]);

        // Act
        $this->service->updateDeliveryStatus(
            $delivery,
            'success',
            'Deployment triggered',
            $deployment->id
        );

        // Assert
        $delivery->refresh();
        $this->assertEquals('success', $delivery->status);
        $this->assertEquals('Deployment triggered', $delivery->response);
        $this->assertEquals($deployment->id, $delivery->deployment_id);
    }

    #[Test]
    public function it_updates_delivery_status_to_failed(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $delivery = WebhookDelivery::create([
            'project_id' => $project->id,
            'provider' => 'github',
            'event_type' => 'push',
            'payload' => [],
            'status' => 'pending',
        ]);

        // Act
        $this->service->updateDeliveryStatus(
            $delivery,
            'failed',
            'Invalid signature'
        );

        // Assert
        $delivery->refresh();
        $this->assertEquals('failed', $delivery->status);
        $this->assertEquals('Invalid signature', $delivery->response);
        $this->assertNull($delivery->deployment_id);
    }

    #[Test]
    public function it_gets_github_event_type_from_header(): void
    {
        // Arrange
        $eventHeader = 'push';

        // Act
        $result = $this->service->getGitHubEventType($eventHeader);

        // Assert
        $this->assertEquals('push', $result);
    }

    #[Test]
    public function it_returns_unknown_when_github_event_header_missing(): void
    {
        // Act
        $result = $this->service->getGitHubEventType(null);

        // Assert
        $this->assertEquals('unknown', $result);
    }

    #[Test]
    public function it_gets_gitlab_event_type_from_payload(): void
    {
        // Arrange
        $payload = [
            'object_kind' => 'push',
            'ref' => 'refs/heads/main',
        ];

        // Act
        $result = $this->service->getGitLabEventType($payload);

        // Assert
        $this->assertEquals('push', $result);
    }

    #[Test]
    public function it_returns_unknown_when_gitlab_object_kind_missing(): void
    {
        // Arrange
        $payload = [
            'ref' => 'refs/heads/main',
        ];

        // Act
        $result = $this->service->getGitLabEventType($payload);

        // Assert
        $this->assertEquals('unknown', $result);
    }

    #[Test]
    public function it_processes_github_push_event(): void
    {
        // Act
        $result = $this->service->shouldProcessEvent('github', 'push');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_processes_gitlab_push_event(): void
    {
        // Act
        $result = $this->service->shouldProcessEvent('gitlab', 'push');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_processes_gitlab_push_hook_event(): void
    {
        // Act
        $result = $this->service->shouldProcessEvent('gitlab', 'Push Hook');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_processes_bitbucket_repo_push_event(): void
    {
        // Act
        $result = $this->service->shouldProcessEvent('bitbucket', 'repo:push');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_does_not_process_github_pull_request_event(): void
    {
        // Act
        $result = $this->service->shouldProcessEvent('github', 'pull_request');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_does_not_process_gitlab_merge_request_event(): void
    {
        // Act
        $result = $this->service->shouldProcessEvent('gitlab', 'merge_request');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_does_not_process_unknown_provider(): void
    {
        // Act
        $result = $this->service->shouldProcessEvent('unknown_provider', 'push');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_handles_complex_github_payload_with_all_fields(): void
    {
        // Arrange
        $payload = [
            'ref' => 'refs/heads/feature/complex-branch',
            'before' => 'old-commit-hash',
            'after' => 'new-commit-hash',
            'head_commit' => [
                'id' => 'head-commit-id',
                'message' => "Multi-line commit\nmessage here",
                'timestamp' => '2025-01-15T10:30:00Z',
                'author' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ],
            'repository' => [
                'id' => 12345,
                'name' => 'complex-repo',
                'full_name' => 'organization/complex-repo',
                'html_url' => 'https://github.com/organization/complex-repo',
                'description' => 'A complex repository',
            ],
            'sender' => [
                'login' => 'johndoe',
                'id' => 67890,
            ],
            'pusher' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ];

        // Act
        $result = $this->service->parseGitHubPayload($payload);

        // Assert
        $this->assertEquals('feature/complex-branch', $result['branch']);
        $this->assertEquals('head-commit-id', $result['commit']);
        $this->assertStringContainsString('Multi-line commit', $result['commit_message']);
        $this->assertEquals('complex-repo', $result['repository']['name']);
        $this->assertEquals('organization/complex-repo', $result['repository']['full_name']);
        $this->assertEquals('johndoe', $result['sender']);
        $this->assertEquals('John Doe', $result['pusher']);
    }

    #[Test]
    public function it_handles_edge_case_with_empty_commits_array_in_gitlab(): void
    {
        // Arrange
        $payload = [
            'ref' => 'refs/heads/main',
            'checkout_sha' => 'fallback-sha',
            'commits' => [], // Empty commits array
            'project' => [
                'name' => 'test-project',
            ],
            'user_name' => 'Test User',
        ];

        // Act
        $result = $this->service->parseGitLabPayload($payload);

        // Assert
        $this->assertEquals('main', $result['branch']);
        $this->assertEquals('fallback-sha', $result['commit']); // Should use checkout_sha
        $this->assertNull($result['commit_message']);
    }

    #[Test]
    public function it_logs_webhook_ignored_when_pipeline_config_not_met(): void
    {
        // Arrange
        Log::spy();
        $project = Project::factory()->create([
            'webhook_enabled' => true,
            'slug' => 'test-project',
        ]);

        /** @phpstan-ignore-next-line */
        $pipelineConfig = Mockery::mock(PipelineConfig::class)->makePartial();
        $pipelineConfig->enabled = true;
        /** @phpstan-ignore-next-line */
        $pipelineConfig->shouldReceive('shouldDeploy')
            ->with('feature-branch', 'WIP: Work in progress')
            ->andReturn(false);
        /** @phpstan-ignore-next-line */
        $pipelineConfig->shouldReceive('setAttribute')->andReturnSelf();
        /** @phpstan-ignore-next-line */
        $pipelineConfig->shouldReceive('getAttribute')->andReturnUsing(function ($key) {
            if ($key === 'enabled') {
                return true;
            }

            return null;
        });

        $project->setRelation('pipelineConfig', $pipelineConfig);

        // Act
        $result = $this->service->shouldTriggerDeployment($project, 'feature-branch', 'WIP: Work in progress');

        // Assert
        $this->assertFalse($result); // Add assertion to fix risky test
        /** @phpstan-ignore-next-line */
        Log::shouldHaveReceived('info')
            ->once()
            ->with(
                Mockery::on(function ($message) {
                    return str_contains($message, 'pipeline config conditions not met');
                }),
                Mockery::on(function ($context) {
                    return $context['branch'] === 'feature-branch' &&
                           $context['commit_message'] === 'WIP: Work in progress';
                })
            );
    }

    #[Test]
    public function it_correctly_strips_refs_heads_prefix_from_various_branch_formats(): void
    {
        // Test various branch name formats
        $testCases = [
            'refs/heads/main' => 'main',
            'refs/heads/develop' => 'develop',
            'refs/heads/feature/new-feature' => 'feature/new-feature',
            'refs/heads/hotfix/critical-fix' => 'hotfix/critical-fix',
            'refs/heads/release/v1.0.0' => 'release/v1.0.0',
        ];

        foreach ($testCases as $ref => $expectedBranch) {
            // Arrange
            $payload = ['ref' => $ref];

            // Act
            $result = $this->service->parseGitHubPayload($payload);

            // Assert
            $this->assertEquals($expectedBranch, $result['branch'], "Failed for ref: {$ref}");
        }
    }

    #[Test]
    public function it_handles_webhook_delivery_update_with_null_deployment_id(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $delivery = WebhookDelivery::create([
            'project_id' => $project->id,
            'provider' => 'github',
            'event_type' => 'push',
            'payload' => [],
            'status' => 'pending',
        ]);

        // Act
        $this->service->updateDeliveryStatus(
            $delivery,
            'ignored',
            'Branch does not match',
            null
        );

        // Assert
        $delivery->refresh();
        $this->assertEquals('ignored', $delivery->status);
        $this->assertEquals('Branch does not match', $delivery->response);
        $this->assertNull($delivery->deployment_id);
    }
}
