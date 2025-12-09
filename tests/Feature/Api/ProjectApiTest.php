<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Server $server;
    protected string $apiToken;
    protected array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->apiToken = 'test-api-token-123';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $this->apiToken),
            'expires_at' => now()->addDays(30),
        ]);

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Accept' => 'application/json',
        ];
    }

    // ==================== List Projects ====================

    /** @test */
    public function it_can_list_all_projects(): void
    {
        Project::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/projects');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'status', 'created_at'],
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication_to_list_projects(): void
    {
        $response = $this->getJson('/api/v1/projects');

        $response->assertUnauthorized();
    }

    // ==================== Create Project ====================

    /** @test */
    public function it_can_create_a_project(): void
    {
        $projectData = [
            'name' => 'Test Project',
            'repository_url' => 'https://github.com/test/repo.git',
            'branch' => 'main',
            'server_id' => $this->server->id,
            'framework' => 'laravel',
        ];

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/projects', $projectData);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test Project');

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'repository_url' => 'https://github.com/test/repo.git',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_project(): void
    {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/projects', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'repository_url', 'server_id']);
    }

    /** @test */
    public function it_validates_repository_url_format(): void
    {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/projects', [
                'name' => 'Test',
                'repository_url' => 'not-a-url',
                'server_id' => $this->server->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['repository_url']);
    }

    // ==================== Get Single Project ====================

    /** @test */
    public function it_can_get_a_single_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/projects/' . $project->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $project->id);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_project(): void
    {
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/projects/99999');

        $response->assertNotFound();
    }

    // ==================== Update Project ====================

    /** @test */
    public function it_can_update_a_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Original Name',
        ]);

        $response = $this->withHeaders($this->headers)
            ->putJson('/api/v1/projects/' . $project->id, [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
        ]);
    }

    // ==================== Delete Project ====================

    /** @test */
    public function it_can_delete_a_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->deleteJson('/api/v1/projects/' . $project->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    // ==================== Deploy Project ====================

    /** @test */
    public function it_can_trigger_deployment(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/projects/' . $project->id . '/deploy');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'status', 'created_at'],
            ]);

        $this->assertDatabaseHas('deployments', [
            'project_id' => $project->id,
        ]);
    }

    // ==================== List Deployments ====================

    /** @test */
    public function it_can_list_project_deployments(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->count(3)->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/projects/' . $project->id . '/deployments');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }
}
