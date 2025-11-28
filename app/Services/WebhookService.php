<?php

namespace App\Services;

use App\Models\Project;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Verify GitHub webhook signature using HMAC-SHA256
     */
    public function verifyGitHubSignature(string $payload, string $signature, string $secret): bool
    {
        if (empty($signature)) {
            return false;
        }

        // GitHub signature format: sha256=<hash>
        if (!str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $hash = substr($signature, 7);
        $expectedHash = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedHash, $hash);
    }

    /**
     * Verify GitLab webhook token
     */
    public function verifyGitLabToken(string $token, string $secret): bool
    {
        return hash_equals($secret, $token);
    }

    /**
     * Parse GitHub webhook payload to extract relevant information
     */
    public function parseGitHubPayload(array $payload): array
    {
        $branch = null;
        $commit = null;
        $commitMessage = null;
        $repository = null;

        // Extract branch from ref (e.g., refs/heads/main -> main)
        if (isset($payload['ref'])) {
            $branch = str_replace('refs/heads/', '', $payload['ref']);
        }

        // Extract commit information
        if (isset($payload['head_commit'])) {
            $commit = $payload['head_commit']['id'] ?? null;
            $commitMessage = $payload['head_commit']['message'] ?? null;
        } elseif (isset($payload['after'])) {
            $commit = $payload['after'];
        }

        // Extract repository information
        if (isset($payload['repository'])) {
            $repository = [
                'name' => $payload['repository']['name'] ?? null,
                'full_name' => $payload['repository']['full_name'] ?? null,
                'url' => $payload['repository']['html_url'] ?? null,
            ];
        }

        return [
            'branch' => $branch,
            'commit' => $commit,
            'commit_message' => $commitMessage,
            'repository' => $repository,
            'sender' => $payload['sender']['login'] ?? 'unknown',
            'pusher' => $payload['pusher']['name'] ?? null,
        ];
    }

    /**
     * Parse GitLab webhook payload to extract relevant information
     */
    public function parseGitLabPayload(array $payload): array
    {
        $branch = null;
        $commit = null;
        $commitMessage = null;
        $repository = null;

        // Extract branch from ref (e.g., refs/heads/main -> main)
        if (isset($payload['ref'])) {
            $branch = str_replace('refs/heads/', '', $payload['ref']);
        }

        // Extract commit information
        if (isset($payload['checkout_sha'])) {
            $commit = $payload['checkout_sha'];
        }

        if (isset($payload['commits']) && !empty($payload['commits'])) {
            $latestCommit = end($payload['commits']);
            $commit = $latestCommit['id'] ?? $commit;
            $commitMessage = $latestCommit['message'] ?? null;
        }

        // Extract repository information
        if (isset($payload['project'])) {
            $repository = [
                'name' => $payload['project']['name'] ?? null,
                'full_name' => $payload['project']['path_with_namespace'] ?? null,
                'url' => $payload['project']['web_url'] ?? null,
            ];
        } elseif (isset($payload['repository'])) {
            $repository = [
                'name' => $payload['repository']['name'] ?? null,
                'full_name' => $payload['repository']['name'] ?? null,
                'url' => $payload['repository']['homepage'] ?? null,
            ];
        }

        return [
            'branch' => $branch,
            'commit' => $commit,
            'commit_message' => $commitMessage,
            'repository' => $repository,
            'sender' => $payload['user_name'] ?? $payload['user']['name'] ?? 'unknown',
            'pusher' => $payload['user_name'] ?? null,
        ];
    }

    /**
     * Check if the webhook should trigger a deployment
     */
    public function shouldTriggerDeployment(Project $project, string $branch): bool
    {
        // Check if webhooks are enabled for this project
        if (!$project->webhook_enabled) {
            Log::info("Webhook ignored: webhooks disabled for project {$project->slug}");
            return false;
        }

        // Check if the branch matches the project's configured branch
        if ($project->branch !== $branch) {
            Log::info("Webhook ignored: branch mismatch for project {$project->slug}. Expected: {$project->branch}, Got: {$branch}");
            return false;
        }

        return true;
    }

    /**
     * Create a webhook delivery record
     */
    public function createDeliveryRecord(
        Project $project,
        string $provider,
        string $event,
        array $payload,
        ?string $signature
    ): WebhookDelivery {
        return WebhookDelivery::create([
            'project_id' => $project->id,
            'provider' => $provider,
            'event_type' => $event,
            'payload' => $payload,
            'signature' => $signature,
            'status' => 'pending',
        ]);
    }

    /**
     * Update webhook delivery status
     */
    public function updateDeliveryStatus(
        WebhookDelivery $delivery,
        string $status,
        ?string $response = null,
        ?int $deploymentId = null
    ): void {
        $delivery->update([
            'status' => $status,
            'response' => $response,
            'deployment_id' => $deploymentId,
        ]);
    }

    /**
     * Get event type from headers
     */
    public function getGitHubEventType(?string $eventHeader): string
    {
        return $eventHeader ?? 'unknown';
    }

    /**
     * Get event type from GitLab payload
     */
    public function getGitLabEventType(array $payload): string
    {
        return $payload['object_kind'] ?? 'unknown';
    }

    /**
     * Check if event should be processed
     */
    public function shouldProcessEvent(string $provider, string $eventType): bool
    {
        // Only process push events
        $validEvents = [
            'github' => ['push'],
            'gitlab' => ['push', 'Push Hook'],
            'bitbucket' => ['repo:push'],
        ];

        return in_array($eventType, $validEvents[$provider] ?? []);
    }
}
