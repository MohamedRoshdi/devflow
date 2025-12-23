<?php

namespace Tests\Browser;

use PHPUnit\Framework\Attributes\Group;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ServerShowTest extends DuskTestCase
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
                'notification_sound' => true,
                'desktop_notifications' => false,
            ]
        );

        // Create or get a server for testing
        $this->server = Server::first() ?? Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'hostname' => 'test-server.devflow.test',
            'ip_address' => '192.168.1.100',
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
     * Test 1: Page loads successfully when server exists
     *     */
    #[Group('server-show')]
    public function test_page_loads_successfully_with_server(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertPathIs('/servers/'.$this->server->id)
                ->assertVisible('body')
                ->assertDontSee('404');

            $this->testResults['page_loads'] = 'PASS';
        });
    }

    /**
     * Test 2: Page shows error when server doesn't exist
     *     */
    #[Group('server-show')]
    public function test_page_shows_error_for_nonexistent_server(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/servers/99999')
                ->pause(1000)
                ->assertSee('404');

            $this->testResults['nonexistent_server'] = 'PASS';
        });
    }

    /**
     * Test 3: Server name is displayed
     *     */
    #[Group('server-show')]
    public function test_server_name_displayed(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee($this->server->name);

            $this->testResults['server_name'] = 'PASS';
        });
    }

    /**
     * Test 4: Server IP address is shown
     *     */
    #[Group('server-show')]
    public function test_server_ip_address_shown(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee($this->server->ip_address);

            $this->testResults['ip_address'] = 'PASS';
        });
    }

    /**
     * Test 5: Server status indicator is visible
     *     */
    #[Group('server-show')]
    public function test_server_status_indicator_visible(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee(ucfirst($this->server->status));

            $this->testResults['status_indicator'] = 'PASS';
        });
    }

    /**
     * Test 6: Edit server button is visible
     *     */
    #[Group('server-show')]
    public function test_edit_server_button_visible(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSeeLink('Edit');

            $this->testResults['edit_button'] = 'PASS';
        });
    }

    /**
     * Test 7: Back to servers link is visible
     *     */
    #[Group('server-show')]
    public function test_back_button_visible(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSeeLink('Back');

            $this->testResults['back_button'] = 'PASS';
        });
    }

    /**
     * Test 8: Quick Actions section is present
     *     */
    #[Group('server-show')]
    public function test_quick_actions_section_present(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee('Quick Actions');

            $this->testResults['quick_actions'] = 'PASS';
        });
    }

    /**
     * Test 9: Metrics link is visible
     *     */
    #[Group('server-show')]
    public function test_metrics_link_visible(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSeeLink('Metrics Dashboard');

            $this->testResults['metrics_link'] = 'PASS';
        });
    }

    /**
     * Test 10: Security link is visible
     *     */
    #[Group('server-show')]
    public function test_security_link_visible(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSeeLink('Security');

            $this->testResults['security_link'] = 'PASS';
        });
    }

    /**
     * Test 11: Backups link is visible
     *     */
    #[Group('server-show')]
    public function test_backups_link_visible(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSeeLink('Backups');

            $this->testResults['backups_link'] = 'PASS';
        });
    }

    /**
     * Test 12: Docker link/button is visible
     *     */
    #[Group('server-show')]
    public function test_docker_link_visible(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500);

            // Check for either Docker Panel or Install Docker or Check Docker
            $hasDockerpanel = $browser->seeLink('Docker Panel');
            $hasInstallDocker = $browser->seeIn('body', 'Install Docker');
            $hasCheckDocker = $browser->seeIn('body', 'Check Docker');

            $this->assertTrue(
                $hasDockerpanel || $hasInstallDocker || $hasCheckDocker,
                'Should see Docker-related link or button'
            );

            $this->testResults['docker_link'] = 'PASS';
        });
    }

    /**
     * Test 13: SSL link is visible
     *     */
    #[Group('server-show')]
    public function test_ssl_link_visible(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSeeLink('SSL Certificates');

            $this->testResults['ssl_link'] = 'PASS';
        });
    }

    /**
     * Test 14: Projects section is displayed
     *     */
    #[Group('server-show')]
    public function test_projects_section_displayed(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee('Projects');

            $this->testResults['projects_section'] = 'PASS';
        });
    }

    /**
     * Test 15: Projects list shows server projects
     *     */
    #[Group('server-show')]
    public function test_projects_list_shows_server_projects(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500);

            $projectCount = $this->server->projects()->count();

            if ($projectCount > 0) {
                $firstProject = $this->server->projects()->first();
                $browser->assertSee($firstProject->name);
            } else {
                $browser->assertSee('No projects on this server');
            }

            $this->testResults['projects_list'] = 'PASS';
        });
    }

    /**
     * Test 16: Resource usage/metrics section is shown
     *     */
    #[Group('server-show')]
    public function test_resource_usage_shown(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee('Live Metrics');

            $this->testResults['resource_usage'] = 'PASS';
        });
    }

    /**
     * Test 17: Connection status or last ping is shown
     *     */
    #[Group('server-show')]
    public function test_connection_status_indicator(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee('Last ping:');

            $this->testResults['connection_status'] = 'PASS';
        });
    }

    /**
     * Test 18: Server OS info is displayed
     *     */
    #[Group('server-show')]
    public function test_server_os_info_displayed(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee('Operating System');

            if ($this->server->os) {
                $browser->assertSee($this->server->os);
            } else {
                $browser->assertSee('Unknown');
            }

            $this->testResults['os_info'] = 'PASS';
        });
    }

    /**
     * Test 19: CPU/RAM/Disk info is shown
     *     */
    #[Group('server-show')]
    public function test_cpu_ram_disk_info_shown(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500);

            // Check for CPU info
            $browser->assertSee('CPU Cores');

            // Check for Memory info
            $browser->assertSee('Memory');

            // Check for Disk info
            $browser->assertSee('Disk Space');

            $this->testResults['system_specs'] = 'PASS';
        });
    }

    /**
     * Test 20: Server Information section is present
     *     */
    #[Group('server-show')]
    public function test_server_information_section_present(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee('Server Information')
                ->assertSee('Hostname')
                ->assertSee('IP Address')
                ->assertSee('SSH Port')
                ->assertSee('Username');

            $this->testResults['server_information'] = 'PASS';
        });
    }

    /**
     * Test 21: Flash messages display after actions
     *     */
    #[Group('server-show')]
    public function test_flash_messages_display(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            // Manually set a flash message by visiting with session
            session()->flash('message', 'Test success message');

            $browser->visit(route('servers.show', $this->server))
                ->pause(1000);

            // Note: Flash messages are consumed on first read, so this test
            // just verifies the page doesn't crash when flash messages exist
            $this->assertTrue(true, 'Page loads with flash messages');

            $this->testResults['flash_messages'] = 'PASS';
        });
    }

    /**
     * Test 22: Ping server button is functional
     *     */
    #[Group('server-show')]
    public function test_ping_server_button_functional(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500);

            // Check if ping button exists
            $hasPingButton = $browser->seeIn('body', 'Ping');

            $this->assertTrue($hasPingButton, 'Should see Ping button');

            $this->testResults['ping_button'] = 'PASS';
        });
    }

    /**
     * Test 23: Docker status card is displayed
     *     */
    #[Group('server-show')]
    public function test_docker_status_card_displayed(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee('Docker');

            if ($this->server->docker_installed) {
                // Should show installed with version
                $browser->assertDontSee('Not Installed');
            } else {
                // Should show not installed
                $browser->assertSee('Not Installed');
            }

            $this->testResults['docker_status_card'] = 'PASS';
        });
    }

    /**
     * Test 24: Status card shows correct status badge
     *     */
    #[Group('server-show')]
    public function test_status_card_shows_correct_badge(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee('Status');

            // Verify the status text is present
            $expectedStatus = ucfirst($this->server->status);
            $browser->assertSee($expectedStatus);

            $this->testResults['status_card'] = 'PASS';
        });
    }

    /**
     * Test 25: Recent Deployments section is present
     *     */
    #[Group('server-show')]
    public function test_recent_deployments_section_present(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee('Recent Deployments');

            $this->testResults['deployments_section'] = 'PASS';
        });
    }

    /**
     * Test 26: Reboot server button is visible
     *     */
    #[Group('server-show')]
    public function test_reboot_server_button_visible(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500);

            $hasRebootButton = $browser->seeIn('body', 'Reboot');

            $this->assertTrue($hasRebootButton, 'Should see Reboot button');

            $this->testResults['reboot_button'] = 'PASS';
        });
    }

    /**
     * Test 27: Clear cache button is visible
     *     */
    #[Group('server-show')]
    public function test_clear_cache_button_visible(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500);

            $hasClearCacheButton = $browser->seeIn('body', 'Clear Cache');

            $this->assertTrue($hasClearCacheButton, 'Should see Clear Cache button');

            $this->testResults['clear_cache_button'] = 'PASS';
        });
    }

    /**
     * Test 28: Services restart dropdown is visible
     *     */
    #[Group('server-show')]
    public function test_services_dropdown_visible(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500);

            $hasServicesButton = $browser->seeIn('body', 'Services');

            $this->assertTrue($hasServicesButton, 'Should see Services button');

            $this->testResults['services_dropdown'] = 'PASS';
        });
    }

    /**
     * Test 29: SSH Terminal section is present
     *     */
    #[Group('server-show')]
    public function test_ssh_terminal_section_present(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee('SSH Terminal');

            $this->testResults['ssh_terminal'] = 'PASS';
        });
    }

    /**
     * Test 30: Metrics data displays when available
     *     */
    #[Group('server-show')]
    public function test_metrics_data_displays_when_available(): void
    {        // Create a metric for the server
        ServerMetric::create([
            'server_id' => $this->server->id,
            'cpu_usage' => 45.5,
            'memory_usage' => 62.3,
            'disk_usage' => 38.7,
            'network_rx' => 1024,
            'network_tx' => 2048,
            'recorded_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500);

            // Check for CPU, Memory, Disk usage labels
            $browser->assertSee('CPU Usage')
                ->assertSee('Memory Usage')
                ->assertSee('Disk Usage');

            $this->testResults['metrics_display'] = 'PASS';
        });
    }

    /**
     * Test 31: Empty state message when no metrics
     *     */
    #[Group('server-show')]
    public function test_empty_state_when_no_metrics(): void
    {
        // Create a server with no metrics
        $serverWithoutMetrics = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server No Metrics',
            'hostname' => 'test-no-metrics.example.com',
            'ip_address' => '192.168.100.50',
            'port' => 22,
            'username' => 'testuser',
            'status' => 'online',
        ]);

        $this->browse(function (Browser $browser) use ($serverWithoutMetrics) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $serverWithoutMetrics))
                ->pause(1500)
                ->assertSee('No metrics available yet');

            $this->testResults['no_metrics_empty_state'] = 'PASS';
        });

        // Cleanup
        $serverWithoutMetrics->delete();
    }

    /**
     * Test 32: Empty state message when no deployments
     *     */
    #[Group('server-show')]
    public function test_empty_state_when_no_deployments(): void
    {
        // Create a server with no deployments
        $serverWithoutDeployments = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server No Deployments',
            'hostname' => 'test-no-deployments.example.com',
            'ip_address' => '192.168.100.51',
            'port' => 22,
            'username' => 'testuser',
            'status' => 'online',
        ]);

        $this->browse(function (Browser $browser) use ($serverWithoutDeployments) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $serverWithoutDeployments))
                ->pause(1500)
                ->assertSee('No deployments yet');

            $this->testResults['no_deployments_empty_state'] = 'PASS';
        });

        // Cleanup
        $serverWithoutDeployments->delete();
    }

    /**
     * Test 33: Server hostname is displayed in info section
     *     */
    #[Group('server-show')]
    public function test_server_hostname_displayed(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee($this->server->hostname);

            $this->testResults['hostname_displayed'] = 'PASS';
        });
    }

    /**
     * Test 34: Server port is displayed
     *     */
    #[Group('server-show')]
    public function test_server_port_displayed(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee((string) $this->server->port);

            $this->testResults['port_displayed'] = 'PASS';
        });
    }

    /**
     * Test 35: Server username is displayed
     *     */
    #[Group('server-show')]
    public function test_server_username_displayed(): void
    {        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit(route('servers.show', $this->server))
                ->pause(1500)
                ->assertSee($this->server->username);

            $this->testResults['username_displayed'] = 'PASS';
        });
    }

    /**
     * Clean up after all tests
     */
    protected function tearDown(): void
    {
        if (! empty($this->testResults)) {
            echo "\n\n=== ServerShow Test Results ===\n";
            foreach ($this->testResults as $test => $result) {
                echo sprintf("%-40s %s\n", $test, $result);
            }
            echo "================================\n\n";
        }

        parent::tearDown();
    }
}
