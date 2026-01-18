<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerResource extends JsonResource
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
            'hostname' => $this->hostname,
            'ip_address' => $this->ip_address,
            'port' => $this->port,
            'username' => $this->username,
            'status' => $this->status,
            'os' => $this->os,
            'cpu_cores' => $this->cpu_cores,
            'memory_gb' => $this->memory_gb,
            'disk_gb' => $this->disk_gb,
            'docker_installed' => $this->docker_installed,
            'docker_version' => $this->docker_version,
            'location_name' => $this->location_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'last_ping_at' => $this->last_ping_at?->toISOString(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),

            // Relationships
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ],
            'projects' => ProjectResource::collection($this->whenLoaded('projects')),
            'projects_count' => $this->when($this->projects_count !== null, $this->projects_count),
            'latest_metrics' => new ServerMetricResource($this->whenLoaded('latestMetric')),

            // Links
            'links' => [
                'self' => route('api.v1.servers.show', $this->id),
                'metrics' => route('api.v1.servers.metrics', $this->id),
            ],
        ];
    }
}
