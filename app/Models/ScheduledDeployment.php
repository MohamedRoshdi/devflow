<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $user_id
 * @property string $branch
 * @property \Illuminate\Support\Carbon $scheduled_at
 * @property string $timezone
 * @property string $status
 * @property string|null $notes
 * @property int|null $deployment_id
 * @property \Illuminate\Support\Carbon|null $executed_at
 * @property bool $notify_before
 * @property int|null $notify_minutes
 * @property bool $notified
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Project $project
 * @property-read User|null $user
 * @property-read Deployment|null $deployment
 * @property-read string $scheduled_at_local
 * @property-read string $time_until
 *
 * @method static Builder|ScheduledDeployment pending()
 * @method static Builder|ScheduledDeployment due()
 * @method static Builder|ScheduledDeployment upcoming()
 */
class ScheduledDeployment extends Model
{
    /**
     * @var array<int, string>
     */
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

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
        'notify_before' => 'boolean',
        'notified' => 'boolean',
    ];

    /**
     * @return BelongsTo<Project, ScheduledDeployment>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, ScheduledDeployment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Deployment, ScheduledDeployment>
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    /**
     * @param  Builder<ScheduledDeployment>  $query
     * @return Builder<ScheduledDeployment>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * @param  Builder<ScheduledDeployment>  $query
     * @return Builder<ScheduledDeployment>
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('scheduled_at', '<=', now())
            ->where('status', 'pending');
    }

    /**
     * @param  Builder<ScheduledDeployment>  $query
     * @return Builder<ScheduledDeployment>
     */
    public function scopeUpcoming(Builder $query): Builder
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
