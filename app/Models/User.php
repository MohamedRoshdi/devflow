<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'timezone',
        'last_login_at',
        'current_team_id',
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
        ];
    }

    // Relationships
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function deployments()
    {
        return $this->hasMany(Deployment::class);
    }

    public function sshKeys()
    {
        return $this->hasMany(SSHKey::class);
    }

    public function serverTags()
    {
        return $this->hasMany(ServerTag::class);
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    // Team relationships
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot(['role', 'permissions', 'invited_by', 'joined_at'])
            ->withTimestamps();
    }

    public function ownedTeams()
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function currentTeam()
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function teamInvitations()
    {
        return $this->hasMany(TeamInvitation::class, 'invited_by');
    }

    public function settings()
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
    public function requestedApprovals()
    {
        return $this->hasMany(DeploymentApproval::class, 'requested_by');
    }

    public function approvedDeployments()
    {
        return $this->hasMany(DeploymentApproval::class, 'approved_by');
    }

    public function deploymentComments()
    {
        return $this->hasMany(DeploymentComment::class);
    }

    public function auditLogs()
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
