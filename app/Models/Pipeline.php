<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string $provider
 * @property array<string, mixed> $configuration
 * @property array<string, mixed> $triggers
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PipelineRun> $runs
 * @property-read PipelineRun|null $latestRun
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Pipeline newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Pipeline newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Pipeline query()
 */
class Pipeline extends Model
{
    /** @use HasFactory<\Database\Factories\PipelineFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'provider',
        'configuration',
        'triggers',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'configuration' => 'array',
        'triggers' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Project, Pipeline>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<PipelineRun, Pipeline>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(PipelineRun::class);
    }

    /**
     * @return HasOne<PipelineRun, Pipeline>
     */
    public function latestRun(): HasOne
    {
        return $this->hasOne(PipelineRun::class)->latestOfMany();
    }
}
