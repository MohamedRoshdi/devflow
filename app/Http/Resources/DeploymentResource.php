<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeploymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'commit_hash' => $this->commit_hash,
            'commit_message' => $this->commit_message,
            'branch' => $this->branch,
            'status' => $this->status,
            'output_log' => $this->when($request->has('include_logs'), $this->output_log),
            'error_log' => $this->when($request->has('include_logs'), $this->error_log),
            'error_message' => $this->error_message,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'duration_seconds' => $this->duration_seconds,
            'triggered_by' => $this->triggered_by,
            'environment_snapshot' => $this->when($request->has('include_snapshot'), $this->environment_snapshot),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Relationships
            'project' => [
                'id' => $this->project_id,
                'name' => $this->project?->name,
                'slug' => $this->project?->slug,
            ],
            'server' => [
                'id' => $this->server_id,
                'name' => $this->server?->name,
                'hostname' => $this->server?->hostname,
            ],
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ],
            'rollback_of' => $this->when($this->rollback_deployment_id, [
                'id' => $this->rollback_deployment_id,
                'commit_hash' => $this->rollbackOf?->commit_hash,
            ]),

            // Links
            'links' => [
                'self' => route('api.v1.deployments.show', $this->id),
                'rollback' => route('api.v1.deployments.rollback', $this->id),
            ],
        ];
    }
}
