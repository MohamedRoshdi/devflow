<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSetupTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'task_type',
        'status',
        'message',
        'result_data',
        'progress',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'result_data' => 'array',
            'progress' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Task types
    public const TYPE_SSL = 'ssl';
    public const TYPE_WEBHOOK = 'webhook';
    public const TYPE_HEALTH_CHECK = 'health_check';
    public const TYPE_BACKUP = 'backup';
    public const TYPE_NOTIFICATIONS = 'notifications';
    public const TYPE_DEPLOYMENT = 'deployment';

    // Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public static function getAllTypes(): array
    {
        return [
            self::TYPE_SSL,
            self::TYPE_WEBHOOK,
            self::TYPE_HEALTH_CHECK,
            self::TYPE_BACKUP,
            self::TYPE_NOTIFICATIONS,
            self::TYPE_DEPLOYMENT,
        ];
    }

    public static function getTypeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_SSL => 'SSL Certificate',
            self::TYPE_WEBHOOK => 'Git Webhook',
            self::TYPE_HEALTH_CHECK => 'Health Checks',
            self::TYPE_BACKUP => 'Database Backup',
            self::TYPE_NOTIFICATIONS => 'Notifications',
            self::TYPE_DEPLOYMENT => 'Initial Deployment',
            default => ucfirst($type),
        };
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function markAsRunning(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(?string $message = null, ?array $resultData = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'message' => $message,
            'result_data' => $resultData,
            'progress' => 100,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $message): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'message' => $message,
            'completed_at' => now(),
        ]);
    }

    public function markAsSkipped(?string $message = null): void
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'message' => $message ?? 'Skipped by user',
            'completed_at' => now(),
        ]);
    }

    public function updateProgress(int $progress, ?string $message = null): void
    {
        $data = ['progress' => min(100, max(0, $progress))];
        if ($message) {
            $data['message'] = $message;
        }
        $this->update($data);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function isDone(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_SKIPPED]);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_RUNNING => 'blue',
            self::STATUS_SKIPPED => 'gray',
            default => 'yellow',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'check-circle',
            self::STATUS_FAILED => 'x-circle',
            self::STATUS_RUNNING => 'refresh',
            self::STATUS_SKIPPED => 'minus-circle',
            default => 'clock',
        };
    }
}
