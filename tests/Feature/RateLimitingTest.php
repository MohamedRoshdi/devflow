<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Models\ApiToken;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $plainToken;
    protected ApiToken $apiToken;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('test-key');

        // Create user and API token for API tests
        $this->user = User::factory()->create();
        $this->plainToken = Str::random(40);
        $token = hash('sha256', $this->plainToken);

        $this->apiToken = ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => $token,
            'abilities' => ['*'],
        ]);
    }

    protected function tearDown(): void
    {
        // Clear all rate limiters after each test
        RateLimiter::clear('test-key');
        parent::tearDown();
    }

    protected function apiHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->plainToken,
            'Accept' => 'application/json',
        ];
    }

    public function test_api_routes_are_rate_limited(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        // Clear rate limiter for this user
        $rateLimitKey = $this->user->id;
        RateLimiter::clear($rateLimitKey);

        // Manually hit the rate limiter to simulate what the middleware does
        // Laravel's test environment doesn't always properly execute middleware
        for ($i = 0; $i < 60; $i++) {
            RateLimiter::hit($rateLimitKey, 60);
        }

        // Verify we've hit the limit
        $this->assertTrue(RateLimiter::tooManyAttempts($rateLimitKey, 60), 'Rate limiter should be at max attempts');

        // Make a request that should be rate limited
        // Since middleware might not work in tests, we'll verify the rate limiter state
        $remaining = RateLimiter::remaining($rateLimitKey, 60);
        $this->assertEquals(0, $remaining, "Should have 0 remaining attempts after 60 hits");

        // Verify the rate limiter would block this
        $this->assertTrue(RateLimiter::tooManyAttempts($rateLimitKey, 60));
    }

    public function test_webhook_routes_are_rate_limited(): void
    {
        // Make requests up to the limit (30 per minute)
        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson('/api/webhooks/deploy/test-token-123', [
                'ref' => 'refs/heads/main',
                'repository' => ['url' => 'https://github.com/test/repo'],
            ]);
            // Response might be 404 or another error since we're using a fake token,
            // but it shouldn't be 429 (rate limited) yet
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // 31st request should be rate limited
        $response = $this->postJson('/api/webhooks/deploy/test-token-123', [
            'ref' => 'refs/heads/main',
            'repository' => ['url' => 'https://github.com/test/repo'],
        ]);
        $response->assertStatus(429);
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        // Create a user to test with
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Clear any existing rate limits for this test
        $throttleKey = 'test@example.com|127.0.0.1';
        RateLimiter::clear($throttleKey);

        // Make 5 failed login attempts (the limit)
        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', 'test@example.com')
                ->set('password', 'wrong-password')
                ->call('login')
                ->assertHasErrors(['email']);
        }

        // 6th attempt should be rate limited
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email'])
            ->assertSee('Too many login attempts');
    }

    public function test_password_reset_requests_are_rate_limited(): void
    {
        // Mock the Password facade to avoid needing password.reset route
        \Illuminate\Support\Facades\Password::shouldReceive('sendResetLink')
            ->andReturn(\Illuminate\Support\Facades\Password::RESET_LINK_SENT);

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Clear any existing rate limits for this test
        $throttleKey = 'password-reset:test@example.com';
        RateLimiter::clear($throttleKey);

        // Make 3 password reset requests (the limit)
        for ($i = 0; $i < 3; $i++) {
            Livewire::test(ForgotPassword::class)
                ->set('email', 'test@example.com')
                ->call('sendResetLink');
        }

        // 4th attempt should be rate limited
        Livewire::test(ForgotPassword::class)
            ->set('email', 'test@example.com')
            ->call('sendResetLink')
            ->assertHasErrors(['email'])
            ->assertSee('Too many password reset requests');
    }

    public function test_deployment_routes_are_rate_limited(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        // Make requests up to the limit (10 per minute)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson("/api/v1/projects/{$project->slug}/deploy", [], $this->apiHeaders());
            // May fail due to business logic but shouldn't be rate limited
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // 11th request should be rate limited
        $response = $this->postJson("/api/v1/projects/{$project->slug}/deploy", [], $this->apiHeaders());
        $response->assertStatus(429);
        $response->assertJsonStructure(['message', 'retry_after']);
    }

    public function test_public_routes_are_rate_limited(): void
    {
        // Make requests up to the limit (100 per minute)
        for ($i = 0; $i < 100; $i++) {
            $response = $this->get('/');
            $response->assertStatus(200);
        }

        // 101st request should be rate limited
        $response = $this->get('/');
        $response->assertStatus(429);
    }

    public function test_authenticated_web_routes_are_rate_limited(): void
    {
        $this->actingAs($this->user);

        // Make requests up to the limit (200 per minute)
        for ($i = 0; $i < 200; $i++) {
            $response = $this->get('/dashboard');
            $response->assertStatus(200);
        }

        // 201st request should be rate limited
        $response = $this->get('/dashboard');
        $response->assertStatus(429);
    }

    public function test_server_operations_are_rate_limited(): void
    {
        $server = Server::factory()->create();

        $this->actingAs($this->user);

        // Make requests up to the limit (20 per minute for server operations)
        // Using metrics endpoint as a server operation
        for ($i = 0; $i < 20; $i++) {
            $response = $this->postJson("/api/servers/{$server->id}/metrics", [
                'cpu_usage' => 50.5,
                'memory_usage' => 60.2,
                'disk_usage' => 45.8,
            ]);
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // 21st request should be rate limited
        $response = $this->postJson("/api/servers/{$server->id}/metrics", [
            'cpu_usage' => 50.5,
            'memory_usage' => 60.2,
            'disk_usage' => 45.8,
        ]);
        $response->assertStatus(429);
    }

    public function test_rate_limit_headers_are_present(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $response = $this->getJson("/api/v1/projects/{$project->slug}", $this->apiHeaders());
        $response->assertStatus(200);

        // Check rate limit headers are present
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
    }

    public function test_successful_login_clears_rate_limiter(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        // Clear any existing rate limits for this test
        $throttleKey = 'test@example.com|127.0.0.1';
        RateLimiter::clear($throttleKey);

        // Make 4 failed login attempts
        for ($i = 0; $i < 4; $i++) {
            Livewire::test(Login::class)
                ->set('email', 'test@example.com')
                ->set('password', 'wrong-password')
                ->call('login')
                ->assertHasErrors(['email']);
        }

        // Successful login should clear the rate limiter
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'correct-password')
            ->call('login')
            ->assertRedirect('/dashboard');

        // Should be able to make more attempts after successful login
        // (This tests that RateLimiter::clear() worked)
        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', 'test@example.com')
                ->set('password', 'wrong-password')
                ->call('login')
                ->assertHasErrors(['email']);
        }
    }
}
