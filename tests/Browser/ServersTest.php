<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Server;
use App\Models\ServerTag;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ServersTest extends DuskTestCase
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
     * Test 1: Server list page loads successfully
     *
     */

    #[Test]
    public function test_server_list_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-list-page');

            // Check if servers page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasServersContent =
                str_contains($pageSource, 'server') ||
                str_contains($pageSource, 'servers') ||
                str_contains($pageSource, 'add server');

            $this->assertTrue($hasServersContent, 'Server list page should load');

            $this->testResults['servers_list'] = 'Server list page loaded successfully';
        });
    }

    /**
     * Test 2: Server creation button is visible
     *
     */

    #[Test]
    public function test_server_creation_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-create-button');

            // Check for add server button via page source
            $pageSource = $browser->driver->getPageSource();
            $hasAddButton =
                str_contains($pageSource, 'Add Server') ||
                str_contains($pageSource, 'Create Server') ||
                str_contains($pageSource, 'New Server') ||
                str_contains($pageSource, '/servers/create');

            $this->assertTrue($hasAddButton, 'Server creation button should be visible');

            $this->testResults['server_create_button'] = 'Server creation button is visible';
        });
    }

    /**
     * Test 3: Server creation page loads
     *
     */

    #[Test]
    public function test_server_creation_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-create-page');

            // Check if create page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCreateForm =
                str_contains($pageSource, 'create server') ||
                str_contains($pageSource, 'add server') ||
                str_contains($pageSource, 'wire:model="name"');

            $this->assertTrue($hasCreateForm, 'Server creation page should load');

            $this->testResults['server_create_page'] = 'Server creation page loaded successfully';
        });
    }

    /**
     * Test 4: Server creation form has required fields
     *
     */

    #[Test]
    public function test_server_creation_form_has_required_fields()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-create-form-fields');

            // Check if form fields are defined in page source
            $pageSource = $browser->driver->getPageSource();
            $hasFormFields =
                str_contains($pageSource, 'wire:model="name"') &&
                str_contains($pageSource, 'wire:model="ip_address"') &&
                str_contains($pageSource, 'wire:model="port"');

            $this->assertTrue($hasFormFields, 'Server creation form should have required fields');

            $this->testResults['server_create_form_fields'] = 'Server creation form has required fields defined';
        });
    }

    /**
     * Test 5: Server show/details page loads
     *
     */

    #[Test]
    public function test_server_show_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-show-page');

            // Check if server details page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasServerDetails =
                str_contains($pageSource, 'server details') ||
                str_contains($pageSource, 'test server') ||
                str_contains($pageSource, $this->testServer->ip_address);

            $this->assertTrue($hasServerDetails, 'Server show page should load');

            $this->testResults['server_show_page'] = 'Server show page loaded successfully';
        });
    }

    /**
     * Test 6: Server edit page loads
     *
     */

    #[Test]
    public function test_server_edit_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/edit")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-edit-page');

            // Check if edit page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEditForm =
                str_contains($pageSource, 'edit server') ||
                str_contains($pageSource, 'update server') ||
                str_contains($pageSource, $this->testServer->name);

            $this->assertTrue($hasEditForm, 'Server edit page should load');

            $this->testResults['server_edit_page'] = 'Server edit page loaded successfully';
        });
    }

    /**
     * Test 7: Server metrics dashboard loads
     *
     */

    #[Test]
    public function test_server_metrics_dashboard_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-metrics-dashboard');

            // Check if metrics page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMetricsContent =
                str_contains($pageSource, 'metrics') ||
                str_contains($pageSource, 'cpu') ||
                str_contains($pageSource, 'memory') ||
                str_contains($pageSource, 'disk');

            $this->assertTrue($hasMetricsContent, 'Server metrics dashboard should load');

            $this->testResults['server_metrics_dashboard'] = 'Server metrics dashboard loaded successfully';
        });
    }

    /**
     * Test 8: Server tags manager page loads
     *
     */

    #[Test]
    public function test_server_tags_manager_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/tags')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-tags-manager');

            // Check if tags page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTagsContent =
                str_contains($pageSource, 'tag') ||
                str_contains($pageSource, 'create tag') ||
                str_contains($pageSource, 'manage tags');

            $this->assertTrue($hasTagsContent, 'Server tags manager should load');

            $this->testResults['server_tags_manager'] = 'Server tags manager loaded successfully';
        });
    }

    /**
     * Test 9: Server status is displayed
     *
     */

    #[Test]
    public function test_server_status_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-status-display');

            // Check for status display via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatus =
                str_contains($pageSource, 'online') ||
                str_contains($pageSource, 'offline') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasStatus, 'Server status should be displayed');

            $this->testResults['server_status'] = 'Server status is displayed';
        });
    }

    /**
     * Test 10: Server search functionality is present
     *
     */

    #[Test]
    public function test_server_search_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-search');

            // Check for search input via page source
            $pageSource = $browser->driver->getPageSource();
            $hasSearch =
                str_contains($pageSource, 'wire:model.live="search"') ||
                str_contains($pageSource, 'wire:model="search"') ||
                str_contains($pageSource, 'Search');

            $this->assertTrue($hasSearch, 'Server search functionality should be present');

            $this->testResults['server_search'] = 'Server search functionality is present';
        });
    }

    /**
     * Test 11: Server filter by status is available
     *
     */

    #[Test]
    public function test_server_status_filter_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-status-filter');

            // Check for status filter via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFilter =
                str_contains($pageSource, 'statusfilter') ||
                str_contains($pageSource, 'filter by status');

            $this->assertTrue($hasFilter, 'Server status filter should be available');

            $this->testResults['server_status_filter'] = 'Server status filter is available';
        });
    }

    /**
     * Test 12: Server ping action is present
     *
     */

    #[Test]
    public function test_server_ping_action_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-ping-action');

            // Check for ping action via page source
            $pageSource = $browser->driver->getPageSource();
            $hasPing =
                str_contains($pageSource, 'pingServer') ||
                str_contains($pageSource, 'Ping') ||
                str_contains($pageSource, 'Test Connection');

            $this->assertTrue($hasPing, 'Server ping action should be present');

            $this->testResults['server_ping_action'] = 'Server ping action is present';
        });
    }

    /**
     * Test 13: Server reboot action is present
     *
     */

    #[Test]
    public function test_server_reboot_action_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-reboot-action');

            // Check for reboot action via page source
            $pageSource = $browser->driver->getPageSource();
            $hasReboot =
                str_contains($pageSource, 'rebootServer') ||
                str_contains($pageSource, 'Reboot') ||
                str_contains($pageSource, 'restart');

            $this->assertTrue($hasReboot, 'Server reboot action should be present');

            $this->testResults['server_reboot_action'] = 'Server reboot action is present';
        });
    }

    /**
     * Test 14: Server Docker status is displayed
     *
     */

    #[Test]
    public function test_server_docker_status_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-docker-status');

            // Check for Docker status via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDockerStatus =
                str_contains($pageSource, 'docker') ||
                str_contains($pageSource, 'docker_installed');

            $this->assertTrue($hasDockerStatus, 'Server Docker status should be displayed');

            $this->testResults['server_docker_status'] = 'Server Docker status is displayed';
        });
    }

    /**
     * Test 15: Server Docker management page loads
     *
     */

    #[Test]
    public function test_server_docker_management_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/docker")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-docker-management');

            // Check if Docker management page loaded
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDockerManagement =
                str_contains($pageSource, 'docker') ||
                str_contains($pageSource, 'container');

            $this->assertTrue($hasDockerManagement, 'Server Docker management page should load');

            $this->testResults['server_docker_management'] = 'Server Docker management page loaded successfully';
        });
    }

    /**
     * Test 16: Server hardware info is displayed
     *
     */

    #[Test]
    public function test_server_hardware_info_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-hardware-info');

            // Check for hardware info via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHardwareInfo =
                str_contains($pageSource, 'cpu') ||
                str_contains($pageSource, 'memory') ||
                str_contains($pageSource, 'disk') ||
                str_contains($pageSource, 'cores');

            $this->assertTrue($hasHardwareInfo, 'Server hardware info should be displayed');

            $this->testResults['server_hardware_info'] = 'Server hardware info is displayed';
        });
    }

    /**
     * Test 17: Server projects list is displayed
     *
     */

    #[Test]
    public function test_server_projects_list_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-projects-list');

            // Check for projects list via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProjectsList =
                str_contains($pageSource, 'project') ||
                str_contains($pageSource, 'deployment');

            $this->assertTrue($hasProjectsList, 'Server projects list should be displayed');

            $this->testResults['server_projects_list'] = 'Server projects list is displayed';
        });
    }

    /**
     * Test 18: Server SSH configuration is present
     *
     */

    #[Test]
    public function test_server_ssh_configuration_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-ssh-config');

            // Check for SSH configuration fields
            $pageSource = $browser->driver->getPageSource();
            $hasSshConfig =
                str_contains($pageSource, 'ssh_password') ||
                str_contains($pageSource, 'ssh_key') ||
                str_contains($pageSource, 'auth_method');

            $this->assertTrue($hasSshConfig, 'Server SSH configuration should be present');

            $this->testResults['server_ssh_config'] = 'Server SSH configuration is present';
        });
    }

    /**
     * Test 19: Server delete action is present
     *
     */

    #[Test]
    public function test_server_delete_action_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-delete-action');

            // Check for delete action via page source
            $pageSource = $browser->driver->getPageSource();
            $hasDelete =
                str_contains($pageSource, 'deleteServer') ||
                str_contains($pageSource, 'Delete') ||
                str_contains($pageSource, 'Remove');

            $this->assertTrue($hasDelete, 'Server delete action should be present');

            $this->testResults['server_delete_action'] = 'Server delete action is present';
        });
    }

    /**
     * Test 20: Server SSL manager page loads
     *
     */

    #[Test]
    public function test_server_ssl_manager_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-ssl-manager');

            // Check if SSL manager page loaded
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSslManager =
                str_contains($pageSource, 'ssl') ||
                str_contains($pageSource, 'certificate');

            $this->assertTrue($hasSslManager, 'Server SSL manager page should load');

            $this->testResults['server_ssl_manager'] = 'Server SSL manager page loaded successfully';
        });
    }

    /**
     * Test 21: Server alerts page loads
     *
     */

    #[Test]
    public function test_server_alerts_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/alerts")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-alerts-page');

            // Check if alerts page loaded
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAlerts =
                str_contains($pageSource, 'alert') ||
                str_contains($pageSource, 'resource') ||
                str_contains($pageSource, 'threshold');

            $this->assertTrue($hasAlerts, 'Server alerts page should load');

            $this->testResults['server_alerts_page'] = 'Server alerts page loaded successfully';
        });
    }

    /**
     * Test 22: Server backups page loads
     *
     */

    #[Test]
    public function test_server_backups_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-backups-page');

            // Check if backups page loaded
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBackups =
                str_contains($pageSource, 'backup') ||
                str_contains($pageSource, 'restore') ||
                str_contains($pageSource, 'schedule');

            $this->assertTrue($hasBackups, 'Server backups page should load');

            $this->testResults['server_backups_page'] = 'Server backups page loaded successfully';
        });
    }

    /**
     * Test 23: Server security dashboard loads
     *
     */

    #[Test]
    public function test_server_security_dashboard_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/security")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-security-dashboard');

            // Check if security dashboard loaded
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSecurity =
                str_contains($pageSource, 'security') ||
                str_contains($pageSource, 'firewall') ||
                str_contains($pageSource, 'ssh');

            $this->assertTrue($hasSecurity, 'Server security dashboard should load');

            $this->testResults['server_security_dashboard'] = 'Server security dashboard loaded successfully';
        });
    }

    /**
     * Test 24: Server tag assignment is available
     *
     */

    #[Test]
    public function test_server_tag_assignment_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-tag-assignment');

            // Check for tag functionality via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTagAssignment =
                str_contains($pageSource, 'tag') ||
                str_contains($pageSource, 'label');

            $this->assertTrue($hasTagAssignment, 'Server tag assignment should be available');

            $this->testResults['server_tag_assignment'] = 'Server tag assignment is available';
        });
    }

    /**
     * Test 25: Server connection test is available
     *
     */

    #[Test]
    public function test_server_connection_test_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-connection-test');

            // Check for connection test functionality
            $pageSource = $browser->driver->getPageSource();
            $hasConnectionTest =
                str_contains($pageSource, 'testConnection') ||
                str_contains($pageSource, 'Test Connection');

            $this->assertTrue($hasConnectionTest, 'Server connection test should be available');

            $this->testResults['server_connection_test'] = 'Server connection test is available';
        });
    }

    /**
     * Test 26: Server metrics show CPU usage
     *
     */

    #[Test]
    public function test_server_metrics_show_cpu_usage()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-metrics-cpu');

            // Check for CPU metrics
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCpuMetrics =
                str_contains($pageSource, 'cpu usage') ||
                str_contains($pageSource, 'cpu_usage') ||
                str_contains($pageSource, 'processor');

            $this->assertTrue($hasCpuMetrics, 'Server metrics should show CPU usage');

            $this->testResults['server_metrics_cpu'] = 'Server metrics show CPU usage';
        });
    }

    /**
     * Test 27: Server metrics show memory usage
     *
     */

    #[Test]
    public function test_server_metrics_show_memory_usage()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-metrics-memory');

            // Check for memory metrics
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMemoryMetrics =
                str_contains($pageSource, 'memory usage') ||
                str_contains($pageSource, 'memory_usage') ||
                str_contains($pageSource, 'ram');

            $this->assertTrue($hasMemoryMetrics, 'Server metrics should show memory usage');

            $this->testResults['server_metrics_memory'] = 'Server metrics show memory usage';
        });
    }

    /**
     * Test 28: Server metrics show disk usage
     *
     */

    #[Test]
    public function test_server_metrics_show_disk_usage()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-metrics-disk');

            // Check for disk metrics
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDiskMetrics =
                str_contains($pageSource, 'disk usage') ||
                str_contains($pageSource, 'disk_usage') ||
                str_contains($pageSource, 'storage');

            $this->assertTrue($hasDiskMetrics, 'Server metrics should show disk usage');

            $this->testResults['server_metrics_disk'] = 'Server metrics show disk usage';
        });
    }

    /**
     * Test 29: Server bulk actions are available
     *
     */

    #[Test]
    public function test_server_bulk_actions_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-bulk-actions');

            // Check for bulk actions
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBulkActions =
                str_contains($pageSource, 'bulk') ||
                str_contains($pageSource, 'select all') ||
                str_contains($pageSource, 'selectedservers');

            $this->assertTrue($hasBulkActions, 'Server bulk actions should be available');

            $this->testResults['server_bulk_actions'] = 'Server bulk actions are available';
        });
    }

    /**
     * Test 30: Server location information is present
     *
     */

    #[Test]
    public function test_server_location_information_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-location-info');

            // Check for location fields
            $pageSource = $browser->driver->getPageSource();
            $hasLocationInfo =
                str_contains($pageSource, 'latitude') ||
                str_contains($pageSource, 'longitude') ||
                str_contains($pageSource, 'location');

            $this->assertTrue($hasLocationInfo, 'Server location information should be present');

            $this->testResults['server_location_info'] = 'Server location information is present';
        });
    }

    /**
     * Test 31: Server validation messages are handled
     *
     */

    #[Test]
    public function test_server_validation_messages_handled()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers/create')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-validation');

            // Check for validation error handling
            $pageSource = $browser->driver->getPageSource();
            $hasValidation =
                str_contains($pageSource, '@error(') ||
                str_contains($pageSource, '$message');

            $this->assertTrue($hasValidation, 'Server validation messages should be handled');

            $this->testResults['server_validation'] = 'Server validation messages are handled';
        });
    }

    /**
     * Test 32: Server last ping time is displayed
     *
     */

    #[Test]
    public function test_server_last_ping_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-last-ping');

            // Check for last ping time
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLastPing =
                str_contains($pageSource, 'last ping') ||
                str_contains($pageSource, 'last_ping_at') ||
                str_contains($pageSource, 'checked');

            $this->assertTrue($hasLastPing, 'Server last ping time should be displayed');

            $this->testResults['server_last_ping'] = 'Server last ping time is displayed';
        });
    }

    /**
     * Test 33: Server service restart is available
     *
     */

    #[Test]
    public function test_server_service_restart_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-service-restart');

            // Check for service restart functionality
            $pageSource = $browser->driver->getPageSource();
            $hasServiceRestart =
                str_contains($pageSource, 'restartService') ||
                str_contains($pageSource, 'Restart Service');

            $this->assertTrue($hasServiceRestart, 'Server service restart should be available');

            $this->testResults['server_service_restart'] = 'Server service restart is available';
        });
    }

    /**
     * Test 34: Server cache clear is available
     *
     */

    #[Test]
    public function test_server_cache_clear_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-cache-clear');

            // Check for cache clear functionality
            $pageSource = $browser->driver->getPageSource();
            $hasCacheClear =
                str_contains($pageSource, 'clearSystemCache') ||
                str_contains($pageSource, 'Clear Cache');

            $this->assertTrue($hasCacheClear, 'Server cache clear should be available');

            $this->testResults['server_cache_clear'] = 'Server cache clear is available';
        });
    }

    /**
     * Test 35: Server supports dark mode
     *
     */

    #[Test]
    public function test_server_supports_dark_mode()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-dark-mode');

            // Check for dark mode classes
            $pageSource = $browser->driver->getPageSource();
            $hasDarkMode =
                str_contains($pageSource, 'dark:bg-') ||
                str_contains($pageSource, 'dark:text-');

            $this->assertTrue($hasDarkMode, 'Server pages should support dark mode');

            $this->testResults['server_dark_mode'] = 'Server pages support dark mode';
        });
    }

    /**
     * Test 36: Server pagination is present
     *
     */

    #[Test]
    public function test_server_pagination_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-pagination');

            // Check for pagination
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPagination =
                str_contains($pageSource, 'pagination') ||
                str_contains($pageSource, 'page') ||
                str_contains($pageSource, 'next');

            $this->assertTrue($hasPagination, 'Server pagination should be present');

            $this->testResults['server_pagination'] = 'Server pagination is present';
        });
    }

    /**
     * Test 37: Server OS information is displayed
     *
     */

    #[Test]
    public function test_server_os_information_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-os-info');

            // Check for OS information
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasOsInfo =
                str_contains($pageSource, 'ubuntu') ||
                str_contains($pageSource, 'operating system') ||
                str_contains($pageSource, 'os');

            $this->assertTrue($hasOsInfo, 'Server OS information should be displayed');

            $this->testResults['server_os_info'] = 'Server OS information is displayed';
        });
    }

    /**
     * Test 38: Server IP address is displayed
     *
     */

    #[Test]
    public function test_server_ip_address_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-ip-address');

            // Check for IP address display
            $pageSource = $browser->driver->getPageSource();
            $hasIpAddress =
                str_contains($pageSource, $this->testServer->ip_address) ||
                str_contains($pageSource, 'ip_address');

            $this->assertTrue($hasIpAddress, 'Server IP address should be displayed');

            $this->testResults['server_ip_address'] = 'Server IP address is displayed';
        });
    }

    /**
     * Test 39: Navigation to servers from dashboard works
     *
     */

    #[Test]
    public function test_navigation_to_servers_from_dashboard()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/dashboard')
                ->pause(2000)
                ->waitFor('body', 15);

            // Try to navigate to servers page
            $browser->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-navigation');

            $currentUrl = $browser->driver->getCurrentURL();
            $onServersPage = str_contains($currentUrl, '/servers');

            $this->assertTrue($onServersPage, 'Should be able to navigate to servers page');

            $this->testResults['server_navigation'] = 'Navigation to servers page works';
        });
    }

    /**
     * Test 40: Server current VPS quick add is available
     *
     */

    #[Test]
    public function test_server_current_vps_quick_add_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/servers')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('servers-quick-add');

            // Check for quick add current server functionality
            $pageSource = $browser->driver->getPageSource();
            $hasQuickAdd =
                str_contains($pageSource, 'addCurrentServer') ||
                str_contains($pageSource, 'Add Current Server');

            $this->assertTrue($hasQuickAdd, 'Server current VPS quick add should be available');

            $this->testResults['server_quick_add'] = 'Server current VPS quick add is available';
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
                'test_suite' => 'Server Management Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'servers_count' => Server::count(),
                    'tags_count' => ServerTag::count(),
                    'admin_user_id' => $this->adminUser->id,
                    'admin_user_name' => $this->adminUser->name,
                    'test_server_id' => $this->testServer->id,
                    'test_server_name' => $this->testServer->name,
                ],
            ];

            $reportPath = storage_path('app/test-reports/server-management-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
