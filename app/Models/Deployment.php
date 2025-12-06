<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    /** @use HasFactory<\Database\Factories\DeploymentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'server_id',
        'commit_hash',
        'commit_message',
        'branch',
        'status',
        'output_log',
        'error_log',
        'error_message',
        'started_at',
        'completed_at',
        'duration_seconds',
        'triggered_by',
        'rollback_deployment_id',
        'environment_snapshot',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'environment_snapshot' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    // Rollback relationship
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Deployment, $this>
     */
    public function rollbackOf()
    {
        return $this->belongsTo(Deployment::class, 'rollback_deployment_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Deployment, $this>
     */
    public function rollbacks()
    {
        return $this->hasMany(Deployment::class, 'rollback_deployment_id');
    }

    // Relationships
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Project, $this>
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Server, $this>
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    // Status helpers
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'success' => 'green',
            'failed' => 'red',
            'running' => 'yellow',
            'pending' => 'blue',
            default => 'gray',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'success' => 'check-circle',
            'failed' => 'x-circle',
            'running' => 'arrow-path',
            'pending' => 'clock',
            default => 'question-mark-circle',
        };
    }

    // New collaboration relationships
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<DeploymentApproval, $this>
     */
    public function approvals()
    {
        return $this->hasMany(DeploymentApproval::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<DeploymentApproval, $this>
     */
    public function pendingApproval()
    {
        return $this->hasOne(DeploymentApproval::class)->where('status', 'pending');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<DeploymentComment, $this>
     */
    public function comments()
    {
        return $this->hasMany(DeploymentComment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<AuditLog, $this>
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    // Approval helpers
    public function requiresApproval(): bool
    {
        return $this->project && $this->project->requires_approval;
    }

    public function isApproved(): bool
    {
        return $this->approvals()->where('status', 'approved')->exists();
    }

    public function isPendingApproval(): bool
    {
        return $this->approvals()->where('status', 'pending')->exists();
    }
}
