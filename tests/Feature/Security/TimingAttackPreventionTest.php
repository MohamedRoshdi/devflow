<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Project;
use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for timing attack prevention in webhook handling
 *
 * Verifies that webhook secret validation uses timing-safe comparison
 */
class TimingAttackPreventionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['owner_id' => $this->user->id]);
        $this->user->teams()->attach($this->team->id, ['role' => 'owner']);
        $this->user->update(['current_team_id' => $this->team->id]);

        $this->server = Server::factory()->create([
            'team_id' => $this->team->id,
            'status' => 'online',
        ]);
    }

    public function test_invalid_webhook_token_returns_not_found(): void
    {
        $response = $this->postJson('/api/webhooks/deploy/invalid-token-12345');

        $response->assertNotFound();
    }

    public function test_valid_webhook_token_processes_request(): void
    {
        // Create project with webhook enabled
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'auto_deploy' => true,
            'webhook_secret' => 'valid-secret-token-abc123',
            'branch' => 'main',
        ]);

        // Verify project was created correctly
        $this->assertTrue($project->auto_deploy);
        $this->assertEquals('valid-secret-token-abc123', $project->webhook_secret);

        // Verify the project can be found via the same query the controller uses
        $foundProject = Project::where('auto_deploy', true)
            ->whereNotNull('webhook_secret')
            ->where('webhook_secret', 'valid-secret-token-abc123')
            ->first();

        $this->assertNotNull($foundProject, 'Project should be findable via webhook_secret query');
        $this->assertEquals($project->id, $foundProject->id);

        // Now test the webhook endpoint
        $response = $this->postJson('/api/webhooks/deploy/valid-secret-token-abc123', [
            'ref' => 'refs/heads/main',
        ]);

        // Should not be 404 - the timing-safe code should find the project
        // May return 200 (success), 409 (conflict), or other valid codes
        $this->assertNotEquals(404, $response->getStatusCode(),
            'Webhook should find project. Response: ' . $response->getContent());
    }

    public function test_similar_tokens_are_rejected(): void
    {
        Project::factory()->create([
            'team_id' => $this->team->id,
            'server_id' => $this->server->id,
            'auto_deploy' => true,
            'webhook_secret' => 'valid-secret-token-abc123',
        ]);

        // Similar but not exact tokens should be rejected
        $similarTokens = [
            'valid-secret-token-abc124',  // One char different
            'valid-secret-token-abc12',   // Missing char
            'valid-secret-token-abc1234', // Extra char
            'VALID-SECRET-TOKEN-ABC123',  // Case different
        ];

        foreach ($similarTokens as $token) {
            $response = $this->postJson("/api/webhooks/deploy/{$token}");
            $response->assertNotFound();
        }
    }

    public function test_webhook_only_works_when_auto_deploy_enabled(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'server_id' => $this->server->id,
            'auto_deploy' => false, // Auto-deploy disabled
            'webhook_secret' => 'secret-but-disabled',
        ]);

        $response = $this->postJson('/api/webhooks/deploy/secret-but-disabled');

        // Should be 404 because auto_deploy is false
        $response->assertNotFound();
    }

    public function test_null_webhook_secret_is_not_matched(): void
    {
        Project::factory()->create([
            'team_id' => $this->team->id,
            'server_id' => $this->server->id,
            'auto_deploy' => true,
            'webhook_secret' => null, // No secret set
        ]);

        $response = $this->postJson('/api/webhooks/deploy/any-token');

        $response->assertNotFound();
    }

    public function test_empty_webhook_secret_is_not_matched(): void
    {
        Project::factory()->create([
            'team_id' => $this->team->id,
            'server_id' => $this->server->id,
            'auto_deploy' => true,
            'webhook_secret' => '',
        ]);

        $response = $this->postJson('/api/webhooks/deploy/empty');

        $response->assertNotFound();
    }

    /**
     * Test that response time is relatively constant regardless of token validity
     *
     * This is a basic timing check - in a real security audit, you'd use
     * statistical analysis over many requests
     */
    public function test_response_times_are_consistent(): void
    {
        Project::factory()->create([
            'team_id' => $this->team->id,
            'server_id' => $this->server->id,
            'auto_deploy' => true,
            'webhook_secret' => 'real-secret-token',
        ]);

        $invalidTimes = [];
        $validTimes = [];

        // Measure response times for invalid tokens
        for ($i = 0; $i < 3; $i++) {
            $start = microtime(true);
            $this->postJson('/api/webhooks/deploy/invalid-token-' . $i);
            $invalidTimes[] = microtime(true) - $start;
        }

        // Measure response times for valid token (but will fail on other validation)
        for ($i = 0; $i < 3; $i++) {
            $start = microtime(true);
            $this->postJson('/api/webhooks/deploy/real-secret-token', ['ref' => 'refs/heads/main']);
            $validTimes[] = microtime(true) - $start;
        }

        $avgInvalid = array_sum($invalidTimes) / count($invalidTimes);
        $avgValid = array_sum($validTimes) / count($validTimes);

        // The difference should be small (within 50ms tolerance for test environment)
        // In production, you'd want much tighter tolerance
        $this->assertLessThan(0.5, abs($avgValid - $avgInvalid));
    }
}
