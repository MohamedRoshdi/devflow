<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Deployment;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Observers\DeploymentObserver;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register deployment observer for automatic job dispatching
        Deployment::observe(DeploymentObserver::class);

        // Register audit observers for key models
        Deployment::observe(AuditObserver::class);
        Project::observe(AuditObserver::class);
        Server::observe(AuditObserver::class);
        Domain::observe(AuditObserver::class);

        // Listen for authentication events
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function ($event) {
                if ($event->user) {
                    app(\App\Services\AuditService::class)->log(
                        'user.login',
                        $event->user,
                        null,
                        ['login_at' => now()->toIso8601String()]
                    );
                }
            }
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            function ($event) {
                if ($event->user) {
                    app(\App\Services\AuditService::class)->log(
                        'user.logout',
                        $event->user,
                        null,
                        ['logout_at' => now()->toIso8601String()]
                    );
                }
            }
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Failed::class,
            function ($event) {
                // Log failed login attempts (without user model since login failed)
                \App\Models\AuditLog::create([
                    'user_id' => null,
                    'action' => 'user.login_failed',
                    'auditable_type' => User::class,
                    'auditable_id' => 0,
                    'old_values' => null,
                    'new_values' => [
                        'email' => $event->credentials['email'] ?? 'unknown',
                        'attempted_at' => now()->toIso8601String(),
                    ],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            }
        );
    }
}
