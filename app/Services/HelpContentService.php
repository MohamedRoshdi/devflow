<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HelpContent;
use App\Models\HelpInteraction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HelpContentService
{
    public function getByKey(string $key): ?HelpContent
    {
        return Cache::remember(
            "help_content_{$key}",
            now()->addDay(),
            fn() => HelpContent::active()
                ->where('key', $key)
                ->first()
        );
    }

    public function getByCategory(string $category): Collection
    {
        return Cache::remember(
            "help_content_category_{$category}",
            now()->addHour(),
            fn() => HelpContent::active()
                ->byCategory($category)
                ->orderBy('title')
                ->get()
        );
    }

    public function search(string $query): Collection
    {
        return HelpContent::active()
            ->search($query)
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get();
    }

    public function recordView(string $key, ?int $userId = null): void
    {
        $helpContent = $this->getByKey($key);

        if (!$helpContent) {
            return;
        }

        $helpContent->incrementViewCount();

        HelpInteraction::create([
            'user_id' => $userId,
            'help_content_id' => $helpContent->id,
            'interaction_type' => 'view',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function recordHelpful(string $key, ?int $userId = null): void
    {
        $helpContent = $this->getByKey($key);

        if (!$helpContent) {
            return;
        }

        $helpContent->markHelpful();

        HelpInteraction::create([
            'user_id' => $userId,
            'help_content_id' => $helpContent->id,
            'interaction_type' => 'helpful',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function recordNotHelpful(string $key, ?int $userId = null): void
    {
        $helpContent = $this->getByKey($key);

        if (!$helpContent) {
            return;
        }

        $helpContent->markNotHelpful();

        HelpInteraction::create([
            'user_id' => $userId,
            'help_content_id' => $helpContent->id,
            'interaction_type' => 'not_helpful',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getPopularHelp(int $limit = 10): Collection
    {
        return Cache::remember(
            "popular_help_{$limit}",
            now()->addHour(),
            fn() => HelpContent::active()
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get()
        );
    }

    public function getMostHelpful(int $limit = 10): Collection
    {
        return Cache::remember(
            "most_helpful_help_{$limit}",
            now()->addHour(),
            fn() => HelpContent::active()
                ->where('helpful_count', '>', 0)
                ->orderByRaw('(helpful_count / (helpful_count + not_helpful_count + 1)) DESC')
                ->limit($limit)
                ->get()
        );
    }

    public function getRelatedHelp(string $key, int $limit = 5): Collection
    {
        $helpContent = $this->getByKey($key);

        if (!$helpContent) {
            return collect();
        }

        return $helpContent->relatedContents()
            ->with('relatedHelpContent')
            ->orderBy('relevance_score', 'desc')
            ->limit($limit)
            ->get()
            ->pluck('relatedHelpContent');
    }

    public function clearCache(): void
    {
        Cache::flush();
    }
}
