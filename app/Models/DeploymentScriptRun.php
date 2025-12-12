<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property int $deployment_script_id
 * @property int|null $deployment_id
 * @property string $status
 * @property string|null $output
 * @property string|null $error_output
 * @property int|null $exit_code
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project $project
 * @property-read DeploymentScript $script
 * @property-read Deployment|null $deployment
 * @property-read int|null $duration
 *
 * @method static \Illuminate\Database\Eloquent\Builder|DeploymentScriptRun newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DeploymentScriptRun newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DeploymentScriptRun query()
 */
class DeploymentScriptRun extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'project_id',
        'deployment_script_id',
        'deployment_id',
        'status',
        'output',
        'error_output',
        'exit_code',
        'started_at',
        'finished_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'exit_code' => 'integer',
    ];

    /**
     * @return BelongsTo<Project, DeploymentScriptRun>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<DeploymentScript, DeploymentScriptRun>
     */
    public function script(): BelongsTo
    {
        return $this->belongsTo(DeploymentScript::class, 'deployment_script_id');
    }

    /**
     * @return BelongsTo<Deployment, DeploymentScriptRun>
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    /**
     * @return int|null
     */
    public function getDurationAttribute(): ?int
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        return (int) $this->finished_at->diffInSeconds($this->started_at);
    }
}
