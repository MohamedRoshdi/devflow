<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SecurityIncident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SecurityIncidentDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SecurityIncident $incident
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.'.$this->incident->server_id),
            new PrivateChannel('security-incidents'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'incident.detected';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'incident_id' => $this->incident->id,
            'server_id' => $this->incident->server_id,
            'server_name' => $this->incident->server->name,
            'incident_type' => $this->incident->incident_type,
            'severity' => $this->incident->severity,
            'title' => $this->incident->title,
            'detected_at' => $this->incident->detected_at->toIso8601String(),
        ];
    }
}
