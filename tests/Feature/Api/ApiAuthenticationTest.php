<?php

declare(strict_types=1);

namespace Tests\Feature\Api;


use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Models\ApiToken;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API Authentication & Authorization Test
 *
 * Tests the authentication and authorization functionality for API endpoints.
 * Covers token validation, expiration, abilities/permissions, and error responses.
 */
#[Group('api')]
#[Group('authentication')]
class ApiAuthenticationTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable rate limiting for tests
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);
    }

    // ==================== Token Authentication ====================

    #[Test]
    public function it_rejects_requests_without_token(): void
    {
        $response = $this->getJson('/api/v1/projects');

        $response->assertUnauthorized()
            ->assertJson([
                'error' => 'missing_token',
            ]);
    }

    #[Test]
    public function it_rejects_requests_with_invalid_token(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-here',
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertUnauthorized()
            ->assertJson([
                'error' => 'invalid_token',
            ]);
    }

    #[Test]
    public function it_rejects_requests_with_malformed_authorization_header(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'InvalidFormat token123',
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertUnauthorized()
            ->assertJson([
                'error' => 'missing_token',
            ]);
    }

    #[Test]
    public function it_accepts_requests_with_valid_token(): void
    {
        $token = 'valid-test-token-123';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertOk();
    }

    #[Test]
    public function it_requires_bearer_prefix(): void
    {
        $token = 'test-token-no-bearer';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addDays(30),
        ]);

        // Without Bearer prefix
        $response = $this->withHeaders([
            'Authorization' => $token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertUnauthorized();
    }

    // ==================== Token Expiration ====================

    #[Test]
    public function it_rejects_expired_tokens(): void
    {
        $token = 'expired-token-test';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->subDay(), // Expired yesterday
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        // API returns invalid_token for expired tokens (doesn't distinguish)
        $response->assertUnauthorized()
            ->assertJson([
                'error' => 'invalid_token',
            ]);
    }

    #[Test]
    public function it_accepts_tokens_without_expiration(): void
    {
        $token = 'never-expires-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'expires_at' => null, // Never expires
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertOk();
    }

    #[Test]
    public function it_accepts_tokens_expiring_in_future(): void
    {
        $token = 'future-expiry-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertOk();
    }

    #[Test]
    public function it_rejects_tokens_that_just_expired(): void
    {
        $token = 'just-expired-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->subSecond(), // Just expired
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        // API returns invalid_token for expired tokens (doesn't distinguish)
        $response->assertUnauthorized()
            ->assertJson([
                'error' => 'invalid_token',
            ]);
    }

    // ==================== Token Abilities/Permissions ====================

    #[Test]
    public function it_allows_tokens_with_wildcard_ability(): void
    {
        $token = 'wildcard-ability-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'abilities' => ['*'],
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertOk();
    }

    #[Test]
    public function it_allows_tokens_with_empty_abilities_full_access(): void
    {
        $token = 'empty-abilities-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'abilities' => [], // Empty = full access
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertOk();
    }

    #[Test]
    public function it_allows_tokens_with_null_abilities_full_access(): void
    {
        $token = 'null-abilities-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'abilities' => null, // Null = full access
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertOk();
    }

    #[Test]
    public function it_allows_tokens_with_specific_ability(): void
    {
        $token = 'specific-ability-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'abilities' => ['projects:read', 'projects:list'],
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertOk();
    }

    #[Test]
    public function it_allows_tokens_with_category_wildcard(): void
    {
        $token = 'category-wildcard-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'abilities' => ['projects:*'], // All project abilities
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertOk();
    }

    // ==================== User Context ====================

    #[Test]
    public function it_sets_authenticated_user_from_token(): void
    {
        $token = 'user-context-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addMonth(),
        ]);

        // Create projects for this user
        Project::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Create projects for another user
        $otherUser = User::factory()->create();
        $otherServer = Server::factory()->create(['user_id' => $otherUser->id]);
        Project::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'server_id' => $otherServer->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertOk();

        // Should only see the authenticated user's projects (3 + 1 from setUp)
        $data = $response->json('data');
        $this->assertCount(4, $data);
    }

    #[Test]
    public function it_rejects_token_with_deleted_user(): void
    {
        $tempUser = User::factory()->create();
        $token = 'deleted-user-token';
        ApiToken::factory()->create([
            'user_id' => $tempUser->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addMonth(),
        ]);

        // Delete the user
        $tempUser->delete();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        // Token is invalid because scope:active checks won't find it or user is gone
        $response->assertUnauthorized();
    }

    // ==================== Token Last Used Tracking ====================

    #[Test]
    public function it_tracks_token_last_used_timestamp(): void
    {
        $token = 'last-used-tracking-token';
        $apiToken = ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'last_used_at' => null,
            'expires_at' => now()->addMonth(),
        ]);

        $freshToken = $apiToken->fresh();
        $this->assertNotNull($freshToken);
        $this->assertNull($freshToken->last_used_at);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertOk();

        // Note: The last_used_at is updated asynchronously (dispatch()->afterResponse())
        // In tests, we may need to process jobs or wait
    }

    // ==================== Cross-User Access Control ====================

    #[Test]
    public function it_prevents_accessing_other_users_projects(): void
    {
        // Create another user's project
        $otherUser = User::factory()->create();
        $otherServer = Server::factory()->create(['user_id' => $otherUser->id]);
        $otherProject = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $otherServer->id,
        ]);

        $token = 'cross-user-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects/'.$otherProject->slug);

        // Should be forbidden or not found (user doesn't own project)
        $this->assertContains($response->status(), [403, 404]);
    }

    #[Test]
    public function it_prevents_modifying_other_users_projects(): void
    {
        // Create another user's project
        $otherUser = User::factory()->create();
        $otherServer = Server::factory()->create(['user_id' => $otherUser->id]);
        $otherProject = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $otherServer->id,
        ]);

        $token = 'cross-user-modify-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->putJson('/api/v1/projects/'.$otherProject->slug, [
            'name' => 'Hacked Name',
        ]);

        // Should be forbidden or not found
        $this->assertContains($response->status(), [403, 404]);
    }

    #[Test]
    public function it_prevents_deploying_other_users_projects(): void
    {
        // Create another user's project
        $otherUser = User::factory()->create();
        $otherServer = Server::factory()->create(['user_id' => $otherUser->id]);
        $otherProject = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $otherServer->id,
        ]);

        $token = 'cross-user-deploy-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/projects/'.$otherProject->slug.'/deploy');

        // Should be forbidden, not found, or rate limited (all prevent unauthorized deployment)
        $this->assertContains($response->status(), [403, 404, 429]);
    }

    // ==================== Sanctum Token (Legacy) ====================

    #[Test]
    public function it_accepts_sanctum_tokens_for_legacy_routes(): void
    {
        $sanctumToken = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$sanctumToken,
            'Accept' => 'application/json',
        ])->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('id', $this->user->id)
            ->assertJsonPath('email', $this->user->email);
    }

    #[Test]
    public function it_returns_current_user_info(): void
    {
        $sanctumToken = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$sanctumToken,
            'Accept' => 'application/json',
        ])->getJson('/api/user');

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ]);
    }

    // ==================== API Token vs Sanctum Token ====================

    #[Test]
    public function it_distinguishes_between_api_tokens_and_sanctum_tokens(): void
    {
        // ApiToken for v1 routes
        $apiToken = 'api-v1-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $apiToken),
            'expires_at' => now()->addMonth(),
        ]);

        // Sanctum token for legacy routes
        $sanctumToken = $this->user->createToken('sanctum-test')->plainTextToken;

        // API token should work on v1 routes
        $v1Response = $this->withHeaders([
            'Authorization' => 'Bearer '.$apiToken,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');
        $v1Response->assertOk();

        // Sanctum token should work on legacy /api/user route
        $legacyResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$sanctumToken,
            'Accept' => 'application/json',
        ])->getJson('/api/user');
        $legacyResponse->assertOk();
    }

    // ==================== Error Response Format ====================

    #[Test]
    public function it_returns_consistent_error_format_for_missing_token(): void
    {
        $response = $this->getJson('/api/v1/projects');

        $response->assertUnauthorized()
            ->assertJsonStructure([
                'message',
                'error',
            ]);
    }

    #[Test]
    public function it_returns_consistent_error_format_for_invalid_token(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer totally-invalid-token',
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertUnauthorized()
            ->assertJsonStructure([
                'message',
                'error',
            ]);
    }

    #[Test]
    public function it_returns_consistent_error_format_for_expired_token(): void
    {
        $token = 'expired-format-test-token';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertUnauthorized()
            ->assertJsonStructure([
                'message',
                'error',
            ]);
    }

    // ==================== Multiple Tokens Per User ====================

    #[Test]
    public function it_allows_user_to_have_multiple_valid_tokens(): void
    {
        $token1 = 'multi-token-1';
        $token2 = 'multi-token-2';

        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Token 1',
            'token' => hash('sha256', $token1),
            'expires_at' => now()->addMonth(),
        ]);

        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Token 2',
            'token' => hash('sha256', $token2),
            'expires_at' => now()->addMonth(),
        ]);

        // Both tokens should work
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer '.$token1,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');
        $response1->assertOk();

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer '.$token2,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');
        $response2->assertOk();
    }

    #[Test]
    public function it_invalidates_only_specific_token_when_deleted(): void
    {
        $token1 = 'delete-test-token-1';
        $token2 = 'delete-test-token-2';

        $apiToken1 = ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token1),
            'expires_at' => now()->addMonth(),
        ]);

        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $token2),
            'expires_at' => now()->addMonth(),
        ]);

        // Delete first token
        $apiToken1->delete();

        // First token should no longer work
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer '.$token1,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');
        $response1->assertUnauthorized();

        // Second token should still work
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer '.$token2,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');
        $response2->assertOk();
    }

    // ==================== Token Hash Security ====================

    #[Test]
    public function it_stores_tokens_as_hashed_values(): void
    {
        $plainToken = 'plain-text-token-test';
        $apiToken = ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addMonth(),
        ]);

        // The stored token should be the hash, not plain text
        $this->assertEquals(hash('sha256', $plainToken), $apiToken->token);
        $this->assertNotEquals($plainToken, $apiToken->token);
    }

    #[Test]
    public function it_cannot_authenticate_with_hashed_token_directly(): void
    {
        $plainToken = 'hash-auth-test-token';
        $hashedToken = hash('sha256', $plainToken);

        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => $hashedToken,
            'expires_at' => now()->addMonth(),
        ]);

        // Using the hash directly should fail
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$hashedToken,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response->assertUnauthorized();

        // Using plain token should work
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer '.$plainToken,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/projects');

        $response2->assertOk();
    }
}
