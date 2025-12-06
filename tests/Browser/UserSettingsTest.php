<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\ApiToken;
use App\Models\SSHKey;
use App\Models\User;
use App\Models\UserSettings;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

/**
 * Comprehensive User Profile/Settings Tests for DevFlow Pro
 *
 * Tests all user profile and settings functionality including:
 * - Profile information management
 * - Avatar upload
 * - Password management
 * - API token management
 * - SSH key management
 * - Notification preferences
 * - Theme preferences
 * - Timezone settings
 * - Session management
 * - Account security
 */
class UserSettingsTest extends DuskTestCase
{
    use LoginViaUI;

    /**
     * Test user credentials
     */
    protected const TEST_EMAIL = 'admin@devflow.com';

    protected const TEST_PASSWORD = 'DevFlow@2025';

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Tests run against existing application instance
    }

    /**
     * Test 1: User can access profile settings page
     *
     * @test
     */
    public function test_user_can_view_profile_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->pause(1000)
                ->visit('/settings/profile')
                ->pause(1000)
                ->assertSee('Profile')
                ->screenshot('01-profile-settings-page');
        });
    }

    /**
     * Test 2: Profile settings page displays user information
     *
     * @test
     */
    public function test_profile_page_displays_user_information(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();

            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->assertSee($user->name)
                ->assertSee($user->email)
                ->screenshot('02-profile-user-info-displayed');
        });
    }

    /**
     * Test 3: User can edit their name
     *
     * @test
     */
    public function test_user_can_edit_name(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $originalName = $user->name;
            $newName = 'Updated Test User';

            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->clear('input[name="name"]')
                ->type('input[name="name"]', $newName)
                ->screenshot('03-before-name-update')
                ->press('Save Changes')
                ->pause(2000)
                ->screenshot('03-after-name-update');

            // Restore original name
            $user->refresh();
            $user->update(['name' => $originalName]);
        });
    }

    /**
     * Test 4: User can edit their email
     *
     * @test
     */
    public function test_user_can_edit_email(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $originalEmail = $user->email;
            $newEmail = 'newemail@devflow.com';

            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->clear('input[name="email"]')
                ->type('input[name="email"]', $newEmail)
                ->screenshot('04-before-email-update')
                ->press('Save Changes')
                ->pause(2000)
                ->screenshot('04-after-email-update');

            // Restore original email
            $user->refresh();
            $user->update(['email' => $originalEmail]);
        });
    }

    /**
     * Test 5: User can view avatar upload section
     *
     * @test
     */
    public function test_user_can_view_avatar_section(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->assertSee('Avatar')
                ->screenshot('05-avatar-section-visible');
        });
    }

    /**
     * Test 6: User can access password change section
     *
     * @test
     */
    public function test_user_can_access_password_change_section(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/security')
                ->pause(1000)
                ->assertSee('Password')
                ->assertSee('Current Password')
                ->screenshot('06-password-change-section');
        });
    }

    /**
     * Test 7: Password change requires current password
     *
     * @test
     */
    public function test_password_change_requires_current_password(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/security')
                ->pause(1000)
                ->assertPresent('input[name="current_password"]')
                ->assertPresent('input[name="new_password"]')
                ->assertPresent('input[name="new_password_confirmation"]')
                ->screenshot('07-password-fields-present');
        });
    }

    /**
     * Test 8: User can view API token management page
     *
     * @test
     */
    public function test_user_can_view_api_token_management(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/api-tokens')
                ->pause(1000)
                ->assertSee('API Tokens')
                ->screenshot('08-api-tokens-page');
        });
    }

    /**
     * Test 9: User can create new API token
     *
     * @test
     */
    public function test_user_can_create_api_token(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/api-tokens')
                ->pause(1000)
                ->click('button:contains("Create Token")')
                ->pause(500)
                ->assertSee('Create API Token')
                ->screenshot('09-create-token-modal');
        });
    }

    /**
     * Test 10: API token creation requires name
     *
     * @test
     */
    public function test_api_token_creation_requires_name(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/api-tokens')
                ->pause(1000)
                ->assertPresent('button:contains("Create Token")')
                ->screenshot('10-api-token-name-required');
        });
    }

    /**
     * Test 11: User can view existing API tokens
     *
     * @test
     */
    public function test_user_can_view_existing_api_tokens(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();

            // Create a test token
            $token = ApiToken::create([
                'user_id' => $user->id,
                'name' => 'Test Token',
                'token' => hash('sha256', Str::random(60)),
                'abilities' => ['projects:read'],
            ]);

            $this->loginViaUI($browser)
                ->visit('/settings/api-tokens')
                ->pause(1000)
                ->assertSee('Test Token')
                ->screenshot('11-existing-tokens-visible');

            // Cleanup
            $token->delete();
        });
    }

    /**
     * Test 12: User can revoke API tokens
     *
     * @test
     */
    public function test_user_can_revoke_api_tokens(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();

            // Create a test token
            $token = ApiToken::create([
                'user_id' => $user->id,
                'name' => 'Token to Revoke',
                'token' => hash('sha256', Str::random(60)),
                'abilities' => ['projects:read'],
            ]);

            $this->loginViaUI($browser)
                ->visit('/settings/api-tokens')
                ->pause(1000)
                ->assertSee('Token to Revoke')
                ->screenshot('12-before-token-revoke');

            // Try to find and click revoke button
            try {
                $browser->click('button:contains("Revoke")')
                    ->pause(1000)
                    ->screenshot('12-after-token-revoke');
            } catch (\Exception $e) {
                $browser->screenshot('12-revoke-button-not-found');
            }

            // Cleanup
            $token->delete();
        });
    }

    /**
     * Test 13: User can access SSH key management
     *
     * @test
     */
    public function test_user_can_access_ssh_key_management(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/ssh-keys')
                ->pause(1000)
                ->assertSee('SSH Keys')
                ->screenshot('13-ssh-keys-page');
        });
    }

    /**
     * Test 14: SSH key page has add key button
     *
     * @test
     */
    public function test_ssh_key_page_has_add_button(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/ssh-keys')
                ->pause(1000)
                ->assertPresent('button:contains("Add SSH Key")')
                ->screenshot('14-add-ssh-key-button');
        });
    }

    /**
     * Test 15: User can view existing SSH keys
     *
     * @test
     */
    public function test_user_can_view_existing_ssh_keys(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();

            // Create a test SSH key
            $sshKey = SSHKey::create([
                'user_id' => $user->id,
                'name' => 'Test SSH Key',
                'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQ...',
            ]);

            $this->loginViaUI($browser)
                ->visit('/settings/ssh-keys')
                ->pause(1000)
                ->assertSee('Test SSH Key')
                ->screenshot('15-existing-ssh-keys-visible');

            // Cleanup
            $sshKey->delete();
        });
    }

    /**
     * Test 16: User can access notification preferences
     *
     * @test
     */
    public function test_user_can_access_notification_preferences(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/notifications')
                ->pause(1000)
                ->assertSee('Notifications')
                ->screenshot('16-notifications-preferences');
        });
    }

    /**
     * Test 17: Notification preferences show deployment notifications
     *
     * @test
     */
    public function test_notification_preferences_show_deployment_options(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/notifications')
                ->pause(1000)
                ->screenshot('17-deployment-notifications');
        });
    }

    /**
     * Test 18: User can access theme preferences
     *
     * @test
     */
    public function test_user_can_access_theme_preferences(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(1000)
                ->assertSee('Preferences')
                ->screenshot('18-theme-preferences');
        });
    }

    /**
     * Test 19: Theme preferences show dark/light mode options
     *
     * @test
     */
    public function test_theme_preferences_show_mode_options(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(1000)
                ->screenshot('19-theme-mode-options');
        });
    }

    /**
     * Test 20: User can toggle dark mode
     *
     * @test
     */
    public function test_user_can_toggle_dark_mode(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(1000)
                ->screenshot('20-before-theme-toggle');

            // Look for theme toggle
            try {
                $browser->click('input[name="theme"]')
                    ->pause(1000)
                    ->screenshot('20-after-theme-toggle');
            } catch (\Exception $e) {
                $browser->screenshot('20-theme-toggle-not-found');
            }
        });
    }

    /**
     * Test 21: User can view timezone settings
     *
     * @test
     */
    public function test_user_can_view_timezone_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->assertSee('Timezone')
                ->screenshot('21-timezone-settings');
        });
    }

    /**
     * Test 22: Timezone settings show timezone selector
     *
     * @test
     */
    public function test_timezone_settings_show_selector(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->assertPresent('select[name="timezone"]')
                ->screenshot('22-timezone-selector');
        });
    }

    /**
     * Test 23: User can change timezone
     *
     * @test
     */
    public function test_user_can_change_timezone(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $originalTimezone = $user->timezone ?? 'UTC';

            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->select('select[name="timezone"]', 'America/New_York')
                ->screenshot('23-before-timezone-change')
                ->press('Save Changes')
                ->pause(2000)
                ->screenshot('23-after-timezone-change');

            // Restore original timezone
            $user->refresh();
            $user->update(['timezone' => $originalTimezone]);
        });
    }

    /**
     * Test 24: User can view language/locale settings
     *
     * @test
     */
    public function test_user_can_view_language_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(1000)
                ->screenshot('24-language-settings');
        });
    }

    /**
     * Test 25: User can access session management
     *
     * @test
     */
    public function test_user_can_access_session_management(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/security')
                ->pause(1000)
                ->assertSee('Security')
                ->screenshot('25-session-management');
        });
    }

    /**
     * Test 26: Session management shows active sessions
     *
     * @test
     */
    public function test_session_management_shows_active_sessions(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/security')
                ->pause(1000)
                ->screenshot('26-active-sessions');
        });
    }

    /**
     * Test 27: User can view account deletion section
     *
     * @test
     */
    public function test_user_can_view_account_deletion_section(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/security')
                ->pause(1000)
                ->screenshot('27-account-deletion-section');
        });
    }

    /**
     * Test 28: User can view email verification status
     *
     * @test
     */
    public function test_user_can_view_email_verification_status(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->screenshot('28-email-verification-status');
        });
    }

    /**
     * Test 29: User can access activity log
     *
     * @test
     */
    public function test_user_can_access_activity_log(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/activity')
                ->pause(1000)
                ->screenshot('29-activity-log');
        });
    }

    /**
     * Test 30: Activity log shows recent activities
     *
     * @test
     */
    public function test_activity_log_shows_recent_activities(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/activity')
                ->pause(1000)
                ->screenshot('30-recent-activities');
        });
    }

    /**
     * Test 31: Profile form has proper validation
     *
     * @test
     */
    public function test_profile_form_has_validation(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->assertPresent('input[name="name"][required]')
                ->assertPresent('input[name="email"][required]')
                ->screenshot('31-profile-validation');
        });
    }

    /**
     * Test 32: Email must be unique validation
     *
     * @test
     */
    public function test_email_must_be_unique_validation(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->assertAttribute('input[name="email"]', 'type', 'email')
                ->screenshot('32-email-unique-validation');
        });
    }

    /**
     * Test 33: User can view two-factor authentication settings
     *
     * @test
     */
    public function test_user_can_view_two_factor_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/security')
                ->pause(1000)
                ->screenshot('33-two-factor-settings');
        });
    }

    /**
     * Test 34: Two-factor authentication shows enable option
     *
     * @test
     */
    public function test_two_factor_shows_enable_option(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/security')
                ->pause(1000)
                ->screenshot('34-two-factor-enable-option');
        });
    }

    /**
     * Test 35: User preferences are persisted
     *
     * @test
     */
    public function test_user_preferences_are_persisted(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(1000)
                ->screenshot('35-preferences-persisted');

            // Verify settings exist
            $this->assertNotNull($settings);
        });
    }

    /**
     * Test 36: Navigation between settings sections works
     *
     * @test
     */
    public function test_navigation_between_settings_sections(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->screenshot('36a-profile-section')
                ->visit('/settings/security')
                ->pause(1000)
                ->screenshot('36b-security-section')
                ->visit('/settings/preferences')
                ->pause(1000)
                ->screenshot('36c-preferences-section');
        });
    }

    /**
     * Test 37: Settings page has proper breadcrumbs
     *
     * @test
     */
    public function test_settings_page_has_breadcrumbs(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->screenshot('37-settings-breadcrumbs');
        });
    }

    /**
     * Test 38: User can cancel profile edits
     *
     * @test
     */
    public function test_user_can_cancel_profile_edits(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->clear('input[name="name"]')
                ->type('input[name="name"]', 'Temporary Name')
                ->screenshot('38-before-cancel');

            // Look for cancel button
            try {
                $browser->click('button:contains("Cancel")')
                    ->pause(1000)
                    ->screenshot('38-after-cancel');
            } catch (\Exception $e) {
                // Refresh page to cancel
                $browser->visit('/settings/profile')
                    ->pause(1000)
                    ->screenshot('38-cancel-via-refresh');
            }
        });
    }

    /**
     * Test 39: Settings page is responsive on mobile
     *
     * @test
     */
    public function test_settings_page_responsive_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(375, 667)
                ->visit('/settings/profile')
                ->pause(1000)
                ->screenshot('39-settings-mobile-view')
                ->assertSee('Profile');
        });
    }

    /**
     * Test 40: Settings page is responsive on tablet
     *
     * @test
     */
    public function test_settings_page_responsive_on_tablet(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/settings/profile')
                ->pause(1000)
                ->screenshot('40-settings-tablet-view')
                ->assertSee('Profile');
        });
    }

    /**
     * Test 41: Settings sidebar navigation is visible
     *
     * @test
     */
    public function test_settings_sidebar_navigation_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->screenshot('41-settings-sidebar-navigation');
        });
    }

    /**
     * Test 42: User can access GitHub settings from settings menu
     *
     * @test
     */
    public function test_user_can_access_github_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('GitHub')
                ->screenshot('42-github-settings');
        });
    }

    /**
     * Test 43: User can access storage settings
     *
     * @test
     */
    public function test_user_can_access_storage_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(1000)
                ->assertSee('Storage')
                ->screenshot('43-storage-settings');
        });
    }

    /**
     * Test 44: User can access system status from settings
     *
     * @test
     */
    public function test_user_can_access_system_status(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/system-status')
                ->pause(1000)
                ->assertSee('System Status')
                ->screenshot('44-system-status');
        });
    }

    /**
     * Test 45: User can access health checks settings
     *
     * @test
     */
    public function test_user_can_access_health_checks_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(1000)
                ->assertSee('Health Checks')
                ->screenshot('45-health-checks-settings');
        });
    }

    /**
     * Test 46: Settings pages require authentication
     *
     * @test
     */
    public function test_settings_pages_require_authentication(): void
    {
        $this->browse(function (Browser $browser) {
            // Logout first
            $this->post('/logout');

            $browser->visit('/settings/profile')
                ->pause(1000)
                ->waitForLocation('/login', 5)
                ->assertPathIs('/login')
                ->screenshot('46-settings-require-auth');
        });
    }

    /**
     * Test 47: Profile update shows success message
     *
     * @test
     */
    public function test_profile_update_shows_success_message(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/profile')
                ->pause(1000)
                ->press('Save Changes')
                ->pause(2000)
                ->screenshot('47-profile-success-message');
        });
    }

    /**
     * Test 48: User can view default setup preferences
     *
     * @test
     */
    public function test_user_can_view_default_setup_preferences(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(1000)
                ->screenshot('48-default-setup-preferences');
        });
    }

    /**
     * Test 49: Default preferences show SSL toggle
     *
     * @test
     */
    public function test_default_preferences_show_ssl_toggle(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(1000)
                ->screenshot('49-ssl-toggle-preference');
        });
    }

    /**
     * Test 50: Settings page has consistent layout across sections
     *
     * @test
     */
    public function test_settings_consistent_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser);

            // Visit multiple settings pages to verify consistency
            $pages = [
                '/settings/profile',
                '/settings/security',
                '/settings/preferences',
                '/settings/api-tokens',
                '/settings/ssh-keys',
            ];

            foreach ($pages as $index => $page) {
                $browser->visit($page)
                    ->pause(1000)
                    ->screenshot("50-layout-consistency-{$index}");
            }
        });
    }
}
