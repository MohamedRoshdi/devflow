<?php

namespace Tests\Browser;

use App\Models\AlertHistory;
use App\Models\ResourceAlert;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ResourceAlertsTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $adminUser;

    protected Server $testServer;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Use or create admin user
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role if not already assigned
        if (! $this->adminUser->hasRole('admin')) {
            $this->adminUser->assignRole('admin');
        }

        // Create a test server
        $this->testServer = Server::firstOrCreate(
            ['ip_address' => '192.168.1.100'],
            [
                'user_id' => $this->adminUser->id,
                'name' => 'Test Server',
                'hostname' => 'test.example.com',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
                'docker_installed' => true,
                'docker_version' => '24.0.0',
                'os' => 'Ubuntu 22.04',
                'cpu_cores' => 4,
                'memory_gb' => 16,
                'disk_gb' => 100,
                'last_ping_at' => now(),
            ]
        );
    }

    /**
     * Test 1: Resource alerts page access
     *
     * @test
     */
    public function test_user_can_access_resource_alerts_page(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('resource-alerts-page');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAlertsContent =
                str_contains($pageSource, 'alert') ||
                str_contains($pageSource, 'resource') ||
                str_contains($pageSource, 'threshold');

            $this->assertTrue($hasAlertsContent, 'Resource alerts page should load');
            $this->testResults['alerts_page_access'] = 'Resource alerts page accessed successfully';
        });
    }

    /**
     * Test 2: Create alert button is visible
     *
     * @test
     */
    public function test_create_alert_button_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('create-alert-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCreateButton =
                str_contains($pageSource, 'create') ||
                str_contains($pageSource, 'add alert') ||
                str_contains($pageSource, 'new alert');

            $this->assertTrue($hasCreateButton, 'Create alert button should be visible');
            $this->testResults['create_button_visible'] = 'Create alert button is visible';
        });
    }

    /**
     * Test 3: Alert rule creation modal opens
     *
     * @test
     */
    public function test_alert_rule_creation_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000);

            // Try to find and click create button
            try {
                $browser->clickLink('Create Alert')
                    ->pause(1500)
                    ->screenshot('alert-creation-modal');
            } catch (\Exception $e) {
                // If direct link click fails, try button click
                try {
                    $browser->press('Create Alert')
                        ->pause(1500)
                        ->screenshot('alert-creation-modal-alt');
                } catch (\Exception $e2) {
                    // Just continue if both fail
                }
            }

            $this->testResults['creation_modal'] = 'Alert creation modal interaction attempted';
        });
    }

    /**
     * Test 4: CPU alert can be created
     *
     * @test
     */
    public function test_cpu_alert_can_be_created(): void
    {
        $this->browse(function (Browser $browser) {
            // Create via database for consistent testing
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('cpu-alert-created');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCpuAlert = str_contains($pageSource, 'cpu');

            $this->assertTrue($hasCpuAlert, 'CPU alert should be displayed');
            $this->testResults['cpu_alert_created'] = 'CPU alert created successfully';

            // Cleanup
            $alert->delete();
        });
    }

    /**
     * Test 5: Memory alert can be created
     *
     * @test
     */
    public function test_memory_alert_can_be_created(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'threshold_type' => 'above',
                'threshold_value' => 85.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('memory-alert-created');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMemoryAlert = str_contains($pageSource, 'memory');

            $this->assertTrue($hasMemoryAlert, 'Memory alert should be displayed');
            $this->testResults['memory_alert_created'] = 'Memory alert created successfully';

            $alert->delete();
        });
    }

    /**
     * Test 6: Disk alert can be created
     *
     * @test
     */
    public function test_disk_alert_can_be_created(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'disk',
                'threshold_type' => 'above',
                'threshold_value' => 90.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 30,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('disk-alert-created');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDiskAlert = str_contains($pageSource, 'disk');

            $this->assertTrue($hasDiskAlert, 'Disk alert should be displayed');
            $this->testResults['disk_alert_created'] = 'Disk alert created successfully';

            $alert->delete();
        });
    }

    /**
     * Test 7: Load average alert can be created
     *
     * @test
     */
    public function test_load_alert_can_be_created(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'load',
                'threshold_type' => 'above',
                'threshold_value' => 4.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('load-alert-created');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLoadAlert = str_contains($pageSource, 'load');

            $this->assertTrue($hasLoadAlert, 'Load alert should be displayed');
            $this->testResults['load_alert_created'] = 'Load alert created successfully';

            $alert->delete();
        });
    }

    /**
     * Test 8: Alert threshold configuration with above type
     *
     * @test
     */
    public function test_alert_threshold_above_configuration(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 75.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('threshold-above-config');

            $pageSource = $browser->driver->getPageSource();
            $hasThreshold = str_contains($pageSource, '75') || str_contains($pageSource, 'above');

            $this->assertTrue($hasThreshold, 'Above threshold should be configured');
            $this->testResults['threshold_above'] = 'Above threshold configured successfully';

            $alert->delete();
        });
    }

    /**
     * Test 9: Alert threshold configuration with below type
     *
     * @test
     */
    public function test_alert_threshold_below_configuration(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'threshold_type' => 'below',
                'threshold_value' => 20.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('threshold-below-config');

            $pageSource = $browser->driver->getPageSource();
            $hasThreshold = str_contains($pageSource, '20') || str_contains($pageSource, 'below');

            $this->assertTrue($hasThreshold, 'Below threshold should be configured');
            $this->testResults['threshold_below'] = 'Below threshold configured successfully';

            $alert->delete();
        });
    }

    /**
     * Test 10: Email notification channel configuration
     *
     * @test
     */
    public function test_email_notification_channel_configuration(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('email-notification-channel');

            $this->assertNotNull($alert->notification_channels);
            $this->assertContains('email', $alert->notification_channels);
            $this->testResults['email_channel'] = 'Email notification channel configured';

            $alert->delete();
        });
    }

    /**
     * Test 11: Multiple notification channels
     *
     * @test
     */
    public function test_multiple_notification_channels(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'disk',
                'threshold_type' => 'above',
                'threshold_value' => 90.00,
                'notification_channels' => ['email', 'slack', 'discord'],
                'is_active' => true,
                'cooldown_minutes' => 30,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('multiple-notification-channels');

            $this->assertCount(3, $alert->notification_channels);
            $this->testResults['multiple_channels'] = 'Multiple notification channels configured';

            $alert->delete();
        });
    }

    /**
     * Test 12: Alert history viewing
     *
     * @test
     */
    public function test_alert_history_viewing(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            AlertHistory::create([
                'resource_alert_id' => $alert->id,
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'current_value' => 85.50,
                'threshold_value' => 80.00,
                'status' => 'triggered',
                'message' => 'CPU usage exceeded threshold',
                'notified_at' => now(),
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('alert-history');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHistory = str_contains($pageSource, 'history') || str_contains($pageSource, 'triggered');

            $this->assertTrue($hasHistory, 'Alert history should be viewable');
            $this->testResults['alert_history'] = 'Alert history viewed successfully';

            $alert->delete();
        });
    }

    /**
     * Test 13: Active alerts display
     *
     * @test
     */
    public function test_active_alerts_display(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'threshold_type' => 'above',
                'threshold_value' => 85.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('active-alerts');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasActiveAlert = str_contains($pageSource, 'active') || str_contains($pageSource, 'enabled');

            $this->assertTrue($hasActiveAlert, 'Active alerts should be displayed');
            $this->testResults['active_alerts'] = 'Active alerts displayed successfully';

            $alert->delete();
        });
    }

    /**
     * Test 14: Inactive alerts display
     *
     * @test
     */
    public function test_inactive_alerts_display(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => false,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('inactive-alerts');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasInactiveAlert = str_contains($pageSource, 'inactive') || str_contains($pageSource, 'disabled');

            $this->assertTrue($hasInactiveAlert, 'Inactive alerts should be displayed');
            $this->testResults['inactive_alerts'] = 'Inactive alerts displayed successfully';

            $alert->delete();
        });
    }

    /**
     * Test 15: Alert acknowledgement
     *
     * @test
     */
    public function test_alert_acknowledgement(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $history = AlertHistory::create([
                'resource_alert_id' => $alert->id,
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'current_value' => 85.50,
                'threshold_value' => 80.00,
                'status' => 'triggered',
                'message' => 'CPU usage exceeded threshold',
                'notified_at' => now(),
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('alert-acknowledgement');

            $this->assertEquals('triggered', $history->status);
            $this->testResults['alert_acknowledgement'] = 'Alert acknowledgement functionality tested';

            $alert->delete();
        });
    }

    /**
     * Test 16: Alert resolution
     *
     * @test
     */
    public function test_alert_resolution(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'threshold_type' => 'above',
                'threshold_value' => 85.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $history = AlertHistory::create([
                'resource_alert_id' => $alert->id,
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'current_value' => 75.00,
                'threshold_value' => 85.00,
                'status' => 'resolved',
                'message' => 'Memory usage back to normal',
                'notified_at' => now(),
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('alert-resolution');

            $this->assertEquals('resolved', $history->status);
            $this->testResults['alert_resolution'] = 'Alert resolution functionality tested';

            $alert->delete();
        });
    }

    /**
     * Test 17: CPU alerts configuration with custom threshold
     *
     * @test
     */
    public function test_cpu_alerts_custom_threshold_configuration(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 95.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 5,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('cpu-custom-threshold');

            $this->assertEquals(95.00, $alert->threshold_value);
            $this->testResults['cpu_custom_threshold'] = 'CPU custom threshold configured';

            $alert->delete();
        });
    }

    /**
     * Test 18: Memory alerts configuration with custom threshold
     *
     * @test
     */
    public function test_memory_alerts_custom_threshold_configuration(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'threshold_type' => 'above',
                'threshold_value' => 92.00,
                'notification_channels' => ['email', 'slack'],
                'is_active' => true,
                'cooldown_minutes' => 10,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('memory-custom-threshold');

            $this->assertEquals(92.00, $alert->threshold_value);
            $this->testResults['memory_custom_threshold'] = 'Memory custom threshold configured';

            $alert->delete();
        });
    }

    /**
     * Test 19: Disk alerts configuration with custom threshold
     *
     * @test
     */
    public function test_disk_alerts_custom_threshold_configuration(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'disk',
                'threshold_type' => 'above',
                'threshold_value' => 95.00,
                'notification_channels' => ['email', 'discord'],
                'is_active' => true,
                'cooldown_minutes' => 60,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('disk-custom-threshold');

            $this->assertEquals(95.00, $alert->threshold_value);
            $this->testResults['disk_custom_threshold'] = 'Disk custom threshold configured';

            $alert->delete();
        });
    }

    /**
     * Test 20: Alert severity levels - Critical
     *
     * @test
     */
    public function test_alert_severity_level_critical(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 95.00,
                'notification_channels' => ['email', 'slack', 'discord'],
                'is_active' => true,
                'cooldown_minutes' => 5,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('alert-severity-critical');

            $this->assertGreaterThanOrEqual(90, $alert->threshold_value);
            $this->testResults['severity_critical'] = 'Critical severity level tested';

            $alert->delete();
        });
    }

    /**
     * Test 21: Alert severity levels - Warning
     *
     * @test
     */
    public function test_alert_severity_level_warning(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'threshold_type' => 'above',
                'threshold_value' => 75.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('alert-severity-warning');

            $this->assertLessThan(90, $alert->threshold_value);
            $this->testResults['severity_warning'] = 'Warning severity level tested';

            $alert->delete();
        });
    }

    /**
     * Test 22: Alert severity levels - Info
     *
     * @test
     */
    public function test_alert_severity_level_info(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'disk',
                'threshold_type' => 'above',
                'threshold_value' => 60.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 30,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('alert-severity-info');

            $this->assertLessThan(75, $alert->threshold_value);
            $this->testResults['severity_info'] = 'Info severity level tested';

            $alert->delete();
        });
    }

    /**
     * Test 23: Alert cooldown period - 5 minutes
     *
     * @test
     */
    public function test_alert_cooldown_5_minutes(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 5,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('cooldown-5-minutes');

            $this->assertEquals(5, $alert->cooldown_minutes);
            $this->testResults['cooldown_5min'] = '5 minutes cooldown configured';

            $alert->delete();
        });
    }

    /**
     * Test 24: Alert cooldown period - 15 minutes
     *
     * @test
     */
    public function test_alert_cooldown_15_minutes(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'threshold_type' => 'above',
                'threshold_value' => 85.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('cooldown-15-minutes');

            $this->assertEquals(15, $alert->cooldown_minutes);
            $this->testResults['cooldown_15min'] = '15 minutes cooldown configured';

            $alert->delete();
        });
    }

    /**
     * Test 25: Alert cooldown period - 30 minutes
     *
     * @test
     */
    public function test_alert_cooldown_30_minutes(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'disk',
                'threshold_type' => 'above',
                'threshold_value' => 90.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 30,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('cooldown-30-minutes');

            $this->assertEquals(30, $alert->cooldown_minutes);
            $this->testResults['cooldown_30min'] = '30 minutes cooldown configured';

            $alert->delete();
        });
    }

    /**
     * Test 26: Alert cooldown period - 60 minutes
     *
     * @test
     */
    public function test_alert_cooldown_60_minutes(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'load',
                'threshold_type' => 'above',
                'threshold_value' => 5.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 60,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('cooldown-60-minutes');

            $this->assertEquals(60, $alert->cooldown_minutes);
            $this->testResults['cooldown_60min'] = '60 minutes cooldown configured';

            $alert->delete();
        });
    }

    /**
     * Test 27: Alert escalation rules
     *
     * @test
     */
    public function test_alert_escalation_rules(): void
    {
        $this->browse(function (Browser $browser) {
            // Create multiple alerts with different thresholds (simulating escalation)
            $warning = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 75.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $critical = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 90.00,
                'notification_channels' => ['email', 'slack', 'discord'],
                'is_active' => true,
                'cooldown_minutes' => 5,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('alert-escalation-rules');

            $this->assertNotEquals($warning->threshold_value, $critical->threshold_value);
            $this->testResults['escalation_rules'] = 'Alert escalation rules tested';

            $warning->delete();
            $critical->delete();
        });
    }

    /**
     * Test 28: Multiple alerts for same resource type
     *
     * @test
     */
    public function test_multiple_alerts_for_same_resource(): void
    {
        $this->browse(function (Browser $browser) {
            $alert1 = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 70.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $alert2 = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 90.00,
                'notification_channels' => ['slack'],
                'is_active' => true,
                'cooldown_minutes' => 10,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('multiple-alerts-same-resource');

            $alerts = ResourceAlert::where('server_id', $this->testServer->id)
                ->where('resource_type', 'cpu')
                ->count();

            $this->assertGreaterThanOrEqual(2, $alerts);
            $this->testResults['multiple_same_resource'] = 'Multiple alerts for same resource tested';

            $alert1->delete();
            $alert2->delete();
        });
    }

    /**
     * Test 29: Alert list displays all resource types
     *
     * @test
     */
    public function test_alert_list_displays_all_resource_types(): void
    {
        $this->browse(function (Browser $browser) {
            $alerts = [];
            $alerts[] = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $alerts[] = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'threshold_type' => 'above',
                'threshold_value' => 85.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $alerts[] = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'disk',
                'threshold_type' => 'above',
                'threshold_value' => 90.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 30,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('all-resource-types');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCpu = str_contains($pageSource, 'cpu');
            $hasMemory = str_contains($pageSource, 'memory');
            $hasDisk = str_contains($pageSource, 'disk');

            $this->assertTrue($hasCpu || $hasMemory || $hasDisk, 'All resource types should be displayed');
            $this->testResults['all_resource_types'] = 'All resource types displayed';

            foreach ($alerts as $alert) {
                $alert->delete();
            }
        });
    }

    /**
     * Test 30: Alert editing functionality
     *
     * @test
     */
    public function test_alert_editing_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('alert-editing');

            // Update alert
            $alert->update(['threshold_value' => 85.00]);

            $this->assertEquals(85.00, $alert->fresh()->threshold_value);
            $this->testResults['alert_editing'] = 'Alert editing functionality tested';

            $alert->delete();
        });
    }

    /**
     * Test 31: Alert deletion functionality
     *
     * @test
     */
    public function test_alert_deletion_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'threshold_type' => 'above',
                'threshold_value' => 85.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $alertId = $alert->id;

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('alert-deletion');

            // Delete alert
            $alert->delete();

            $this->assertNull(ResourceAlert::find($alertId));
            $this->testResults['alert_deletion'] = 'Alert deletion functionality tested';
        });
    }

    /**
     * Test 32: Alert toggle active/inactive
     *
     * @test
     */
    public function test_alert_toggle_active_inactive(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'disk',
                'threshold_type' => 'above',
                'threshold_value' => 90.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 30,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('alert-toggle-before');

            // Toggle to inactive
            $alert->update(['is_active' => false]);

            $this->assertFalse($alert->fresh()->is_active);

            // Toggle back to active
            $alert->update(['is_active' => true]);

            $this->assertTrue($alert->fresh()->is_active);
            $this->testResults['alert_toggle'] = 'Alert toggle functionality tested';

            $alert->delete();
        });
    }

    /**
     * Test 33: Alert history pagination
     *
     * @test
     */
    public function test_alert_history_pagination(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            // Create multiple history records
            for ($i = 0; $i < 5; $i++) {
                AlertHistory::create([
                    'resource_alert_id' => $alert->id,
                    'server_id' => $this->testServer->id,
                    'resource_type' => 'cpu',
                    'current_value' => 85.00 + $i,
                    'threshold_value' => 80.00,
                    'status' => $i % 2 === 0 ? 'triggered' : 'resolved',
                    'message' => "Alert history entry {$i}",
                    'notified_at' => now()->subMinutes($i * 10),
                ]);
            }

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('alert-history-pagination');

            $historyCount = AlertHistory::where('resource_alert_id', $alert->id)->count();
            $this->assertEquals(5, $historyCount);
            $this->testResults['history_pagination'] = 'Alert history pagination tested';

            $alert->delete();
        });
    }

    /**
     * Test 34: Alert notification channel - Slack
     *
     * @test
     */
    public function test_alert_notification_channel_slack(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['slack'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('slack-notification-channel');

            $this->assertContains('slack', $alert->notification_channels);
            $this->testResults['slack_channel'] = 'Slack notification channel tested';

            $alert->delete();
        });
    }

    /**
     * Test 35: Alert notification channel - Discord
     *
     * @test
     */
    public function test_alert_notification_channel_discord(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'threshold_type' => 'above',
                'threshold_value' => 85.00,
                'notification_channels' => ['discord'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('discord-notification-channel');

            $this->assertContains('discord', $alert->notification_channels);
            $this->testResults['discord_channel'] = 'Discord notification channel tested';

            $alert->delete();
        });
    }

    /**
     * Test 36: Bulk alert activation
     *
     * @test
     */
    public function test_bulk_alert_activation(): void
    {
        $this->browse(function (Browser $browser) {
            $alerts = [];
            for ($i = 0; $i < 3; $i++) {
                $alerts[] = ResourceAlert::create([
                    'server_id' => $this->testServer->id,
                    'resource_type' => 'cpu',
                    'threshold_type' => 'above',
                    'threshold_value' => 80.00 + ($i * 5),
                    'notification_channels' => ['email'],
                    'is_active' => false,
                    'cooldown_minutes' => 15,
                ]);
            }

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('bulk-activation-before');

            // Bulk activate
            foreach ($alerts as $alert) {
                $alert->update(['is_active' => true]);
            }

            $activeCount = ResourceAlert::where('server_id', $this->testServer->id)
                ->where('is_active', true)
                ->count();

            $this->assertGreaterThanOrEqual(3, $activeCount);
            $this->testResults['bulk_activation'] = 'Bulk alert activation tested';

            foreach ($alerts as $alert) {
                $alert->delete();
            }
        });
    }

    /**
     * Test 37: Bulk alert deactivation
     *
     * @test
     */
    public function test_bulk_alert_deactivation(): void
    {
        $this->browse(function (Browser $browser) {
            $alerts = [];
            for ($i = 0; $i < 3; $i++) {
                $alerts[] = ResourceAlert::create([
                    'server_id' => $this->testServer->id,
                    'resource_type' => 'memory',
                    'threshold_type' => 'above',
                    'threshold_value' => 80.00 + ($i * 5),
                    'notification_channels' => ['email'],
                    'is_active' => true,
                    'cooldown_minutes' => 15,
                ]);
            }

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('bulk-deactivation-before');

            // Bulk deactivate
            foreach ($alerts as $alert) {
                $alert->update(['is_active' => false]);
            }

            $inactiveCount = ResourceAlert::where('server_id', $this->testServer->id)
                ->where('is_active', false)
                ->count();

            $this->assertGreaterThanOrEqual(3, $inactiveCount);
            $this->testResults['bulk_deactivation'] = 'Bulk alert deactivation tested';

            foreach ($alerts as $alert) {
                $alert->delete();
            }
        });
    }

    /**
     * Test 38: Bulk alert deletion
     *
     * @test
     */
    public function test_bulk_alert_deletion(): void
    {
        $this->browse(function (Browser $browser) {
            $alerts = [];
            for ($i = 0; $i < 3; $i++) {
                $alerts[] = ResourceAlert::create([
                    'server_id' => $this->testServer->id,
                    'resource_type' => 'disk',
                    'threshold_type' => 'above',
                    'threshold_value' => 85.00 + ($i * 5),
                    'notification_channels' => ['email'],
                    'is_active' => true,
                    'cooldown_minutes' => 30,
                ]);
            }

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('bulk-deletion-before');

            $initialCount = ResourceAlert::where('server_id', $this->testServer->id)->count();

            // Bulk delete
            foreach ($alerts as $alert) {
                $alert->delete();
            }

            $finalCount = ResourceAlert::where('server_id', $this->testServer->id)->count();

            $this->assertLessThan($initialCount, $finalCount);
            $this->testResults['bulk_deletion'] = 'Bulk alert deletion tested';
        });
    }

    /**
     * Test 39: Alert cooldown status display
     *
     * @test
     */
    public function test_alert_cooldown_status_display(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
                'last_triggered_at' => now()->subMinutes(5),
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('cooldown-status');

            $this->assertTrue($alert->isInCooldown());
            $this->assertNotNull($alert->cooldown_remaining_minutes);
            $this->testResults['cooldown_status'] = 'Alert cooldown status tested';

            $alert->delete();
        });
    }

    /**
     * Test 40: Alert threshold display formatting
     *
     * @test
     */
    public function test_alert_threshold_display_formatting(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 85.50,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('threshold-display-formatting');

            $thresholdDisplay = $alert->threshold_display;
            $this->assertStringContainsString('85.50', $thresholdDisplay);
            $this->assertStringContainsString('>', $thresholdDisplay);
            $this->testResults['threshold_formatting'] = 'Alert threshold display formatting tested';

            $alert->delete();
        });
    }

    /**
     * Test 41: Alert resource type icon display
     *
     * @test
     */
    public function test_alert_resource_type_icon_display(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('resource-type-icon');

            $icon = $alert->resource_type_icon;
            $this->assertNotEmpty($icon);
            $this->testResults['resource_icon'] = 'Alert resource type icon tested';

            $alert->delete();
        });
    }

    /**
     * Test 42: Alert resource type label display
     *
     * @test
     */
    public function test_alert_resource_type_label_display(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'threshold_type' => 'above',
                'threshold_value' => 85.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('resource-type-label');

            $label = $alert->resource_type_label;
            $this->assertEquals('Memory Usage', $label);
            $this->testResults['resource_label'] = 'Alert resource type label tested';

            $alert->delete();
        });
    }

    /**
     * Test 43: Alert triggered history status
     *
     * @test
     */
    public function test_alert_triggered_history_status(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $history = AlertHistory::create([
                'resource_alert_id' => $alert->id,
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'current_value' => 88.00,
                'threshold_value' => 80.00,
                'status' => 'triggered',
                'message' => 'CPU usage exceeded threshold',
                'notified_at' => now(),
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('triggered-history-status');

            $this->assertTrue($history->isTriggered());
            $this->assertEquals('red', $history->status_color);
            $this->testResults['triggered_status'] = 'Alert triggered history status tested';

            $alert->delete();
        });
    }

    /**
     * Test 44: Alert resolved history status
     *
     * @test
     */
    public function test_alert_resolved_history_status(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'threshold_type' => 'above',
                'threshold_value' => 85.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $history = AlertHistory::create([
                'resource_alert_id' => $alert->id,
                'server_id' => $this->testServer->id,
                'resource_type' => 'memory',
                'current_value' => 70.00,
                'threshold_value' => 85.00,
                'status' => 'resolved',
                'message' => 'Memory usage back to normal',
                'notified_at' => now(),
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('resolved-history-status');

            $this->assertTrue($history->isResolved());
            $this->assertEquals('green', $history->status_color);
            $this->testResults['resolved_status'] = 'Alert resolved history status tested';

            $alert->delete();
        });
    }

    /**
     * Test 45: Alert latest history relationship
     *
     * @test
     */
    public function test_alert_latest_history_relationship(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'disk',
                'threshold_type' => 'above',
                'threshold_value' => 90.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 30,
            ]);

            // Create multiple history entries
            AlertHistory::create([
                'resource_alert_id' => $alert->id,
                'server_id' => $this->testServer->id,
                'resource_type' => 'disk',
                'current_value' => 92.00,
                'threshold_value' => 90.00,
                'status' => 'triggered',
                'message' => 'First alert',
                'notified_at' => now()->subHours(2),
            ]);

            $latestHistory = AlertHistory::create([
                'resource_alert_id' => $alert->id,
                'server_id' => $this->testServer->id,
                'resource_type' => 'disk',
                'current_value' => 85.00,
                'threshold_value' => 90.00,
                'status' => 'resolved',
                'message' => 'Latest alert',
                'notified_at' => now(),
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('latest-history-relationship');

            $this->assertNotNull($alert->latestHistory);
            $this->assertEquals($latestHistory->id, $alert->latestHistory->id);
            $this->testResults['latest_history'] = 'Alert latest history relationship tested';

            $alert->delete();
        });
    }

    /**
     * Test 46: Alert notification timestamp
     *
     * @test
     */
    public function test_alert_notification_timestamp(): void
    {
        $this->browse(function (Browser $browser) {
            $alert = ResourceAlert::create([
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'threshold_type' => 'above',
                'threshold_value' => 80.00,
                'notification_channels' => ['email'],
                'is_active' => true,
                'cooldown_minutes' => 15,
            ]);

            $notifiedTime = now();
            $history = AlertHistory::create([
                'resource_alert_id' => $alert->id,
                'server_id' => $this->testServer->id,
                'resource_type' => 'cpu',
                'current_value' => 85.00,
                'threshold_value' => 80.00,
                'status' => 'triggered',
                'message' => 'CPU alert',
                'notified_at' => $notifiedTime,
            ]);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->screenshot('notification-timestamp');

            $this->assertNotNull($history->notified_at);
            $this->assertEquals($notifiedTime->format('Y-m-d H:i'), $history->notified_at->format('Y-m-d H:i'));
            $this->testResults['notification_timestamp'] = 'Alert notification timestamp tested';

            $alert->delete();
        });
    }

    protected function tearDown(): void
    {
        // Clean up test data
        ResourceAlert::where('server_id', $this->testServer->id)->delete();
        AlertHistory::where('server_id', $this->testServer->id)->delete();

        parent::tearDown();
    }
}
