<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Deployment;
use App\Models\HelpContent;
use App\Models\KubernetesCluster;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Centralized cache invalidation service.
 *
 * Provides model-aware cache clearing with consistent key patterns.
 * Called automatically by model observers for event-based invalidation.
 */
class CacheInvalidationService
{
    /**
     * Cache key prefixes for different models
     *
     * @var array<class-string, array<string>>
     */
    private const MODEL_CACHE_KEYS = [
        Project::class => [
            'projects_dropdown_list_user_%d',
            'user_project_ids_%d',
            'k8s_deployable_projects',
        ],
        Server::class => [
            'server_tags_list',
        ],
        Deployment::class => [
            'deployment_stats_user_%d',
        ],
        HelpContent::class => [
            'help_content_stats',
            'help_content_categories',
        ],
        KubernetesCluster::class => [
            'k8s_clusters_count',
        ],
    ];

    /**
     * Invalidate caches for a model
     */
    public function invalidateForModel(Model $model): void
    {
        $modelClass = $model::class;
        $keys = self::MODEL_CACHE_KEYS[$modelClass] ?? [];

        foreach ($keys as $keyPattern) {
            $this->invalidateKey($keyPattern, $model);
        }

        Log::debug("CacheInvalidation: Cleared caches for {$modelClass}", [
            'model_id' => $model->getKey(),
            'keys' => $keys,
        ]);
    }

    /**
     * Invalidate a specific cache key pattern
     */
    private function invalidateKey(string $keyPattern, Model $model): void
    {
        // Handle patterns with user_id placeholder
        if (str_contains($keyPattern, '%d')) {
            // For Project model, get user_id
            if ($model instanceof Project && $model->user_id) {
                $key = sprintf($keyPattern, $model->user_id);
                Cache::forget($key);

                return;
            }

            // For Deployment model, get user_id via project
            if ($model instanceof Deployment && $model->project && $model->project->user_id) {
                $key = sprintf($keyPattern, $model->project->user_id);
                Cache::forget($key);

                return;
            }

            // For Server model, get user_id
            if ($model instanceof Server && $model->user_id) {
                $key = sprintf($keyPattern, $model->user_id);
                Cache::forget($key);

                return;
            }

            // If we can't determine user_id, skip user-specific caches
            return;
        }

        // Static keys without placeholders
        Cache::forget($keyPattern);
    }

    /**
     * Invalidate all project-related caches for a user
     */
    public function invalidateProjectCaches(int $userId): void
    {
        Cache::forget("projects_dropdown_list_user_{$userId}");
        Cache::forget("user_project_ids_{$userId}");
        Cache::forget('k8s_deployable_projects');
    }

    /**
     * Invalidate all deployment-related caches for a user
     */
    public function invalidateDeploymentCaches(int $userId): void
    {
        Cache::forget("deployment_stats_user_{$userId}");
    }

    /**
     * Invalidate all server-related caches
     */
    public function invalidateServerCaches(): void
    {
        Cache::forget('server_tags_list');
    }

    /**
     * Invalidate all help content caches
     */
    public function invalidateHelpContentCaches(): void
    {
        Cache::forget('help_content_stats');
        Cache::forget('help_content_categories');
    }

    /**
     * Invalidate all Kubernetes-related caches
     */
    public function invalidateKubernetesCaches(): void
    {
        Cache::forget('k8s_clusters_count');
        Cache::forget('k8s_deployable_projects');
    }

    /**
     * Flush all application caches (use with caution)
     */
    public function flushAll(): void
    {
        Cache::flush();
        Log::info('CacheInvalidation: All caches flushed');
    }
}
