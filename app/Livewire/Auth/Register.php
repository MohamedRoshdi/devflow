<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.guest')]
#[Title('Register')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Get the validation rules for registration.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }

    /**
     * Register a new user.
     */
    public function register()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        // Assign default role to new user
        $defaultRoleName = SystemSetting::get('auth.default_role', 'viewer');
        $defaultRole = Role::where('name', $defaultRoleName)->first();

        if ($defaultRole) {
            $user->assignRole($defaultRole);
        } else {
            // Fallback to viewer role if configured role doesn't exist
            $viewerRole = Role::where('name', 'viewer')->first();
            if ($viewerRole) {
                $user->assignRole($viewerRole);
            }
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    /**
     * Render the registration component.
     */
    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
