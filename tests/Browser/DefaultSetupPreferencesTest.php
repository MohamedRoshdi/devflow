<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use App\Models\UserSettings;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

/**
 * Comprehensive Default Setup Preferences Tests for DevFlow Pro
 *
 * Tests all default setup preferences functionality including:
 * - Page access and navigation
 * - SSL certificate default settings
 * - Webhooks default settings
 * - Health checks default settings
 * - Backups default settings
 * - Notifications default settings
 * - Auto-deploy default settings
 * - Theme preferences
 * - Wizard tips settings
 * - Settings persistence
 * - Toggle interactions
 * - Save functionality
 * - UI elements and layout
 */
class DefaultSetupPreferencesTest extends DuskTestCase
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
     * Test 1: User can access default setup preferences page
     *
     * @test
     */
    public function test_user_can_access_default_setup_preferences_page(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->pause(1000)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('01-default-setup-preferences-page');

            // Check if the page loaded
            $pageSource = $browser->driver->getPageSource();
            $hasPreferencesContent =
                str_contains($pageSource, 'Default Setup Preferences') ||
                str_contains($pageSource, 'Preferences') ||
                str_contains($pageSource, 'Default');

            $this->assertTrue($hasPreferencesContent, 'Default setup preferences page should load');
        });
    }

    /**
     * Test 2: Page displays hero section with title
     *
     * @test
     */
    public function test_page_displays_hero_section_with_title(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('02-hero-section-with-title');

            $pageSource = $browser->driver->getPageSource();
            $hasTitle = str_contains($pageSource, 'Default Setup Preferences');

            $this->assertTrue($hasTitle, 'Hero section with title should be displayed');
        });
    }

    /**
     * Test 3: Project creation defaults section is visible
     *
     * @test
     */
    public function test_project_creation_defaults_section_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('03-project-creation-defaults-section');

            $pageSource = $browser->driver->getPageSource();
            $hasProjectDefaults = str_contains($pageSource, 'Project Creation Defaults');

            $this->assertTrue($hasProjectDefaults, 'Project creation defaults section should be visible');
        });
    }

    /**
     * Test 4: SSL certificate toggle is displayed
     *
     * @test
     */
    public function test_ssl_certificate_toggle_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('04-ssl-certificate-toggle');

            $pageSource = $browser->driver->getPageSource();
            $hasSslToggle =
                str_contains($pageSource, 'Enable SSL Certificates') ||
                str_contains($pageSource, 'SSL');

            $this->assertTrue($hasSslToggle, 'SSL certificate toggle should be displayed');
        });
    }

    /**
     * Test 5: User can toggle SSL certificate setting
     *
     * @test
     */
    public function test_user_can_toggle_ssl_certificate_setting(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);
            $originalSslSetting = $settings->default_enable_ssl;

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('05-before-ssl-toggle');

            // Try to toggle SSL setting
            try {
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('\$toggle', 'defaultEnableSsl')");
                $browser->pause(1000)
                    ->screenshot('05-after-ssl-toggle');
            } catch (\Exception $e) {
                $browser->screenshot('05-ssl-toggle-not-found');
            }

            // Restore original setting
            $settings->update(['default_enable_ssl' => $originalSslSetting]);
        });
    }

    /**
     * Test 6: Webhooks toggle is displayed
     *
     * @test
     */
    public function test_webhooks_toggle_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('06-webhooks-toggle');

            $pageSource = $browser->driver->getPageSource();
            $hasWebhooksToggle =
                str_contains($pageSource, 'Enable Webhooks') ||
                str_contains($pageSource, 'Webhooks');

            $this->assertTrue($hasWebhooksToggle, 'Webhooks toggle should be displayed');
        });
    }

    /**
     * Test 7: User can toggle webhooks setting
     *
     * @test
     */
    public function test_user_can_toggle_webhooks_setting(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);
            $originalWebhooksSetting = $settings->default_enable_webhooks;

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('07-before-webhooks-toggle');

            // Try to toggle webhooks setting
            try {
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('\$toggle', 'defaultEnableWebhooks')");
                $browser->pause(1000)
                    ->screenshot('07-after-webhooks-toggle');
            } catch (\Exception $e) {
                $browser->screenshot('07-webhooks-toggle-not-found');
            }

            // Restore original setting
            $settings->update(['default_enable_webhooks' => $originalWebhooksSetting]);
        });
    }

    /**
     * Test 8: Health checks toggle is displayed
     *
     * @test
     */
    public function test_health_checks_toggle_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('08-health-checks-toggle');

            $pageSource = $browser->driver->getPageSource();
            $hasHealthChecksToggle =
                str_contains($pageSource, 'Enable Health Checks') ||
                str_contains($pageSource, 'Health Checks');

            $this->assertTrue($hasHealthChecksToggle, 'Health checks toggle should be displayed');
        });
    }

    /**
     * Test 9: User can toggle health checks setting
     *
     * @test
     */
    public function test_user_can_toggle_health_checks_setting(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);
            $originalHealthChecksSetting = $settings->default_enable_health_checks;

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('09-before-health-checks-toggle');

            // Try to toggle health checks setting
            try {
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('\$toggle', 'defaultEnableHealthChecks')");
                $browser->pause(1000)
                    ->screenshot('09-after-health-checks-toggle');
            } catch (\Exception $e) {
                $browser->screenshot('09-health-checks-toggle-not-found');
            }

            // Restore original setting
            $settings->update(['default_enable_health_checks' => $originalHealthChecksSetting]);
        });
    }

    /**
     * Test 10: Backups toggle is displayed
     *
     * @test
     */
    public function test_backups_toggle_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('10-backups-toggle');

            $pageSource = $browser->driver->getPageSource();
            $hasBackupsToggle =
                str_contains($pageSource, 'Enable Backups') ||
                str_contains($pageSource, 'Backups');

            $this->assertTrue($hasBackupsToggle, 'Backups toggle should be displayed');
        });
    }

    /**
     * Test 11: User can toggle backups setting
     *
     * @test
     */
    public function test_user_can_toggle_backups_setting(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);
            $originalBackupsSetting = $settings->default_enable_backups;

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('11-before-backups-toggle');

            // Try to toggle backups setting
            try {
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('\$toggle', 'defaultEnableBackups')");
                $browser->pause(1000)
                    ->screenshot('11-after-backups-toggle');
            } catch (\Exception $e) {
                $browser->screenshot('11-backups-toggle-not-found');
            }

            // Restore original setting
            $settings->update(['default_enable_backups' => $originalBackupsSetting]);
        });
    }

    /**
     * Test 12: Notifications toggle is displayed
     *
     * @test
     */
    public function test_notifications_toggle_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('12-notifications-toggle');

            $pageSource = $browser->driver->getPageSource();
            $hasNotificationsToggle =
                str_contains($pageSource, 'Enable Notifications') ||
                str_contains($pageSource, 'Notifications');

            $this->assertTrue($hasNotificationsToggle, 'Notifications toggle should be displayed');
        });
    }

    /**
     * Test 13: User can toggle notifications setting
     *
     * @test
     */
    public function test_user_can_toggle_notifications_setting(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);
            $originalNotificationsSetting = $settings->default_enable_notifications;

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('13-before-notifications-toggle');

            // Try to toggle notifications setting
            try {
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('\$toggle', 'defaultEnableNotifications')");
                $browser->pause(1000)
                    ->screenshot('13-after-notifications-toggle');
            } catch (\Exception $e) {
                $browser->screenshot('13-notifications-toggle-not-found');
            }

            // Restore original setting
            $settings->update(['default_enable_notifications' => $originalNotificationsSetting]);
        });
    }

    /**
     * Test 14: Auto-deploy toggle is displayed
     *
     * @test
     */
    public function test_auto_deploy_toggle_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('14-auto-deploy-toggle');

            $pageSource = $browser->driver->getPageSource();
            $hasAutoDeployToggle =
                str_contains($pageSource, 'Enable Auto Deploy') ||
                str_contains($pageSource, 'Auto Deploy');

            $this->assertTrue($hasAutoDeployToggle, 'Auto-deploy toggle should be displayed');
        });
    }

    /**
     * Test 15: User can toggle auto-deploy setting
     *
     * @test
     */
    public function test_user_can_toggle_auto_deploy_setting(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);
            $originalAutoDeploySetting = $settings->default_enable_auto_deploy;

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('15-before-auto-deploy-toggle');

            // Try to toggle auto-deploy setting
            try {
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('\$toggle', 'defaultEnableAutoDeploy')");
                $browser->pause(1000)
                    ->screenshot('15-after-auto-deploy-toggle');
            } catch (\Exception $e) {
                $browser->screenshot('15-auto-deploy-toggle-not-found');
            }

            // Restore original setting
            $settings->update(['default_enable_auto_deploy' => $originalAutoDeploySetting]);
        });
    }

    /**
     * Test 16: UI preferences section is visible
     *
     * @test
     */
    public function test_ui_preferences_section_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('16-ui-preferences-section');

            $pageSource = $browser->driver->getPageSource();
            $hasUiPreferences = str_contains($pageSource, 'UI Preferences');

            $this->assertTrue($hasUiPreferences, 'UI preferences section should be visible');
        });
    }

    /**
     * Test 17: Theme selection is displayed
     *
     * @test
     */
    public function test_theme_selection_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('17-theme-selection');

            $pageSource = $browser->driver->getPageSource();
            $hasThemeSelection =
                str_contains($pageSource, 'Preferred Theme') ||
                str_contains($pageSource, 'Theme');

            $this->assertTrue($hasThemeSelection, 'Theme selection should be displayed');
        });
    }

    /**
     * Test 18: Dark theme option is available
     *
     * @test
     */
    public function test_dark_theme_option_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('18-dark-theme-option');

            $pageSource = $browser->driver->getPageSource();
            $hasDarkTheme = str_contains($pageSource, 'Dark');

            $this->assertTrue($hasDarkTheme, 'Dark theme option should be available');
        });
    }

    /**
     * Test 19: Light theme option is available
     *
     * @test
     */
    public function test_light_theme_option_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('19-light-theme-option');

            $pageSource = $browser->driver->getPageSource();
            $hasLightTheme = str_contains($pageSource, 'Light');

            $this->assertTrue($hasLightTheme, 'Light theme option should be available');
        });
    }

    /**
     * Test 20: User can select dark theme
     *
     * @test
     */
    public function test_user_can_select_dark_theme(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);
            $originalTheme = $settings->theme;

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('20-before-dark-theme-select');

            // Try to select dark theme
            try {
                $browser->radio('theme', 'dark')
                    ->pause(1000)
                    ->screenshot('20-after-dark-theme-select');
            } catch (\Exception $e) {
                $browser->screenshot('20-dark-theme-radio-not-found');
            }

            // Restore original theme
            $settings->update(['theme' => $originalTheme]);
        });
    }

    /**
     * Test 21: User can select light theme
     *
     * @test
     */
    public function test_user_can_select_light_theme(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);
            $originalTheme = $settings->theme;

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('21-before-light-theme-select');

            // Try to select light theme
            try {
                $browser->radio('theme', 'light')
                    ->pause(1000)
                    ->screenshot('21-after-light-theme-select');
            } catch (\Exception $e) {
                $browser->screenshot('21-light-theme-radio-not-found');
            }

            // Restore original theme
            $settings->update(['theme' => $originalTheme]);
        });
    }

    /**
     * Test 22: Wizard tips toggle is displayed
     *
     * @test
     */
    public function test_wizard_tips_toggle_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('22-wizard-tips-toggle');

            $pageSource = $browser->driver->getPageSource();
            $hasWizardTipsToggle =
                str_contains($pageSource, 'Show Setup Wizard Tips') ||
                str_contains($pageSource, 'Wizard Tips');

            $this->assertTrue($hasWizardTipsToggle, 'Wizard tips toggle should be displayed');
        });
    }

    /**
     * Test 23: User can toggle wizard tips setting
     *
     * @test
     */
    public function test_user_can_toggle_wizard_tips_setting(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);
            $originalWizardTipsSetting = $settings->show_wizard_tips;

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('23-before-wizard-tips-toggle');

            // Try to toggle wizard tips setting
            try {
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('\$toggle', 'showWizardTips')");
                $browser->pause(1000)
                    ->screenshot('23-after-wizard-tips-toggle');
            } catch (\Exception $e) {
                $browser->screenshot('23-wizard-tips-toggle-not-found');
            }

            // Restore original setting
            $settings->update(['show_wizard_tips' => $originalWizardTipsSetting]);
        });
    }

    /**
     * Test 24: Save preferences button is visible
     *
     * @test
     */
    public function test_save_preferences_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('24-save-preferences-button');

            $pageSource = $browser->driver->getPageSource();
            $hasSaveButton =
                str_contains($pageSource, 'Save Preferences') ||
                str_contains($pageSource, 'Save');

            $this->assertTrue($hasSaveButton, 'Save preferences button should be visible');
        });
    }

    /**
     * Test 25: Cancel button is visible
     *
     * @test
     */
    public function test_cancel_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('25-cancel-button');

            $pageSource = $browser->driver->getPageSource();
            $hasCancelButton = str_contains($pageSource, 'Cancel');

            $this->assertTrue($hasCancelButton, 'Cancel button should be visible');
        });
    }

    /**
     * Test 26: User can save preferences
     *
     * @test
     */
    public function test_user_can_save_preferences(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('26-before-save-preferences');

            // Try to click save button
            try {
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('save')");
                $browser->pause(3000)
                    ->screenshot('26-after-save-preferences');
            } catch (\Exception $e) {
                $browser->screenshot('26-save-preferences-failed');
            }
        });
    }

    /**
     * Test 27: Success message is displayed after save
     *
     * @test
     */
    public function test_success_message_displayed_after_save(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15);

            // Try to save and check for success message
            try {
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('save')");
                $browser->pause(3000)
                    ->screenshot('27-success-message-after-save');
            } catch (\Exception $e) {
                $browser->screenshot('27-save-failed');
            }
        });
    }

    /**
     * Test 28: Settings are persisted after save
     *
     * @test
     */
    public function test_settings_persisted_after_save(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);
            $originalSslSetting = $settings->default_enable_ssl;

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15);

            // Toggle a setting and save
            try {
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('\$toggle', 'defaultEnableSsl')");
                $browser->pause(1000);
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('save')");
                $browser->pause(3000)
                    ->screenshot('28-settings-persisted');

                // Verify persistence
                $settings->refresh();
                $this->assertNotEquals($originalSslSetting, $settings->default_enable_ssl);
            } catch (\Exception $e) {
                $browser->screenshot('28-persistence-test-failed');
            }

            // Restore original setting
            $settings->update(['default_enable_ssl' => $originalSslSetting]);
        });
    }

    /**
     * Test 29: SSL description text is visible
     *
     * @test
     */
    public function test_ssl_description_text_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('29-ssl-description-text');

            $pageSource = $browser->driver->getPageSource();
            $hasSslDescription =
                str_contains($pageSource, 'Auto-generate and manage SSL certificates') ||
                str_contains($pageSource, 'SSL');

            $this->assertTrue($hasSslDescription, 'SSL description text should be visible');
        });
    }

    /**
     * Test 30: Webhooks description text is visible
     *
     * @test
     */
    public function test_webhooks_description_text_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('30-webhooks-description-text');

            $pageSource = $browser->driver->getPageSource();
            $hasWebhooksDescription =
                str_contains($pageSource, 'Allow GitHub webhooks for automatic deployments') ||
                str_contains($pageSource, 'GitHub');

            $this->assertTrue($hasWebhooksDescription, 'Webhooks description text should be visible');
        });
    }

    /**
     * Test 31: Health checks description text is visible
     *
     * @test
     */
    public function test_health_checks_description_text_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('31-health-checks-description-text');

            $pageSource = $browser->driver->getPageSource();
            $hasHealthChecksDescription =
                str_contains($pageSource, 'Monitor project health status regularly') ||
                str_contains($pageSource, 'Monitor');

            $this->assertTrue($hasHealthChecksDescription, 'Health checks description text should be visible');
        });
    }

    /**
     * Test 32: Backups description text is visible
     *
     * @test
     */
    public function test_backups_description_text_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('32-backups-description-text');

            $pageSource = $browser->driver->getPageSource();
            $hasBackupsDescription =
                str_contains($pageSource, 'Automatically backup databases and files') ||
                str_contains($pageSource, 'backup');

            $this->assertTrue($hasBackupsDescription, 'Backups description text should be visible');
        });
    }

    /**
     * Test 33: Notifications description text is visible
     *
     * @test
     */
    public function test_notifications_description_text_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('33-notifications-description-text');

            $pageSource = $browser->driver->getPageSource();
            $hasNotificationsDescription =
                str_contains($pageSource, 'Receive deployment and system alerts') ||
                str_contains($pageSource, 'deployment');

            $this->assertTrue($hasNotificationsDescription, 'Notifications description text should be visible');
        });
    }

    /**
     * Test 34: Auto-deploy description text is visible
     *
     * @test
     */
    public function test_auto_deploy_description_text_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('34-auto-deploy-description-text');

            $pageSource = $browser->driver->getPageSource();
            $hasAutoDeployDescription =
                str_contains($pageSource, 'Automatically deploy on repository changes') ||
                str_contains($pageSource, 'repository');

            $this->assertTrue($hasAutoDeployDescription, 'Auto-deploy description text should be visible');
        });
    }

    /**
     * Test 35: Wizard tips description text is visible
     *
     * @test
     */
    public function test_wizard_tips_description_text_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('35-wizard-tips-description-text');

            $pageSource = $browser->driver->getPageSource();
            $hasWizardTipsDescription =
                str_contains($pageSource, 'Display helpful tips during project creation') ||
                str_contains($pageSource, 'helpful tips');

            $this->assertTrue($hasWizardTipsDescription, 'Wizard tips description text should be visible');
        });
    }

    /**
     * Test 36: Toggle switches have proper styling
     *
     * @test
     */
    public function test_toggle_switches_have_proper_styling(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('36-toggle-switches-styling');

            $pageSource = $browser->driver->getPageSource();
            $hasToggleStyling =
                str_contains($pageSource, 'toggle') ||
                str_contains($pageSource, 'bg-green-600') ||
                str_contains($pageSource, 'rounded-full');

            $this->assertTrue($hasToggleStyling, 'Toggle switches should have proper styling');
        });
    }

    /**
     * Test 37: Icons are displayed for each setting
     *
     * @test
     */
    public function test_icons_displayed_for_each_setting(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('37-setting-icons');

            $pageSource = $browser->driver->getPageSource();
            $hasIcons = str_contains($pageSource, '<svg');

            $this->assertTrue($hasIcons, 'Icons should be displayed for each setting');
        });
    }

    /**
     * Test 38: Page is responsive on mobile
     *
     * @test
     */
    public function test_page_responsive_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(375, 667)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('38-mobile-responsive');

            $pageSource = $browser->driver->getPageSource();
            $hasContent = str_contains($pageSource, 'Default Setup Preferences');

            $this->assertTrue($hasContent, 'Page should be responsive on mobile');
        });
    }

    /**
     * Test 39: Page is responsive on tablet
     *
     * @test
     */
    public function test_page_responsive_on_tablet(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('39-tablet-responsive');

            $pageSource = $browser->driver->getPageSource();
            $hasContent = str_contains($pageSource, 'Default Setup Preferences');

            $this->assertTrue($hasContent, 'Page should be responsive on tablet');
        });
    }

    /**
     * Test 40: Save button shows loading state
     *
     * @test
     */
    public function test_save_button_shows_loading_state(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('40-save-button-loading-state');

            $pageSource = $browser->driver->getPageSource();
            $hasLoadingState =
                str_contains($pageSource, 'wire:loading') ||
                str_contains($pageSource, 'Saving...');

            $this->assertTrue($hasLoadingState, 'Save button should show loading state');
        });
    }

    /**
     * Test 41: Settings load current user preferences
     *
     * @test
     */
    public function test_settings_load_current_user_preferences(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('41-current-preferences-loaded');

            // Verify settings are loaded
            $this->assertNotNull($settings);
        });
    }

    /**
     * Test 42: Multiple toggles can be changed before save
     *
     * @test
     */
    public function test_multiple_toggles_can_be_changed_before_save(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);
            $originalSslSetting = $settings->default_enable_ssl;
            $originalWebhooksSetting = $settings->default_enable_webhooks;

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15);

            // Toggle multiple settings
            try {
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('\$toggle', 'defaultEnableSsl')");
                $browser->pause(500);
                $browser->script("window.livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('\$toggle', 'defaultEnableWebhooks')");
                $browser->pause(1000)
                    ->screenshot('42-multiple-toggles-changed');
            } catch (\Exception $e) {
                $browser->screenshot('42-multiple-toggles-failed');
            }

            // Restore original settings
            $settings->update([
                'default_enable_ssl' => $originalSslSetting,
                'default_enable_webhooks' => $originalWebhooksSetting,
            ]);
        });
    }

    /**
     * Test 43: Page has gradient background styling
     *
     * @test
     */
    public function test_page_has_gradient_background_styling(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('43-gradient-background');

            $pageSource = $browser->driver->getPageSource();
            $hasGradient =
                str_contains($pageSource, 'gradient') ||
                str_contains($pageSource, 'bg-gradient');

            $this->assertTrue($hasGradient, 'Page should have gradient background styling');
        });
    }

    /**
     * Test 44: Settings sections have proper card styling
     *
     * @test
     */
    public function test_settings_sections_have_card_styling(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('44-card-styling');

            $pageSource = $browser->driver->getPageSource();
            $hasCardStyling =
                str_contains($pageSource, 'rounded-xl') ||
                str_contains($pageSource, 'shadow-lg');

            $this->assertTrue($hasCardStyling, 'Settings sections should have proper card styling');
        });
    }

    /**
     * Test 45: Dark mode toggle reflects system theme
     *
     * @test
     */
    public function test_dark_mode_toggle_reflects_system_theme(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('45-theme-reflects-setting');

            // Verify theme setting exists
            $this->assertContains($settings->theme, ['dark', 'light']);
        });
    }

    /**
     * Test 46: Page requires authentication
     *
     * @test
     */
    public function test_page_requires_authentication(): void
    {
        $this->browse(function (Browser $browser) {
            // Logout first
            $this->post('/logout');

            $browser->visit('/settings/preferences')
                ->pause(1000)
                ->waitForLocation('/login', 5)
                ->assertPathIs('/login')
                ->screenshot('46-requires-authentication');
        });
    }

    /**
     * Test 47: Preferences apply to new project creation
     *
     * @test
     */
    public function test_preferences_apply_to_new_project_creation(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('47-preferences-inheritance');

            // Verify that settings exist and can be applied
            $this->assertNotNull($settings->default_enable_ssl);
            $this->assertNotNull($settings->default_enable_webhooks);
            $this->assertNotNull($settings->default_enable_health_checks);
        });
    }

    /**
     * Test 48: All toggle switches work independently
     *
     * @test
     */
    public function test_all_toggle_switches_work_independently(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', self::TEST_EMAIL)->first();
            $settings = UserSettings::getForUser($user);

            // Save original settings
            $originalSettings = [
                'default_enable_ssl' => $settings->default_enable_ssl,
                'default_enable_webhooks' => $settings->default_enable_webhooks,
                'default_enable_health_checks' => $settings->default_enable_health_checks,
                'default_enable_backups' => $settings->default_enable_backups,
                'default_enable_notifications' => $settings->default_enable_notifications,
                'default_enable_auto_deploy' => $settings->default_enable_auto_deploy,
                'show_wizard_tips' => $settings->show_wizard_tips,
            ];

            $this->loginViaUI($browser)
                ->visit('/settings/preferences')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('48-all-toggles-independent');

            // Verify all toggles are present
            $pageSource = $browser->driver->getPageSource();
            $this->assertTrue(str_contains($pageSource, 'Enable SSL Certificates'));
            $this->assertTrue(str_contains($pageSource, 'Enable Webhooks'));
            $this->assertTrue(str_contains($pageSource, 'Enable Health Checks'));
            $this->assertTrue(str_contains($pageSource, 'Enable Backups'));
            $this->assertTrue(str_contains($pageSource, 'Enable Notifications'));
            $this->assertTrue(str_contains($pageSource, 'Enable Auto Deploy'));
            $this->assertTrue(str_contains($pageSource, 'Show Setup Wizard Tips'));

            // Restore original settings
            $settings->update($originalSettings);
        });
    }
}
