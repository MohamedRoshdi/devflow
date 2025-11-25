<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider',
        'webhook_url',
        'webhook_secret',
        'enabled',
        'events',
        'metadata',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'events' => 'array',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'webhook_secret',
    ];

    public function setWebhookSecretAttribute($value)
    {
        $this->attributes['webhook_secret'] = $value ? encrypt($value) : null;
    }

    public function getWebhookSecretAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function shouldNotifyForEvent(string $event): bool
    {
        return $this->enabled && in_array($event, $this->events ?? []);
    }
}