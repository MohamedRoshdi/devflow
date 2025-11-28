<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'server_id',
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
        'health_check_url',
        'last_deployed_at',
        'storage_used_mb',
        'latitude',
        'longitude',
        'auto_deploy',
        'metadata',
        'current_commit_hash',
        'current_commit_message',
        'last_commit_at',
    ];

    protected function casts(): array
    {
        return [
            'env_variables' => 'array',
            'metadata' => 'array',
            'install_commands' => 'array',
            'build_commands' => 'array',
            'post_deploy_commands' => 'array',
            'auto_deploy' => 'boolean',
            'last_deployed_at' => 'datetime',
            'last_commit_at' => 'datetime',
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
        return match($this->status) {
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
}


