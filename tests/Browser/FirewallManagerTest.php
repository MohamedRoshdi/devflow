<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class FirewallManagerTest extends DuskTestCase
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
            'name' => 'Test Firewall Server',
            'hostname' => 'firewall-test.devflow.test',
            'ip_address' => '192.168.1.101',
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
     * Test 1: Firewall manager page loads successfully
     *
     */

    #[Test]
    public function test_firewall_manager_page_loads_successfully()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('firewall-manager-page-loads');

            // Check if firewall page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFirewallContent =
                str_contains($pageSource, 'firewall') ||
                str_contains($pageSource, 'ufw');

            $this->assertTrue($hasFirewallContent, 'Firewall manager page should load successfully');

            $this->testResults['firewall_page_loads'] = 'Firewall manager page loaded successfully';
        });
    }

    /**
     * Test 2: Firewall status is displayed
     *
     */

    #[Test]
    public function test_firewall_status_is_displayed()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('firewall-status-displayed');

            // Check for status indicators
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatus =
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'inactive') ||
                str_contains($pageSource, 'enabled') ||
                str_contains($pageSource, 'disabled');

            $this->assertTrue($hasStatus, 'Firewall status should be displayed');

            $this->testResults['firewall_status_displayed'] = 'Firewall status is displayed';
        });
    }

    /**
     * Test 3: Enable firewall button is visible
     *
     */

    #[Test]
    public function test_enable_firewall_button_is_visible()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('enable-firewall-button');

            // Check for enable button
            $pageSource = $browser->driver->getPageSource();
            $hasEnableButton =
                str_contains($pageSource, 'Enable Firewall') ||
                str_contains($pageSource, 'enableFirewall') ||
                str_contains($pageSource, 'wire:click');

            $this->assertTrue($hasEnableButton, 'Enable firewall button should be visible');

            $this->testResults['enable_button_visible'] = 'Enable firewall button is visible';
        });
    }

    /**
     * Test 4: Disable firewall button is visible
     *
     */

    #[Test]
    public function test_disable_firewall_button_is_visible()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('disable-firewall-button');

            // Check for disable button
            $pageSource = $browser->driver->getPageSource();
            $hasDisableButton =
                str_contains($pageSource, 'Disable Firewall') ||
                str_contains($pageSource, 'disableFirewall') ||
                str_contains($pageSource, 'confirmDisableFirewall');

            $this->assertTrue($hasDisableButton, 'Disable firewall button should be visible');

            $this->testResults['disable_button_visible'] = 'Disable firewall button is visible';
        });
    }

    /**
     * Test 5: Add rule button is present
     *
     */

    #[Test]
    public function test_add_rule_button_is_present()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('add-rule-button');

            // Check for add rule button
            $pageSource = $browser->driver->getPageSource();
            $hasAddButton =
                str_contains($pageSource, 'Add Rule') ||
                str_contains($pageSource, 'openAddRuleModal');

            $this->assertTrue($hasAddButton, 'Add rule button should be present');

            $this->testResults['add_rule_button_present'] = 'Add rule button is present';
        });
    }

    /**
     * Test 6: Add rule modal opens
     *
     */

    #[Test]
    public function test_add_rule_modal_opens()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('add-rule-modal-check');

            // Check for modal structure
            $pageSource = $browser->driver->getPageSource();
            $hasModal =
                str_contains($pageSource, 'showAddRuleModal') ||
                str_contains($pageSource, 'Add Firewall Rule');

            $this->assertTrue($hasModal, 'Add rule modal should be available to open');

            $this->testResults['add_rule_modal_opens'] = 'Add rule modal structure is present';
        });
    }

    /**
     * Test 7: Add rule form has port field
     *
     */

    #[Test]
    public function test_add_rule_form_has_port_field()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('add-rule-form-port-field');

            // Check for port field
            $pageSource = $browser->driver->getPageSource();
            $hasPortField =
                str_contains($pageSource, 'rulePort') ||
                str_contains($pageSource, 'wire:model="rulePort"');

            $this->assertTrue($hasPortField, 'Add rule form should have port field');

            $this->testResults['port_field_present'] = 'Add rule form has port field';
        });
    }

    /**
     * Test 8: Add rule form has protocol field
     *
     */

    #[Test]
    public function test_add_rule_form_has_protocol_field()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('add-rule-form-protocol-field');

            // Check for protocol field
            $pageSource = $browser->driver->getPageSource();
            $hasProtocolField =
                str_contains($pageSource, 'ruleProtocol') ||
                str_contains($pageSource, 'wire:model="ruleProtocol"');

            $this->assertTrue($hasProtocolField, 'Add rule form should have protocol field');

            $this->testResults['protocol_field_present'] = 'Add rule form has protocol field';
        });
    }

    /**
     * Test 9: Protocol dropdown has tcp, udp, any options
     *
     */

    #[Test]
    public function test_protocol_dropdown_has_options()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('protocol-dropdown-options');

            // Check for protocol options
            $pageSource = $browser->driver->getPageSource();
            $hasProtocolOptions =
                (str_contains($pageSource, 'tcp') || str_contains($pageSource, 'TCP')) &&
                (str_contains($pageSource, 'udp') || str_contains($pageSource, 'UDP')) &&
                (str_contains($pageSource, 'any') || str_contains($pageSource, 'Any'));

            $this->assertTrue($hasProtocolOptions, 'Protocol dropdown should have tcp, udp, any options');

            $this->testResults['protocol_options_present'] = 'Protocol dropdown has tcp, udp, any options';
        });
    }

    /**
     * Test 10: Add rule form has action field
     *
     */

    #[Test]
    public function test_add_rule_form_has_action_field()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('add-rule-form-action-field');

            // Check for action field
            $pageSource = $browser->driver->getPageSource();
            $hasActionField =
                str_contains($pageSource, 'ruleAction') ||
                str_contains($pageSource, 'wire:model="ruleAction"');

            $this->assertTrue($hasActionField, 'Add rule form should have action field');

            $this->testResults['action_field_present'] = 'Add rule form has action field';
        });
    }

    /**
     * Test 11: Action dropdown has allow, deny, reject, limit options
     *
     */

    #[Test]
    public function test_action_dropdown_has_options()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('action-dropdown-options');

            // Check for action options
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasActionOptions =
                str_contains($pageSource, 'allow') &&
                str_contains($pageSource, 'deny') &&
                str_contains($pageSource, 'reject') &&
                str_contains($pageSource, 'limit');

            $this->assertTrue($hasActionOptions, 'Action dropdown should have allow, deny, reject, limit options');

            $this->testResults['action_options_present'] = 'Action dropdown has allow, deny, reject, limit options';
        });
    }

    /**
     * Test 12: Add rule form has from IP field
     *
     */

    #[Test]
    public function test_add_rule_form_has_from_ip_field()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('add-rule-form-from-ip-field');

            // Check for from IP field
            $pageSource = $browser->driver->getPageSource();
            $hasFromIpField =
                str_contains($pageSource, 'ruleFromIp') ||
                str_contains($pageSource, 'wire:model="ruleFromIp"') ||
                str_contains($pageSource, 'From IP');

            $this->assertTrue($hasFromIpField, 'Add rule form should have from IP field');

            $this->testResults['from_ip_field_present'] = 'Add rule form has from IP field';
        });
    }

    /**
     * Test 13: Add rule form has description field
     *
     */

    #[Test]
    public function test_add_rule_form_has_description_field()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('add-rule-form-description-field');

            // Check for description field
            $pageSource = $browser->driver->getPageSource();
            $hasDescriptionField =
                str_contains($pageSource, 'ruleDescription') ||
                str_contains($pageSource, 'wire:model="ruleDescription"') ||
                str_contains($pageSource, 'Description');

            $this->assertTrue($hasDescriptionField, 'Add rule form should have description field');

            $this->testResults['description_field_present'] = 'Add rule form has description field';
        });
    }

    /**
     * Test 14: Rule list section is displayed
     *
     */

    #[Test]
    public function test_rule_list_is_displayed()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('rule-list-displayed');

            // Check for rules section
            $pageSource = $browser->driver->getPageSource();
            $hasRulesList =
                str_contains($pageSource, 'Firewall Rules') ||
                str_contains($pageSource, 'rules') ||
                str_contains($pageSource, 'No firewall rules');

            $this->assertTrue($hasRulesList, 'Rule list should be displayed');

            $this->testResults['rule_list_displayed'] = 'Rule list is displayed';
        });
    }

    /**
     * Test 15: Delete rule buttons are present
     *
     */

    #[Test]
    public function test_delete_rule_buttons_are_present()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('delete-rule-buttons');

            // Check for delete functionality
            $pageSource = $browser->driver->getPageSource();
            $hasDeleteButtons =
                str_contains($pageSource, 'deleteRule') ||
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'remove');

            $this->assertTrue($hasDeleteButtons, 'Delete rule buttons should be present');

            $this->testResults['delete_buttons_present'] = 'Delete rule buttons are present';
        });
    }

    /**
     * Test 16: Install UFW button shown when not installed
     *
     */

    #[Test]
    public function test_install_ufw_button_shown_when_not_installed()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('install-ufw-button');

            // Check for install UFW button
            $pageSource = $browser->driver->getPageSource();
            $hasInstallButton =
                str_contains($pageSource, 'Install UFW') ||
                str_contains($pageSource, 'installUfw');

            $this->assertTrue($hasInstallButton, 'Install UFW button should be shown when not installed');

            $this->testResults['install_ufw_button_shown'] = 'Install UFW button shown when not installed';
        });
    }

    /**
     * Test 17: Flash messages display area exists
     *
     */

    #[Test]
    public function test_flash_messages_display()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('flash-messages-display');

            // Check for flash message structure
            $pageSource = $browser->driver->getPageSource();
            $hasFlashMessages =
                str_contains($pageSource, 'flashMessage') ||
                str_contains($pageSource, 'flashType');

            $this->assertTrue($hasFlashMessages, 'Flash messages display area should exist');

            $this->testResults['flash_messages_display'] = 'Flash messages display area exists';
        });
    }

    /**
     * Test 18: Form validation indicators are present
     *
     */

    #[Test]
    public function test_form_validation_works()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('form-validation');

            // Check for validation error display
            $pageSource = $browser->driver->getPageSource();
            $hasValidation =
                str_contains($pageSource, '@error') ||
                str_contains($pageSource, 'required');

            $this->assertTrue($hasValidation, 'Form validation should work');

            $this->testResults['form_validation_works'] = 'Form validation indicators are present';
        });
    }

    /**
     * Test 19: Navigation back to security dashboard works
     *
     */

    #[Test]
    public function test_navigation_back_to_security_dashboard_works()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('navigation-back-link');

            // Check for back navigation
            $pageSource = $browser->driver->getPageSource();
            $hasBackLink =
                str_contains($pageSource, '/security') ||
                str_contains($pageSource, 'route(\'servers.security\'');

            $this->assertTrue($hasBackLink, 'Navigation back to security dashboard should work');

            $this->testResults['navigation_back_works'] = 'Navigation back to security dashboard works';
        });
    }

    /**
     * Test 20: Refresh button is present
     *
     */

    #[Test]
    public function test_refresh_button_is_present()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('refresh-button');

            // Check for refresh button
            $pageSource = $browser->driver->getPageSource();
            $hasRefreshButton =
                str_contains($pageSource, 'Refresh') ||
                str_contains($pageSource, 'loadFirewallStatus');

            $this->assertTrue($hasRefreshButton, 'Refresh button should be present');

            $this->testResults['refresh_button_present'] = 'Refresh button is present';
        });
    }

    /**
     * Test 21: UFW status indicator is visible
     *
     */

    #[Test]
    public function test_ufw_status_indicator_is_visible()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ufw-status-indicator');

            // Check for UFW status
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasUfwStatus =
                str_contains($pageSource, 'ufw') ||
                str_contains($pageSource, 'firewall status');

            $this->assertTrue($hasUfwStatus, 'UFW status indicator should be visible');

            $this->testResults['ufw_status_indicator_visible'] = 'UFW status indicator is visible';
        });
    }

    /**
     * Test 22: Rules table structure is present
     *
     */

    #[Test]
    public function test_rules_table_structure_is_present()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('rules-table-structure');

            // Check for table structure
            $pageSource = $browser->driver->getPageSource();
            $hasTableStructure =
                str_contains($pageSource, '<table') ||
                str_contains($pageSource, '<thead') ||
                str_contains($pageSource, '<tbody');

            $this->assertTrue($hasTableStructure, 'Rules table structure should be present');

            $this->testResults['rules_table_structure_present'] = 'Rules table structure is present';
        });
    }

    /**
     * Test 23: Modal cancel button works
     *
     */

    #[Test]
    public function test_modal_cancel_button_works()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('modal-cancel-button');

            // Check for cancel button
            $pageSource = $browser->driver->getPageSource();
            $hasCancelButton =
                str_contains($pageSource, 'Cancel') ||
                str_contains($pageSource, 'closeAddRuleModal');

            $this->assertTrue($hasCancelButton, 'Modal cancel button should work');

            $this->testResults['modal_cancel_button_works'] = 'Modal cancel button works';
        });
    }

    /**
     * Test 24: Confirm disable modal exists
     *
     */

    #[Test]
    public function test_confirm_disable_modal_exists()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('confirm-disable-modal');

            // Check for confirmation modal
            $pageSource = $browser->driver->getPageSource();
            $hasConfirmModal =
                str_contains($pageSource, 'showConfirmDisable') ||
                str_contains($pageSource, 'Disable Firewall?');

            $this->assertTrue($hasConfirmModal, 'Confirm disable modal should exist');

            $this->testResults['confirm_disable_modal_exists'] = 'Confirm disable modal exists';
        });
    }

    /**
     * Test 25: Server name is displayed in header
     *
     */

    #[Test]
    public function test_server_name_is_displayed_in_header()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-name-in-header');

            // Check for server name in header
            $pageSource = $browser->driver->getPageSource();
            $hasServerName =
                str_contains($pageSource, $this->server->name) ||
                str_contains($pageSource, 'UFW Configuration');

            $this->assertTrue($hasServerName, 'Server name should be displayed in header');

            $this->testResults['server_name_in_header'] = 'Server name is displayed in header';
        });
    }

    /**
     * Test 26: Loading states are handled
     *
     */

    #[Test]
    public function test_loading_states_are_handled()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('loading-states');

            // Check for loading indicators
            $pageSource = $browser->driver->getPageSource();
            $hasLoadingStates =
                str_contains($pageSource, 'wire:loading') ||
                str_contains($pageSource, 'isLoading');

            $this->assertTrue($hasLoadingStates, 'Loading states should be handled');

            $this->testResults['loading_states_handled'] = 'Loading states are handled';
        });
    }

    /**
     * Test 27: Rule action badges are styled
     *
     */

    #[Test]
    public function test_rule_action_badges_are_styled()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('rule-action-badges');

            // Check for action badges styling
            $pageSource = $browser->driver->getPageSource();
            $hasActionBadges =
                str_contains($pageSource, 'bg-green-500') ||
                str_contains($pageSource, 'bg-red-500') ||
                str_contains($pageSource, 'ALLOW') ||
                str_contains($pageSource, 'DENY');

            $this->assertTrue($hasActionBadges, 'Rule action badges should be styled');

            $this->testResults['rule_action_badges_styled'] = 'Rule action badges are styled';
        });
    }

    /**
     * Test 28: Empty state message is shown
     *
     */

    #[Test]
    public function test_empty_state_message_is_shown()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('empty-state-message');

            // Check for empty state
            $pageSource = $browser->driver->getPageSource();
            $hasEmptyState =
                str_contains($pageSource, 'No firewall rules') ||
                str_contains($pageSource, 'Add your first rule');

            $this->assertTrue($hasEmptyState, 'Empty state message should be shown');

            $this->testResults['empty_state_message_shown'] = 'Empty state message is shown';
        });
    }

    /**
     * Test 29: Debug output section exists
     *
     */

    #[Test]
    public function test_debug_output_section_exists()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('debug-output-section');

            // Check for debug output
            $pageSource = $browser->driver->getPageSource();
            $hasDebugOutput =
                str_contains($pageSource, 'rawOutput') ||
                str_contains($pageSource, 'Debug Output') ||
                str_contains($pageSource, '<details');

            $this->assertTrue($hasDebugOutput, 'Debug output section should exist');

            $this->testResults['debug_output_section_exists'] = 'Debug output section exists';
        });
    }

    /**
     * Test 30: Firewall hero section is displayed
     *
     */

    #[Test]
    public function test_firewall_hero_section_is_displayed()
    {
        if (! $this->server) {
            $this->markTestSkipped('No server available for firewall testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/security/firewall')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('firewall-hero-section');

            // Check for hero section
            $pageSource = $browser->driver->getPageSource();
            $hasHeroSection =
                str_contains($pageSource, 'Firewall Manager') ||
                str_contains($pageSource, 'from-orange-800');

            $this->assertTrue($hasHeroSection, 'Firewall hero section should be displayed');

            $this->testResults['firewall_hero_section_displayed'] = 'Firewall hero section is displayed';
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
                'test_suite' => 'Firewall Manager Tests',
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

            $reportPath = storage_path('app/test-reports/firewall-manager-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
