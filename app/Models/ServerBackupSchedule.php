<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerBackupSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\ServerBackupScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'type',
        'frequency',
        'time',
        'day_of_week',
        'day_of_month',
        'retention_days',
        'storage_driver',
        'is_active',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'day_of_week' => 'integer',
            'day_of_month' => 'integer',
            'retention_days' => 'integer',
        ];
    }

    // Relationships
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    // Helpers
    public function isDue(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = Carbon::now();

        // Parse the time (HH:MM format)
        [$hour, $minute] = explode(':', $this->time);

        // Check if we've already run today
        if ($this->last_run_at && $this->last_run_at->isToday()) {
            return false;
        }

        // Check if it's time to run based on frequency
        $shouldRun = match ($this->frequency) {
            'daily' => true,
            'weekly' => $now->dayOfWeek === $this->day_of_week,
            'monthly' => $now->day === $this->day_of_month,
            default => false,
        };

        // Check if the time has passed
        $scheduledTime = Carbon::today()->setTime((int) $hour, (int) $minute);

        return $shouldRun && $now->gte($scheduledTime);
    }

    public function getNextRunAttribute(): ?Carbon
    {
        if (! $this->is_active) {
            return null;
        }

        [$hour, $minute] = explode(':', $this->time);
        $baseTime = Carbon::today()->setTime((int) $hour, (int) $minute);

        return match ($this->frequency) {
            'daily' => $baseTime->isFuture() ? $baseTime : $baseTime->addDay(),
            'weekly' => $baseTime->copy()->next($this->day_of_week),
            'monthly' => $this->getNextMonthlyRun($baseTime),
            default => null,
        };
    }

    private function getNextMonthlyRun(Carbon $baseTime): Carbon
    {
        $next = $baseTime->copy()->day($this->day_of_month);

        if ($next->isPast()) {
            $next->addMonth();
        }

        return $next;
    }

    public function getFrequencyDescriptionAttribute(): string
    {
        $time = $this->time ?? '00:00';
        $dayOfWeek = Carbon::now()->dayOfWeek($this->day_of_week);
        $dayName = $dayOfWeek instanceof Carbon ? $dayOfWeek->format('l') : 'Monday';

        return match ($this->frequency) {
            'daily' => "Daily at {$time}",
            'weekly' => "Weekly on {$dayName} at {$time}",
            'monthly' => "Monthly on day {$this->day_of_month} at {$time}",
            default => 'Unknown',
        };
    }
}
