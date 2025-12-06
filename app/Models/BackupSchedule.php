<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\BackupScheduleFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'project_id',
        'server_id',
        'database_type',
        'database_name',
        'frequency',
        'time',
        'day_of_week',
        'day_of_month',
        'retention_days',
        'retention_daily',
        'retention_weekly',
        'retention_monthly',
        'storage_disk',
        'encrypt',
        'notify_on_failure',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'encrypt' => 'boolean',
            'notify_on_failure' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'day_of_week' => 'integer',
            'day_of_month' => 'integer',
            'retention_days' => 'integer',
            'retention_daily' => 'integer',
            'retention_weekly' => 'integer',
            'retention_monthly' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BackupSchedule $schedule) {
            if (! $schedule->next_run_at) {
                $schedule->next_run_at = $schedule->calculateNextRun();
            }
        });
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return HasMany<DatabaseBackup, $this>
     */
    public function databaseBackups(): HasMany
    {
        return $this->hasMany(DatabaseBackup::class, 'project_id', 'project_id')
            ->where('database_name', $this->database_name);
    }

    public function calculateNextRun(): Carbon
    {
        $now = now();
        $time = Carbon::parse($this->time);

        return match ($this->frequency) {
            'hourly' => $now->copy()->addHour()->startOfHour(),
            'daily' => $now->copy()->setTime($time->hour, $time->minute)->addDay(),
            'weekly' => $this->calculateWeeklyNextRun($now, $time),
            'monthly' => $this->calculateMonthlyNextRun($now, $time),
        };
    }

    protected function calculateWeeklyNextRun(Carbon $now, Carbon $time): Carbon
    {
        $next = $now->copy()->setTime($time->hour, $time->minute);
        $targetDay = $this->day_of_week ?? 0;

        while ($next->dayOfWeek !== $targetDay || $next->lte($now)) {
            $next->addDay();
        }

        return $next;
    }

    protected function calculateMonthlyNextRun(Carbon $now, Carbon $time): Carbon
    {
        $next = $now->copy()->setTime($time->hour, $time->minute);
        $targetDay = $this->day_of_month ?? 1;

        if ($next->day > $targetDay || $next->lte($now)) {
            $next->addMonth();
        }

        $next->day($targetDay);

        return $next;
    }

    public function updateNextRun(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }

    /**
     * @param  Builder<BackupSchedule>  $query
     * @return Builder<BackupSchedule>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<BackupSchedule>  $query
     * @return Builder<BackupSchedule>
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('next_run_at', '<=', now());
    }

    /**
     * @param  Builder<BackupSchedule>  $query
     * @return Builder<BackupSchedule>
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    public function getFrequencyLabelAttribute(): string
    {
        $timeStr = $this->time ?? '00:00';
        $dayOfWeek = Carbon::now()->dayOfWeek($this->day_of_week);
        $dayName = $dayOfWeek instanceof Carbon ? $dayOfWeek->format('l') : 'Monday';

        return match ($this->frequency) {
            'hourly' => 'Every Hour',
            'daily' => "Daily at {$timeStr}",
            'weekly' => "Weekly on {$dayName} at {$timeStr}",
            'monthly' => "Monthly on day {$this->day_of_month} at {$timeStr}",
        };
    }
}
