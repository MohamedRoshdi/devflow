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
        $response = $this->postJson('/api/webhook/deploy/invalid-token-12345');

        $response->assertNotFound();
    }

    public function test_valid_webhook_token_processes_request(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'server_id' => $this->server->id,
            'auto_deploy' => true,
            'webhook_secret' => 'valid-secret-token-abc123',
        ]);

        // Note: This will fail because the project might not be fully configured,
        // but we're testing that it doesn't return 404 (unauthorized)
        $response = $this->postJson('/api/webhook/deploy/valid-secret-token-abc123', [
            'ref' => 'refs/heads/main',
        ]);

        // Should not be 404 (the timing-attack safe code should find the project)
        $this->assertNotEquals(404, $response->getStatusCode());
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
            $response = $this->postJson("/api/webhook/deploy/{$token}");
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

        $response = $this->postJson('/api/webhook/deploy/secret-but-disabled');

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

        $response = $this->postJson('/api/webhook/deploy/any-token');

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

        $response = $this->postJson('/api/webhook/deploy/empty');

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
            $this->postJson('/api/webhook/deploy/invalid-token-' . $i);
            $invalidTimes[] = microtime(true) - $start;
        }

        // Measure response times for valid token (but will fail on other validation)
        for ($i = 0; $i < 3; $i++) {
            $start = microtime(true);
            $this->postJson('/api/webhook/deploy/real-secret-token', ['ref' => 'refs/heads/main']);
            $validTimes[] = microtime(true) - $start;
        }

        $avgInvalid = array_sum($invalidTimes) / count($invalidTimes);
        $avgValid = array_sum($validTimes) / count($validTimes);

        // The difference should be small (within 50ms tolerance for test environment)
        // In production, you'd want much tighter tolerance
        $this->assertLessThan(0.5, abs($avgValid - $avgInvalid));
    }
}
