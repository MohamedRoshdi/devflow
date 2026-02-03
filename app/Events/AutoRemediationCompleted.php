<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Server;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AutoRemediationCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array<string, mixed> $results
     */
    public function __construct(
        public Server $server,
        public array $results
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.'.$this->server->id),
            new PrivateChannel('security-incidents'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'remediation.completed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'server_id' => $this->server->id,
            'server_name' => $this->server->name,
            'results_count' => count($this->results),
            'completed_at' => now()->toIso8601String(),
        ];
    }
}
