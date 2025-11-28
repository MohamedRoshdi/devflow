<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledDeployment extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'branch',
        'scheduled_at',
        'timezone',
        'status',
        'notes',
        'deployment_id',
        'executed_at',
        'notify_before',
        'notify_minutes',
        'notified',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
        'notify_before' => 'boolean',
        'notified' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue($query)
    {
        return $query->where('scheduled_at', '<=', now())
            ->where('status', 'pending');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
            ->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['pending']);
    }

    public function getScheduledAtLocalAttribute(): string
    {
        return $this->scheduled_at
            ->timezone($this->timezone)
            ->format('M d, Y H:i');
    }

    public function getTimeUntilAttribute(): string
    {
        if ($this->scheduled_at->isPast()) {
            return 'Overdue';
        }

        return $this->scheduled_at->diffForHumans();
    }
}
