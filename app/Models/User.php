<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'timezone',
        'last_login_at',
        'current_team_id',
        'show_inline_help',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'show_inline_help' => 'boolean',
        ];
    }

    // Relationships
    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * @return HasMany<Deployment, $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * @return HasMany<SSHKey, $this>
     */
    public function sshKeys(): HasMany
    {
        return $this->hasMany(SSHKey::class);
    }

    /**
     * @return HasMany<ServerTag, $this>
     */
    public function serverTags(): HasMany
    {
        return $this->hasMany(ServerTag::class);
    }

    /**
     * @return HasMany<ApiToken, $this>
     */
    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    // Team relationships
    /**
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot(['role', 'permissions', 'invited_by', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * @return HasMany<TeamInvitation, $this>
     */
    public function teamInvitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class, 'invited_by');
    }

    /**
     * @return HasOne<UserSettings, $this>
     */
    public function settings(): HasOne
    {
        return $this->hasOne(UserSettings::class);
    }

    /**
     * Get user settings or create default ones
     */
    public function getSettings(): UserSettings
    {
        return UserSettings::getForUser($this);
    }

    // Collaboration relationships
    /**
     * @return HasMany<DeploymentApproval, $this>
     */
    public function requestedApprovals(): HasMany
    {
        return $this->hasMany(DeploymentApproval::class, 'requested_by');
    }

    /**
     * @return HasMany<DeploymentApproval, $this>
     */
    public function approvedDeployments(): HasMany
    {
        return $this->hasMany(DeploymentApproval::class, 'approved_by');
    }

    /**
     * @return HasMany<DeploymentComment, $this>
     */
    public function deploymentComments(): HasMany
    {
        return $this->hasMany(DeploymentComment::class);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // Helper method for avatar URL
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=ffffff&background=6366f1&bold=true';
    }
}
