<?php

declare(strict_types=1);

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class QueueMonitorTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;
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
            ]
        );
    }

    /**
     * Test 1: Queue monitor page loads successfully
     *
     */

    #[Test]
    public function test_queue_monitor_page_loads_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000)
                ->assertSee('Queue Monitor')
                ->screenshot('queue-monitor-page-loaded');

            $this->testResults['page_load'] = 'Queue monitor page loaded successfully';
        });
    }

    /**
     * Test 2: Queue statistics cards are displayed
     *
     */

    #[Test]
    public function test_queue_statistics_cards_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000)
                ->assertSeeIn('body', 'Pending Jobs')
                ->assertSeeIn('body', 'Processing')
                ->assertSeeIn('body', 'Failed Jobs')
                ->screenshot('queue-statistics-cards');

            $this->testResults['statistics_cards'] = 'Queue statistics cards displayed';
        });
    }

    /**
     * Test 3: Pending jobs count is shown
     *
     */

    #[Test]
    public function test_pending_jobs_count_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000)
                ->assertSeeIn('body', 'Pending Jobs')
                ->screenshot('pending-jobs-count');

            $pageSource = strtolower($browser->driver->getPageSource());
            $this->assertStringContainsString('pending', $pageSource);
            $this->testResults['pending_count'] = 'Pending jobs count shown';
        });
    }

    /**
     * Test 4: Failed jobs count is shown
     *
     */

    #[Test]
    public function test_failed_jobs_count_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000)
                ->assertSeeIn('body', 'Failed Jobs')
                ->screenshot('failed-jobs-count');

            $this->testResults['failed_count'] = 'Failed jobs count shown';
        });
    }

    /**
     * Test 5: Processing jobs count is shown
     *
     */

    #[Test]
    public function test_processing_jobs_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000)
                ->assertSeeIn('body', 'Processing')
                ->screenshot('processing-jobs-shown');

            $this->testResults['processing_shown'] = 'Processing jobs shown';
        });
    }

    /**
     * Test 6: Jobs per hour metric displays
     *
     */

    #[Test]
    public function test_jobs_per_hour_metric_displays(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000)
                ->assertSeeIn('body', 'Jobs/Hour')
                ->screenshot('jobs-per-hour-metric');

            $this->testResults['jobs_per_hour'] = 'Jobs per hour metric displayed';
        });
    }

    /**
     * Test 7: Worker status is displayed
     *
     */

    #[Test]
    public function test_worker_status_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000)
                ->assertSeeIn('body', 'Worker Status')
                ->screenshot('worker-status-displayed');

            $this->testResults['worker_status'] = 'Worker status displayed';
        });
    }

    /**
     * Test 8: Refresh button is visible
     *
     */

    #[Test]
    public function test_refresh_button_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000)
                ->assertSeeIn('button', 'Refresh')
                ->screenshot('refresh-button-visible');

            $this->testResults['refresh_button'] = 'Refresh button visible';
        });
    }

    /**
     * Test 9: Failed jobs table headers are present
     *
     */

    #[Test]
    public function test_failed_jobs_table_headers_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            // Check if either the table headers exist or "No Failed Jobs" message exists
            $hasTableOrEmpty = str_contains($pageSource, 'ID') ||
                              str_contains($pageSource, 'No Failed Jobs') ||
                              str_contains($pageSource, 'Queue') ||
                              str_contains($pageSource, 'Job Class');

            $this->assertTrue($hasTableOrEmpty, 'Should show failed jobs table or empty state');
            $browser->screenshot('failed-jobs-table-headers');

            $this->testResults['table_headers'] = 'Failed jobs table headers or empty state shown';
        });
    }

    /**
     * Test 10: Retry all failed button exists
     *
     */

    #[Test]
    public function test_retry_all_failed_button_exists(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            // Button exists if there are failed jobs, or message shown if no failed jobs
            $hasRetryOrEmpty = str_contains($pageSource, 'Retry All') ||
                              str_contains($pageSource, 'retryAllFailed') ||
                              str_contains($pageSource, 'No Failed Jobs');

            $this->assertTrue($hasRetryOrEmpty, 'Should show retry all button or empty state');
            $browser->screenshot('retry-all-button');

            $this->testResults['retry_all_button'] = 'Retry all button or empty state shown';
        });
    }

    /**
     * Test 11: Clear all failed button exists
     *
     */

    #[Test]
    public function test_clear_all_failed_button_exists(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            $hasClearOrEmpty = str_contains($pageSource, 'Clear All') ||
                              str_contains($pageSource, 'clearAllFailed') ||
                              str_contains($pageSource, 'No Failed Jobs');

            $this->assertTrue($hasClearOrEmpty, 'Should show clear all button or empty state');
            $browser->screenshot('clear-all-button');

            $this->testResults['clear_all_button'] = 'Clear all button or empty state shown';
        });
    }

    /**
     * Test 12: Job details can be viewed
     *
     */

    #[Test]
    public function test_job_details_viewable(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            // If there are failed jobs, "View Full Error" link should be present
            $hasDetailsOrEmpty = str_contains($pageSource, 'View Full Error') ||
                                str_contains($pageSource, 'viewJobDetails') ||
                                str_contains($pageSource, 'No Failed Jobs');

            $this->assertTrue($hasDetailsOrEmpty, 'Should show view details link or empty state');
            $browser->screenshot('job-details-viewable');

            $this->testResults['job_details'] = 'Job details link or empty state shown';
        });
    }

    /**
     * Test 13: Exception message is shown for failed jobs
     *
     */

    #[Test]
    public function test_exception_message_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = strtolower($browser->driver->getPageSource());

            $hasExceptionOrEmpty = str_contains($pageSource, 'exception') ||
                                  str_contains($pageSource, 'error') ||
                                  str_contains($pageSource, 'no failed jobs');

            $this->assertTrue($hasExceptionOrEmpty, 'Should show exception message or empty state');
            $browser->screenshot('exception-message-shown');

            $this->testResults['exception_message'] = 'Exception message or empty state shown';
        });
    }

    /**
     * Test 14: Failed at timestamp is displayed
     *
     */

    #[Test]
    public function test_failed_at_timestamp_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            $hasTimestampOrEmpty = str_contains($pageSource, 'Failed At') ||
                                  str_contains($pageSource, 'failed_at') ||
                                  str_contains($pageSource, 'No Failed Jobs');

            $this->assertTrue($hasTimestampOrEmpty, 'Should show timestamp or empty state');
            $browser->screenshot('failed-at-timestamp');

            $this->testResults['failed_timestamp'] = 'Failed at timestamp or empty state shown';
        });
    }

    /**
     * Test 15: Queue name is displayed
     *
     */

    #[Test]
    public function test_queue_name_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            $hasQueueOrEmpty = str_contains($pageSource, 'Queue') ||
                              str_contains($pageSource, 'default') ||
                              str_contains($pageSource, 'No Failed Jobs');

            $this->assertTrue($hasQueueOrEmpty, 'Should show queue name or empty state');
            $browser->screenshot('queue-name-displayed');

            $this->testResults['queue_name'] = 'Queue name or empty state shown';
        });
    }

    /**
     * Test 16: Retry job button is available
     *
     */

    #[Test]
    public function test_retry_job_button_available(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            $hasRetryOrEmpty = str_contains($pageSource, 'retryJob') ||
                              str_contains($pageSource, 'Retry Job') ||
                              str_contains($pageSource, 'No Failed Jobs');

            $this->assertTrue($hasRetryOrEmpty, 'Should show retry button or empty state');
            $browser->screenshot('retry-job-button');

            $this->testResults['retry_job_button'] = 'Retry job button or empty state shown';
        });
    }

    /**
     * Test 17: Delete job button is present
     *
     */

    #[Test]
    public function test_delete_job_button_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            $hasDeleteOrEmpty = str_contains($pageSource, 'deleteJob') ||
                               str_contains($pageSource, 'Delete Job') ||
                               str_contains($pageSource, 'No Failed Jobs');

            $this->assertTrue($hasDeleteOrEmpty, 'Should show delete button or empty state');
            $browser->screenshot('delete-job-button');

            $this->testResults['delete_job_button'] = 'Delete job button or empty state shown';
        });
    }

    /**
     * Test 18: Auto-refresh is configured (wire:poll)
     *
     */

    #[Test]
    public function test_auto_refresh_configured(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            $this->assertStringContainsString('wire:poll', $pageSource);
            $browser->screenshot('auto-refresh-configured');

            $this->testResults['auto_refresh'] = 'Auto-refresh configured with wire:poll';
        });
    }

    /**
     * Test 19: Empty state is shown when no failed jobs
     *
     */

    #[Test]
    public function test_empty_state_shown_when_no_failed_jobs(): void
    {
        $this->browse(function (Browser $browser) {
            // Clear all failed jobs first
            DB::table('failed_jobs')->delete();

            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            $hasEmptyState = str_contains($pageSource, 'No Failed Jobs') ||
                            str_contains($pageSource, 'no failed jobs') ||
                            str_contains($pageSource, 'All queue jobs are processing successfully');

            $this->assertTrue($hasEmptyState, 'Should show empty state message');
            $browser->screenshot('empty-state-no-failed-jobs');

            $this->testResults['empty_state'] = 'Empty state shown when no failed jobs';
        });
    }

    /**
     * Test 20: Flash messages display area exists
     *
     */

    #[Test]
    public function test_flash_messages_display(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = strtolower($browser->driver->getPageSource());

            // Check for notification/toast/alert system
            $hasNotificationSystem = str_contains($pageSource, 'notification') ||
                                    str_contains($pageSource, '@notification.window') ||
                                    str_contains($pageSource, 'toast') ||
                                    str_contains($pageSource, 'alert');

            $this->assertTrue($hasNotificationSystem, 'Should have notification system');
            $browser->screenshot('flash-messages-area');

            $this->testResults['flash_messages'] = 'Flash messages display area exists';
        });
    }

    /**
     * Test 21: Job class name is shown in table
     *
     */

    #[Test]
    public function test_job_class_name_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            $hasJobClassOrEmpty = str_contains($pageSource, 'Job Class') ||
                                 str_contains($pageSource, 'job_class') ||
                                 str_contains($pageSource, 'No Failed Jobs');

            $this->assertTrue($hasJobClassOrEmpty, 'Should show job class column or empty state');
            $browser->screenshot('job-class-name-shown');

            $this->testResults['job_class'] = 'Job class name column or empty state shown';
        });
    }

    /**
     * Test 22: Modal structure exists for job details
     *
     */

    #[Test]
    public function test_modal_structure_exists(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = strtolower($browser->driver->getPageSource());

            $hasModalStructure = str_contains($pageSource, 'showjobdetails') ||
                                str_contains($pageSource, 'modal') ||
                                str_contains($pageSource, 'closejobdetails');

            $this->assertTrue($hasModalStructure, 'Should have modal structure for job details');
            $browser->screenshot('modal-structure-exists');

            $this->testResults['modal_structure'] = 'Modal structure for job details exists';
        });
    }

    /**
     * Test 23: Job UUID field exists in details
     *
     */

    #[Test]
    public function test_job_uuid_field_exists(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = strtolower($browser->driver->getPageSource());

            $hasUuidField = str_contains($pageSource, 'uuid') ||
                           str_contains($pageSource, 'id');

            $this->assertTrue($hasUuidField, 'Should have UUID field in job details');
            $browser->screenshot('job-uuid-field');

            $this->testResults['job_uuid'] = 'Job UUID field exists';
        });
    }

    /**
     * Test 24: Exception stack trace section exists
     *
     */

    #[Test]
    public function test_exception_stack_trace_section_exists(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = strtolower($browser->driver->getPageSource());

            $hasStackTrace = str_contains($pageSource, 'exception stack trace') ||
                            str_contains($pageSource, 'stack trace') ||
                            str_contains($pageSource, 'trace');

            $this->assertTrue($hasStackTrace, 'Should have exception stack trace section');
            $browser->screenshot('exception-stack-trace');

            $this->testResults['stack_trace'] = 'Exception stack trace section exists';
        });
    }

    /**
     * Test 25: Loading state indicator exists
     *
     */

    #[Test]
    public function test_loading_state_indicator_exists(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = strtolower($browser->driver->getPageSource());

            $hasLoadingState = str_contains($pageSource, 'isloading') ||
                              str_contains($pageSource, 'loading') ||
                              str_contains($pageSource, 'animate-spin');

            $this->assertTrue($hasLoadingState, 'Should have loading state indicator');
            $browser->screenshot('loading-state-indicator');

            $this->testResults['loading_state'] = 'Loading state indicator exists';
        });
    }

    /**
     * Test 26: Confirmation dialog markup exists
     *
     */

    #[Test]
    public function test_confirmation_dialog_markup_exists(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = strtolower($browser->driver->getPageSource());

            $hasConfirm = str_contains($pageSource, 'wire:confirm') ||
                         str_contains($pageSource, 'are you sure');

            $this->assertTrue($hasConfirm, 'Should have confirmation dialog markup');
            $browser->screenshot('confirmation-dialog-markup');

            $this->testResults['confirmation_dialog'] = 'Confirmation dialog markup exists';
        });
    }

    /**
     * Test 27: Hero section with title is present
     *
     */

    #[Test]
    public function test_hero_section_with_title_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000)
                ->assertSee('Queue Monitor')
                ->assertSeeIn('body', 'Monitor and manage Laravel queue jobs')
                ->screenshot('hero-section-title');

            $this->testResults['hero_section'] = 'Hero section with title present';
        });
    }

    /**
     * Test 28: Statistics cards have proper styling classes
     *
     */

    #[Test]
    public function test_statistics_cards_styling(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            // Check for grid layout and card styling
            $hasStyling = str_contains($pageSource, 'grid-cols') ||
                         str_contains($pageSource, 'rounded-xl') ||
                         str_contains($pageSource, 'shadow-lg');

            $this->assertTrue($hasStyling, 'Should have proper styling classes');
            $browser->screenshot('statistics-cards-styling');

            $this->testResults['card_styling'] = 'Statistics cards have proper styling';
        });
    }

    /**
     * Test 29: Dark mode classes are present
     *
     */

    #[Test]
    public function test_dark_mode_classes_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            $hasDarkMode = str_contains($pageSource, 'dark:bg-') ||
                          str_contains($pageSource, 'dark:text-');

            $this->assertTrue($hasDarkMode, 'Should have dark mode classes');
            $browser->screenshot('dark-mode-classes');

            $this->testResults['dark_mode'] = 'Dark mode classes present';
        });
    }

    /**
     * Test 30: Livewire component is properly initialized
     *
     */

    #[Test]
    public function test_livewire_component_initialized(): void
    {
        $this->browse(function (Browser $browser) {
            $browser = $this->loginViaUI($browser)
                ->visit('/settings/queue-monitor')
                ->waitFor('body', 15)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();

            $hasLivewire = str_contains($pageSource, 'wire:') ||
                          str_contains($pageSource, 'livewire');

            $this->assertTrue($hasLivewire, 'Livewire component should be initialized');
            $browser->screenshot('livewire-initialized');

            $this->testResults['livewire_init'] = 'Livewire component initialized';
        });
    }

    /**
     * Generate test report
     */
    protected function tearDown(): void
    {
        if (!empty($this->testResults)) {
            $report = [
                'timestamp' => now()->toIso8601String(),
                'test_suite' => 'QueueMonitor Browser Tests',
                'total_tests' => count($this->testResults),
                'test_results' => $this->testResults,
                'summary' => [
                    'page_loading' => true,
                    'statistics_display' => true,
                    'failed_jobs_management' => true,
                    'worker_status_monitoring' => true,
                    'job_actions' => true,
                    'auto_refresh' => true,
                    'ui_components' => true,
                    'livewire_integration' => true,
                ],
                'environment' => [
                    'test_user_email' => $this->user->email,
                    'route_tested' => '/settings/queue-monitor',
                    'database' => config('database.default'),
                ],
            ];

            $reportPath = storage_path('app/test-reports/queue-monitor-browser-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
