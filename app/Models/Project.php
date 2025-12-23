<?php

declare(strict_types=1);

namespace App\Models;

use App\Mappers\HealthScoreMapper;
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
        'notes',
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
        'webhook_provider',
        'webhook_id',
        'webhook_url',
        'metadata',
        'current_commit_hash',
        'current_commit_message',
        'last_commit_at',
        'requires_approval',
        'approval_settings',
        'kubernetes_cluster_id',
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
        static::saving(function (Project $project) {
            $project->sanitizeInputs();
            $project->validatePathSecurity();
        });

        static::updated(function (Project $project) {
            // Clear project-specific cache when project is updated
            cache()->forget("project_{$project->id}_stats");
        });
    }

    /**
     * Sanitize input fields by stripping HTML tags to prevent XSS attacks.
     */
    protected function sanitizeInputs(): void
    {
        $sanitizeFields = ['name', 'repository_url', 'branch', 'health_check_url', 'notes'];

        foreach ($sanitizeFields as $field) {
            if (isset($this->attributes[$field]) && is_string($this->attributes[$field])) {
                $this->attributes[$field] = strip_tags($this->attributes[$field]);
            }
        }
    }

    /**
     * Validate path-related fields to prevent path traversal attacks.
     *
     * @throws \InvalidArgumentException if path contains unsafe patterns
     */
    protected function validatePathSecurity(): void
    {
        $pathFields = ['root_directory'];

        foreach ($pathFields as $field) {
            if (! isset($this->attributes[$field]) || ! is_string($this->attributes[$field])) {
                continue;
            }

            $path = $this->attributes[$field];

            // Reject path traversal sequences
            if (str_contains($path, '..')) {
                throw new \InvalidArgumentException(
                    "The {$field} field contains path traversal sequences (..) which are not allowed."
                );
            }

            // Reject absolute paths to sensitive system directories
            // Allow /var/www/ as it's a legitimate web root
            $dangerousPaths = ['/etc/', '/var/log/', '/var/run/', '/root/', '/home/', '/usr/', '/bin/', '/sbin/', '/boot/', '/proc/', '/sys/'];
            foreach ($dangerousPaths as $dangerous) {
                if (str_starts_with(strtolower($path), $dangerous)) {
                    throw new \InvalidArgumentException(
                        "The {$field} field cannot point to system directories."
                    );
                }
            }

            // Reject Windows system paths
            if (preg_match('/^[A-Za-z]:\\\\(Windows|System|Program)/i', $path)) {
                throw new \InvalidArgumentException(
                    "The {$field} field cannot point to Windows system directories."
                );
            }

            // Sanitize the path by removing any traversal attempts
            $this->attributes[$field] = preg_replace('/\.\.[\\/\\\\]/', '', $path);
        }
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
     * @return BelongsTo<KubernetesCluster, $this>
     */
    public function kubernetesCluster(): BelongsTo
    {
        return $this->belongsTo(KubernetesCluster::class);
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
        return HealthScoreMapper::statusToColor($this->status);
    }

    /**
     * Get validated slug safe for shell commands.
     *
     * Provides defense-in-depth validation before shell command usage.
     * This accessor is cached per model instance, avoiding repeated validation.
     *
     * @throws \InvalidArgumentException if slug contains unsafe characters
     */
    public function getValidatedSlugAttribute(): string
    {
        $slug = $this->slug;

        // Validate slug format (lowercase alphanumeric and hyphens only)
        if (! preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new \InvalidArgumentException(
                "Project slug '{$slug}' contains invalid characters. Only lowercase letters, numbers, and hyphens are allowed."
            );
        }

        // Additional safety: prevent directory traversal attempts
        if (str_contains($slug, '..') || str_contains($slug, '/')) {
            throw new \InvalidArgumentException(
                "Project slug '{$slug}' contains path traversal characters."
            );
        }

        return $slug;
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

    /**
     * @return HasMany<DockerRegistry, $this>
     */
    public function dockerRegistries(): HasMany
    {
        return $this->hasMany(DockerRegistry::class);
    }

    /**
     * @return HasOne<DockerRegistry, $this>
     */
    public function defaultDockerRegistry(): HasOne
    {
        return $this->hasOne(DockerRegistry::class)
            ->where('is_default', true)
            ->where('status', 'active');
    }
}
