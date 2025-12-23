<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\CacheInvalidationService;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic cache invalidation observer.
 *
 * Automatically clears related caches when models are created, updated, or deleted.
 * Provides event-based cache invalidation for better consistency.
 */
class CacheInvalidationObserver
{
    public function __construct(
        private readonly CacheInvalidationService $cacheService
    ) {}

    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->cacheService->invalidateForModel($model);
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->cacheService->invalidateForModel($model);
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->cacheService->invalidateForModel($model);
    }

    /**
     * Handle the Model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->cacheService->invalidateForModel($model);
    }

    /**
     * Handle the Model "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        $this->cacheService->invalidateForModel($model);
    }
}
