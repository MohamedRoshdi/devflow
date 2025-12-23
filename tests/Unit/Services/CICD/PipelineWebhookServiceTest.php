<?php

declare(strict_types=1);

namespace Tests\Unit\Services\CICD;

use App\Models\Pipeline;
use App\Models\Project;
use App\Services\CICD\PipelineWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class PipelineWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    private PipelineWebhookService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PipelineWebhookService();
    }

    public function test_detect_git_provider_github(): void
    {
        $provider = $this->service->detectGitProvider('https://github.com/devflow/test.git');

        $this->assertEquals('github', $provider);
    }

    public function test_detect_git_provider_gitlab(): void
    {
        $provider = $this->service->detectGitProvider('https://gitlab.com/devflow/test.git');

        $this->assertEquals('gitlab', $provider);
    }

    public function test_detect_git_provider_bitbucket(): void
    {
        $provider = $this->service->detectGitProvider('https://bitbucket.org/devflow/test.git');

        $this->assertEquals('bitbucket', $provider);
    }

    public function test_detect_git_provider_custom(): void
    {
        $provider = $this->service->detectGitProvider('https://custom-git.example.com/devflow/test.git');

        $this->assertEquals('custom', $provider);
    }

    public function test_setup_github_webhook(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response([
                'id' => 12345,
            ], 201),
        ]);

        $project = Project::factory()->create([
            'repository_url' => 'https://github.com/devflow/test-project.git',
            'webhook_secret' => 'test-secret',
        ]);

        config(['services.github.token' => 'test-token']);

        $result = $this->service->setupWebhook($project);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('webhook_id', $result);
        // @phpstan-ignore offsetAccess.notFound
        $this->assertEquals('12345', $result['webhook_id']);

        $project->refresh();
        $this->assertTrue($project->webhook_enabled);
        $this->assertEquals('github', $project->webhook_provider);
    }

    public function test_setup_github_webhook_generates_secret_if_missing(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response([
                'id' => 12345,
            ], 201),
        ]);

        $project = Project::factory()->create([
            'repository_url' => 'https://github.com/devflow/test-project.git',
            'webhook_secret' => null,
        ]);

        config(['services.github.token' => 'test-token']);

        $result = $this->service->setupWebhook($project);

        $project->refresh();
        $this->assertNotNull($project->webhook_secret);
        $this->assertTrue($result['success']);
    }

    public function test_setup_github_webhook_handles_missing_token(): void
    {
        $project = Project::factory()->create([
            'repository_url' => 'https://github.com/devflow/test-project.git',
        ]);

        config(['services.github.token' => null]);

        $result = $this->service->setupWebhook($project);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        // @phpstan-ignore offsetAccess.notFound
        $this->assertStringContainsString('GitHub token not configured', $result['error']);
    }

    public function test_setup_gitlab_webhook(): void
    {
        Http::fake([
            'gitlab.com/*' => Http::response([
                'id' => 67890,
            ], 201),
        ]);

        $project = Project::factory()->create([
            'repository_url' => 'https://gitlab.com/devflow/test-project.git',
            'webhook_secret' => 'test-secret',
        ]);

        config(['services.gitlab.token' => 'test-token']);

        $result = $this->service->setupWebhook($project);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('webhook_id', $result);
        // @phpstan-ignore offsetAccess.notFound
        $this->assertEquals('67890', $result['webhook_id']);

        $project->refresh();
        $this->assertTrue($project->webhook_enabled);
        $this->assertEquals('gitlab', $project->webhook_provider);
    }

    public function test_setup_bitbucket_webhook(): void
    {
        Http::fake([
            'api.bitbucket.org/*' => Http::response([
                'uuid' => '{abc-123}',
            ], 201),
        ]);

        $project = Project::factory()->create([
            'repository_url' => 'https://bitbucket.org/devflow/test-project.git',
            'webhook_secret' => 'test-secret',
        ]);

        config([
            'services.bitbucket.username' => 'test-user',
            'services.bitbucket.app_password' => 'test-password',
        ]);

        $result = $this->service->setupWebhook($project);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('webhook_id', $result);
        // @phpstan-ignore offsetAccess.notFound
        $this->assertEquals('{abc-123}', $result['webhook_id']);

        $project->refresh();
        $this->assertTrue($project->webhook_enabled);
        $this->assertEquals('bitbucket', $project->webhook_provider);
    }

    public function test_delete_github_webhook(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response([], 204),
        ]);

        $project = Project::factory()->create([
            'repository_url' => 'https://github.com/devflow/test-project.git',
            'webhook_enabled' => true,
            'webhook_provider' => 'github',
            'webhook_id' => '12345',
        ]);

        config(['services.github.token' => 'test-token']);

        $result = $this->service->deleteWebhook($project);

        $this->assertTrue($result);

        $project->refresh();
        $this->assertFalse($project->webhook_enabled);
        $this->assertNull($project->webhook_id);
    }

    public function test_delete_webhook_handles_already_deleted(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response([], 404),
        ]);

        $project = Project::factory()->create([
            'repository_url' => 'https://github.com/devflow/test-project.git',
            'webhook_enabled' => true,
            'webhook_provider' => 'github',
            'webhook_id' => '12345',
        ]);

        config(['services.github.token' => 'test-token']);

        $result = $this->service->deleteWebhook($project);

        // 404 should still return true (webhook already deleted)
        $this->assertTrue($result);
    }

    public function test_delete_webhook_returns_true_when_no_webhook(): void
    {
        $project = Project::factory()->create([
            'webhook_enabled' => false,
            'webhook_id' => null,
        ]);

        $result = $this->service->deleteWebhook($project);

        $this->assertTrue($result);
    }

    public function test_verify_github_signature_valid(): void
    {
        $secret = 'test-secret';
        $payload = '{"action":"push"}';
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $payload);

        $project = Project::factory()->create([
            'webhook_enabled' => true,
            'webhook_provider' => 'github',
            'webhook_secret' => $secret,
        ]);

        $result = $this->service->verifyWebhookSignature($request, $project);

        $this->assertTrue($result);
    }

    public function test_verify_github_signature_invalid(): void
    {
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
        ], '{"action":"push"}');

        $project = Project::factory()->create([
            'webhook_enabled' => true,
            'webhook_provider' => 'github',
            'webhook_secret' => 'test-secret',
        ]);

        $result = $this->service->verifyWebhookSignature($request, $project);

        $this->assertFalse($result);
    }

    public function test_verify_gitlab_signature_valid(): void
    {
        $secret = 'test-secret';

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_GITLAB_TOKEN' => $secret,
        ], '{"object_kind":"push"}');

        $project = Project::factory()->create([
            'webhook_enabled' => true,
            'webhook_provider' => 'gitlab',
            'webhook_secret' => $secret,
        ]);

        $result = $this->service->verifyWebhookSignature($request, $project);

        $this->assertTrue($result);
    }

    public function test_verify_gitlab_signature_invalid(): void
    {
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_GITLAB_TOKEN' => 'wrong-token',
        ], '{"object_kind":"push"}');

        $project = Project::factory()->create([
            'webhook_enabled' => true,
            'webhook_provider' => 'gitlab',
            'webhook_secret' => 'test-secret',
        ]);

        $result = $this->service->verifyWebhookSignature($request, $project);

        $this->assertFalse($result);
    }

    public function test_verify_webhook_returns_false_when_not_configured(): void
    {
        $request = Request::create('/webhook', 'POST');

        $project = Project::factory()->create([
            'webhook_enabled' => false,
            'webhook_secret' => null,
        ]);

        $result = $this->service->verifyWebhookSignature($request, $project);

        $this->assertFalse($result);
    }

    public function test_create_pipeline_file_github(): void
    {
        Process::fake();

        $project = Project::factory()->create([
            'slug' => 'test-project',
            'branch' => 'main',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'github',
            'configuration' => [
                'name' => 'CI/CD',
                'on' => ['push'],
                'jobs' => [],
            ],
        ]);

        $this->service->createPipelineFile($pipeline);

        Process::assertRan('git add .');
        Process::assertRan('git commit -m "Add DevFlow CI/CD pipeline"');
        Process::assertRan('git push origin main');
    }

    public function test_create_pipeline_file_gitlab(): void
    {
        Process::fake();

        $project = Project::factory()->create([
            'slug' => 'test-project',
            'branch' => 'main',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'gitlab',
            'configuration' => [
                'stages' => ['test'],
            ],
        ]);

        $this->service->createPipelineFile($pipeline);

        Process::assertRan('git add .');
        Process::assertRan('git commit -m "Add DevFlow CI/CD pipeline"');
    }

    public function test_update_webhook(): void
    {
        Http::fake([
            'api.github.com/*' => Http::sequence()
                ->push([], 204)  // Delete old webhook
                ->push(['id' => 99999], 201),  // Create new webhook
        ]);

        $project = Project::factory()->create([
            'repository_url' => 'https://github.com/devflow/test-project.git',
            'webhook_enabled' => true,
            'webhook_provider' => 'github',
            'webhook_id' => '12345',
            'webhook_secret' => 'test-secret',
        ]);

        config(['services.github.token' => 'test-token']);

        $result = $this->service->updateWebhook($project);

        $this->assertTrue($result['success']);
        $this->assertEquals('99999', $result['webhook_id']);
    }

    public function test_test_github_webhook(): void
    {
        Http::fake([
            'api.github.com/*/pings' => Http::response([], 204),
        ]);

        $project = Project::factory()->create([
            'repository_url' => 'https://github.com/devflow/test-project.git',
            'webhook_enabled' => true,
            'webhook_provider' => 'github',
            'webhook_id' => '12345',
        ]);

        config(['services.github.token' => 'test-token']);

        $result = $this->service->testWebhook($project);

        $this->assertTrue($result['success']);
        $this->assertEquals('Ping sent successfully', $result['message']);
    }

    public function test_test_webhook_fails_when_not_configured(): void
    {
        $project = Project::factory()->create([
            'webhook_enabled' => false,
            'webhook_id' => null,
        ]);

        $result = $this->service->testWebhook($project);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No webhook configured', $result['error']);
    }

    public function test_get_webhook_deliveries_github(): void
    {
        Http::fake([
            'api.github.com/*/deliveries*' => Http::response([
                [
                    'id' => 1,
                    'event' => 'push',
                    'status' => 'success',
                    'status_code' => 200,
                    'delivered_at' => '2025-01-01T12:00:00Z',
                    'duration' => 1.5,
                ],
                [
                    'id' => 2,
                    'event' => 'pull_request',
                    'status' => 'failed',
                    'status_code' => 500,
                    'delivered_at' => '2025-01-01T11:00:00Z',
                    'duration' => 5.0,
                ],
            ], 200),
        ]);

        $project = Project::factory()->create([
            'repository_url' => 'https://github.com/devflow/test-project.git',
            'webhook_enabled' => true,
            'webhook_provider' => 'github',
            'webhook_id' => '12345',
        ]);

        config(['services.github.token' => 'test-token']);

        $deliveries = $this->service->getWebhookDeliveries($project);

        $this->assertCount(2, $deliveries);
        $this->assertEquals('push', $deliveries[0]['event']);
        $this->assertEquals('success', $deliveries[0]['status']);
    }

    public function test_get_webhook_deliveries_returns_empty_for_non_github(): void
    {
        $project = Project::factory()->create([
            'webhook_enabled' => true,
            'webhook_provider' => 'gitlab',
        ]);

        $deliveries = $this->service->getWebhookDeliveries($project);

        $this->assertEmpty($deliveries);
    }

    public function test_extract_github_owner(): void
    {
        $this->assertEquals('devflow', $this->service->extractGitHubOwner('https://github.com/devflow/test.git'));
        $this->assertEquals('devflow', $this->service->extractGitHubOwner('git@github.com:devflow/test.git'));
    }

    public function test_extract_github_repo(): void
    {
        $this->assertEquals('test', $this->service->extractGitHubRepo('https://github.com/devflow/test.git'));
        $this->assertEquals('test', $this->service->extractGitHubRepo('git@github.com:devflow/test.git'));
    }

    public function test_extract_gitlab_project_id(): void
    {
        $this->assertEquals(urlencode('devflow/test'), $this->service->extractGitLabProjectId('https://gitlab.com/devflow/test.git'));
        $this->assertEquals(urlencode('devflow/test'), $this->service->extractGitLabProjectId('git@gitlab.com:devflow/test.git'));
    }
}
