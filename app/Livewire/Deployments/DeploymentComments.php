<?php

declare(strict_types=1);

namespace App\Livewire\Deployments;

use App\Models\{Deployment, DeploymentComment, User};
use Livewire\Component;
use Livewire\Attributes\{Computed, On};

class DeploymentComments extends Component
{
    public Deployment $deployment;
    public string $newComment = '';
    public ?int $editingCommentId = null;
    public string $editingContent = '';

    public function mount(Deployment $deployment): void
    {
        $this->deployment = $deployment;
    }

    #[Computed]
    public function comments()
    {
        return $this->deployment->comments()
            ->with('user')
            ->latest()
            ->get();
    }

    public function addComment(): void
    {
        $this->validate([
            'newComment' => 'required|string|max:5000',
        ]);

        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => auth()->id(),
            'content' => $this->newComment,
        ]);

        // Extract and save mentions
        $mentions = $comment->extractMentions();
        if (!empty($mentions)) {
            $comment->update(['mentions' => $mentions]);

            // Notify mentioned users
            $this->notifyMentionedUsers($mentions, $comment);
        }

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Comment added successfully'
        ]);

        $this->reset('newComment');
        unset($this->comments);
    }

    public function startEditing(int $commentId): void
    {
        $comment = DeploymentComment::findOrFail($commentId);

        // Only allow editing own comments
        if ($comment->user_id !== auth()->id()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You can only edit your own comments'
            ]);
            return;
        }

        $this->editingCommentId = $commentId;
        $this->editingContent = $comment->content;
    }

    public function updateComment(): void
    {
        $this->validate([
            'editingContent' => 'required|string|max:5000',
        ]);

        $comment = DeploymentComment::findOrFail($this->editingCommentId);

        if ($comment->user_id !== auth()->id()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You can only edit your own comments'
            ]);
            return;
        }

        $comment->update(['content' => $this->editingContent]);

        // Update mentions
        $mentions = $comment->extractMentions();
        $comment->update(['mentions' => $mentions]);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Comment updated successfully'
        ]);

        $this->reset('editingCommentId', 'editingContent');
        unset($this->comments);
    }

    public function cancelEditing(): void
    {
        $this->reset('editingCommentId', 'editingContent');
    }

    public function deleteComment(int $commentId): void
    {
        $comment = DeploymentComment::findOrFail($commentId);

        // Only allow deleting own comments or if user is admin
        if ($comment->user_id !== auth()->id() && !auth()->user()->can('manage_all_comments')) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You can only delete your own comments'
            ]);
            return;
        }

        $comment->delete();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Comment deleted successfully'
        ]);

        unset($this->comments);
    }

    #[On('comment-added')]
    public function onCommentAdded(): void
    {
        unset($this->comments);
    }

    private function notifyMentionedUsers(array $userIds, DeploymentComment $comment): void
    {
        $users = User::whereIn('id', $userIds)
            ->where('id', '!=', auth()->id()) // Don't notify the commenter
            ->get();

        foreach ($users as $user) {
            $user->notify(new \App\Notifications\UserMentionedInComment($comment));
        }
    }

    public function render()
    {
        return view('livewire.deployments.deployment-comments');
    }
}
