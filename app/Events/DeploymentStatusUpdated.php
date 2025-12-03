<?php

namespace App\Events;

use App\Models\Deployment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeploymentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Deployment $deployment;
    public string $message;
    public string $type;

    /**
     * Create a new event instance.
     */
    public function __construct(Deployment $deployment, string $message, string $type = 'info')
    {
        $this->deployment = $deployment;
        $this->message = $message;
        $this->type = $type; // success, error, warning, info
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->deployment->user_id),
            new PrivateChannel('deployment.' . $this->deployment->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'deployment.status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'deployment_id' => $this->deployment->id,
            'project_id' => $this->deployment->project_id,
            'project_name' => $this->deployment->project->name,
            'status' => $this->deployment->status,
            'message' => $this->message,
            'type' => $this->type,
            'progress' => $this->deployment->progress ?? 0,
            'started_at' => $this->deployment->started_at,
            'completed_at' => $this->deployment->completed_at,
        ];
    }
}