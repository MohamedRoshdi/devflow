<?php

namespace App\Livewire\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserList extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingUserId = null;
    
    // Form fields
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $selectedRoles = [];

    protected $queryString = ['search', 'roleFilter'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function createUser()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function saveUser()
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

        if (!empty($this->selectedRoles)) {
            $user->assignRole($this->selectedRoles);
        }

        session()->flash('message', 'User created successfully!');
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function editUser($userId)
    {
        $user = User::findOrFail($userId);
        
        $this->editingUserId = $userId;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
        
        $this->showEditModal = true;
    }

    public function updateUser()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->editingUserId,
            'password' => 'nullable|min:8|confirmed',
            'selectedRoles' => 'array',
        ]);

        $user = User::findOrFail($this->editingUserId);
        
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        if ($this->password) {
            $user->update(['password' => bcrypt($this->password)]);
        }

        $user->syncRoles($this->selectedRoles);

        session()->flash('message', 'User updated successfully!');
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function deleteUser($userId)
    {
        if ($userId == auth()->id()) {
            session()->flash('error', 'You cannot delete your own account!');
            return;
        }

        $user = User::findOrFail($userId);
        $user->delete();

        session()->flash('message', 'User deleted successfully!');
    }

    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedRoles = [];
        $this->editingUserId = null;
    }

    public function render()
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->roleFilter) {
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
