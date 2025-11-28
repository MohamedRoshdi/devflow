<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'framework',
        'icon',
        'color',
        'is_system',
        'is_active',
        'user_id',
        'default_branch',
        'php_version',
        'node_version',
        'install_commands',
        'build_commands',
        'post_deploy_commands',
        'env_template',
        'docker_compose_template',
        'dockerfile_template',
        'health_check_path',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'install_commands' => 'array',
        'build_commands' => 'array',
        'post_deploy_commands' => 'array',
        'env_template' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    public function scopeByFramework($query, string $framework)
    {
        return $query->where('framework', $framework);
    }

    public function canDelete(): bool
    {
        return !$this->is_system;
    }

    public function getFormattedCommandsAttribute(): array
    {
        $commands = [];

        if ($this->install_commands) {
            $commands['Install'] = $this->install_commands;
        }
        if ($this->build_commands) {
            $commands['Build'] = $this->build_commands;
        }
        if ($this->post_deploy_commands) {
            $commands['Post-Deploy'] = $this->post_deploy_commands;
        }

        return $commands;
    }

    public function applyToProject(Project $project): void
    {
        $project->update([
            'framework' => $this->framework,
            'branch' => $this->default_branch,
            'php_version' => $this->php_version,
            'node_version' => $this->node_version,
            'install_commands' => $this->install_commands,
            'build_commands' => $this->build_commands,
            'post_deploy_commands' => $this->post_deploy_commands,
            'health_check_url' => $this->health_check_path ? "https://{$project->domains->first()?->full_domain}{$this->health_check_path}" : null,
        ]);
    }
}
