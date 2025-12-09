<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // API Routes - 60 requests per minute per user or IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many API requests. Please try again later.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429);
                });
        });

        // Webhook Routes - 30 requests per minute per IP (stricter to prevent abuse)
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many webhook requests. Please try again later.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429);
                });
        });

        // Login Attempts - 5 attempts per minute per IP (prevent brute force)
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = strtolower($request->input('email', '')).'|'.$request->ip();

            return Limit::perMinute(5)->by($throttleKey)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again in '.$headers['Retry-After'].' seconds.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429);
                });
        });

        // Password Reset Requests - 3 requests per minute per email
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by(strtolower($request->input('email', '')))
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many password reset requests. Please try again later.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429);
                });
        });

        // Deployment Actions - 10 deployments per minute per project (prevent deployment spam)
        RateLimiter::for('deployments', function (Request $request) {
            $projectId = $request->route('project')?->id ?? $request->route('project');

            return Limit::perMinute(10)->by($projectId ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many deployment requests. Please wait before triggering another deployment.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429);
                });
        });

        // Server Operations - 20 requests per minute (prevent server command spam)
        RateLimiter::for('server-operations', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many server operations. Please slow down.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429);
                });
        });

        // Public Pages - 100 requests per minute (generous but prevents abuse)
        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        // Authenticated Web Routes - 200 requests per minute
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(200)->by($request->user()?->id ?: $request->ip());
        });
    }
}
