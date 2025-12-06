<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function template()
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function deployments()
    {
        return $this->hasMany(Deployment::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function analytics()
    {
        return $this->hasMany(ProjectAnalytic::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function pipelines()
    {
        return $this->hasMany(Pipeline::class);
    }

    public function storageConfigurations()
    {
        return $this->hasMany(StorageConfiguration::class);
    }

    public function webhookDeliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function databaseBackups()
    {
        return $this->hasMany(DatabaseBackup::class);
    }

    public function backupSchedules()
    {
        return $this->hasMany(BackupSchedule::class);
    }

    public function fileBackups()
    {
        return $this->hasMany(FileBackup::class);
    }

    public function setupTasks()
    {
        return $this->hasMany(ProjectSetupTask::class);
    }

    public function pipelineStages()
    {
        return $this->hasMany(PipelineStage::class);
    }

    public function pipelineConfig()
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

    public function getLatestDeployment()
    {
        return $this->deployments()->latest()->first();
    }

    public function notificationChannels()
    {
        return $this->hasMany(NotificationChannel::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
