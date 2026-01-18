<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'repository_url' => $this->repository_url,
            'branch' => $this->branch,
            'framework' => $this->framework,
            'project_type' => $this->project_type,
            'environment' => $this->environment,
            'php_version' => $this->php_version,
            'node_version' => $this->node_version,
            'port' => $this->port,
            'root_directory' => $this->root_directory,
            'build_command' => $this->build_command,
            'start_command' => $this->start_command,
            'install_commands' => $this->install_commands,
            'build_commands' => $this->build_commands,
            'post_deploy_commands' => $this->post_deploy_commands,
            'env_variables' => $this->when($request->has('include_env'), $this->env_variables),
            'status' => $this->status,
            'health_check_url' => $this->health_check_url,
            'last_deployed_at' => $this->last_deployed_at?->toISOString(),
            'storage_used_mb' => $this->storage_used_mb,
            'auto_deploy' => $this->auto_deploy,
            'webhook_enabled' => $this->webhook_enabled,
            'webhook_secret' => $this->when($request->has('include_webhook'), $this->webhook_secret),
            'current_commit_hash' => $this->current_commit_hash,
            'current_commit_message' => $this->current_commit_message,
            'last_commit_at' => $this->last_commit_at?->toISOString(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),

            // Relationships
            'server' => new ServerResource($this->whenLoaded('server')),
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ],
            'latest_deployment' => new DeploymentResource($this->whenLoaded('latestDeployment')),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'deployments_count' => $this->when($this->deployments_count !== null, $this->deployments_count),

            // Links
            'links' => [
                'self' => route('api.v1.projects.show', $this->slug),
                'deployments' => route('api.v1.projects.deployments.index', $this->slug),
                'deploy' => route('api.v1.projects.deploy', $this->slug),
            ],
        ];
    }
}
