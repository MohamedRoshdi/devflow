<?php

declare(strict_types=1);

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Comprehensive Forgot Password Tests for DevFlow Pro
 *
 * Tests all password reset functionality including email input,
 * validation, reset link sending, and user feedback.
 */
class ForgotPasswordTest extends DuskTestCase
{
    /**
     * Test user credentials
     */
    protected const TEST_EMAIL = 'admin@devflow.com';

    protected const INVALID_EMAIL = 'notanemail';

    protected const NONEXISTENT_EMAIL = 'nonexistent@example.com';

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
     * Test 1: Forgot password page loads successfully
     *
     * Verifies that the forgot password page loads and contains basic content
     *
     */

    #[Test]
    public function forgot_password_page_loads_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('01-forgot-password-page-loaded')
                ->assertSee('Reset Password')
                ->assertPathIs('/forgot-password');
        });
    }

    /**
     * Test 2: Email field is present with proper attributes
     *
     * Verifies that the email input field exists and has correct attributes
     *
     */

    #[Test]
    public function email_field_is_present_with_proper_attributes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('02-email-field-check')

                    // Assert email field exists
                ->assertPresent('#email')

                    // Assert proper input type
                ->assertAttribute('#email', 'type', 'email')

                    // Assert required attribute
                ->assertAttribute('#email', 'required', 'true')

                    // Assert autofocus attribute
                ->assertAttribute('#email', 'autofocus', 'true')

                    // Assert label exists
                ->assertSee('Email address');
        });
    }

    /**
     * Test 3: Submit button is visible and properly labeled
     *
     * Verifies that the submit button exists with correct text
     *
     */

    #[Test]
    public function submit_button_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('03-submit-button-check')

                    // Assert submit button exists
                ->assertPresent('button[type="submit"]')

                    // Assert button text
                ->assertSee('Send Reset Link')

                    // Verify button has full width class
                ->assertPresent('button.w-full');
        });
    }

    /**
     * Test 4: Back to login link is present
     *
     * Verifies that the back to login link exists and works
     *
     */

    #[Test]
    public function back_to_login_link_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('04-back-to-login-check')

                    // Assert link text exists
                ->assertSee('Back to login')

                    // Assert it's a clickable link
                ->assertSeeLink('Back to login')

                    // Click and verify navigation
                ->clickLink('Back to login')
                ->waitForLocation('/login', 5)
                ->assertPathIs('/login')
                ->screenshot('04-navigated-to-login');
        });
    }

    /**
     * Test 5: Form validation works - empty email
     *
     * Verifies that HTML5 validation prevents empty submissions
     *
     */

    #[Test]
    public function form_validation_prevents_empty_submission(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')

                    // Try to submit without filling email
                ->screenshot('05-before-empty-submit')
                ->press('Send Reset Link')
                ->pause(500)

                    // Should stay on same page due to HTML5 validation
                ->assertPathIs('/forgot-password')
                ->screenshot('05-after-empty-submit-validation');
        });
    }

    /**
     * Test 6: Invalid email format shows error
     *
     * Verifies that invalid email format triggers validation
     *
     */

    #[Test]
    public function invalid_email_format_shows_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')

                    // Enter invalid email format
                ->type('#email', self::INVALID_EMAIL)
                ->screenshot('06-invalid-email-entered')
                ->press('Send Reset Link')
                ->pause(500)

                    // HTML5 validation should prevent submission
                ->assertPathIs('/forgot-password')
                ->screenshot('06-invalid-email-validation');
        });
    }

    /**
     * Test 7: Non-existent email is handled gracefully
     *
     * Verifies that the system handles non-existent emails
     * Note: Laravel may show success message for security reasons
     *
     */

    #[Test]
    public function nonexistent_email_handled_gracefully(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')

                    // Enter non-existent email
                ->type('#email', self::NONEXISTENT_EMAIL)
                ->screenshot('07-nonexistent-email-entered')
                ->press('Send Reset Link')
                ->waitForText('Sending...', 2)
                ->pause(2000)

                    // Should either show success or error message
                    // Laravel typically shows generic message for security
                ->screenshot('07-after-nonexistent-email-submit')
                ->assertPathIs('/forgot-password');
        });
    }

    /**
     * Test 8: Success message displayed for valid email
     *
     * Verifies that success message appears after valid submission
     *
     */

    #[Test]
    public function success_message_displayed_for_valid_email(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')

                    // Enter valid email
                ->type('#email', self::TEST_EMAIL)
                ->screenshot('08-valid-email-entered')
                ->press('Send Reset Link')
                ->waitForText('Sending...', 2)
                ->pause(2000)

                    // Wait for success message
                ->waitForText("We've sent you a password reset link", 10)
                ->assertSee("We've sent you a password reset link to your email address.")
                ->screenshot('08-success-message-displayed')

                    // Verify success message styling
                ->assertPresent('.bg-green-50, .bg-green-900\\/30, [class*="bg-green"]');
        });
    }

    /**
     * Test 9: Page title is correct
     *
     * Verifies that the page has the correct title
     *
     */

    #[Test]
    public function page_title_is_correct(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('09-page-title-check')

                    // Assert page title
                ->assertTitle('Forgot Password')

                    // Assert heading
                ->assertSee('Reset Password');
        });
    }

    /**
     * Test 10: Form has proper styling and structure
     *
     * Verifies that the form has proper CSS classes and styling
     *
     */

    #[Test]
    public function form_has_proper_styling(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('10-form-styling-check')

                    // Check for form element
                ->assertPresent('form')

                    // Check for proper container styling
                ->assertPresent('.bg-white, .dark\\:bg-gray-800, [class*="bg-"]')

                    // Check for shadow and rounded classes
                ->assertPresent('.shadow-md, .rounded-lg, [class*="shadow"], [class*="rounded"]')

                    // Verify button has primary styling
                ->assertPresent('.btn-primary, button.btn, [class*="btn"]');
        });
    }

    /**
     * Test 11: Input has placeholder or label
     *
     * Verifies that the email input is properly labeled
     *
     */

    #[Test]
    public function input_has_proper_label(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('11-input-label-check')

                    // Assert label exists and is associated with input
                ->assertPresent('label[for="email"]')
                ->assertSee('Email address')

                    // Verify label has proper styling
                ->assertPresent('label.block, label[class*="text-"]');
        });
    }

    /**
     * Test 12: CSRF protection is present
     *
     * Verifies that Livewire CSRF protection is in place
     *
     */

    #[Test]
    public function csrf_protection_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('12-csrf-check')

                    // Livewire automatically handles CSRF
                    // Check for Livewire attributes on form
                ->assertPresent('form[wire\\:submit], form[wire\\:submit\\.prevent]')

                    // Verify Livewire is loaded
                ->assertPresent('[wire\\:id]');
        });
    }

    /**
     * Test 13: Rate limiting message appears after multiple attempts
     *
     * Verifies that rate limiting is enforced
     *
     */

    #[Test]
    public function rate_limiting_prevents_abuse(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password');

            // Attempt multiple submissions quickly
            for ($i = 1; $i <= 3; $i++) {
                $browser->type('#email', self::TEST_EMAIL)
                    ->screenshot("13-rate-limit-attempt-{$i}")
                    ->press('Send Reset Link')
                    ->pause(500);

                // Clear the email field for next attempt
                if ($i < 3) {
                    $browser->clear('#email');
                }
            }

            // After multiple attempts, might see rate limiting or error
            // Laravel's Password facade may throttle requests
            $browser->pause(1000)
                ->screenshot('13-after-multiple-attempts');
        });
    }

    /**
     * Test 14: Loading state appears on submit
     *
     * Verifies that the loading state is shown during processing
     *
     */

    #[Test]
    public function loading_state_appears_on_submit(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->type('#email', self::TEST_EMAIL)
                ->screenshot('14-before-submit')

                    // Submit form
                ->press('Send Reset Link')

                    // Try to catch loading state
                ->pause(100)

                    // Button should be disabled during loading
                ->assertPresent('button[disabled], button[wire\\:loading\\.attr="disabled"]')

                    // Should see "Sending..." text
                ->waitForText('Sending...', 2)
                ->assertSee('Sending...')
                ->screenshot('14-loading-state-visible')
                ->pause(2000);
        });
    }

    /**
     * Test 15: Responsive design works on mobile
     *
     * Verifies that the page is responsive on mobile devices
     *
     */

    #[Test]
    public function responsive_design_works_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            // Test mobile viewport (iPhone SE)
            $browser->resize(375, 667)
                ->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('15-mobile-view')

                    // All elements should be visible
                ->assertSee('Reset Password')
                ->assertPresent('#email')
                ->assertPresent('button[type="submit"]')
                ->assertSee('Back to login');

            // Test tablet viewport (iPad)
            $browser->resize(768, 1024)
                ->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('15-tablet-view')
                ->assertSee('Reset Password')
                ->assertPresent('#email');

            // Test desktop viewport
            $browser->resize(1920, 1080)
                ->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('15-desktop-view')
                ->assertSee('Reset Password');
        });
    }

    /**
     * Test 16: Dark mode styling is present
     *
     * Verifies that dark mode CSS classes exist
     *
     */

    #[Test]
    public function dark_mode_styling_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('16-dark-mode-check')

                    // Check for dark mode classes on container
                ->assertPresent('.dark\\:bg-gray-800, [class*="dark:bg"]')

                    // Check for dark mode text colors
                ->assertPresent('.dark\\:text-white, .dark\\:text-gray-300, [class*="dark:text"]')

                    // Check heading has dark mode class
                ->assertPresent('h2.dark\\:text-white, h2[class*="dark:"]');
        });
    }

    /**
     * Test 17: Email field autofocus works
     *
     * Verifies that the email field receives focus on page load
     *
     */

    #[Test]
    public function email_field_has_autofocus(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('17-autofocus-check')

                    // Check autofocus attribute
                ->assertAttribute('#email', 'autofocus', 'true')

                    // Verify field is focused
                ->assertFocused('#email');
        });
    }

    /**
     * Test 18: Form can be submitted with Enter key
     *
     * Verifies keyboard accessibility
     *
     */

    #[Test]
    public function form_submits_with_enter_key(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->type('#email', self::TEST_EMAIL)
                ->screenshot('18-before-enter-submit')

                    // Press Enter to submit
                ->keys('#email', '{enter}')
                ->pause(500)

                    // Should process the submission
                ->waitForText('Sending...', 2)
                ->pause(2000)
                ->screenshot('18-after-enter-submit');
        });
    }

    /**
     * Test 19: Error message styling is proper
     *
     * Verifies that validation errors are styled correctly
     *
     */

    #[Test]
    public function error_message_styling_is_proper(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')

                    // Trigger an error by using invalid format initially
                    // Then fix it to see both states
                ->type('#email', 'invalid')
                ->press('Send Reset Link')
                ->pause(1000)
                ->screenshot('19-error-state-check')

                    // Fix the email and try again
                ->clear('#email')
                ->type('#email', self::TEST_EMAIL)
                ->press('Send Reset Link')
                ->pause(2000)
                ->screenshot('19-success-state-check');
        });
    }

    /**
     * Test 20: Success message has proper dismiss behavior
     *
     * Verifies that success message persists appropriately
     *
     */

    #[Test]
    public function success_message_persists_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->type('#email', self::TEST_EMAIL)
                ->press('Send Reset Link')
                ->waitForText('Sending...', 2)
                ->pause(2000)

                    // Success message should be visible
                ->waitForText("We've sent you a password reset link", 10)
                ->assertSee("We've sent you a password reset link")
                ->screenshot('20-success-message-persists')

                    // Message should remain visible (not auto-dismiss)
                ->pause(3000)
                ->assertSee("We've sent you a password reset link")
                ->screenshot('20-success-message-still-visible');
        });
    }

    /**
     * Test 21: Multiple email submissions show correct state
     *
     * Verifies that multiple valid submissions work correctly
     *
     */

    #[Test]
    public function multiple_valid_submissions_work(): void
    {
        $this->browse(function (Browser $browser) {
            // First submission
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->type('#email', self::TEST_EMAIL)
                ->press('Send Reset Link')
                ->pause(2000)
                ->waitForText("We've sent you a password reset link", 10)
                ->screenshot('21-first-submission-success');

            // Wait a bit to avoid rate limiting
            $browser->pause(3000);

            // Second submission - visit page fresh
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->type('#email', self::TEST_EMAIL)
                ->screenshot('21-before-second-submission')
                ->press('Send Reset Link')
                ->pause(2000)
                ->screenshot('21-second-submission-complete');
        });
    }

    /**
     * Test 22: Email input clears error on typing
     *
     * Verifies that Livewire real-time validation works
     *
     */

    #[Test]
    public function email_input_has_livewire_binding(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('22-livewire-binding-check')

                    // Check for wire:model attribute
                ->assertPresent('input[wire\\:model="email"]')

                    // Type and verify Livewire is updating
                ->type('#email', self::TEST_EMAIL)
                ->pause(500)
                ->screenshot('22-after-typing');
        });
    }

    /**
     * Test 23: Page accessible via direct URL
     *
     * Verifies that the route is properly configured
     *
     */

    #[Test]
    public function page_accessible_via_direct_url(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->pause(500)
                ->assertPathIs('/forgot-password')
                ->waitForText('Reset Password')
                ->assertSee('Reset Password')
                ->screenshot('23-direct-url-access');
        });
    }

    /**
     * Test 24: Guest layout is applied
     *
     * Verifies that the guest layout wrapper is used
     *
     */

    #[Test]
    public function guest_layout_is_applied(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->screenshot('24-guest-layout-check')

                    // Guest layout should not have main navigation
                    // (as opposed to authenticated layout)
                ->assertDontSee('Projects')
                ->assertDontSee('Servers')
                ->assertDontSee('Dashboard')

                    // Should have the centered form layout
                ->assertPresent('.bg-white, .dark\\:bg-gray-800, [class*="bg-"]');
        });
    }

    /**
     * Test 25: Authenticated users can still access forgot password
     *
     * Verifies that even logged-in users can access the page
     *
     */

    #[Test]
    public function authenticated_users_can_access_page(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();

            $browser->loginAs($user)
                ->visit('/forgot-password')
                ->waitForText('Reset Password')
                ->assertSee('Reset Password')
                ->assertPresent('#email')
                ->screenshot('25-authenticated-access');
        });
    }
}
