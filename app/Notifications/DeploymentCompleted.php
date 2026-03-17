<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Deployment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DeploymentCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Deployment $deployment,
        public bool $success
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $projectName = $this->deployment->project?->name ?? 'Unknown';

        return [
            'deployment_id' => $this->deployment->id,
            'project_name' => $projectName,
            'status' => $this->success ? 'success' : 'failed',
            'message' => $this->success
                ? "Deployment #{$this->deployment->id} for {$projectName} completed successfully"
                : "Deployment #{$this->deployment->id} for {$projectName} failed",
            'commit' => $this->deployment->commit_hash,
        ];
    }
}
