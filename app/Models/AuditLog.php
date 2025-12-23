<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null; // Audit logs don't need updated_at

    /** @var array<int, string> */
    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get human-readable action name
     */
    public function getActionNameAttribute(): string
    {
        return str_replace('.', ' ', $this->action);
    }

    /**
     * Get action category (deployment, server, project, etc.)
     */
    public function getActionCategoryAttribute(): string
    {
        return explode('.', $this->action)[0] ?? 'unknown';
    }

    /**
     * Get action type (created, updated, deleted, etc.)
     */
    public function getActionTypeAttribute(): string
    {
        $parts = explode('.', $this->action);

        return $parts[1] ?? 'unknown';
    }

    /**
     * Get the model short name
     */
    public function getModelNameAttribute(): string
    {
        return class_basename($this->auditable_type);
    }

    /**
     * Get changes summary
     *
     * @return array<string, array<string, mixed>>
     */
    public function getChangesSummaryAttribute(): array
    {
        /** @var array<string, mixed>|null $oldValues */
        $oldValues = $this->old_values;
        /** @var array<string, mixed>|null $newValues */
        $newValues = $this->new_values;

        if (! is_array($oldValues) || ! is_array($newValues) || empty($oldValues) || empty($newValues)) {
            return [];
        }

        $changes = [];
        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;

            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
