<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class HelpContent extends Model
{
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

    public function translations(): HasMany
    {
        return $this->hasMany(HelpContentTranslation::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(HelpInteraction::class);
    }

    public function relatedContents(): HasMany
    {
        return $this->hasMany(HelpContentRelated::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('brief', 'like', "%{$search}%")
              ->orWhere('key', 'like', "%{$search}%");
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
