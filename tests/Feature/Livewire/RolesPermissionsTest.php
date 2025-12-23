<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\RolesPermissions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesPermissionsTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create();

        // Create some permissions for testing
        Permission::create(['name' => 'view-projects', 'guard_name' => 'web']);
        Permission::create(['name' => 'create-projects', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit-projects', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete-projects', 'guard_name' => 'web']);
        Permission::create(['name' => 'view-users', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage-users', 'guard_name' => 'web']);
        Permission::create(['name' => 'view-settings', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage-settings', 'guard_name' => 'web']);
    }

    // ============================================================
    // Component Rendering Tests
    // ============================================================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->assertStatus(200);
    }

    public function test_component_displays_view(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->assertViewIs('livewire.settings.roles-permissions');
    }

    public function test_component_displays_roles_list(): void
    {
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'Editor', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->assertSee('Admin')
            ->assertSee('Editor');
    }

    public function test_component_shows_user_count_for_roles(): void
    {
        $role = Role::create(['name' => 'TestRole', 'guard_name' => 'web']);
        $this->admin->assignRole($role);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->assertSee('TestRole');
    }

    // ============================================================
    // Create Role Modal Tests
    // ============================================================

    public function test_create_modal_is_closed_by_default(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->assertSet('showCreateModal', false);
    }

    public function test_can_open_create_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('createRole')
            ->assertSet('showCreateModal', true);
    }

    public function test_can_close_create_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('createRole')
            ->assertSet('showCreateModal', true)
            ->call('closeCreateModal')
            ->assertSet('showCreateModal', false);
    }

    public function test_closing_create_modal_resets_form(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('createRole')
            ->set('roleName', 'Test Role')
            ->set('selectedPermissions', ['view-projects'])
            ->call('closeCreateModal')
            ->assertSet('roleName', '')
            ->assertSet('selectedPermissions', []);
    }

    // ============================================================
    // Create Role Tests
    // ============================================================

    public function test_can_create_role(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('createRole')
            ->set('roleName', 'New Role')
            ->call('saveRole')
            ->assertSet('showCreateModal', false);

        $this->assertDatabaseHas('roles', [
            'name' => 'New Role',
            'guard_name' => 'web',
        ]);
    }

    public function test_can_create_role_with_permissions(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('createRole')
            ->set('roleName', 'Editor Role')
            ->set('selectedPermissions', ['view-projects', 'edit-projects'])
            ->call('saveRole');

        $role = Role::where('name', 'Editor Role')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('view-projects', 'web'));
        $this->assertTrue($role->hasPermissionTo('edit-projects', 'web'));
        $this->assertFalse($role->hasPermissionTo('delete-projects', 'web'));
    }

    public function test_role_name_is_required(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('createRole')
            ->set('roleName', '')
            ->call('saveRole')
            ->assertHasErrors(['roleName' => 'required']);
    }

    public function test_role_name_max_length_validation(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('createRole')
            ->set('roleName', str_repeat('a', 256))
            ->call('saveRole')
            ->assertHasErrors(['roleName' => 'max']);
    }

    public function test_role_name_must_be_unique(): void
    {
        Role::create(['name' => 'Existing Role', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('createRole')
            ->set('roleName', 'Existing Role')
            ->call('saveRole')
            ->assertHasErrors(['roleName' => 'unique']);
    }

    public function test_create_role_flashes_success_message(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('createRole')
            ->set('roleName', 'Flash Test Role')
            ->call('saveRole')
            ->assertSessionHas('message', 'Role created successfully!');
    }

    // ============================================================
    // Edit Role Modal Tests
    // ============================================================

    public function test_edit_modal_is_closed_by_default(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->assertSet('showEditModal', false);
    }

    public function test_can_open_edit_modal(): void
    {
        $role = Role::create(['name' => 'Edit Test', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('editRole', $role->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingRoleId', $role->id)
            ->assertSet('roleName', 'Edit Test');
    }

    public function test_edit_modal_loads_role_permissions(): void
    {
        $role = Role::create(['name' => 'Permission Test', 'guard_name' => 'web']);
        $role->givePermissionTo(['view-projects', 'edit-projects']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('editRole', $role->id)
            ->assertSet('selectedPermissions', ['view-projects', 'edit-projects']);
    }

    public function test_can_close_edit_modal(): void
    {
        $role = Role::create(['name' => 'Close Test', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('editRole', $role->id)
            ->call('closeEditModal')
            ->assertSet('showEditModal', false)
            ->assertSet('editingRoleId', null);
    }

    // ============================================================
    // Update Role Tests
    // ============================================================

    public function test_can_update_role_name(): void
    {
        $role = Role::create(['name' => 'Old Name', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('editRole', $role->id)
            ->set('roleName', 'New Name')
            ->call('updateRole');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'New Name',
        ]);
    }

    public function test_can_update_role_permissions(): void
    {
        $role = Role::create(['name' => 'Update Perm Test', 'guard_name' => 'web']);
        $role->givePermissionTo(['view-projects']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('editRole', $role->id)
            ->set('selectedPermissions', ['view-projects', 'create-projects', 'edit-projects'])
            ->call('updateRole');

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('view-projects', 'web'));
        $this->assertTrue($role->hasPermissionTo('create-projects', 'web'));
        $this->assertTrue($role->hasPermissionTo('edit-projects', 'web'));
    }

    public function test_can_remove_permissions_from_role(): void
    {
        $role = Role::create(['name' => 'Remove Perm Test', 'guard_name' => 'web']);
        $role->givePermissionTo(['view-projects', 'edit-projects', 'delete-projects']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('editRole', $role->id)
            ->set('selectedPermissions', ['view-projects'])
            ->call('updateRole');

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('view-projects', 'web'));
        $this->assertFalse($role->hasPermissionTo('edit-projects', 'web'));
        $this->assertFalse($role->hasPermissionTo('delete-projects', 'web'));
    }

    public function test_update_role_validates_unique_name(): void
    {
        $role1 = Role::create(['name' => 'Role One', 'guard_name' => 'web']);
        $role2 = Role::create(['name' => 'Role Two', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('editRole', $role2->id)
            ->set('roleName', 'Role One')
            ->call('updateRole')
            ->assertHasErrors(['roleName' => 'unique']);
    }

    public function test_update_role_allows_same_name(): void
    {
        $role = Role::create(['name' => 'Same Name', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('editRole', $role->id)
            ->set('roleName', 'Same Name')
            ->call('updateRole')
            ->assertHasNoErrors(['roleName']);
    }

    public function test_update_role_flashes_success_message(): void
    {
        $role = Role::create(['name' => 'Flash Update Test', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('editRole', $role->id)
            ->set('roleName', 'Updated Flash Test')
            ->call('updateRole')
            ->assertSessionHas('message', 'Role updated successfully!');
    }

    // ============================================================
    // Delete Role Tests
    // ============================================================

    public function test_delete_modal_is_closed_by_default(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->assertSet('showDeleteModal', false);
    }

    public function test_can_open_delete_confirmation_modal(): void
    {
        $role = Role::create(['name' => 'Delete Test', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('confirmDelete', $role->id)
            ->assertSet('showDeleteModal', true)
            ->assertSet('deletingRoleId', $role->id);
    }

    public function test_can_close_delete_modal(): void
    {
        $role = Role::create(['name' => 'Close Delete Test', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('confirmDelete', $role->id)
            ->call('closeDeleteModal')
            ->assertSet('showDeleteModal', false)
            ->assertSet('deletingRoleId', null);
    }

    public function test_can_delete_role_without_users(): void
    {
        $role = Role::create(['name' => 'Deletable Role', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('confirmDelete', $role->id)
            ->call('deleteRole');

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_cannot_delete_role_with_assigned_users(): void
    {
        $role = Role::create(['name' => 'Assigned Role', 'guard_name' => 'web']);
        $this->admin->assignRole($role);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('confirmDelete', $role->id)
            ->call('deleteRole')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Assigned Role',
        ]);
    }

    public function test_delete_role_without_id_does_nothing(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->set('deletingRoleId', null)
            ->call('deleteRole')
            ->assertStatus(200);
    }

    public function test_delete_role_flashes_success_message(): void
    {
        $role = Role::create(['name' => 'Delete Flash Test', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('confirmDelete', $role->id)
            ->call('deleteRole')
            ->assertSessionHas('message', 'Role deleted successfully!');
    }

    // ============================================================
    // Manage Permissions Modal Tests
    // ============================================================

    public function test_permissions_modal_is_closed_by_default(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->assertSet('showPermissionsModal', false);
    }

    public function test_can_open_permissions_modal(): void
    {
        $role = Role::create(['name' => 'Perm Modal Test', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('managePermissions', $role->id)
            ->assertSet('showPermissionsModal', true)
            ->assertSet('managingPermissionsRoleId', $role->id);
    }

    public function test_permissions_modal_loads_current_permissions(): void
    {
        $role = Role::create(['name' => 'Load Perm Test', 'guard_name' => 'web']);
        $role->givePermissionTo(['view-users', 'manage-users']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('managePermissions', $role->id)
            ->assertSet('selectedPermissions', ['view-users', 'manage-users']);
    }

    public function test_can_close_permissions_modal(): void
    {
        $role = Role::create(['name' => 'Close Perm Test', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('managePermissions', $role->id)
            ->call('closePermissionsModal')
            ->assertSet('showPermissionsModal', false)
            ->assertSet('managingPermissionsRoleId', null)
            ->assertSet('selectedPermissions', []);
    }

    public function test_can_update_permissions(): void
    {
        $role = Role::create(['name' => 'Update Perm Modal Test', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('managePermissions', $role->id)
            ->set('selectedPermissions', ['view-settings', 'manage-settings'])
            ->call('updatePermissions');

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('view-settings', 'web'));
        $this->assertTrue($role->hasPermissionTo('manage-settings', 'web'));
    }

    public function test_update_permissions_without_role_id_does_nothing(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->set('managingPermissionsRoleId', null)
            ->call('updatePermissions')
            ->assertStatus(200);
    }

    public function test_update_permissions_flashes_success_message(): void
    {
        $role = Role::create(['name' => 'Perm Flash Test', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('managePermissions', $role->id)
            ->set('selectedPermissions', ['view-projects'])
            ->call('updatePermissions')
            ->assertSessionHas('message', 'Permissions updated successfully!');
    }

    // ============================================================
    // Search Tests
    // ============================================================

    public function test_can_search_roles(): void
    {
        Role::create(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::create(['name' => 'Editor', 'guard_name' => 'web']);
        Role::create(['name' => 'Viewer', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->set('search', 'Admin')
            ->assertSee('Administrator')
            ->assertDontSee('Editor')
            ->assertDontSee('Viewer');
    }

    public function test_search_is_case_insensitive(): void
    {
        Role::create(['name' => 'Administrator', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->set('search', 'admin')
            ->assertSee('Administrator');
    }

    public function test_search_resets_pagination(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->set('search', 'test')
            ->assertSet('page', 1);
    }

    public function test_can_clear_filters(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->set('search', 'test search')
            ->call('clearFilters')
            ->assertSet('search', '');
    }

    public function test_clear_filters_resets_pagination(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->set('search', 'test')
            ->call('clearFilters')
            ->assertSet('page', 1);
    }

    // ============================================================
    // Grouped Permissions Tests
    // ============================================================

    public function test_grouped_permissions_returns_array(): void
    {
        $component = new RolesPermissions();
        $grouped = $component->getGroupedPermissions();

        $this->assertNotEmpty($grouped);
    }

    public function test_grouped_permissions_groups_by_category(): void
    {
        $component = new RolesPermissions();
        $grouped = $component->getGroupedPermissions();

        $this->assertArrayHasKey('projects', $grouped);
        $this->assertArrayHasKey('users', $grouped);
        $this->assertArrayHasKey('settings', $grouped);
    }

    public function test_grouped_permissions_are_sorted_alphabetically(): void
    {
        $component = new RolesPermissions();
        $grouped = $component->getGroupedPermissions();

        $keys = array_keys($grouped);
        $sortedKeys = $keys;
        sort($sortedKeys);

        $this->assertEquals($sortedKeys, $keys);
    }

    // ============================================================
    // Pagination Tests
    // ============================================================

    public function test_pagination_works(): void
    {
        // Create 20 roles to trigger pagination (default 15 per page)
        for ($i = 1; $i <= 20; $i++) {
            Role::create(['name' => "Role {$i}", 'guard_name' => 'web']);
        }

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->assertSet('page', 1);
    }

    // ============================================================
    // Form Reset Tests
    // ============================================================

    public function test_reset_form_clears_all_fields(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->set('roleName', 'Test Role')
            ->set('roleGuardName', 'api')
            ->set('selectedPermissions', ['view-projects', 'edit-projects'])
            ->set('editingRoleId', 1)
            ->call('resetForm')
            ->assertSet('roleName', '')
            ->assertSet('roleGuardName', 'web')
            ->assertSet('selectedPermissions', [])
            ->assertSet('editingRoleId', null);
    }

    // ============================================================
    // Query String Tests
    // ============================================================

    public function test_search_is_in_query_string(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->set('search', 'admin')
            ->assertSet('search', 'admin');
    }

    // ============================================================
    // Guard Name Tests
    // ============================================================

    public function test_default_guard_name_is_web(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->assertSet('roleGuardName', 'web');
    }

    public function test_role_is_created_with_guard_name(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('createRole')
            ->set('roleName', 'API Role')
            ->set('roleGuardName', 'api')
            ->call('saveRole');

        $this->assertDatabaseHas('roles', [
            'name' => 'API Role',
            'guard_name' => 'api',
        ]);
    }

    // ============================================================
    // Edge Cases
    // ============================================================

    public function test_handles_empty_roles_list(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->assertStatus(200);
    }

    public function test_handles_role_with_no_permissions(): void
    {
        $role = Role::create(['name' => 'No Permissions', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('editRole', $role->id)
            ->assertSet('selectedPermissions', []);
    }

    public function test_create_role_with_all_permissions(): void
    {
        $allPermissions = Permission::pluck('name')->toArray();

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('createRole')
            ->set('roleName', 'Super Admin')
            ->set('selectedPermissions', $allPermissions)
            ->call('saveRole');

        $role = Role::where('name', 'Super Admin')->first();
        $this->assertNotNull($role);
        $this->assertCount(count($allPermissions), $role->permissions);
    }

    public function test_roles_ordered_by_name(): void
    {
        Role::create(['name' => 'Zebra', 'guard_name' => 'web']);
        Role::create(['name' => 'Alpha', 'guard_name' => 'web']);
        Role::create(['name' => 'Beta', 'guard_name' => 'web']);

        $roles = Role::orderBy('name')->pluck('name')->toArray();
        $this->assertEquals(['Alpha', 'Beta', 'Zebra'], $roles);
    }

    public function test_edit_nonexistent_role_throws_exception(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->call('editRole', 99999);
    }

    public function test_delete_nonexistent_role_throws_exception(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->admin)
            ->test(RolesPermissions::class)
            ->set('deletingRoleId', 99999)
            ->call('deleteRole');
    }
}
