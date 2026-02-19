<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueGreenEnvironment extends Model
{
    /** @use HasFactory<\Database\Factories\BlueGreenEnvironmentFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'environment',
        'status',
        'port',
        'commit_hash',
        'health_status',
        'last_health_check_at',
        'container_ids',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'container_ids' => 'array',
            'metadata' => 'array',
            'last_health_check_at' => 'datetime',
            'port' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isHealthy(): bool
    {
        return $this->health_status === 'healthy';
    }

    public function isBlue(): bool
    {
        return $this->environment === 'blue';
    }

    public function isGreen(): bool
    {
        return $this->environment === 'green';
    }
}
