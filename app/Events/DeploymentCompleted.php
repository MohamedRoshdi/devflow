<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Deployment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeploymentCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Deployment $deployment;

    /**
     * Create a new event instance.
     */
    public function __construct(Deployment $deployment)
    {
        $this->deployment = $deployment;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('dashboard'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'deployment_id' => $this->deployment->id,
            'project_name' => $this->deployment->project?->name,
            'project_id' => $this->deployment->project_id,
            'status' => $this->deployment->status,
            'branch' => $this->deployment->branch,
            'triggered_by' => $this->deployment->triggered_by,
            'started_at' => $this->deployment->started_at?->toIso8601String(),
            'completed_at' => $this->deployment->completed_at?->toIso8601String(),
            'duration_seconds' => $this->deployment->duration_seconds,
        ];
    }
}
