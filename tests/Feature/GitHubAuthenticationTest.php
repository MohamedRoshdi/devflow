<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GitHubConnection;
use App\Models\User;
use App\Services\GitHubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class GitHubAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_redirect_to_github_generates_state_and_redirects(): void
    {
        $mockService = Mockery::mock(GitHubService::class);
        $mockService->shouldReceive('getAuthUrl')
            ->once()
            ->andReturn('https://github.com/login/oauth/authorize?state=test-state');

        $this->app->instance(GitHubService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->get(route('github.redirect'));

        $response->assertRedirect();
        $this->assertNotNull(Session::get('github_oauth_state'));
    }

    public function test_callback_validates_state_parameter(): void
    {
        Session::put('github_oauth_state', 'valid-state');

        $response = $this->actingAs($this->user)
            ->get(route('github.callback', [
                'state' => 'invalid-state',
                'code' => 'test-code',
            ]));

        $response->assertRedirect(route('settings.github'));
        $response->assertSessionHas('error', 'Invalid state parameter. Please try again.');
    }

    public function test_callback_handles_github_error(): void
    {
        Session::put('github_oauth_state', 'valid-state');

        $response = $this->actingAs($this->user)
            ->get(route('github.callback', [
                'state' => 'valid-state',
                'error' => 'access_denied',
            ]));

        $response->assertRedirect(route('settings.github'));
        $response->assertSessionHas('error', 'GitHub authorization was denied.');
    }

    public function test_callback_requires_authorization_code(): void
    {
        Session::put('github_oauth_state', 'valid-state');

        $response = $this->actingAs($this->user)
            ->get(route('github.callback', [
                'state' => 'valid-state',
            ]));

        $response->assertRedirect(route('settings.github'));
        $response->assertSessionHas('error', 'No authorization code received from GitHub.');
    }

    public function test_successful_callback_creates_github_connection(): void
    {
        Session::put('github_oauth_state', 'valid-state');

        $mockService = Mockery::mock(GitHubService::class);
        $mockService->shouldReceive('handleCallback')
            ->with('test-code')
            ->once()
            ->andReturn([
                'access_token' => 'github-token-123',
                'scope' => 'repo,user',
            ]);

        $mockService->shouldReceive('getUser')
            ->once()
            ->andReturn([
                'id' => 12345,
                'login' => 'testuser',
                'avatar_url' => 'https://github.com/avatar.png',
            ]);

        $mockService->shouldReceive('syncRepositories')
            ->once()
            ->andReturn(true);

        $this->app->instance(GitHubService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->get(route('github.callback', [
                'state' => 'valid-state',
                'code' => 'test-code',
            ]));

        $response->assertRedirect(route('settings.github'));
        $response->assertSessionHas('success', 'Successfully connected to GitHub!');

        $this->assertDatabaseHas('github_connections', [
            'user_id' => $this->user->id,
            'github_user_id' => '12345',
            'github_username' => 'testuser',
            'is_active' => true,
        ]);
    }

    public function test_callback_deactivates_existing_connections(): void
    {
        $existingConnection = GitHubConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Session::put('github_oauth_state', 'valid-state');

        $mockService = Mockery::mock(GitHubService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'access_token' => 'new-token',
                'scope' => 'repo,user',
            ]);

        $mockService->shouldReceive('getUser')
            ->once()
            ->andReturn([
                'id' => 67890,
                'login' => 'newuser',
                'avatar_url' => null,
            ]);

        $mockService->shouldReceive('syncRepositories')
            ->once()
            ->andReturn(true);

        $this->app->instance(GitHubService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->get(route('github.callback', [
                'state' => 'valid-state',
                'code' => 'test-code',
            ]));

        $response->assertRedirect(route('settings.github'));

        $existingConnection->refresh();
        $this->assertFalse($existingConnection->is_active);
    }

    public function test_disconnect_removes_github_connection(): void
    {
        $connection = GitHubConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('github.disconnect'));

        $response->assertRedirect(route('settings.github'));
        $response->assertSessionHas('success', 'GitHub account disconnected successfully.');

        $this->assertDatabaseMissing('github_connections', [
            'id' => $connection->id,
        ]);
    }

    public function test_disconnect_fails_when_no_active_connection(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('github.disconnect'));

        $response->assertRedirect(route('settings.github'));
        $response->assertSessionHas('error', 'No active GitHub connection found.');
    }

    public function test_github_auth_requires_authentication(): void
    {
        $response = $this->get(route('github.redirect'));

        $response->assertRedirect(route('login'));
    }

    public function test_callback_continues_even_if_repo_sync_fails(): void
    {
        Session::put('github_oauth_state', 'valid-state');

        $mockService = Mockery::mock(GitHubService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'access_token' => 'token',
                'scope' => 'repo',
            ]);

        $mockService->shouldReceive('getUser')
            ->once()
            ->andReturn([
                'id' => 99999,
                'login' => 'syncfailuser',
            ]);

        $mockService->shouldReceive('syncRepositories')
            ->once()
            ->andThrow(new \Exception('Sync failed'));

        $this->app->instance(GitHubService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->get(route('github.callback', [
                'state' => 'valid-state',
                'code' => 'test-code',
            ]));

        // Should still redirect successfully even if sync fails
        $response->assertRedirect(route('settings.github'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('github_connections', [
            'user_id' => $this->user->id,
            'github_user_id' => '99999',
        ]);
    }
}
