<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeploymentComment extends Model
{
    /** @use HasFactory<\Database\Factories\DeploymentCommentFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'deployment_id',
        'user_id',
        'content',
        'mentions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mentions' => 'array',
        ];
    }

    // Relationships
    /**
     * @return BelongsTo<Deployment, $this>
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Extract mentioned users from content
     * Supports @username format
     *
     * @return array<int, int>
     */
    public function extractMentions(): array
    {
        preg_match_all('/@(\w+)/', $this->content, $matches);

        if (empty($matches[1])) {
            return [];
        }

        // Find users by name or email
        $usernames = $matches[1];
        $users = User::where(function ($query) use ($usernames): void {
            foreach ($usernames as $username) {
                $query->orWhere('name', 'LIKE', "%{$username}%")
                    ->orWhere('email', 'LIKE', "%{$username}%");
            }
        })->get(['id', 'name']);

        return $users->pluck('id')->toArray();
    }

    /**
     * Format content with clickable mentions
     */
    public function getFormattedContentAttribute(): string
    {
        $content = $this->content;

        if (! empty($this->mentions)) {
            $users = User::whereIn('id', $this->mentions)->get(['id', 'name']);

            foreach ($users as $user) {
                $content = (string) preg_replace(
                    '/@'.preg_quote($user->name, '/').'\b/',
                    '<span class="text-blue-600 font-semibold">@'.$user->name.'</span>',
                    $content
                );
            }
        }

        return $content;
    }
}
