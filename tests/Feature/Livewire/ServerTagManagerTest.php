<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\ServerTagManager;
use App\Models\Server;
use App\Models\ServerTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServerTagManagerTest extends TestCase
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
            ->test(ServerTagManager::class)
            ->assertStatus(200);
    }

    public function test_component_loads_tags_on_mount(): void
    {
        ServerTag::factory()->count(3)->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('tags', function ($tags) {
                return count($tags) === 3;
            });
    }

    public function test_component_displays_empty_state_without_tags(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('tags', []);
    }

    public function test_tags_are_ordered_by_name(): void
    {
        ServerTag::factory()->create(['name' => 'Zebra', 'user_id' => $this->user->id]);
        ServerTag::factory()->create(['name' => 'Alpha', 'user_id' => $this->user->id]);
        ServerTag::factory()->create(['name' => 'Beta', 'user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('tags', function ($tags) {
                return $tags[0]['name'] === 'Alpha' &&
                       $tags[1]['name'] === 'Beta' &&
                       $tags[2]['name'] === 'Zebra';
            });
    }

    // ==================== CREATE TAG TESTS ====================

    public function test_can_create_tag(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Production')
            ->set('newTagColor', '#ff5733')
            ->call('createTag')
            ->assertHasNoErrors()
            ->assertSet('newTagName', '')
            ->assertSet('newTagColor', '#6366f1');

        $this->assertDatabaseHas('server_tags', [
            'name' => 'Production',
            'color' => '#ff5733',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_create_tag_flashes_success_message(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Test Tag')
            ->set('newTagColor', '#123456')
            ->call('createTag')
            ->assertSessionHas('message', 'Tag created successfully');
    }

    public function test_create_tag_dispatches_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Event Test')
            ->set('newTagColor', '#abcdef')
            ->call('createTag')
            ->assertDispatched('tag-updated');
    }

    public function test_create_tag_requires_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', '')
            ->set('newTagColor', '#123456')
            ->call('createTag')
            ->assertHasErrors(['newTagName' => 'required']);
    }

    public function test_create_tag_name_must_be_unique(): void
    {
        ServerTag::factory()->create(['name' => 'Existing', 'user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Existing')
            ->set('newTagColor', '#123456')
            ->call('createTag')
            ->assertHasErrors(['newTagName' => 'unique']);
    }

    public function test_create_tag_name_max_length(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', str_repeat('a', 51))
            ->set('newTagColor', '#123456')
            ->call('createTag')
            ->assertHasErrors(['newTagName' => 'max']);
    }

    public function test_create_tag_requires_valid_hex_color(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Test')
            ->set('newTagColor', 'invalid')
            ->call('createTag')
            ->assertHasErrors(['newTagColor' => 'regex']);
    }

    public function test_create_tag_color_must_be_valid_format(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Test')
            ->set('newTagColor', '#12345')
            ->call('createTag')
            ->assertHasErrors(['newTagColor']);
    }

    public function test_create_tag_accepts_uppercase_hex(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Uppercase')
            ->set('newTagColor', '#AABBCC')
            ->call('createTag')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('server_tags', [
            'name' => 'Uppercase',
            'color' => '#AABBCC',
        ]);
    }

    public function test_create_tag_reloads_tags_list(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('tags', [])
            ->set('newTagName', 'New Tag')
            ->set('newTagColor', '#123456')
            ->call('createTag')
            ->assertSet('tags', function ($tags) {
                return count($tags) === 1 && $tags[0]['name'] === 'New Tag';
            });
    }

    // ==================== EDIT TAG TESTS ====================

    public function test_can_open_edit_modal(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('editTag', $tag->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingTag', $tag->id)
            ->assertSet('editTagName', $tag->name)
            ->assertSet('editTagColor', $tag->color);
    }

    public function test_edit_nonexistent_tag_does_nothing(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('editTag', 99999)
            ->assertSet('showEditModal', false)
            ->assertSet('editingTag', null);
    }

    public function test_can_update_tag(): void
    {
        $tag = ServerTag::factory()->create([
            'name' => 'Original',
            'color' => '#111111',
            'user_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('editTag', $tag->id)
            ->set('editTagName', 'Updated')
            ->set('editTagColor', '#222222')
            ->call('updateTag')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('server_tags', [
            'id' => $tag->id,
            'name' => 'Updated',
            'color' => '#222222',
        ]);
    }

    public function test_update_tag_flashes_success_message(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('editTag', $tag->id)
            ->set('editTagName', 'Updated Name')
            ->call('updateTag')
            ->assertSessionHas('message', 'Tag updated successfully');
    }

    public function test_update_tag_dispatches_event(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('editTag', $tag->id)
            ->set('editTagName', 'Updated')
            ->call('updateTag')
            ->assertDispatched('tag-updated');
    }

    public function test_update_tag_validates_name_required(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('editTag', $tag->id)
            ->set('editTagName', '')
            ->call('updateTag')
            ->assertHasErrors(['editTagName' => 'required']);
    }

    public function test_update_tag_allows_same_name(): void
    {
        $tag = ServerTag::factory()->create([
            'name' => 'Same Name',
            'user_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('editTag', $tag->id)
            ->set('editTagName', 'Same Name')
            ->set('editTagColor', '#ffffff')
            ->call('updateTag')
            ->assertHasNoErrors();
    }

    public function test_update_tag_validates_unique_name_excluding_self(): void
    {
        $tag1 = ServerTag::factory()->create(['name' => 'Tag One', 'user_id' => $this->user->id]);
        ServerTag::factory()->create(['name' => 'Tag Two', 'user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('editTag', $tag1->id)
            ->set('editTagName', 'Tag Two')
            ->call('updateTag')
            ->assertHasErrors(['editTagName' => 'unique']);
    }

    public function test_update_without_editing_tag_does_nothing(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('editTagName', 'Test')
            ->set('editTagColor', '#123456')
            ->call('updateTag')
            ->assertSet('tags', []);
    }

    // ==================== DELETE TAG TESTS ====================

    public function test_can_delete_tag(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('deleteTag', $tag->id)
            ->assertSessionHas('message', 'Tag deleted successfully');

        $this->assertDatabaseMissing('server_tags', ['id' => $tag->id]);
    }

    public function test_delete_tag_dispatches_event(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('deleteTag', $tag->id)
            ->assertDispatched('tag-updated');
    }

    public function test_delete_nonexistent_tag_does_nothing(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('deleteTag', 99999)
            ->assertSessionMissing('message');
    }

    public function test_delete_tag_reloads_tags_list(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('tags', function ($tags) {
                return count($tags) === 1;
            })
            ->call('deleteTag', $tag->id)
            ->assertSet('tags', []);
    }

    // ==================== MODAL TESTS ====================

    public function test_can_close_edit_modal(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('editTag', $tag->id)
            ->assertSet('showEditModal', true)
            ->call('closeEditModal')
            ->assertSet('showEditModal', false)
            ->assertSet('editingTag', null)
            ->assertSet('editTagName', '')
            ->assertSet('editTagColor', '');
    }

    public function test_close_modal_resets_all_edit_fields(): void
    {
        $tag = ServerTag::factory()->create([
            'name' => 'Test Tag',
            'color' => '#aabbcc',
            'user_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->call('editTag', $tag->id)
            ->assertSet('editingTag', $tag->id)
            ->assertSet('editTagName', 'Test Tag')
            ->assertSet('editTagColor', '#aabbcc')
            ->call('closeEditModal')
            ->assertSet('editingTag', null)
            ->assertSet('editTagName', '')
            ->assertSet('editTagColor', '');
    }

    // ==================== SERVER COUNT TESTS ====================

    public function test_tags_include_server_count(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);
        $servers = Server::factory()->count(3)->create(['status' => 'active']);
        $tag->servers()->attach($servers->pluck('id'));

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('tags', function ($tags) {
                return count($tags) === 1 && $tags[0]['servers_count'] === 3;
            });
    }

    public function test_tag_with_no_servers_has_zero_count(): void
    {
        ServerTag::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('tags', function ($tags) {
                return count($tags) === 1 && $tags[0]['servers_count'] === 0;
            });
    }

    // ==================== DEFAULT VALUES TESTS ====================

    public function test_default_color_is_set(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('newTagColor', '#6366f1');
    }

    public function test_edit_modal_is_closed_by_default(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('showEditModal', false);
    }

    public function test_editing_tag_is_null_by_default(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('editingTag', null);
    }

    // ==================== REFRESH TESTS ====================

    public function test_refresh_tags_reloads_list(): void
    {
        $tag = ServerTag::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('tags', function ($tags) {
                return count($tags) === 1;
            });

        ServerTag::factory()->create(['name' => 'New Tag', 'user_id' => $this->user->id]);

        $component->call('refreshTags')
            ->assertSet('tags', function ($tags) {
                return count($tags) === 2;
            });
    }

    public function test_responds_to_tag_updated_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('tags', [])
            ->dispatch('tag-updated')
            ->assertSet('tags', []);
    }

    // ==================== MULTIPLE USERS TESTS ====================

    public function test_all_users_see_all_tags(): void
    {
        $user2 = User::factory()->create();
        ServerTag::factory()->create(['name' => 'User1 Tag', 'user_id' => $this->user->id]);
        ServerTag::factory()->create(['name' => 'User2 Tag', 'user_id' => $user2->id]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('tags', function ($tags) {
                return count($tags) === 2;
            });

        Livewire::actingAs($user2)
            ->test(ServerTagManager::class)
            ->assertSet('tags', function ($tags) {
                return count($tags) === 2;
            });
    }

    // ==================== COLOR VALIDATION TESTS ====================

    public function test_color_with_lowercase_hex_is_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Lowercase')
            ->set('newTagColor', '#aabbcc')
            ->call('createTag')
            ->assertHasNoErrors();
    }

    public function test_color_with_mixed_case_hex_is_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Mixed Case')
            ->set('newTagColor', '#AaBbCc')
            ->call('createTag')
            ->assertHasNoErrors();
    }

    public function test_color_without_hash_is_invalid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'No Hash')
            ->set('newTagColor', 'aabbcc')
            ->call('createTag')
            ->assertHasErrors(['newTagColor']);
    }

    public function test_color_with_rgb_format_is_invalid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'RGB Format')
            ->set('newTagColor', 'rgb(255,0,0)')
            ->call('createTag')
            ->assertHasErrors(['newTagColor']);
    }

    // ==================== SPECIAL CHARACTERS TESTS ====================

    public function test_tag_name_with_spaces_is_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Production Servers')
            ->set('newTagColor', '#123456')
            ->call('createTag')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('server_tags', ['name' => 'Production Servers']);
    }

    public function test_tag_name_with_special_characters_is_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->set('newTagName', 'Env: Prod-1')
            ->set('newTagColor', '#123456')
            ->call('createTag')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('server_tags', ['name' => 'Env: Prod-1']);
    }

    // ==================== LOAD TAGS TESTS ====================

    public function test_load_tags_method_refreshes_tags(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('tags', [])
            ->call('loadTags')
            ->assertSet('tags', []);
    }

    public function test_tags_array_contains_all_tag_attributes(): void
    {
        $tag = ServerTag::factory()->create([
            'name' => 'Test',
            'color' => '#abcdef',
            'user_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerTagManager::class)
            ->assertSet('tags', function ($tags) use ($tag) {
                return $tags[0]['id'] === $tag->id &&
                       $tags[0]['name'] === 'Test' &&
                       $tags[0]['color'] === '#abcdef' &&
                       $tags[0]['user_id'] === $this->user->id;
            });
    }

    // ==================== WORKFLOW TESTS ====================

    public function test_full_create_edit_delete_workflow(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ServerTagManager::class);

        // Create
        $component->set('newTagName', 'Workflow Tag')
            ->set('newTagColor', '#111111')
            ->call('createTag')
            ->assertHasNoErrors();

        $tag = ServerTag::where('name', 'Workflow Tag')->first();
        $this->assertNotNull($tag);

        // Edit
        $component->call('editTag', $tag->id)
            ->set('editTagName', 'Updated Workflow Tag')
            ->set('editTagColor', '#222222')
            ->call('updateTag')
            ->assertHasNoErrors();

        $freshTag = $tag->fresh();
        $this->assertNotNull($freshTag);
        $this->assertEquals('Updated Workflow Tag', $freshTag->name);
        $this->assertEquals('#222222', $freshTag->color);

        // Delete
        $component->call('deleteTag', $tag->id);
        $this->assertDatabaseMissing('server_tags', ['id' => $tag->id]);
    }

    public function test_can_create_multiple_tags(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ServerTagManager::class);

        for ($i = 1; $i <= 5; $i++) {
            $component->set('newTagName', "Tag {$i}")
                ->set('newTagColor', '#'.$i.$i.$i.$i.$i.$i)
                ->call('createTag')
                ->assertHasNoErrors();
        }

        $this->assertDatabaseCount('server_tags', 5);
    }
}
