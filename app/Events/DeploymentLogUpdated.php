<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast-only event for real-time deployment log streaming.
 *
 * This event streams deployment log output in real-time to the frontend,
 * allowing users to watch deployment progress as it happens.
 *
 * Each log line is broadcast immediately with its severity level (info, warning, error).
 *
 * No server-side listener required - this event only broadcasts to frontend clients.
 */
class DeploymentLogUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $deploymentId;

    public string $line;

    public string $level;

    public string $timestamp;

    /**
     * Create a new event instance.
     *
     * @param  int  $deploymentId  The deployment ID
     * @param  string  $line  The log line content
     * @param  string  $level  Log level (info, warning, error, debug)
     */
    public function __construct(int $deploymentId, string $line, string $level = 'info')
    {
        $this->deploymentId = $deploymentId;
        $this->line = $line;
        $this->level = $level;
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('deployment-logs.'.$this->deploymentId),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'deployment_id' => $this->deploymentId,
            'line' => $this->line,
            'level' => $this->level,
            'timestamp' => $this->timestamp,
        ];
    }
}
