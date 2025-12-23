<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property string $role
 * @property array<int, string>|null $permissions
 * @property int|null $invited_by
 * @property \Illuminate\Support\Carbon|null $joined_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Team $team
 * @property-read User $user
 * @property-read User $inviter
 */
class TeamMember extends Model
{
    /** @use HasFactory<\Database\Factories\TeamMemberFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'permissions',
        'invited_by',
        'joined_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Team, TeamMember>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, TeamMember>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, TeamMember>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function hasPermission(string $permission): bool
    {
        // Owner and admin have all permissions
        if (in_array($this->role, ['owner', 'admin'])) {
            return true;
        }

        // Check custom permissions
        if ($this->permissions) {
            return in_array($permission, $this->permissions);
        }

        // Default role permissions
        return match ($this->role) {
            'member' => in_array($permission, [
                'view_projects',
                'view_deployments',
                'view_logs',
                'view_servers',
            ]),
            'viewer' => in_array($permission, [
                'view_projects',
                'view_deployments',
                'view_logs',
            ]),
            default => false,
        };
    }
}
