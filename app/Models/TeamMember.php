<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'permissions',
        'invited_by',
        'joined_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
        return match($this->role) {
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
