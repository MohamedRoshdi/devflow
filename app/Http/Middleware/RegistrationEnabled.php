<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrationEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! SystemSetting::isRegistrationEnabled()) {
            return redirect()->route('login')
                ->with('status', 'Registration is currently closed. Please contact an administrator for access.');
        }

        return $next($request);
    }
}
