<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Forgot Password')]
class ForgotPassword extends Component
{
    public string $email = '';

    public bool $emailSent = false;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
        ];
    }

    /**
     * Send password reset link with rate limiting.
     */
    public function sendResetLink(): void
    {
        $this->validate();

        // Apply rate limiting - 3 attempts per minute per email
        $throttleKey = 'password-reset:'.strtolower($this->email);

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many password reset requests. Please try again in {$seconds} seconds.",
            ]);
        }

        // Increment attempts
        RateLimiter::hit($throttleKey, 60);

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->emailSent = true;
        } else {
            $this->addError('email', __($status));
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.auth.forgot-password');
    }
}
