<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\GitHubConnection;
use App\Models\GitHubRepository;
use App\Models\User;
use App\Services\GitHubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GitHubServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GitHubService $githubService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->githubService = new GitHubService;

        // Set up GitHub config
        config([
            'services.github.client_id' => 'test-client-id',
            'services.github.client_secret' => 'test-client-secret',
            'services.github.redirect' => 'https://devflow.test/github/callback',
            'services.github.scopes' => 'repo,user,admin:repo_hook',
        ]);
    }

    /** @test */
    public function it_generates_correct_github_oauth_authorization_url(): void
    {
        $state = 'random-state-string';

        $url = $this->githubService->getAuthUrl($state);

        $this->assertStringStartsWith('https://github.com/login/oauth/authorize', $url);
        $this->assertStringContainsString('client_id=test-client-id', $url);
        $this->assertStringContainsString('redirect_uri='.urlencode('https://devflow.test/github/callback'), $url);
        $this->assertStringContainsString('scope=repo%2Cuser%2Cadmin%3Arepo_hook', $url);
        $this->assertStringContainsString('state='.$state, $url);
    }

    /** @test */
    public function it_handles_oauth_callback_successfully(): void
    {
        Http::fake([
            'github.com/login/oauth/access_token' => Http::response(
                'access_token=gho_test_token_12345&token_type=bearer&scope=repo,user',
                200
            ),
        ]);

        $result = $this->githubService->handleCallback('auth-code-123');

        $this->assertEquals('gho_test_token_12345', $result['access_token']);
        $this->assertEquals('bearer', $result['token_type']);
        $this->assertEquals('repo,user', $result['scope']);
    }

    /** @test */
    public function it_handles_oauth_callback_with_missing_token_type(): void
    {
        Http::fake([
            'github.com/login/oauth/access_token' => Http::response(
                'access_token=gho_test_token&scope=repo',
                200
            ),
        ]);

        $result = $this->githubService->handleCallback('auth-code-123');

        $this->assertEquals('gho_test_token', $result['access_token']);
        $this->assertEquals('bearer', $result['token_type']); // Default value
        $this->assertEquals('repo', $result['scope']);
    }

    /** @test */
    public function it_throws_exception_when_oauth_callback_fails_to_obtain_access_token(): void
    {
        Http::fake([
            'github.com/login/oauth/access_token' => Http::response(
                'error=bad_verification_code&error_description=The+code+passed+is+incorrect',
                200
            ),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to obtain access token from GitHub');

        $this->githubService->handleCallback('invalid-code');
    }

    /** @test */
    public function it_throws_exception_when_oauth_request_fails(): void
    {
        Http::fake([
            'github.com/login/oauth/access_token' => Http::response('', 500),
        ]);

        $this->expectException(\Exception::class);

        $this->githubService->handleCallback('auth-code-123');
    }

    /** @test */
    public function it_refreshes_access_token_successfully(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'old_token',
            'refresh_token' => 'refresh_token_123',
            'token_expires_at' => now()->subHour(),
        ]);

        Http::fake([
            'github.com/login/oauth/access_token' => Http::response(
                'access_token=new_token_456&refresh_token=new_refresh_789&expires_in=3600',
                200
            ),
        ]);

        $this->githubService->refreshToken($connection);

        $connection->refresh();

        $this->assertEquals('new_token_456', $connection->access_token);
        $this->assertEquals('new_refresh_789', $connection->refresh_token);
        $this->assertNotNull($connection->token_expires_at);
        $this->assertTrue($connection->token_expires_at->isFuture());
    }

    /** @test */
    public function it_refreshes_token_without_new_refresh_token(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'old_token',
            'refresh_token' => 'refresh_token_123',
            'token_expires_at' => now()->subHour(),
        ]);

        Http::fake([
            'github.com/login/oauth/access_token' => Http::response(
                'access_token=new_token_456&expires_in=7200',
                200
            ),
        ]);

        $this->githubService->refreshToken($connection);

        $connection->refresh();

        $this->assertEquals('new_token_456', $connection->access_token);
        $this->assertEquals('refresh_token_123', $connection->refresh_token); // Unchanged
    }

    /** @test */
    public function it_throws_exception_when_refreshing_token_without_refresh_token(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'old_token',
            'refresh_token' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No refresh token available');

        $this->githubService->refreshToken($connection);
    }

    /** @test */
    public function it_gets_authenticated_user_information(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user' => Http::response([
                'login' => 'testuser',
                'id' => 12345,
                'avatar_url' => 'https://avatars.githubusercontent.com/u/12345',
                'name' => 'Test User',
                'email' => 'test@example.com',
            ], 200),
        ]);

        $result = $this->githubService->getUser($connection);

        $this->assertEquals('testuser', $result['login']);
        $this->assertEquals(12345, $result['id']);
        $this->assertEquals('Test User', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    /** @test */
    public function it_lists_repositories_for_authenticated_user(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user/repos*' => Http::response([
                [
                    'id' => 1,
                    'name' => 'repo1',
                    'full_name' => 'testuser/repo1',
                    'private' => false,
                    'description' => 'First repository',
                    'default_branch' => 'main',
                    'clone_url' => 'https://github.com/testuser/repo1.git',
                    'ssh_url' => 'git@github.com:testuser/repo1.git',
                    'html_url' => 'https://github.com/testuser/repo1',
                    'language' => 'PHP',
                    'stargazers_count' => 10,
                    'forks_count' => 5,
                ],
                [
                    'id' => 2,
                    'name' => 'repo2',
                    'full_name' => 'testuser/repo2',
                    'private' => true,
                    'description' => null,
                    'default_branch' => 'master',
                    'clone_url' => 'https://github.com/testuser/repo2.git',
                    'ssh_url' => 'git@github.com:testuser/repo2.git',
                    'html_url' => 'https://github.com/testuser/repo2',
                    'language' => 'JavaScript',
                    'stargazers_count' => 3,
                    'forks_count' => 1,
                ],
            ], 200),
        ]);

        $result = $this->githubService->listRepositories($connection);

        $this->assertCount(2, $result);
        $this->assertEquals('repo1', $result[0]['name']);
        $this->assertEquals('testuser/repo2', $result[1]['full_name']);
        $this->assertTrue($result[1]['private']);
    }

    /** @test */
    public function it_lists_repositories_with_pagination_parameters(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user/repos*' => Http::response([], 200),
        ]);

        $this->githubService->listRepositories($connection, perPage: 50, page: 2);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.github.com/user/repos' &&
                $request['per_page'] === 50 &&
                $request['page'] === 2 &&
                $request['sort'] === 'updated' &&
                $request['direction'] === 'desc';
        });
    }

    /** @test */
    public function it_gets_detailed_repository_information(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo' => Http::response([
                'id' => 123,
                'name' => 'test-repo',
                'full_name' => 'testuser/test-repo',
                'description' => 'A test repository',
                'private' => false,
                'default_branch' => 'main',
                'language' => 'PHP',
                'stargazers_count' => 42,
                'forks_count' => 7,
                'open_issues_count' => 3,
            ], 200),
        ]);

        $result = $this->githubService->getRepository($connection, 'testuser/test-repo');

        $this->assertEquals(123, $result['id']);
        $this->assertEquals('test-repo', $result['name']);
        $this->assertEquals('A test repository', $result['description']);
        $this->assertEquals(42, $result['stargazers_count']);
    }

    /** @test */
    public function it_lists_branches_for_a_repository(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo/branches' => Http::response([
                [
                    'name' => 'main',
                    'commit' => [
                        'sha' => 'abc123',
                        'url' => 'https://api.github.com/repos/testuser/test-repo/commits/abc123',
                    ],
                    'protected' => true,
                ],
                [
                    'name' => 'develop',
                    'commit' => [
                        'sha' => 'def456',
                        'url' => 'https://api.github.com/repos/testuser/test-repo/commits/def456',
                    ],
                    'protected' => false,
                ],
            ], 200),
        ]);

        $result = $this->githubService->listBranches($connection, 'testuser/test-repo');

        $this->assertCount(2, $result);
        $this->assertEquals('main', $result[0]['name']);
        $this->assertEquals('develop', $result[1]['name']);
        $this->assertTrue($result[0]['protected']);
    }

    /** @test */
    public function it_lists_commits_for_a_repository_branch(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo/commits*' => Http::response([
                [
                    'sha' => 'abc123def456',
                    'commit' => [
                        'message' => 'feat: Add new feature',
                        'author' => [
                            'name' => 'John Doe',
                            'email' => 'john@example.com',
                            'date' => '2024-01-01T12:00:00Z',
                        ],
                    ],
                    'author' => [
                        'login' => 'johndoe',
                        'avatar_url' => 'https://avatars.githubusercontent.com/u/123',
                    ],
                ],
                [
                    'sha' => 'def456ghi789',
                    'commit' => [
                        'message' => 'fix: Fix bug',
                        'author' => [
                            'name' => 'Jane Smith',
                            'email' => 'jane@example.com',
                            'date' => '2024-01-01T11:00:00Z',
                        ],
                    ],
                    'author' => [
                        'login' => 'janesmith',
                        'avatar_url' => 'https://avatars.githubusercontent.com/u/456',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->githubService->listCommits($connection, 'testuser/test-repo', 'main', 10);

        $this->assertCount(2, $result);
        $this->assertEquals('abc123def456', $result[0]['sha']);
        $this->assertEquals('feat: Add new feature', $result[0]['commit']['message']);
        $this->assertEquals('John Doe', $result[0]['commit']['author']['name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.github.com/repos/testuser/test-repo/commits' &&
                $request['sha'] === 'main' &&
                $request['per_page'] === 10;
        });
    }

    /** @test */
    public function it_syncs_repositories_to_database_successfully(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user/repos*' => Http::sequence()
                ->push([
                    [
                        'id' => 1,
                        'name' => 'repo1',
                        'full_name' => 'testuser/repo1',
                        'description' => 'First repository',
                        'private' => false,
                        'default_branch' => 'main',
                        'clone_url' => 'https://github.com/testuser/repo1.git',
                        'ssh_url' => 'git@github.com:testuser/repo1.git',
                        'html_url' => 'https://github.com/testuser/repo1',
                        'language' => 'PHP',
                        'stargazers_count' => 10,
                        'forks_count' => 5,
                    ],
                ], 200)
                ->push([], 200), // Empty response for second page
        ]);

        $syncedCount = $this->githubService->syncRepositories($connection);

        $this->assertEquals(1, $syncedCount);

        $this->assertDatabaseHas('github_repositories', [
            'github_connection_id' => $connection->id,
            'repo_id' => '1',
            'name' => 'repo1',
            'full_name' => 'testuser/repo1',
            'private' => false,
            'default_branch' => 'main',
            'language' => 'PHP',
        ]);
    }

    /** @test */
    public function it_syncs_multiple_pages_of_repositories(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'valid_token',
        ]);

        // Generate 100 repos for first page
        $firstPageRepos = collect(range(1, 100))->map(fn ($i) => [
            'id' => $i,
            'name' => "repo{$i}",
            'full_name' => "testuser/repo{$i}",
            'description' => "Repository {$i}",
            'private' => false,
            'default_branch' => 'main',
            'clone_url' => "https://github.com/testuser/repo{$i}.git",
            'ssh_url' => "git@github.com:testuser/repo{$i}.git",
            'html_url' => "https://github.com/testuser/repo{$i}",
            'language' => 'PHP',
            'stargazers_count' => $i,
            'forks_count' => $i,
        ])->toArray();

        // Generate 50 repos for second page
        $secondPageRepos = collect(range(101, 150))->map(fn ($i) => [
            'id' => $i,
            'name' => "repo{$i}",
            'full_name' => "testuser/repo{$i}",
            'description' => "Repository {$i}",
            'private' => false,
            'default_branch' => 'main',
            'clone_url' => "https://github.com/testuser/repo{$i}.git",
            'ssh_url' => "git@github.com:testuser/repo{$i}.git",
            'html_url' => "https://github.com/testuser/repo{$i}",
            'language' => 'JavaScript',
            'stargazers_count' => $i,
            'forks_count' => $i,
        ])->toArray();

        Http::fake([
            'api.github.com/user/repos*' => Http::sequence()
                ->push($firstPageRepos, 200)
                ->push($secondPageRepos, 200)
                ->push([], 200), // Empty response for third page
        ]);

        $syncedCount = $this->githubService->syncRepositories($connection);

        $this->assertEquals(150, $syncedCount);
        $this->assertCount(150, GitHubRepository::where('github_connection_id', $connection->id)->get());
    }

    /** @test */
    public function it_updates_existing_repositories_during_sync(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'valid_token',
        ]);

        // Create existing repository
        GitHubRepository::factory()->create([
            'github_connection_id' => $connection->id,
            'repo_id' => '1',
            'name' => 'repo1',
            'full_name' => 'testuser/repo1',
            'stars_count' => 5,
            'forks_count' => 2,
        ]);

        Http::fake([
            'api.github.com/user/repos*' => Http::sequence()
                ->push([
                    [
                        'id' => 1,
                        'name' => 'repo1',
                        'full_name' => 'testuser/repo1',
                        'description' => 'Updated description',
                        'private' => false,
                        'default_branch' => 'main',
                        'clone_url' => 'https://github.com/testuser/repo1.git',
                        'ssh_url' => 'git@github.com:testuser/repo1.git',
                        'html_url' => 'https://github.com/testuser/repo1',
                        'language' => 'PHP',
                        'stargazers_count' => 20, // Updated
                        'forks_count' => 10, // Updated
                    ],
                ], 200)
                ->push([], 200),
        ]);

        $syncedCount = $this->githubService->syncRepositories($connection);

        $this->assertEquals(1, $syncedCount);

        $repository = GitHubRepository::where('github_connection_id', $connection->id)
            ->where('repo_id', '1')
            ->first();

        $this->assertEquals(20, $repository->stars_count);
        $this->assertEquals(10, $repository->forks_count);
        $this->assertEquals('Updated description', $repository->description);
    }

    /** @test */
    public function it_creates_webhook_for_repository(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo/hooks' => Http::response([
                'id' => 12345,
                'name' => 'web',
                'active' => true,
                'events' => ['push', 'pull_request'],
                'config' => [
                    'url' => 'https://devflow.test/webhooks/github',
                    'content_type' => 'json',
                    'insecure_ssl' => '0',
                ],
            ], 201),
        ]);

        $result = $this->githubService->createWebhook(
            $connection,
            'testuser/test-repo',
            'https://devflow.test/webhooks/github'
        );

        $this->assertEquals(12345, $result['id']);
        $this->assertEquals('web', $result['name']);
        $this->assertTrue($result['active']);
        $this->assertContains('push', $result['events']);
        $this->assertContains('pull_request', $result['events']);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://api.github.com/repos/testuser/test-repo/hooks' &&
                $request->method() === 'POST' &&
                $body['name'] === 'web' &&
                $body['active'] === true &&
                $body['events'] === ['push', 'pull_request'] &&
                $body['config']['url'] === 'https://devflow.test/webhooks/github' &&
                $body['config']['content_type'] === 'json' &&
                $body['config']['insecure_ssl'] === '0';
        });
    }

    /** @test */
    public function it_deletes_webhook_from_repository(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo/hooks/12345' => Http::response('', 204),
        ]);

        $this->githubService->deleteWebhook($connection, 'testuser/test-repo', 12345);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.github.com/repos/testuser/test-repo/hooks/12345' &&
                $request->method() === 'DELETE';
        });
    }

    /** @test */
    public function it_automatically_refreshes_expired_token_before_making_request(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'expired_token',
            'refresh_token' => 'refresh_token_123',
            'token_expires_at' => now()->subHour(), // Expired
        ]);

        Http::fake([
            'github.com/login/oauth/access_token' => Http::response(
                'access_token=new_token&refresh_token=new_refresh&expires_in=3600',
                200
            ),
            'api.github.com/user' => Http::response([
                'login' => 'testuser',
                'id' => 12345,
            ], 200),
        ]);

        $result = $this->githubService->getUser($connection);

        $this->assertEquals('testuser', $result['login']);

        // Verify token was refreshed
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'github.com/login/oauth/access_token');
        });

        // Verify API call was made with new token
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.github.com/user';
        });
    }

    /** @test */
    public function it_skips_token_refresh_when_token_not_expired(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
            'refresh_token' => 'refresh_token_123',
            'token_expires_at' => now()->addHour(), // Not expired
        ]);

        Http::fake([
            'api.github.com/user' => Http::response([
                'login' => 'testuser',
                'id' => 12345,
            ], 200),
        ]);

        $this->githubService->getUser($connection);

        // Verify no refresh attempt was made
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'github.com/login/oauth/access_token');
        });
    }

    /** @test */
    public function it_skips_token_refresh_when_no_refresh_token_available(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
            'refresh_token' => null,
            'token_expires_at' => now()->subHour(), // Expired but no refresh token
        ]);

        Http::fake([
            'api.github.com/user' => Http::response([
                'login' => 'testuser',
                'id' => 12345,
            ], 200),
        ]);

        $this->githubService->getUser($connection);

        // Verify no refresh attempt was made
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'github.com/login/oauth/access_token');
        });
    }

    /** @test */
    public function it_includes_correct_headers_in_api_requests(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user' => Http::response(['login' => 'testuser'], 200),
        ]);

        $this->githubService->getUser($connection);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer valid_token') &&
                $request->hasHeader('Accept', 'application/vnd.github+json') &&
                $request->hasHeader('X-GitHub-Api-Version', '2022-11-28');
        });
    }

    /** @test */
    public function it_throws_exception_and_logs_error_when_api_request_fails(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('GitHub API request failed', \Mockery::on(function ($context) {
                return $context['endpoint'] === '/user' &&
                    $context['status'] === 401;
            }));

        $connection = GitHubConnection::factory()->create([
            'access_token' => 'invalid_token',
        ]);

        Http::fake([
            'api.github.com/user' => Http::response([
                'message' => 'Bad credentials',
            ], 401),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/GitHub API request failed: 401/');

        $this->githubService->getUser($connection);
    }

    /** @test */
    public function it_handles_404_error_for_repository_not_found(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('GitHub API request failed', \Mockery::type('array'));

        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/nonexistent' => Http::response([
                'message' => 'Not Found',
            ], 404),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/GitHub API request failed: 404/');

        $this->githubService->getRepository($connection, 'testuser/nonexistent');
    }

    /** @test */
    public function it_handles_rate_limiting_error(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('GitHub API request failed', \Mockery::type('array'));

        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user' => Http::response([
                'message' => 'API rate limit exceeded',
            ], 429),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/GitHub API request failed: 429/');

        $this->githubService->getUser($connection);
    }

    /** @test */
    public function it_handles_server_errors(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('GitHub API request failed', \Mockery::type('array'));

        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user' => Http::response([
                'message' => 'Internal Server Error',
            ], 500),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/GitHub API request failed: 500/');

        $this->githubService->getUser($connection);
    }

    /** @test */
    public function it_supports_get_http_method(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user' => Http::response(['login' => 'testuser'], 200),
        ]);

        $this->githubService->getUser($connection);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET';
        });
    }

    /** @test */
    public function it_supports_post_http_method(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo/hooks' => Http::response(['id' => 123], 201),
        ]);

        $this->githubService->createWebhook($connection, 'testuser/test-repo', 'https://example.com');

        Http::assertSent(function ($request) {
            return $request->method() === 'POST';
        });
    }

    /** @test */
    public function it_supports_delete_http_method(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo/hooks/123' => Http::response('', 204),
        ]);

        $this->githubService->deleteWebhook($connection, 'testuser/test-repo', 123);

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE';
        });
    }

    /** @test */
    public function it_returns_empty_array_for_successful_delete_response(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo/hooks/123' => Http::response('', 204),
        ]);

        // Should not throw exception
        $this->githubService->deleteWebhook($connection, 'testuser/test-repo', 123);

        $this->assertTrue(true); // Assert no exception was thrown
    }

    /** @test */
    public function it_handles_network_exceptions(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('GitHub API exception', \Mockery::on(function ($context) {
                return $context['endpoint'] === '/user' &&
                    isset($context['message']);
            }));

        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake(function () {
            throw new \Exception('Network connection failed');
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Network connection failed');

        $this->githubService->getUser($connection);
    }

    /** @test */
    public function it_handles_repositories_with_null_optional_fields(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user/repos*' => Http::sequence()
                ->push([
                    [
                        'id' => 1,
                        'name' => 'minimal-repo',
                        'full_name' => 'testuser/minimal-repo',
                        'description' => null,
                        'private' => false,
                        'clone_url' => 'https://github.com/testuser/minimal-repo.git',
                        'ssh_url' => 'git@github.com:testuser/minimal-repo.git',
                        'html_url' => 'https://github.com/testuser/minimal-repo',
                        'language' => null,
                        // Missing optional fields like default_branch, stargazers_count, forks_count
                    ],
                ], 200)
                ->push([], 200),
        ]);

        $syncedCount = $this->githubService->syncRepositories($connection);

        $this->assertEquals(1, $syncedCount);

        $repository = GitHubRepository::where('github_connection_id', $connection->id)->first();

        $this->assertNull($repository->description);
        $this->assertNull($repository->language);
        $this->assertEquals('main', $repository->default_branch); // Default value
        $this->assertEquals(0, $repository->stars_count); // Default value
        $this->assertEquals(0, $repository->forks_count); // Default value
    }

    /** @test */
    public function it_returns_empty_array_when_api_response_has_no_json_body(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo/hooks/123' => Http::response('', 204),
        ]);

        $this->githubService->deleteWebhook($connection, 'testuser/test-repo', 123);

        // Verify it doesn't throw exception when json() returns null
        $this->assertTrue(true);
    }

    /** @test */
    public function it_syncs_private_and_public_repositories(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user/repos*' => Http::sequence()
                ->push([
                    [
                        'id' => 1,
                        'name' => 'public-repo',
                        'full_name' => 'testuser/public-repo',
                        'description' => 'Public repository',
                        'private' => false,
                        'default_branch' => 'main',
                        'clone_url' => 'https://github.com/testuser/public-repo.git',
                        'ssh_url' => 'git@github.com:testuser/public-repo.git',
                        'html_url' => 'https://github.com/testuser/public-repo',
                        'language' => 'PHP',
                        'stargazers_count' => 10,
                        'forks_count' => 5,
                    ],
                    [
                        'id' => 2,
                        'name' => 'private-repo',
                        'full_name' => 'testuser/private-repo',
                        'description' => 'Private repository',
                        'private' => true,
                        'default_branch' => 'main',
                        'clone_url' => 'https://github.com/testuser/private-repo.git',
                        'ssh_url' => 'git@github.com:testuser/private-repo.git',
                        'html_url' => 'https://github.com/testuser/private-repo',
                        'language' => 'JavaScript',
                        'stargazers_count' => 3,
                        'forks_count' => 1,
                    ],
                ], 200)
                ->push([], 200),
        ]);

        $syncedCount = $this->githubService->syncRepositories($connection);

        $this->assertEquals(2, $syncedCount);

        $publicRepo = GitHubRepository::where('repo_id', '1')->first();
        $privateRepo = GitHubRepository::where('repo_id', '2')->first();

        $this->assertFalse($publicRepo->private);
        $this->assertTrue($privateRepo->private);
    }

    /** @test */
    public function it_handles_commits_with_default_per_page_value(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo/commits*' => Http::response([], 200),
        ]);

        $this->githubService->listCommits($connection, 'testuser/test-repo', 'main');

        Http::assertSent(function ($request) {
            return $request['per_page'] === 10; // Default value
        });
    }

    /** @test */
    public function it_constructs_correct_api_urls(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $this->githubService->getUser($connection);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/user');

        $this->githubService->listRepositories($connection);
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://api.github.com/user/repos'));

        $this->githubService->getRepository($connection, 'owner/repo');
        Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/repos/owner/repo');

        $this->githubService->listBranches($connection, 'owner/repo');
        Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/repos/owner/repo/branches');
    }

    /** @test */
    public function it_handles_empty_repository_response_during_sync(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user/repos*' => Http::response([], 200),
        ]);

        $syncedCount = $this->githubService->syncRepositories($connection);

        $this->assertEquals(0, $syncedCount);
        $this->assertCount(0, GitHubRepository::where('github_connection_id', $connection->id)->get());
    }

    /** @test */
    public function it_handles_webhook_creation_with_custom_events(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo/hooks' => Http::response([
                'id' => 99999,
                'name' => 'web',
                'active' => true,
                'events' => ['push', 'pull_request'],
            ], 201),
        ]);

        $result = $this->githubService->createWebhook(
            $connection,
            'testuser/test-repo',
            'https://devflow.test/webhooks/github'
        );

        $this->assertArrayHasKey('events', $result);
        $this->assertIsArray($result['events']);
    }

    /** @test */
    public function it_throws_exception_for_unsupported_http_method(): void
    {
        Log::shouldReceive('error')->once();

        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported HTTP method: INVALID');

        // Use reflection to access private method with invalid HTTP method
        $reflection = new \ReflectionClass($this->githubService);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);
        $method->invoke($this->githubService, $connection, '/test', [], 'INVALID');
    }

    /** @test */
    public function it_handles_oauth_callback_with_empty_scope(): void
    {
        Http::fake([
            'github.com/login/oauth/access_token' => Http::response(
                'access_token=gho_test_token',
                200
            ),
        ]);

        $result = $this->githubService->handleCallback('auth-code-123');

        $this->assertEquals('', $result['scope']); // Default empty string
    }

    /** @test */
    public function it_syncs_repositories_with_different_languages(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user/repos*' => Http::sequence()
                ->push([
                    [
                        'id' => 1,
                        'name' => 'php-repo',
                        'full_name' => 'testuser/php-repo',
                        'description' => 'PHP repository',
                        'private' => false,
                        'default_branch' => 'main',
                        'clone_url' => 'https://github.com/testuser/php-repo.git',
                        'ssh_url' => 'git@github.com:testuser/php-repo.git',
                        'html_url' => 'https://github.com/testuser/php-repo',
                        'language' => 'PHP',
                        'stargazers_count' => 10,
                        'forks_count' => 5,
                    ],
                    [
                        'id' => 2,
                        'name' => 'js-repo',
                        'full_name' => 'testuser/js-repo',
                        'description' => 'JavaScript repository',
                        'private' => false,
                        'default_branch' => 'main',
                        'clone_url' => 'https://github.com/testuser/js-repo.git',
                        'ssh_url' => 'git@github.com:testuser/js-repo.git',
                        'html_url' => 'https://github.com/testuser/js-repo',
                        'language' => 'JavaScript',
                        'stargazers_count' => 20,
                        'forks_count' => 10,
                    ],
                    [
                        'id' => 3,
                        'name' => 'py-repo',
                        'full_name' => 'testuser/py-repo',
                        'description' => 'Python repository',
                        'private' => false,
                        'default_branch' => 'main',
                        'clone_url' => 'https://github.com/testuser/py-repo.git',
                        'ssh_url' => 'git@github.com:testuser/py-repo.git',
                        'html_url' => 'https://github.com/testuser/py-repo',
                        'language' => 'Python',
                        'stargazers_count' => 30,
                        'forks_count' => 15,
                    ],
                ], 200)
                ->push([], 200),
        ]);

        $syncedCount = $this->githubService->syncRepositories($connection);

        $this->assertEquals(3, $syncedCount);

        $phpRepo = GitHubRepository::where('language', 'PHP')->first();
        $jsRepo = GitHubRepository::where('language', 'JavaScript')->first();
        $pyRepo = GitHubRepository::where('language', 'Python')->first();

        $this->assertNotNull($phpRepo);
        $this->assertNotNull($jsRepo);
        $this->assertNotNull($pyRepo);
    }

    /** @test */
    public function it_handles_refresh_token_without_expires_in(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'old_token',
            'refresh_token' => 'refresh_token_123',
            'token_expires_at' => now()->subHour(),
        ]);

        Http::fake([
            'github.com/login/oauth/access_token' => Http::response(
                'access_token=new_token_456&refresh_token=new_refresh_789',
                200
            ),
        ]);

        $this->githubService->refreshToken($connection);

        $connection->refresh();

        $this->assertEquals('new_token_456', $connection->access_token);
        $this->assertNull($connection->token_expires_at); // No expires_in in response
    }

    /** @test */
    public function it_lists_commits_with_pagination_parameters(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo/commits*' => Http::response([], 200),
        ]);

        $this->githubService->listCommits($connection, 'testuser/test-repo', 'main', 50);

        Http::assertSent(function ($request) {
            return $request['sha'] === 'main' && $request['per_page'] === 50;
        });
    }

    /** @test */
    public function it_handles_api_request_with_put_method(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            '*' => Http::response(['updated' => true], 200),
        ]);

        // Use reflection to test PUT method
        $reflection = new \ReflectionClass($this->githubService);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);
        $result = $method->invoke($this->githubService, $connection, '/test', ['key' => 'value'], 'PUT');

        $this->assertArrayHasKey('updated', $result);
        $this->assertTrue($result['updated']);
    }

    /** @test */
    public function it_handles_api_request_with_patch_method(): void
    {
        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            '*' => Http::response(['patched' => true], 200),
        ]);

        // Use reflection to test PATCH method
        $reflection = new \ReflectionClass($this->githubService);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);
        $result = $method->invoke($this->githubService, $connection, '/test', ['key' => 'value'], 'PATCH');

        $this->assertArrayHasKey('patched', $result);
        $this->assertTrue($result['patched']);
    }

    /** @test */
    public function it_syncs_repositories_and_updates_synced_at_timestamp(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'valid_token',
        ]);

        $pastTime = now()->subDay();

        GitHubRepository::factory()->create([
            'github_connection_id' => $connection->id,
            'repo_id' => '1',
            'synced_at' => $pastTime,
        ]);

        Http::fake([
            'api.github.com/user/repos*' => Http::sequence()
                ->push([
                    [
                        'id' => 1,
                        'name' => 'repo1',
                        'full_name' => 'testuser/repo1',
                        'description' => 'Updated repository',
                        'private' => false,
                        'default_branch' => 'main',
                        'clone_url' => 'https://github.com/testuser/repo1.git',
                        'ssh_url' => 'git@github.com:testuser/repo1.git',
                        'html_url' => 'https://github.com/testuser/repo1',
                        'language' => 'PHP',
                        'stargazers_count' => 15,
                        'forks_count' => 7,
                    ],
                ], 200)
                ->push([], 200),
        ]);

        $this->githubService->syncRepositories($connection);

        $repository = GitHubRepository::where('github_connection_id', $connection->id)
            ->where('repo_id', '1')
            ->first();

        $this->assertNotNull($repository->synced_at);
        $this->assertTrue($repository->synced_at->isAfter($pastTime));
    }

    /** @test */
    public function it_generates_auth_url_with_special_characters_in_state(): void
    {
        $state = 'state-with-special!@#$%^&*()';

        $url = $this->githubService->getAuthUrl($state);

        $this->assertStringContainsString('state='.urlencode($state), $url);
    }

    /** @test */
    public function it_handles_webhook_deletion_failure(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('GitHub API request failed', \Mockery::type('array'));

        $connection = GitHubConnection::factory()->create([
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/repos/testuser/test-repo/hooks/99999' => Http::response([
                'message' => 'Not Found',
            ], 404),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/GitHub API request failed: 404/');

        $this->githubService->deleteWebhook($connection, 'testuser/test-repo', 99999);
    }

    /** @test */
    public function it_handles_repository_sync_with_master_branch(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'valid_token',
        ]);

        Http::fake([
            'api.github.com/user/repos*' => Http::sequence()
                ->push([
                    [
                        'id' => 1,
                        'name' => 'old-repo',
                        'full_name' => 'testuser/old-repo',
                        'description' => 'Repository with master branch',
                        'private' => false,
                        'default_branch' => 'master',
                        'clone_url' => 'https://github.com/testuser/old-repo.git',
                        'ssh_url' => 'git@github.com:testuser/old-repo.git',
                        'html_url' => 'https://github.com/testuser/old-repo',
                        'language' => 'PHP',
                        'stargazers_count' => 5,
                        'forks_count' => 2,
                    ],
                ], 200)
                ->push([], 200),
        ]);

        $syncedCount = $this->githubService->syncRepositories($connection);

        $repository = GitHubRepository::where('github_connection_id', $connection->id)->first();

        $this->assertEquals(1, $syncedCount);
        $this->assertEquals('master', $repository->default_branch);
    }
}
