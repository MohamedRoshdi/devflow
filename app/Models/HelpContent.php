<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class HelpContent extends Model
{
    /** @use HasFactory<\Database\Factories\HelpContentFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'category',
        'ui_element_type',
        'icon',
        'title',
        'brief',
        'details',
        'docs_url',
        'video_url',
        'is_active',
    ];

    protected $casts = [
        'details' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships

    /**
     * @return HasMany<HelpContentTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(HelpContentTranslation::class);
    }

    /**
     * @return HasMany<HelpInteraction, $this>
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(HelpInteraction::class);
    }

    /**
     * @return HasMany<HelpContentRelated, $this>
     */
    public function relatedContents(): HasMany
    {
        return $this->hasMany(HelpContentRelated::class);
    }

    // Scopes

    /**
     * @param \Illuminate\Database\Eloquent\Builder<HelpContent> $query
     * @return \Illuminate\Database\Eloquent\Builder<HelpContent>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<HelpContent> $query
     * @return \Illuminate\Database\Eloquent\Builder<HelpContent>
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<HelpContent> $query
     * @return \Illuminate\Database\Eloquent\Builder<HelpContent>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        $searchLower = strtolower($search);

        return $query->where(function ($q) use ($searchLower) {
            $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
              ->orWhereRaw('LOWER(brief) LIKE ?', ["%{$searchLower}%"])
              ->orWhereRaw('LOWER(key) LIKE ?', ["%{$searchLower}%"]);
        });
    }

    // Accessors & Methods

    public function getLocalizedBrief(): string
    {
        $locale = App::getLocale();

        if ($locale === 'en') {
            return $this->brief;
        }

        $translation = $this->translations()
            ->where('locale', $locale)
            ->first();

        return $translation?->brief ?? $this->brief;
    }

    public function getLocalizedDetails(): array
    {
        $locale = App::getLocale();

        if ($locale === 'en') {
            return $this->details;
        }

        $translation = $this->translations()
            ->where('locale', $locale)
            ->first();

        return $translation?->details ?? $this->details;
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function markHelpful(): void
    {
        $this->increment('helpful_count');
    }

    public function markNotHelpful(): void
    {
        $this->increment('not_helpful_count');
    }

    public function getHelpfulnessPercentage(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;

        if ($total === 0) {
            return 0;
        }

        return round(($this->helpful_count / $total) * 100, 2);
    }
}
