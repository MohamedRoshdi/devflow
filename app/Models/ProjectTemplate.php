<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $framework
 * @property string|null $icon
 * @property string|null $color
 * @property bool $is_system
 * @property bool $is_active
 * @property int|null $user_id
 * @property string $default_branch
 * @property string|null $php_version
 * @property string|null $node_version
 * @property array<int, string>|null $install_commands
 * @property array<int, string>|null $build_commands
 * @property array<int, string>|null $post_deploy_commands
 * @property array<string, mixed>|null $env_template
 * @property string|null $docker_compose_template
 * @property string|null $dockerfile_template
 * @property string|null $health_check_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read array<string, array<int, string>> $formatted_commands
 *
 * @method static Builder<static> active()
 * @method static Builder<static> system()
 * @method static Builder<static> custom()
 * @method static Builder<static> byFramework(string $framework)
 */
class ProjectTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectTemplateFactory> */
    use HasFactory;

    /** @var array<int, string> */
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

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'install_commands' => 'array',
        'build_commands' => 'array',
        'post_deploy_commands' => 'array',
        'env_template' => 'array',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeByFramework(Builder $query, string $framework): Builder
    {
        return $query->where('framework', $framework);
    }

    public function canDelete(): bool
    {
        return ! $this->is_system;
    }

    /**
     * @return array<string, array<int, string>>
     */
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
