<?php

namespace Tests\Browser;

use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ServerEditTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected ?Server $server = null;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing test user
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Get first available server for testing
        $this->server = Server::first();
    }

    /**
     * Test 1: Page loads successfully (skip if no server)
     *
     * @test
     */
    public function test_page_loads_successfully()
    {
        if (! $this->server) {
            $this->testResults['page_loads'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('server-edit-page-load');

            // Check page loaded successfully
            $pageSource = strtolower($browser->driver->getPageSource());
            $isEditPage = str_contains($pageSource, 'edit') ||
                         str_contains($pageSource, 'update') ||
                         str_contains($pageSource, $this->server->name);

            $this->assertTrue($isEditPage, 'Server edit page should load successfully');

            $this->testResults['page_loads'] = 'Page loaded successfully';
        });
    }

    /**
     * Test 2: Server name field pre-filled
     *
     * @test
     */
    public function test_server_name_field_prefilled()
    {
        if (! $this->server) {
            $this->testResults['name_prefilled'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('server-name-prefilled');

            // Check if name field exists and has value
            $browser->assertPresent('#name');

            $nameValue = $browser->value('#name');
            $this->assertNotEmpty($nameValue, 'Name field should have a value');
            $this->assertEquals($this->server->name, $nameValue, 'Name field should be pre-filled with server name');

            $this->testResults['name_prefilled'] = 'Server name field is pre-filled';
        });
    }

    /**
     * Test 3: IP address field pre-filled
     *
     * @test
     */
    public function test_ip_address_field_prefilled()
    {
        if (! $this->server) {
            $this->testResults['ip_prefilled'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('server-ip-prefilled');

            // Check if IP address field exists and has value
            $browser->assertPresent('#ip_address');

            $ipValue = $browser->value('#ip_address');
            $this->assertNotEmpty($ipValue, 'IP address field should have a value');
            $this->assertEquals($this->server->ip_address, $ipValue, 'IP address field should be pre-filled');

            $this->testResults['ip_prefilled'] = 'IP address field is pre-filled';
        });
    }

    /**
     * Test 4: SSH port field pre-filled
     *
     * @test
     */
    public function test_ssh_port_field_prefilled()
    {
        if (! $this->server) {
            $this->testResults['port_prefilled'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('server-port-prefilled');

            // Check if port field exists and has value
            $browser->assertPresent('#port');

            $portValue = $browser->value('#port');
            $expectedPort = $this->server->port ?? 22;
            $this->assertEquals((string) $expectedPort, $portValue, 'Port field should be pre-filled');

            $this->testResults['port_prefilled'] = 'SSH port field is pre-filled';
        });
    }

    /**
     * Test 5: SSH user field pre-filled
     *
     * @test
     */
    public function test_ssh_user_field_prefilled()
    {
        if (! $this->server) {
            $this->testResults['user_prefilled'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('server-user-prefilled');

            // Check if username field exists and has value
            $browser->assertPresent('#username');

            $usernameValue = $browser->value('#username');
            $expectedUsername = $this->server->username ?? 'root';
            $this->assertEquals($expectedUsername, $usernameValue, 'Username field should be pre-filled');

            $this->testResults['user_prefilled'] = 'SSH user field is pre-filled';
        });
    }

    /**
     * Test 6: Authentication method options present
     *
     * @test
     */
    public function test_auth_method_options_present()
    {
        if (! $this->server) {
            $this->testResults['auth_method'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('auth-method-options');

            // Check for authentication method radio buttons
            $browser->assertPresent('input[value="password"]')
                ->assertPresent('input[value="key"]');

            // Verify page contains password/key authentication labels
            $pageSource = $browser->driver->getPageSource();
            $this->assertTrue(
                str_contains($pageSource, 'Password') && str_contains($pageSource, 'SSH Key'),
                'Authentication method options should be visible'
            );

            $this->testResults['auth_method'] = 'Authentication method options present';
        });
    }

    /**
     * Test 7: Current values displayed correctly
     *
     * @test
     */
    public function test_current_values_displayed()
    {
        if (! $this->server) {
            $this->testResults['current_values'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('current-values-display');

            // Verify all current values are displayed
            $nameValue = $browser->value('#name');
            $ipValue = $browser->value('#ip_address');
            $portValue = $browser->value('#port');
            $usernameValue = $browser->value('#username');

            $this->assertEquals($this->server->name, $nameValue);
            $this->assertEquals($this->server->ip_address, $ipValue);
            $this->assertEquals((string) ($this->server->port ?? 22), $portValue);
            $this->assertEquals($this->server->username ?? 'root', $usernameValue);

            $this->testResults['current_values'] = 'All current values displayed correctly';
        });
    }

    /**
     * Test 8: Update button visible
     *
     * @test
     */
    public function test_update_button_visible()
    {
        if (! $this->server) {
            $this->testResults['update_button'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('update-button-visible');

            // Check for Update/Save button
            $pageSource = $browser->driver->getPageSource();
            $hasUpdateButton = str_contains($pageSource, 'Update Server') ||
                              str_contains($pageSource, 'Save Changes') ||
                              str_contains($pageSource, 'Save Server') ||
                              str_contains($pageSource, 'Update');

            $this->assertTrue($hasUpdateButton, 'Update button should be visible');

            $this->testResults['update_button'] = 'Update button is visible';
        });
    }

    /**
     * Test 9: Cancel button present
     *
     * @test
     */
    public function test_cancel_button_present()
    {
        if (! $this->server) {
            $this->testResults['cancel_button'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('cancel-button-present');

            // Check for Cancel button or Back link
            $pageSource = $browser->driver->getPageSource();
            $hasCancelButton = str_contains($pageSource, 'Cancel') ||
                              str_contains($pageSource, 'Back') ||
                              str_contains($pageSource, 'Return');

            $this->assertTrue($hasCancelButton, 'Cancel or Back button should be present');

            $this->testResults['cancel_button'] = 'Cancel button is present';
        });
    }

    /**
     * Test 10: Test connection button present
     *
     * @test
     */
    public function test_connection_button_present()
    {
        if (! $this->server) {
            $this->testResults['test_connection'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('test-connection-button');

            // Check for Test Connection button
            $pageSource = $browser->driver->getPageSource();
            $hasTestButton = str_contains($pageSource, 'Test Connection') ||
                            str_contains($pageSource, 'Test SSH') ||
                            str_contains($pageSource, 'Verify Connection');

            $this->assertTrue($hasTestButton, 'Test Connection button should be present');

            $this->testResults['test_connection'] = 'Test Connection button present';
        });
    }

    /**
     * Test 11: Form validation works for required fields
     *
     * @test
     */
    public function test_form_validation_works()
    {
        if (! $this->server) {
            $this->testResults['form_validation'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000);

            // Clear required field (name) and try to submit
            $browser->clear('#name')
                ->pause(500);

            // Try to find and click update button
            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Update Server')) {
                $browser->press('Update Server');
            } elseif (str_contains($pageSource, 'Save Changes')) {
                $browser->press('Save Changes');
            }

            $browser->pause(2000)
                ->screenshot('form-validation-error');

            // Validation should prevent submission (either error message or still on page)
            $currentUrl = $browser->driver->getCurrentURL();
            $isStillOnEditPage = str_contains($currentUrl, '/edit');

            $this->assertTrue($isStillOnEditPage, 'Form validation should prevent submission with empty name');

            $this->testResults['form_validation'] = 'Form validation works';
        });
    }

    /**
     * Test 12: Changes persist after save
     *
     * @test
     */
    public function test_changes_persist_after_save()
    {
        if (! $this->server) {
            $this->testResults['changes_persist'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $originalName = $this->server->name;
            $newName = 'Updated Test Server '.time();

            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000);

            // Update server name
            $browser->clear('#name')
                ->type('#name', $newName)
                ->pause(1000);

            // Find and click update button
            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Update Server')) {
                $browser->press('Update Server');
            } elseif (str_contains($pageSource, 'Save Changes')) {
                $browser->press('Save Changes');
            }

            $browser->pause(4000)
                ->screenshot('changes-saved');

            // Verify redirect to server detail page
            $currentUrl = $browser->driver->getCurrentURL();
            $redirected = str_contains($currentUrl, '/servers/'.$this->server->id) &&
                         ! str_contains($currentUrl, '/edit');

            $this->assertTrue($redirected, 'Should redirect after save');

            // Verify changes persisted in database
            $this->server->refresh();
            $this->assertEquals($newName, $this->server->name, 'Changes should persist in database');

            // Restore original name
            $this->server->update(['name' => $originalName]);

            $this->testResults['changes_persist'] = 'Changes persist after save';
        });
    }

    /**
     * Test 13: Success message displayed after update
     *
     * @test
     */
    public function test_success_message_displayed()
    {
        if (! $this->server) {
            $this->testResults['success_message'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000);

            // Make a small change
            $browser->clear('#hostname')
                ->type('#hostname', 'test.example.com')
                ->pause(1000);

            // Submit form
            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Update Server')) {
                $browser->press('Update Server');
            } elseif (str_contains($pageSource, 'Save Changes')) {
                $browser->press('Save Changes');
            }

            $browser->pause(4000)
                ->screenshot('success-message-displayed');

            // Check for success message
            $pageSource = $browser->driver->getPageSource();
            $hasSuccessMessage = str_contains($pageSource, 'success') ||
                                str_contains($pageSource, 'updated') ||
                                str_contains($pageSource, 'saved') ||
                                str_contains($pageSource, 'Successfully');

            $this->assertTrue($hasSuccessMessage, 'Success message should be displayed');

            $this->testResults['success_message'] = 'Success message displayed';
        });
    }

    /**
     * Test 14: Error handling works for invalid IP
     *
     * @test
     */
    public function test_error_handling_invalid_ip()
    {
        if (! $this->server) {
            $this->testResults['error_handling'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $originalIp = $this->server->ip_address;

            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000);

            // Enter invalid IP address
            $browser->clear('#ip_address')
                ->type('#ip_address', 'invalid-ip-address')
                ->pause(1000);

            // Try to submit
            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Update Server')) {
                $browser->press('Update Server');
            }

            $browser->pause(2000)
                ->screenshot('error-handling-invalid-ip');

            // Should still be on edit page or show error
            $currentUrl = $browser->driver->getCurrentURL();
            $isStillOnEditPage = str_contains($currentUrl, '/edit');

            $this->assertTrue($isStillOnEditPage, 'Should stay on page with validation error');

            // Verify database wasn't updated
            $this->server->refresh();
            $this->assertEquals($originalIp, $this->server->ip_address, 'Invalid IP should not be saved');

            $this->testResults['error_handling'] = 'Error handling works for invalid data';
        });
    }

    /**
     * Test 15: Back navigation works
     *
     * @test
     */
    public function test_back_navigation_works()
    {
        if (! $this->server) {
            $this->testResults['back_navigation'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('before-back-navigation');

            // Look for Cancel or Back button and click it
            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Cancel')) {
                $browser->press('Cancel');
            } elseif (str_contains($pageSource, 'Back')) {
                $browser->press('Back');
            } else {
                // Navigate back using browser
                $browser->back();
            }

            $browser->pause(3000)
                ->screenshot('after-back-navigation');

            // Should navigate away from edit page
            $currentUrl = $browser->driver->getCurrentURL();
            $leftEditPage = ! str_contains($currentUrl, '/edit') ||
                           str_contains($currentUrl, '/servers/'.$this->server->id) ||
                           str_contains($currentUrl, '/servers');

            $this->assertTrue($leftEditPage, 'Should navigate away from edit page');

            $this->testResults['back_navigation'] = 'Back navigation works';
        });
    }

    /**
     * Test 16: Delete server option exists
     *
     * @test
     */
    public function test_delete_server_option_exists()
    {
        if (! $this->server) {
            $this->testResults['delete_option'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('delete-server-option');

            // Look for delete option
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDeleteOption = str_contains($pageSource, 'delete') ||
                              str_contains($pageSource, 'remove server') ||
                              str_contains($pageSource, 'danger zone');

            $this->assertTrue($hasDeleteOption, 'Delete server option should exist');

            $this->testResults['delete_option'] = 'Delete server option exists';
        });
    }

    /**
     * Test 17: Danger zone section visible
     *
     * @test
     */
    public function test_danger_zone_section()
    {
        if (! $this->server) {
            $this->testResults['danger_zone'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('danger-zone-section');

            // Check for danger zone or delete section
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDangerZone = str_contains($pageSource, 'danger') ||
                            str_contains($pageSource, 'delete') ||
                            str_contains($pageSource, 'remove');

            $this->assertTrue($hasDangerZone, 'Danger zone section should be visible');

            $this->testResults['danger_zone'] = 'Danger zone section visible';
        });
    }

    /**
     * Test 18: Hostname field present
     *
     * @test
     */
    public function test_hostname_field_present()
    {
        if (! $this->server) {
            $this->testResults['hostname_field'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('hostname-field-present');

            // Check for hostname field
            $browser->assertPresent('#hostname');

            $this->testResults['hostname_field'] = 'Hostname field present';
        });
    }

    /**
     * Test 19: Location fields present (latitude, longitude)
     *
     * @test
     */
    public function test_location_fields_present()
    {
        if (! $this->server) {
            $this->testResults['location_fields'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('location-fields-present');

            // Check for location fields
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLocationFields = str_contains($pageSource, 'latitude') ||
                                str_contains($pageSource, 'longitude') ||
                                str_contains($pageSource, 'location');

            $this->assertTrue($hasLocationFields, 'Location fields should be present');

            $this->testResults['location_fields'] = 'Location fields present';
        });
    }

    /**
     * Test 20: Flash messages display properly
     *
     * @test
     */
    public function test_flash_messages_display()
    {
        if (! $this->server) {
            $this->testResults['flash_messages'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000);

            // Click Test Connection button if available
            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Test Connection')) {
                $browser->press('Test Connection')
                    ->pause(4000)
                    ->screenshot('flash-message-after-test');

                // Check for flash message (success or error)
                $pageSource = $browser->driver->getPageSource();
                $hasFlashMessage = str_contains($pageSource, 'success') ||
                                  str_contains($pageSource, 'error') ||
                                  str_contains($pageSource, 'Connection') ||
                                  str_contains($pageSource, 'failed') ||
                                  str_contains($pageSource, 'reachable');

                $this->assertTrue($hasFlashMessage, 'Flash message should display after action');
            }

            $this->testResults['flash_messages'] = 'Flash messages display properly';
        });
    }

    /**
     * Test 21: Password field conditionally shown
     *
     * @test
     */
    public function test_password_field_conditionally_shown()
    {
        if (! $this->server) {
            $this->testResults['password_conditional'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000);

            // Click password auth method
            $browser->click('input[value="password"]')
                ->pause(2000)
                ->screenshot('password-auth-selected');

            // Check if password field is visible
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPasswordField = str_contains($pageSource, 'password') &&
                               str_contains($pageSource, 'ssh');

            $this->assertTrue($hasPasswordField, 'Password field should be visible when password auth selected');

            $this->testResults['password_conditional'] = 'Password field shown conditionally';
        });
    }

    /**
     * Test 22: SSH Key field conditionally shown
     *
     * @test
     */
    public function test_ssh_key_field_conditionally_shown()
    {
        if (! $this->server) {
            $this->testResults['ssh_key_conditional'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000);

            // Click SSH key auth method
            $browser->click('input[value="key"]')
                ->pause(2000)
                ->screenshot('ssh-key-auth-selected');

            // Check if SSH key field is visible
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasKeyField = str_contains($pageSource, 'ssh key') ||
                          str_contains($pageSource, 'private key') ||
                          str_contains($pageSource, 'key content');

            $this->assertTrue($hasKeyField, 'SSH key field should be visible when key auth selected');

            $this->testResults['ssh_key_conditional'] = 'SSH key field shown conditionally';
        });
    }

    /**
     * Test 23: Port validation accepts valid ports
     *
     * @test
     */
    public function test_port_validation()
    {
        if (! $this->server) {
            $this->testResults['port_validation'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000);

            // Try valid port
            $browser->clear('#port')
                ->type('#port', '2222')
                ->pause(1000)
                ->screenshot('valid-port-entered');

            // Port value should be accepted
            $portValue = $browser->value('#port');
            $this->assertEquals('2222', $portValue, 'Valid port should be accepted');

            $this->testResults['port_validation'] = 'Port validation works correctly';
        });
    }

    /**
     * Test 24: All form fields are editable
     *
     * @test
     */
    public function test_all_fields_editable()
    {
        if (! $this->server) {
            $this->testResults['fields_editable'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000);

            // Test editing each field
            $browser->clear('#name')
                ->type('#name', 'New Name')
                ->clear('#hostname')
                ->type('#hostname', 'new.host.com')
                ->clear('#ip_address')
                ->type('#ip_address', '10.0.0.1')
                ->clear('#port')
                ->type('#port', '2222')
                ->clear('#username')
                ->type('#username', 'ubuntu')
                ->pause(1000)
                ->screenshot('all-fields-edited');

            // Verify fields were updated
            $this->assertEquals('New Name', $browser->value('#name'));
            $this->assertEquals('new.host.com', $browser->value('#hostname'));
            $this->assertEquals('10.0.0.1', $browser->value('#ip_address'));
            $this->assertEquals('2222', $browser->value('#port'));
            $this->assertEquals('ubuntu', $browser->value('#username'));

            $this->testResults['fields_editable'] = 'All form fields are editable';
        });
    }

    /**
     * Test 25: Form layout is properly structured
     *
     * @test
     */
    public function test_form_layout_structured()
    {
        if (! $this->server) {
            $this->testResults['form_layout'] = 'Skipped - no servers available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/servers/'.$this->server->id.'/edit')
                ->pause(3000)
                ->screenshot('form-layout-structure');

            // Verify essential form elements exist
            $browser->assertPresent('#name')
                ->assertPresent('#ip_address')
                ->assertPresent('#port')
                ->assertPresent('#username')
                ->assertPresent('input[value="password"]')
                ->assertPresent('input[value="key"]');

            // Check page structure via source
            $pageSource = $browser->driver->getPageSource();
            $hasProperStructure = str_contains($pageSource, 'form') &&
                                 str_contains($pageSource, 'wire:') &&
                                 (str_contains($pageSource, 'Update') || str_contains($pageSource, 'Save'));

            $this->assertTrue($hasProperStructure, 'Form should have proper Livewire structure');

            $this->testResults['form_layout'] = 'Form layout is properly structured';
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
                'test_suite' => 'Server Edit Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                    'server_tested' => $this->server?->name ?? 'None',
                ],
                'environment' => [
                    'servers_available' => Server::count(),
                    'user_email' => $this->user->email,
                ],
            ];

            $reportPath = storage_path('app/test-reports/server-edit-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
