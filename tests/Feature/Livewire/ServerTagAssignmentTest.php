<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\ServerTagAssignment;
use App\Models\Server;
use App\Models\ServerTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServerTagAssignmentTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['status' => 'online']);
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    public function test_component_loads_with_server(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('server.id', $this->server->id);
    }

    public function test_component_loads_available_tags_on_mount(): void
    {
        ServerTag::factory()->count(3)->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('availableTags', function ($tags) {
                return count($tags) === 3;
            });
    }

    public function test_available_tags_are_ordered_by_name(): void
    {
        ServerTag::factory()->create(['name' => 'Zebra', 'user_id' => $this->user->id]);
        ServerTag::factory()->create(['name' => 'Alpha', 'user_id' => $this->user->id]);
        ServerTag::factory()->create(['name' => 'Beta', 'user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('availableTags', function ($tags) {
                return $tags[0]['name'] === 'Alpha' &&
                       $tags[1]['name'] === 'Beta' &&
                       $tags[2]['name'] === 'Zebra';
            });
    }

    public function test_component_displays_empty_state_without_tags(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('availableTags', []);
    }

    // ==================== SELECTED TAGS TESTS ====================

    public function test_loads_currently_selected_tags(): void
    {
        $tags = ServerTag::factory()->count(3)->create(['user_id' => $this->user->id]);
        $tagIds = $tags->pluck('id')->toArray();
        $this->server->tags()->attach($tagIds);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('selectedTags', function ($selectedTags) use ($tagIds) {
                return count($selectedTags) === 3 &&
                       in_array($tagIds[0], $selectedTags) &&
                       in_array($tagIds[1], $selectedTags) &&
                       in_array($tagIds[2], $selectedTags);
            });
    }

    public function test_selected_tags_empty_when_no_tags_assigned(): void
    {
        ServerTag::factory()->count(3)->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('selectedTags', []);
    }

    // ==================== TOGGLE TAG TESTS ====================

    public function test_can_toggle_tag_to_add(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('selectedTags', [])
            ->call('toggleTag', $tag->id)
            ->assertSet('selectedTags', function ($selectedTags) use ($tag) {
                return in_array($tag->id, $selectedTags);
            });
    }

    public function test_can_toggle_tag_to_remove(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);
        $this->server->tags()->attach($tag->id);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('selectedTags', function ($selectedTags) use ($tag) {
                return in_array($tag->id, $selectedTags);
            })
            ->call('toggleTag', $tag->id)
            ->assertSet('selectedTags', []);
    }

    public function test_toggle_is_idempotent(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->call('toggleTag', $tag->id)
            ->assertSet('selectedTags', function ($selectedTags) use ($tag) {
                return in_array($tag->id, $selectedTags);
            })
            ->call('toggleTag', $tag->id)
            ->assertSet('selectedTags', [])
            ->call('toggleTag', $tag->id)
            ->assertSet('selectedTags', function ($selectedTags) use ($tag) {
                return in_array($tag->id, $selectedTags);
            });
    }

    public function test_can_toggle_multiple_tags(): void
    {
        $tags = ServerTag::factory()->count(3)->create(['user_id' => $this->user->id]);
        $tagIds = $tags->pluck('id')->toArray();

        $component = Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server]);

        foreach ($tagIds as $tagId) {
            $component->call('toggleTag', $tagId);
        }

        $component->assertSet('selectedTags', function ($selectedTags) {
            return count($selectedTags) === 3;
        });
    }

    // ==================== SAVE TAGS TESTS ====================

    public function test_can_save_tags(): void
    {
        $tags = ServerTag::factory()->count(2)->create(['user_id' => $this->user->id]);
        $tagIds = $tags->pluck('id')->toArray();

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->call('toggleTag', $tagIds[0])
            ->call('toggleTag', $tagIds[1])
            ->call('saveTags')
            ->assertSessionHas('message', 'Tags updated successfully');

        $freshServer = $this->server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertCount(2, $freshServer->tags);
    }

    public function test_save_tags_syncs_database(): void
    {
        $allTags = ServerTag::factory()->count(3)->create(['user_id' => $this->user->id]);
        $tagIds = $allTags->pluck('id')->toArray();
        $this->server->tags()->attach($tagIds[0]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->call('toggleTag', $tagIds[0])
            ->call('toggleTag', $tagIds[1])
            ->call('toggleTag', $tagIds[2])
            ->call('saveTags');

        $freshServer = $this->server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertCount(2, $freshServer->tags);
        $this->assertFalse($freshServer->tags->contains('id', $tagIds[0]));
        $this->assertTrue($freshServer->tags->contains('id', $tagIds[1]));
        $this->assertTrue($freshServer->tags->contains('id', $tagIds[2]));
    }

    public function test_save_tags_dispatches_events(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->call('toggleTag', $tag->id)
            ->call('saveTags')
            ->assertDispatched('tags-assigned')
            ->assertDispatched('tag-updated');
    }

    public function test_save_empty_tags_removes_all(): void
    {
        $tags = ServerTag::factory()->count(2)->create(['user_id' => $this->user->id]);
        $tagIds = $tags->pluck('id')->toArray();
        $this->server->tags()->attach($tagIds);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->call('toggleTag', $tagIds[0])
            ->call('toggleTag', $tagIds[1])
            ->call('saveTags');

        $freshServer = $this->server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertCount(0, $freshServer->tags);
    }

    // ==================== REFRESH TESTS ====================

    public function test_refresh_tags_reloads_list(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('availableTags', []);

        ServerTag::factory()->create(['name' => 'New Tag', 'user_id' => $this->user->id]);

        $component->call('refreshTags')
            ->assertSet('availableTags', function ($tags) {
                return count($tags) === 1;
            });
    }

    public function test_responds_to_tag_updated_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->dispatch('tag-updated')
            ->assertStatus(200);
    }

    // ==================== USER ISOLATION TESTS ====================

    public function test_only_shows_current_user_tags(): void
    {
        $otherUser = User::factory()->create();
        ServerTag::factory()->count(2)->create(['user_id' => $this->user->id]);
        ServerTag::factory()->count(3)->create(['user_id' => $otherUser->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('availableTags', function ($tags) {
                return count($tags) === 2;
            });
    }

    public function test_different_users_see_their_own_tags(): void
    {
        $otherUser = User::factory()->create();
        ServerTag::factory()->create(['name' => 'User1 Tag', 'user_id' => $this->user->id]);
        ServerTag::factory()->create(['name' => 'User2 Tag', 'user_id' => $otherUser->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('availableTags', function ($tags) {
                return count($tags) === 1 && $tags[0]['name'] === 'User1 Tag';
            });

        Livewire::actingAs($otherUser)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('availableTags', function ($tags) {
                return count($tags) === 1 && $tags[0]['name'] === 'User2 Tag';
            });
    }

    // ==================== SERVER ISOLATION TESTS ====================

    public function test_tags_are_server_specific(): void
    {
        $server2 = Server::factory()->create(['status' => 'online']);
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        $this->server->tags()->attach($tag->id);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('selectedTags', function ($selectedTags) use ($tag) {
                return in_array($tag->id, $selectedTags);
            });

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $server2])
            ->assertSet('selectedTags', []);
    }

    public function test_saving_tags_does_not_affect_other_servers(): void
    {
        $server2 = Server::factory()->create(['status' => 'online']);
        $tags = ServerTag::factory()->count(2)->create(['user_id' => $this->user->id]);
        $tagIds = $tags->pluck('id')->toArray();

        $this->server->tags()->attach($tagIds[0]);
        $server2->tags()->attach($tagIds[1]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->call('toggleTag', $tagIds[0])
            ->call('saveTags');

        $freshServer2 = $server2->fresh();
        $this->assertNotNull($freshServer2);
        $this->assertCount(1, $freshServer2->tags);
        $this->assertTrue($freshServer2->tags->contains('id', $tagIds[1]));
    }

    // ==================== EDGE CASES ====================

    public function test_toggle_nonexistent_tag(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->call('toggleTag', 99999)
            ->assertSet('selectedTags', function ($selectedTags) {
                return in_array(99999, $selectedTags);
            });
    }

    public function test_save_with_nonexistent_tag_in_selection(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->set('selectedTags', [99999])
            ->call('saveTags');

        $freshServer = $this->server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertCount(0, $freshServer->tags);
    }

    // ==================== TAG DATA TESTS ====================

    public function test_available_tags_contain_all_attributes(): void
    {
        $tag = ServerTag::factory()->create([
            'name' => 'Production',
            'color' => '#ff5733',
            'user_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('availableTags', function ($tags) use ($tag) {
                return $tags[0]['id'] === $tag->id &&
                       $tags[0]['name'] === 'Production' &&
                       $tags[0]['color'] === '#ff5733';
            });
    }

    // ==================== MULTIPLE SERVERS TESTS ====================

    public function test_can_assign_same_tag_to_multiple_servers(): void
    {
        $server2 = Server::factory()->create(['status' => 'online']);
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->call('toggleTag', $tag->id)
            ->call('saveTags');

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $server2])
            ->call('toggleTag', $tag->id)
            ->call('saveTags');

        $freshServer1 = $this->server->fresh();
        $freshServer2 = $server2->fresh();
        $this->assertNotNull($freshServer1);
        $this->assertNotNull($freshServer2);
        $this->assertTrue($freshServer1->tags->contains('id', $tag->id));
        $this->assertTrue($freshServer2->tags->contains('id', $tag->id));
    }

    // ==================== WORKFLOW TESTS ====================

    public function test_full_assignment_workflow(): void
    {
        $tags = ServerTag::factory()->count(5)->create(['user_id' => $this->user->id]);
        $tagIds = $tags->pluck('id')->toArray();

        $component = Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server]);

        // Select some tags
        $component->call('toggleTag', $tagIds[0])
            ->call('toggleTag', $tagIds[2])
            ->call('toggleTag', $tagIds[4])
            ->call('saveTags')
            ->assertSessionHas('message', 'Tags updated successfully');

        $freshServer = $this->server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertCount(3, $freshServer->tags);

        // Change selection
        $component->call('toggleTag', $tagIds[0])
            ->call('toggleTag', $tagIds[1])
            ->call('saveTags');

        $freshServer = $this->server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertCount(3, $freshServer->tags);
        $this->assertFalse($freshServer->tags->contains('id', $tagIds[0]));
        $this->assertTrue($freshServer->tags->contains('id', $tagIds[1]));
    }

    // ==================== LARGE DATASET TESTS ====================

    public function test_handles_many_available_tags(): void
    {
        for ($i = 1; $i <= 50; $i++) {
            ServerTag::factory()->create([
                'name' => "Tag {$i}",
                'user_id' => $this->user->id,
            ]);
        }

        Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server])
            ->assertSet('availableTags', function ($tags) {
                return count($tags) === 50;
            });
    }

    public function test_can_assign_many_tags(): void
    {
        $tagIds = [];
        for ($i = 1; $i <= 20; $i++) {
            $tag = ServerTag::factory()->create([
                'name' => "Tag {$i}",
                'user_id' => $this->user->id,
            ]);
            $tagIds[] = $tag->id;
        }

        $component = Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server]);

        foreach ($tagIds as $tagId) {
            $component->call('toggleTag', $tagId);
        }

        $component->call('saveTags');

        $freshServer = $this->server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertCount(20, $freshServer->tags);
    }

    // ==================== LOAD TAGS TESTS ====================

    public function test_load_tags_method_refreshes_data(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);
        $this->server->tags()->attach($tag->id);

        $component = Livewire::actingAs($this->user)
            ->test(ServerTagAssignment::class, ['server' => $this->server]);

        $newTag = ServerTag::factory()->create(['name' => 'New', 'user_id' => $this->user->id]);
        $this->server->tags()->attach($newTag->id);

        $component->call('loadTags')
            ->assertSet('selectedTags', function ($selectedTags) use ($tag, $newTag) {
                return in_array($tag->id, $selectedTags) &&
                       in_array($newTag->id, $selectedTags);
            });
    }
}
