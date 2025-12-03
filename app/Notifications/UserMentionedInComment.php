<?php

namespace App\Notifications;

use App\Models\DeploymentComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserMentionedInComment extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DeploymentComment $comment
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $deployment = $this->comment->deployment;
        $project = $deployment->project;
        $author = $this->comment->user;

        return (new MailMessage)
            ->subject("{$author->name} mentioned you in a comment")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$author->name} mentioned you in a comment on deployment for **{$project->name}**.")
            ->line("**Comment:**")
            ->line($this->comment->content)
            ->action('View Comment', url("/deployments/{$deployment->id}#comment-{$this->comment->id}"))
            ->line('Click the button above to view the full conversation.');
    }

    public function toArray($notifiable): array
    {
        $deployment = $this->comment->deployment;
        $project = $deployment->project;

        return [
            'type' => 'user_mentioned_in_comment',
            'comment_id' => $this->comment->id,
            'deployment_id' => $deployment->id,
            'project_id' => $project->id,
            'project_name' => $project->name,
            'author_name' => $this->comment->user->name,
            'message' => "{$this->comment->user->name} mentioned you in a comment",
        ];
    }
}
