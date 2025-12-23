<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\DeploymentApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeploymentApprovalRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DeploymentApproval $approval
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $deployment = $this->approval->deployment;
        $project = $deployment->project;
        $projectName = $project?->name ?? 'Unknown Project';

        return (new MailMessage)
            ->subject("Deployment Approval Required: {$projectName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("A deployment approval has been requested for **{$projectName}**.")
            ->line("**Branch:** {$deployment->branch}")
            ->line("**Requested by:** {$this->approval->requester->name}")
            ->line('**Commit:** '.substr($deployment->commit_hash ?? '', 0, 7))
            ->line("**Message:** {$deployment->commit_message}")
            ->action('Review Deployment', url("/deployments/{$deployment->id}/approvals"))
            ->line('Please review and approve or reject this deployment.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        $deployment = $this->approval->deployment;
        $project = $deployment->project;
        $projectId = $project?->id ?? 0;
        $projectName = $project?->name ?? 'Unknown Project';

        return [
            'type' => 'deployment_approval_requested',
            'approval_id' => $this->approval->id,
            'deployment_id' => $deployment->id,
            'project_id' => $projectId,
            'project_name' => $projectName,
            'branch' => $deployment->branch,
            'requester_name' => $this->approval->requester->name,
            'message' => "Deployment approval requested for {$projectName}",
        ];
    }
}
