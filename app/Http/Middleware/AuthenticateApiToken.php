<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return response()->json([
                'message' => 'Unauthenticated. Please provide a valid Bearer token.',
                'error' => 'missing_token',
            ], 401);
        }

        $apiToken = ApiToken::where('token', hash('sha256', $token))
            ->active()
            ->first();

        if (! $apiToken) {
            return response()->json([
                'message' => 'Invalid or expired token.',
                'error' => 'invalid_token',
            ], 401);
        }

        if ($apiToken->hasExpired()) {
            return response()->json([
                'message' => 'Token has expired.',
                'error' => 'expired_token',
            ], 401);
        }

        // Check if the token has the required ability
        if ($ability && ! $apiToken->can($ability)) {
            return response()->json([
                'message' => 'This token does not have the required permission.',
                'error' => 'insufficient_permissions',
                'required_ability' => $ability,
            ], 403);
        }

        // Set the authenticated user
        $user = $apiToken->user;
        if ($user === null) {
            return response()->json([
                'message' => 'Token user not found.',
                'error' => 'user_not_found',
            ], 401);
        }

        auth()->setUser($user);

        // Store the API token in the request for later use
        $request->attributes->set('api_token', $apiToken);

        // Update last used timestamp (async to avoid slowing down requests)
        dispatch(function () use ($apiToken) {
            $apiToken->updateLastUsedAt();
        })->afterResponse();

        return $next($request);
    }

    /**
     * Extract the token from the request.
     */
    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }
}
