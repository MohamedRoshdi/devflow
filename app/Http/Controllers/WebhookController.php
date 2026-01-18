<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Deployment;
use App\Models\Project;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService
    ) {}

    /**
     * Handle GitHub webhook deliveries
     */
    public function handleGitHub(Request $request, string $secret)
    {
        try {
            // Find project by webhook secret using timing-safe comparison
            // This prevents timing attacks that could enumerate valid secrets
            $project = $this->findProjectByWebhookSecret($secret);

            if (! $project) {
                Log::warning('GitHub webhook received with invalid secret');

                return response()->json(['error' => 'Invalid webhook secret'], 401);
            }

            // Get the raw payload for signature verification
            $payload = $request->getContent();
            $signature = $request->header('X-Hub-Signature-256') ?? '';
            $eventType = $request->header('X-GitHub-Event') ?? 'unknown';

            // Verify signature
            if (! $this->webhookService->verifyGitHubSignature($payload, $signature, $project->webhook_secret ?? '')) {
                Log::warning("GitHub webhook signature verification failed for project: {$project->slug}");

                // Create delivery record with failed status
                $delivery = $this->webhookService->createDeliveryRecord(
                    $project,
                    'github',
                    $eventType ?? 'unknown',
                    $request->json()->all(),
                    $signature
                );

                $this->webhookService->updateDeliveryStatus($delivery, 'failed', 'Invalid signature');

                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payloadData = $request->json()->all();

            // Create delivery record
            $delivery = $this->webhookService->createDeliveryRecord(
                $project,
                'github',
                $eventType ?? 'unknown',
                $payloadData,
                $signature
            );

            // Check if this is a push event
            if (! $this->webhookService->shouldProcessEvent('github', $eventType)) {
                $this->webhookService->updateDeliveryStatus(
                    $delivery,
                    'ignored',
                    "Event type '{$eventType}' is not processed (only 'push' events trigger deployments)"
                );

                Log::info("GitHub webhook ignored for project {$project->slug}: event type '{$eventType}' not processed");

                return response()->json(['message' => 'Event acknowledged but not processed'], 200);
            }

            // Parse the payload
            $parsed = $this->webhookService->parseGitHubPayload($payloadData);

            // Check if deployment should be triggered
            if (! $this->webhookService->shouldTriggerDeployment($project, $parsed['branch'], $parsed['commit_message'])) {
                $this->webhookService->updateDeliveryStatus(
                    $delivery,
                    'ignored',
                    'Deployment conditions not met (branch or commit message patterns)'
                );

                return response()->json(['message' => 'Deployment conditions not met', 'reason' => 'Branch or commit message pattern check failed'], 200);
            }

            // Create deployment
            $deployment = Deployment::create([
                'user_id' => $project->user_id,
                'project_id' => $project->id,
                'server_id' => $project->server_id,
                'commit_hash' => $parsed['commit'],
                'commit_message' => $parsed['commit_message'],
                'branch' => $parsed['branch'],
                'status' => 'pending',
                'triggered_by' => 'webhook',
                'started_at' => now(),
                'metadata' => [
                    'webhook_provider' => 'github',
                    'webhook_event' => $eventType,
                    'sender' => $parsed['sender'],
                    'pusher' => $parsed['pusher'],
                ],
            ]);

            // Dispatch deployment job
            \App\Jobs\DeployProjectJob::dispatch($deployment);

            // Update delivery record with deployment ID
            $this->webhookService->updateDeliveryStatus(
                $delivery,
                'success',
                "Deployment #{$deployment->id} triggered successfully",
                $deployment->id
            );

            Log::info("GitHub webhook triggered deployment #{$deployment->id} for project {$project->slug}");

            return response()->json([
                'message' => 'Deployment triggered successfully',
                'deployment_id' => $deployment->id,
            ], 200);

        } catch (\Exception $e) {
            Log::error('GitHub webhook processing error: '.$e->getMessage(), [
                'exception' => $e,
                'secret' => $secret,
            ]);

            if (isset($delivery)) {
                $this->webhookService->updateDeliveryStatus($delivery, 'failed', $e->getMessage());
            }

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle GitLab webhook deliveries
     */
    public function handleGitLab(Request $request, string $secret)
    {
        try {
            // Find project by webhook secret using timing-safe comparison
            // This prevents timing attacks that could enumerate valid secrets
            $project = $this->findProjectByWebhookSecret($secret);

            if (! $project) {
                Log::warning('GitLab webhook received with invalid secret');

                return response()->json(['error' => 'Invalid webhook secret'], 401);
            }

            // Get the token from header
            $token = $request->header('X-Gitlab-Token') ?? '';

            // Verify token
            if (! $this->webhookService->verifyGitLabToken($token, $project->webhook_secret ?? '')) {
                Log::warning("GitLab webhook token verification failed for project: {$project->slug}");

                $payloadData = $request->json()->all();
                $eventType = $this->webhookService->getGitLabEventType($payloadData);

                // Create delivery record with failed status
                $delivery = $this->webhookService->createDeliveryRecord(
                    $project,
                    'gitlab',
                    $eventType,
                    $payloadData,
                    $token
                );

                $this->webhookService->updateDeliveryStatus($delivery, 'failed', 'Invalid token');

                return response()->json(['error' => 'Invalid token'], 401);
            }

            $payloadData = $request->json()->all();
            $eventType = $this->webhookService->getGitLabEventType($payloadData);

            // Create delivery record
            $delivery = $this->webhookService->createDeliveryRecord(
                $project,
                'gitlab',
                $eventType,
                $payloadData,
                $token
            );

            // Check if this is a push event
            if (! $this->webhookService->shouldProcessEvent('gitlab', $eventType)) {
                $this->webhookService->updateDeliveryStatus(
                    $delivery,
                    'ignored',
                    "Event type '{$eventType}' is not processed (only 'push' events trigger deployments)"
                );

                Log::info("GitLab webhook ignored for project {$project->slug}: event type '{$eventType}' not processed");

                return response()->json(['message' => 'Event acknowledged but not processed'], 200);
            }

            // Parse the payload
            $parsed = $this->webhookService->parseGitLabPayload($payloadData);

            // Check if deployment should be triggered
            if (! $this->webhookService->shouldTriggerDeployment($project, $parsed['branch'], $parsed['commit_message'])) {
                $this->webhookService->updateDeliveryStatus(
                    $delivery,
                    'ignored',
                    'Deployment conditions not met (branch or commit message patterns)'
                );

                return response()->json(['message' => 'Deployment conditions not met', 'reason' => 'Branch or commit message pattern check failed'], 200);
            }

            // Create deployment
            $deployment = Deployment::create([
                'user_id' => $project->user_id,
                'project_id' => $project->id,
                'server_id' => $project->server_id,
                'commit_hash' => $parsed['commit'],
                'commit_message' => $parsed['commit_message'],
                'branch' => $parsed['branch'],
                'status' => 'pending',
                'triggered_by' => 'webhook',
                'started_at' => now(),
                'metadata' => [
                    'webhook_provider' => 'gitlab',
                    'webhook_event' => $eventType,
                    'sender' => $parsed['sender'],
                    'pusher' => $parsed['pusher'],
                ],
            ]);

            // Dispatch deployment job
            \App\Jobs\DeployProjectJob::dispatch($deployment);

            // Update delivery record with deployment ID
            $this->webhookService->updateDeliveryStatus(
                $delivery,
                'success',
                "Deployment #{$deployment->id} triggered successfully",
                $deployment->id
            );

            Log::info("GitLab webhook triggered deployment #{$deployment->id} for project {$project->slug}");

            return response()->json([
                'message' => 'Deployment triggered successfully',
                'deployment_id' => $deployment->id,
            ], 200);

        } catch (\Exception $e) {
            Log::error('GitLab webhook processing error: '.$e->getMessage(), [
                'exception' => $e,
                'secret' => $secret,
            ]);

            if (isset($delivery)) {
                $this->webhookService->updateDeliveryStatus($delivery, 'failed', $e->getMessage());
            }

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Find project by webhook secret using timing-safe comparison
     *
     * This method prevents timing attacks by comparing all webhook secrets
     * in constant time, regardless of whether a match is found early or late.
     *
     * @param string $secret The webhook secret to match
     * @return Project|null The matching project or null if not found
     */
    private function findProjectByWebhookSecret(string $secret): ?Project
    {
        // Get all webhook-enabled projects
        $projects = Project::where('webhook_enabled', true)
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
