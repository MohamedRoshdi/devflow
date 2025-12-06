<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeamAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Get team from route or user's current team
        $team = $request->route('team');

        if (! $team) {
            $team = $user->currentTeam;
        }

        if (! $team instanceof Team) {
            abort(404, 'Team not found');
        }

        // Check if user is a member
        if (! $team->hasMember($user)) {
            abort(403, 'You do not have access to this team.');
        }

        // Check specific permission if required
        if ($permission) {
            $member = $team->members()->where('user_id', $user->id)->first();

            if (! $member || ! $member->pivot->hasPermission($permission)) {
                abort(403, 'You do not have permission to perform this action.');
            }
        }

        return $next($request);
    }
}
