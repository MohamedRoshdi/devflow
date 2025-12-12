<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property array<string, mixed> $metadata
 * @property array<string, mixed> $approval_settings
 */
class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'server_id',
        'team_id',
        'template_id',
        'name',
        'slug',
        'repository_url',
        'branch',
        'framework',
        'deployment_method',
        'project_type',
        'environment',
        'php_version',
        'node_version',
        'port',
        'root_directory',
        'build_command',
        'start_command',
        'install_commands',
        'build_commands',
        'post_deploy_commands',
        'env_variables',
        'status',
        'setup_status',
        'setup_config',
        'setup_completed_at',
        'health_check_url',
        'last_deployed_at',
        'storage_used_mb',
        'latitude',
        'longitude',
        'auto_deploy',
        'webhook_secret',
        'webhook_enabled',
        'metadata',
        'current_commit_hash',
        'current_commit_message',
        'last_commit_at',
        'requires_approval',
        'approval_settings',
    ];

    protected function casts(): array
    {
        return [
            'env_variables' => 'array',
            'metadata' => 'array',
            'install_commands' => 'array',
            'build_commands' => 'array',
            'post_deploy_commands' => 'array',
            'setup_config' => 'array',
            'approval_settings' => 'array',
            'auto_deploy' => 'boolean',
            'webhook_enabled' => 'boolean',
            'requires_approval' => 'boolean',
            'last_deployed_at' => 'datetime',
            'last_commit_at' => 'datetime',
            'setup_completed_at' => 'datetime',
            'storage_used_mb' => 'integer',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::updated(function (Project $project) {
            // Clear project-specific cache when project is updated
            cache()->forget("project_{$project->id}_stats");
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<ProjectTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }

    // Relationships
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return HasMany<Deployment, $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * @return HasOne<Deployment, $this>
     */
    public function latestDeployment(): HasOne
    {
        return $this->hasOne(Deployment::class)->latestOfMany();
    }

    /**
     * @return HasOne<Deployment, $this>
     */
    public function activeDeployment(): HasOne
    {
        return $this->hasOne(Deployment::class)
            ->whereIn('status', ['pending', 'running'])
            ->latest();
    }

    /**
     * @return HasMany<Domain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * @return HasMany<ProjectAnalytic, $this>
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(ProjectAnalytic::class);
    }

    /**
     * @return HasMany<Tenant, $this>
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * @return HasMany<Pipeline, $this>
     */
    public function pipelines(): HasMany
    {
        return $this->hasMany(Pipeline::class);
    }

    /**
     * @return HasMany<StorageConfiguration, $this>
     */
    public function storageConfigurations(): HasMany
    {
        return $this->hasMany(StorageConfiguration::class);
    }

    /**
     * @return HasMany<WebhookDelivery, $this>
     */
    public function webhookDeliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * @return HasMany<DatabaseBackup, $this>
     */
    public function databaseBackups(): HasMany
    {
        return $this->hasMany(DatabaseBackup::class);
    }

    /**
     * @return HasMany<BackupSchedule, $this>
     */
    public function backupSchedules(): HasMany
    {
        return $this->hasMany(BackupSchedule::class);
    }

    /**
     * @return HasMany<FileBackup, $this>
     */
    public function fileBackups(): HasMany
    {
        return $this->hasMany(FileBackup::class);
    }

    /**
     * @return HasMany<ProjectSetupTask, $this>
     */
    public function setupTasks(): HasMany
    {
        return $this->hasMany(ProjectSetupTask::class);
    }

    /**
     * @return HasMany<PipelineStage, $this>
     */
    public function pipelineStages(): HasMany
    {
        return $this->hasMany(PipelineStage::class);
    }

    /**
     * @return HasOne<PipelineConfig, $this>
     */
    public function pipelineConfig(): HasOne
    {
        return $this->hasOne(PipelineConfig::class);
    }

    // Setup status helpers
    public function isSetupPending(): bool
    {
        return $this->setup_status === 'pending';
    }

    public function isSetupInProgress(): bool
    {
        return $this->setup_status === 'in_progress';
    }

    public function isSetupCompleted(): bool
    {
        return $this->setup_status === 'completed';
    }

    public function isSetupFailed(): bool
    {
        return $this->setup_status === 'failed';
    }

    public function getSetupProgressAttribute(): int
    {
        // Use loaded relationship if available to prevent N+1 queries
        if (! $this->relationLoaded('setupTasks')) {
            // If relationship not loaded, use a single query
            $avgProgress = $this->setupTasks()->avg('progress');

            return $avgProgress ? (int) round($avgProgress) : 0;
        }

        $tasks = $this->setupTasks;
        if ($tasks->isEmpty()) {
            return 0;
        }

        return (int) round($tasks->avg('progress'));
    }

    // Webhook methods
    public function generateWebhookSecret(): string
    {
        return bin2hex(random_bytes(32)); // Generates a 64-character hex string
    }

    // Status helpers
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isStopped(): bool
    {
        return $this->status === 'stopped';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'running' => 'green',
            'stopped' => 'red',
            'building' => 'yellow',
            'error' => 'red',
            default => 'gray',
        };
    }

    /**
     * @return HasMany<NotificationChannel, $this>
     */
    public function notificationChannels(): HasMany
    {
        return $this->hasMany(NotificationChannel::class);
    }

    /**
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
