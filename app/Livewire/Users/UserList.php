<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public ?int $editingUserId = null;

    // Form fields
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @var array<int, string> */
    public array $selectedRoles = [];

    /** @var array<int, string> */
    protected $queryString = ['search', 'roleFilter'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function createUser(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function saveUser(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'selectedRoles' => 'array',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
        ]);

        if (! empty($this->selectedRoles)) {
            $user->assignRole($this->selectedRoles);
        }

        session()->flash('message', 'User created successfully!');
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function editUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->editingUserId = $userId;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->selectedRoles = $user->roles->pluck('name')->toArray();

        $this->showEditModal = true;
    }

    public function updateUser(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$this->editingUserId,
            'password' => 'nullable|min:8|confirmed',
            'selectedRoles' => 'array',
        ]);

        $user = User::findOrFail($this->editingUserId);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        if ($this->password !== '') {
            $user->update(['password' => bcrypt($this->password)]);
        }

        $user->syncRoles($this->selectedRoles);

        session()->flash('message', 'User updated successfully!');
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function deleteUser(int $userId): void
    {
        if ($userId === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account!');

            return;
        }

        $user = User::findOrFail($userId);
        $user->delete();

        session()->flash('message', 'User deleted successfully!');
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->roleFilter = '';
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

    public function resetForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedRoles = [];
        $this->editingUserId = null;
    }

    public function render(): View
    {
        $query = User::query();

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->roleFilter !== '') {
            $query->role($this->roleFilter);
        }

        $users = $query->latest()->paginate(15);
        $roles = Role::all();

        return view('livewire.users.user-list', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }
}
