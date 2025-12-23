<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\DeployProjectJob;
use App\Models\Deployment;
use App\Models\Project;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeploymentWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService
    ) {}

    public function handle(Request $request, string $token)
    {
        // Find project by webhook secret using timing-safe comparison
        // This prevents timing attacks that could enumerate valid secrets
        $project = $this->findProjectByWebhookSecret($token);

        if (! $project) {
            return response()->json(['error' => 'Invalid webhook token'], 404);
        }

        if (! $project->auto_deploy) {
            return response()->json(['error' => 'Auto-deploy is not enabled'], 403);
        }

        // Verify HMAC signature (GitHub style)
        $payload = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256') ?? '';

        // Verify signature if provided (GitHub webhooks)
        if ($signature && ! $this->webhookService->verifyGitHubSignature($payload, $signature, $project->webhook_secret ?? '')) {
            Log::warning("Webhook signature verification failed for project: {$project->slug}");
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        Log::info('Webhook received', [
            'project' => $project->name,
            'payload' => $request->all(),
        ]);

        // Parse webhook payload (GitHub, GitLab, Bitbucket formats)
        $commitData = $this->parseWebhookPayload($request);

        // Check if branch matches
        $branch = $commitData['branch'] ?? null;
        if ($branch && $branch !== $project->branch) {
            return response()->json([
                'message' => 'Branch does not match, deployment skipped',
            ], 200);
        }

        // Check if there's already a running deployment
        $runningDeployment = Deployment::where('project_id', $project->id)
            ->where('status', 'running')
            ->exists();

        if ($runningDeployment) {
            return response()->json([
                'error' => 'A deployment is already in progress',
            ], 409);
        }

        // Create deployment
        $deployment = Deployment::create([
            'user_id' => $project->user_id,
            'project_id' => $project->id,
            'server_id' => $project->server_id,
            'branch' => $branch ?? $project->branch,
            'commit_hash' => $commitData['commit_hash'] ?? null,
            'commit_message' => $commitData['commit_message'] ?? null,
            'status' => 'pending',
            'triggered_by' => 'webhook',
        ]);

        // Dispatch deployment job
        DeployProjectJob::dispatch($deployment);

        return response()->json([
            'message' => 'Deployment triggered successfully',
            'deployment_id' => $deployment->id,
        ], 200);
    }

    protected function parseWebhookPayload(Request $request): array
    {
        $payload = $request->all();

        // GitLab (check first since it also has 'ref' like GitHub)
        if (isset($payload['object_kind']) && $payload['object_kind'] === 'push') {
            return [
                'branch' => str_replace('refs/heads/', '', $payload['ref'] ?? ''),
                'commit_hash' => $payload['checkout_sha'] ?? null,
                'commit_message' => $payload['commits'][0]['message'] ?? null,
            ];
        }

        // GitLab (alternative format - has 'project' key)
        if (isset($payload['project']) && isset($payload['checkout_sha'])) {
            return [
                'branch' => str_replace('refs/heads/', '', $payload['ref'] ?? ''),
                'commit_hash' => $payload['checkout_sha'],
                'commit_message' => $payload['commits'][0]['message'] ?? null,
            ];
        }

        // Bitbucket
        if (isset($payload['push']['changes'])) {
            $change = $payload['push']['changes'][0];

            return [
                'branch' => $change['new']['name'] ?? null,
                'commit_hash' => $change['new']['target']['hash'] ?? null,
                'commit_message' => $change['new']['target']['message'] ?? null,
            ];
        }

        // GitHub (check last as fallback for 'ref' key)
        if (isset($payload['ref'])) {
            return [
                'branch' => str_replace('refs/heads/', '', $payload['ref']),
                'commit_hash' => $payload['head_commit']['id'] ?? $payload['after'] ?? null,
                'commit_message' => $payload['head_commit']['message'] ?? null,
            ];
        }

        return [];
    }

    /**
     * Find a project by its webhook secret using timing-safe comparison.
     *
     * This method prevents timing attacks by comparing all webhook secrets
     * in constant time, regardless of whether a match is found early or late.
     *
     * @param  string  $secret  The webhook secret to match
     * @return Project|null The matching project or null if not found
     */
    private function findProjectByWebhookSecret(string $secret): ?Project
    {
        // Get all webhook-enabled projects with auto_deploy enabled
        $projects = Project::where('auto_deploy', true)
            ->whereNotNull('webhook_secret')
            ->get(['id', 'webhook_secret']);

        $matchedProject = null;

        // Use timing-safe comparison for each project's secret
        // We iterate through ALL projects to maintain constant time
        foreach ($projects as $project) {
            if (hash_equals($project->webhook_secret, $secret)) {
                $matchedProject = $project;
                // Don't break early - continue iterating for constant time
            }
        }

        // If we found a match, reload the full project model
        if ($matchedProject !== null) {
            return Project::find($matchedProject->id);
        }

        return null;
    }
}
