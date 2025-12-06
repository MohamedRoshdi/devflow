<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $server_id
 * @property int|null $project_id
 * @property string $name
 * @property string $type
 * @property string $path
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property int|null $last_position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Server|null $server
 * @property-read Project|null $project
 * @property-read string $display_name
 *
 * @method static Builder<static> active()
 * @method static Builder<static> forServer(int $serverId)
 * @method static Builder<static> forProject(int $projectId)
 */
class LogSource extends Model
{
    /** @var array<int, string> */
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

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'last_position' => 'integer',
    ];

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForServer(Builder $query, int $serverId): Builder
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->project) {
            return "{$this->name} ({$this->project->name})";
        }

        $serverName = $this->server?->name ?? 'Unknown Server';

        return "{$this->name} ({$serverName})";
    }

    /**
     * @return array<string, array<string, string>>
     */
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
