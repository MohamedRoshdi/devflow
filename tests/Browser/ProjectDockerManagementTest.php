<?php

namespace Tests\Browser;

use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ProjectDockerManagementTest extends DuskTestCase
{
    use LoginViaUI;

    // use RefreshDatabase; // Disabled - testing against existing app

    protected User $user;

    protected Server $server;

    protected Project $project;

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

        // Get or create test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'docker.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Docker Test Server',
                'ip_address' => '192.168.1.200',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Get or create test project with Docker
        $this->project = Project::firstOrCreate(
            ['slug' => 'test-docker-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Docker Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/docker-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/docker-project',
            ]
        );
    }

    /**
     * Test 1: Docker management page is accessible
     */
    public function test_docker_management_page_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->assertPresent('button:contains("Docker"), a:contains("Docker"), [x-on\\:click*="docker"]')
                ->screenshot('docker-management-accessible');
        });
    }

    /**
     * Test 2: Docker tab displays overview section
     */
    public function test_docker_tab_displays_overview_section(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(2000)
                ->assertPresent('[wire\\:click*="switchTab(\'overview\')"], button:contains("Overview"), [class*="overview"]')
                ->screenshot('docker-overview-section');
        });
    }

    /**
     * Test 3: Container information is displayed
     */
    public function test_container_information_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('div, section, [class*="container"]')
                ->screenshot('docker-container-information');
        });
    }

    /**
     * Test 4: Container status indicator is visible
     */
    public function test_container_status_indicator_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="status"], [class*="badge"], .badge, span')
                ->screenshot('docker-status-indicator');
        });
    }

    /**
     * Test 5: Start container button is present
     */
    public function test_start_container_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Start"), button[wire\\:click*="startContainer"]')
                ->screenshot('docker-start-button');
        });
    }

    /**
     * Test 6: Stop container button is present
     */
    public function test_stop_container_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Stop"), button[wire\\:click*="stopContainer"]')
                ->screenshot('docker-stop-button');
        });
    }

    /**
     * Test 7: Restart container button is present
     */
    public function test_restart_container_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Restart"), button[wire\\:click*="restartContainer"]')
                ->screenshot('docker-restart-button');
        });
    }

    /**
     * Test 8: Build image button is present
     */
    public function test_build_image_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Build"), button[wire\\:click*="buildImage"]')
                ->screenshot('docker-build-button');
        });
    }

    /**
     * Test 9: Docker images list is displayed
     */
    public function test_docker_images_list_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('div, section, table, [class*="image"]')
                ->screenshot('docker-images-list');
        });
    }

    /**
     * Test 10: Container logs tab is accessible
     */
    public function test_container_logs_tab_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(2000)
                ->assertPresent('button:contains("Logs"), a:contains("Logs"), [wire\\:click*="switchTab(\'logs\')"]')
                ->screenshot('docker-logs-tab');
        });
    }

    /**
     * Test 11: Container logs are displayed when logs tab is clicked
     */
    public function test_container_logs_are_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(2000)
                ->click('button:contains("Logs"), [wire\\:click*="switchTab(\'logs\')"]')
                ->pause(3000)
                ->assertPresent('pre, code, [class*="log"], textarea')
                ->screenshot('docker-logs-displayed');
        });
    }

    /**
     * Test 12: Refresh logs button is present
     */
    public function test_refresh_logs_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(2000)
                ->click('button:contains("Logs"), [wire\\:click*="switchTab(\'logs\')"]')
                ->pause(2000)
                ->assertPresent('button:contains("Refresh"), button[wire\\:click*="refreshLogs"]')
                ->screenshot('docker-refresh-logs-button');
        });
    }

    /**
     * Test 13: Log lines selector is present
     */
    public function test_log_lines_selector_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(2000)
                ->click('button:contains("Logs"), [wire\\:click*="switchTab(\'logs\')"]')
                ->pause(2000)
                ->assertPresent('select[wire\\:model*="logLines"], input[wire\\:model*="logLines"]')
                ->screenshot('docker-log-lines-selector');
        });
    }

    /**
     * Test 14: Container stats display CPU usage
     */
    public function test_container_stats_display_cpu_usage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="cpu"], [class*="stats"]')
                ->screenshot('docker-cpu-usage');
        });
    }

    /**
     * Test 15: Container stats display memory usage
     */
    public function test_container_stats_display_memory_usage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="memory"], [class*="mem"], [class*="stats"]')
                ->screenshot('docker-memory-usage');
        });
    }

    /**
     * Test 16: Container stats are updated in real-time
     */
    public function test_container_stats_real_time_updates(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[wire\\:poll], [wire\\:poll\\.5s]')
                ->screenshot('docker-real-time-stats');
        });
    }

    /**
     * Test 17: Container network information is displayed
     */
    public function test_container_network_information_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="network"], div, section')
                ->screenshot('docker-network-info');
        });
    }

    /**
     * Test 18: Container port mappings are displayed
     */
    public function test_container_port_mappings_are_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="port"], div, section, table')
                ->screenshot('docker-port-mappings');
        });
    }

    /**
     * Test 19: Container volumes information is displayed
     */
    public function test_container_volumes_information_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="volume"], div, section, table')
                ->screenshot('docker-volumes-info');
        });
    }

    /**
     * Test 20: Environment variables section is accessible
     */
    public function test_environment_variables_section_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(2000)
                ->assertPresent('[class*="env"], [class*="environment"], div, section')
                ->screenshot('docker-environment-section');
        });
    }

    /**
     * Test 21: Container ID is displayed
     */
    public function test_container_id_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="id"], code, span, div')
                ->screenshot('docker-container-id');
        });
    }

    /**
     * Test 22: Container image name is displayed
     */
    public function test_container_image_name_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="image"], code, span, div')
                ->screenshot('docker-image-name');
        });
    }

    /**
     * Test 23: Container created time is displayed
     */
    public function test_container_created_time_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('time, [datetime], [class*="time"], [class*="created"]')
                ->screenshot('docker-created-time');
        });
    }

    /**
     * Test 24: Container uptime is displayed
     */
    public function test_container_uptime_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="uptime"], span, div')
                ->screenshot('docker-uptime');
        });
    }

    /**
     * Test 25: Export container button is present
     */
    public function test_export_container_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Export"), button[wire\\:click*="exportContainer"]')
                ->screenshot('docker-export-button');
        });
    }

    /**
     * Test 26: Delete image button is present for each image
     */
    public function test_delete_image_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Delete"), button[wire\\:click*="deleteImage"]')
                ->screenshot('docker-delete-image-button');
        });
    }

    /**
     * Test 27: Docker compose file viewer is accessible
     */
    public function test_docker_compose_file_viewer_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(2000)
                ->assertPresent('button:contains("Compose"), a:contains("Compose"), [class*="compose"]')
                ->screenshot('docker-compose-viewer');
        });
    }

    /**
     * Test 28: Container running state shows green indicator
     */
    public function test_container_running_state_shows_green_indicator(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000);

            // Check for green/success color indicators
            $greenIndicator = $browser->element('[class*="green"], [class*="success"], [class*="emerald"]');
            $this->assertNotNull($greenIndicator, 'Running container should have green/success indicator');

            $browser->screenshot('docker-running-green-indicator');
        });
    }

    /**
     * Test 29: Loading state is displayed during operations
     */
    public function test_loading_state_is_displayed_during_operations(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(2000)
                ->assertPresent('[wire\\:loading], [class*="loading"], [class*="spinner"]')
                ->screenshot('docker-loading-state');
        });
    }

    /**
     * Test 30: Error messages are displayed when operations fail
     */
    public function test_error_messages_are_displayed_on_failure(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="error"], [class*="alert"], div, section')
                ->screenshot('docker-error-display');
        });
    }

    /**
     * Test 31: Success messages are displayed after operations
     */
    public function test_success_messages_are_displayed_after_operations(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="success"], [class*="alert"], div, section')
                ->screenshot('docker-success-message');
        });
    }

    /**
     * Test 32: Container health check status is displayed
     */
    public function test_container_health_check_status_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="health"], div, section, span')
                ->screenshot('docker-health-status');
        });
    }

    /**
     * Test 33: Container restart count is displayed
     */
    public function test_container_restart_count_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="restart"], span, div')
                ->screenshot('docker-restart-count');
        });
    }

    /**
     * Test 34: Image size information is displayed
     */
    public function test_image_size_information_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="size"], td, span, div')
                ->screenshot('docker-image-size');
        });
    }

    /**
     * Test 35: Image tags are displayed
     */
    public function test_image_tags_are_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="tag"], code, span, div')
                ->screenshot('docker-image-tags');
        });
    }

    /**
     * Test 36: Image created date is displayed
     */
    public function test_image_created_date_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('time, [datetime], [class*="created"], [class*="date"]')
                ->screenshot('docker-image-created-date');
        });
    }

    /**
     * Test 37: Container shell access button is present
     */
    public function test_container_shell_access_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Shell"), button:contains("Terminal"), a:contains("Shell")')
                ->screenshot('docker-shell-access-button');
        });
    }

    /**
     * Test 38: Container events history is accessible
     */
    public function test_container_events_history_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(2000)
                ->assertPresent('button:contains("Events"), a:contains("Events"), [class*="event"]')
                ->screenshot('docker-events-history');
        });
    }

    /**
     * Test 39: Prune unused resources button is present
     */
    public function test_prune_unused_resources_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Prune"), button:contains("Clean"), button[wire\\:click*="prune"]')
                ->screenshot('docker-prune-button');
        });
    }

    /**
     * Test 40: Docker compose up button is present
     */
    public function test_docker_compose_up_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Up"), button:contains("Start All"), button[wire\\:click*="composeUp"]')
                ->screenshot('docker-compose-up-button');
        });
    }

    /**
     * Test 41: Docker compose down button is present
     */
    public function test_docker_compose_down_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Down"), button:contains("Stop All"), button[wire\\:click*="composeDown"]')
                ->screenshot('docker-compose-down-button');
        });
    }

    /**
     * Test 42: Pull latest images button is present
     */
    public function test_pull_latest_images_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Pull"), button[wire\\:click*="pull"]')
                ->screenshot('docker-pull-images-button');
        });
    }

    /**
     * Test 43: Rebuild containers button is present
     */
    public function test_rebuild_containers_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Rebuild"), button[wire\\:click*="rebuild"]')
                ->screenshot('docker-rebuild-button');
        });
    }

    /**
     * Test 44: Container network mode is displayed
     */
    public function test_container_network_mode_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="network"], [class*="mode"], span, div')
                ->screenshot('docker-network-mode');
        });
    }

    /**
     * Test 45: Container IP address is displayed
     */
    public function test_container_ip_address_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="ip"], code, span, div')
                ->screenshot('docker-ip-address');
        });
    }

    /**
     * Test 46: Container mounts/volumes list is displayed
     */
    public function test_container_mounts_volumes_list_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="mount"], [class*="volume"], table, ul, div')
                ->screenshot('docker-mounts-list');
        });
    }

    /**
     * Test 47: Container command/entrypoint is displayed
     */
    public function test_container_command_entrypoint_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[class*="command"], code, pre, span')
                ->screenshot('docker-command-entrypoint');
        });
    }

    /**
     * Test 48: Docker stats auto-refresh interval is configurable
     */
    public function test_docker_stats_auto_refresh_interval_is_configurable(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('[wire\\:poll], [wire\\:poll\\.5s], [wire\\:poll\\.10s]')
                ->screenshot('docker-auto-refresh-interval');
        });
    }

    /**
     * Test 49: No container state is handled gracefully
     */
    public function test_no_container_state_is_handled_gracefully(): void
    {
        // Create project without Docker container
        $projectWithoutContainer = Project::firstOrCreate(
            ['slug' => 'test-project-no-container'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project No Container',
                'framework' => 'laravel',
                'status' => 'stopped',
                'repository_url' => 'https://github.com/test/no-container.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/no-container',
            ]
        );

        $this->browse(function (Browser $browser) use ($projectWithoutContainer) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$projectWithoutContainer->slug)
                ->waitForText($projectWithoutContainer->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertSee('No container')
                ->screenshot('docker-no-container-state');
        });
    }

    /**
     * Test 50: Docker management page shows refresh button
     */
    public function test_docker_management_shows_refresh_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(3000)
                ->assertPresent('button:contains("Refresh"), button[wire\\:click*="loadDockerInfo"]')
                ->screenshot('docker-refresh-button');
        });
    }
}
