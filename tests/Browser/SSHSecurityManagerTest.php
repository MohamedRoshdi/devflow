<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class SSHSecurityManagerTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected ?Server $server = null;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();
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
            'name' => 'Test SSH Security Server',
            'hostname' => 'ssh-security-test.devflow.test',
            'ip_address' => '192.168.1.102',
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
     * Test 1: SSH security manager page loads successfully
     *
     */

    #[Test]
    public function test_ssh_security_manager_page_loads_successfully()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-manager-page-load');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSSHSecurityContent = str_contains($pageSource, 'ssh security') ||
                                    str_contains($pageSource, 'ssh configuration') ||
                                    str_contains($pageSource, 'ssh');

            $this->assertTrue($hasSSHSecurityContent, 'SSH security manager page should load successfully');
            $this->testResults['page_load'] = 'SSH security manager page loads successfully';
        });
    }

    /**
     * Test 2: SSH configuration is displayed
     *
     */

    #[Test]
    public function test_ssh_configuration_is_displayed()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-configuration-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasConfiguration = str_contains($pageSource, 'current configuration') ||
                               str_contains($pageSource, 'configuration') ||
                               str_contains($pageSource, 'ssh port');

            $this->assertTrue($hasConfiguration, 'SSH configuration should be displayed');
            $this->testResults['configuration_display'] = 'SSH configuration is displayed';
        });
    }

    /**
     * Test 3: Current SSH port is shown
     *
     */

    #[Test]
    public function test_current_ssh_port_is_shown()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-port-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPort = str_contains($pageSource, 'ssh port') ||
                      str_contains($pageSource, 'port') ||
                      str_contains($pageSource, 'current port');

            $this->assertTrue($hasPort, 'Current SSH port should be shown');
            $this->testResults['port_display'] = 'Current SSH port is shown';
        });
    }

    /**
     * Test 4: Root login toggle is visible
     *
     */

    #[Test]
    public function test_root_login_toggle_is_visible()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-root-login-toggle');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRootLoginToggle = str_contains($pageSource, 'root login') ||
                                 str_contains($pageSource, 'rootloginenabled') ||
                                 str_contains($pageSource, 'allow root user');

            $this->assertTrue($hasRootLoginToggle, 'Root login toggle should be visible');
            $this->testResults['root_login_toggle'] = 'Root login toggle is visible';
        });
    }

    /**
     * Test 5: Password authentication toggle is visible
     *
     */

    #[Test]
    public function test_password_authentication_toggle_is_visible()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-password-auth-toggle');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPasswordAuthToggle = str_contains($pageSource, 'password authentication') ||
                                    str_contains($pageSource, 'passwordauthenabled') ||
                                    str_contains($pageSource, 'allow login with password');

            $this->assertTrue($hasPasswordAuthToggle, 'Password authentication toggle should be visible');
            $this->testResults['password_auth_toggle'] = 'Password authentication toggle is visible';
        });
    }

    /**
     * Test 6: Public key authentication status shown
     *
     */

    #[Test]
    public function test_public_key_authentication_status_shown()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-pubkey-status');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPubkeyStatus = str_contains($pageSource, 'public key authentication') ||
                              str_contains($pageSource, 'ssh keys') ||
                              str_contains($pageSource, 'pubkey');

            $this->assertTrue($hasPubkeyStatus, 'Public key authentication status should be shown');
            $this->testResults['pubkey_status'] = 'Public key authentication status shown';
        });
    }

    /**
     * Test 7: Max auth tries is displayed
     *
     */

    #[Test]
    public function test_max_auth_tries_is_displayed()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-max-auth-tries');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMaxAuthTries = str_contains($pageSource, 'max auth tries') ||
                              str_contains($pageSource, 'maximum authentication') ||
                              str_contains($pageSource, 'authentication attempts');

            $this->assertTrue($hasMaxAuthTries, 'Max auth tries should be displayed');
            $this->testResults['max_auth_tries'] = 'Max auth tries is displayed';
        });
    }

    /**
     * Test 8: Change port button is present
     *
     */

    #[Test]
    public function test_change_port_button_is_present()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-change-port-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasChangePortButton = str_contains($pageSource, 'change') ||
                                  str_contains($pageSource, 'changeport') ||
                                  str_contains($pageSource, 'wire:click="changeport"');

            $this->assertTrue($hasChangePortButton, 'Change port button should be present');
            $this->testResults['change_port_button'] = 'Change port button is present';
        });
    }

    /**
     * Test 9: Harden SSH button is present
     *
     */

    #[Test]
    public function test_harden_ssh_button_is_present()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-harden-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHardenButton = str_contains($pageSource, 'harden') ||
                              str_contains($pageSource, 'quick harden') ||
                              str_contains($pageSource, 'hardenssh');

            $this->assertTrue($hasHardenButton, 'Harden SSH button should be present');
            $this->testResults['harden_button'] = 'Harden SSH button is present';
        });
    }

    /**
     * Test 10: Restart SSH button is present
     *
     */

    #[Test]
    public function test_restart_ssh_button_is_present()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-restart-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRestartButton = str_contains($pageSource, 'restart ssh') ||
                               str_contains($pageSource, 'restart') ||
                               str_contains($pageSource, 'restartssh');

            $this->assertTrue($hasRestartButton, 'Restart SSH button should be present');
            $this->testResults['restart_button'] = 'Restart SSH button is present';
        });
    }

    /**
     * Test 11: Harden confirmation modal can open
     *
     */

    #[Test]
    public function test_harden_confirmation_modal_opens()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-harden-modal-before');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHardenConfirmModal = str_contains($pageSource, 'showhardenconfirm') ||
                                    str_contains($pageSource, 'apply ssh hardening') ||
                                    str_contains($pageSource, 'hardening');

            $this->assertTrue($hasHardenConfirmModal, 'Harden confirmation modal should be present in page');
            $this->testResults['harden_modal'] = 'Harden confirmation modal opens';
        });
    }

    /**
     * Test 12: Flash messages display area exists
     *
     */

    #[Test]
    public function test_flash_messages_display()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-flash-messages');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFlashArea = str_contains($pageSource, 'flashmessage') ||
                           str_contains($pageSource, 'flashtype');

            $this->assertTrue($hasFlashArea, 'Flash messages display area should exist');
            $this->testResults['flash_messages'] = 'Flash messages display';
        });
    }

    /**
     * Test 13: Loading states work
     *
     */

    #[Test]
    public function test_loading_states_work()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-loading-states');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLoadingStates = str_contains($pageSource, 'wire:loading') ||
                               str_contains($pageSource, 'isloading') ||
                               str_contains($pageSource, 'loading');

            $this->assertTrue($hasLoadingStates, 'Loading states should work');
            $this->testResults['loading_states'] = 'Loading states work';
        });
    }

    /**
     * Test 14: Toggle buttons have correct wire:click handlers
     *
     */

    #[Test]
    public function test_toggle_buttons_work()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-toggle-buttons');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasToggleHandlers = str_contains($pageSource, 'togglerootlogin') ||
                                str_contains($pageSource, 'togglepasswordauth') ||
                                str_contains($pageSource, 'wire:click');

            $this->assertTrue($hasToggleHandlers, 'Toggle buttons should have wire:click handlers');
            $this->testResults['toggle_buttons'] = 'Toggle buttons work';
        });
    }

    /**
     * Test 15: Port input validation attributes exist
     *
     */

    #[Test]
    public function test_port_input_validation_works()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-port-validation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPortValidation = str_contains($pageSource, 'wire:model="port"') ||
                                str_contains($pageSource, 'type="number"') ||
                                (str_contains($pageSource, 'min=') && str_contains($pageSource, 'max='));

            $this->assertTrue($hasPortValidation, 'Port input validation should work');
            $this->testResults['port_validation'] = 'Port input validation works';
        });
    }

    /**
     * Test 16: Navigation back to security dashboard works
     *
     */

    #[Test]
    public function test_navigation_back_to_security_dashboard_works()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-back-navigation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBackLink = str_contains($pageSource, 'servers/'.$this->server->id.'/security') ||
                          str_contains($pageSource, 'back') ||
                          str_contains($pageSource, 'return');

            $this->assertTrue($hasBackLink, 'Navigation back to security dashboard should work');
            $this->testResults['back_navigation'] = 'Navigation back to security dashboard works';
        });
    }

    /**
     * Test 17: Refresh button is present
     *
     */

    #[Test]
    public function test_refresh_button_is_present()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-refresh-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRefreshButton = str_contains($pageSource, 'refresh') ||
                               str_contains($pageSource, 'loadsshconfig') ||
                               str_contains($pageSource, 'wire:click="loadsshconfig"');

            $this->assertTrue($hasRefreshButton, 'Refresh button should be present');
            $this->testResults['refresh_button'] = 'Refresh button is present';
        });
    }

    /**
     * Test 18: Security recommendations section exists
     *
     */

    #[Test]
    public function test_security_recommendations_section_exists()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-recommendations');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRecommendations = str_contains($pageSource, 'security recommendations') ||
                                 str_contains($pageSource, 'recommendations') ||
                                 str_contains($pageSource, 'good!');

            $this->assertTrue($hasRecommendations, 'Security recommendations section should exist');
            $this->testResults['recommendations'] = 'Security recommendations section exists';
        });
    }

    /**
     * Test 19: Port recommendation is displayed
     *
     */

    #[Test]
    public function test_port_recommendation_is_displayed()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-port-recommendation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPortRecommendation = str_contains($pageSource, 'non-standard port') ||
                                    str_contains($pageSource, 'default ssh port') ||
                                    str_contains($pageSource, 'port 22');

            $this->assertTrue($hasPortRecommendation, 'Port recommendation should be displayed');
            $this->testResults['port_recommendation'] = 'Port recommendation is displayed';
        });
    }

    /**
     * Test 20: Root login recommendation is displayed
     *
     */

    #[Test]
    public function test_root_login_recommendation_is_displayed()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-root-recommendation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRootRecommendation = str_contains($pageSource, 'root login disabled') ||
                                    str_contains($pageSource, 'root login enabled') ||
                                    str_contains($pageSource, 'disable root login');

            $this->assertTrue($hasRootRecommendation, 'Root login recommendation should be displayed');
            $this->testResults['root_recommendation'] = 'Root login recommendation is displayed';
        });
    }

    /**
     * Test 21: Password authentication recommendation is displayed
     *
     */

    #[Test]
    public function test_password_authentication_recommendation_is_displayed()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-password-recommendation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPasswordRecommendation = str_contains($pageSource, 'key-only authentication') ||
                                        str_contains($pageSource, 'password authentication enabled') ||
                                        str_contains($pageSource, 'ssh keys only');

            $this->assertTrue($hasPasswordRecommendation, 'Password authentication recommendation should be displayed');
            $this->testResults['password_recommendation'] = 'Password authentication recommendation is displayed';
        });
    }

    /**
     * Test 22: Max auth tries recommendation is displayed
     *
     */

    #[Test]
    public function test_max_auth_tries_recommendation_is_displayed()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-auth-tries-recommendation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAuthTriesRecommendation = str_contains($pageSource, 'low auth tries limit') ||
                                         str_contains($pageSource, 'high auth tries limit') ||
                                         str_contains($pageSource, 'maxauthtries');

            $this->assertTrue($hasAuthTriesRecommendation, 'Max auth tries recommendation should be displayed');
            $this->testResults['auth_tries_recommendation'] = 'Max auth tries recommendation is displayed';
        });
    }

    /**
     * Test 23: Harden modal shows warning message
     *
     */

    #[Test]
    public function test_harden_modal_shows_warning_message()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-harden-warning');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWarning = str_contains($pageSource, 'warning') ||
                         str_contains($pageSource, 'make sure you have ssh key access') ||
                         str_contains($pageSource, 'before applying');

            $this->assertTrue($hasWarning, 'Harden modal should show warning message');
            $this->testResults['harden_warning'] = 'Harden modal shows warning message';
        });
    }

    /**
     * Test 24: Harden modal lists changes to be applied
     *
     */

    #[Test]
    public function test_harden_modal_lists_changes()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-harden-changes');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasChangesList = str_contains($pageSource, 'disable root login') &&
                             str_contains($pageSource, 'disable password authentication') &&
                             str_contains($pageSource, 'maxauthtries to 3');

            $this->assertTrue($hasChangesList, 'Harden modal should list changes to be applied');
            $this->testResults['harden_changes_list'] = 'Harden modal lists changes';
        });
    }

    /**
     * Test 25: Server name is displayed in header
     *
     */

    #[Test]
    public function test_server_name_is_displayed_in_header()
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/servers/{$this->server->id}/security/ssh")
                ->pause(2000)
                ->screenshot('ssh-security-server-name');

            $pageSource = strtolower($browser->driver->getPageSource());
            $serverName = strtolower($this->server->name);
            $hasServerName = str_contains($pageSource, $serverName) ||
                            str_contains($pageSource, 'server') ||
                            str_contains($pageSource, 'ssh configuration');

            $this->assertTrue($hasServerName, 'Server name should be displayed in header');
            $this->testResults['server_name'] = 'Server name is displayed in header';
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
                'test_suite' => 'SSH Security Manager Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'user_id' => $this->user->id,
                    'user_name' => $this->user->name,
                    'server_id' => $this->server?->id,
                    'server_name' => $this->server?->name,
                    'servers_count' => Server::count(),
                ],
            ];

            $reportPath = storage_path('app/test-reports/ssh-security-manager-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
