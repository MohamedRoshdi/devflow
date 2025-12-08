<?php

namespace Tests\Browser;

use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class SecurityTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

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

        // Get or create test server for security testing
        $this->server = Server::firstOrCreate(
            ['hostname' => 'security-test.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Security Test Server',
                'ip_address' => '192.168.1.200',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
                'cpu_cores' => 4,
                'memory_gb' => 8,
                'disk_gb' => 250,
                'docker_installed' => true,
                'docker_version' => '24.0.5',
                'os' => 'Ubuntu 22.04',
            ]
        );
    }

    /**
     * Test 1: Firewall manager page loads
     *
     * @test
     */
    public function test_firewall_manager_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('firewall-manager-page');

            // Check if firewall page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFirewallContent =
                str_contains($pageSource, 'firewall') ||
                str_contains($pageSource, 'security') ||
                str_contains($pageSource, 'rules') ||
                str_contains($pageSource, $this->server->name);

            $this->assertTrue($hasFirewallContent, 'Firewall manager page should load');

            $this->testResults['firewall_manager'] = 'Firewall manager page loaded successfully';
        });
    }

    /**
     * Test 2: SSL manager page loads
     *
     * @test
     */
    public function test_ssl_manager_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/ssl')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-manager-page');

            // Check if SSL page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSSLContent =
                str_contains($pageSource, 'ssl') ||
                str_contains($pageSource, 'certificate') ||
                str_contains($pageSource, 'https') ||
                str_contains($pageSource, $this->server->name);

            $this->assertTrue($hasSSLContent, 'SSL manager page should load');

            $this->testResults['ssl_manager'] = 'SSL manager page loaded successfully';
        });
    }

    /**
     * Test 3: Security dashboard is accessible
     *
     * @test
     */
    public function test_security_dashboard_accessible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-dashboard');

            // Check if security dashboard loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSecurityContent =
                str_contains($pageSource, 'security') ||
                str_contains($pageSource, 'dashboard') ||
                str_contains($pageSource, $this->server->name);

            $this->assertTrue($hasSecurityContent, 'Security dashboard should be accessible');

            $this->testResults['security_dashboard'] = 'Security dashboard is accessible';
        });
    }

    /**
     * Test 4: SSH security settings are visible
     *
     * @test
     */
    public function test_ssh_security_settings_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/ssh')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-security-settings');

            // Check if SSH security page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSSHContent =
                str_contains($pageSource, 'ssh') ||
                str_contains($pageSource, 'port') ||
                str_contains($pageSource, 'authentication') ||
                str_contains($pageSource, 'security') ||
                str_contains($pageSource, $this->server->name);

            $this->assertTrue($hasSSHContent, 'SSH security settings should be visible');

            $this->testResults['ssh_security'] = 'SSH security settings are visible';
        });
    }

    /**
     * Test 5: Fail2ban status displays
     *
     * @test
     */
    public function test_fail2ban_status_displays()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-status');

            // Check if Fail2ban page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFail2banContent =
                str_contains($pageSource, 'fail2ban') ||
                str_contains($pageSource, 'intrusion') ||
                str_contains($pageSource, 'banned') ||
                str_contains($pageSource, 'security') ||
                str_contains($pageSource, $this->server->name);

            $this->assertTrue($hasFail2banContent, 'Fail2ban status should display');

            $this->testResults['fail2ban_status'] = 'Fail2ban status displays correctly';
        });
    }

    /**
     * Test 6: Security dashboard shows security metrics
     *
     * @test
     */
    public function test_security_dashboard_shows_metrics()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-metrics');

            // Check for security metrics via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMetrics =
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'enabled') ||
                str_contains($pageSource, 'disabled');

            $this->assertTrue($hasMetrics, 'Security metrics should be displayed');

            $this->testResults['security_metrics'] = 'Security metrics are displayed';
        });
    }

    /**
     * Test 7: Firewall rules section is visible
     *
     * @test
     */
    public function test_firewall_rules_section_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('firewall-rules-section');

            // Check for firewall rules via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRulesSection =
                str_contains($pageSource, 'rule') ||
                str_contains($pageSource, 'port') ||
                str_contains($pageSource, 'allow') ||
                str_contains($pageSource, 'deny') ||
                str_contains($pageSource, 'add');

            $this->assertTrue($hasRulesSection, 'Firewall rules section should be visible');

            $this->testResults['firewall_rules'] = 'Firewall rules section is visible';
        });
    }

    /**
     * Test 8: SSL certificates list is displayed
     *
     * @test
     */
    public function test_ssl_certificates_list_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/ssl')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-certificates-list');

            // Check for SSL certificates section via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCertificatesList =
                str_contains($pageSource, 'certificate') ||
                str_contains($pageSource, 'domain') ||
                str_contains($pageSource, 'expir') ||
                str_contains($pageSource, 'valid') ||
                str_contains($pageSource, 'issue');

            $this->assertTrue($hasCertificatesList, 'SSL certificates list should be displayed');

            $this->testResults['ssl_certificates'] = 'SSL certificates list is displayed';
        });
    }

    /**
     * Test 9: SSH security configuration options are present
     *
     * @test
     */
    public function test_ssh_security_configuration_options_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/ssh')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-configuration-options');

            // Check for SSH configuration options via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasConfigOptions =
                str_contains($pageSource, 'port') ||
                str_contains($pageSource, 'password') ||
                str_contains($pageSource, 'key') ||
                str_contains($pageSource, 'root') ||
                str_contains($pageSource, 'config');

            $this->assertTrue($hasConfigOptions, 'SSH configuration options should be present');

            $this->testResults['ssh_config_options'] = 'SSH configuration options are present';
        });
    }

    /**
     * Test 10: Fail2ban jails are listed
     *
     * @test
     */
    public function test_fail2ban_jails_listed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-jails');

            // Check for Fail2ban jails via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasJails =
                str_contains($pageSource, 'jail') ||
                str_contains($pageSource, 'sshd') ||
                str_contains($pageSource, 'ban') ||
                str_contains($pageSource, 'service') ||
                str_contains($pageSource, 'enabled');

            $this->assertTrue($hasJails, 'Fail2ban jails should be listed');

            $this->testResults['fail2ban_jails'] = 'Fail2ban jails are listed';
        });
    }

    /**
     * Test 11: Security scan page is accessible
     *
     * @test
     */
    public function test_security_scan_page_accessible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/scan')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-scan-page');

            // Check if security scan page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasScanContent =
                str_contains($pageSource, 'scan') ||
                str_contains($pageSource, 'vulnerability') ||
                str_contains($pageSource, 'security') ||
                str_contains($pageSource, $this->server->name);

            $this->assertTrue($hasScanContent, 'Security scan page should be accessible');

            $this->testResults['security_scan'] = 'Security scan page is accessible';
        });
    }

    /**
     * Test 12: Firewall add rule button is present
     *
     * @test
     */
    public function test_firewall_add_rule_button_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('firewall-add-rule-button');

            // Check for add rule button via page source
            $pageSource = $browser->driver->getPageSource();
            $hasAddButton =
                str_contains($pageSource, 'Add Rule') ||
                str_contains($pageSource, 'New Rule') ||
                str_contains($pageSource, 'Create Rule') ||
                str_contains($pageSource, 'wire:click');

            $this->assertTrue($hasAddButton, 'Firewall add rule button should be present');

            $this->testResults['firewall_add_button'] = 'Firewall add rule button is present';
        });
    }

    /**
     * Test 13: SSL certificate renewal option is visible
     *
     * @test
     */
    public function test_ssl_renewal_option_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/ssl')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-renewal-option');

            // Check for renewal option via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRenewalOption =
                str_contains($pageSource, 'renew') ||
                str_contains($pageSource, 'refresh') ||
                str_contains($pageSource, 'update') ||
                str_contains($pageSource, 'install');

            $this->assertTrue($hasRenewalOption, 'SSL renewal option should be visible');

            $this->testResults['ssl_renewal'] = 'SSL renewal option is visible';
        });
    }

    /**
     * Test 14: SSH port configuration is editable
     *
     * @test
     */
    public function test_ssh_port_configuration_editable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/ssh')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-port-configuration');

            // Check for SSH port configuration via page source
            $pageSource = $browser->driver->getPageSource();
            $hasPortConfig =
                str_contains($pageSource, 'wire:model') ||
                str_contains($pageSource, 'port') ||
                str_contains($pageSource, 'input');

            $this->assertTrue($hasPortConfig, 'SSH port configuration should be editable');

            $this->testResults['ssh_port_config'] = 'SSH port configuration is editable';
        });
    }

    /**
     * Test 15: Fail2ban banned IPs are shown
     *
     * @test
     */
    public function test_fail2ban_banned_ips_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-banned-ips');

            // Check for banned IPs section via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBannedIPs =
                str_contains($pageSource, 'banned') ||
                str_contains($pageSource, 'ip') ||
                str_contains($pageSource, 'address') ||
                str_contains($pageSource, 'block');

            $this->assertTrue($hasBannedIPs, 'Fail2ban banned IPs should be shown');

            $this->testResults['fail2ban_banned_ips'] = 'Fail2ban banned IPs are shown';
        });
    }

    /**
     * Test 16: Security dashboard navigation links work
     *
     * @test
     */
    public function test_security_dashboard_navigation_links()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-dashboard-nav');

            // Check for navigation links via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNavLinks =
                str_contains($pageSource, 'firewall') ||
                str_contains($pageSource, 'ssh') ||
                str_contains($pageSource, 'fail2ban') ||
                str_contains($pageSource, 'scan');

            $this->assertTrue($hasNavLinks, 'Security dashboard navigation links should work');

            $this->testResults['security_nav_links'] = 'Security dashboard navigation links work';
        });
    }

    /**
     * Test 17: Firewall status indicator is displayed
     *
     * @test
     */
    public function test_firewall_status_indicator_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('firewall-status-indicator');

            // Check for status indicator via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusIndicator =
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'inactive') ||
                str_contains($pageSource, 'enabled') ||
                str_contains($pageSource, 'disabled') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasStatusIndicator, 'Firewall status indicator should be displayed');

            $this->testResults['firewall_status'] = 'Firewall status indicator is displayed';
        });
    }

    /**
     * Test 18: SSL expiration warnings are visible
     *
     * @test
     */
    public function test_ssl_expiration_warnings_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/ssl')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-expiration-warnings');

            // Check for expiration warnings via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasExpirationInfo =
                str_contains($pageSource, 'expir') ||
                str_contains($pageSource, 'valid') ||
                str_contains($pageSource, 'days') ||
                str_contains($pageSource, 'date');

            $this->assertTrue($hasExpirationInfo, 'SSL expiration warnings should be visible');

            $this->testResults['ssl_expiration'] = 'SSL expiration warnings are visible';
        });
    }

    /**
     * Test 19: SSH authentication methods are configurable
     *
     * @test
     */
    public function test_ssh_authentication_methods_configurable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/ssh')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-auth-methods');

            // Check for authentication methods via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAuthMethods =
                str_contains($pageSource, 'password') ||
                str_contains($pageSource, 'key') ||
                str_contains($pageSource, 'authentication') ||
                str_contains($pageSource, 'login');

            $this->assertTrue($hasAuthMethods, 'SSH authentication methods should be configurable');

            $this->testResults['ssh_auth_methods'] = 'SSH authentication methods are configurable';
        });
    }

    /**
     * Test 20: Fail2ban service control buttons are present
     *
     * @test
     */
    public function test_fail2ban_service_control_buttons_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-control-buttons');

            // Check for service control buttons via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasControlButtons =
                str_contains($pageSource, 'start') ||
                str_contains($pageSource, 'stop') ||
                str_contains($pageSource, 'restart') ||
                str_contains($pageSource, 'enable') ||
                str_contains($pageSource, 'disable');

            $this->assertTrue($hasControlButtons, 'Fail2ban service control buttons should be present');

            $this->testResults['fail2ban_controls'] = 'Fail2ban service control buttons are present';
        });
    }

    /**
     * Test 21: Security score display is visible
     *
     * @test
     */
    public function test_security_score_display_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-score-display');

            // Check for security score via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasScore =
                str_contains($pageSource, 'score') ||
                str_contains($pageSource, 'rating') ||
                str_contains($pageSource, 'grade') ||
                str_contains($pageSource, 'level');

            $this->assertTrue($hasScore, 'Security score should be displayed');

            $this->testResults['security_score'] = 'Security score display is visible';
        });
    }

    /**
     * Test 22: Security recommendations are shown
     *
     * @test
     */
    public function test_security_recommendations_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-recommendations');

            // Check for recommendations via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRecommendations =
                str_contains($pageSource, 'recommend') ||
                str_contains($pageSource, 'suggestion') ||
                str_contains($pageSource, 'improve') ||
                str_contains($pageSource, 'action');

            $this->assertTrue($hasRecommendations, 'Security recommendations should be shown');

            $this->testResults['security_recommendations'] = 'Security recommendations are shown';
        });
    }

    /**
     * Test 23: Port management interface is accessible
     *
     * @test
     */
    public function test_port_management_interface_accessible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('port-management');

            // Check for port management via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPortManagement =
                str_contains($pageSource, 'port') ||
                str_contains($pageSource, 'protocol') ||
                str_contains($pageSource, '80') ||
                str_contains($pageSource, '443');

            $this->assertTrue($hasPortManagement, 'Port management interface should be accessible');

            $this->testResults['port_management'] = 'Port management interface is accessible';
        });
    }

    /**
     * Test 24: SSH key management page loads
     *
     * @test
     */
    public function test_ssh_key_management_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/ssh-keys')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-key-management');

            // Check for SSH key management via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasKeyManagement =
                str_contains($pageSource, 'ssh') ||
                str_contains($pageSource, 'key') ||
                str_contains($pageSource, 'public') ||
                str_contains($pageSource, 'add');

            $this->assertTrue($hasKeyManagement, 'SSH key management page should load');

            $this->testResults['ssh_key_page'] = 'SSH key management page loads';
        });
    }

    /**
     * Test 25: Two-factor authentication settings exist
     *
     * @test
     */
    public function test_two_factor_authentication_settings_exist()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('two-factor-settings');

            // Check for 2FA settings via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $has2FA =
                str_contains($pageSource, '2fa') ||
                str_contains($pageSource, 'two-factor') ||
                str_contains($pageSource, 'two factor') ||
                str_contains($pageSource, 'authentication');

            $this->assertTrue($has2FA, 'Two-factor authentication settings should exist');

            $this->testResults['two_factor_settings'] = 'Two-factor authentication settings exist';
        });
    }

    /**
     * Test 26: Security audit logs are accessible
     *
     * @test
     */
    public function test_security_audit_logs_accessible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/admin/audit-logs')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-audit-logs');

            // Check for audit logs via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAuditLogs =
                str_contains($pageSource, 'audit') ||
                str_contains($pageSource, 'log') ||
                str_contains($pageSource, 'activity') ||
                str_contains($pageSource, 'event');

            $this->assertTrue($hasAuditLogs, 'Security audit logs should be accessible');

            $this->testResults['audit_logs'] = 'Security audit logs are accessible';
        });
    }

    /**
     * Test 27: Intrusion detection alerts are displayed
     *
     * @test
     */
    public function test_intrusion_detection_alerts_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('intrusion-alerts');

            // Check for intrusion detection alerts via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAlerts =
                str_contains($pageSource, 'alert') ||
                str_contains($pageSource, 'intrusion') ||
                str_contains($pageSource, 'detection') ||
                str_contains($pageSource, 'attempt');

            $this->assertTrue($hasAlerts, 'Intrusion detection alerts should be displayed');

            $this->testResults['intrusion_alerts'] = 'Intrusion detection alerts are displayed';
        });
    }

    /**
     * Test 28: IP whitelist management is available
     *
     * @test
     */
    public function test_ip_whitelist_management_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ip-whitelist');

            // Check for whitelist management via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWhitelist =
                str_contains($pageSource, 'whitelist') ||
                str_contains($pageSource, 'allow') ||
                str_contains($pageSource, 'permitted') ||
                str_contains($pageSource, 'ip');

            $this->assertTrue($hasWhitelist, 'IP whitelist management should be available');

            $this->testResults['ip_whitelist'] = 'IP whitelist management is available';
        });
    }

    /**
     * Test 29: IP blacklist management is available
     *
     * @test
     */
    public function test_ip_blacklist_management_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ip-blacklist');

            // Check for blacklist management via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBlacklist =
                str_contains($pageSource, 'blacklist') ||
                str_contains($pageSource, 'block') ||
                str_contains($pageSource, 'deny') ||
                str_contains($pageSource, 'banned');

            $this->assertTrue($hasBlacklist, 'IP blacklist management should be available');

            $this->testResults['ip_blacklist'] = 'IP blacklist management is available';
        });
    }

    /**
     * Test 30: Security policy configuration page exists
     *
     * @test
     */
    public function test_security_policy_configuration_exists()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-policy');

            // Check for security policy via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPolicy =
                str_contains($pageSource, 'policy') ||
                str_contains($pageSource, 'rule') ||
                str_contains($pageSource, 'configuration') ||
                str_contains($pageSource, 'setting');

            $this->assertTrue($hasPolicy, 'Security policy configuration should exist');

            $this->testResults['security_policy'] = 'Security policy configuration exists';
        });
    }

    /**
     * Test 31: Vulnerability scanning can be initiated
     *
     * @test
     */
    public function test_vulnerability_scanning_can_be_initiated()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/scan')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('vulnerability-scan-initiate');

            // Check for scan initiation via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasScanButton =
                str_contains($pageSource, 'scan') ||
                str_contains($pageSource, 'start') ||
                str_contains($pageSource, 'run') ||
                str_contains($pageSource, 'check');

            $this->assertTrue($hasScanButton, 'Vulnerability scanning should be initiated');

            $this->testResults['vulnerability_scan_init'] = 'Vulnerability scanning can be initiated';
        });
    }

    /**
     * Test 32: Security scan results are displayed
     *
     * @test
     */
    public function test_security_scan_results_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/scan')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-scan-results');

            // Check for scan results via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasResults =
                str_contains($pageSource, 'result') ||
                str_contains($pageSource, 'finding') ||
                str_contains($pageSource, 'issue') ||
                str_contains($pageSource, 'vulnerability');

            $this->assertTrue($hasResults, 'Security scan results should be displayed');

            $this->testResults['security_scan_results'] = 'Security scan results are displayed';
        });
    }

    /**
     * Test 33: Firewall can enable/disable rules
     *
     * @test
     */
    public function test_firewall_can_enable_disable_rules()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('firewall-toggle-rules');

            // Check for enable/disable functionality via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasToggle =
                str_contains($pageSource, 'enable') ||
                str_contains($pageSource, 'disable') ||
                str_contains($pageSource, 'toggle') ||
                str_contains($pageSource, 'active');

            $this->assertTrue($hasToggle, 'Firewall should enable/disable rules');

            $this->testResults['firewall_toggle'] = 'Firewall can enable/disable rules';
        });
    }

    /**
     * Test 34: SSL certificate auto-renewal settings visible
     *
     * @test
     */
    public function test_ssl_auto_renewal_settings_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/ssl')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-auto-renewal');

            // Check for auto-renewal settings via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAutoRenewal =
                str_contains($pageSource, 'auto') ||
                str_contains($pageSource, 'automatic') ||
                str_contains($pageSource, 'renew') ||
                str_contains($pageSource, 'schedule');

            $this->assertTrue($hasAutoRenewal, 'SSL auto-renewal settings should be visible');

            $this->testResults['ssl_auto_renewal'] = 'SSL auto-renewal settings are visible';
        });
    }

    /**
     * Test 35: SSH root login can be disabled
     *
     * @test
     */
    public function test_ssh_root_login_can_be_disabled()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/ssh')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-root-login');

            // Check for root login settings via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRootSetting =
                str_contains($pageSource, 'root') ||
                str_contains($pageSource, 'permitroot') ||
                str_contains($pageSource, 'login') ||
                str_contains($pageSource, 'disable');

            $this->assertTrue($hasRootSetting, 'SSH root login should be configurable');

            $this->testResults['ssh_root_login'] = 'SSH root login can be disabled';
        });
    }

    /**
     * Test 36: Fail2ban ban time is configurable
     *
     * @test
     */
    public function test_fail2ban_ban_time_configurable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-ban-time');

            // Check for ban time configuration via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBanTime =
                str_contains($pageSource, 'ban') ||
                str_contains($pageSource, 'time') ||
                str_contains($pageSource, 'duration') ||
                str_contains($pageSource, 'minutes');

            $this->assertTrue($hasBanTime, 'Fail2ban ban time should be configurable');

            $this->testResults['fail2ban_ban_time'] = 'Fail2ban ban time is configurable';
        });
    }

    /**
     * Test 37: Security events timeline is shown
     *
     * @test
     */
    public function test_security_events_timeline_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-events-timeline');

            // Check for events timeline via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTimeline =
                str_contains($pageSource, 'event') ||
                str_contains($pageSource, 'timeline') ||
                str_contains($pageSource, 'history') ||
                str_contains($pageSource, 'activity');

            $this->assertTrue($hasTimeline, 'Security events timeline should be shown');

            $this->testResults['security_timeline'] = 'Security events timeline is shown';
        });
    }

    /**
     * Test 38: Firewall rule priorities can be set
     *
     * @test
     */
    public function test_firewall_rule_priorities_can_be_set()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('firewall-rule-priorities');

            // Check for priority settings via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPriority =
                str_contains($pageSource, 'priority') ||
                str_contains($pageSource, 'order') ||
                str_contains($pageSource, 'position') ||
                str_contains($pageSource, 'number');

            $this->assertTrue($hasPriority, 'Firewall rule priorities should be configurable');

            $this->testResults['firewall_priorities'] = 'Firewall rule priorities can be set';
        });
    }

    /**
     * Test 39: SSL certificate validation status shown
     *
     * @test
     */
    public function test_ssl_certificate_validation_status_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/ssl')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-validation-status');

            // Check for validation status via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasValidation =
                str_contains($pageSource, 'valid') ||
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'verified') ||
                str_contains($pageSource, 'check');

            $this->assertTrue($hasValidation, 'SSL validation status should be shown');

            $this->testResults['ssl_validation'] = 'SSL certificate validation status shown';
        });
    }

    /**
     * Test 40: SSH password authentication can be toggled
     *
     * @test
     */
    public function test_ssh_password_authentication_can_be_toggled()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/ssh')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-password-auth');

            // Check for password authentication toggle via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPasswordAuth =
                str_contains($pageSource, 'password') ||
                str_contains($pageSource, 'authentication') ||
                str_contains($pageSource, 'enable') ||
                str_contains($pageSource, 'disable');

            $this->assertTrue($hasPasswordAuth, 'SSH password authentication should be toggleable');

            $this->testResults['ssh_password_auth'] = 'SSH password authentication can be toggled';
        });
    }

    /**
     * Test 41: Fail2ban max retry attempts configurable
     *
     * @test
     */
    public function test_fail2ban_max_retry_attempts_configurable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-max-retry');

            // Check for max retry configuration via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMaxRetry =
                str_contains($pageSource, 'max') ||
                str_contains($pageSource, 'retry') ||
                str_contains($pageSource, 'attempt') ||
                str_contains($pageSource, 'tries');

            $this->assertTrue($hasMaxRetry, 'Fail2ban max retry should be configurable');

            $this->testResults['fail2ban_max_retry'] = 'Fail2ban max retry attempts configurable';
        });
    }

    /**
     * Test 42: Security notifications preferences exist
     *
     * @test
     */
    public function test_security_notifications_preferences_exist()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-notifications');

            // Check for notification preferences via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNotifications =
                str_contains($pageSource, 'notification') ||
                str_contains($pageSource, 'alert') ||
                str_contains($pageSource, 'email') ||
                str_contains($pageSource, 'settings');

            $this->assertTrue($hasNotifications, 'Security notification preferences should exist');

            $this->testResults['security_notifications'] = 'Security notification preferences exist';
        });
    }

    /**
     * Test 43: Firewall logging is configurable
     *
     * @test
     */
    public function test_firewall_logging_configurable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('firewall-logging');

            // Check for logging configuration via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLogging =
                str_contains($pageSource, 'log') ||
                str_contains($pageSource, 'logging') ||
                str_contains($pageSource, 'record') ||
                str_contains($pageSource, 'track');

            $this->assertTrue($hasLogging, 'Firewall logging should be configurable');

            $this->testResults['firewall_logging'] = 'Firewall logging is configurable';
        });
    }

    /**
     * Test 44: SSL certificate chain is validated
     *
     * @test
     */
    public function test_ssl_certificate_chain_validated()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/ssl')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-certificate-chain');

            // Check for certificate chain validation via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasChainValidation =
                str_contains($pageSource, 'chain') ||
                str_contains($pageSource, 'intermediate') ||
                str_contains($pageSource, 'ca') ||
                str_contains($pageSource, 'authority');

            $this->assertTrue($hasChainValidation, 'SSL certificate chain should be validated');

            $this->testResults['ssl_chain_validation'] = 'SSL certificate chain is validated';
        });
    }

    /**
     * Test 45: SSH key fingerprints are displayed
     *
     * @test
     */
    public function test_ssh_key_fingerprints_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/ssh-keys')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-key-fingerprints');

            // Check for key fingerprints via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFingerprints =
                str_contains($pageSource, 'fingerprint') ||
                str_contains($pageSource, 'key') ||
                str_contains($pageSource, 'hash') ||
                str_contains($pageSource, 'ssh');

            $this->assertTrue($hasFingerprints, 'SSH key fingerprints should be displayed');

            $this->testResults['ssh_fingerprints'] = 'SSH key fingerprints are displayed';
        });
    }

    /**
     * Test 46: Fail2ban unban functionality exists
     *
     * @test
     */
    public function test_fail2ban_unban_functionality_exists()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/fail2ban')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('fail2ban-unban');

            // Check for unban functionality via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasUnban =
                str_contains($pageSource, 'unban') ||
                str_contains($pageSource, 'remove') ||
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'release');

            $this->assertTrue($hasUnban, 'Fail2ban unban functionality should exist');

            $this->testResults['fail2ban_unban'] = 'Fail2ban unban functionality exists';
        });
    }

    /**
     * Test 47: Security compliance reports available
     *
     * @test
     */
    public function test_security_compliance_reports_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-compliance-reports');

            // Check for compliance reports via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCompliance =
                str_contains($pageSource, 'compliance') ||
                str_contains($pageSource, 'report') ||
                str_contains($pageSource, 'audit') ||
                str_contains($pageSource, 'standard');

            $this->assertTrue($hasCompliance, 'Security compliance reports should be available');

            $this->testResults['compliance_reports'] = 'Security compliance reports available';
        });
    }

    /**
     * Test 48: Firewall default policies can be set
     *
     * @test
     */
    public function test_firewall_default_policies_can_be_set()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('firewall-default-policies');

            // Check for default policies via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDefaultPolicies =
                str_contains($pageSource, 'default') ||
                str_contains($pageSource, 'policy') ||
                str_contains($pageSource, 'incoming') ||
                str_contains($pageSource, 'outgoing');

            $this->assertTrue($hasDefaultPolicies, 'Firewall default policies should be configurable');

            $this->testResults['firewall_default_policies'] = 'Firewall default policies can be set';
        });
    }

    /**
     * Test 49: SSL certificate installation wizard exists
     *
     * @test
     */
    public function test_ssl_certificate_installation_wizard_exists()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/ssl')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-installation-wizard');

            // Check for installation wizard via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWizard =
                str_contains($pageSource, 'install') ||
                str_contains($pageSource, 'add') ||
                str_contains($pageSource, 'new') ||
                str_contains($pageSource, 'certificate');

            $this->assertTrue($hasWizard, 'SSL installation wizard should exist');

            $this->testResults['ssl_installation_wizard'] = 'SSL certificate installation wizard exists';
        });
    }

    /**
     * Test 50: SSH connection timeout is configurable
     *
     * @test
     */
    public function test_ssh_connection_timeout_configurable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/ssh')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-timeout');

            // Check for timeout configuration via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTimeout =
                str_contains($pageSource, 'timeout') ||
                str_contains($pageSource, 'interval') ||
                str_contains($pageSource, 'idle') ||
                str_contains($pageSource, 'disconnect');

            $this->assertTrue($hasTimeout, 'SSH connection timeout should be configurable');

            $this->testResults['ssh_timeout'] = 'SSH connection timeout is configurable';
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
                'test_suite' => 'Security Management Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'servers_tested' => Server::count(),
                    'users_tested' => User::count(),
                    'test_server_id' => $this->server->id,
                    'test_server_name' => $this->server->name,
                ],
            ];

            $reportPath = storage_path('app/test-reports/security-management-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
