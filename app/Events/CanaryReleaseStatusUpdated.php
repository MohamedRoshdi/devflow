<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\CanaryRelease;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CanaryReleaseStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CanaryRelease $canaryRelease
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('canary-releases'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'canary.status.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->canaryRelease->id,
            'project_id' => $this->canaryRelease->project_id,
            'status' => $this->canaryRelease->status,
            'current_weight' => $this->canaryRelease->current_weight,
            'current_step' => $this->canaryRelease->current_step,
        ];
    }
}
