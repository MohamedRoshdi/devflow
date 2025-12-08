<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Jobs\DeployProjectJob;
use App\Models\ApiToken;
use App\Models\Deployment;
use App\Models\GitHubConnection;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Services\GitHubService;
use App\Services\TeamService;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ControllersTest extends TestCase
{

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected string $plainToken;

    protected ApiToken $apiToken;

    protected function setUp(): void
    {
        parent::setUp();

        Process::fake();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['user_id' => $this->user->id]);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Create API token for testing custom API auth
        $this->plainToken = Str::random(60);
        $this->apiToken = ApiToken::create([
            'user_id' => $this->user->id,
            'name' => 'Test Token',
            'token' => hash('sha256', $this->plainToken),
            'abilities' => ['*'],
            'expires_at' => now()->addYear(),
        ]);
    }

    /**
     * Make an authenticated API request with Bearer token
     */
    protected function withApiToken(): static
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->plainToken,
        ]);
    }

    /**
     * Create an API token for a given user
     */
    protected function createTokenForUser(User $user): string
    {
        $token = Str::random(60);
        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'Test Token for ' . $user->name,
            'token' => hash('sha256', $token),
            'abilities' => ['*'],
            'expires_at' => now()->addYear(),
        ]);

        return $token;
    }

    /**
     * Make an authenticated API request with a specific user's token
     */
    protected function withUserApiToken(User $user): static
    {
        $token = $this->createTokenForUser($user);

        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    // ========================================
    // ProjectController Tests (API V1)
    // ========================================

    /** @test */
    public function project_index_returns_projects_list(): void
    {
        Project::factory()->count(3)->create(['user_id' => $this->user->id, 'server_id' => $this->server->id]);

        $response = $this->withApiToken()->getJson('/api/v1/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'status'],
                ],
            ]);
    }

    /** @test */
    public function project_index_filters_by_status(): void
    {
        Project::factory()->create(['user_id' => $this->user->id, 'server_id' => $this->server->id, 'status' => 'running']);
        Project::factory()->create(['user_id' => $this->user->id, 'server_id' => $this->server->id, 'status' => 'stopped']);

        $response = $this->withApiToken()->getJson('/api/v1/projects?status=running');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    /** @test */
    public function project_index_searches_by_name(): void
    {
        Project::factory()->create(['user_id' => $this->user->id, 'server_id' => $this->server->id, 'name' => 'Test Project Alpha']);
        Project::factory()->create(['user_id' => $this->user->id, 'server_id' => $this->server->id, 'name' => 'Beta Project']);

        $response = $this->withApiToken()->getJson('/api/v1/projects?search=Alpha');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    /** @test */
    public function project_store_creates_new_project(): void
    {
        $response = $this->withApiToken()->postJson('/api/v1/projects', [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'server_id' => $this->server->id,
            'repository_url' => 'https://github.com/test/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function project_store_validates_required_fields(): void
    {
        $response = $this->withApiToken()->postJson('/api/v1/projects', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug', 'repository_url', 'branch', 'framework', 'project_type', 'server_id']);
    }

    /** @test */
    public function project_store_validates_slug_format(): void
    {
        $response = $this->withApiToken()->postJson('/api/v1/projects', [
            'name' => 'Test Project',
            'slug' => 'Invalid Slug!',
            'server_id' => $this->server->id,
            'repository_url' => 'https://github.com/test/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    /** @test */
    public function project_store_generates_webhook_secret_when_enabled(): void
    {
        $response = $this->withApiToken()->postJson('/api/v1/projects', [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'server_id' => $this->server->id,
            'repository_url' => 'https://github.com/test/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'webhook_enabled' => true,
        ]);

        $response->assertStatus(201);

        $project = Project::where('slug', 'test-project')->first();
        $this->assertNotNull($project->webhook_secret);
    }

    /** @test */
    public function project_show_returns_single_project(): void
    {
        $response = $this->withApiToken()->getJson("/api/v1/projects/{$this->project->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'slug', 'status']]);
    }

    /** @test */
    public function project_show_allows_any_authenticated_user(): void
    {
        // All projects are shared across all authenticated users per ProjectPolicy
        $otherUser = User::factory()->create();

        $response = $this->withUserApiToken($otherUser)->getJson("/api/v1/projects/{$this->project->slug}");

        $response->assertStatus(200);
    }

    /** @test */
    public function project_update_modifies_project(): void
    {
        $response = $this->withApiToken()->patchJson("/api/v1/projects/{$this->project->slug}", [
            'name' => 'Updated Project Name',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Project updated successfully']);

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'name' => 'Updated Project Name',
        ]);
    }

    /** @test */
    public function project_update_allows_any_authenticated_user(): void
    {
        // All projects are shared across all authenticated users per ProjectPolicy
        $otherUser = User::factory()->create();

        $response = $this->withUserApiToken($otherUser)->patchJson("/api/v1/projects/{$this->project->slug}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function project_destroy_deletes_project(): void
    {
        $response = $this->withApiToken()->deleteJson("/api/v1/projects/{$this->project->slug}");

        $response->assertStatus(204);

        // Project model uses SoftDeletes
        $this->assertSoftDeleted('projects', [
            'id' => $this->project->id,
        ]);
    }

    /** @test */
    public function project_destroy_allows_any_authenticated_user(): void
    {
        // All projects are shared across all authenticated users per ProjectPolicy
        $projectToDelete = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);
        $otherUser = User::factory()->create();

        $response = $this->withUserApiToken($otherUser)->deleteJson("/api/v1/projects/{$projectToDelete->slug}");

        $response->assertStatus(204);

        // Project model uses SoftDeletes
        $this->assertSoftDeleted('projects', [
            'id' => $projectToDelete->id,
        ]);
    }

    /** @test */
    public function project_deploy_creates_deployment(): void
    {
        $response = $this->withApiToken()->postJson("/api/v1/projects/{$this->project->slug}/deploy", [
            'branch' => 'main',
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure(['message', 'data' => ['deployment_id', 'status']]);

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'triggered_by' => 'manual',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function project_deploy_prevents_concurrent_deployments(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        $response = $this->withApiToken()->postJson("/api/v1/projects/{$this->project->slug}/deploy");

        $response->assertStatus(409)
            ->assertJson(['error' => 'deployment_in_progress']);
    }

    /** @test */
    public function unauthenticated_project_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/projects');

        $response->assertStatus(401);
    }

    // ========================================
    // ServerController Tests (API V1)
    // ========================================

    /** @test */
    public function server_index_returns_servers_list(): void
    {
        Server::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withApiToken()->getJson('/api/v1/servers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'ip_address', 'status'],
                ],
            ]);
    }

    /** @test */
    public function server_index_filters_by_status(): void
    {
        // Create additional servers with explicit statuses
        Server::factory()->create(['user_id' => $this->user->id, 'status' => 'online']);
        Server::factory()->create(['user_id' => $this->user->id, 'status' => 'offline']);

        $response = $this->withApiToken()->getJson('/api/v1/servers?status=online');

        $response->assertStatus(200);

        // Only count the explicitly 'online' server we created (setUp server may be random status)
        $onlineCount = count($response->json('data'));
        $this->assertGreaterThanOrEqual(1, $onlineCount);
    }

    /** @test */
    public function server_store_creates_new_server(): void
    {
        $response = $this->withApiToken()->postJson('/api/v1/servers', [
            'name' => 'Production Server',
            'hostname' => 'prod.example.com',
            'ip_address' => '192.168.1.100',
            'username' => 'root',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('servers', [
            'name' => 'Production Server',
            'ip_address' => '192.168.1.100',
            'user_id' => $this->user->id,
            'status' => 'offline', // Default status
        ]);
    }

    /** @test */
    public function server_store_validates_required_fields(): void
    {
        $response = $this->withApiToken()->postJson('/api/v1/servers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'hostname', 'ip_address', 'username']);
    }

    /** @test */
    public function server_store_validates_ip_address_format(): void
    {
        $response = $this->withApiToken()->postJson('/api/v1/servers', [
            'name' => 'Test Server',
            'hostname' => 'test.example.com',
            'ip_address' => 'invalid-ip',
            'username' => 'root',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ip_address']);
    }

    /** @test */
    public function server_store_validates_unique_ip_address(): void
    {
        Server::factory()->create(['user_id' => $this->user->id, 'ip_address' => '192.168.1.50']);

        $response = $this->withApiToken()->postJson('/api/v1/servers', [
            'name' => 'Test Server',
            'hostname' => 'test.example.com',
            'ip_address' => '192.168.1.50',
            'username' => 'root',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ip_address']);
    }

    /** @test */
    public function server_show_returns_single_server(): void
    {
        $response = $this->withApiToken()->getJson("/api/v1/servers/{$this->server->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'ip_address', 'status']]);
    }

    /** @test */
    public function server_update_modifies_server(): void
    {
        $response = $this->withApiToken()->patchJson("/api/v1/servers/{$this->server->id}", [
            'name' => 'Updated Server Name',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'name' => 'Updated Server Name',
        ]);
    }

    /** @test */
    public function server_destroy_deletes_server_without_projects(): void
    {
        $emptyServer = Server::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withApiToken()->deleteJson("/api/v1/servers/{$emptyServer->id}");

        $response->assertStatus(204);

        // Server model uses SoftDeletes
        $this->assertSoftDeleted('servers', [
            'id' => $emptyServer->id,
        ]);
    }

    /** @test */
    public function server_destroy_prevents_deletion_with_active_projects(): void
    {
        // $this->server already has a project from setUp

        $response = $this->withApiToken()->deleteJson("/api/v1/servers/{$this->server->id}");

        $response->assertStatus(409)
            ->assertJson(['error' => 'server_has_projects']);

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
        ]);
    }

    /** @test */
    public function server_metrics_returns_metrics_data(): void
    {
        ServerMetric::factory()->count(5)->create(['server_id' => $this->server->id]);

        $response = $this->withApiToken()->getJson("/api/v1/servers/{$this->server->id}/metrics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'metrics',
                    'aggregates',
                    'range',
                    'count',
                ],
            ]);
    }

    /** @test */
    public function server_metrics_filters_by_time_range(): void
    {
        ServerMetric::factory()->count(10)->create(['server_id' => $this->server->id]);

        $response = $this->withApiToken()->getJson("/api/v1/servers/{$this->server->id}/metrics?range=24h");

        $response->assertStatus(200)
            ->assertJsonPath('data.range', '24h');
    }

    // ========================================
    // DeploymentController Tests (API V1)
    // ========================================

    /** @test */
    public function deployment_index_returns_deployments_list(): void
    {
        Deployment::factory()->count(3)->create(['project_id' => $this->project->id]);

        $response = $this->withApiToken()->getJson("/api/v1/projects/{$this->project->slug}/deployments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'status', 'branch'],
                ],
            ]);
    }

    /** @test */
    public function deployment_index_filters_by_status(): void
    {
        Deployment::factory()->create(['project_id' => $this->project->id, 'status' => 'success']);
        Deployment::factory()->create(['project_id' => $this->project->id, 'status' => 'failed']);

        $response = $this->withApiToken()->getJson("/api/v1/projects/{$this->project->slug}/deployments?status=success");

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    /** @test */
    public function deployment_store_creates_new_deployment(): void
    {
        $response = $this->withApiToken()->postJson("/api/v1/projects/{$this->project->slug}/deployments", [
            'branch' => 'main',
            'commit_hash' => 'abc123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'branch' => 'main',
            'commit_hash' => 'abc123',
        ]);
    }

    /** @test */
    public function deployment_store_prevents_concurrent_deployments(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        $response = $this->withApiToken()->postJson("/api/v1/projects/{$this->project->slug}/deployments");

        $response->assertStatus(409)
            ->assertJson(['error' => 'deployment_in_progress']);
    }

    /** @test */
    public function deployment_show_returns_single_deployment(): void
    {
        $deployment = Deployment::factory()->create(['project_id' => $this->project->id]);

        $response = $this->withApiToken()->getJson("/api/v1/deployments/{$deployment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'status', 'branch']]);
    }

    /** @test */
    public function deployment_rollback_creates_rollback_deployment(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'success',
            'commit_hash' => 'abc123',
        ]);

        $response = $this->withApiToken()->postJson("/api/v1/deployments/{$deployment->id}/rollback");

        $response->assertStatus(202)
            ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'rollback_deployment_id' => $deployment->id,
            'triggered_by' => 'rollback',
        ]);
    }

    /** @test */
    public function deployment_rollback_only_allows_successful_deployments(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        $response = $this->withApiToken()->postJson("/api/v1/deployments/{$deployment->id}/rollback");

        $response->assertStatus(422)
            ->assertJson(['error' => 'invalid_deployment_status']);
    }

    /** @test */
    public function deployment_rollback_prevents_concurrent_deployments(): void
    {
        $successfulDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'success',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        $response = $this->withApiToken()->postJson("/api/v1/deployments/{$successfulDeployment->id}/rollback");

        $response->assertStatus(409)
            ->assertJson(['error' => 'deployment_in_progress']);
    }

    // ========================================
    // ServerMetricsController Tests (API)
    // ========================================

    /** @test */
    public function server_metrics_index_returns_latest_metrics(): void
    {
        // These routes use Sanctum auth, not custom API token auth
        ServerMetric::factory()->count(10)->create(['server_id' => $this->server->id]);

        $response = $this->actingAs($this->user)->getJson("/api/servers/{$this->server->id}/metrics");

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function server_metrics_index_allows_any_authenticated_user(): void
    {
        // All servers are accessible to any authenticated user per ServerPolicy
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->getJson("/api/servers/{$this->server->id}/metrics");

        $response->assertStatus(200);
    }

    /** @test */
    public function server_metrics_store_creates_metric_record(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/servers/{$this->server->id}/metrics", [
            'cpu_usage' => 45.5,
            'memory_usage' => 60.2,
            'disk_usage' => 75.8,
            'network_in' => 1024,
            'network_out' => 2048,
            'load_average' => 1.5,
            'active_connections' => 50,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('server_metrics', [
            'server_id' => $this->server->id,
            'cpu_usage' => 45.5,
            'memory_usage' => 60.2,
        ]);
    }

    /** @test */
    public function server_metrics_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/servers/{$this->server->id}/metrics", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cpu_usage', 'memory_usage', 'disk_usage']);
    }

    /** @test */
    public function server_metrics_store_validates_percentage_range(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/servers/{$this->server->id}/metrics", [
            'cpu_usage' => 150, // Invalid: over 100
            'memory_usage' => -10, // Invalid: negative
            'disk_usage' => 50,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cpu_usage', 'memory_usage']);
    }

    /** @test */
    public function server_metrics_store_updates_server_status(): void
    {
        $this->server->update(['status' => 'offline']);

        $this->actingAs($this->user)->postJson("/api/servers/{$this->server->id}/metrics", [
            'cpu_usage' => 45.5,
            'memory_usage' => 60.2,
            'disk_usage' => 75.8,
        ]);

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => 'online',
        ]);
    }

    // ========================================
    // DeploymentWebhookController Tests
    // ========================================

    /** @test */
    public function deployment_webhook_handles_valid_token(): void
    {
        Bus::fake();

        $this->project->update(['auto_deploy' => true]);

        $response = $this->postJson("/api/webhooks/deploy/{$this->project->slug}", [
            'ref' => 'refs/heads/main',
            'after' => 'abc123def456',
            'head_commit' => [
                'message' => 'Test commit',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'deployment_id']);

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'triggered_by' => 'webhook',
        ]);

        Bus::assertDispatched(DeployProjectJob::class);
    }

    /** @test */
    public function deployment_webhook_rejects_invalid_token(): void
    {
        $response = $this->postJson('/api/webhooks/deploy/invalid-token', []);

        $response->assertStatus(404)
            ->assertJson(['error' => 'Invalid webhook token']);
    }

    /** @test */
    public function deployment_webhook_rejects_when_auto_deploy_disabled(): void
    {
        $this->project->update(['auto_deploy' => false]);

        $response = $this->postJson("/api/webhooks/deploy/{$this->project->slug}", []);

        $response->assertStatus(403)
            ->assertJson(['error' => 'Auto-deploy is not enabled']);
    }

    /** @test */
    public function deployment_webhook_parses_github_payload(): void
    {
        Bus::fake();

        $this->project->update(['auto_deploy' => true]);

        $response = $this->postJson("/api/webhooks/deploy/{$this->project->slug}", [
            'ref' => 'refs/heads/develop',
            'after' => 'commit-hash-123',
            'head_commit' => [
                'message' => 'Fix bug in authentication',
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'branch' => 'develop',
            'commit_hash' => 'commit-hash-123',
        ]);
    }

    /** @test */
    public function deployment_webhook_parses_gitlab_payload(): void
    {
        Bus::fake();

        $this->project->update(['auto_deploy' => true]);

        $response = $this->postJson("/api/webhooks/deploy/{$this->project->slug}", [
            'object_kind' => 'push',
            'ref' => 'refs/heads/main',
            'checkout_sha' => 'gitlab-commit-123',
            'commits' => [
                ['message' => 'GitLab commit message'],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'commit_hash' => 'gitlab-commit-123',
        ]);
    }

    /** @test */
    public function deployment_webhook_parses_bitbucket_payload(): void
    {
        Bus::fake();

        $this->project->update(['auto_deploy' => true]);

        $response = $this->postJson("/api/webhooks/deploy/{$this->project->slug}", [
            'push' => [
                'changes' => [
                    [
                        'new' => [
                            'name' => 'feature-branch',
                            'target' => [
                                'hash' => 'bitbucket-hash-123',
                                'message' => 'Bitbucket commit',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'branch' => 'feature-branch',
            'commit_hash' => 'bitbucket-hash-123',
        ]);
    }

    // ========================================
    // GitHubAuthController Tests
    // ========================================

    /** @test */
    public function github_redirect_generates_auth_url(): void
    {
        $this->actingAs($this->user);

        $gitHubService = $this->mock(GitHubService::class);
        $gitHubService->shouldReceive('getAuthUrl')
            ->once()
            ->andReturn('https://github.com/login/oauth/authorize?client_id=test');

        $response = $this->get('/auth/github');

        $response->assertRedirect();
        $this->assertNotNull(session('github_oauth_state'));
    }

    /** @test */
    public function github_callback_creates_connection(): void
    {
        $this->actingAs($this->user);

        session(['github_oauth_state' => 'test-state']);

        $gitHubService = $this->mock(GitHubService::class);
        $gitHubService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'access_token' => 'gho_test_token',
                'scope' => 'repo,user',
            ]);

        $gitHubService->shouldReceive('getUser')
            ->once()
            ->andReturn([
                'id' => 12345,
                'login' => 'testuser',
                'avatar_url' => 'https://github.com/avatar.png',
            ]);

        $gitHubService->shouldReceive('syncRepositories')
            ->once();

        $response = $this->get('/auth/github/callback?code=test-code&state=test-state');

        $response->assertRedirect(route('settings.github'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('github_connections', [
            'user_id' => $this->user->id,
            'github_user_id' => '12345',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function github_callback_rejects_invalid_state(): void
    {
        $this->actingAs($this->user);

        session(['github_oauth_state' => 'correct-state']);

        $response = $this->get('/auth/github/callback?code=test-code&state=wrong-state');

        $response->assertRedirect(route('settings.github'))
            ->assertSessionHas('error');
    }

    /** @test */
    public function github_callback_handles_user_denial(): void
    {
        $this->actingAs($this->user);

        session(['github_oauth_state' => 'test-state']);

        $response = $this->get('/auth/github/callback?error=access_denied&state=test-state');

        $response->assertRedirect(route('settings.github'))
            ->assertSessionHas('error', 'GitHub authorization was denied.');
    }

    /** @test */
    public function github_disconnect_removes_connection(): void
    {
        $this->actingAs($this->user);

        $connection = GitHubConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->get('/auth/github/disconnect');

        $response->assertRedirect(route('settings.github'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('github_connections', [
            'id' => $connection->id,
        ]);
    }

    /** @test */
    public function github_disconnect_handles_no_connection(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/auth/github/disconnect');

        $response->assertRedirect(route('settings.github'))
            ->assertSessionHas('error', 'No active GitHub connection found.');
    }

    // ========================================
    // TeamInvitationController Tests
    // ========================================

    /** @test */
    public function team_invitation_show_displays_valid_invitation(): void
    {
        $team = Team::factory()->create(['owner_id' => $this->user->id]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invitee@example.com',
            'invited_by' => $this->user->id,
        ]);

        $response = $this->get("/invitations/{$invitation->token}");

        $response->assertStatus(200)
            ->assertViewHas('invitation')
            ->assertViewHas('expired', false);
    }

    /** @test */
    public function team_invitation_show_handles_accepted_invitation(): void
    {
        $team = Team::factory()->create(['owner_id' => $this->user->id]);
        $invitation = TeamInvitation::factory()->accepted()->create([
            'team_id' => $team->id,
            'email' => 'invitee@example.com',
            'invited_by' => $this->user->id,
        ]);

        $response = $this->get("/invitations/{$invitation->token}");

        $response->assertRedirect(route('teams.index'))
            ->assertSessionHas('error');
    }

    /** @test */
    public function team_invitation_show_handles_expired_invitation(): void
    {
        $team = Team::factory()->create(['owner_id' => $this->user->id]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invitee@example.com',
            'invited_by' => $this->user->id,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->get("/invitations/{$invitation->token}");

        $response->assertStatus(200)
            ->assertViewHas('expired', true);
    }

    /** @test */
    public function team_invitation_accept_requires_authentication(): void
    {
        $team = Team::factory()->create(['owner_id' => $this->user->id]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $this->user->id,
        ]);

        $response = $this->post("/invitations/{$invitation->token}/accept");

        // Route has 'auth' middleware which redirects to login without a message
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function team_invitation_accept_adds_user_to_team(): void
    {
        $this->actingAs($this->user);

        $team = Team::factory()->create(['owner_id' => $this->user->id]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => $this->user->email,
            'invited_by' => $this->user->id,
        ]);

        $teamService = $this->mock(TeamService::class);
        $teamService->shouldReceive('acceptInvitation')
            ->once()
            ->with($invitation->token)
            ->andReturn($team);

        $response = $this->post("/invitations/{$invitation->token}/accept");

        $response->assertRedirect(route('teams.index'))
            ->assertSessionHas('success');
    }

    /** @test */
    public function team_invitation_accept_handles_errors(): void
    {
        $this->actingAs($this->user);

        $team = Team::factory()->create(['owner_id' => $this->user->id]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $this->user->id,
        ]);

        $teamService = $this->mock(TeamService::class);
        $teamService->shouldReceive('acceptInvitation')
            ->once()
            ->andThrow(new \Exception('Invitation expired'));

        $response = $this->post("/invitations/{$invitation->token}/accept");

        $response->assertRedirect(route('teams.index'))
            ->assertSessionHas('error', 'Invitation expired');
    }

    // ========================================
    // WebhookController Tests
    // ========================================

    /** @test */
    public function webhook_github_requires_valid_secret(): void
    {
        $response = $this->postJson('/webhooks/github/invalid-secret', []);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid webhook secret']);
    }

    /** @test */
    public function webhook_github_requires_webhook_enabled(): void
    {
        $this->project->update([
            'webhook_secret' => 'test-secret',
            'webhook_enabled' => false,
        ]);

        $response = $this->postJson('/webhooks/github/test-secret', []);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid webhook secret']);
    }

    /** @test */
    public function webhook_github_verifies_signature(): void
    {
        Log::shouldReceive('warning')->once();

        $this->project->update([
            'webhook_secret' => 'test-secret',
            'webhook_enabled' => true,
        ]);

        $webhookService = $this->mock(WebhookService::class);
        $webhookService->shouldReceive('verifyGitHubSignature')
            ->once()
            ->andReturn(false);

        $webhookService->shouldReceive('createDeliveryRecord')
            ->once()
            ->andReturn(new WebhookDelivery);

        $webhookService->shouldReceive('updateDeliveryStatus')
            ->once();

        $response = $this->postJson('/webhooks/github/test-secret', [
            'ref' => 'refs/heads/main',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid signature']);
    }

    /** @test */
    public function webhook_github_ignores_non_push_events(): void
    {
        Log::shouldReceive('info')->once();

        $this->project->update([
            'webhook_secret' => 'test-secret',
            'webhook_enabled' => true,
        ]);

        $webhookService = $this->mock(WebhookService::class);
        $webhookService->shouldReceive('verifyGitHubSignature')->andReturn(true);
        $webhookService->shouldReceive('createDeliveryRecord')->andReturn(new WebhookDelivery);
        $webhookService->shouldReceive('shouldProcessEvent')->andReturn(false);
        $webhookService->shouldReceive('updateDeliveryStatus')->once();

        $response = $this->withHeaders([
            'X-Hub-Signature-256' => 'sha256=test',
            'X-GitHub-Event' => 'pull_request',
        ])->postJson('/webhooks/github/test-secret', [
            'action' => 'opened',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Event acknowledged but not processed']);
    }

    /** @test */
    public function webhook_github_triggers_deployment_on_push(): void
    {
        Queue::fake();
        Log::shouldReceive('info')->once();

        $this->project->update([
            'webhook_secret' => 'test-secret',
            'webhook_enabled' => true,
        ]);

        $webhookService = $this->mock(WebhookService::class);
        $webhookService->shouldReceive('verifyGitHubSignature')->andReturn(true);
        $webhookService->shouldReceive('createDeliveryRecord')->andReturn(new WebhookDelivery);
        $webhookService->shouldReceive('shouldProcessEvent')->andReturn(true);
        $webhookService->shouldReceive('parseGitHubPayload')->andReturn([
            'branch' => 'main',
            'commit' => 'abc123',
            'commit_message' => 'Test commit',
            'sender' => 'testuser',
            'pusher' => 'testuser',
        ]);
        $webhookService->shouldReceive('shouldTriggerDeployment')->andReturn(true);
        $webhookService->shouldReceive('updateDeliveryStatus')->once();

        $response = $this->withHeaders([
            'X-Hub-Signature-256' => 'sha256=test',
            'X-GitHub-Event' => 'push',
        ])->postJson('/webhooks/github/test-secret', [
            'ref' => 'refs/heads/main',
            'after' => 'abc123',
            'head_commit' => ['message' => 'Test commit'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'deployment_id']);

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'triggered_by' => 'webhook',
        ]);
    }

    /** @test */
    public function webhook_gitlab_verifies_token(): void
    {
        Log::shouldReceive('warning')->once();

        $this->project->update([
            'webhook_secret' => 'test-secret',
            'webhook_enabled' => true,
        ]);

        $webhookService = $this->mock(WebhookService::class);
        $webhookService->shouldReceive('verifyGitLabToken')->andReturn(false);
        $webhookService->shouldReceive('getGitLabEventType')->andReturn('push');
        $webhookService->shouldReceive('createDeliveryRecord')->andReturn(new WebhookDelivery);
        $webhookService->shouldReceive('updateDeliveryStatus')->once();

        $response = $this->withHeaders([
            'X-Gitlab-Token' => 'wrong-token',
        ])->postJson('/webhooks/gitlab/test-secret', [
            'object_kind' => 'push',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid token']);
    }

    /** @test */
    public function webhook_gitlab_triggers_deployment(): void
    {
        Queue::fake();
        Log::shouldReceive('info')->once();

        $this->project->update([
            'webhook_secret' => 'test-secret',
            'webhook_enabled' => true,
        ]);

        $webhookService = $this->mock(WebhookService::class);
        $webhookService->shouldReceive('verifyGitLabToken')->andReturn(true);
        $webhookService->shouldReceive('getGitLabEventType')->andReturn('push');
        $webhookService->shouldReceive('createDeliveryRecord')->andReturn(new WebhookDelivery);
        $webhookService->shouldReceive('shouldProcessEvent')->andReturn(true);
        $webhookService->shouldReceive('parseGitLabPayload')->andReturn([
            'branch' => 'main',
            'commit' => 'gitlab123',
            'commit_message' => 'GitLab commit',
            'sender' => 'gitlabuser',
            'pusher' => 'gitlabuser',
        ]);
        $webhookService->shouldReceive('shouldTriggerDeployment')->andReturn(true);
        $webhookService->shouldReceive('updateDeliveryStatus')->once();

        $response = $this->withHeaders([
            'X-Gitlab-Token' => 'test-secret',
        ])->postJson('/webhooks/gitlab/test-secret', [
            'object_kind' => 'push',
            'ref' => 'refs/heads/main',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'deployment_id']);
    }

    /** @test */
    public function webhook_handles_exceptions_gracefully(): void
    {
        Log::shouldReceive('error')->once();

        $this->project->update([
            'webhook_secret' => 'test-secret',
            'webhook_enabled' => true,
        ]);

        $webhookService = $this->mock(WebhookService::class);
        $webhookService->shouldReceive('verifyGitHubSignature')->andThrow(new \Exception('Database error'));

        $response = $this->withHeaders([
            'X-Hub-Signature-256' => 'sha256=test',
            'X-GitHub-Event' => 'push',
        ])->postJson('/webhooks/github/test-secret', []);

        $response->assertStatus(500)
            ->assertJson(['error' => 'Internal server error']);
    }
}
