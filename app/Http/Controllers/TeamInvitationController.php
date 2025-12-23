<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamInvitationController extends Controller
{
    public function __construct(
        private readonly TeamService $teamService
    ) {}

    /**
     * Show the invitation page
     */
    public function show(string $token)
    {
        $invitation = TeamInvitation::where('token', $token)
            ->with(['team', 'inviter'])
            ->firstOrFail();

        if ($invitation->isAccepted()) {
            return redirect()->route('teams.index')
                ->with('error', 'This invitation has already been accepted.');
        }

        if ($invitation->isExpired()) {
            return view('teams.invitation', [
                'invitation' => $invitation,
                'expired' => true,
            ]);
        }

        return view('teams.invitation', [
            'invitation' => $invitation,
            'expired' => false,
        ]);
    }

    /**
     * Accept the invitation
     */
    public function accept(Request $request, string $token)
    {
        if (! Auth::check()) {
            return redirect()->route('login')
                ->with('message', 'Please log in to accept the invitation.');
        }

        try {
            $team = $this->teamService->acceptInvitation($token);

            return redirect()->route('teams.index')
                ->with('success', "Welcome to {$team->name}!");
        } catch (\Exception $e) {
            return redirect()->route('teams.index')
                ->with('error', $e->getMessage());
        }
    }
}
