<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check Sanctum token abilities.
 *
 * This middleware enforces ability-based authorization for Sanctum tokens,
 * returning 403 Forbidden when a token lacks the required abilities rather
 * than relying on policy checks which may return 404.
 */
class CheckSanctumAbility
{
    /**
     * Ability mapping for HTTP methods.
     *
     * @var array<string, string>
     */
    protected array $methodAbilityMap = [
        'GET' => 'read',
        'HEAD' => 'read',
        'POST' => 'create',
        'PUT' => 'update',
        'PATCH' => 'update',
        'DELETE' => 'delete',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $resource  The resource name (e.g., 'projects', 'servers')
     * @param  string|null  $ability  Specific ability to check (overrides method-based detection)
     */
    public function handle(Request $request, Closure $next, ?string $resource = null, ?string $ability = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Get the current access token
        $token = $user->currentAccessToken();

        // If no token, continue (non-token-based auth)
        if ($token === null) {
            return $next($request);
        }

        // If not a Sanctum PersonalAccessToken (e.g., TransientToken), continue
        // @phpstan-ignore instanceof.alwaysTrue (TransientToken is returned when using actingAs)
        if (! ($token instanceof PersonalAccessToken)) {
            return $next($request);
        }

        // Determine the required ability
        $requiredAbility = $this->determineRequiredAbility($request, $resource, $ability);

        if (! $requiredAbility) {
            return $next($request);
        }

        // Check if token has the required ability or wildcard
        if (! $this->tokenHasAbility($token, $requiredAbility)) {
            return response()->json([
                'message' => 'This action is forbidden. Your token does not have the required permission.',
                'error' => 'insufficient_token_abilities',
                'required_ability' => $requiredAbility,
            ], 403);
        }

        return $next($request);
    }

    /**
     * Determine the required ability based on request context.
     */
    protected function determineRequiredAbility(Request $request, ?string $resource, ?string $ability): ?string
    {
        // If specific ability provided, use it
        if ($ability) {
            return $resource ? "{$resource}:{$ability}" : $ability;
        }

        // If no resource specified, cannot determine ability
        if (! $resource) {
            return null;
        }

        // Map HTTP method to ability
        $method = $request->method();
        $action = $this->methodAbilityMap[$method] ?? 'read';

        return "{$resource}:{$action}";
    }

    /**
     * Check if token has the required ability.
     */
    protected function tokenHasAbility(PersonalAccessToken $token, string $requiredAbility): bool
    {
        $abilities = $token->abilities ?? [];

        // Wildcard grants all abilities
        if (in_array('*', $abilities, true)) {
            return true;
        }

        // Check for exact match
        if (in_array($requiredAbility, $abilities, true)) {
            return true;
        }

        // Check for resource wildcard (e.g., 'projects:*' for 'projects:delete')
        $parts = explode(':', $requiredAbility);
        if (count($parts) === 2) {
            $resourceWildcard = $parts[0] . ':*';
            if (in_array($resourceWildcard, $abilities, true)) {
                return true;
            }
        }

        return false;
    }
}
