<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogSource extends Model
{
    protected $fillable = [
        'server_id',
        'project_id',
        'name',
        'type',
        'path',
        'is_active',
        'last_synced_at',
        'last_position',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'last_position' => 'integer',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForServer($query, int $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->project
            ? "{$this->name} ({$this->project->name})"
            : "{$this->name} ({$this->server->name})";
    }

    public static function predefinedTemplates(): array
    {
        return [
            'laravel' => [
                'name' => 'Laravel Application Logs',
                'type' => 'file',
                'path' => '/var/www/*/storage/logs/laravel.log',
                'source' => 'laravel',
            ],
            'nginx_access' => [
                'name' => 'Nginx Access Logs',
                'type' => 'file',
                'path' => '/var/log/nginx/access.log',
                'source' => 'nginx',
            ],
            'nginx_error' => [
                'name' => 'Nginx Error Logs',
                'type' => 'file',
                'path' => '/var/log/nginx/error.log',
                'source' => 'nginx',
            ],
            'php_fpm' => [
                'name' => 'PHP-FPM Logs',
                'type' => 'file',
                'path' => '/var/log/php8.4-fpm.log',
                'source' => 'php',
            ],
            'mysql' => [
                'name' => 'MySQL Error Logs',
                'type' => 'file',
                'path' => '/var/log/mysql/error.log',
                'source' => 'mysql',
            ],
            'system' => [
                'name' => 'System Logs',
                'type' => 'file',
                'path' => '/var/log/syslog',
                'source' => 'system',
            ],
            'docker' => [
                'name' => 'Docker Container Logs',
                'type' => 'docker',
                'path' => 'container_name',
                'source' => 'docker',
            ],
        ];
    }
}
