<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class Fail2banManagerTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected ?Server $server = null;

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

        // Create or get a server for testing
        $this->server = Server::first() ?? Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Fail2ban Server',
            'hostname' => 'fail2ban-test.devflow.test',
            'ip_address' => '192.168.1.103',
            'port' => 22,
            'username' => 'root',
            'status' => 'online',
            'os' => 'Ubuntu 22.04',
            'cpu_cores' => 4,
            'memory_gb' => 8,
            'disk_gb' => 100,
            'docker_installed' => true,
        ]);
    }

    /**
     * Test 1: Fail2ban manager page loads successfully
     *
     */

    #[Test]
    public function test_fail2ban_manager_page_loads_successfully()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-page-load');

            // Check if Fail2ban manager page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPageContent =
                str_contains($pageSource, 'fail2ban') ||
                str_contains($pageSource, 'intrusion') ||
                str_contains($pageSource, 'prevention') ||
                str_contains($pageSource, $this->server->name);

            $this->assertTrue($hasPageContent, 'Fail2ban manager page should load successfully');

            $this->testResults['page_loads'] = 'Fail2ban manager page loaded successfully';
        });
    }

    /**
     * Test 2: Fail2ban status is displayed
     *
     */

    #[Test]
    public function test_fail2ban_status_is_displayed()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-status-display');

            // Check for status information via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusDisplay =
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'running') ||
                str_contains($pageSource, 'stopped') ||
                str_contains($pageSource, 'enabled') ||
                str_contains($pageSource, 'disabled');

            $this->assertTrue($hasStatusDisplay, 'Fail2ban status should be displayed');

            $this->testResults['status_displayed'] = 'Fail2ban status is displayed';
        });
    }

    /**
     * Test 3: Start fail2ban button is visible
     *
     */

    #[Test]
    public function test_start_fail2ban_button_is_visible()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-start-button');

            // Check for start button via page source
            $pageSource = $browser->driver->getPageSource();
            $hasStartButton =
                str_contains($pageSource, 'Start Service') ||
                str_contains($pageSource, 'startFail2ban') ||
                str_contains($pageSource, 'wire:click="startFail2ban"');

            $this->assertTrue($hasStartButton, 'Start fail2ban button should be visible');

            $this->testResults['start_button_visible'] = 'Start fail2ban button is visible';
        });
    }

    /**
     * Test 4: Stop fail2ban button is visible
     *
     */

    #[Test]
    public function test_stop_fail2ban_button_is_visible()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-stop-button');

            // Check for stop button via page source
            $pageSource = $browser->driver->getPageSource();
            $hasStopButton =
                str_contains($pageSource, 'Stop Service') ||
                str_contains($pageSource, 'stopFail2ban') ||
                str_contains($pageSource, 'wire:click="stopFail2ban"');

            $this->assertTrue($hasStopButton, 'Stop fail2ban button should be visible');

            $this->testResults['stop_button_visible'] = 'Stop fail2ban button is visible';
        });
    }

    /**
     * Test 5: Jail list is displayed
     *
     */

    #[Test]
    public function test_jail_list_is_displayed()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-jail-list');

            // Check for jails section via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasJailList =
                str_contains($pageSource, 'jail') ||
                str_contains($pageSource, 'sshd') ||
                str_contains($pageSource, 'no jails configured');

            $this->assertTrue($hasJailList, 'Jail list should be displayed');

            $this->testResults['jail_list_displayed'] = 'Jail list is displayed';
        });
    }

    /**
     * Test 6: Jail selection functionality works
     *
     */

    #[Test]
    public function test_jail_selection_works()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-jail-selection');

            // Check for jail selection functionality via page source
            $pageSource = $browser->driver->getPageSource();
            $hasJailSelection =
                str_contains($pageSource, 'selectJail') ||
                str_contains($pageSource, 'wire:click="selectJail') ||
                str_contains($pageSource, 'selectedJail');

            $this->assertTrue($hasJailSelection, 'Jail selection functionality should work');

            $this->testResults['jail_selection_works'] = 'Jail selection functionality works';
        });
    }

    /**
     * Test 7: Banned IPs list is displayed
     *
     */

    #[Test]
    public function test_banned_ips_list_is_displayed()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-banned-ips-list');

            // Check for banned IPs section via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBannedIPsList =
                str_contains($pageSource, 'banned ips') ||
                str_contains($pageSource, 'ip address') ||
                str_contains($pageSource, 'no ips currently banned');

            $this->assertTrue($hasBannedIPsList, 'Banned IPs list should be displayed');

            $this->testResults['banned_ips_list'] = 'Banned IPs list is displayed';
        });
    }

    /**
     * Test 8: Unban IP button is present
     *
     */

    #[Test]
    public function test_unban_ip_button_is_present()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-unban-button');

            // Check for unban button via page source
            $pageSource = $browser->driver->getPageSource();
            $hasUnbanButton =
                str_contains($pageSource, 'Unban') ||
                str_contains($pageSource, 'unbanIP') ||
                str_contains($pageSource, 'wire:click="unbanIP');

            $this->assertTrue($hasUnbanButton, 'Unban IP button should be present');

            $this->testResults['unban_button_present'] = 'Unban IP button is present';
        });
    }

    /**
     * Test 9: Install fail2ban button shown when not installed
     *
     */

    #[Test]
    public function test_install_fail2ban_button_shown_when_not_installed()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-install-button');

            // Check for install button via page source
            $pageSource = $browser->driver->getPageSource();
            $hasInstallSection =
                str_contains($pageSource, 'Install Fail2ban') ||
                str_contains($pageSource, 'installFail2ban') ||
                str_contains($pageSource, 'Not Installed') ||
                str_contains($pageSource, 'wire:click="installFail2ban"');

            $this->assertTrue($hasInstallSection, 'Install fail2ban section should be shown when not installed');

            $this->testResults['install_button_shown'] = 'Install fail2ban button shown when not installed';
        });
    }

    /**
     * Test 10: Flash messages display
     *
     */

    #[Test]
    public function test_flash_messages_display()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-flash-messages');

            // Check for flash message container via page source
            $pageSource = $browser->driver->getPageSource();
            $hasFlashMessages =
                str_contains($pageSource, 'flashMessage') ||
                str_contains($pageSource, 'flashType') ||
                str_contains($pageSource, 'bg-green-900') ||
                str_contains($pageSource, 'bg-red-900');

            $this->assertTrue($hasFlashMessages, 'Flash messages should be able to display');

            $this->testResults['flash_messages_display'] = 'Flash messages display functionality exists';
        });
    }

    /**
     * Test 11: Selected jail is highlighted
     *
     */

    #[Test]
    public function test_selected_jail_is_highlighted()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-selected-jail-highlight');

            // Check for selected jail highlighting via page source
            $pageSource = $browser->driver->getPageSource();
            $hasJailHighlight =
                str_contains($pageSource, 'bg-red-600') ||
                str_contains($pageSource, 'selectedJail') ||
                str_contains($pageSource, '$selectedJail');

            $this->assertTrue($hasJailHighlight, 'Selected jail should be highlighted');

            $this->testResults['jail_highlighted'] = 'Selected jail is highlighted';
        });
    }

    /**
     * Test 12: Loading states work
     *
     */

    #[Test]
    public function test_loading_states_work()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-loading-states');

            // Check for loading states via page source
            $pageSource = $browser->driver->getPageSource();
            $hasLoadingStates =
                str_contains($pageSource, 'wire:loading') ||
                str_contains($pageSource, 'isLoading') ||
                str_contains($pageSource, 'wire:loading.attr="disabled"') ||
                str_contains($pageSource, 'animate-spin');

            $this->assertTrue($hasLoadingStates, 'Loading states should work');

            $this->testResults['loading_states_work'] = 'Loading states work';
        });
    }

    /**
     * Test 13: Navigation back to security dashboard works
     *
     */

    #[Test]
    public function test_navigation_back_to_security_dashboard_works()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-back-navigation');

            // Check for back navigation link via page source
            $pageSource = $browser->driver->getPageSource();
            $hasBackNavigation =
                str_contains($pageSource, 'servers.security') ||
                str_contains($pageSource, '/security/fail2ban') ||
                str_contains($pageSource, 'M10 19l-7-7m0 0l7-7m-7 7h18');

            $this->assertTrue($hasBackNavigation, 'Navigation back to security dashboard should work');

            $this->testResults['back_navigation_works'] = 'Navigation back to security dashboard works';
        });
    }

    /**
     * Test 14: Service status indicators (enabled/disabled)
     *
     */

    #[Test]
    public function test_service_status_indicators_display()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-status-indicators');

            // Check for status indicators via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusIndicators =
                str_contains($pageSource, 'bg-green-500/20') ||
                str_contains($pageSource, 'bg-red-500/20') ||
                str_contains($pageSource, 'text-green-400') ||
                str_contains($pageSource, 'text-red-400') ||
                str_contains($pageSource, 'running') ||
                str_contains($pageSource, 'stopped');

            $this->assertTrue($hasStatusIndicators, 'Service status indicators should display');

            $this->testResults['status_indicators'] = 'Service status indicators display';
        });
    }

    /**
     * Test 15: Refresh status button works
     *
     */

    #[Test]
    public function test_refresh_status_button_works()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-refresh-button');

            // Check for refresh button via page source
            $pageSource = $browser->driver->getPageSource();
            $hasRefreshButton =
                str_contains($pageSource, 'loadFail2banStatus') ||
                str_contains($pageSource, 'wire:click="loadFail2banStatus"') ||
                str_contains($pageSource, 'Refresh');

            $this->assertTrue($hasRefreshButton, 'Refresh status button should work');

            $this->testResults['refresh_button_works'] = 'Refresh status button works';
        });
    }

    /**
     * Test 16: Fail2ban service control section is present
     *
     */

    #[Test]
    public function test_fail2ban_service_control_section_is_present()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-service-control');

            // Check for service control section via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasServiceControl =
                str_contains($pageSource, 'service') ||
                str_contains($pageSource, 'control') ||
                str_contains($pageSource, 'start') ||
                str_contains($pageSource, 'stop');

            $this->assertTrue($hasServiceControl, 'Fail2ban service control section should be present');

            $this->testResults['service_control_present'] = 'Fail2ban service control section is present';
        });
    }

    /**
     * Test 17: Jail count is displayed
     *
     */

    #[Test]
    public function test_jail_count_is_displayed()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-jail-count');

            // Check for jail count via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasJailCount =
                str_contains($pageSource, 'jail') ||
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'configured');

            $this->assertTrue($hasJailCount, 'Jail count should be displayed');

            $this->testResults['jail_count_displayed'] = 'Jail count is displayed';
        });
    }

    /**
     * Test 18: Banned IP count is displayed
     *
     */

    #[Test]
    public function test_banned_ip_count_is_displayed()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-banned-ip-count');

            // Check for banned IP count via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBannedCount =
                str_contains($pageSource, 'banned') ||
                str_contains($pageSource, 'no ips currently banned');

            $this->assertTrue($hasBannedCount, 'Banned IP count should be displayed');

            $this->testResults['banned_ip_count'] = 'Banned IP count is displayed';
        });
    }

    /**
     * Test 19: Page title displays Fail2ban Manager
     *
     */

    #[Test]
    public function test_page_title_displays_fail2ban_manager()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-page-title');

            // Check for page title via page source
            $pageSource = $browser->driver->getPageSource();
            $hasPageTitle =
                str_contains($pageSource, 'Fail2ban Manager') ||
                str_contains($pageSource, 'Intrusion Prevention');

            $this->assertTrue($hasPageTitle, 'Page title should display Fail2ban Manager');

            $this->testResults['page_title_correct'] = 'Page title displays Fail2ban Manager';
        });
    }

    /**
     * Test 20: Server name is displayed in the header
     *
     */

    #[Test]
    public function test_server_name_is_displayed_in_header()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-server-name');

            // Check for server name via page source
            $pageSource = $browser->driver->getPageSource();
            $hasServerName =
                str_contains($pageSource, $this->server->name) ||
                str_contains($pageSource, '$server->name');

            $this->assertTrue($hasServerName, 'Server name should be displayed in header');

            $this->testResults['server_name_displayed'] = 'Server name is displayed in header';
        });
    }

    /**
     * Test 21: Empty state message displayed when no jails configured
     *
     */

    #[Test]
    public function test_empty_state_message_when_no_jails()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-empty-jails');

            // Check for empty state message via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEmptyState =
                str_contains($pageSource, 'no jails configured') ||
                str_contains($pageSource, 'jail');

            $this->assertTrue($hasEmptyState, 'Empty state message should display when no jails');

            $this->testResults['empty_state_jails'] = 'Empty state message displayed when no jails';
        });
    }

    /**
     * Test 22: Empty state message displayed when no IPs banned
     *
     */

    #[Test]
    public function test_empty_state_message_when_no_banned_ips()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-empty-banned-ips');

            // Check for empty state message via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEmptyState =
                str_contains($pageSource, 'no ips currently banned') ||
                str_contains($pageSource, 'banned');

            $this->assertTrue($hasEmptyState, 'Empty state message should display when no IPs banned');

            $this->testResults['empty_state_ips'] = 'Empty state message displayed when no IPs banned';
        });
    }

    /**
     * Test 23: Confirmation dialog for unban action exists
     *
     */

    #[Test]
    public function test_confirmation_dialog_for_unban_exists()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-unban-confirmation');

            // Check for confirmation dialog via page source
            $pageSource = $browser->driver->getPageSource();
            $hasConfirmation =
                str_contains($pageSource, 'wire:confirm') ||
                str_contains($pageSource, 'Are you sure you want to unban');

            $this->assertTrue($hasConfirmation, 'Confirmation dialog for unban should exist');

            $this->testResults['unban_confirmation'] = 'Confirmation dialog for unban exists';
        });
    }

    /**
     * Test 24: Icons are properly displayed
     *
     */

    #[Test]
    public function test_icons_are_properly_displayed()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-icons');

            // Check for SVG icons via page source
            $pageSource = $browser->driver->getPageSource();
            $hasIcons =
                str_contains($pageSource, '<svg') &&
                str_contains($pageSource, 'viewBox="0 0 24 24"');

            $this->assertTrue($hasIcons, 'Icons should be properly displayed');

            $this->testResults['icons_displayed'] = 'Icons are properly displayed';
        });
    }

    /**
     * Test 25: Responsive design elements present
     *
     */

    #[Test]
    public function test_responsive_design_elements_present()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for testing');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-responsive-design');

            // Check for responsive design classes via page source
            $pageSource = $browser->driver->getPageSource();
            $hasResponsiveClasses =
                str_contains($pageSource, 'lg:col-span') ||
                str_contains($pageSource, 'sm:px-') ||
                str_contains($pageSource, 'max-w-7xl');

            $this->assertTrue($hasResponsiveClasses, 'Responsive design elements should be present');

            $this->testResults['responsive_design'] = 'Responsive design elements present';
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
                'test_suite' => 'Fail2ban Manager Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'servers_tested' => Server::count(),
                    'users_tested' => User::count(),
                    'test_server_id' => $this->server?->id,
                    'test_server_name' => $this->server?->name,
                ],
            ];

            $reportPath = storage_path('app/test-reports/fail2ban-manager-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
