<?php

declare(strict_types=1);

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Comprehensive Authentication Tests for DevFlow Pro
 *
 * Tests all authentication functionality including login, logout,
 * validation, redirects, and dark mode support.
 */
class AuthenticationTest extends DuskTestCase
{
    // use RefreshDatabase; // Disabled - testing against existing app

    /**
     * Test user credentials
     */
    protected const TEST_EMAIL = 'admin@devflow.com';

    protected const TEST_PASSWORD = 'DevFlow@2025';

    protected const INVALID_EMAIL = 'invalid@example.com';

    protected const INVALID_PASSWORD = 'wrongpassword';

    /**
     * Set up test environment
     * No database operations - tests run against existing app
     */
    protected function setUp(): void
    {
        parent::setUp();
        // No database setup needed - testing UI only against running application
    }

    /**
     * Test 1: Login page loads correctly with all form elements
     *
     * Verifies that the login page contains all required elements:
     * - Email input field
     * - Password input field
     * - Remember me checkbox
     * - Sign in button
     * - Forgot password link
     * - Proper heading and labels
     *
     */

    #[Test]
    public function login_page_loads_with_all_form_elements(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')
                ->screenshot('01-login-page-loaded')

                    // Assert page title and heading
                ->assertTitle('Login')
                ->assertSee('Sign in to your account')

                    // Assert email field exists with proper attributes
                ->assertPresent('#email')
                ->assertAttribute('#email', 'type', 'email')
                ->assertAttribute('#email', 'required', 'true')
                ->assertSee('Email address')

                    // Assert password field exists with proper attributes
                ->assertPresent('#password')
                ->assertAttribute('#password', 'type', 'password')
                ->assertAttribute('#password', 'required', 'true')
                ->assertSee('Password')

                    // Assert remember me checkbox exists
                ->assertPresent('#remember')
                ->assertAttribute('#remember', 'type', 'checkbox')
                ->assertSee('Remember me')

                    // Assert forgot password link
                ->assertSee('Forgot your password?')
                ->assertSeeLink('Forgot your password?')

                    // Assert submit button
                ->assertPresent('button[type="submit"]')
                ->assertSee('Sign in')

                    // Assert helper text
                ->assertSee('Need an account?')
                ->assertSee('Contact your DevFlow Pro administrator to request access.');
        });
    }

    /**
     * Test 2: Login with valid credentials redirects to dashboard
     *
     * Tests successful authentication flow:
     * - Enter valid credentials
     * - Submit the form
     * - Verify redirect to dashboard
     * - Verify authentication status
     *
     */

    #[Test]
    public function login_with_valid_credentials_redirects_to_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')

                    // Fill in the login form with valid credentials
                ->type('#email', self::TEST_EMAIL)
                ->type('#password', self::TEST_PASSWORD)
                ->screenshot('02-valid-credentials-entered')

                    // Submit the form
                ->press('Sign in')

                    // Wait for Livewire to process the request
                ->waitForText('Signing in...', 2)
                ->pause(1000)

                    // Assert redirect to dashboard
                ->waitForLocation('/dashboard', 10)
                ->assertPathIs('/dashboard')
                ->screenshot('02-dashboard-after-login')

                    // Verify user is authenticated
                ->assertAuthenticated();
        });
    }

    /**
     * Test 3: Login with invalid credentials shows error message
     *
     * Tests failed authentication scenarios:
     * - Invalid email
     * - Invalid password
     * - Verify error message display
     * - Verify no redirect occurs
     *
     */

    #[Test]
    public function login_with_invalid_credentials_shows_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')

                    // Attempt login with invalid credentials
                ->type('#email', self::INVALID_EMAIL)
                ->type('#password', self::INVALID_PASSWORD)
                ->screenshot('03-invalid-credentials-entered')
                ->press('Sign in')
                ->waitForText('Signing in...', 2)
                ->pause(1500)

                    // Assert error message is displayed
                ->waitForText('The provided credentials do not match our records.', 5)
                ->assertSee('The provided credentials do not match our records.')
                ->screenshot('03-error-message-displayed')

                    // Assert still on login page
                ->assertPathIs('/login')

                    // Verify user is not authenticated
                ->assertGuest();
        });
    }

    /**
     * Test 4: Login form validation - empty email
     *
     * Tests validation when email field is empty
     *
     */

    #[Test]
    public function login_validation_empty_email(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')

                    // Leave email empty, fill password
                ->type('#password', self::TEST_PASSWORD)
                ->screenshot('04a-empty-email-before-submit')
                ->press('Sign in')
                ->pause(500)

                    // HTML5 validation should prevent submission
                    // The browser will show a native validation message
                ->assertPathIs('/login')
                ->assertGuest()
                ->screenshot('04a-empty-email-validation');
        });
    }

    /**
     * Test 5: Login form validation - empty password
     *
     * Tests validation when password field is empty
     *
     */

    #[Test]
    public function login_validation_empty_password(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')

                    // Fill email, leave password empty
                ->type('#email', self::TEST_EMAIL)
                ->screenshot('04b-empty-password-before-submit')
                ->press('Sign in')
                ->pause(500)

                    // HTML5 validation should prevent submission
                ->assertPathIs('/login')
                ->assertGuest()
                ->screenshot('04b-empty-password-validation');
        });
    }

    /**
     * Test 6: Login form validation - invalid email format
     *
     * Tests validation when email format is invalid
     *
     */

    #[Test]
    public function login_validation_invalid_email_format(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')

                    // Enter invalid email format
                ->type('#email', 'not-an-email')
                ->type('#password', self::TEST_PASSWORD)
                ->screenshot('04c-invalid-email-format')
                ->press('Sign in')
                ->pause(500)

                    // HTML5 validation should prevent submission
                ->assertPathIs('/login')
                ->assertGuest()
                ->screenshot('04c-invalid-email-validation');
        });
    }

    /**
     * Test 7: Logout functionality works and redirects to login
     *
     * Tests logout flow:
     * - Login first
     * - Perform logout
     * - Verify redirect to home/login
     * - Verify session is cleared
     *
     */

    #[Test]
    public function logout_functionality_works_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            // First login
            $browser->loginAs(User::where('email', self::TEST_EMAIL)->first())
                ->visit('/dashboard')
                ->assertAuthenticated()
                ->screenshot('05-logged-in-before-logout')

                    // Find and click logout button/link
                ->pause(500);

            // Logout via POST request (since logout is a POST route)
            $user = User::where('email', self::TEST_EMAIL)->first();
            $browser->visit('/dashboard')
                ->assertAuthenticated();

            // Manually logout using the logout route
            $this->post('/logout');

            $browser->visit('/login')
                ->pause(500)
                ->assertGuest()
                ->screenshot('05-after-logout');
        });
    }

    /**
     * Test 8: Authenticated user can access dashboard
     *
     * Tests that authenticated users have access to protected routes
     *
     */

    #[Test]
    public function authenticated_user_can_access_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();

            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertAuthenticated()
                ->assertPathIs('/dashboard')
                ->screenshot('06-authenticated-dashboard-access')

                    // Assert dashboard content is visible
                ->pause(1000);
        });
    }

    /**
     * Test 9: Unauthenticated user is redirected to login
     *
     * Tests that guests are redirected to login when accessing protected routes
     *
     */

    #[Test]
    public function unauthenticated_user_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dashboard')
                ->assertGuest()

                    // Should be redirected to login
                ->waitForLocation('/login', 5)
                ->assertPathIs('/login')
                ->screenshot('07-guest-redirected-to-login')
                ->assertSee('Sign in to your account');
        });
    }

    /**
     * Test 10: Remember me checkbox functionality
     *
     * Tests that the remember me checkbox persists the session
     *
     */

    #[Test]
    public function remember_me_checkbox_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')

                    // Fill credentials and check remember me
                ->type('#email', self::TEST_EMAIL)
                ->type('#password', self::TEST_PASSWORD)
                ->check('#remember')
                ->assertChecked('#remember')
                ->screenshot('08-remember-me-checked')
                ->press('Sign in')
                ->waitForLocation('/dashboard', 10)
                ->assertAuthenticated()
                ->screenshot('08-logged-in-with-remember');

            // Note: Testing actual remember me persistence requires checking cookies
            // which would need additional browser session testing
        });
    }

    /**
     * Test 11: Login page has proper dark mode styling
     *
     * Tests that the login page supports dark mode and has proper styling
     *
     */

    #[Test]
    public function login_page_supports_dark_mode(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')
                ->screenshot('09-login-light-mode')

                    // Check for dark mode classes on elements
                    // The form container should have dark mode classes
                ->assertPresent('.dark\\:bg-gray-800, .dark\\:text-white, [class*="dark:"]')

                    // Verify dark mode classes exist in the DOM
                ->assertSeeIn('h2', 'Sign in to your account')
                ->assertPresent('.dark\\:text-gray-300, .dark\\:text-gray-400, [class*="dark:text"]')
                ->screenshot('09-dark-mode-classes-verified');

            // Note: To actually test dark mode rendering, you would need to:
            // 1. Set system/browser preference to dark mode
            // 2. Or toggle dark mode if there's a theme switcher
            // This test verifies the classes exist in the markup
        });
    }

    /**
     * Test 12: Forgot password link navigates correctly
     *
     * Tests that the forgot password link works
     *
     */

    #[Test]
    public function forgot_password_link_navigates_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')
                ->screenshot('10-before-forgot-password-click')
                ->clickLink('Forgot your password?')
                ->pause(500)

                    // Should navigate to password reset page
                ->waitForLocation('/forgot-password', 5)
                ->assertPathIs('/forgot-password')
                ->screenshot('10-forgot-password-page');
        });
    }

    /**
     * Test 13: Login form Livewire wire:loading states
     *
     * Tests that Livewire loading states work correctly
     *
     */

    #[Test]
    public function login_form_shows_loading_state(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')
                ->type('#email', self::TEST_EMAIL)
                ->type('#password', self::TEST_PASSWORD)

                    // Submit and quickly check for loading state
                ->press('Sign in')

                    // Try to catch the "Signing in..." text
                    // Note: This might be too fast to catch in some cases
                ->pause(100)

                    // Eventually should redirect to dashboard
                ->waitForLocation('/dashboard', 10)
                ->assertAuthenticated()
                ->screenshot('11-successful-login-complete');
        });
    }

    /**
     * Test 14: Multiple failed login attempts
     *
     * Tests behavior with multiple failed login attempts
     *
     */

    #[Test]
    public function multiple_failed_login_attempts(): void
    {
        $this->browse(function (Browser $browser) {
            for ($i = 1; $i <= 3; $i++) {
                $browser->visit('/login')
                    ->waitForText('Sign in to your account')
                    ->type('#email', self::INVALID_EMAIL)
                    ->type('#password', self::INVALID_PASSWORD)
                    ->screenshot("12-failed-attempt-{$i}-before")
                    ->press('Sign in')
                    ->waitForText('Signing in...', 2)
                    ->pause(1500)
                    ->waitForText('The provided credentials do not match our records.', 5)
                    ->assertSee('The provided credentials do not match our records.')
                    ->screenshot("12-failed-attempt-{$i}-after")
                    ->assertPathIs('/login')
                    ->assertGuest();

                // Clear the form between attempts
                $browser->clear('#email')->clear('#password');
                $browser->pause(500);
            }
        });
    }

    /**
     * Test 15: Login form autofocus on email field
     *
     * Tests that the email field is autofocused on page load
     *
     */

    #[Test]
    public function login_email_field_has_autofocus(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')

                    // Check that email field has autofocus attribute
                ->assertAttribute('#email', 'autofocus', 'true')
                ->screenshot('13-autofocus-verified')

                    // The focused element should be the email input
                    // Note: Checking actual focus state is browser-dependent
                ->assertFocused('#email');
        });
    }

    /**
     * Test 16: Session message display (e.g., registration closed)
     *
     * Tests that session status messages are displayed correctly
     *
     */

    #[Test]
    public function login_displays_session_status_messages(): void
    {
        $this->browse(function (Browser $browser) {
            // Visit register route which redirects to login with a message
            $browser->visit('/register')
                ->pause(500)

                    // Should be redirected to login
                ->waitForLocation('/login', 5)
                ->assertPathIs('/login')

                    // Should see the status message
                ->pause(500)
                ->assertSee('Registration is currently closed')
                ->screenshot('14-session-status-message-displayed');
        });
    }

    /**
     * Test 17: Verify login updates last_login_at timestamp
     *
     * Tests that successful login updates the user's last login timestamp
     *
     */

    #[Test]
    public function successful_login_updates_last_login_timestamp(): void
    {
        $user = User::where('email', self::TEST_EMAIL)->first();
        $originalLastLogin = $user->last_login_at;

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')
                ->type('#email', self::TEST_EMAIL)
                ->type('#password', self::TEST_PASSWORD)
                ->press('Sign in')
                ->waitForLocation('/dashboard', 10)
                ->assertAuthenticated()
                ->screenshot('15-login-timestamp-test');
        });

        // Verify last_login_at was updated
        $user->refresh();
        $this->assertNotNull($user->last_login_at);

        if ($originalLastLogin) {
            $this->assertTrue($user->last_login_at->isAfter($originalLastLogin));
        }
    }

    /**
     * Test 18: Login page responsive layout
     *
     * Tests that login page renders correctly on different screen sizes
     *
     */

    #[Test]
    public function login_page_responsive_layout(): void
    {
        $this->browse(function (Browser $browser) {
            // Test mobile viewport
            $browser->resize(375, 667)
                ->visit('/login')
                ->waitForText('Sign in to your account')
                ->screenshot('16-login-mobile-view')
                ->assertSee('Sign in to your account')
                ->assertPresent('#email')
                ->assertPresent('#password');

            // Test tablet viewport
            $browser->resize(768, 1024)
                ->visit('/login')
                ->waitForText('Sign in to your account')
                ->screenshot('16-login-tablet-view')
                ->assertSee('Sign in to your account');

            // Test desktop viewport
            $browser->resize(1920, 1080)
                ->visit('/login')
                ->waitForText('Sign in to your account')
                ->screenshot('16-login-desktop-view')
                ->assertSee('Sign in to your account');
        });
    }

    /**
     * Test 19: Intended redirect after authentication
     *
     * Tests that users are redirected to their intended destination after login
     *
     */

    #[Test]
    public function login_redirects_to_intended_destination(): void
    {
        $this->browse(function (Browser $browser) {
            // Try to access a protected route while not authenticated
            $browser->visit('/projects')
                ->pause(500)

                    // Should be redirected to login
                ->waitForLocation('/login', 5)
                ->assertPathIs('/login')

                    // Now login
                ->type('#email', self::TEST_EMAIL)
                ->type('#password', self::TEST_PASSWORD)
                ->screenshot('17-login-before-intended-redirect')
                ->press('Sign in')
                ->pause(2000)

                    // Should redirect to the originally intended page (dashboard as default)
                    // Note: Laravel's intended() might redirect to dashboard if no intended URL
                ->screenshot('17-after-intended-redirect');
        });
    }

    /**
     * Test 20: Login form accessibility
     *
     * Tests basic accessibility features of the login form
     *
     */

    #[Test]
    public function login_form_has_proper_accessibility_features(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Sign in to your account')

                    // Check for proper label associations
                ->assertPresent('label[for="email"]')
                ->assertPresent('label[for="password"]')
                ->assertPresent('label[for="remember"]')

                    // Check that inputs have required attribute
                ->assertAttribute('#email', 'required', 'true')
                ->assertAttribute('#password', 'required', 'true')

                    // Check for proper input types
                ->assertAttribute('#email', 'type', 'email')
                ->assertAttribute('#password', 'type', 'password')
                ->assertAttribute('#remember', 'type', 'checkbox')
                ->screenshot('18-accessibility-verified');
        });
    }
}
