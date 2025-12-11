<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissions extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public bool $showPermissionsModal = false;

    public ?int $editingRoleId = null;

    public ?int $deletingRoleId = null;

    public ?int $managingPermissionsRoleId = null;

    // Form fields
    public string $roleName = '';

    public string $roleGuardName = 'web';

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    /** @var array<int, string> */
    protected $queryString = ['search'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createRole(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function saveRole(): void
    {
        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name',
            'selectedPermissions' => 'array',
        ]);

        $role = Role::create([
            'name' => $this->roleName,
            'guard_name' => $this->roleGuardName,
        ]);

        if (! empty($this->selectedPermissions)) {
            $role->syncPermissions($this->selectedPermissions);
        }

        session()->flash('message', 'Role created successfully!');
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function editRole(int $roleId): void
    {
        $role = Role::findOrFail($roleId);

        $this->editingRoleId = $roleId;
        $this->roleName = $role->name;
        $this->roleGuardName = $role->guard_name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();

        $this->showEditModal = true;
    }

    public function updateRole(): void
    {
        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name,'.$this->editingRoleId,
            'selectedPermissions' => 'array',
        ]);

        $role = Role::findOrFail($this->editingRoleId);

        $role->update([
            'name' => $this->roleName,
            'guard_name' => $this->roleGuardName,
        ]);

        $role->syncPermissions($this->selectedPermissions);

        session()->flash('message', 'Role updated successfully!');
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $roleId): void
    {
        $this->deletingRoleId = $roleId;
        $this->showDeleteModal = true;
    }

    public function deleteRole(): void
    {
        if ($this->deletingRoleId === null) {
            return;
        }

        $role = Role::findOrFail($this->deletingRoleId);

        // Check if role is assigned to any users
        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            session()->flash('error', "Cannot delete role '{$role->name}'. It is assigned to {$usersCount} user(s).");
            $this->showDeleteModal = false;
            $this->deletingRoleId = null;

            return;
        }

        $role->delete();

        session()->flash('message', 'Role deleted successfully!');
        $this->showDeleteModal = false;
        $this->deletingRoleId = null;
    }

    public function managePermissions(int $roleId): void
    {
        $role = Role::findOrFail($roleId);

        $this->managingPermissionsRoleId = $roleId;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showPermissionsModal = true;
    }

    public function updatePermissions(): void
    {
        if ($this->managingPermissionsRoleId === null) {
            return;
        }

        $role = Role::findOrFail($this->managingPermissionsRoleId);
        $role->syncPermissions($this->selectedPermissions);

        session()->flash('message', 'Permissions updated successfully!');
        $this->showPermissionsModal = false;
        $this->managingPermissionsRoleId = null;
        $this->selectedPermissions = [];
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingRoleId = null;
    }

    public function closePermissionsModal(): void
    {
        $this->showPermissionsModal = false;
        $this->managingPermissionsRoleId = null;
        $this->selectedPermissions = [];
    }

    public function resetForm(): void
    {
        $this->roleName = '';
        $this->roleGuardName = 'web';
        $this->selectedPermissions = [];
        $this->editingRoleId = null;
    }

    /**
     * Group permissions by category for better UX
     *
     * @return array<string, array<int, Permission>>
     */
    public function getGroupedPermissions(): array
    {
        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $permission) {
            // Extract category from permission name (e.g., "view-projects" -> "projects")
            $parts = explode('-', $permission->name);
            $category = count($parts) > 1 ? $parts[1] : 'general';

            if (! isset($grouped[$category])) {
                $grouped[$category] = [];
            }

            $grouped[$category][] = $permission;
        }

        // Sort categories alphabetically
        ksort($grouped);

        return $grouped;
    }

    public function render(): View
    {
        $query = Role::query()->withCount(['users', 'permissions']);

        if ($this->search !== '') {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        $roles = $query->orderBy('name')->paginate(15);
        $permissions = Permission::all();
        $groupedPermissions = $this->getGroupedPermissions();

        return view('livewire.settings.roles-permissions', [
            'roles' => $roles,
            'permissions' => $permissions,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }
}
