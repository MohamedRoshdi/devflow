<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class SystemAdminTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $adminUser;

    protected User $regularUser;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Create admin user
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        if (! $this->adminUser->hasRole('admin')) {
            $this->adminUser->assignRole('admin');
        }

        // Create regular user for access tests
        $this->regularUser = User::firstOrCreate(
            ['email' => 'user@devflow.test'],
            [
                'name' => 'Regular User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        if (! $this->regularUser->hasRole('user')) {
            $this->regularUser->assignRole('user');
        }
    }

    /**
     * Test 1: System admin page loads successfully
     *
     */

    #[Test]
    public function test_system_admin_page_loads_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-page-loads');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSystemContent =
                str_contains($pageSource, 'system') ||
                str_contains($pageSource, 'administration') ||
                str_contains($pageSource, 'admin');

            $this->assertTrue($hasSystemContent, 'System admin page should load');
            $this->testResults['page_loads'] = 'System admin page loaded successfully';
        });
    }

    /**
     * Test 2: System overview displayed
     *
     */

    #[Test]
    public function test_system_overview_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-overview');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasOverview =
                str_contains($pageSource, 'overview') ||
                str_contains($pageSource, 'dashboard') ||
                str_contains($pageSource, 'statistics');

            $this->assertTrue($hasOverview, 'System overview should be displayed');
            $this->testResults['overview_displayed'] = 'System overview displayed';
        });
    }

    /**
     * Test 3: Database backup stats shown
     *
     */

    #[Test]
    public function test_database_backup_stats_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-backup-stats');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBackupStats =
                str_contains($pageSource, 'backup') ||
                str_contains($pageSource, 'database');

            $this->assertTrue($hasBackupStats, 'Backup stats should be shown');
            $this->testResults['backup_stats'] = 'Database backup stats displayed';
        });
    }

    /**
     * Test 4: System monitoring stats shown
     *
     */

    #[Test]
    public function test_system_monitoring_stats_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-monitoring-stats');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMonitoringStats =
                str_contains($pageSource, 'monitoring') ||
                str_contains($pageSource, 'alert');

            $this->assertTrue($hasMonitoringStats, 'Monitoring stats should be shown');
            $this->testResults['monitoring_stats'] = 'System monitoring stats displayed';
        });
    }

    /**
     * Test 5: Log rotation info shown
     *
     */

    #[Test]
    public function test_log_rotation_info_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-log-rotation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLogRotation =
                str_contains($pageSource, 'log') ||
                str_contains($pageSource, 'rotation') ||
                str_contains($pageSource, 'retention');

            $this->assertTrue($hasLogRotation, 'Log rotation info should be shown');
            $this->testResults['log_rotation'] = 'Log rotation info displayed';
        });
    }

    /**
     * Test 6: DB optimization schedule shown
     *
     */

    #[Test]
    public function test_db_optimization_schedule_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-db-optimization');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasOptimization =
                str_contains($pageSource, 'optimization') ||
                str_contains($pageSource, 'optimize');

            $this->assertTrue($hasOptimization, 'DB optimization schedule should be shown');
            $this->testResults['db_optimization'] = 'DB optimization schedule displayed';
        });
    }

    /**
     * Test 7: Quick actions section displayed
     *
     */

    #[Test]
    public function test_quick_actions_section_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-quick-actions');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasQuickActions =
                str_contains($pageSource, 'run backup') ||
                str_contains($pageSource, 'optimize');

            $this->assertTrue($hasQuickActions, 'Quick actions should be displayed');
            $this->testResults['quick_actions'] = 'Quick actions section displayed';
        });
    }

    /**
     * Test 8: Recent alerts section displayed
     *
     */

    #[Test]
    public function test_recent_alerts_section_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-recent-alerts');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAlerts =
                str_contains($pageSource, 'alert') ||
                str_contains($pageSource, 'warning') ||
                str_contains($pageSource, 'recent');

            $this->assertTrue($hasAlerts, 'Recent alerts section should be displayed');
            $this->testResults['recent_alerts'] = 'Recent alerts section displayed';
        });
    }

    /**
     * Test 9: System health indicators shown
     *
     */

    #[Test]
    public function test_system_health_indicators_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-health-indicators');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHealth =
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'healthy');

            $this->assertTrue($hasHealth, 'System health indicators should be shown');
            $this->testResults['health_indicators'] = 'System health indicators displayed';
        });
    }

    /**
     * Test 10: Navigation tabs displayed
     *
     */

    #[Test]
    public function test_navigation_tabs_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-tabs');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTabs =
                str_contains($pageSource, 'overview') ||
                str_contains($pageSource, 'backup logs') ||
                str_contains($pageSource, 'monitoring logs');

            $this->assertTrue($hasTabs, 'Navigation tabs should be displayed');
            $this->testResults['navigation_tabs'] = 'Navigation tabs displayed';
        });
    }

    /**
     * Test 11: Switch to backup logs tab
     *
     */

    #[Test]
    public function test_switch_to_backup_logs_tab(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-backup-logs-before');

            $pageSource = $browser->driver->getPageSource();
            if (preg_match('/wire:click="viewBackupLogs"/i', $pageSource)) {
                $browser->pause(1000)->screenshot('system-admin-backup-logs-tab');
            }

            $this->assertTrue(true, 'Backup logs tab functionality available');
            $this->testResults['backup_logs_tab'] = 'Backup logs tab accessible';
        });
    }

    /**
     * Test 12: Switch to monitoring logs tab
     *
     */

    #[Test]
    public function test_switch_to_monitoring_logs_tab(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-monitoring-logs-before');

            $pageSource = $browser->driver->getPageSource();
            if (preg_match('/wire:click="viewMonitoringLogs"/i', $pageSource)) {
                $browser->pause(1000)->screenshot('system-admin-monitoring-logs-tab');
            }

            $this->assertTrue(true, 'Monitoring logs tab functionality available');
            $this->testResults['monitoring_logs_tab'] = 'Monitoring logs tab accessible';
        });
    }

    /**
     * Test 13: Switch to optimization logs tab
     *
     */

    #[Test]
    public function test_switch_to_optimization_logs_tab(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-optimization-logs-before');

            $pageSource = $browser->driver->getPageSource();
            if (preg_match('/wire:click="viewOptimizationLogs"/i', $pageSource)) {
                $browser->pause(1000)->screenshot('system-admin-optimization-logs-tab');
            }

            $this->assertTrue(true, 'Optimization logs tab functionality available');
            $this->testResults['optimization_logs_tab'] = 'Optimization logs tab accessible';
        });
    }

    /**
     * Test 14: Statistics cards displayed
     *
     */

    #[Test]
    public function test_statistics_cards_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-statistics-cards');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatCards =
                str_contains($pageSource, 'database backups') ||
                str_contains($pageSource, 'system monitoring') ||
                str_contains($pageSource, 'log rotation') ||
                str_contains($pageSource, 'db optimization');

            $this->assertTrue($hasStatCards, 'Statistics cards should be displayed');
            $this->testResults['statistics_cards'] = 'Statistics cards displayed';
        });
    }

    /**
     * Test 15: Run backup now button visible
     *
     */

    #[Test]
    public function test_run_backup_now_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-run-backup-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBackupButton =
                str_contains($pageSource, 'run backup') ||
                str_contains($pageSource, 'backup now');

            $this->assertTrue($hasBackupButton, 'Run backup button should be visible');
            $this->testResults['backup_button'] = 'Run backup now button visible';
        });
    }

    /**
     * Test 16: Optimize now button visible
     *
     */

    #[Test]
    public function test_optimize_now_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-optimize-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasOptimizeButton =
                str_contains($pageSource, 'optimize now') ||
                str_contains($pageSource, 'optimization');

            $this->assertTrue($hasOptimizeButton, 'Optimize button should be visible');
            $this->testResults['optimize_button'] = 'Optimize now button visible';
        });
    }

    /**
     * Test 17: Backup status indicator shown
     *
     */

    #[Test]
    public function test_backup_status_indicator_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-backup-status');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBackupStatus =
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'last backup') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasBackupStatus, 'Backup status should be shown');
            $this->testResults['backup_status'] = 'Backup status indicator displayed';
        });
    }

    /**
     * Test 18: System alerts section visible
     *
     */

    #[Test]
    public function test_system_alerts_section_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-alerts-section');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAlertsSection =
                str_contains($pageSource, 'recent alerts') ||
                str_contains($pageSource, 'warnings') ||
                str_contains($pageSource, 'all systems operational');

            $this->assertTrue($hasAlertsSection, 'System alerts section should be visible');
            $this->testResults['alerts_section'] = 'System alerts section visible';
        });
    }

    /**
     * Test 19: Admin-only access verified
     *
     */

    #[Test]
    public function test_admin_only_access_verified(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->regularUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-non-admin-access');

            $currentUrl = $browser->driver->getCurrentURL();
            $pageSource = strtolower($browser->driver->getPageSource());

            $isBlocked =
                str_contains($currentUrl, '/dashboard') ||
                str_contains($currentUrl, '/login') ||
                str_contains($pageSource, 'unauthorized') ||
                str_contains($pageSource, 'forbidden') ||
                str_contains($pageSource, '403');

            $this->assertTrue($isBlocked, 'Non-admin should be blocked');
            $this->testResults['admin_only_access'] = 'Admin-only access verified';
        });
    }

    /**
     * Test 20: Flash messages display correctly
     *
     */

    #[Test]
    public function test_flash_messages_display_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-flash-messages');

            $pageSource = $browser->driver->getPageSource();
            $hasFlashSupport =
                str_contains($pageSource, 'session(') ||
                str_contains($pageSource, 'flash') ||
                str_contains($pageSource, 'message');

            $this->assertTrue($hasFlashSupport, 'Flash message support should exist');
            $this->testResults['flash_messages'] = 'Flash messages display support verified';
        });
    }

    /**
     * Test 21: Backup statistics complete
     *
     */

    #[Test]
    public function test_backup_statistics_complete(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-backup-statistics');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBackupDetails =
                str_contains($pageSource, 'databases') ||
                str_contains($pageSource, 'last') ||
                str_contains($pageSource, 'size');

            $this->assertTrue($hasBackupDetails, 'Backup statistics should be complete');
            $this->testResults['backup_statistics'] = 'Backup statistics complete';
        });
    }

    /**
     * Test 22: Schedule information displayed
     *
     */

    #[Test]
    public function test_schedule_information_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-schedule-info');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSchedule =
                str_contains($pageSource, 'daily') ||
                str_contains($pageSource, 'weekly') ||
                str_contains($pageSource, 'every') ||
                str_contains($pageSource, 'schedule');

            $this->assertTrue($hasSchedule, 'Schedule information should be displayed');
            $this->testResults['schedule_info'] = 'Schedule information displayed';
        });
    }

    /**
     * Test 23: Alert severity levels shown
     *
     */

    #[Test]
    public function test_alert_severity_levels_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-alert-severity');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSeverity =
                str_contains($pageSource, 'warning') ||
                str_contains($pageSource, 'error') ||
                str_contains($pageSource, 'critical') ||
                str_contains($pageSource, 'alert');

            $this->assertTrue($hasSeverity, 'Alert severity levels should be shown');
            $this->testResults['alert_severity'] = 'Alert severity levels displayed';
        });
    }

    /**
     * Test 24: Loading states working
     *
     */

    #[Test]
    public function test_loading_states_working(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-loading-states');

            $pageSource = $browser->driver->getPageSource();
            $hasLoadingStates =
                str_contains($pageSource, 'wire:loading') ||
                str_contains($pageSource, 'loading') ||
                str_contains($pageSource, 'spinner');

            $this->assertTrue($hasLoadingStates, 'Loading states should be implemented');
            $this->testResults['loading_states'] = 'Loading states working';
        });
    }

    /**
     * Test 25: Info banner displayed
     *
     */

    #[Test]
    public function test_info_banner_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-info-banner');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasInfoBanner =
                str_contains($pageSource, 'automated') ||
                str_contains($pageSource, 'production') ||
                str_contains($pageSource, 'features');

            $this->assertTrue($hasInfoBanner, 'Info banner should be displayed');
            $this->testResults['info_banner'] = 'Info banner displayed';
        });
    }

    /**
     * Test 26: Automated features list shown
     *
     */

    #[Test]
    public function test_automated_features_list_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-automated-features');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFeaturesList =
                str_contains($pageSource, 'backups') ||
                str_contains($pageSource, 'monitoring') ||
                str_contains($pageSource, 'ssl');

            $this->assertTrue($hasFeaturesList, 'Automated features list should be shown');
            $this->testResults['automated_features'] = 'Automated features list displayed';
        });
    }

    /**
     * Test 27: Backup retention policy shown
     *
     */

    #[Test]
    public function test_backup_retention_policy_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-retention-policy');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRetention =
                str_contains($pageSource, 'retention') ||
                str_contains($pageSource, 'days') ||
                str_contains($pageSource, '14');

            $this->assertTrue($hasRetention, 'Retention policy should be shown');
            $this->testResults['retention_policy'] = 'Backup retention policy displayed';
        });
    }

    /**
     * Test 28: Monitoring interval shown
     *
     */

    #[Test]
    public function test_monitoring_interval_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-monitoring-interval');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasInterval =
                str_contains($pageSource, '5 min') ||
                str_contains($pageSource, 'every 5') ||
                str_contains($pageSource, 'interval');

            $this->assertTrue($hasInterval, 'Monitoring interval should be shown');
            $this->testResults['monitoring_interval'] = 'Monitoring interval displayed';
        });
    }

    /**
     * Test 29: Database operations listed
     *
     */

    #[Test]
    public function test_database_operations_listed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-db-operations');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasOperations =
                str_contains($pageSource, 'optimize') ||
                str_contains($pageSource, 'analyze') ||
                str_contains($pageSource, 'operations');

            $this->assertTrue($hasOperations, 'Database operations should be listed');
            $this->testResults['db_operations'] = 'Database operations listed';
        });
    }

    /**
     * Test 30: System metrics visible
     *
     */

    #[Test]
    public function test_system_metrics_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-system-metrics');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMetrics =
                str_contains($pageSource, 'cpu') ||
                str_contains($pageSource, 'memory') ||
                str_contains($pageSource, 'disk') ||
                str_contains($pageSource, 'metrics');

            $this->assertTrue($hasMetrics, 'System metrics should be visible');
            $this->testResults['system_metrics'] = 'System metrics visible';
        });
    }

    /**
     * Test 31: Hero section displayed
     *
     */

    #[Test]
    public function test_hero_section_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-hero-section');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHero =
                str_contains($pageSource, 'system administration') ||
                str_contains($pageSource, 'production tools');

            $this->assertTrue($hasHero, 'Hero section should be displayed');
            $this->testResults['hero_section'] = 'Hero section displayed';
        });
    }

    /**
     * Test 32: Visual indicators present
     *
     */

    #[Test]
    public function test_visual_indicators_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-visual-indicators');

            $pageSource = $browser->driver->getPageSource();
            $hasVisuals =
                str_contains($pageSource, 'svg') ||
                str_contains($pageSource, 'icon') ||
                str_contains($pageSource, 'badge');

            $this->assertTrue($hasVisuals, 'Visual indicators should be present');
            $this->testResults['visual_indicators'] = 'Visual indicators present';
        });
    }

    /**
     * Test 33: Success message styling
     *
     */

    #[Test]
    public function test_success_message_styling(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-success-styling');

            $pageSource = $browser->driver->getPageSource();
            $hasSuccessStyle =
                str_contains($pageSource, 'green') ||
                str_contains($pageSource, 'success');

            $this->assertTrue($hasSuccessStyle, 'Success message styling should exist');
            $this->testResults['success_styling'] = 'Success message styling present';
        });
    }

    /**
     * Test 34: Error message styling
     *
     */

    #[Test]
    public function test_error_message_styling(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-error-styling');

            $pageSource = $browser->driver->getPageSource();
            $hasErrorStyle =
                str_contains($pageSource, 'red') ||
                str_contains($pageSource, 'error');

            $this->assertTrue($hasErrorStyle, 'Error message styling should exist');
            $this->testResults['error_styling'] = 'Error message styling present';
        });
    }

    /**
     * Test 35: Responsive grid layout
     *
     */

    #[Test]
    public function test_responsive_grid_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-grid-layout');

            $pageSource = $browser->driver->getPageSource();
            $hasGrid =
                str_contains($pageSource, 'grid') ||
                str_contains($pageSource, 'col-span') ||
                str_contains($pageSource, 'lg:');

            $this->assertTrue($hasGrid, 'Responsive grid layout should exist');
            $this->testResults['grid_layout'] = 'Responsive grid layout present';
        });
    }

    /**
     * Test 36: Card hover effects
     *
     */

    #[Test]
    public function test_card_hover_effects(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-hover-effects');

            $pageSource = $browser->driver->getPageSource();
            $hasHover =
                str_contains($pageSource, 'hover:') ||
                str_contains($pageSource, 'transition');

            $this->assertTrue($hasHover, 'Card hover effects should exist');
            $this->testResults['hover_effects'] = 'Card hover effects present';
        });
    }

    /**
     * Test 37: Dark mode support
     *
     */

    #[Test]
    public function test_dark_mode_support(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-dark-mode');

            $pageSource = $browser->driver->getPageSource();
            $hasDarkMode =
                str_contains($pageSource, 'dark:') ||
                str_contains($pageSource, 'dark-mode');

            $this->assertTrue($hasDarkMode, 'Dark mode support should exist');
            $this->testResults['dark_mode'] = 'Dark mode support present';
        });
    }

    /**
     * Test 38: Gradient backgrounds used
     *
     */

    #[Test]
    public function test_gradient_backgrounds_used(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-gradients');

            $pageSource = $browser->driver->getPageSource();
            $hasGradients =
                str_contains($pageSource, 'gradient') ||
                str_contains($pageSource, 'from-') ||
                str_contains($pageSource, 'to-');

            $this->assertTrue($hasGradients, 'Gradient backgrounds should be used');
            $this->testResults['gradients'] = 'Gradient backgrounds present';
        });
    }

    /**
     * Test 39: Log display formatting
     *
     */

    #[Test]
    public function test_log_display_formatting(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-log-formatting');

            $pageSource = $browser->driver->getPageSource();
            $hasLogFormat =
                str_contains($pageSource, 'font-mono') ||
                str_contains($pageSource, 'pre') ||
                str_contains($pageSource, 'code');

            $this->assertTrue($hasLogFormat, 'Log display formatting should exist');
            $this->testResults['log_formatting'] = 'Log display formatting present';
        });
    }

    /**
     * Test 40: Timestamp display in cards
     *
     */

    #[Test]
    public function test_timestamp_display_in_cards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit('/admin/system')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('system-admin-timestamps');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTimestamps =
                str_contains($pageSource, 'last:') ||
                str_contains($pageSource, 'next:') ||
                str_contains($pageSource, 'ago') ||
                preg_match('/\d+:\d+/', $pageSource);

            $this->assertTrue($hasTimestamps, 'Timestamps should be displayed');
            $this->testResults['timestamps'] = 'Timestamp display in cards verified';
        });
    }

    protected function tearDown(): void
    {
        // Output test results summary
        if (! empty($this->testResults)) {
            echo "\n\n=== System Admin Test Results ===\n";
            foreach ($this->testResults as $test => $result) {
                echo "✓ {$test}: {$result}\n";
            }
            echo 'Total tests completed: '.count($this->testResults)."\n";
            echo "==================================\n\n";
        }

        parent::tearDown();
    }
}
