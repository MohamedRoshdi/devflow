<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

/**
 * @use HasFactory<\Database\Factories\FailedJobFactory>
 */
class FailedJob extends Model
{
    /** @use HasFactory<\Database\Factories\FailedJobFactory> */
    use HasFactory;

    protected $table = 'failed_jobs';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'connection',
        'queue',
        'payload',
        'exception',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'failed_at' => 'datetime',
        ];
    }

    /**
     * Get the decoded payload
     */
    public function getDecodedPayloadAttribute(): ?array
    {
        try {
            return json_decode($this->payload, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the job class name from the payload
     */
    public function getJobClassAttribute(): ?string
    {
        $payload = $this->decoded_payload;

        if (! $payload) {
            return null;
        }

        $command = unserialize($payload['data']['command'] ?? '');

        if (is_object($command)) {
            return get_class($command);
        }

        return $payload['displayName'] ?? 'Unknown Job';
    }

    /**
     * Get the first line of the exception
     */
    public function getShortExceptionAttribute(): string
    {
        $lines = explode("\n", $this->exception);

        return $lines[0] ?? 'No error message';
    }

    /**
     * Retry this failed job
     */
    public function retry(): bool
    {
        try {
            Artisan::call('queue:retry', ['id' => [$this->id]]);
            $this->delete();

            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to retry job {$this->id}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Delete this failed job
     */
    public function forget(): bool
    {
        try {
            Artisan::call('queue:forget', ['id' => $this->id]);

            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to forget job {$this->id}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Retry all failed jobs
     */
    public static function retryAll(): bool
    {
        try {
            Artisan::call('queue:retry', ['id' => ['all']]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to retry all jobs: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Clear all failed jobs
     */
    public static function forgetAll(): bool
    {
        try {
            Artisan::call('queue:flush');

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to flush failed jobs: '.$e->getMessage());

            return false;
        }
    }
}
