<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GitHubConnection;
use App\Models\GitHubRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    private const GITHUB_API_URL = 'https://api.github.com';
    private const GITHUB_OAUTH_URL = 'https://github.com/login/oauth';

    /**
     * Generate GitHub OAuth authorization URL.
     */
    public function getAuthUrl(string $state): string
    {
        $params = http_build_query([
            'client_id' => config('services.github.client_id'),
            'redirect_uri' => config('services.github.redirect'),
            'scope' => config('services.github.scopes'),
            'state' => $state,
        ]);

        return self::GITHUB_OAUTH_URL . "/authorize?{$params}";
    }

    /**
     * Exchange authorization code for access token.
     */
    public function handleCallback(string $code): array
    {
        $response = Http::asForm()->post(self::GITHUB_OAUTH_URL . '/access_token', [
            'client_id' => config('services.github.client_id'),
            'client_secret' => config('services.github.client_secret'),
            'code' => $code,
            'redirect_uri' => config('services.github.redirect'),
        ])->throw();

        parse_str($response->body(), $result);

        if (!isset($result['access_token'])) {
            throw new \Exception('Failed to obtain access token from GitHub');
        }

        return [
            'access_token' => $result['access_token'],
            'token_type' => $result['token_type'] ?? 'bearer',
            'scope' => $result['scope'] ?? '',
        ];
    }

    /**
     * Refresh an expired access token.
     */
    public function refreshToken(GitHubConnection $connection): void
    {
        if ($connection->refresh_token === null) {
            throw new \Exception('No refresh token available');
        }

        $response = Http::asForm()->post(self::GITHUB_OAUTH_URL . '/access_token', [
            'client_id' => config('services.github.client_id'),
            'client_secret' => config('services.github.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $connection->refresh_token,
        ])->throw();

        parse_str($response->body(), $result);

        $connection->update([
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'] ?? $connection->refresh_token,
            'token_expires_at' => isset($result['expires_in'])
                ? now()->addSeconds($result['expires_in'])
                : null,
        ]);
    }

    /**
     * Get authenticated user information.
     */
    public function getUser(GitHubConnection $connection): array
    {
        return $this->makeRequest($connection, '/user');
    }

    /**
     * List repositories for authenticated user.
     */
    public function listRepositories(GitHubConnection $connection, int $perPage = 100, int $page = 1): array
    {
        return $this->makeRequest($connection, '/user/repos', [
            'per_page' => $perPage,
            'page' => $page,
            'sort' => 'updated',
            'direction' => 'desc',
        ]);
    }

    /**
     * Get detailed repository information.
     */
    public function getRepository(GitHubConnection $connection, string $fullName): array
    {
        return $this->makeRequest($connection, "/repos/{$fullName}");
    }

    /**
     * List branches for a repository.
     */
    public function listBranches(GitHubConnection $connection, string $fullName): array
    {
        return $this->makeRequest($connection, "/repos/{$fullName}/branches");
    }

    /**
     * List commits for a repository branch.
     */
    public function listCommits(GitHubConnection $connection, string $fullName, string $branch, int $perPage = 10): array
    {
        return $this->makeRequest($connection, "/repos/{$fullName}/commits", [
            'sha' => $branch,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Sync all repositories to database.
     */
    public function syncRepositories(GitHubConnection $connection): int
    {
        $syncedCount = 0;
        $page = 1;
        $perPage = 100;

        do {
            $repositories = $this->listRepositories($connection, $perPage, $page);

            foreach ($repositories as $repo) {
                GitHubRepository::updateOrCreate(
                    [
                        'github_connection_id' => $connection->id,
                        'repo_id' => (string) $repo['id'],
                    ],
                    [
                        'name' => $repo['name'],
                        'full_name' => $repo['full_name'],
                        'description' => $repo['description'] ?? null,
                        'private' => $repo['private'],
                        'default_branch' => $repo['default_branch'] ?? 'main',
                        'clone_url' => $repo['clone_url'],
                        'ssh_url' => $repo['ssh_url'],
                        'html_url' => $repo['html_url'],
                        'language' => $repo['language'] ?? null,
                        'stars_count' => $repo['stargazers_count'] ?? 0,
                        'forks_count' => $repo['forks_count'] ?? 0,
                        'synced_at' => now(),
                    ]
                );

                $syncedCount++;
            }

            $page++;
        } while (count($repositories) === $perPage);

        return $syncedCount;
    }

    /**
     * Create a webhook for a repository.
     */
    public function createWebhook(GitHubConnection $connection, string $fullName, string $webhookUrl): array
    {
        return $this->makeRequest($connection, "/repos/{$fullName}/hooks", [
            'name' => 'web',
            'active' => true,
            'events' => ['push', 'pull_request'],
            'config' => [
                'url' => $webhookUrl,
                'content_type' => 'json',
                'insecure_ssl' => '0',
            ],
        ], 'POST');
    }

    /**
     * Delete a webhook from a repository.
     */
    public function deleteWebhook(GitHubConnection $connection, string $fullName, int $hookId): void
    {
        $this->makeRequest($connection, "/repos/{$fullName}/hooks/{$hookId}", [], 'DELETE');
    }

    /**
     * Make an authenticated request to GitHub API.
     */
    private function makeRequest(GitHubConnection $connection, string $endpoint, array $params = [], string $method = 'GET'): array
    {
        // Check if token is expired and refresh if needed
        if ($connection->isTokenExpired() && $connection->refresh_token !== null) {
            $this->refreshToken($connection);
            $connection->refresh();
        }

        $url = self::GITHUB_API_URL . $endpoint;

        $request = Http::withToken($connection->access_token)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ]);

        try {
            $response = match(strtoupper($method)) {
                'GET' => $request->get($url, $params),
                'POST' => $request->post($url, $params),
                'PUT' => $request->put($url, $params),
                'PATCH' => $request->patch($url, $params),
                'DELETE' => $request->delete($url, $params),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            if (!$response->successful()) {
                Log::error('GitHub API request failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception("GitHub API request failed: {$response->status()} - {$response->body()}");
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('GitHub API exception', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
