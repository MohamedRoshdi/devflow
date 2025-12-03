<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DeploymentComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'deployment_id',
        'user_id',
        'content',
        'mentions',
    ];

    protected function casts(): array
    {
        return [
            'mentions' => 'array',
        ];
    }

    // Relationships
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Extract mentioned users from content
     * Supports @username format
     */
    public function extractMentions(): array
    {
        preg_match_all('/@(\w+)/', $this->content, $matches);

        if (empty($matches[1])) {
            return [];
        }

        // Find users by name or email
        $usernames = $matches[1];
        $users = User::where(function($query) use ($usernames) {
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

        if (!empty($this->mentions)) {
            $users = User::whereIn('id', $this->mentions)->get(['id', 'name']);

            foreach ($users as $user) {
                $content = preg_replace(
                    '/@' . preg_quote($user->name, '/') . '\b/',
                    '<span class="text-blue-600 font-semibold">@' . $user->name . '</span>',
                    $content
                );
            }
        }

        return $content;
    }
}
