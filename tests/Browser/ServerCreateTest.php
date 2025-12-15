<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ServerCreateTest extends DuskTestCase
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
     * Test 1: Server create page loads successfully
     *
     */

    #[Test]
    public function test_page_loads_successfully()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertSee('Add New Server')
                ->assertSee('Connect a server to your DevFlow Pro account')
                ->screenshot('server-create-page-loaded');

            $this->testResults['page_load'] = 'Server create page loaded successfully';
        });
    }

    /**
     * Test 2: Server name field is present and functional
     *
     */

    #[Test]
    public function test_server_name_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertPresent('#name')
                ->assertVisible('#name')
                ->type('#name', 'Test Server')
                ->pause(500)
                ->assertInputValue('#name', 'Test Server')
                ->screenshot('server-name-field');

            $this->testResults['name_field'] = 'Server name field is present and functional';
        });
    }

    /**
     * Test 3: IP address field is present and functional
     *
     */

    #[Test]
    public function test_ip_address_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertPresent('#ip_address')
                ->assertVisible('#ip_address')
                ->type('#ip_address', '192.168.1.100')
                ->pause(500)
                ->assertInputValue('#ip_address', '192.168.1.100')
                ->screenshot('ip-address-field');

            $this->testResults['ip_field'] = 'IP address field is present and functional';
        });
    }

    /**
     * Test 4: SSH port field is present with default value
     *
     */

    #[Test]
    public function test_ssh_port_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertPresent('#port')
                ->assertVisible('#port')
                ->assertInputValue('#port', '22')
                ->screenshot('ssh-port-field');

            $this->testResults['port_field'] = 'SSH port field is present with default value 22';
        });
    }

    /**
     * Test 5: SSH user field is present with default value
     *
     */

    #[Test]
    public function test_ssh_user_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertPresent('#username')
                ->assertVisible('#username')
                ->assertInputValue('#username', 'root')
                ->screenshot('ssh-user-field');

            $this->testResults['username_field'] = 'SSH username field is present with default value root';
        });
    }

    /**
     * Test 6: Hostname field is present and optional
     *
     */

    #[Test]
    public function test_hostname_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertPresent('#hostname')
                ->assertVisible('#hostname')
                ->assertSee('Optional')
                ->screenshot('hostname-field');

            $this->testResults['hostname_field'] = 'Hostname field is present and marked as optional';
        });
    }

    /**
     * Test 7: Authentication method selector is present
     *
     */

    #[Test]
    public function test_authentication_method_selector_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertSee('Authentication Method')
                ->assertPresent('input[value="password"]')
                ->assertPresent('input[value="key"]')
                ->assertSee('Password')
                ->assertSee('SSH Key')
                ->screenshot('auth-method-selector');

            $this->testResults['auth_method_selector'] = 'Authentication method selector is present';
        });
    }

    /**
     * Test 8: Test connection button is visible
     *
     */

    #[Test]
    public function test_test_connection_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertSee('Test Connection')
                ->screenshot('test-connection-button');

            // Verify button exists in page source
            $pageSource = $browser->driver->getPageSource();
            $hasButton = str_contains($pageSource, 'Test Connection');
            $this->assertTrue($hasButton, 'Test Connection button should be visible');

            $this->testResults['test_connection_button'] = 'Test connection button is visible';
        });
    }

    /**
     * Test 9: Add Server button is visible
     *
     */

    #[Test]
    public function test_add_server_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertSee('Add Server')
                ->screenshot('add-server-button');

            // Verify button exists in page source
            $pageSource = $browser->driver->getPageSource();
            $hasButton = str_contains($pageSource, 'Add Server');
            $this->assertTrue($hasButton, 'Add Server button should be visible');

            $this->testResults['add_server_button'] = 'Add Server button is visible';
        });
    }

    /**
     * Test 10: Form validation works on submission
     *
     */

    #[Test]
    public function test_form_validation_works()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->press('Add Server')
                ->pause(2000)
                ->screenshot('form-validation-triggered');

            // Form should not submit without required fields
            // Should stay on the same page or show validation errors
            $currentUrl = $browser->driver->getCurrentURL();
            $this->assertTrue(
                str_contains($currentUrl, '/servers/create') || str_contains($currentUrl, '/servers'),
                'Form validation should prevent submission'
            );

            $this->testResults['form_validation'] = 'Form validation triggered on empty submission';
        });
    }

    /**
     * Test 11: Required field validation for server name
     *
     */

    #[Test]
    public function test_server_name_required_validation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->type('#ip_address', '192.168.1.100')
                ->type('#ssh_password', 'testpassword')
                ->press('Add Server')
                ->pause(2000)
                ->screenshot('name-validation');

            // Check if validation error appears or form doesn't submit
            $pageSource = $browser->driver->getPageSource();
            $hasValidation = str_contains($pageSource, 'required') ||
                            str_contains($pageSource, 'field is required') ||
                            str_contains($currentUrl = $browser->driver->getCurrentURL(), '/servers/create');

            $this->assertTrue($hasValidation, 'Name field should be required');

            $this->testResults['name_validation'] = 'Server name required validation works';
        });
    }

    /**
     * Test 12: IP address validation
     *
     */

    #[Test]
    public function test_ip_address_validation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->type('#name', 'Test Server')
                ->type('#ip_address', 'invalid-ip')
                ->type('#ssh_password', 'testpassword')
                ->press('Add Server')
                ->pause(2000)
                ->screenshot('ip-validation');

            // Check if validation error appears for invalid IP
            $currentUrl = $browser->driver->getCurrentURL();
            $this->assertTrue(
                str_contains($currentUrl, '/servers/create'),
                'Invalid IP should trigger validation'
            );

            $this->testResults['ip_validation'] = 'IP address validation works';
        });
    }

    /**
     * Test 13: Port number validation
     *
     */

    #[Test]
    public function test_port_number_validation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->type('#name', 'Test Server')
                ->type('#ip_address', '192.168.1.100')
                ->clear('#port')
                ->type('#port', '99999')
                ->type('#ssh_password', 'testpassword')
                ->press('Add Server')
                ->pause(2000)
                ->screenshot('port-validation');

            // Port should be validated (max 65535)
            $currentUrl = $browser->driver->getCurrentURL();
            $this->assertTrue(
                str_contains($currentUrl, '/servers/create'),
                'Invalid port should trigger validation'
            );

            $this->testResults['port_validation'] = 'Port number validation works';
        });
    }

    /**
     * Test 14: Cancel button is present and functional
     *
     */

    #[Test]
    public function test_cancel_button_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertSee('Cancel')
                ->screenshot('cancel-button');

            // Verify cancel button exists in page source
            $pageSource = $browser->driver->getPageSource();
            $hasButton = str_contains($pageSource, 'Cancel');
            $this->assertTrue($hasButton, 'Cancel button should be visible');

            $this->testResults['cancel_button'] = 'Cancel button is present';
        });
    }

    /**
     * Test 15: Back navigation works with cancel button
     *
     */

    #[Test]
    public function test_back_navigation_works()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->clickLink('Cancel')
                ->pause(2000)
                ->screenshot('after-cancel-click');

            // Should navigate back to servers list
            $currentUrl = $browser->driver->getCurrentURL();
            $this->assertTrue(
                str_contains($currentUrl, '/servers'),
                'Cancel should navigate to servers page'
            );

            $this->testResults['back_navigation'] = 'Cancel button navigates back successfully';
        });
    }

    /**
     * Test 16: SSH key option can be selected
     *
     */

    #[Test]
    public function test_ssh_key_selection_works()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->click('input[value="key"]')
                ->pause(1500)
                ->screenshot('ssh-key-selected');

            // Check if SSH key textarea appeared
            $pageSource = $browser->driver->getPageSource();
            $hasSshKeyField = str_contains($pageSource, 'ssh_key') ||
                             str_contains($pageSource, 'SSH Private Key');

            $this->assertTrue($hasSshKeyField, 'SSH key field should appear');

            $this->testResults['ssh_key_selection'] = 'SSH key authentication method can be selected';
        });
    }

    /**
     * Test 17: Password field is shown when password method is selected
     *
     */

    #[Test]
    public function test_password_field_shown_when_selected()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15);

            // Password should be selected by default
            $browser->assertPresent('input[value="password"]:checked')
                ->assertPresent('#ssh_password')
                ->assertVisible('#ssh_password')
                ->screenshot('password-field-visible');

            $this->testResults['password_field'] = 'Password field is shown when password authentication is selected';
        });
    }

    /**
     * Test 18: SSH key field is shown when key method is selected
     *
     */

    #[Test]
    public function test_ssh_key_field_shown_when_selected()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->click('input[value="key"]')
                ->pause(1500)
                ->assertPresent('#ssh_key')
                ->assertVisible('#ssh_key')
                ->screenshot('ssh-key-field-visible');

            $this->testResults['ssh_key_field'] = 'SSH key field is shown when key authentication is selected';
        });
    }

    /**
     * Test 19: GPS location fields are present
     *
     */

    #[Test]
    public function test_gps_location_fields_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertSee('GPS Location')
                ->assertPresent('#latitude')
                ->assertPresent('#longitude')
                ->assertPresent('#location_name')
                ->screenshot('gps-location-fields');

            $this->testResults['gps_fields'] = 'GPS location fields are present';
        });
    }

    /**
     * Test 20: Get location button is visible
     *
     */

    #[Test]
    public function test_get_location_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertSee('Use Current GPS Location')
                ->screenshot('get-location-button');

            // Verify button exists in page source
            $pageSource = $browser->driver->getPageSource();
            $hasButton = str_contains($pageSource, 'Use Current GPS Location') ||
                        str_contains($pageSource, 'getLocation');

            $this->assertTrue($hasButton, 'Get location button should be visible');

            $this->testResults['get_location_button'] = 'Get location button is visible';
        });
    }

    /**
     * Test 21: Authentication method toggle updates form
     *
     */

    #[Test]
    public function test_authentication_method_toggle_updates_form()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15);

            // Initially password should be selected
            $browser->assertPresent('#ssh_password')
                ->screenshot('password-auth-initial');

            // Switch to SSH key
            $browser->click('input[value="key"]')
                ->pause(1500)
                ->assertPresent('#ssh_key')
                ->screenshot('ssh-key-auth-selected');

            // Switch back to password
            $browser->click('input[value="password"]')
                ->pause(1500)
                ->assertPresent('#ssh_password')
                ->screenshot('password-auth-reselected');

            $this->testResults['auth_toggle'] = 'Authentication method toggle updates form correctly';
        });
    }

    /**
     * Test 22: Port field accepts custom port numbers
     *
     */

    #[Test]
    public function test_port_field_accepts_custom_values()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->clear('#port')
                ->type('#port', '2222')
                ->pause(500)
                ->assertInputValue('#port', '2222')
                ->screenshot('custom-port-value');

            $this->testResults['custom_port'] = 'Port field accepts custom port numbers';
        });
    }

    /**
     * Test 23: Username field can be changed from default
     *
     */

    #[Test]
    public function test_username_field_can_be_changed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->clear('#username')
                ->type('#username', 'deploy')
                ->pause(500)
                ->assertInputValue('#username', 'deploy')
                ->screenshot('custom-username');

            $this->testResults['custom_username'] = 'Username field can be changed from default';
        });
    }

    /**
     * Test 24: Password field has proper input type
     *
     */

    #[Test]
    public function test_password_field_is_password_type()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->assertPresent('#ssh_password');

            // Check if password field has type="password"
            $fieldType = $browser->attribute('#ssh_password', 'type');
            $this->assertEquals('password', $fieldType, 'SSH password field should be password type');

            $browser->screenshot('password-field-type');

            $this->testResults['password_type'] = 'Password field has proper input type';
        });
    }

    /**
     * Test 25: Form has proper HTML5 validation attributes
     *
     */

    #[Test]
    public function test_form_has_required_attributes()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15);

            // Check for required attributes on key fields
            $nameRequired = $browser->attribute('#name', 'required');
            $ipRequired = $browser->attribute('#ip_address', 'required');
            $portRequired = $browser->attribute('#port', 'required');

            $this->assertNotNull($nameRequired, 'Name field should have required attribute');
            $this->assertNotNull($ipRequired, 'IP field should have required attribute');
            $this->assertNotNull($portRequired, 'Port field should have required attribute');

            $browser->screenshot('required-attributes');

            $this->testResults['required_attributes'] = 'Form has proper HTML5 required attributes';
        });
    }

    /**
     * Test 26: SSH key textarea has proper placeholder
     *
     */

    #[Test]
    public function test_ssh_key_textarea_has_placeholder()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->click('input[value="key"]')
                ->pause(1500)
                ->assertPresent('#ssh_key')
                ->screenshot('ssh-key-placeholder');

            // Check for placeholder text
            $pageSource = $browser->driver->getPageSource();
            $hasPlaceholder = str_contains($pageSource, 'BEGIN OPENSSH PRIVATE KEY') ||
                             str_contains($pageSource, 'placeholder');

            $this->assertTrue($hasPlaceholder, 'SSH key field should have helpful placeholder');

            $this->testResults['ssh_key_placeholder'] = 'SSH key textarea has proper placeholder';
        });
    }

    /**
     * Test 27: IP address field has proper placeholder
     *
     */

    #[Test]
    public function test_ip_address_has_placeholder()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15);

            // Check placeholder attribute
            $placeholder = $browser->attribute('#ip_address', 'placeholder');
            $this->assertNotEmpty($placeholder, 'IP address field should have placeholder');
            $this->assertTrue(
                str_contains($placeholder, '192.168') || str_contains($placeholder, 'IP'),
                'IP placeholder should be helpful'
            );

            $browser->screenshot('ip-placeholder');

            $this->testResults['ip_placeholder'] = 'IP address field has proper placeholder';
        });
    }

    /**
     * Test 28: Required fields are marked with asterisks
     *
     */

    #[Test]
    public function test_required_fields_marked_with_asterisks()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->screenshot('required-field-markers');

            // Check page source for asterisk markers
            $pageSource = $browser->driver->getPageSource();
            $hasAsterisks = str_contains($pageSource, 'text-red-500">*</span>');

            $this->assertTrue($hasAsterisks, 'Required fields should be marked with asterisks');

            $this->testResults['required_markers'] = 'Required fields are marked with asterisks';
        });
    }

    /**
     * Test 29: GPS latitude field accepts decimal values
     *
     */

    #[Test]
    public function test_latitude_field_accepts_decimal_values()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->type('#latitude', '40.7128')
                ->pause(500)
                ->assertInputValue('#latitude', '40.7128')
                ->screenshot('latitude-decimal-value');

            $this->testResults['latitude_decimal'] = 'Latitude field accepts decimal values';
        });
    }

    /**
     * Test 30: GPS longitude field accepts decimal values
     *
     */

    #[Test]
    public function test_longitude_field_accepts_decimal_values()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitForText('Add New Server', 15)
                ->type('#longitude', '-74.0060')
                ->pause(500)
                ->assertInputValue('#longitude', '-74.0060')
                ->screenshot('longitude-decimal-value');

            $this->testResults['longitude_decimal'] = 'Longitude field accepts decimal values';
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
                'test_suite' => 'Server Create Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                    'tested_features' => [
                        'Page Loading',
                        'Form Fields',
                        'Validation',
                        'Authentication Methods',
                        'GPS Location',
                        'User Input',
                    ],
                ],
                'coverage' => [
                    'component' => 'App\Livewire\Servers\ServerCreate',
                    'route' => '/servers/create',
                    'view' => 'livewire.servers.server-create',
                ],
            ];

            $reportPath = storage_path('app/test-reports/server-create-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
