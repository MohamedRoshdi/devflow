<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\SSHTerminalSelector;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SSHTerminalSelectorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertStatus(200);
    }

    public function test_component_displays_available_servers(): void
    {
        $servers = Server::factory()->count(3)->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 3;
            });
    }

    public function test_component_excludes_deleted_servers(): void
    {
        Server::factory()->count(2)->create(['status' => 'active']);
        Server::factory()->create(['status' => 'deleted']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 2;
            });
    }

    public function test_component_orders_servers_by_name(): void
    {
        Server::factory()->create(['name' => 'Zebra Server', 'status' => 'active']);
        Server::factory()->create(['name' => 'Alpha Server', 'status' => 'active']);
        Server::factory()->create(['name' => 'Beta Server', 'status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                $names = $viewServers->pluck('name')->toArray();
                return $names[0] === 'Alpha Server' &&
                       $names[1] === 'Beta Server' &&
                       $names[2] === 'Zebra Server';
            });
    }

    public function test_component_renders_with_no_servers(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->isEmpty();
            });
    }

    // ==================== SERVER SELECTION TESTS ====================

    public function test_can_select_server(): void
    {
        $server = Server::factory()->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertSet('selectedServerId', null)
            ->call('selectServer', $server->id)
            ->assertSet('selectedServerId', $server->id);
    }

    public function test_selected_server_is_passed_to_view(): void
    {
        $server = Server::factory()->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->call('selectServer', $server->id)
            ->assertViewHas('selectedServer', function ($selectedServer) use ($server) {
                return $selectedServer !== null && $selectedServer->id === $server->id;
            });
    }

    public function test_can_change_selected_server(): void
    {
        $server1 = Server::factory()->create(['status' => 'active']);
        $server2 = Server::factory()->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->call('selectServer', $server1->id)
            ->assertSet('selectedServerId', $server1->id)
            ->call('selectServer', $server2->id)
            ->assertSet('selectedServerId', $server2->id);
    }

    public function test_selecting_different_server_updates_view(): void
    {
        $server1 = Server::factory()->create(['name' => 'Server One', 'status' => 'active']);
        $server2 = Server::factory()->create(['name' => 'Server Two', 'status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->call('selectServer', $server1->id)
            ->assertViewHas('selectedServer', function ($selectedServer) {
                return $selectedServer->name === 'Server One';
            })
            ->call('selectServer', $server2->id)
            ->assertViewHas('selectedServer', function ($selectedServer) {
                return $selectedServer->name === 'Server Two';
            });
    }

    public function test_no_server_selected_by_default(): void
    {
        Server::factory()->count(3)->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertSet('selectedServerId', null)
            ->assertViewHas('selectedServer', null);
    }

    // ==================== SERVER STATUS TESTS ====================

    public function test_includes_active_servers(): void
    {
        Server::factory()->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 1;
            });
    }

    public function test_includes_inactive_servers(): void
    {
        Server::factory()->create(['status' => 'inactive']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 1;
            });
    }

    public function test_includes_maintenance_servers(): void
    {
        Server::factory()->create(['status' => 'maintenance']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 1;
            });
    }

    public function test_includes_offline_servers(): void
    {
        Server::factory()->create(['status' => 'offline']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 1;
            });
    }

    public function test_only_excludes_deleted_status(): void
    {
        Server::factory()->create(['status' => 'active']);
        Server::factory()->create(['status' => 'inactive']);
        Server::factory()->create(['status' => 'maintenance']);
        Server::factory()->create(['status' => 'offline']);
        Server::factory()->create(['status' => 'deleted']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 4;
            });
    }

    // ==================== EDGE CASES ====================

    public function test_can_select_nonexistent_server(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->call('selectServer', 99999)
            ->assertSet('selectedServerId', 99999)
            ->assertViewHas('selectedServer', null);
    }

    public function test_can_select_deleted_server_id(): void
    {
        $server = Server::factory()->create(['status' => 'deleted']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->call('selectServer', $server->id)
            ->assertSet('selectedServerId', $server->id)
            ->assertViewHas('selectedServer', function ($selectedServer) use ($server) {
                return $selectedServer !== null && $selectedServer->id === $server->id;
            });
    }

    public function test_server_list_updates_when_server_created(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->isEmpty();
            });

        Server::factory()->create(['status' => 'active']);

        $component->call('$refresh')
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 1;
            });
    }

    public function test_server_list_excludes_newly_deleted_server(): void
    {
        $server = Server::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 1;
            });

        $server->update(['status' => 'deleted']);

        $component->call('$refresh')
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->isEmpty();
            });
    }

    // ==================== SERVER DATA TESTS ====================

    public function test_server_list_contains_all_server_attributes(): void
    {
        $server = Server::factory()->create([
            'name' => 'Production Server',
            'ip_address' => '192.168.1.100',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                $firstServer = $viewServers->first();
                return $firstServer !== null &&
                       $firstServer->name === 'Production Server' &&
                       $firstServer->ip_address === '192.168.1.100' &&
                       $firstServer->status === 'active';
            });
    }

    public function test_selected_server_contains_full_model(): void
    {
        $server = Server::factory()->create([
            'name' => 'Test Server',
            'hostname' => 'test.example.com',
            'ip_address' => '10.0.0.1',
            'ssh_port' => 22,
            'status' => 'active',
        ]);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->call('selectServer', $server->id)
            ->assertViewHas('selectedServer', function ($selectedServer) {
                return $selectedServer->name === 'Test Server' &&
                       $selectedServer->hostname === 'test.example.com' &&
                       $selectedServer->ip_address === '10.0.0.1' &&
                       $selectedServer->ssh_port === 22;
            });
    }

    // ==================== MULTIPLE USER TESTS ====================

    public function test_different_users_have_independent_selections(): void
    {
        $user2 = User::factory()->create();
        $server1 = Server::factory()->create(['status' => 'active']);
        $server2 = Server::factory()->create(['status' => 'active']);

        $component1 = Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->call('selectServer', $server1->id)
            ->assertSet('selectedServerId', $server1->id);

        $component2 = Livewire::actingAs($user2)
            ->test(SSHTerminalSelector::class)
            ->call('selectServer', $server2->id)
            ->assertSet('selectedServerId', $server2->id);

        // Verify they remain independent
        $component1->assertSet('selectedServerId', $server1->id);
    }

    public function test_all_users_see_same_server_list(): void
    {
        $user2 = User::factory()->create();
        Server::factory()->count(5)->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 5;
            });

        Livewire::actingAs($user2)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 5;
            });
    }

    // ==================== PROPERTY TESTS ====================

    public function test_selected_server_id_is_nullable(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertSet('selectedServerId', null);
    }

    public function test_can_set_selected_server_id_directly(): void
    {
        $server = Server::factory()->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->set('selectedServerId', $server->id)
            ->assertSet('selectedServerId', $server->id);
    }

    // ==================== LARGE DATASET TESTS ====================

    public function test_handles_large_number_of_servers(): void
    {
        Server::factory()->count(100)->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 100;
            });
    }

    public function test_alphabetical_ordering_with_many_servers(): void
    {
        $names = ['Delta', 'Alpha', 'Gamma', 'Beta', 'Epsilon'];
        foreach ($names as $name) {
            Server::factory()->create(['name' => $name, 'status' => 'active']);
        }

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                $orderedNames = $viewServers->pluck('name')->toArray();
                return $orderedNames === ['Alpha', 'Beta', 'Delta', 'Epsilon', 'Gamma'];
            });
    }

    // ==================== VIEW RENDERING TESTS ====================

    public function test_view_receives_correct_data_structure(): void
    {
        $server = Server::factory()->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->call('selectServer', $server->id)
            ->assertViewHas('servers')
            ->assertViewHas('selectedServer');
    }

    public function test_servers_collection_is_eloquent_collection(): void
    {
        Server::factory()->count(3)->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers instanceof \Illuminate\Database\Eloquent\Collection;
            });
    }

    // ==================== CONNECTION STATUS TESTS ====================

    public function test_can_select_server_regardless_of_connection_status(): void
    {
        $activeServer = Server::factory()->create(['status' => 'active']);
        $offlineServer = Server::factory()->create(['status' => 'offline']);

        $component = Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class);

        $component->call('selectServer', $activeServer->id)
            ->assertSet('selectedServerId', $activeServer->id);

        $component->call('selectServer', $offlineServer->id)
            ->assertSet('selectedServerId', $offlineServer->id);
    }

    public function test_selected_server_shows_correct_status(): void
    {
        $server = Server::factory()->create(['status' => 'maintenance']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->call('selectServer', $server->id)
            ->assertViewHas('selectedServer', function ($selectedServer) {
                return $selectedServer->status === 'maintenance';
            });
    }

    // ==================== REFRESH TESTS ====================

    public function test_component_can_be_refreshed(): void
    {
        Server::factory()->count(3)->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->call('$refresh')
            ->assertStatus(200)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->count() === 3;
            });
    }

    public function test_selection_persists_after_refresh(): void
    {
        $server = Server::factory()->create(['status' => 'active']);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->call('selectServer', $server->id)
            ->assertSet('selectedServerId', $server->id)
            ->call('$refresh')
            ->assertSet('selectedServerId', $server->id);
    }

    // ==================== SPECIAL CHARACTER TESTS ====================

    public function test_handles_servers_with_special_characters_in_name(): void
    {
        $server = Server::factory()->create([
            'name' => 'Server (Production) #1 - Main',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->call('selectServer', $server->id)
            ->assertViewHas('selectedServer', function ($selectedServer) {
                return $selectedServer->name === 'Server (Production) #1 - Main';
            });
    }

    public function test_handles_servers_with_unicode_names(): void
    {
        $server = Server::factory()->create([
            'name' => 'サーバー Production 服务器',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->user)
            ->test(SSHTerminalSelector::class)
            ->assertViewHas('servers', function ($viewServers) {
                return $viewServers->first()->name === 'サーバー Production 服务器';
            });
    }
}
