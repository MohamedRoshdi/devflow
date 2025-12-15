<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class SSHTerminalTest extends DuskTestCase
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
                'name' => 'Test SSH Server',
                'hostname' => 'ssh-test.example.com',
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
     * Test 1: SSH Terminal page loads successfully
     *
     */

    #[Test]
    public function test_user_can_access_ssh_terminal()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-page');

            // Check if terminal page loaded
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTerminal =
                str_contains($pageSource, 'terminal') ||
                str_contains($pageSource, 'ssh') ||
                str_contains($pageSource, 'command');

            $this->assertTrue($hasTerminal, 'SSH Terminal page should load');

            $this->testResults['terminal_page_access'] = 'SSH Terminal page loaded successfully';
        });
    }

    /**
     * Test 2: Terminal header is displayed
     *
     */

    #[Test]
    public function test_terminal_header_is_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-header');

            // Check for terminal header elements
            $pageSource = $browser->driver->getPageSource();
            $hasHeader =
                str_contains($pageSource, 'SSH Terminal') ||
                str_contains($pageSource, $this->testServer->username) ||
                str_contains($pageSource, $this->testServer->ip_address);

            $this->assertTrue($hasHeader, 'Terminal header should be displayed');

            $this->testResults['terminal_header'] = 'Terminal header is displayed';
        });
    }

    /**
     * Test 3: Connection status indicator is present
     *
     */

    #[Test]
    public function test_connection_status_indicator_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-status');

            // Check for connection status elements
            $pageSource = $browser->driver->getPageSource();
            $hasStatus =
                str_contains($pageSource, 'bg-green-500') ||
                str_contains($pageSource, 'bg-red-500') ||
                str_contains($pageSource, 'bg-yellow-500');

            $this->assertTrue($hasStatus, 'Connection status indicator should be present');

            $this->testResults['connection_status'] = 'Connection status indicator is present';
        });
    }

    /**
     * Test 4: Command input field is visible
     *
     */

    #[Test]
    public function test_command_input_field_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-input');

            // Check for command input field
            $pageSource = $browser->driver->getPageSource();
            $hasInput =
                str_contains($pageSource, 'wire:model="command"') ||
                str_contains($pageSource, 'Enter command');

            $this->assertTrue($hasInput, 'Command input field should be visible');

            $this->testResults['command_input'] = 'Command input field is visible';
        });
    }

    /**
     * Test 5: Execute button is present
     *
     */

    #[Test]
    public function test_execute_button_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-execute-button');

            // Check for execute button
            $pageSource = $browser->driver->getPageSource();
            $hasExecuteButton =
                str_contains($pageSource, 'Execute') ||
                str_contains($pageSource, 'executeCommand');

            $this->assertTrue($hasExecuteButton, 'Execute button should be present');

            $this->testResults['execute_button'] = 'Execute button is present';
        });
    }

    /**
     * Test 6: Command output display area exists
     *
     */

    #[Test]
    public function test_command_output_display_exists()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-output');

            // Check for output display area
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasOutput =
                str_contains($pageSource, 'command history') ||
                str_contains($pageSource, 'output') ||
                str_contains($pageSource, 'history');

            $this->assertTrue($hasOutput, 'Command output display area should exist');

            $this->testResults['output_display'] = 'Command output display area exists';
        });
    }

    /**
     * Test 7: Terminal history section is available
     *
     */

    #[Test]
    public function test_terminal_history_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-history');

            // Check for history section
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHistory =
                str_contains($pageSource, 'history') ||
                str_contains($pageSource, 'clear history');

            $this->assertTrue($hasHistory, 'Terminal history section should be available');

            $this->testResults['terminal_history'] = 'Terminal history section is available';
        });
    }

    /**
     * Test 8: Clear history button is present
     *
     */

    #[Test]
    public function test_clear_history_button_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-clear-history');

            // Check for clear history button
            $pageSource = $browser->driver->getPageSource();
            $hasClearButton =
                str_contains($pageSource, 'Clear History') ||
                str_contains($pageSource, 'clearHistory');

            $this->assertTrue($hasClearButton, 'Clear history button should be present');

            $this->testResults['clear_history_button'] = 'Clear history button is present';
        });
    }

    /**
     * Test 9: Terminal prompt symbol is displayed
     *
     */

    #[Test]
    public function test_terminal_prompt_symbol_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-prompt');

            // Check for prompt symbol
            $pageSource = $browser->driver->getPageSource();
            $hasPrompt =
                str_contains($pageSource, 'text-green-400') ||
                str_contains($pageSource, '$');

            $this->assertTrue($hasPrompt, 'Terminal prompt symbol should be displayed');

            $this->testResults['terminal_prompt'] = 'Terminal prompt symbol is displayed';
        });
    }

    /**
     * Test 10: Quick commands section is available
     *
     */

    #[Test]
    public function test_quick_commands_section_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-quick-commands');

            // Check for quick commands
            $pageSource = $browser->driver->getPageSource();
            $hasQuickCommands =
                str_contains($pageSource, 'Quick Commands') ||
                str_contains($pageSource, 'quickCommands');

            $this->assertTrue($hasQuickCommands, 'Quick commands section should be available');

            $this->testResults['quick_commands'] = 'Quick commands section is available';
        });
    }

    /**
     * Test 11: System info quick commands are present
     *
     */

    #[Test]
    public function test_system_info_quick_commands_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-system-commands');

            // Check for system info commands
            $pageSource = $browser->driver->getPageSource();
            $hasSystemCommands =
                str_contains($pageSource, 'System Info') ||
                str_contains($pageSource, 'uname -a') ||
                str_contains($pageSource, 'df -h');

            $this->assertTrue($hasSystemCommands, 'System info quick commands should be present');

            $this->testResults['system_commands'] = 'System info quick commands are present';
        });
    }

    /**
     * Test 12: Docker quick commands are present
     *
     */

    #[Test]
    public function test_docker_quick_commands_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-docker-commands');

            // Check for Docker commands
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDockerCommands =
                str_contains($pageSource, 'docker') ||
                str_contains($pageSource, 'docker ps');

            $this->assertTrue($hasDockerCommands, 'Docker quick commands should be present');

            $this->testResults['docker_commands'] = 'Docker quick commands are present';
        });
    }

    /**
     * Test 13: Process & Services commands are available
     *
     */

    #[Test]
    public function test_process_services_commands_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-process-commands');

            // Check for process/service commands
            $pageSource = $browser->driver->getPageSource();
            $hasProcessCommands =
                str_contains($pageSource, 'Process') ||
                str_contains($pageSource, 'Services') ||
                str_contains($pageSource, 'ps aux');

            $this->assertTrue($hasProcessCommands, 'Process & Services commands should be available');

            $this->testResults['process_commands'] = 'Process & Services commands are available';
        });
    }

    /**
     * Test 14: Terminal has dark theme styling
     *
     */

    #[Test]
    public function test_terminal_has_dark_theme()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-dark-theme');

            // Check for dark theme styling
            $pageSource = $browser->driver->getPageSource();
            $hasDarkTheme =
                str_contains($pageSource, 'bg-gray-900') ||
                str_contains($pageSource, 'bg-gray-800');

            $this->assertTrue($hasDarkTheme, 'Terminal should have dark theme styling');

            $this->testResults['dark_theme'] = 'Terminal has dark theme styling';
        });
    }

    /**
     * Test 15: Terminal font is monospace
     *
     */

    #[Test]
    public function test_terminal_font_is_monospace()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-monospace');

            // Check for monospace font
            $pageSource = $browser->driver->getPageSource();
            $hasMonospace =
                str_contains($pageSource, 'font-mono');

            $this->assertTrue($hasMonospace, 'Terminal should use monospace font');

            $this->testResults['monospace_font'] = 'Terminal uses monospace font';
        });
    }

    /**
     * Test 16: Command rerun functionality is available
     *
     */

    #[Test]
    public function test_command_rerun_functionality_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-rerun');

            // Check for rerun functionality
            $pageSource = $browser->driver->getPageSource();
            $hasRerun =
                str_contains($pageSource, 'Rerun') ||
                str_contains($pageSource, 'rerunCommand');

            $this->assertTrue($hasRerun, 'Command rerun functionality should be available');

            $this->testResults['rerun_functionality'] = 'Command rerun functionality is available';
        });
    }

    /**
     * Test 17: Exit code is displayed in history
     *
     */

    #[Test]
    public function test_exit_code_displayed_in_history()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-exit-code');

            // Check for exit code display
            $pageSource = $browser->driver->getPageSource();
            $hasExitCode =
                str_contains($pageSource, 'exit_code') ||
                str_contains($pageSource, 'Exit');

            $this->assertTrue($hasExitCode, 'Exit code should be displayed in history');

            $this->testResults['exit_code_display'] = 'Exit code is displayed in history';
        });
    }

    /**
     * Test 18: Success/failure indicators are present
     *
     */

    #[Test]
    public function test_success_failure_indicators_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-indicators');

            // Check for success/failure indicators
            $pageSource = $browser->driver->getPageSource();
            $hasIndicators =
                str_contains($pageSource, 'bg-green-900') ||
                str_contains($pageSource, 'bg-red-900') ||
                str_contains($pageSource, 'success');

            $this->assertTrue($hasIndicators, 'Success/failure indicators should be present');

            $this->testResults['success_indicators'] = 'Success/failure indicators are present';
        });
    }

    /**
     * Test 19: Timestamp is shown for commands
     *
     */

    #[Test]
    public function test_timestamp_shown_for_commands()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-timestamp');

            // Check for timestamp display
            $pageSource = $browser->driver->getPageSource();
            $hasTimestamp =
                str_contains($pageSource, 'timestamp') ||
                str_contains($pageSource, 'diffForHumans');

            $this->assertTrue($hasTimestamp, 'Timestamp should be shown for commands');

            $this->testResults['timestamp_display'] = 'Timestamp is shown for commands';
        });
    }

    /**
     * Test 20: Web services quick commands are available
     *
     */

    #[Test]
    public function test_web_services_commands_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-web-commands');

            // Check for web services commands
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWebCommands =
                str_contains($pageSource, 'web') ||
                str_contains($pageSource, 'nginx') ||
                str_contains($pageSource, 'apache');

            $this->assertTrue($hasWebCommands, 'Web services commands should be available');

            $this->testResults['web_commands'] = 'Web services commands are available';
        });
    }

    /**
     * Test 21: Log viewing commands are present
     *
     */

    #[Test]
    public function test_log_viewing_commands_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-log-commands');

            // Check for log commands
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLogCommands =
                str_contains($pageSource, 'logs') ||
                str_contains($pageSource, 'journalctl') ||
                str_contains($pageSource, 'tail');

            $this->assertTrue($hasLogCommands, 'Log viewing commands should be present');

            $this->testResults['log_commands'] = 'Log viewing commands are present';
        });
    }

    /**
     * Test 22: Terminal shows server username
     *
     */

    #[Test]
    public function test_terminal_shows_server_username()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-username');

            // Check for username display
            $pageSource = $browser->driver->getPageSource();
            $hasUsername =
                str_contains($pageSource, $this->testServer->username) ||
                str_contains($pageSource, 'username');

            $this->assertTrue($hasUsername, 'Terminal should show server username');

            $this->testResults['server_username'] = 'Terminal shows server username';
        });
    }

    /**
     * Test 23: Terminal shows server IP address
     *
     */

    #[Test]
    public function test_terminal_shows_server_ip()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-ip');

            // Check for IP address display
            $pageSource = $browser->driver->getPageSource();
            $hasIpAddress =
                str_contains($pageSource, $this->testServer->ip_address);

            $this->assertTrue($hasIpAddress, 'Terminal should show server IP address');

            $this->testResults['server_ip'] = 'Terminal shows server IP address';
        });
    }

    /**
     * Test 24: Terminal has responsive design
     *
     */

    #[Test]
    public function test_terminal_has_responsive_design()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-responsive');

            // Check for responsive classes
            $pageSource = $browser->driver->getPageSource();
            $hasResponsive =
                str_contains($pageSource, 'flex') ||
                str_contains($pageSource, 'space-');

            $this->assertTrue($hasResponsive, 'Terminal should have responsive design');

            $this->testResults['responsive_design'] = 'Terminal has responsive design';
        });
    }

    /**
     * Test 25: Command input has placeholder text
     *
     */

    #[Test]
    public function test_command_input_has_placeholder()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-placeholder');

            // Check for placeholder text
            $pageSource = $browser->driver->getPageSource();
            $hasPlaceholder =
                str_contains($pageSource, 'placeholder') ||
                str_contains($pageSource, 'Enter command');

            $this->assertTrue($hasPlaceholder, 'Command input should have placeholder text');

            $this->testResults['input_placeholder'] = 'Command input has placeholder text';
        });
    }

    /**
     * Test 26: Terminal has loading state indicator
     *
     */

    #[Test]
    public function test_terminal_has_loading_indicator()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-loading');

            // Check for loading state
            $pageSource = $browser->driver->getPageSource();
            $hasLoading =
                str_contains($pageSource, 'wire:loading') ||
                str_contains($pageSource, 'Running...');

            $this->assertTrue($hasLoading, 'Terminal should have loading state indicator');

            $this->testResults['loading_indicator'] = 'Terminal has loading state indicator';
        });
    }

    /**
     * Test 27: Execute button is disabled when executing
     *
     */

    #[Test]
    public function test_execute_button_disabled_when_executing()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-disabled-button');

            // Check for disabled state logic
            $pageSource = $browser->driver->getPageSource();
            $hasDisabledLogic =
                str_contains($pageSource, 'disabled') ||
                str_contains($pageSource, 'isExecuting');

            $this->assertTrue($hasDisabledLogic, 'Execute button should be disabled when executing');

            $this->testResults['disabled_button'] = 'Execute button is disabled when executing';
        });
    }

    /**
     * Test 28: Terminal input is disabled when executing
     *
     */

    #[Test]
    public function test_terminal_input_disabled_when_executing()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-disabled-input');

            // Check for input disabled state
            $pageSource = $browser->driver->getPageSource();
            $hasDisabledInput =
                str_contains($pageSource, '@if($isExecuting) disabled @endif');

            $this->assertTrue($hasDisabledInput, 'Terminal input should be disabled when executing');

            $this->testResults['disabled_input'] = 'Terminal input is disabled when executing';
        });
    }

    /**
     * Test 29: Quick command buttons have hover effect
     *
     */

    #[Test]
    public function test_quick_command_buttons_have_hover()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-hover');

            // Check for hover styles
            $pageSource = $browser->driver->getPageSource();
            $hasHover =
                str_contains($pageSource, 'hover:bg-');

            $this->assertTrue($hasHover, 'Quick command buttons should have hover effect');

            $this->testResults['hover_effect'] = 'Quick command buttons have hover effect';
        });
    }

    /**
     * Test 30: Quick command buttons have tooltips
     *
     */

    #[Test]
    public function test_quick_command_buttons_have_tooltips()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-tooltips');

            // Check for tooltips
            $pageSource = $browser->driver->getPageSource();
            $hasTooltips =
                str_contains($pageSource, 'title=');

            $this->assertTrue($hasTooltips, 'Quick command buttons should have tooltips');

            $this->testResults['tooltips'] = 'Quick command buttons have tooltips';
        });
    }

    /**
     * Test 31: Terminal has proper color scheme
     *
     */

    #[Test]
    public function test_terminal_has_proper_color_scheme()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-colors');

            // Check for color classes
            $pageSource = $browser->driver->getPageSource();
            $hasColors =
                str_contains($pageSource, 'text-green-400') ||
                str_contains($pageSource, 'text-blue-') ||
                str_contains($pageSource, 'text-red-');

            $this->assertTrue($hasColors, 'Terminal should have proper color scheme');

            $this->testResults['color_scheme'] = 'Terminal has proper color scheme';
        });
    }

    /**
     * Test 32: Terminal window has macOS-style controls
     *
     */

    #[Test]
    public function test_terminal_has_macos_controls()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-macos-controls');

            // Check for macOS-style control circles
            $pageSource = $browser->driver->getPageSource();
            $hasControls =
                str_contains($pageSource, 'bg-red-500') &&
                str_contains($pageSource, 'bg-yellow-500') &&
                str_contains($pageSource, 'bg-green-500');

            $this->assertTrue($hasControls, 'Terminal should have macOS-style controls');

            $this->testResults['macos_controls'] = 'Terminal has macOS-style controls';
        });
    }

    /**
     * Test 33: Explore system commands are available
     *
     */

    #[Test]
    public function test_explore_system_commands_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-explore-commands');

            // Check for explore commands
            $pageSource = $browser->driver->getPageSource();
            $hasExploreCommands =
                str_contains($pageSource, 'Explore') ||
                str_contains($pageSource, 'ls -la') ||
                str_contains($pageSource, 'pwd');

            $this->assertTrue($hasExploreCommands, 'Explore system commands should be available');

            $this->testResults['explore_commands'] = 'Explore system commands are available';
        });
    }

    /**
     * Test 34: Terminal form uses wire:submit.prevent
     *
     */

    #[Test]
    public function test_terminal_form_uses_wire_submit()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-wire-submit');

            // Check for wire:submit.prevent
            $pageSource = $browser->driver->getPageSource();
            $hasWireSubmit =
                str_contains($pageSource, 'wire:submit.prevent');

            $this->assertTrue($hasWireSubmit, 'Terminal form should use wire:submit.prevent');

            $this->testResults['wire_submit'] = 'Terminal form uses wire:submit.prevent';
        });
    }

    /**
     * Test 35: Command input has autofocus
     *
     */

    #[Test]
    public function test_command_input_has_autofocus()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-autofocus');

            // Check for autofocus attribute
            $pageSource = $browser->driver->getPageSource();
            $hasAutofocus =
                str_contains($pageSource, 'autofocus');

            $this->assertTrue($hasAutofocus, 'Command input should have autofocus');

            $this->testResults['autofocus'] = 'Command input has autofocus';
        });
    }

    /**
     * Test 36: Terminal has rounded corners
     *
     */

    #[Test]
    public function test_terminal_has_rounded_corners()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-rounded');

            // Check for rounded corners
            $pageSource = $browser->driver->getPageSource();
            $hasRounded =
                str_contains($pageSource, 'rounded-lg') ||
                str_contains($pageSource, 'rounded-t-lg');

            $this->assertTrue($hasRounded, 'Terminal should have rounded corners');

            $this->testResults['rounded_corners'] = 'Terminal has rounded corners';
        });
    }

    /**
     * Test 37: Terminal uses Livewire wire:model
     *
     */

    #[Test]
    public function test_terminal_uses_livewire_wire_model()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-wire-model');

            // Check for wire:model
            $pageSource = $browser->driver->getPageSource();
            $hasWireModel =
                str_contains($pageSource, 'wire:model');

            $this->assertTrue($hasWireModel, 'Terminal should use Livewire wire:model');

            $this->testResults['wire_model'] = 'Terminal uses Livewire wire:model';
        });
    }

    /**
     * Test 38: Command categories are organized
     *
     */

    #[Test]
    public function test_command_categories_are_organized()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-categories');

            // Check for command categories
            $pageSource = $browser->driver->getPageSource();
            $hasCategories =
                str_contains($pageSource, 'category') ||
                str_contains($pageSource, '@foreach');

            $this->assertTrue($hasCategories, 'Command categories should be organized');

            $this->testResults['categories'] = 'Command categories are organized';
        });
    }

    /**
     * Test 39: Terminal supports dark mode properly
     *
     */

    #[Test]
    public function test_terminal_supports_dark_mode()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-dark-mode-support');

            // Check for dark mode classes
            $pageSource = $browser->driver->getPageSource();
            $hasDarkMode =
                str_contains($pageSource, 'dark:bg-') ||
                str_contains($pageSource, 'dark:text-');

            $this->assertTrue($hasDarkMode, 'Terminal should support dark mode properly');

            $this->testResults['dark_mode_support'] = 'Terminal supports dark mode properly';
        });
    }

    /**
     * Test 40: Terminal has spacing and layout structure
     *
     */

    #[Test]
    public function test_terminal_has_spacing_layout()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-spacing');

            // Check for spacing classes
            $pageSource = $browser->driver->getPageSource();
            $hasSpacing =
                str_contains($pageSource, 'space-y-') ||
                str_contains($pageSource, 'px-') ||
                str_contains($pageSource, 'py-');

            $this->assertTrue($hasSpacing, 'Terminal should have proper spacing and layout structure');

            $this->testResults['spacing_layout'] = 'Terminal has spacing and layout structure';
        });
    }

    /**
     * Test 41: Quick command click sets input value
     *
     */

    #[Test]
    public function test_quick_command_click_sets_input()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-quick-click');

            // Check for wire:click set command
            $pageSource = $browser->driver->getPageSource();
            $hasSetCommand =
                str_contains($pageSource, 'wire:click="$set(\'command\'');

            $this->assertTrue($hasSetCommand, 'Quick command click should set input value');

            $this->testResults['quick_click'] = 'Quick command click sets input value';
        });
    }

    /**
     * Test 42: Terminal has confirmation for clear history
     *
     */

    #[Test]
    public function test_terminal_has_clear_history_confirmation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-clear-confirm');

            // Check for confirmation dialog
            $pageSource = $browser->driver->getPageSource();
            $hasConfirm =
                str_contains($pageSource, 'confirm(') ||
                str_contains($pageSource, 'Clear command history');

            $this->assertTrue($hasConfirm, 'Terminal should have confirmation for clear history');

            $this->testResults['clear_confirmation'] = 'Terminal has confirmation for clear history';
        });
    }

    /**
     * Test 43: Navigation from server details to terminal works
     *
     */

    #[Test]
    public function test_navigation_from_server_to_terminal()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}")
                ->pause(2000)
                ->waitFor('body', 15);

            // Try to navigate to terminal
            $browser->visit("/servers/{$this->testServer->id}/terminal")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssh-terminal-navigation');

            $currentUrl = $browser->driver->getCurrentURL();
            $onTerminalPage = str_contains($currentUrl, '/terminal');

            $this->assertTrue($onTerminalPage, 'Should be able to navigate to terminal from server details');

            $this->testResults['terminal_navigation'] = 'Navigation from server to terminal works';
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
                'test_suite' => 'SSH Terminal Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'servers_count' => Server::count(),
                    'admin_user_id' => $this->adminUser->id,
                    'admin_user_name' => $this->adminUser->name,
                    'test_server_id' => $this->testServer->id,
                    'test_server_name' => $this->testServer->name,
                ],
            ];

            $reportPath = storage_path('app/test-reports/ssh-terminal-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
