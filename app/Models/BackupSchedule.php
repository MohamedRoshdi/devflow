<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class BackupSchedule extends Model
{
    use HasFactory;

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
        'storage_disk',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'day_of_week' => 'integer',
            'day_of_month' => 'integer',
            'retention_days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BackupSchedule $schedule) {
            if (!$schedule->next_run_at) {
                $schedule->next_run_at = $schedule->calculateNextRun();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function databaseBackups(): HasMany
    {
        return $this->hasMany(DatabaseBackup::class, 'project_id', 'project_id')
            ->where('database_name', $this->database_name);
    }

    public function calculateNextRun(): Carbon
    {
        $now = now();
        $time = Carbon::parse($this->time);

        return match($this->frequency) {
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_run_at', '<=', now());
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match($this->frequency) {
            'hourly' => 'Every Hour',
            'daily' => 'Daily at ' . Carbon::parse($this->time)->format('H:i'),
            'weekly' => 'Weekly on ' . Carbon::create()->dayOfWeek($this->day_of_week)->format('l') . ' at ' . Carbon::parse($this->time)->format('H:i'),
            'monthly' => 'Monthly on day ' . $this->day_of_month . ' at ' . Carbon::parse($this->time)->format('H:i'),
        };
    }
}
