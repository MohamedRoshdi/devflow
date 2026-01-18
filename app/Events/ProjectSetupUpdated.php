<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Project;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast-only event for real-time project setup progress updates.
 *
 * This event broadcasts project initialization progress to the frontend, including:
 * - Setup task status changes
 * - Progress percentage updates
 * - Task-specific messages and errors
 * - Overall setup completion status
 *
 * No server-side listener required - this event only broadcasts to frontend clients.
 */
class ProjectSetupUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Project  $project  The project being set up
     */
    public function __construct(
        public Project $project
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('project.'.$this->project->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $this->project->load('setupTasks');

        return [
            'project_id' => $this->project->id,
            'setup_status' => $this->project->setup_status,
            'setup_progress' => $this->project->setup_progress,
            'setup_completed_at' => $this->project->setup_completed_at?->toIso8601String(),
            'tasks' => $this->project->setupTasks->map(fn ($task) => [
                'id' => $task->id,
                'type' => $task->task_type,
                'label' => \App\Models\ProjectSetupTask::getTypeLabel($task->task_type),
                'status' => $task->status,
                'progress' => $task->progress,
                'message' => $task->message,
                'status_color' => $task->status_color,
                'status_icon' => $task->status_icon,
            ])->toArray(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'setup.updated';
    }
}
