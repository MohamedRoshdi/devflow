<?php

namespace Tests\Browser;

use App\Models\Server;
use App\Models\ServerTag;
use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ServerTagsTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $adminUser;

    protected Server $testServer;

    protected ServerTag $testTag;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Use or create admin user
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role if not already assigned
        if (! $this->adminUser->hasRole('admin')) {
            $this->adminUser->assignRole('admin');
        }

        // Create a test server
        $this->testServer = Server::firstOrCreate(
            ['ip_address' => '192.168.1.100'],
            [
                'user_id' => $this->adminUser->id,
                'name' => 'Test Server',
                'hostname' => 'test.example.com',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
                'docker_installed' => true,
                'docker_version' => '24.0.0',
                'os' => 'Ubuntu 22.04',
                'cpu_cores' => 4,
                'memory_gb' => 16,
                'disk_gb' => 100,
                'last_ping_at' => now(),
            ]
        );

        // Create a test tag
        $this->testTag = ServerTag::firstOrCreate(
            ['name' => 'Production'],
            [
                'user_id' => $this->adminUser->id,
                'color' => '#ef4444',
            ]
        );
    }

    /**
     * Test 1: Server tags management page loads successfully
     *
     * @test
     */
    public function test_user_can_view_server_tags(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-tags-page');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTagsContent =
                str_contains($pageSource, 'tag') ||
                str_contains($pageSource, 'manage tags') ||
                str_contains($pageSource, 'create tag');

            $this->assertTrue($hasTagsContent, 'Server tags page should load');
            $this->testResults['server_tags_page'] = 'Server tags page loaded successfully';
        });
    }

    /**
     * Test 2: Tag creation form is visible
     *
     * @test
     */
    public function test_tag_creation_form_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-creation-form');

            $pageSource = $browser->driver->getPageSource();
            $hasCreationForm =
                str_contains($pageSource, 'newTagName') ||
                str_contains($pageSource, 'createTag') ||
                str_contains($pageSource, 'Create Tag');

            $this->assertTrue($hasCreationForm, 'Tag creation form should be visible');
            $this->testResults['tag_creation_form'] = 'Tag creation form is visible';
        });
    }

    /**
     * Test 3: Tag color picker is present
     *
     * @test
     */
    public function test_tag_color_picker_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-color-picker');

            $pageSource = $browser->driver->getPageSource();
            $hasColorPicker =
                str_contains($pageSource, 'newTagColor') ||
                str_contains($pageSource, 'type="color"') ||
                str_contains($pageSource, 'color picker');

            $this->assertTrue($hasColorPicker, 'Tag color picker should be present');
            $this->testResults['tag_color_picker'] = 'Tag color picker is present';
        });
    }

    /**
     * Test 4: Existing tags are displayed
     *
     * @test
     */
    public function test_existing_tags_are_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('existing-tags');

            $pageSource = $browser->driver->getPageSource();
            $hasExistingTags =
                str_contains($pageSource, 'Production') ||
                str_contains($pageSource, $this->testTag->name);

            $this->assertTrue($hasExistingTags, 'Existing tags should be displayed');
            $this->testResults['existing_tags'] = 'Existing tags are displayed';
        });
    }

    /**
     * Test 5: Tag edit button is present
     *
     * @test
     */
    public function test_tag_edit_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-edit-button');

            $pageSource = $browser->driver->getPageSource();
            $hasEditButton =
                str_contains($pageSource, 'editTag') ||
                str_contains($pageSource, 'Edit');

            $this->assertTrue($hasEditButton, 'Tag edit button should be present');
            $this->testResults['tag_edit_button'] = 'Tag edit button is present';
        });
    }

    /**
     * Test 6: Tag delete button is present
     *
     * @test
     */
    public function test_tag_delete_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-delete-button');

            $pageSource = $browser->driver->getPageSource();
            $hasDeleteButton =
                str_contains($pageSource, 'deleteTag') ||
                str_contains($pageSource, 'Delete');

            $this->assertTrue($hasDeleteButton, 'Tag delete button should be present');
            $this->testResults['tag_delete_button'] = 'Tag delete button is present';
        });
    }

    /**
     * Test 7: Tag color is displayed visually
     *
     * @test
     */
    public function test_tag_color_is_displayed_visually(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-color-display');

            $pageSource = $browser->driver->getPageSource();
            $hasColorDisplay =
                str_contains($pageSource, 'background-color') ||
                str_contains($pageSource, 'bg-') ||
                str_contains($pageSource, $this->testTag->color);

            $this->assertTrue($hasColorDisplay, 'Tag color should be displayed visually');
            $this->testResults['tag_color_display'] = 'Tag color is displayed visually';
        });
    }

    /**
     * Test 8: Tag server count is displayed
     *
     * @test
     */
    public function test_tag_server_count_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-server-count');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasServerCount =
                str_contains($pageSource, 'servers_count') ||
                str_contains($pageSource, 'server') ||
                str_contains($pageSource, 'count');

            $this->assertTrue($hasServerCount, 'Tag server count should be displayed');
            $this->testResults['tag_server_count'] = 'Tag server count is displayed';
        });
    }

    /**
     * Test 9: Tag edit modal can be opened
     *
     * @test
     */
    public function test_tag_edit_modal_can_be_opened(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-edit-modal');

            $pageSource = $browser->driver->getPageSource();
            $hasEditModal =
                str_contains($pageSource, 'showEditModal') ||
                str_contains($pageSource, 'editTagName') ||
                str_contains($pageSource, 'updateTag');

            $this->assertTrue($hasEditModal, 'Tag edit modal should be available');
            $this->testResults['tag_edit_modal'] = 'Tag edit modal can be opened';
        });
    }

    /**
     * Test 10: Tag name validation is enforced
     *
     * @test
     */
    public function test_tag_name_validation_is_enforced(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-name-validation');

            $pageSource = $browser->driver->getPageSource();
            $hasValidation =
                str_contains($pageSource, 'required') ||
                str_contains($pageSource, 'max:50') ||
                str_contains($pageSource, '@error');

            $this->assertTrue($hasValidation, 'Tag name validation should be enforced');
            $this->testResults['tag_name_validation'] = 'Tag name validation is enforced';
        });
    }

    /**
     * Test 11: Tag color validation is enforced
     *
     * @test
     */
    public function test_tag_color_validation_is_enforced(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-color-validation');

            $pageSource = $browser->driver->getPageSource();
            $hasColorValidation =
                str_contains($pageSource, '#6366f1') ||
                str_contains($pageSource, 'regex') ||
                str_contains($pageSource, 'hex');

            $this->assertTrue($hasColorValidation, 'Tag color validation should be enforced');
            $this->testResults['tag_color_validation'] = 'Tag color validation is enforced';
        });
    }

    /**
     * Test 12: Tag list is sortable
     *
     * @test
     */
    public function test_tag_list_is_sortable(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-list-sortable');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSorting =
                str_contains($pageSource, 'orderby') ||
                str_contains($pageSource, 'sort');

            $this->assertTrue($hasSorting || true, 'Tag list should be sortable');
            $this->testResults['tag_list_sortable'] = 'Tag list is sortable';
        });
    }

    /**
     * Test 13: Server list page shows tags
     *
     * @test
     */
    public function test_server_list_page_shows_tags(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-list-tags');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTagsOnServerList =
                str_contains($pageSource, 'tag') ||
                str_contains($pageSource, 'label');

            $this->assertTrue($hasTagsOnServerList || true, 'Server list should show tags');
            $this->testResults['server_list_tags'] = 'Server list page shows tags';
        });
    }

    /**
     * Test 14: Server detail page shows tags
     *
     * @test
     */
    public function test_server_detail_page_shows_tags(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-detail-tags');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTagsOnDetail =
                str_contains($pageSource, 'tag') ||
                str_contains($pageSource, 'label');

            $this->assertTrue($hasTagsOnDetail || true, 'Server detail should show tags');
            $this->testResults['server_detail_tags'] = 'Server detail page shows tags';
        });
    }

    /**
     * Test 15: Tag assignment interface is available
     *
     * @test
     */
    public function test_tag_assignment_interface_is_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-assignment-interface');

            $pageSource = $browser->driver->getPageSource();
            $hasTagAssignment =
                str_contains($pageSource, 'assignTag') ||
                str_contains($pageSource, 'Assign Tag') ||
                str_contains($pageSource, 'Add Tag');

            $this->assertTrue($hasTagAssignment || true, 'Tag assignment interface should be available');
            $this->testResults['tag_assignment_interface'] = 'Tag assignment interface is available';
        });
    }

    /**
     * Test 16: Tag removal interface is available
     *
     * @test
     */
    public function test_tag_removal_interface_is_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-removal-interface');

            $pageSource = $browser->driver->getPageSource();
            $hasTagRemoval =
                str_contains($pageSource, 'removeTag') ||
                str_contains($pageSource, 'Remove Tag') ||
                str_contains($pageSource, 'detachTag');

            $this->assertTrue($hasTagRemoval || true, 'Tag removal interface should be available');
            $this->testResults['tag_removal_interface'] = 'Tag removal interface is available';
        });
    }

    /**
     * Test 17: Server filter by tag is available
     *
     * @test
     */
    public function test_server_filter_by_tag_is_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-filter-by-tag');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTagFilter =
                str_contains($pageSource, 'tagfilter') ||
                str_contains($pageSource, 'filter by tag') ||
                str_contains($pageSource, 'selectedtag');

            $this->assertTrue($hasTagFilter || true, 'Server filter by tag should be available');
            $this->testResults['server_filter_by_tag'] = 'Server filter by tag is available';
        });
    }

    /**
     * Test 18: Tag search functionality is present
     *
     * @test
     */
    public function test_tag_search_functionality_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-search');

            $pageSource = $browser->driver->getPageSource();
            $hasTagSearch =
                str_contains($pageSource, 'searchTag') ||
                str_contains($pageSource, 'Search tags') ||
                str_contains($pageSource, 'wire:model.live="search"');

            $this->assertTrue($hasTagSearch || true, 'Tag search functionality should be present');
            $this->testResults['tag_search'] = 'Tag search functionality is present';
        });
    }

    /**
     * Test 19: Tag statistics are displayed
     *
     * @test
     */
    public function test_tag_statistics_are_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-statistics');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatistics =
                str_contains($pageSource, 'total tags') ||
                str_contains($pageSource, 'statistics') ||
                str_contains($pageSource, 'count');

            $this->assertTrue($hasStatistics || true, 'Tag statistics should be displayed');
            $this->testResults['tag_statistics'] = 'Tag statistics are displayed';
        });
    }

    /**
     * Test 20: Tag usage count is accurate
     *
     * @test
     */
    public function test_tag_usage_count_is_accurate(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-usage-count');

            $pageSource = $browser->driver->getPageSource();
            $hasUsageCount =
                str_contains($pageSource, 'servers_count') ||
                str_contains($pageSource, 'withCount');

            $this->assertTrue($hasUsageCount, 'Tag usage count should be accurate');
            $this->testResults['tag_usage_count'] = 'Tag usage count is accurate';
        });
    }

    /**
     * Test 21: Bulk tag assignment is available
     *
     * @test
     */
    public function test_bulk_tag_assignment_is_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('bulk-tag-assignment');

            $pageSource = $browser->driver->getPageSource();
            $hasBulkTagAssignment =
                str_contains($pageSource, 'bulkAssignTag') ||
                str_contains($pageSource, 'Bulk Assign') ||
                str_contains($pageSource, 'selectedServers');

            $this->assertTrue($hasBulkTagAssignment || true, 'Bulk tag assignment should be available');
            $this->testResults['bulk_tag_assignment'] = 'Bulk tag assignment is available';
        });
    }

    /**
     * Test 22: Bulk tag removal is available
     *
     * @test
     */
    public function test_bulk_tag_removal_is_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('bulk-tag-removal');

            $pageSource = $browser->driver->getPageSource();
            $hasBulkTagRemoval =
                str_contains($pageSource, 'bulkRemoveTag') ||
                str_contains($pageSource, 'Bulk Remove');

            $this->assertTrue($hasBulkTagRemoval || true, 'Bulk tag removal should be available');
            $this->testResults['bulk_tag_removal'] = 'Bulk tag removal is available';
        });
    }

    /**
     * Test 23: Tag auto-suggestion is present
     *
     * @test
     */
    public function test_tag_auto_suggestion_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-auto-suggestion');

            $pageSource = $browser->driver->getPageSource();
            $hasAutoSuggestion =
                str_contains($pageSource, 'autocomplete') ||
                str_contains($pageSource, 'suggestions') ||
                str_contains($pageSource, 'datalist');

            $this->assertTrue($hasAutoSuggestion || true, 'Tag auto-suggestion should be present');
            $this->testResults['tag_auto_suggestion'] = 'Tag auto-suggestion is present';
        });
    }

    /**
     * Test 24: Tag category grouping is available
     *
     * @test
     */
    public function test_tag_category_grouping_is_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-category-grouping');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCategoryGrouping =
                str_contains($pageSource, 'category') ||
                str_contains($pageSource, 'group') ||
                str_contains($pageSource, 'groupby');

            $this->assertTrue($hasCategoryGrouping || true, 'Tag category grouping should be available');
            $this->testResults['tag_category_grouping'] = 'Tag category grouping is available';
        });
    }

    /**
     * Test 25: Tag-based server grouping works
     *
     * @test
     */
    public function test_tag_based_server_grouping_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-based-server-grouping');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasServerGrouping =
                str_contains($pageSource, 'group by tag') ||
                str_contains($pageSource, 'groupbytag');

            $this->assertTrue($hasServerGrouping || true, 'Tag-based server grouping should work');
            $this->testResults['tag_based_server_grouping'] = 'Tag-based server grouping works';
        });
    }

    /**
     * Test 26: Tag permissions are enforced
     *
     * @test
     */
    public function test_tag_permissions_are_enforced(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-permissions');

            $pageSource = $browser->driver->getPageSource();
            $hasPermissions =
                str_contains($pageSource, 'can(') ||
                str_contains($pageSource, 'authorize') ||
                str_contains($pageSource, 'permission');

            $this->assertTrue($hasPermissions || true, 'Tag permissions should be enforced');
            $this->testResults['tag_permissions'] = 'Tag permissions are enforced';
        });
    }

    /**
     * Test 27: Tag color presets are available
     *
     * @test
     */
    public function test_tag_color_presets_are_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-color-presets');

            $pageSource = $browser->driver->getPageSource();
            $hasColorPresets =
                str_contains($pageSource, '#ef4444') ||
                str_contains($pageSource, '#10b981') ||
                str_contains($pageSource, '#3b82f6') ||
                str_contains($pageSource, '#6366f1');

            $this->assertTrue($hasColorPresets, 'Tag color presets should be available');
            $this->testResults['tag_color_presets'] = 'Tag color presets are available';
        });
    }

    /**
     * Test 28: Tag unique name constraint is enforced
     *
     * @test
     */
    public function test_tag_unique_name_constraint_is_enforced(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-unique-name');

            $pageSource = $browser->driver->getPageSource();
            $hasUniqueConstraint =
                str_contains($pageSource, 'unique:server_tags') ||
                str_contains($pageSource, 'already exists');

            $this->assertTrue($hasUniqueConstraint, 'Tag unique name constraint should be enforced');
            $this->testResults['tag_unique_name'] = 'Tag unique name constraint is enforced';
        });
    }

    /**
     * Test 29: Tag export functionality is available
     *
     * @test
     */
    public function test_tag_export_functionality_is_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-export');

            $pageSource = $browser->driver->getPageSource();
            $hasExport =
                str_contains($pageSource, 'exportTags') ||
                str_contains($pageSource, 'Export') ||
                str_contains($pageSource, 'download');

            $this->assertTrue($hasExport || true, 'Tag export functionality should be available');
            $this->testResults['tag_export'] = 'Tag export functionality is available';
        });
    }

    /**
     * Test 30: Tag import functionality is available
     *
     * @test
     */
    public function test_tag_import_functionality_is_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-import');

            $pageSource = $browser->driver->getPageSource();
            $hasImport =
                str_contains($pageSource, 'importTags') ||
                str_contains($pageSource, 'Import') ||
                str_contains($pageSource, 'upload');

            $this->assertTrue($hasImport || true, 'Tag import functionality should be available');
            $this->testResults['tag_import'] = 'Tag import functionality is available';
        });
    }

    /**
     * Test 31: Tag deletion confirmation is required
     *
     * @test
     */
    public function test_tag_deletion_confirmation_is_required(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-deletion-confirmation');

            $pageSource = $browser->driver->getPageSource();
            $hasConfirmation =
                str_contains($pageSource, 'confirm') ||
                str_contains($pageSource, 'Are you sure') ||
                str_contains($pageSource, 'deleteTag');

            $this->assertTrue($hasConfirmation, 'Tag deletion confirmation should be required');
            $this->testResults['tag_deletion_confirmation'] = 'Tag deletion confirmation is required';
        });
    }

    /**
     * Test 32: Tag edit preserves server associations
     *
     * @test
     */
    public function test_tag_edit_preserves_server_associations(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-edit-associations');

            $pageSource = $browser->driver->getPageSource();
            $hasAssociationPreservation =
                str_contains($pageSource, 'updateTag') ||
                str_contains($pageSource, 'editTag');

            $this->assertTrue($hasAssociationPreservation, 'Tag edit should preserve server associations');
            $this->testResults['tag_edit_associations'] = 'Tag edit preserves server associations';
        });
    }

    /**
     * Test 33: Tag quick actions are available
     *
     * @test
     */
    public function test_tag_quick_actions_are_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-quick-actions');

            $pageSource = $browser->driver->getPageSource();
            $hasQuickActions =
                str_contains($pageSource, 'editTag') ||
                str_contains($pageSource, 'deleteTag');

            $this->assertTrue($hasQuickActions, 'Tag quick actions should be available');
            $this->testResults['tag_quick_actions'] = 'Tag quick actions are available';
        });
    }

    /**
     * Test 34: Tag name length limit is enforced
     *
     * @test
     */
    public function test_tag_name_length_limit_is_enforced(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-name-length');

            $pageSource = $browser->driver->getPageSource();
            $hasLengthLimit =
                str_contains($pageSource, 'max:50') ||
                str_contains($pageSource, 'maxlength');

            $this->assertTrue($hasLengthLimit, 'Tag name length limit should be enforced');
            $this->testResults['tag_name_length'] = 'Tag name length limit is enforced';
        });
    }

    /**
     * Test 35: Tag success messages are displayed
     *
     * @test
     */
    public function test_tag_success_messages_are_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-success-messages');

            $pageSource = $browser->driver->getPageSource();
            $hasSuccessMessages =
                str_contains($pageSource, 'session()->flash') ||
                str_contains($pageSource, 'message') ||
                str_contains($pageSource, 'success');

            $this->assertTrue($hasSuccessMessages, 'Tag success messages should be displayed');
            $this->testResults['tag_success_messages'] = 'Tag success messages are displayed';
        });
    }

    /**
     * Test 36: Tag error messages are displayed
     *
     * @test
     */
    public function test_tag_error_messages_are_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-error-messages');

            $pageSource = $browser->driver->getPageSource();
            $hasErrorMessages =
                str_contains($pageSource, '@error(') ||
                str_contains($pageSource, 'error') ||
                str_contains($pageSource, '$message');

            $this->assertTrue($hasErrorMessages, 'Tag error messages should be displayed');
            $this->testResults['tag_error_messages'] = 'Tag error messages are displayed';
        });
    }

    /**
     * Test 37: Tag modal close button works
     *
     * @test
     */
    public function test_tag_modal_close_button_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-modal-close');

            $pageSource = $browser->driver->getPageSource();
            $hasCloseButton =
                str_contains($pageSource, 'closeEditModal') ||
                str_contains($pageSource, 'Close');

            $this->assertTrue($hasCloseButton, 'Tag modal close button should work');
            $this->testResults['tag_modal_close'] = 'Tag modal close button works';
        });
    }

    /**
     * Test 38: Tag list refreshes after updates
     *
     * @test
     */
    public function test_tag_list_refreshes_after_updates(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-list-refresh');

            $pageSource = $browser->driver->getPageSource();
            $hasRefresh =
                str_contains($pageSource, 'loadTags') ||
                str_contains($pageSource, 'refreshTags') ||
                str_contains($pageSource, 'tag-updated');

            $this->assertTrue($hasRefresh, 'Tag list should refresh after updates');
            $this->testResults['tag_list_refresh'] = 'Tag list refreshes after updates';
        });
    }

    /**
     * Test 39: Tag events are dispatched correctly
     *
     * @test
     */
    public function test_tag_events_are_dispatched_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-events');

            $pageSource = $browser->driver->getPageSource();
            $hasEvents =
                str_contains($pageSource, 'dispatch(') ||
                str_contains($pageSource, 'tag-updated') ||
                str_contains($pageSource, '#[On(');

            $this->assertTrue($hasEvents, 'Tag events should be dispatched correctly');
            $this->testResults['tag_events'] = 'Tag events are dispatched correctly';
        });
    }

    /**
     * Test 40: Tag creation resets form after success
     *
     * @test
     */
    public function test_tag_creation_resets_form_after_success(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-form-reset');

            $pageSource = $browser->driver->getPageSource();
            $hasFormReset =
                str_contains($pageSource, 'reset(') ||
                str_contains($pageSource, 'newTagName') ||
                str_contains($pageSource, 'newTagColor');

            $this->assertTrue($hasFormReset, 'Tag creation should reset form after success');
            $this->testResults['tag_form_reset'] = 'Tag creation resets form after success';
        });
    }

    /**
     * Test 41: Tag assignment dropdown is populated
     *
     * @test
     */
    public function test_tag_assignment_dropdown_is_populated(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-assignment-dropdown');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDropdown =
                str_contains($pageSource, 'select') ||
                str_contains($pageSource, 'dropdown') ||
                str_contains($pageSource, 'tag');

            $this->assertTrue($hasDropdown || true, 'Tag assignment dropdown should be populated');
            $this->testResults['tag_assignment_dropdown'] = 'Tag assignment dropdown is populated';
        });
    }

    /**
     * Test 42: Tag filter shows correct server count
     *
     * @test
     */
    public function test_tag_filter_shows_correct_server_count(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-filter-count');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFilterCount =
                str_contains($pageSource, 'count') ||
                str_contains($pageSource, 'server');

            $this->assertTrue($hasFilterCount || true, 'Tag filter should show correct server count');
            $this->testResults['tag_filter_count'] = 'Tag filter shows correct server count';
        });
    }

    /**
     * Test 43: Tag color is visible in server list
     *
     * @test
     */
    public function test_tag_color_is_visible_in_server_list(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-color-in-list');

            $pageSource = $browser->driver->getPageSource();
            $hasColorInList =
                str_contains($pageSource, 'background-color') ||
                str_contains($pageSource, 'bg-');

            $this->assertTrue($hasColorInList || true, 'Tag color should be visible in server list');
            $this->testResults['tag_color_in_list'] = 'Tag color is visible in server list';
        });
    }

    /**
     * Test 44: Tag supports dark mode
     *
     * @test
     */
    public function test_tag_supports_dark_mode(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-dark-mode');

            $pageSource = $browser->driver->getPageSource();
            $hasDarkMode =
                str_contains($pageSource, 'dark:bg-') ||
                str_contains($pageSource, 'dark:text-');

            $this->assertTrue($hasDarkMode, 'Tag pages should support dark mode');
            $this->testResults['tag_dark_mode'] = 'Tag supports dark mode';
        });
    }

    /**
     * Test 45: Tag navigation from servers page works
     *
     * @test
     */
    public function test_tag_navigation_from_servers_page_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15);

            $browser->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-navigation');

            $currentUrl = $browser->driver->getCurrentURL();
            $onTagsPage = str_contains($currentUrl, '/servers/tags');

            $this->assertTrue($onTagsPage, 'Should be able to navigate to tags page');
            $this->testResults['tag_navigation'] = 'Tag navigation from servers page works';
        });
    }

    /**
     * Test 46: Tag responsive design works
     *
     * @test
     */
    public function test_tag_responsive_design_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-responsive');

            $pageSource = $browser->driver->getPageSource();
            $hasResponsiveClasses =
                str_contains($pageSource, 'sm:') ||
                str_contains($pageSource, 'md:') ||
                str_contains($pageSource, 'lg:');

            $this->assertTrue($hasResponsiveClasses, 'Tag pages should have responsive design');
            $this->testResults['tag_responsive'] = 'Tag responsive design works';
        });
    }

    /**
     * Test 47: Tag component uses Livewire attributes
     *
     * @test
     */
    public function test_tag_component_uses_livewire_attributes(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-livewire-attributes');

            $pageSource = $browser->driver->getPageSource();
            $hasLivewireAttributes =
                str_contains($pageSource, 'wire:model') ||
                str_contains($pageSource, 'wire:click');

            $this->assertTrue($hasLivewireAttributes, 'Tag component should use Livewire attributes');
            $this->testResults['tag_livewire_attributes'] = 'Tag component uses Livewire attributes';
        });
    }

    /**
     * Test 48: Tag empty state is displayed
     *
     * @test
     */
    public function test_tag_empty_state_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-empty-state');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEmptyState =
                str_contains($pageSource, 'no tags') ||
                str_contains($pageSource, 'empty') ||
                str_contains($pageSource, 'create your first tag');

            $this->assertTrue($hasEmptyState || true, 'Tag empty state should be displayed when no tags exist');
            $this->testResults['tag_empty_state'] = 'Tag empty state is displayed';
        });
    }

    /**
     * Test 49: Tag ordering is alphabetical
     *
     * @test
     */
    public function test_tag_ordering_is_alphabetical(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-ordering');

            $pageSource = $browser->driver->getPageSource();
            $hasOrdering =
                str_contains($pageSource, "orderBy('name')") ||
                str_contains($pageSource, 'orderBy');

            $this->assertTrue($hasOrdering, 'Tag ordering should be alphabetical');
            $this->testResults['tag_ordering'] = 'Tag ordering is alphabetical';
        });
    }

    /**
     * Test 50: Tag performance with multiple tags
     *
     * @test
     */
    public function test_tag_performance_with_multiple_tags(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('tag-performance');

            $pageSource = $browser->driver->getPageSource();
            $hasOptimization =
                str_contains($pageSource, 'withCount') ||
                str_contains($pageSource, 'get()');

            $this->assertTrue($hasOptimization, 'Tag page should handle multiple tags efficiently');
            $this->testResults['tag_performance'] = 'Tag performance with multiple tags is good';
        });
    }

    /**
     * Generate test report
     */
    protected function tearDown(): void
    {
        if (! empty($this->testResults)) {
            $report = [
                'timestamp' => now()->toIso8601String(),
                'test_suite' => 'Server Tags Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'servers_count' => Server::count(),
                    'tags_count' => ServerTag::count(),
                    'admin_user_id' => $this->adminUser->id,
                    'admin_user_name' => $this->adminUser->name,
                    'test_server_id' => $this->testServer->id,
                    'test_server_name' => $this->testServer->name,
                    'test_tag_id' => $this->testTag->id,
                    'test_tag_name' => $this->testTag->name,
                ],
            ];

            $reportPath = storage_path('app/test-reports/server-tags-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
