<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Login')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * Check if this is a fresh installation with only the default admin user.
     */
    #[Computed]
    public function isFirstSetup(): bool
    {
        // Check if there's exactly one user with the default admin email
        $userCount = User::count();
        if ($userCount !== 1) {
            return false;
        }

        $defaultAdmin = User::where('email', 'admin@devflow.local')->first();
        if (! $defaultAdmin) {
            return false;
        }

        // Verify the password is still the default
        return Hash::check('password', $defaultAdmin->password);
    }

    /**
     * Get the validation rules that apply to the component.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    /**
     * Attempt to authenticate the user with rate limiting.
     */
    public function login()
    {
        $this->validate();

        // Apply rate limiting - 5 attempts per minute per email + IP combination
        $throttleKey = strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            // Clear rate limiter on successful login
            RateLimiter::clear($throttleKey);

            session()->regenerate();

            $user = Auth::user();
            if ($user !== null) {
                $user->update(['last_login_at' => now()]);
            }

            return redirect()->intended('/dashboard');
        }

        // Increment failed login attempts
        RateLimiter::hit($throttleKey, 60);

        $this->addError('email', 'The provided credentials do not match our records.');

        return null;
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
