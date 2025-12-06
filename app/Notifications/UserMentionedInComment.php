<?php

declare(strict_types=1);

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

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $deployment = $this->comment->deployment;
        $project = $deployment?->project;
        $author = $this->comment->user;

        $authorName = $author?->name ?? 'Someone';
        $projectName = $project?->name ?? 'Unknown Project';
        $deploymentId = $deployment?->id ?? 0;

        return (new MailMessage)
            ->subject("{$authorName} mentioned you in a comment")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$authorName} mentioned you in a comment on deployment for **{$projectName}**.")
            ->line('**Comment:**')
            ->line($this->comment->content)
            ->action('View Comment', url("/deployments/{$deploymentId}#comment-{$this->comment->id}"))
            ->line('Click the button above to view the full conversation.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        $deployment = $this->comment->deployment;
        $project = $deployment?->project;
        $author = $this->comment->user;

        return [
            'type' => 'user_mentioned_in_comment',
            'comment_id' => $this->comment->id,
            'deployment_id' => $deployment?->id ?? 0,
            'project_id' => $project?->id ?? 0,
            'project_name' => $project?->name ?? 'Unknown',
            'author_name' => $author?->name ?? 'Unknown',
            'message' => ($author?->name ?? 'Someone').' mentioned you in a comment',
        ];
    }
}
