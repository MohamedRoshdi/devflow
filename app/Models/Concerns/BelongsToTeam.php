<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Trait for models that belong to a team.
 *
 * This trait adds:
 * - Team relationship
 * - Automatic team_id assignment on creation
 * - Global scope to filter by current user's team
 *
 * @property int|null $team_id
 * @property-read Team|null $team
 */
trait BelongsToTeam
{
    /**
     * Boot the trait.
     */
    public static function bootBelongsToTeam(): void
    {
        // Auto-assign team_id when creating new records
        static::creating(function ($model) {
            if (empty($model->team_id) && Auth::check()) {
                $model->team_id = Auth::user()->current_team_id;
            }
        });

        // Add global scope to filter by current team
        static::addGlobalScope('team', function (Builder $query) {
            if (Auth::check() && Auth::user()->current_team_id) {
                // Check if the model's table has a team_id column
                $table = $query->getModel()->getTable();

                // Use whereHas for models with indirect team relationship
                // or direct where for models with team_id column
                if (in_array('team_id', $query->getModel()->getFillable())) {
                    $query->where(function ($q) use ($table) {
                        $q->where("{$table}.team_id", Auth::user()->current_team_id)
                          ->orWhereNull("{$table}.team_id");
                    });
                }
            }
        });
    }

    /**
     * Get the team that owns this model.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope to filter by a specific team.
     */
    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to include records without team (shared resources).
     */
    public function scopeWithShared(Builder $query): Builder
    {
        return $query->withoutGlobalScope('team')
            ->where(function ($q) {
                if (Auth::check() && Auth::user()->current_team_id) {
                    $q->where('team_id', Auth::user()->current_team_id)
                      ->orWhereNull('team_id');
                }
            });
    }

    /**
     * Scope to bypass team filtering (for admin operations).
     */
    public function scopeAllTeams(Builder $query): Builder
    {
        return $query->withoutGlobalScope('team');
    }

    /**
     * Check if this model belongs to the given team.
     */
    public function belongsToTeam(Team $team): bool
    {
        return $this->team_id === $team->id;
    }

    /**
     * Check if this model belongs to the current user's team.
     */
    public function belongsToCurrentTeam(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return $this->team_id === Auth::user()->current_team_id;
    }
}
