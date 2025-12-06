<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GitHubConnection;
use App\Services\GitHubService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GitHubAuthController extends Controller
{
    public function __construct(
        private readonly GitHubService $gitHubService
    ) {}

    /**
     * Redirect to GitHub OAuth.
     */
    public function redirect(): RedirectResponse
    {
        $state = Str::random(40);
        session(['github_oauth_state' => $state]);

        $authUrl = $this->gitHubService->getAuthUrl($state);

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback from GitHub.
     */
    public function callback(Request $request): RedirectResponse
    {
        // Verify state to prevent CSRF
        $state = $request->get('state');
        $sessionState = session('github_oauth_state');

        if ($state !== $sessionState) {
            return redirect()->route('settings.github')
                ->with('error', 'Invalid state parameter. Please try again.');
        }

        session()->forget('github_oauth_state');

        // Check for error from GitHub
        if ($request->has('error')) {
            return redirect()->route('settings.github')
                ->with('error', 'GitHub authorization was denied.');
        }

        // Exchange code for token
        $code = $request->get('code');
        if (! $code) {
            return redirect()->route('settings.github')
                ->with('error', 'No authorization code received from GitHub.');
        }

        try {
            $tokenData = $this->gitHubService->handleCallback($code);

            // Get user information from GitHub
            $tempConnection = new GitHubConnection([
                'access_token' => $tokenData['access_token'],
            ]);

            $githubUser = $this->gitHubService->getUser($tempConnection);

            // Deactivate any existing connections for this user
            GitHubConnection::where('user_id', Auth::id())
                ->update(['is_active' => false]);

            // Create or update GitHub connection
            $connection = GitHubConnection::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'github_user_id' => (string) $githubUser['id'],
                ],
                [
                    'access_token' => $tokenData['access_token'],
                    'github_username' => $githubUser['login'],
                    'github_avatar' => $githubUser['avatar_url'] ?? null,
                    'scopes' => explode(',', $tokenData['scope']),
                    'is_active' => true,
                ]
            );

            // Sync repositories in the background (optional)
            // You can dispatch a job here for better performance
            try {
                $this->gitHubService->syncRepositories($connection);
            } catch (\Exception $e) {
                // Log but don't fail the connection
                \Log::warning('Failed to sync repositories during OAuth callback', [
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()->route('settings.github')
                ->with('success', 'Successfully connected to GitHub!');

        } catch (\Exception $e) {
            \Log::error('GitHub OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('settings.github')
                ->with('error', 'Failed to connect to GitHub: '.$e->getMessage());
        }
    }

    /**
     * Disconnect GitHub account.
     */
    public function disconnect(): RedirectResponse
    {
        $userId = Auth::id();
        if (! is_int($userId)) {
            return redirect()->route('settings.github')->with('error', 'User not authenticated');
        }

        $connection = GitHubConnection::activeForUser($userId);

        if ($connection) {
            // Delete associated repositories
            $connection->repositories()->delete();

            // Delete the connection
            $connection->delete();

            return redirect()->route('settings.github')
                ->with('success', 'GitHub account disconnected successfully.');
        }

        return redirect()->route('settings.github')
            ->with('error', 'No active GitHub connection found.');
    }
}
