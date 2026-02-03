<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SecurityPrediction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SecurityPredictionCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SecurityPrediction $prediction
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.'.$this->prediction->server_id),
            new PrivateChannel('security-predictions'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'prediction.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'prediction_id' => $this->prediction->id,
            'server_id' => $this->prediction->server_id,
            'server_name' => $this->prediction->server->name,
            'prediction_type' => $this->prediction->prediction_type,
            'severity' => $this->prediction->severity,
            'title' => $this->prediction->title,
            'confidence_score' => $this->prediction->confidence_score,
            'created_at' => $this->prediction->created_at?->toIso8601String(),
        ];
    }
}
