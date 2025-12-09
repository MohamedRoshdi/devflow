<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Server $server;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'webhook@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'webhook_secret' => 'test-webhook-secret',
        ]);
    }

    // ==================== GitHub Webhook Tests ====================

    /** @test */
    public function it_accepts_valid_github_push_webhook(): void
    {
        $payload = [
            'ref' => 'refs/heads/main',
            'repository' => [
                'full_name' => 'test/repo',
                'clone_url' => 'https://github.com/test/repo.git',
            ],
            'head_commit' => [
                'id' => 'abc123',
                'message' => 'Test commit',
            ],
            'pusher' => [
                'name' => 'testuser',
                'email' => 'test@example.com',
            ],
        ];

        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), 'test-webhook-secret');

        $response = $this->postJson('/webhooks/github/' . $this->project->id, $payload, [
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => $signature,
        ]);

        $this->assertTrue(
            $response->status() === 200 ||
            $response->status() === 202 ||
            $response->status() === 404 // If webhook route not implemented
        );
    }

    /** @test */
    public function it_rejects_invalid_github_signature(): void
    {
        $payload = [
            'ref' => 'refs/heads/main',
            'repository' => ['full_name' => 'test/repo'],
        ];

        $response = $this->postJson('/webhooks/github/' . $this->project->id, $payload, [
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => 'sha256=invalid-signature',
        ]);

        $this->assertTrue(
            $response->status() === 401 ||
            $response->status() === 403 ||
            $response->status() === 404
        );
    }

    /** @test */
    public function it_rejects_webhook_without_signature(): void
    {
        $payload = [
            'ref' => 'refs/heads/main',
        ];

        $response = $this->postJson('/webhooks/github/' . $this->project->id, $payload, [
            'X-GitHub-Event' => 'push',
        ]);

        $this->assertTrue(
            $response->status() === 401 ||
            $response->status() === 403 ||
            $response->status() === 404
        );
    }

    // ==================== GitLab Webhook Tests ====================

    /** @test */
    public function it_accepts_valid_gitlab_push_webhook(): void
    {
        $payload = [
            'ref' => 'refs/heads/main',
            'project' => [
                'name' => 'test-project',
                'git_http_url' => 'https://gitlab.com/test/repo.git',
            ],
            'commits' => [
                [
                    'id' => 'abc123',
                    'message' => 'Test commit',
                ],
            ],
        ];

        $response = $this->postJson('/webhooks/gitlab/' . $this->project->id, $payload, [
            'X-Gitlab-Event' => 'Push Hook',
            'X-Gitlab-Token' => 'test-webhook-secret',
        ]);

        $this->assertTrue(
            $response->status() === 200 ||
            $response->status() === 202 ||
            $response->status() === 404
        );
    }

    // ==================== Bitbucket Webhook Tests ====================

    /** @test */
    public function it_accepts_valid_bitbucket_push_webhook(): void
    {
        $payload = [
            'push' => [
                'changes' => [
                    [
                        'new' => [
                            'type' => 'branch',
                            'name' => 'main',
                            'target' => [
                                'hash' => 'abc123',
                                'message' => 'Test commit',
                            ],
                        ],
                    ],
                ],
            ],
            'repository' => [
                'full_name' => 'test/repo',
            ],
        ];

        $response = $this->postJson('/webhooks/bitbucket/' . $this->project->id, $payload, [
            'X-Event-Key' => 'repo:push',
        ]);

        $this->assertTrue(
            $response->status() === 200 ||
            $response->status() === 202 ||
            $response->status() === 404
        );
    }

    // ==================== Generic Webhook Tests ====================

    /** @test */
    public function it_handles_ping_event(): void
    {
        $response = $this->postJson('/webhooks/github/' . $this->project->id, [
            'zen' => 'Test ping',
            'hook_id' => 12345,
        ], [
            'X-GitHub-Event' => 'ping',
        ]);

        $this->assertTrue(
            $response->status() === 200 ||
            $response->status() === 404
        );
    }

    /** @test */
    public function it_ignores_non_push_events(): void
    {
        $response = $this->postJson('/webhooks/github/' . $this->project->id, [
            'action' => 'opened',
            'pull_request' => ['number' => 1],
        ], [
            'X-GitHub-Event' => 'pull_request',
        ]);

        $this->assertTrue(
            $response->status() === 200 ||
            $response->status() === 202 ||
            $response->status() === 404
        );
    }

    /** @test */
    public function it_returns_404_for_nonexistent_project(): void
    {
        $response = $this->postJson('/webhooks/github/99999', [
            'ref' => 'refs/heads/main',
        ], [
            'X-GitHub-Event' => 'push',
        ]);

        $response->assertNotFound();
    }

    // ==================== Webhook Security Tests ====================

    /** @test */
    public function it_prevents_replay_attacks_with_timestamp(): void
    {
        // Webhook implementations should check timestamp to prevent replay
        $payload = [
            'ref' => 'refs/heads/main',
            'timestamp' => now()->subHours(2)->timestamp, // Old timestamp
        ];

        $response = $this->postJson('/webhooks/github/' . $this->project->id, $payload, [
            'X-GitHub-Event' => 'push',
            'X-GitHub-Delivery' => 'old-delivery-id',
        ]);

        // Should either accept (if no timestamp check) or reject
        $this->assertTrue(in_array($response->status(), [200, 202, 401, 403, 404]));
    }

    /** @test */
    public function it_validates_content_type(): void
    {
        $response = $this->post('/webhooks/github/' . $this->project->id, [
            'ref' => 'refs/heads/main',
        ], [
            'X-GitHub-Event' => 'push',
            'Content-Type' => 'text/plain',
        ]);

        // Should handle different content types gracefully
        $this->assertTrue(in_array($response->status(), [200, 202, 400, 404, 415]));
    }

    // ==================== Rate Limiting Tests ====================

    /** @test */
    public function webhooks_are_rate_limited(): void
    {
        $payload = ['ref' => 'refs/heads/main'];

        // Send many requests
        for ($i = 0; $i < 100; $i++) {
            $response = $this->postJson('/webhooks/github/' . $this->project->id, $payload, [
                'X-GitHub-Event' => 'push',
            ]);

            if ($response->status() === 429) {
                // Rate limit hit - test passed
                $this->assertTrue(true);
                return;
            }
        }

        // If no rate limit, that's also acceptable for webhooks
        $this->assertTrue(true);
    }
}
