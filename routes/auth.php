<?php

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');

    // Registration is closed – redirect any attempt back to login with a notice
    Route::get('/register', function () {
        return redirect()->route('login')->with('status', 'Registration is currently closed. Please contact an administrator for access.');
    })->name('register');
});

Route::post('/logout', function () {
    Auth::logout();

    return redirect('/');
})->middleware('auth')->name('logout');
