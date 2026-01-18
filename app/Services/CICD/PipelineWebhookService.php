<?php

declare(strict_types=1);

namespace App\Services\CICD;

use App\Models\Pipeline;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * Handles CI/CD webhook operations.
 *
 * Responsible for setting up, verifying, and managing
 * webhooks for Git providers (GitHub, GitLab, Bitbucket).
 */
class PipelineWebhookService
{
    /**
     * Setup webhook for automatic pipeline triggering
     *
     * @return array{success: bool, webhook_id?: string, webhook_url?: string, error?: string}
     */
    public function setupWebhook(Project $project): array
    {
        try {
            // Generate webhook secret if not already set
            if (! $project->webhook_secret) {
                $project->webhook_secret = $project->generateWebhookSecret();
                $project->save();
            }

            // Determine provider from repository URL
            $provider = $this->detectGitProvider($project->repository_url);

            // Setup webhook based on provider
            $result = match ($provider) {
                'github' => $this->setupGitHubWebhook($project),
                'gitlab' => $this->setupGitLabWebhook($project),
                'bitbucket' => $this->setupBitbucketWebhook($project),
                default => ['success' => false, 'error' => "Unsupported provider: {$provider}"],
            };

            if ($result['success']) {
                // Update project with webhook details - only set provider if it's a valid enum value
                $project->webhook_enabled = true;
                $project->webhook_provider = in_array($provider, ['github', 'gitlab', 'bitbucket', 'custom'], true) ? $provider : 'custom';
                $project->webhook_id = $result['webhook_id'] ?? null;
                $project->webhook_url = $result['webhook_url'] ?? null;
                $project->save();

                Log::info('Webhook created successfully', [
                    'project_id' => $project->id,
                    'provider' => $provider,
                    'webhook_id' => $result['webhook_id'] ?? null,
                ]);
            } else {
                Log::error('Webhook creation failed', [
                    'project_id' => $project->id,
                    'provider' => $provider,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Webhook setup exception', [
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete webhook from Git provider
     */
    public function deleteWebhook(Project $project): bool
    {
        try {
            if (! $project->webhook_enabled || ! $project->webhook_id) {
                Log::warning('No webhook to delete', ['project_id' => $project->id]);

                return true;
            }

            $result = match ($project->webhook_provider) {
                'github' => $this->deleteGitHubWebhook($project),
                'gitlab' => $this->deleteGitLabWebhook($project),
                'bitbucket' => $this->deleteBitbucketWebhook($project),
                default => false,
            };

            if ($result) {
                // Clear webhook data from project
                $project->webhook_enabled = false;
                $project->webhook_id = null;
                $project->webhook_url = null;
                $project->save();

                Log::info('Webhook deleted successfully', [
                    'project_id' => $project->id,
                    'provider' => $project->webhook_provider,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Webhook deletion exception', [
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify webhook signature from incoming request
     */
    public function verifyWebhookSignature(Request $request, Project $project): bool
    {
        try {
            if (! $project->webhook_secret || ! $project->webhook_enabled) {
                Log::warning('Webhook verification failed: No webhook configured', [
                    'project_id' => $project->id,
                ]);

                return false;
            }

            $provider = $project->webhook_provider ?? $this->detectGitProvider($project->repository_url);

            $isValid = match ($provider) {
                'github' => $this->verifyGitHubSignature($request, $project->webhook_secret),
                'gitlab' => $this->verifyGitLabSignature($request, $project->webhook_secret),
                'bitbucket' => $this->verifyBitbucketSignature($request, $project->webhook_secret),
                default => false,
            };

            if (! $isValid) {
                Log::warning('Webhook signature verification failed', [
                    'project_id' => $project->id,
                    'provider' => $provider,
                    'ip' => $request->ip(),
                ]);
            }

            return $isValid;
        } catch (\Exception $e) {
            Log::error('Webhook verification exception', [
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Detect Git provider from repository URL
     */
    public function detectGitProvider(string $url): string
    {
        if (str_contains($url, 'github.com')) {
            return 'github';
        }

        if (str_contains($url, 'gitlab.com') || str_contains($url, 'gitlab')) {
            return 'gitlab';
        }

        if (str_contains($url, 'bitbucket.org')) {
            return 'bitbucket';
        }

        return 'custom';
    }

    /**
     * Setup GitHub webhook
     *
     * @return array{success: bool, webhook_id?: string, webhook_url?: string, error?: string}
     */
    protected function setupGitHubWebhook(Project $project): array
    {
        $token = config('services.github.token');
        if (! $token) {
            return ['success' => false, 'error' => 'GitHub token not configured'];
        }

        $owner = $this->extractGitHubOwner($project->repository_url);
        $repo = $this->extractGitHubRepo($project->repository_url);

        if (! $owner || ! $repo) {
            return ['success' => false, 'error' => 'Invalid GitHub repository URL'];
        }

        $webhookUrl = url("/api/webhooks/github/{$project->id}");

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post("https://api.github.com/repos/{$owner}/{$repo}/hooks", [
                    'name' => 'web',
                    'active' => true,
                    'events' => ['push', 'pull_request', 'release'],
                    'config' => [
                        'url' => $webhookUrl,
                        'content_type' => 'json',
                        'secret' => $project->webhook_secret,
                        'insecure_ssl' => '0',
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'webhook_id' => (string) $data['id'],
                    'webhook_url' => $webhookUrl,
                ];
            }

            return [
                'success' => false,
                'error' => "GitHub API error: {$response->status()} - " . $response->json('message', $response->body()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => "GitHub API exception: {$e->getMessage()}"];
        }
    }

    /**
     * Setup GitLab webhook
     *
     * @return array{success: bool, webhook_id?: string, webhook_url?: string, error?: string}
     */
    protected function setupGitLabWebhook(Project $project): array
    {
        $token = config('services.gitlab.token');
        $gitlabUrl = config('services.gitlab.url', 'https://gitlab.com');

        if (! $token) {
            return ['success' => false, 'error' => 'GitLab token not configured'];
        }

        $projectId = $this->extractGitLabProjectId($project->repository_url);
        if (! $projectId) {
            return ['success' => false, 'error' => 'Invalid GitLab repository URL'];
        }

        $webhookUrl = url("/api/webhooks/gitlab/{$project->id}");

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post("{$gitlabUrl}/api/v4/projects/{$projectId}/hooks", [
                    'url' => $webhookUrl,
                    'token' => $project->webhook_secret,
                    'push_events' => true,
                    'merge_requests_events' => true,
                    'tag_push_events' => true,
                    'releases_events' => true,
                    'enable_ssl_verification' => true,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'webhook_id' => (string) $data['id'],
                    'webhook_url' => $webhookUrl,
                ];
            }

            return [
                'success' => false,
                'error' => "GitLab API error: {$response->status()} - " . $response->json('message', $response->body()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => "GitLab API exception: {$e->getMessage()}"];
        }
    }

    /**
     * Setup Bitbucket webhook
     *
     * @return array{success: bool, webhook_id?: string, webhook_url?: string, error?: string}
     */
    protected function setupBitbucketWebhook(Project $project): array
    {
        $username = config('services.bitbucket.username');
        $appPassword = config('services.bitbucket.app_password');
        $bitbucketUrl = config('services.bitbucket.url', 'https://api.bitbucket.org/2.0');

        if (! $username || ! $appPassword) {
            return ['success' => false, 'error' => 'Bitbucket credentials not configured'];
        }

        [$workspace, $repoSlug] = $this->extractBitbucketInfo($project->repository_url);
        if (! $workspace || ! $repoSlug) {
            return ['success' => false, 'error' => 'Invalid Bitbucket repository URL'];
        }

        $webhookUrl = url("/api/webhooks/bitbucket/{$project->id}");

        try {
            $response = Http::withBasicAuth($username, $appPassword)
                ->timeout(30)
                ->post("{$bitbucketUrl}/repositories/{$workspace}/{$repoSlug}/hooks", [
                    'description' => "DevFlow Pro - {$project->name}",
                    'url' => $webhookUrl,
                    'active' => true,
                    'events' => [
                        'repo:push',
                        'pullrequest:created',
                        'pullrequest:updated',
                        'pullrequest:fulfilled',
                    ],
                    // Bitbucket doesn't support secrets in webhooks, we'll use IP whitelisting
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'webhook_id' => $data['uuid'] ?? (string) ($data['id'] ?? ''),
                    'webhook_url' => $webhookUrl,
                ];
            }

            return [
                'success' => false,
                'error' => "Bitbucket API error: {$response->status()} - " . $response->json('error.message', $response->body()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => "Bitbucket API exception: {$e->getMessage()}"];
        }
    }

    /**
     * Delete GitHub webhook
     */
    protected function deleteGitHubWebhook(Project $project): bool
    {
        $token = config('services.github.token');
        if (! $token) {
            return false;
        }

        $owner = $this->extractGitHubOwner($project->repository_url);
        $repo = $this->extractGitHubRepo($project->repository_url);

        if (! $owner || ! $repo || ! $project->webhook_id) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->delete("https://api.github.com/repos/{$owner}/{$repo}/hooks/{$project->webhook_id}");

            return $response->successful() || $response->status() === 404; // 404 means already deleted
        } catch (\Exception $e) {
            Log::error('GitHub webhook deletion failed', [
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete GitLab webhook
     */
    protected function deleteGitLabWebhook(Project $project): bool
    {
        $token = config('services.gitlab.token');
        $gitlabUrl = config('services.gitlab.url', 'https://gitlab.com');

        if (! $token) {
            return false;
        }

        $projectId = $this->extractGitLabProjectId($project->repository_url);
        if (! $projectId || ! $project->webhook_id) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->delete("{$gitlabUrl}/api/v4/projects/{$projectId}/hooks/{$project->webhook_id}");

            return $response->successful() || $response->status() === 404;
        } catch (\Exception $e) {
            Log::error('GitLab webhook deletion failed', [
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete Bitbucket webhook
     */
    protected function deleteBitbucketWebhook(Project $project): bool
    {
        $username = config('services.bitbucket.username');
        $appPassword = config('services.bitbucket.app_password');
        $bitbucketUrl = config('services.bitbucket.url', 'https://api.bitbucket.org/2.0');

        if (! $username || ! $appPassword) {
            return false;
        }

        [$workspace, $repoSlug] = $this->extractBitbucketInfo($project->repository_url);
        if (! $workspace || ! $repoSlug || ! $project->webhook_id) {
            return false;
        }

        try {
            $response = Http::withBasicAuth($username, $appPassword)
                ->timeout(30)
                ->delete("{$bitbucketUrl}/repositories/{$workspace}/{$repoSlug}/hooks/{$project->webhook_id}");

            return $response->successful() || $response->status() === 404;
        } catch (\Exception $e) {
            Log::error('Bitbucket webhook deletion failed', [
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify GitHub webhook signature
     */
    protected function verifyGitHubSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify GitLab webhook signature
     */
    protected function verifyGitLabSignature(Request $request, string $secret): bool
    {
        $token = $request->header('X-Gitlab-Token');

        return $token === $secret;
    }

    /**
     * Verify Bitbucket webhook signature
     * Note: Bitbucket doesn't support webhook secrets, so we verify using IP whitelisting
     */
    protected function verifyBitbucketSignature(Request $request, string $secret): bool
    {
        // Bitbucket IP ranges (as of 2025)
        $bitbucketIpRanges = [
            '104.192.136.0/21', // Bitbucket Cloud
            '185.166.140.0/22',
            '18.205.93.0/25',
            '18.234.32.128/25',
            '13.52.5.0/25',
        ];

        $requestIp = $request->ip();
        if (! $requestIp) {
            return false;
        }

        foreach ($bitbucketIpRanges as $range) {
            if ($this->ipInRange($requestIp, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP address is within CIDR range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (! str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    /**
     * Extract Bitbucket workspace and repository slug from URL
     *
     * @return array{0: string, 1: string}
     */
    protected function extractBitbucketInfo(string $url): array
    {
        // Handle SSH URLs: git@bitbucket.org:workspace/repo.git
        if (preg_match('/bitbucket\.org:([^\/]+)\/([^\.]+)/', $url, $matches)) {
            return [$matches[1], $matches[2]];
        }

        // Handle HTTPS URLs: https://bitbucket.org/workspace/repo.git
        if (preg_match('/bitbucket\.org\/([^\/]+)\/([^\.\/]+)/', $url, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return ['', ''];
    }

    /**
     * Create pipeline file in repository
     */
    public function createPipelineFile(Pipeline $pipeline): void
    {
        $project = $pipeline->project;
        $projectPath = "/opt/devflow/projects/{$project->slug}";

        switch ($pipeline->provider) {
            case 'github':
                $filePath = "{$projectPath}/.github/workflows/devflow.yml";
                $content = Yaml::dump($pipeline->configuration);
                break;

            case 'gitlab':
                $filePath = "{$projectPath}/.gitlab-ci.yml";
                $content = Yaml::dump($pipeline->configuration);
                break;

            case 'bitbucket':
                $filePath = "{$projectPath}/bitbucket-pipelines.yml";
                $content = Yaml::dump($pipeline->configuration);
                break;

            default:
                return;
        }

        // Create directory if it doesn't exist
        $directory = dirname($filePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write pipeline file
        file_put_contents($filePath, $content);

        // Commit and push the file
        Process::path($projectPath)->run('git add .');
        Process::path($projectPath)->run('git commit -m "Add DevFlow CI/CD pipeline"');
        Process::path($projectPath)->run('git push origin ' . $project->branch);
    }

    /**
     * Update existing webhook configuration
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function updateWebhook(Project $project, array $options = []): array
    {
        try {
            // Delete existing webhook
            $this->deleteWebhook($project);

            // Set up new webhook with updated options
            return $this->setupWebhook($project);
        } catch (\Exception $e) {
            Log::error('Webhook update failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test webhook connectivity
     *
     * @return array<string, mixed>
     */
    public function testWebhook(Project $project): array
    {
        if (! $project->webhook_enabled || ! $project->webhook_id) {
            return [
                'success' => false,
                'error' => 'No webhook configured for this project',
            ];
        }

        try {
            return match ($project->webhook_provider) {
                'github' => $this->testGitHubWebhook($project),
                'gitlab' => $this->testGitLabWebhook($project),
                'bitbucket' => $this->testBitbucketWebhook($project),
                default => ['success' => false, 'error' => 'Unknown provider'],
            };
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test GitHub webhook
     *
     * @return array<string, mixed>
     */
    protected function testGitHubWebhook(Project $project): array
    {
        $token = config('services.github.token');
        $owner = $this->extractGitHubOwner($project->repository_url);
        $repo = $this->extractGitHubRepo($project->repository_url);

        $response = Http::withToken($token)
            ->timeout(30)
            ->post("https://api.github.com/repos/{$owner}/{$repo}/hooks/{$project->webhook_id}/pings");

        return [
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'message' => $response->successful() ? 'Ping sent successfully' : $response->body(),
        ];
    }

    /**
     * Test GitLab webhook
     *
     * @return array<string, mixed>
     */
    protected function testGitLabWebhook(Project $project): array
    {
        $token = config('services.gitlab.token');
        $gitlabUrl = config('services.gitlab.url', 'https://gitlab.com');
        $projectId = $this->extractGitLabProjectId($project->repository_url);

        // GitLab doesn't have a ping endpoint, so we verify the hook exists
        $response = Http::withToken($token)
            ->timeout(30)
            ->get("{$gitlabUrl}/api/v4/projects/{$projectId}/hooks/{$project->webhook_id}");

        return [
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'message' => $response->successful() ? 'Webhook exists and is active' : $response->body(),
        ];
    }

    /**
     * Test Bitbucket webhook
     *
     * @return array<string, mixed>
     */
    protected function testBitbucketWebhook(Project $project): array
    {
        $username = config('services.bitbucket.username');
        $appPassword = config('services.bitbucket.app_password');
        $bitbucketUrl = config('services.bitbucket.url', 'https://api.bitbucket.org/2.0');

        [$workspace, $repoSlug] = $this->extractBitbucketInfo($project->repository_url);

        // Verify the hook exists
        $response = Http::withBasicAuth($username, $appPassword)
            ->timeout(30)
            ->get("{$bitbucketUrl}/repositories/{$workspace}/{$repoSlug}/hooks/{$project->webhook_id}");

        return [
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'message' => $response->successful() ? 'Webhook exists and is active' : $response->body(),
        ];
    }

    /**
     * Get webhook delivery history
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWebhookDeliveries(Project $project, int $limit = 10): array
    {
        if (! $project->webhook_enabled || $project->webhook_provider !== 'github') {
            // Only GitHub provides delivery history via API
            return [];
        }

        try {
            $token = config('services.github.token');
            $owner = $this->extractGitHubOwner($project->repository_url);
            $repo = $this->extractGitHubRepo($project->repository_url);

            $response = Http::withToken($token)
                ->timeout(30)
                ->get("https://api.github.com/repos/{$owner}/{$repo}/hooks/{$project->webhook_id}/deliveries", [
                    'per_page' => $limit,
                ]);

            if ($response->successful()) {
                return collect($response->json())->map(function ($delivery) {
                    return [
                        'id' => $delivery['id'],
                        'event' => $delivery['event'],
                        'status' => $delivery['status'],
                        'status_code' => $delivery['status_code'],
                        'delivered_at' => $delivery['delivered_at'],
                        'duration' => $delivery['duration'],
                    ];
                })->all();
            }

            return [];
        } catch (\Exception $e) {
            Log::warning('Failed to get webhook deliveries', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Extract GitHub owner from repository URL
     */
    public function extractGitHubOwner(string $url): string
    {
        if (preg_match('/github\.com[\/:]([^\/]+)\//', $url, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Extract GitHub repository name from URL
     */
    public function extractGitHubRepo(string $url): string
    {
        if (preg_match('/github\.com[\/:]([^\/]+)\/([^\.]+)/', $url, $matches)) {
            return $matches[2];
        }

        return '';
    }

    /**
     * Extract GitLab project ID from repository URL
     */
    public function extractGitLabProjectId(string $url): string
    {
        // Handle SSH URLs: git@gitlab.com:group/project.git
        if (preg_match('/git@[^:]+:(.+?)(?:\.git)?$/', $url, $matches)) {
            return urlencode($matches[1]);
        }

        // Handle HTTPS URLs: https://gitlab.com/group/project.git
        if (preg_match('/gitlab\.[^\/]+\/(.+?)(?:\.git)?$/', $url, $matches)) {
            return urlencode($matches[1]);
        }

        // Handle self-hosted GitLab URLs
        if (preg_match('/\/\/[^\/]+\/(.+?)(?:\.git)?$/', $url, $matches)) {
            return urlencode($matches[1]);
        }

        return '';
    }
}
