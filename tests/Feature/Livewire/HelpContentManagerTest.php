<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\HelpContentManager;
use App\Models\HelpContent;
use App\Models\HelpContentTranslation;
use App\Models\User;
use App\Services\HelpContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HelpContentManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('super-admin', 'web');

        // Create admin user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        // Create regular user
        $this->regularUser = User::factory()->create();
    }

    private function mockHelpContentService(): void
    {
        $this->mock(HelpContentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('clearCache')->andReturn(true);
        });
    }

    // ==================== AUTHORIZATION TESTS ====================

    public function test_component_renders_for_admin(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->assertStatus(200);
    }

    public function test_component_renders_for_super_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        Livewire::actingAs($superAdmin)
            ->test(HelpContentManager::class)
            ->assertStatus(200);
    }

    public function test_component_denies_access_to_regular_user(): void
    {
        Livewire::actingAs($this->regularUser)
            ->test(HelpContentManager::class)
            ->assertForbidden();
    }

    // ==================== LISTING TESTS ====================

    public function test_displays_help_contents(): void
    {
        HelpContent::factory()->count(3)->create();

        $component = Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class);

        $helpContents = $component->viewData('helpContents');
        $this->assertCount(3, $helpContents);
    }

    public function test_paginates_help_contents(): void
    {
        HelpContent::factory()->count(20)->create();

        $component = Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class);

        $helpContents = $component->viewData('helpContents');
        $this->assertCount(15, $helpContents); // Default pagination is 15
    }

    public function test_displays_empty_state_when_no_content(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->assertSee('No help content found');
    }

    // ==================== SEARCH TESTS ====================

    public function test_can_search_by_title(): void
    {
        HelpContent::factory()->create(['title' => 'How to deploy']);
        HelpContent::factory()->create(['title' => 'Server management']);

        $component = Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->set('search', 'deploy');

        $helpContents = $component->viewData('helpContents');
        $this->assertCount(1, $helpContents);
    }

    public function test_can_search_by_key(): void
    {
        HelpContent::factory()->withKey('deployment-guide')->create();
        HelpContent::factory()->withKey('server-setup')->create();

        $component = Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->set('search', 'deployment');

        $helpContents = $component->viewData('helpContents');
        $this->assertCount(1, $helpContents);
    }

    public function test_search_resets_pagination(): void
    {
        HelpContent::factory()->count(20)->create();

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->set('page', 2)
            ->set('search', 'test')
            ->assertSet('page', 1);
    }

    // ==================== FILTER TESTS ====================

    public function test_can_filter_by_category(): void
    {
        HelpContent::factory()->category('deployment')->count(3)->create();
        HelpContent::factory()->category('servers')->count(2)->create();

        $component = Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->set('categoryFilter', 'deployment');

        $helpContents = $component->viewData('helpContents');
        $this->assertCount(3, $helpContents);
    }

    public function test_can_filter_by_active_status(): void
    {
        HelpContent::factory()->count(3)->create(['is_active' => true]);
        HelpContent::factory()->inactive()->count(2)->create();

        $component = Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->set('statusFilter', 'active');

        $helpContents = $component->viewData('helpContents');
        $this->assertCount(3, $helpContents);
    }

    public function test_can_filter_by_inactive_status(): void
    {
        HelpContent::factory()->count(3)->create(['is_active' => true]);
        HelpContent::factory()->inactive()->count(2)->create();

        $component = Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->set('statusFilter', 'inactive');

        $helpContents = $component->viewData('helpContents');
        $this->assertCount(2, $helpContents);
    }

    public function test_category_filter_resets_pagination(): void
    {
        HelpContent::factory()->count(20)->create();

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->set('page', 2)
            ->set('categoryFilter', 'deployment')
            ->assertSet('page', 1);
    }

    public function test_status_filter_resets_pagination(): void
    {
        HelpContent::factory()->count(20)->create();

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->set('page', 2)
            ->set('statusFilter', 'active')
            ->assertSet('page', 1);
    }

    // ==================== SORTING TESTS ====================

    public function test_can_sort_by_field(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('sortBy', 'title')
            ->assertSet('sortField', 'title')
            ->assertSet('sortDirection', 'asc');
    }

    public function test_toggle_sort_direction(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('sortBy', 'title')
            ->assertSet('sortDirection', 'asc')
            ->call('sortBy', 'title')
            ->assertSet('sortDirection', 'desc');
    }

    public function test_change_field_resets_direction(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('sortBy', 'title')
            ->call('sortBy', 'title')
            ->assertSet('sortDirection', 'desc')
            ->call('sortBy', 'category')
            ->assertSet('sortField', 'category')
            ->assertSet('sortDirection', 'asc');
    }

    // ==================== CREATE MODAL TESTS ====================

    public function test_can_open_create_modal(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSet('editingId', null);
    }

    public function test_create_modal_resets_form(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->set('key', 'some-key')
            ->set('title', 'Some Title')
            ->call('openCreateModal')
            ->assertSet('key', '')
            ->assertSet('title', '');
    }

    // ==================== EDIT MODAL TESTS ====================

    public function test_can_open_edit_modal(): void
    {
        $helpContent = HelpContent::factory()->create([
            'key' => 'test-key',
            'title' => 'Test Title',
            'category' => 'deployment',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openEditModal', $helpContent->id)
            ->assertSet('showCreateModal', true)
            ->assertSet('editingId', $helpContent->id)
            ->assertSet('key', 'test-key')
            ->assertSet('title', 'Test Title')
            ->assertSet('category', 'deployment');
    }

    public function test_edit_modal_loads_translation(): void
    {
        $helpContent = HelpContent::factory()->create();
        HelpContentTranslation::create([
            'help_content_id' => $helpContent->id,
            'locale' => 'ar',
            'brief' => 'Arabic brief',
            'details' => ['key' => 'Arabic value'],
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openEditModal', $helpContent->id)
            ->assertSet('ar_brief', 'Arabic brief')
            ->assertSet('ar_details', ['key' => 'Arabic value']);
    }

    // ==================== CREATE TESTS ====================

    public function test_can_create_help_content(): void
    {
        $this->mockHelpContentService();

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openCreateModal')
            ->set('key', 'new-help-key')
            ->set('category', 'deployment')
            ->set('ui_element_type', 'tooltip')
            ->set('icon', 'info-circle')
            ->set('title', 'New Help Title')
            ->set('brief', 'Brief description of the help content')
            ->call('save')
            ->assertSet('showCreateModal', false)
            ->assertSessionHas('message', 'Help content created successfully!');

        $this->assertDatabaseHas('help_contents', [
            'key' => 'new-help-key',
            'title' => 'New Help Title',
            'category' => 'deployment',
        ]);
    }

    public function test_create_validates_required_fields(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openCreateModal')
            ->call('save')
            ->assertHasErrors(['key', 'category', 'title', 'brief']);
    }

    public function test_create_validates_ui_element_type(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openCreateModal')
            ->set('ui_element_type', 'invalid-type')
            ->call('save')
            ->assertHasErrors(['ui_element_type']);
    }

    public function test_create_validates_urls(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openCreateModal')
            ->set('docs_url', 'not-a-url')
            ->set('video_url', 'also-not-a-url')
            ->call('save')
            ->assertHasErrors(['docs_url', 'video_url']);
    }

    public function test_create_with_translation(): void
    {
        $this->mockHelpContentService();

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openCreateModal')
            ->set('key', 'translated-key')
            ->set('category', 'servers')
            ->set('ui_element_type', 'modal')
            ->set('icon', 'server')
            ->set('title', 'English Title')
            ->set('brief', 'English brief description')
            ->set('ar_brief', 'Arabic brief description')
            ->call('save');

        $helpContent = HelpContent::where('key', 'translated-key')->first();
        $this->assertNotNull($helpContent);

        $translation = $helpContent->translations()->where('locale', 'ar')->first();
        $this->assertNotNull($translation);
        $this->assertEquals('Arabic brief description', $translation->brief);
    }

    // ==================== UPDATE TESTS ====================

    public function test_can_update_help_content(): void
    {
        $this->mockHelpContentService();

        $helpContent = HelpContent::factory()->create([
            'title' => 'Original Title',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openEditModal', $helpContent->id)
            ->set('title', 'Updated Title')
            ->call('save')
            ->assertSessionHas('message', 'Help content updated successfully!');

        $this->assertDatabaseHas('help_contents', [
            'id' => $helpContent->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_update_dispatches_event(): void
    {
        $this->mockHelpContentService();

        $helpContent = HelpContent::factory()->create();

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openEditModal', $helpContent->id)
            ->call('save')
            ->assertDispatched('help-content-saved');
    }

    // ==================== DELETE TESTS ====================

    public function test_can_open_delete_modal(): void
    {
        $helpContent = HelpContent::factory()->create();

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('confirmDelete', $helpContent->id)
            ->assertSet('showDeleteModal', true)
            ->assertSet('deletingId', $helpContent->id);
    }

    public function test_can_delete_help_content(): void
    {
        $this->mockHelpContentService();

        $helpContent = HelpContent::factory()->create();

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('confirmDelete', $helpContent->id)
            ->call('delete')
            ->assertSet('showDeleteModal', false)
            ->assertSessionHas('message', 'Help content deleted successfully!');

        $this->assertDatabaseMissing('help_contents', ['id' => $helpContent->id]);
    }

    public function test_delete_dispatches_event(): void
    {
        $this->mockHelpContentService();

        $helpContent = HelpContent::factory()->create();

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('confirmDelete', $helpContent->id)
            ->call('delete')
            ->assertDispatched('help-content-deleted');
    }

    public function test_delete_does_nothing_without_id(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('delete')
            ->assertSet('showDeleteModal', false);
    }

    // ==================== TOGGLE ACTIVE TESTS ====================

    public function test_can_toggle_active_status(): void
    {
        $this->mockHelpContentService();

        $helpContent = HelpContent::factory()->create(['is_active' => true]);

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('toggleActive', $helpContent->id)
            ->assertSessionHas('message', 'Help content status updated!');

        $helpContent->refresh();
        $this->assertFalse($helpContent->is_active);
    }

    public function test_toggle_active_from_inactive(): void
    {
        $this->mockHelpContentService();

        $helpContent = HelpContent::factory()->inactive()->create();

        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('toggleActive', $helpContent->id);

        $helpContent->refresh();
        $this->assertTrue($helpContent->is_active);
    }

    // ==================== DETAILS MANAGEMENT TESTS ====================

    public function test_can_add_detail(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openCreateModal')
            ->set('newDetailKey', 'Step 1')
            ->set('newDetailValue', 'First step instruction')
            ->call('addDetail')
            ->assertSet('details', ['Step 1' => 'First step instruction'])
            ->assertSet('newDetailKey', '')
            ->assertSet('newDetailValue', '');
    }

    public function test_add_detail_requires_both_key_and_value(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openCreateModal')
            ->set('newDetailKey', 'Key only')
            ->call('addDetail')
            ->assertSet('details', []);
    }

    public function test_can_remove_detail(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openCreateModal')
            ->set('details', ['Step 1' => 'First', 'Step 2' => 'Second'])
            ->call('removeDetail', 'Step 1')
            ->assertSet('details', ['Step 2' => 'Second']);
    }

    public function test_can_add_arabic_detail(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openCreateModal')
            ->set('newDetailKeyAr', 'الخطوة 1')
            ->set('newDetailValueAr', 'تعليمات الخطوة الأولى')
            ->call('addDetailAr')
            ->assertSet('ar_details', ['الخطوة 1' => 'تعليمات الخطوة الأولى'])
            ->assertSet('newDetailKeyAr', '')
            ->assertSet('newDetailValueAr', '');
    }

    public function test_can_remove_arabic_detail(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openCreateModal')
            ->set('ar_details', ['الخطوة 1' => 'أول', 'الخطوة 2' => 'ثاني'])
            ->call('removeDetailAr', 'الخطوة 1')
            ->assertSet('ar_details', ['الخطوة 2' => 'ثاني']);
    }

    // ==================== STATS TESTS ====================

    public function test_displays_stats(): void
    {
        HelpContent::factory()->count(5)->create(['is_active' => true]);
        HelpContent::factory()->inactive()->count(2)->create();

        $component = Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class);

        $stats = $component->viewData('stats');
        $this->assertEquals(7, $stats['total']);
        $this->assertEquals(5, $stats['active']);
    }

    public function test_stats_includes_most_viewed(): void
    {
        HelpContent::factory()->create(['view_count' => 100]);
        $mostViewed = HelpContent::factory()->create(['view_count' => 500]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class);

        $stats = $component->viewData('stats');
        $this->assertNotNull($stats['most_viewed']);
        $this->assertEquals($mostViewed->id, $stats['most_viewed']->id);
    }

    public function test_stats_includes_most_helpful(): void
    {
        HelpContent::factory()->create(['helpful_count' => 10, 'not_helpful_count' => 5]);
        $mostHelpful = HelpContent::factory()->create(['helpful_count' => 100, 'not_helpful_count' => 5]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class);

        $stats = $component->viewData('stats');
        $this->assertNotNull($stats['most_helpful']);
        $this->assertEquals($mostHelpful->id, $stats['most_helpful']->id);
    }

    // ==================== CATEGORIES COMPUTED TESTS ====================

    public function test_categories_returns_distinct_categories(): void
    {
        HelpContent::factory()->category('deployment')->count(3)->create();
        HelpContent::factory()->category('servers')->count(2)->create();
        HelpContent::factory()->category('docker')->create();

        $component = Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class);

        $categories = $component->viewData('categories');
        $this->assertCount(3, $categories);
        $this->assertTrue($categories->contains('deployment'));
        $this->assertTrue($categories->contains('servers'));
        $this->assertTrue($categories->contains('docker'));
    }

    // ==================== CLEAR CACHE TESTS ====================

    public function test_can_clear_cache(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('clearCache')
            ->assertSessionHas('message', 'Cache cleared successfully!');
    }

    // ==================== DEFAULT VALUES TESTS ====================

    public function test_default_filter_values(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->assertSet('search', '')
            ->assertSet('categoryFilter', 'all')
            ->assertSet('statusFilter', 'all')
            ->assertSet('sortField', 'created_at')
            ->assertSet('sortDirection', 'desc');
    }

    public function test_default_form_values(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class)
            ->call('openCreateModal')
            ->assertSet('ui_element_type', 'tooltip')
            ->assertSet('icon', 'info-circle')
            ->assertSet('is_active', true)
            ->assertSet('details', [])
            ->assertSet('ar_details', []);
    }

    // ==================== UI ELEMENT TYPE TESTS ====================

    public function test_valid_ui_element_types(): void
    {
        $this->mockHelpContentService();

        $validTypes = ['tooltip', 'popover', 'modal', 'sidebar', 'inline'];

        foreach ($validTypes as $type) {
            Livewire::actingAs($this->adminUser)
                ->test(HelpContentManager::class)
                ->call('openCreateModal')
                ->set('key', 'test-key-' . $type)
                ->set('category', 'test')
                ->set('ui_element_type', $type)
                ->set('icon', 'info')
                ->set('title', 'Test')
                ->set('brief', 'Brief description')
                ->call('save')
                ->assertHasNoErrors(['ui_element_type']);
        }
    }

    // ==================== INTERACTIONS COUNT TESTS ====================

    public function test_includes_interactions_count(): void
    {
        $helpContent = HelpContent::factory()->create();

        $component = Livewire::actingAs($this->adminUser)
            ->test(HelpContentManager::class);

        $helpContents = $component->viewData('helpContents');
        $this->assertTrue(isset($helpContents->first()->interactions_count));
    }
}
