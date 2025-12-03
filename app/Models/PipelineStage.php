<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class PipelineStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'type',
        'order',
        'commands',
        'enabled',
        'continue_on_failure',
        'timeout_seconds',
        'environment_variables',
    ];

    protected function casts(): array
    {
        return [
            'commands' => 'array',
            'environment_variables' => 'array',
            'enabled' => 'boolean',
            'continue_on_failure' => 'boolean',
            'timeout_seconds' => 'integer',
            'order' => 'integer',
        ];
    }

    /**
     * Relationship: Stage belongs to a project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relationship: Stage has many stage runs
     */
    public function stageRuns(): HasMany
    {
        return $this->hasMany(PipelineStageRun::class);
    }

    /**
     * Relationship: Get the latest stage run
     */
    public function latestRun()
    {
        return $this->hasOne(PipelineStageRun::class)->latestOfMany();
    }

    /**
     * Scope: Get only enabled stages
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope: Get stages ordered by their order field
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Scope: Get stages by type
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Get the icon for the stage based on name/type
     */
    public function getIconAttribute(): string
    {
        $name = strtolower($this->name);

        if (str_contains($name, 'test')) {
            return 'flask';
        } elseif (str_contains($name, 'build')) {
            return 'gear';
        } elseif (str_contains($name, 'deploy')) {
            return 'rocket';
        } elseif (str_contains($name, 'security') || str_contains($name, 'scan')) {
            return 'shield';
        } elseif (str_contains($name, 'install') || str_contains($name, 'composer') || str_contains($name, 'npm')) {
            return 'package';
        } elseif (str_contains($name, 'migrate') || str_contains($name, 'database')) {
            return 'database';
        } else {
            return 'code';
        }
    }

    /**
     * Get the color scheme for the stage based on type
     */
    public function getColorAttribute(): string
    {
        return match($this->type) {
            'pre_deploy' => 'blue',
            'deploy' => 'green',
            'post_deploy' => 'purple',
            default => 'gray',
        };
    }
}
