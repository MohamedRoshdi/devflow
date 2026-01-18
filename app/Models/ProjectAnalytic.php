<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAnalytic extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectAnalyticFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'metric_type',
        'metric_value',
        'metadata',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'metric_value' => 'decimal:2',
            'metadata' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
