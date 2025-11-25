<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id',
        'run_number',
        'status',
        'triggered_by',
        'branch',
        'commit_sha',
        'started_at',
        'finished_at',
        'logs',
        'artifacts',
    ];

    protected $casts = [
        'logs' => 'array',
        'artifacts' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function getDurationAttribute()
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        return $this->finished_at->diffInSeconds($this->started_at);
    }
}