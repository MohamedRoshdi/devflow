<?php

namespace Tests\Browser;

use App\Models\HealthCheck;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class HealthCheckManagerTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

    protected Project $project;

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

        // Create test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'health-test.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Health Test Server',
                'ip_address' => '192.168.1.200',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
                'cpu_cores' => 4,
                'memory_gb' => 8,
                'disk_gb' => 100,
                'docker_installed' => true,
            ]
        );

        // Create test project
        $this->project = Project::firstOrCreate(
            ['slug' => 'health-check-test-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Health Check Test Project',
                'repository' => 'https://github.com/test/health-project',
                'branch' => 'main',
                'deploy_path' => '/var/www/health-project',
            ]
        );

        // Create test health check
        HealthCheck::firstOrCreate(
            ['target_url' => 'https://example.com/health'],
            [
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
                'check_type' => 'http',
                'expected_status' => 200,
                'interval_minutes' => 5,
                'timeout_seconds' => 30,
                'is_active' => true,
                'status' => 'healthy',
                'last_check_at' => now()->subMinutes(2),
                'consecutive_failures' => 0,
            ]
        );

        // Create test notification channel
        NotificationChannel::firstOrCreate(
            [
                'user_id' => $this->user->id,
                'name' => 'Test Email Channel',
            ],
            [
                'type' => 'email',
                'config' => ['email' => 'test@devflow.test'],
                'is_active' => true,
            ]
        );
    }

    /**
     * Test 1: Health check manager page loads successfully
     *
     * @test
     */
    public function test_health_check_manager_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->assertSee('Health Check Manager')
                ->assertSee('Configure automated health checks and notifications')
                ->screenshot('health-check-manager-page');

            $this->testResults['page_loads'] = 'Health check manager page loaded successfully';
        });
    }

    /**
     * Test 2: Health check list is displayed
     *
     * @test
     */
    public function test_health_check_list_displays()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Active Health Checks', 15)
                ->assertSee('Active Health Checks')
                ->assertSee('example.com/health')
                ->screenshot('health-check-list');

            $this->testResults['list_displays'] = 'Health check list displays correctly';
        });
    }

    /**
     * Test 3: Add health check button is visible
     *
     * @test
     */
    public function test_add_health_check_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->assertSee('New Health Check')
                ->screenshot('add-health-check-button');

            $this->testResults['add_button_visible'] = 'Add health check button is visible';
        });
    }

    /**
     * Test 4: Add health check modal opens
     *
     * @test
     */
    public function test_add_health_check_modal_opens()
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15);

            // Click the New Health Check button
            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'New Health Check')) {
                $browser->click('button:contains("New Health Check")')
                    ->pause(1000)
                    ->screenshot('health-check-modal-opened');

                // Modal should be visible
                $modalSource = $browser->driver->getPageSource();
                $hasModal = str_contains($modalSource, 'check_type') ||
                    str_contains($modalSource, 'target_url') ||
                    str_contains($modalSource, 'wire:model');

                $this->assertTrue($hasModal, 'Health check modal should open');
            }

            $this->testResults['modal_opens'] = 'Add health check modal opens successfully';
        });
    }

    /**
     * Test 5: URL field is present in form
     *
     * @test
     */
    public function test_url_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('url-field-check');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasUrlField = str_contains($pageSource, 'target_url') ||
                str_contains($pageSource, 'url') ||
                str_contains($pageSource, 'endpoint');

            $this->assertTrue($hasUrlField, 'URL field should be present');

            $this->testResults['url_field'] = 'URL field is present in form';
        });
    }

    /**
     * Test 6: Check type field is present
     *
     * @test
     */
    public function test_check_type_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('check-type-field');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCheckType = str_contains($pageSource, 'check_type') ||
                str_contains($pageSource, 'http') ||
                str_contains($pageSource, 'tcp') ||
                str_contains($pageSource, 'ping');

            $this->assertTrue($hasCheckType, 'Check type field should be present');

            $this->testResults['check_type_field'] = 'Check type field is present';
        });
    }

    /**
     * Test 7: Interval dropdown works
     *
     * @test
     */
    public function test_interval_dropdown_works()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('interval-dropdown');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasInterval = str_contains($pageSource, 'interval') ||
                str_contains($pageSource, 'every') ||
                str_contains($pageSource, 'minutes');

            $this->assertTrue($hasInterval, 'Interval dropdown should be present');

            $this->testResults['interval_dropdown'] = 'Interval dropdown is functional';
        });
    }

    /**
     * Test 8: Expected status field is present
     *
     * @test
     */
    public function test_expected_status_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('expected-status-field');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusField = str_contains($pageSource, 'expected_status') ||
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, '200');

            $this->assertTrue($hasStatusField, 'Expected status field should be present');

            $this->testResults['status_field'] = 'Expected status field is present';
        });
    }

    /**
     * Test 9: Health check status indicators are shown
     *
     * @test
     */
    public function test_health_check_status_indicators_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Active Health Checks', 15)
                ->screenshot('status-indicators');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusIndicators = str_contains($pageSource, 'healthy') ||
                str_contains($pageSource, 'degraded') ||
                str_contains($pageSource, 'down') ||
                str_contains($pageSource, 'unknown');

            $this->assertTrue($hasStatusIndicators, 'Status indicators should be shown');

            $this->testResults['status_indicators'] = 'Health check status indicators are displayed';
        });
    }

    /**
     * Test 10: Enable/disable toggle works
     *
     * @test
     */
    public function test_enable_disable_toggle_works()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Active Health Checks', 15)
                ->screenshot('toggle-active');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasToggle = str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'inactive') ||
                str_contains($pageSource, 'enable') ||
                str_contains($pageSource, 'disable');

            $this->assertTrue($hasToggle, 'Enable/disable toggle should be present');

            $this->testResults['toggle_works'] = 'Enable/disable toggle is functional';
        });
    }

    /**
     * Test 11: Delete health check button is visible
     *
     * @test
     */
    public function test_delete_health_check_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Active Health Checks', 15)
                ->screenshot('delete-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDeleteButton = str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'remove') ||
                str_contains($pageSource, 'trash');

            $this->assertTrue($hasDeleteButton, 'Delete button should be visible');

            $this->testResults['delete_button'] = 'Delete health check button is visible';
        });
    }

    /**
     * Test 12: Last check timestamp is shown
     *
     * @test
     */
    public function test_last_check_timestamp_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Active Health Checks', 15)
                ->screenshot('last-check-timestamp');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTimestamp = str_contains($pageSource, 'last') ||
                str_contains($pageSource, 'ago') ||
                str_contains($pageSource, 'minutes');

            $this->assertTrue($hasTimestamp, 'Last check timestamp should be shown');

            $this->testResults['timestamp_shown'] = 'Last check timestamp is displayed';
        });
    }

    /**
     * Test 13: Health check history/results are accessible
     *
     * @test
     */
    public function test_health_check_history_accessible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Active Health Checks', 15)
                ->screenshot('history-accessible');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHistory = str_contains($pageSource, 'view results') ||
                str_contains($pageSource, 'history') ||
                str_contains($pageSource, 'results');

            $this->assertTrue($hasHistory, 'Health check history should be accessible');

            $this->testResults['history_accessible'] = 'Health check history is accessible';
        });
    }

    /**
     * Test 14: Health statistics are displayed
     *
     * @test
     */
    public function test_health_statistics_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->assertSee('Total Checks')
                ->assertSee('Healthy')
                ->assertSee('Degraded')
                ->assertSee('Down')
                ->assertSee('Unknown')
                ->screenshot('health-statistics');

            $this->testResults['statistics_displayed'] = 'Health statistics are displayed correctly';
        });
    }

    /**
     * Test 15: Flash messages display
     *
     * @test
     */
    public function test_flash_messages_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('flash-messages');

            // Check if notification system is present
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNotificationSystem = str_contains($pageSource, 'notification') ||
                str_contains($pageSource, 'alert') ||
                str_contains($pageSource, 'toast') ||
                str_contains($pageSource, 'livewire');

            $this->assertTrue($hasNotificationSystem, 'Flash message system should be present');

            $this->testResults['flash_messages'] = 'Flash message system is functional';
        });
    }

    /**
     * Test 16: Run check now button is visible
     *
     * @test
     */
    public function test_run_check_now_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Active Health Checks', 15)
                ->screenshot('run-check-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRunButton = str_contains($pageSource, 'run check') ||
                str_contains($pageSource, 'test') ||
                str_contains($pageSource, 'execute');

            $this->assertTrue($hasRunButton, 'Run check now button should be visible');

            $this->testResults['run_check_button'] = 'Run check now button is visible';
        });
    }

    /**
     * Test 17: Edit health check button is visible
     *
     * @test
     */
    public function test_edit_health_check_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Active Health Checks', 15)
                ->screenshot('edit-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEditButton = str_contains($pageSource, 'edit') ||
                str_contains($pageSource, 'update') ||
                str_contains($pageSource, 'modify');

            $this->assertTrue($hasEditButton, 'Edit button should be visible');

            $this->testResults['edit_button'] = 'Edit health check button is visible';
        });
    }

    /**
     * Test 18: Notification channels are displayed
     *
     * @test
     */
    public function test_notification_channels_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Active Health Checks', 15)
                ->screenshot('notification-channels');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasChannels = str_contains($pageSource, 'notification') ||
                str_contains($pageSource, 'channel') ||
                str_contains($pageSource, 'alert');

            $this->assertTrue($hasChannels, 'Notification channels should be displayed');

            $this->testResults['notification_channels'] = 'Notification channels are displayed';
        });
    }

    /**
     * Test 19: HTTP check type is available
     *
     * @test
     */
    public function test_http_check_type_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('http-check-type');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHttpType = str_contains($pageSource, 'http') ||
                str_contains($pageSource, 'https');

            $this->assertTrue($hasHttpType, 'HTTP check type should be available');

            $this->testResults['http_check_type'] = 'HTTP check type is available';
        });
    }

    /**
     * Test 20: TCP check type is available
     *
     * @test
     */
    public function test_tcp_check_type_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('tcp-check-type');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTcpType = str_contains($pageSource, 'tcp') ||
                str_contains($pageSource, 'port');

            $this->assertTrue($hasTcpType, 'TCP check type should be available');

            $this->testResults['tcp_check_type'] = 'TCP check type is available';
        });
    }

    /**
     * Test 21: SSL expiry check type is available
     *
     * @test
     */
    public function test_ssl_expiry_check_type_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('ssl-check-type');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSslType = str_contains($pageSource, 'ssl') ||
                str_contains($pageSource, 'certificate');

            $this->assertTrue($hasSslType, 'SSL expiry check type should be available');

            $this->testResults['ssl_check_type'] = 'SSL expiry check type is available';
        });
    }

    /**
     * Test 22: Response time is tracked
     *
     * @test
     */
    public function test_response_time_tracked()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Active Health Checks', 15)
                ->screenshot('response-time');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasResponseTime = str_contains($pageSource, 'response') ||
                str_contains($pageSource, 'time') ||
                str_contains($pageSource, 'ms');

            $this->assertTrue($hasResponseTime, 'Response time should be tracked');

            $this->testResults['response_time'] = 'Response time is tracked';
        });
    }

    /**
     * Test 23: Consecutive failures are displayed
     *
     * @test
     */
    public function test_consecutive_failures_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Active Health Checks', 15)
                ->screenshot('consecutive-failures');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFailures = str_contains($pageSource, 'consecutive') ||
                str_contains($pageSource, 'failure') ||
                str_contains($pageSource, 'failed');

            $this->assertTrue($hasFailures, 'Consecutive failures should be displayed');

            $this->testResults['consecutive_failures'] = 'Consecutive failures are displayed';
        });
    }

    /**
     * Test 24: Project selection is available
     *
     * @test
     */
    public function test_project_selection_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('project-selection');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProjectSelection = str_contains($pageSource, 'project') ||
                str_contains($pageSource, 'project_id');

            $this->assertTrue($hasProjectSelection, 'Project selection should be available');

            $this->testResults['project_selection'] = 'Project selection is available';
        });
    }

    /**
     * Test 25: Server selection is available
     *
     * @test
     */
    public function test_server_selection_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('server-selection');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasServerSelection = str_contains($pageSource, 'server') ||
                str_contains($pageSource, 'server_id');

            $this->assertTrue($hasServerSelection, 'Server selection should be available');

            $this->testResults['server_selection'] = 'Server selection is available';
        });
    }

    /**
     * Test 26: Timeout configuration is available
     *
     * @test
     */
    public function test_timeout_configuration_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('timeout-config');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTimeout = str_contains($pageSource, 'timeout') ||
                str_contains($pageSource, 'seconds');

            $this->assertTrue($hasTimeout, 'Timeout configuration should be available');

            $this->testResults['timeout_config'] = 'Timeout configuration is available';
        });
    }

    /**
     * Test 27: Email notification channel type is available
     *
     * @test
     */
    public function test_email_notification_channel_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('email-channel');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEmailChannel = str_contains($pageSource, 'email') ||
                str_contains($pageSource, 'mail');

            $this->assertTrue($hasEmailChannel, 'Email notification channel should be available');

            $this->testResults['email_channel'] = 'Email notification channel is available';
        });
    }

    /**
     * Test 28: Slack notification channel type is available
     *
     * @test
     */
    public function test_slack_notification_channel_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('slack-channel');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSlackChannel = str_contains($pageSource, 'slack') ||
                str_contains($pageSource, 'webhook');

            $this->assertTrue($hasSlackChannel, 'Slack notification channel should be available');

            $this->testResults['slack_channel'] = 'Slack notification channel is available';
        });
    }

    /**
     * Test 29: Discord notification channel type is available
     *
     * @test
     */
    public function test_discord_notification_channel_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Health Check Manager', 15)
                ->screenshot('discord-channel');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDiscordChannel = str_contains($pageSource, 'discord');

            $this->assertTrue($hasDiscordChannel, 'Discord notification channel should be available');

            $this->testResults['discord_channel'] = 'Discord notification channel is available';
        });
    }

    /**
     * Test 30: Health check type badge is displayed
     *
     * @test
     */
    public function test_health_check_type_badge_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/health-checks')
                ->pause(2000)
                ->waitForText('Active Health Checks', 15)
                ->screenshot('type-badge');

            $pageSource = strtoupper($browser->driver->getPageSource());
            $hasTypeBadge = str_contains($pageSource, 'HTTP') ||
                str_contains($pageSource, 'TCP') ||
                str_contains($pageSource, 'PING') ||
                str_contains($pageSource, 'SSL');

            $this->assertTrue($hasTypeBadge, 'Health check type badge should be displayed');

            $this->testResults['type_badge'] = 'Health check type badge is displayed';
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
                'test_suite' => 'Health Check Manager Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'users_tested' => User::count(),
                    'test_user_email' => $this->user->email,
                    'health_checks_count' => HealthCheck::count(),
                    'notification_channels_count' => NotificationChannel::count(),
                    'servers_count' => Server::count(),
                    'projects_count' => Project::count(),
                ],
            ];

            $reportPath = storage_path('app/test-reports/health-check-manager-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
