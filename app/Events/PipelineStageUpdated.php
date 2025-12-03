<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PipelineStageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $pipelineRunId;
    public int $stageRunId;
    public string $stageName;
    public string $status;
    public string $output;
    public int $progressPercent;
    public string $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $pipelineRunId,
        int $stageRunId,
        string $stageName,
        string $status,
        string $output,
        int $progressPercent = 0
    ) {
        $this->pipelineRunId = $pipelineRunId;
        $this->stageRunId = $stageRunId;
        $this->stageName = $stageName;
        $this->status = $status;
        $this->output = $output;
        $this->progressPercent = $progressPercent;
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
            new Channel('pipeline.' . $this->pipelineRunId),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'pipeline_run_id' => $this->pipelineRunId,
            'stage_run_id' => $this->stageRunId,
            'stage_name' => $this->stageName,
            'status' => $this->status,
            'output' => $this->output,
            'progress_percent' => $this->progressPercent,
            'timestamp' => $this->timestamp,
            'status_color' => $this->getStatusColor(),
            'status_icon' => $this->getStatusIcon(),
        ];
    }

    /**
     * Get status color for UI
     */
    private function getStatusColor(): string
    {
        return match($this->status) {
            'success' => 'green',
            'failed' => 'red',
            'running' => 'yellow',
            'pending' => 'blue',
            'skipped' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status icon for UI
     */
    private function getStatusIcon(): string
    {
        return match($this->status) {
            'success' => 'check-circle',
            'failed' => 'x-circle',
            'running' => 'arrow-path',
            'pending' => 'clock',
            'skipped' => 'minus-circle',
            default => 'question-mark-circle',
        };
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'pipeline.stage.updated';
    }
}
