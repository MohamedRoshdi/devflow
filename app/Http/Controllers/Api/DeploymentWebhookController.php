<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\DeployProjectJob;
use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeploymentWebhookController extends Controller
{
    public function handle(Request $request, string $token)
    {
        // Find project by webhook token
        $project = Project::where('slug', $token)->first();

        if (! $project) {
            return response()->json(['error' => 'Invalid webhook token'], 404);
        }

        if (! $project->auto_deploy) {
            return response()->json(['error' => 'Auto-deploy is not enabled'], 403);
        }

        Log::info('Webhook received', [
            'project' => $project->name,
            'payload' => $request->all(),
        ]);

        // Parse webhook payload (GitHub, GitLab, Bitbucket formats)
        $commitData = $this->parseWebhookPayload($request);

        // Create deployment
        $deployment = Deployment::create([
            'user_id' => $project->user_id,
            'project_id' => $project->id,
            'server_id' => $project->server_id,
            'branch' => $commitData['branch'] ?? $project->branch,
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

        // GitHub
        if (isset($payload['ref'])) {
            return [
                'branch' => str_replace('refs/heads/', '', $payload['ref']),
                'commit_hash' => $payload['after'] ?? null,
                'commit_message' => $payload['head_commit']['message'] ?? null,
            ];
        }

        // GitLab
        if (isset($payload['object_kind']) && $payload['object_kind'] === 'push') {
            return [
                'branch' => str_replace('refs/heads/', '', $payload['ref']),
                'commit_hash' => $payload['checkout_sha'] ?? null,
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

        return [];
    }
}
