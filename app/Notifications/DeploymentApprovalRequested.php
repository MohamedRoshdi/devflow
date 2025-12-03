<?php

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

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $deployment = $this->approval->deployment;
        $project = $deployment->project;

        return (new MailMessage)
            ->subject("Deployment Approval Required: {$project->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("A deployment approval has been requested for **{$project->name}**.")
            ->line("**Branch:** {$deployment->branch}")
            ->line("**Requested by:** {$this->approval->requester->name}")
            ->line("**Commit:** " . substr($deployment->commit_hash ?? '', 0, 7))
            ->line("**Message:** {$deployment->commit_message}")
            ->action('Review Deployment', url("/deployments/{$deployment->id}/approvals"))
            ->line('Please review and approve or reject this deployment.');
    }

    public function toArray($notifiable): array
    {
        $deployment = $this->approval->deployment;
        $project = $deployment->project;

        return [
            'type' => 'deployment_approval_requested',
            'approval_id' => $this->approval->id,
            'deployment_id' => $deployment->id,
            'project_id' => $project->id,
            'project_name' => $project->name,
            'branch' => $deployment->branch,
            'requester_name' => $this->approval->requester->name,
            'message' => "Deployment approval requested for {$project->name}",
        ];
    }
}
