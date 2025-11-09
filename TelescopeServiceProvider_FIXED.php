<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TelescopeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            // Only register Telescope in local environment
            if (class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
                $this->app->register(\Laravel\Telescope\TelescopeApplicationServiceProvider::class);
                $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only configure Telescope if it's available
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::filter(function ($entry) {
                if ($this->app->environment('local')) {
                    return true;
                }

                return $entry->isReportableException() ||
                       $entry->isFailedRequest() ||
                       $entry->isFailedJob() ||
                       $entry->isScheduledTask() ||
                       $entry->hasMonitoredTag();
            });
        }
    }
}

