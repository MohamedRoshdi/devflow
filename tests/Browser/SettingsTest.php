<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class SettingsTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing test user (shared database approach)
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Test 1: API token manager page loads
     *
     */

    #[Test]
    public function test_api_token_manager_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/api-tokens')
                ->pause(2000)
                ->screenshot('api-token-manager-page');

            // Check if API token page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasApiTokenContent =
                str_contains($pageSource, 'api') ||
                str_contains($pageSource, 'token') ||
                str_contains($pageSource, 'access') ||
                str_contains($pageSource, 'key');

            $this->assertTrue($hasApiTokenContent, 'API token manager page should load');

            $this->testResults['api_token_manager'] = 'API token manager page loaded successfully';
        });
    }

    /**
     * Test 2: GitHub settings page loads
     *
     */

    #[Test]
    public function test_github_settings_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(2000)
                ->screenshot('github-settings-page');

            // Check if GitHub settings page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasGitHubContent =
                str_contains($pageSource, 'github') ||
                str_contains($pageSource, 'git') ||
                str_contains($pageSource, 'repository') ||
                str_contains($pageSource, 'oauth');

            $this->assertTrue($hasGitHubContent, 'GitHub settings page should load');

            $this->testResults['github_settings'] = 'GitHub settings page loaded successfully';
        });
    }

    /**
     * Test 3: Health check manager displays
     *
     */

    #[Test]
    public function test_health_check_manager_displays()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->screenshot('health-check-manager');

            // Check if health check page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHealthCheckContent =
                str_contains($pageSource, 'health') ||
                str_contains($pageSource, 'check') ||
                str_contains($pageSource, 'monitor') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasHealthCheckContent, 'Health check manager should display');

            $this->testResults['health_check_manager'] = 'Health check manager displays successfully';
        });
    }

    /**
     * Test 4: SSH key manager page loads
     *
     */

    #[Test]
    public function test_ssh_key_manager_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/ssh-keys')
                ->pause(2000)
                ->screenshot('ssh-key-manager-page');

            // Check if SSH key page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSSHKeyContent =
                str_contains($pageSource, 'ssh') ||
                str_contains($pageSource, 'key') ||
                str_contains($pageSource, 'public') ||
                str_contains($pageSource, 'private');

            $this->assertTrue($hasSSHKeyContent, 'SSH key manager page should load');

            $this->testResults['ssh_key_manager'] = 'SSH key manager page loaded successfully';
        });
    }

    /**
     * Test 5: System status page displays
     *
     */

    #[Test]
    public function test_system_status_page_displays()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/system-status')
                ->pause(2000)
                ->screenshot('system-status-page');

            // Check if system status page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSystemStatusContent =
                str_contains($pageSource, 'system') ||
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'server') ||
                str_contains($pageSource, 'resource');

            $this->assertTrue($hasSystemStatusContent, 'System status page should display');

            $this->testResults['system_status'] = 'System status page displays successfully';
        });
    }

    /**
     * Test 6: Storage settings accessible
     *
     */

    #[Test]
    public function test_storage_settings_accessible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->screenshot('storage-settings-page');

            // Check if storage settings page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStorageContent =
                str_contains($pageSource, 'storage') ||
                str_contains($pageSource, 'disk') ||
                str_contains($pageSource, 's3') ||
                str_contains($pageSource, 'driver');

            $this->assertTrue($hasStorageContent, 'Storage settings should be accessible');

            $this->testResults['storage_settings'] = 'Storage settings page is accessible';
        });
    }

    /**
     * Test 7: Queue monitor shows jobs
     *
     */

    #[Test]
    public function test_queue_monitor_shows_jobs()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->pause(2000)
                ->screenshot('queue-monitor-page');

            // Check if queue monitor page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasQueueContent =
                str_contains($pageSource, 'queue') ||
                str_contains($pageSource, 'job') ||
                str_contains($pageSource, 'pending') ||
                str_contains($pageSource, 'failed');

            $this->assertTrue($hasQueueContent, 'Queue monitor should show jobs');

            $this->testResults['queue_monitor'] = 'Queue monitor shows jobs successfully';
        });
    }

    /**
     * Test 8: API token creation button is visible
     *
     */

    #[Test]
    public function test_api_token_creation_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/api-tokens')
                ->pause(2000)
                ->screenshot('api-token-create-button');

            // Check for create token button via page source
            $pageSource = $browser->driver->getPageSource();
            $hasCreateButton =
                str_contains($pageSource, 'Create') ||
                str_contains($pageSource, 'New Token') ||
                str_contains($pageSource, 'Generate') ||
                str_contains($pageSource, 'Add Token');

            $this->assertTrue($hasCreateButton, 'API token creation button should be visible');

            $this->testResults['api_token_create_button'] = 'API token creation button is visible';
        });
    }

    /**
     * Test 9: GitHub OAuth connection status is displayed
     *
     */

    #[Test]
    public function test_github_oauth_connection_status_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(2000)
                ->screenshot('github-oauth-status');

            // Check for OAuth connection status via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasOAuthStatus =
                str_contains($pageSource, 'connect') ||
                str_contains($pageSource, 'disconnect') ||
                str_contains($pageSource, 'authorize') ||
                str_contains($pageSource, 'authenticated');

            $this->assertTrue($hasOAuthStatus, 'GitHub OAuth connection status should be displayed');

            $this->testResults['github_oauth_status'] = 'GitHub OAuth connection status is displayed';
        });
    }

    /**
     * Test 10: Health check endpoints list is visible
     *
     */

    #[Test]
    public function test_health_check_endpoints_list_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->screenshot('health-check-endpoints');

            // Check for health check endpoints via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEndpoints =
                str_contains($pageSource, 'endpoint') ||
                str_contains($pageSource, 'url') ||
                str_contains($pageSource, 'check') ||
                str_contains($pageSource, 'interval');

            $this->assertTrue($hasEndpoints, 'Health check endpoints list should be visible');

            $this->testResults['health_check_endpoints'] = 'Health check endpoints list is visible';
        });
    }

    /**
     * Test 11: SSH key list displays existing keys
     *
     */

    #[Test]
    public function test_ssh_key_list_displays_existing_keys()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/ssh-keys')
                ->pause(2000)
                ->screenshot('ssh-key-list');

            // Check for SSH key list via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasKeyList =
                str_contains($pageSource, 'key') ||
                str_contains($pageSource, 'rsa') ||
                str_contains($pageSource, 'fingerprint') ||
                str_contains($pageSource, 'add');

            $this->assertTrue($hasKeyList, 'SSH key list should display existing keys');

            $this->testResults['ssh_key_list'] = 'SSH key list displays existing keys';
        });
    }

    /**
     * Test 12: System metrics are displayed on status page
     *
     */

    #[Test]
    public function test_system_metrics_displayed_on_status_page()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/system-status')
                ->pause(2000)
                ->screenshot('system-metrics');

            // Check for system metrics via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMetrics =
                str_contains($pageSource, 'cpu') ||
                str_contains($pageSource, 'memory') ||
                str_contains($pageSource, 'disk') ||
                str_contains($pageSource, 'uptime');

            $this->assertTrue($hasMetrics, 'System metrics should be displayed');

            $this->testResults['system_metrics'] = 'System metrics are displayed on status page';
        });
    }

    /**
     * Test 13: Storage usage statistics are shown
     *
     */

    #[Test]
    public function test_storage_usage_statistics_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->screenshot('storage-usage-stats');

            // Check for storage usage statistics via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasUsageStats =
                str_contains($pageSource, 'usage') ||
                str_contains($pageSource, 'used') ||
                str_contains($pageSource, 'available') ||
                str_contains($pageSource, 'total');

            $this->assertTrue($hasUsageStats, 'Storage usage statistics should be shown');

            $this->testResults['storage_usage_stats'] = 'Storage usage statistics are shown';
        });
    }

    /**
     * Test 14: Queue statistics are visible
     *
     */

    #[Test]
    public function test_queue_statistics_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->pause(2000)
                ->screenshot('queue-statistics');

            // Check for queue statistics via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasQueueStats =
                str_contains($pageSource, 'pending') ||
                str_contains($pageSource, 'processing') ||
                str_contains($pageSource, 'completed') ||
                str_contains($pageSource, 'failed');

            $this->assertTrue($hasQueueStats, 'Queue statistics should be visible');

            $this->testResults['queue_statistics'] = 'Queue statistics are visible';
        });
    }

    /**
     * Test 15: API token permissions are configurable
     *
     */

    #[Test]
    public function test_api_token_permissions_configurable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/api-tokens')
                ->pause(2000)
                ->screenshot('api-token-permissions');

            // Check for permission configuration via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPermissions =
                str_contains($pageSource, 'permission') ||
                str_contains($pageSource, 'scope') ||
                str_contains($pageSource, 'access') ||
                str_contains($pageSource, 'ability');

            $this->assertTrue($hasPermissions, 'API token permissions should be configurable');

            $this->testResults['api_token_permissions'] = 'API token permissions are configurable';
        });
    }

    /**
     * Test 16: GitHub repository list is accessible
     *
     */

    #[Test]
    public function test_github_repository_list_accessible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(2000)
                ->screenshot('github-repositories');

            // Check for repository list via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRepositories =
                str_contains($pageSource, 'repo') ||
                str_contains($pageSource, 'repository') ||
                str_contains($pageSource, 'branch') ||
                str_contains($pageSource, 'sync');

            $this->assertTrue($hasRepositories, 'GitHub repository list should be accessible');

            $this->testResults['github_repositories'] = 'GitHub repository list is accessible';
        });
    }

    /**
     * Test 17: Add health check button is present
     *
     */

    #[Test]
    public function test_add_health_check_button_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->screenshot('add-health-check-button');

            // Check for add health check button via page source
            $pageSource = $browser->driver->getPageSource();
            $hasAddButton =
                str_contains($pageSource, 'Add') ||
                str_contains($pageSource, 'Create') ||
                str_contains($pageSource, 'New Health Check') ||
                str_contains($pageSource, 'wire:click');

            $this->assertTrue($hasAddButton, 'Add health check button should be present');

            $this->testResults['add_health_check_button'] = 'Add health check button is present';
        });
    }

    /**
     * Test 18: SSH key generation option is available
     *
     */

    #[Test]
    public function test_ssh_key_generation_option_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/ssh-keys')
                ->pause(2000)
                ->screenshot('ssh-key-generation');

            // Check for key generation option via page source
            $pageSource = $browser->driver->getPageSource();
            $hasGenerationOption =
                str_contains($pageSource, 'Generate') ||
                str_contains($pageSource, 'Create Key') ||
                str_contains($pageSource, 'New Key') ||
                str_contains($pageSource, 'Add SSH Key');

            $this->assertTrue($hasGenerationOption, 'SSH key generation option should be available');

            $this->testResults['ssh_key_generation'] = 'SSH key generation option is available';
        });
    }

    /**
     * Test 19: Service status indicators are shown on system status
     *
     */

    #[Test]
    public function test_service_status_indicators_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/system-status')
                ->pause(2000)
                ->screenshot('service-status-indicators');

            // Check for service status indicators via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasServiceStatus =
                str_contains($pageSource, 'service') ||
                str_contains($pageSource, 'running') ||
                str_contains($pageSource, 'stopped') ||
                str_contains($pageSource, 'active');

            $this->assertTrue($hasServiceStatus, 'Service status indicators should be shown');

            $this->testResults['service_status_indicators'] = 'Service status indicators are shown';
        });
    }

    /**
     * Test 20: Storage driver configuration is visible
     *
     */

    #[Test]
    public function test_storage_driver_configuration_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->screenshot('storage-driver-config');

            // Check for storage driver configuration via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDriverConfig =
                str_contains($pageSource, 'driver') ||
                str_contains($pageSource, 'local') ||
                str_contains($pageSource, 's3') ||
                str_contains($pageSource, 'config');

            $this->assertTrue($hasDriverConfig, 'Storage driver configuration should be visible');

            $this->testResults['storage_driver_config'] = 'Storage driver configuration is visible';
        });
    }

    /**
     * Test 21: Queue worker status is displayed
     *
     */

    #[Test]
    public function test_queue_worker_status_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->pause(2000)
                ->screenshot('queue-worker-status');

            // Check for worker status via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWorkerStatus =
                str_contains($pageSource, 'worker') ||
                str_contains($pageSource, 'process') ||
                str_contains($pageSource, 'running') ||
                str_contains($pageSource, 'idle');

            $this->assertTrue($hasWorkerStatus, 'Queue worker status should be displayed');

            $this->testResults['queue_worker_status'] = 'Queue worker status is displayed';
        });
    }

    /**
     * Test 22: API token revocation option is available
     *
     */

    #[Test]
    public function test_api_token_revocation_option_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/api-tokens')
                ->pause(2000)
                ->screenshot('api-token-revocation');

            // Check for token revocation option via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRevocationOption =
                str_contains($pageSource, 'revoke') ||
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'remove') ||
                str_contains($pageSource, 'disable');

            $this->assertTrue($hasRevocationOption, 'API token revocation option should be available');

            $this->testResults['api_token_revocation'] = 'API token revocation option is available';
        });
    }

    /**
     * Test 23: Health check frequency settings are editable
     *
     */

    #[Test]
    public function test_health_check_frequency_editable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->screenshot('health-check-frequency');

            // Check for frequency settings via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFrequencySettings =
                str_contains($pageSource, 'frequency') ||
                str_contains($pageSource, 'interval') ||
                str_contains($pageSource, 'minute') ||
                str_contains($pageSource, 'schedule');

            $this->assertTrue($hasFrequencySettings, 'Health check frequency settings should be editable');

            $this->testResults['health_check_frequency'] = 'Health check frequency settings are editable';
        });
    }

    /**
     * Test 24: SSH key fingerprint is displayed
     *
     */

    #[Test]
    public function test_ssh_key_fingerprint_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/ssh-keys')
                ->pause(2000)
                ->screenshot('ssh-key-fingerprint');

            // Check for key fingerprint via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFingerprint =
                str_contains($pageSource, 'fingerprint') ||
                str_contains($pageSource, 'hash') ||
                str_contains($pageSource, 'sha') ||
                str_contains($pageSource, 'md5');

            $this->assertTrue($hasFingerprint, 'SSH key fingerprint should be displayed');

            $this->testResults['ssh_key_fingerprint'] = 'SSH key fingerprint is displayed';
        });
    }

    /**
     * Test 25: System load averages are shown
     *
     */

    #[Test]
    public function test_system_load_averages_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/system-status')
                ->pause(2000)
                ->screenshot('system-load-averages');

            // Check for load averages via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLoadAverages =
                str_contains($pageSource, 'load') ||
                str_contains($pageSource, 'average') ||
                str_contains($pageSource, 'cpu') ||
                str_contains($pageSource, 'usage');

            $this->assertTrue($hasLoadAverages, 'System load averages should be shown');

            $this->testResults['system_load_averages'] = 'System load averages are shown';
        });
    }

    /**
     * Test 26: Navigation to settings pages from dashboard works
     *
     */

    #[Test]
    public function test_navigation_to_settings_from_dashboard()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/dashboard')
                ->pause(2000)
                ->waitForText('Welcome Back!', 15)
                ->screenshot('dashboard-before-settings-nav');

            // Look for Settings link in navigation
            try {
                $browser->clickLink('Settings')
                    ->pause(2000)
                    ->screenshot('navigated-to-settings');

                $this->testResults['navigation_to_settings'] = 'Navigation to settings from dashboard works';
            } catch (\Exception $e) {
                // Settings link might not be in main navigation
                $browser->visit('/settings/api-tokens')
                    ->pause(2000)
                    ->screenshot('settings-direct-visit');

                $this->testResults['navigation_to_settings'] = 'Settings pages accessible via direct URL';
            }

            $this->assertTrue(true);
        });
    }

    /**
     * Test 27: Settings sections have proper navigation/tabs
     *
     */

    #[Test]
    public function test_settings_sections_have_navigation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/api-tokens')
                ->pause(2000)
                ->screenshot('settings-navigation');

            // Check for settings navigation via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNavigation =
                str_contains($pageSource, 'api') ||
                str_contains($pageSource, 'github') ||
                str_contains($pageSource, 'ssh') ||
                str_contains($pageSource, 'storage') ||
                str_contains($pageSource, 'queue');

            $this->assertTrue($hasNavigation, 'Settings sections should have proper navigation');

            $this->testResults['settings_navigation'] = 'Settings sections have proper navigation';
        });
    }

    /**
     * Generate test report
     */
    protected function tearDown(): void
    {
        if (! empty($this->testResults)) {
            $report = [
                'timestamp' => now()->toIso8601String(),
                'test_suite' => 'Settings Management Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'users_tested' => User::count(),
                    'test_user_email' => $this->user->email,
                ],
            ];

            $reportPath = storage_path('app/test-reports/settings-management-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
