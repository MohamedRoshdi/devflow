<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\DeploymentCompleted;
use App\Events\DeploymentFailed;
use App\Events\DeploymentStarted;
use App\Events\DeploymentStatusUpdated;
use App\Events\ServerMetricsUpdated;
use App\Listeners\DeploymentCompletedListener;
use App\Listeners\DeploymentFailedListener;
use App\Listeners\DeploymentStartedListener;
use App\Listeners\DeploymentStatusUpdatedListener;
use App\Listeners\ServerMetricsUpdatedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * Events with listeners perform server-side actions (audit logging, notifications, etc.).
     * Broadcast-only events (DashboardUpdated, DeploymentLogUpdated, PipelineStageUpdated,
     * ProjectSetupUpdated) only push real-time updates to frontend clients.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        DeploymentStarted::class => [
            DeploymentStartedListener::class,
        ],
        DeploymentCompleted::class => [
            DeploymentCompletedListener::class,
        ],
        DeploymentFailed::class => [
            DeploymentFailedListener::class,
        ],
        DeploymentStatusUpdated::class => [
            DeploymentStatusUpdatedListener::class,
        ],
        ServerMetricsUpdated::class => [
            ServerMetricsUpdatedListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
