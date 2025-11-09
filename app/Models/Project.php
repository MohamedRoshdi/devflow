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
        'name',
        'slug',
        'repository_url',
        'branch',
        'framework',
        'php_version',
        'node_version',
        'port',
        'root_directory',
        'build_command',
        'start_command',
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
            'auto_deploy' => 'boolean',
            'last_deployed_at' => 'datetime',
            'last_commit_at' => 'datetime',
            'storage_used_mb' => 'integer',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
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


