<?php

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');

    // Registration - controlled by system setting (enabled by default)
    Route::get('/register', function () {
        if (SystemSetting::isRegistrationEnabled()) {
            return app(Register::class);
        }

        return redirect()->route('login')->with('status', 'Registration is currently closed. Please contact an administrator for access.');
    })->name('register');
});

Route::post('/logout', function () {
    Auth::logout();

    return redirect('/');
})->middleware('auth')->name('logout');
