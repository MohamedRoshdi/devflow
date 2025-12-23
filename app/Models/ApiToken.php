<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    /** @use HasFactory<\Database\Factories\ApiTokenFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'api_tokens';

    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'token',
    ];

    // Relationships
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Team, $this>
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    // Methods
    public function can(string $ability): bool
    {
        // If abilities is null or empty, grant all permissions
        if (empty($this->abilities)) {
            return true;
        }

        // Check if the specific ability exists
        if (in_array($ability, $this->abilities)) {
            return true;
        }

        // Check for wildcard permissions (e.g., 'projects:*' grants all project abilities)
        $abilityParts = explode(':', $ability);
        if (count($abilityParts) === 2) {
            $wildcardAbility = $abilityParts[0].':*';
            if (in_array($wildcardAbility, $this->abilities)) {
                return true;
            }
        }

        // Check for super admin wildcard
        if (in_array('*', $this->abilities)) {
            return true;
        }

        return false;
    }

    public function hasExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    public function updateLastUsedAt(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    // Scopes
    /**
     * @param  Builder<ApiToken>  $query
     * @return Builder<ApiToken>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }
}
