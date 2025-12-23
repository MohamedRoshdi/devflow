<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Livewire\Servers\SSHTerminal;
use App\Livewire\Servers\WebTerminal;
use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Tests for Livewire component authorization
 *
 * Verifies that terminal components properly authorize access
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $otherUser;
    private Team $team;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary permissions
        Permission::firstOrCreate(['name' => 'view-servers', 'guard_name' => 'web']);

        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Give owner the view-servers permission
        $this->owner->givePermissionTo('view-servers');

        $this->team = Team::factory()->create(['owner_id' => $this->owner->id]);
        $this->owner->teams()->attach($this->team->id, ['role' => 'owner']);
        $this->owner->update(['current_team_id' => $this->team->id]);

        $this->server = Server::factory()->create([
            'team_id' => $this->team->id,
            'status' => 'online',
        ]);
    }

    public function test_ssh_terminal_requires_authentication(): void
    {
        // Unauthenticated users should get 403 Forbidden
        Livewire::test(SSHTerminal::class, ['server' => $this->server])
            ->assertForbidden();
    }

    public function test_ssh_terminal_authorizes_team_owner(): void
    {
        Livewire::actingAs($this->owner)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->assertSuccessful();
    }

    public function test_ssh_terminal_denies_non_team_member(): void
    {
        // User without permission or team membership should get 403
        Livewire::actingAs($this->otherUser)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->assertForbidden();
    }

    public function test_web_terminal_requires_authentication(): void
    {
        // Unauthenticated users should get 403 Forbidden
        Livewire::test(WebTerminal::class, ['server' => $this->server])
            ->assertForbidden();
    }

    public function test_web_terminal_authorizes_team_owner(): void
    {
        Livewire::actingAs($this->owner)
            ->test(WebTerminal::class, ['server' => $this->server])
            ->assertSuccessful();
    }

    public function test_web_terminal_denies_non_team_member(): void
    {
        // User without permission or team membership should get 403
        Livewire::actingAs($this->otherUser)
            ->test(WebTerminal::class, ['server' => $this->server])
            ->assertForbidden();
    }

    public function test_team_member_can_access_terminal(): void
    {
        // Add other user to the team and give permission
        $this->otherUser->givePermissionTo('view-servers');
        $this->otherUser->teams()->attach($this->team->id, ['role' => 'member']);
        $this->otherUser->update(['current_team_id' => $this->team->id]);

        Livewire::actingAs($this->otherUser)
            ->test(SSHTerminal::class, ['server' => $this->server])
            ->assertSuccessful();
    }

    public function test_accessing_other_teams_server_is_denied(): void
    {
        $otherTeam = Team::factory()->create(['owner_id' => $this->otherUser->id]);
        $this->otherUser->teams()->attach($otherTeam->id, ['role' => 'owner']);
        $this->otherUser->update(['current_team_id' => $otherTeam->id]);
        $this->otherUser->givePermissionTo('view-servers');

        $otherServer = Server::factory()->create([
            'team_id' => $otherTeam->id,
            'status' => 'online',
        ]);

        // Owner of team1 trying to access team2's server should be denied
        Livewire::actingAs($this->owner)
            ->test(SSHTerminal::class, ['server' => $otherServer])
            ->assertForbidden();
    }
}
