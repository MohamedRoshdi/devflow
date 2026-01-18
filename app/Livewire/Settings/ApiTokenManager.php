<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\ApiToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class ApiTokenManager extends Component
{
    /**
     * Collection of API tokens
     *
     * @var Collection<int, ApiToken>
     */
    public Collection $tokens;

    public bool $showCreateModal = false;

    public bool $showTokenModal = false;

    public string $newTokenName = '';

    /**
     * @var array<int, string>
     */
    public array $newTokenAbilities = [];

    public string $newTokenExpiration = 'never';

    public ?string $createdToken = null;

    public ?string $createdTokenPlain = null;

    /**
     * @var array<string, string>
     */
    public array $availableAbilities = [
        'projects:read' => 'View projects',
        'projects:write' => 'Create and update projects',
        'projects:delete' => 'Delete projects',
        'projects:deploy' => 'Deploy projects',
        'servers:read' => 'View servers',
        'servers:write' => 'Create and update servers',
        'servers:delete' => 'Delete servers',
        'deployments:read' => 'View deployments',
        'deployments:write' => 'Create deployments',
        'deployments:rollback' => 'Rollback deployments',
    ];

    public function mount(): void
    {
        $this->loadTokens();
    }

    public function loadTokens(): void
    {
        $user = auth()->user();
        if ($user === null) {
            $this->tokens = collect();

            return;
        }

        $this->tokens = $user
            ->apiTokens()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
        $this->newTokenName = '';
        $this->newTokenAbilities = [];
        $this->newTokenExpiration = 'never';
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->reset(['newTokenName', 'newTokenAbilities', 'newTokenExpiration']);
    }

    public function createToken(): void
    {
        $this->validate([
            'newTokenName' => 'required|string|max:255',
            'newTokenAbilities' => 'required|array|min:1',
            'newTokenExpiration' => 'required|string|in:never,30,90,365',
        ], [
            'newTokenName.required' => 'Please provide a token name',
            'newTokenAbilities.required' => 'Please select at least one ability',
            'newTokenAbilities.min' => 'Please select at least one ability',
        ]);

        // Generate a plain token
        $plainToken = Str::random(64);

        // Calculate expiration
        $expiresAt = match ($this->newTokenExpiration) {
            '30' => now()->addDays(30),
            '90' => now()->addDays(90),
            '365' => now()->addYear(),
            default => null,
        };

        // Create the token
        ApiToken::create([
            'user_id' => auth()->id(),
            'name' => $this->newTokenName,
            'token' => hash('sha256', $plainToken),
            'abilities' => $this->newTokenAbilities,
            'expires_at' => $expiresAt,
        ]);

        $this->createdTokenPlain = $plainToken;
        $this->showCreateModal = false;
        $this->showTokenModal = true;
        $this->loadTokens();

        $this->dispatch('notification', type: 'success', message: 'API token created successfully');
    }

    public function closeTokenModal(): void
    {
        $this->showTokenModal = false;
        $this->createdTokenPlain = null;
    }

    public function revokeToken(int $tokenId): void
    {
        $token = ApiToken::where('id', $tokenId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $token->delete();
        $this->loadTokens();

        $this->dispatch('notification', type: 'success', message: 'API token revoked successfully');
    }

    public function regenerateToken(int $tokenId): void
    {
        $token = ApiToken::where('id', $tokenId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Generate new plain token
        $plainToken = Str::random(64);

        // Update token
        $token->update([
            'token' => hash('sha256', $plainToken),
            'last_used_at' => null,
        ]);

        $this->createdTokenPlain = $plainToken;
        $this->showTokenModal = true;
        $this->loadTokens();

        $this->dispatch('notification', type: 'success', message: 'API token regenerated successfully');
    }

    public function render(): View
    {
        return view('livewire.settings.api-token-manager');
    }
}
