<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $updateType;

    /** @var array<string, mixed> */
    public array $data;

    /**
     * Create a new event instance.
     *
     * @param  string  $updateType  Type of update (stats, server_health, activity)
     * @param  array  $data  The updated data
     */
    public function __construct(string $updateType, array $data = [])
    {
        $this->updateType = $updateType;
        $this->data = $data;
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
            'type' => $this->updateType,
            'data' => $this->data,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
