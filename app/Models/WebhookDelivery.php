<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookDeliveryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'provider',
        'event_type',
        'payload',
        'signature',
        'status',
        'response',
        'deployment_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function deployment()
    {
        return $this->belongsTo(Deployment::class);
    }

    // Status helpers
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isIgnored(): bool
    {
        return $this->status === 'ignored';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'success' => 'green',
            'failed' => 'red',
            'ignored' => 'gray',
            'pending' => 'yellow',
            default => 'gray',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'success' => 'check-circle',
            'failed' => 'x-circle',
            'ignored' => 'minus-circle',
            'pending' => 'clock',
            default => 'question-mark-circle',
        };
    }
}
