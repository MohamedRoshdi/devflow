<?php

namespace Tests\Browser\Traits;

use App\Models\User;
use Laravel\Dusk\Browser;

trait LoginViaUI
{
    /**
     * Login via the UI form instead of loginAs()
     * This is needed when testing against a separate server instance
     */
    protected function loginViaUI(Browser $browser, ?User $user = null): Browser
    {
        $email = $user?->email ?? 'admin@devflow.test';
        $password = 'password';

        // First visit login page
        $browser->visit('/login')->pause(1500);

        // Check if we got redirected to dashboard (already logged in)
        $currentUrl = $browser->driver->getCurrentURL();
        if (str_contains($currentUrl, '/dashboard')) {
            // Already logged in, just return
            return $browser;
        }

        // Check if we can see the login form
        try {
            $browser->waitFor('#email', 10)
                ->clear('#email')
                ->type('#email', $email)
                ->clear('#password')
                ->type('#password', $password)
                ->pause(500)
                ->press('Sign in')
                ->pause(2000)
                ->waitForLocation('/dashboard', 20)
                ->pause(500);
        } catch (\Exception $e) {
            // If login form not found, check if we're already on dashboard
            $currentUrl = $browser->driver->getCurrentURL();
            if (! str_contains($currentUrl, '/dashboard')) {
                throw $e;
            }
        }

        return $browser;
    }

    /**
     * Ensure user is logged in (check if on login page and login if needed)
     */
    protected function ensureLoggedIn(Browser $browser, ?User $user = null): Browser
    {
        // Check if we're on the login page
        $currentUrl = $browser->driver->getCurrentURL();

        if (str_contains($currentUrl, '/login')) {
            return $this->loginViaUI($browser, $user);
        }

        return $browser;
    }
}
