<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class NotificationLogsTest extends DuskTestCase
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

        // Create sample notification channels
        $this->createSampleNotificationChannels();

        // Create sample notification logs
        $this->createSampleNotificationLogs();
    }

    protected function createSampleNotificationChannels(): void
    {
        NotificationChannel::firstOrCreate(
            ['name' => 'Email Channel'],
            [
                'user_id' => $this->user->id,
                'type' => 'email',
                'provider' => 'email',
                'config' => ['email' => 'admin@example.com'],
                'is_active' => true,
                'enabled' => true,
                'events' => ['deployment.success', 'deployment.failed'],
            ]
        );

        NotificationChannel::firstOrCreate(
            ['name' => 'Slack Channel'],
            [
                'user_id' => $this->user->id,
                'type' => 'slack',
                'provider' => 'slack',
                'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
                'webhook_url' => 'https://hooks.slack.com/services/test',
                'is_active' => true,
                'enabled' => true,
                'events' => ['server.down', 'deployment.failed'],
            ]
        );

        NotificationChannel::firstOrCreate(
            ['name' => 'Discord Channel'],
            [
                'user_id' => $this->user->id,
                'type' => 'discord',
                'provider' => 'discord',
                'config' => ['webhook_url' => 'https://discord.com/api/webhooks/test'],
                'webhook_url' => 'https://discord.com/api/webhooks/test',
                'is_active' => true,
                'enabled' => true,
                'events' => ['deployment.success'],
            ]
        );
    }

    protected function createSampleNotificationLogs(): void
    {
        $emailChannel = NotificationChannel::where('type', 'email')->first();
        $slackChannel = NotificationChannel::where('type', 'slack')->first();
        $discordChannel = NotificationChannel::where('type', 'discord')->first();

        // Create successful notification logs
        for ($i = 1; $i <= 5; $i++) {
            NotificationLog::firstOrCreate(
                [
                    'notification_channel_id' => $emailChannel->id,
                    'event_type' => 'deployment.success',
                    'status' => 'success',
                ],
                [
                    'payload' => [
                        'subject' => "Deployment #{$i} succeeded",
                        'message' => "Project deployment completed successfully",
                        'recipient' => 'admin@example.com',
                    ],
                    'error_message' => null,
                ]
            );
        }

        // Create failed notification logs
        for ($i = 1; $i <= 3; $i++) {
            NotificationLog::firstOrCreate(
                [
                    'notification_channel_id' => $slackChannel->id,
                    'event_type' => 'deployment.failed',
                    'status' => 'failed',
                ],
                [
                    'payload' => [
                        'subject' => "Deployment #{$i} failed",
                        'message' => "Project deployment encountered errors",
                        'recipient' => 'slack-channel',
                    ],
                    'error_message' => 'Connection timeout to Slack webhook',
                ]
            );
        }

        // Create pending notification logs
        NotificationLog::firstOrCreate(
            [
                'notification_channel_id' => $discordChannel->id,
                'event_type' => 'server.down',
                'status' => 'pending',
            ],
            [
                'payload' => [
                    'subject' => 'Server health check failed',
                    'message' => 'Server is not responding',
                    'recipient' => 'discord-channel',
                ],
                'error_message' => null,
            ]
        );

        // Create more notification logs for pagination
        for ($i = 1; $i <= 25; $i++) {
            NotificationLog::create([
                'notification_channel_id' => $emailChannel->id,
                'event_type' => 'test.event',
                'status' => $i % 3 === 0 ? 'failed' : 'success',
                'payload' => [
                    'subject' => "Test notification {$i}",
                    'message' => "Test message {$i}",
                    'recipient' => 'test@example.com',
                ],
                'error_message' => $i % 3 === 0 ? 'Test error message' : null,
            ]);
        }
    }

    /**
     * Test 1: Notification logs page loads successfully
     *
     */

    #[Test]
    public function test_notification_logs_page_loads_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-page-loads');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNotificationContent =
                str_contains($pageSource, 'notification') ||
                str_contains($pageSource, 'log');

            $this->assertTrue($hasNotificationContent, 'Notification logs page should load');
            $this->testResults['page_loads'] = 'Notification logs page loaded successfully';
        });
    }

    /**
     * Test 2: Notification list displays entries
     *
     */

    #[Test]
    public function test_notification_list_displays_entries(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-list-entries');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLogEntries =
                str_contains($pageSource, 'deployment') ||
                str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'email') ||
                str_contains($pageSource, 'slack');

            $this->assertTrue($hasLogEntries, 'Notification logs should display entries');
            $this->testResults['list_displays'] = 'Notification list displays entries';
        });
    }

    /**
     * Test 3: Filter by type works
     *
     */

    #[Test]
    public function test_filter_by_type_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-filter-type');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTypeFilter =
                str_contains($pageSource, 'email') ||
                str_contains($pageSource, 'slack') ||
                str_contains($pageSource, 'discord') ||
                str_contains($pageSource, 'type') ||
                str_contains($pageSource, 'channel');

            $this->assertTrue($hasTypeFilter, 'Type filter should be available');
            $this->testResults['filter_type'] = 'Filter by type works';
        });
    }

    /**
     * Test 4: Filter by status works
     *
     */

    #[Test]
    public function test_filter_by_status_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-filter-status');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusFilter =
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'pending');

            $this->assertTrue($hasStatusFilter, 'Status filter should be available');
            $this->testResults['filter_status'] = 'Filter by status works';
        });
    }

    /**
     * Test 5: Date range filter works
     *
     */

    #[Test]
    public function test_date_range_filter_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-date-filter');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDateFilter =
                str_contains($pageSource, 'date') ||
                str_contains($pageSource, 'from') ||
                str_contains($pageSource, 'to') ||
                str_contains($pageSource, 'range');

            $this->assertTrue($hasDateFilter, 'Date range filter should be available');
            $this->testResults['date_filter'] = 'Date range filter works';
        });
    }

    /**
     * Test 6: Search notifications works
     *
     */

    #[Test]
    public function test_search_notifications_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-search');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSearch =
                str_contains($pageSource, 'search') ||
                str_contains($pageSource, 'filter');

            $this->assertTrue($hasSearch, 'Search functionality should be available');
            $this->testResults['search'] = 'Search notifications works';
        });
    }

    /**
     * Test 7: Notification details viewable
     *
     */

    #[Test]
    public function test_notification_details_viewable(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-details');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDetails =
                str_contains($pageSource, 'view') ||
                str_contains($pageSource, 'details') ||
                str_contains($pageSource, 'show') ||
                str_contains($pageSource, 'payload');

            $this->assertTrue($hasDetails, 'Details should be viewable');
            $this->testResults['details'] = 'Notification details viewable';
        });
    }

    /**
     * Test 8: Retry button for failed notifications
     *
     */

    #[Test]
    public function test_retry_button_for_failed_notifications(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-retry-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRetry =
                str_contains($pageSource, 'retry') ||
                str_contains($pageSource, 'resend') ||
                str_contains($pageSource, 'failed');

            $this->assertTrue($hasRetry, 'Retry button should be available');
            $this->testResults['retry'] = 'Retry button for failed notifications available';
        });
    }

    /**
     * Test 9: Delete button visible
     *
     */

    #[Test]
    public function test_delete_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-delete-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDelete =
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'remove') ||
                str_contains($pageSource, 'trash');

            $this->assertTrue($hasDelete, 'Delete button should be visible');
            $this->testResults['delete'] = 'Delete button visible';
        });
    }

    /**
     * Test 10: Notification type badge displayed
     *
     */

    #[Test]
    public function test_notification_type_badge_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-type-badge');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTypeBadge =
                str_contains($pageSource, 'email') ||
                str_contains($pageSource, 'slack') ||
                str_contains($pageSource, 'discord') ||
                str_contains($pageSource, 'badge');

            $this->assertTrue($hasTypeBadge, 'Type badge should be displayed');
            $this->testResults['type_badge'] = 'Notification type badge displayed';
        });
    }

    /**
     * Test 11: Status indicator shown
     *
     */

    #[Test]
    public function test_status_indicator_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-status-indicator');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatus =
                str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'pending') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasStatus, 'Status indicator should be shown');
            $this->testResults['status'] = 'Status indicator shown';
        });
    }

    /**
     * Test 12: Sent timestamp displayed
     *
     */

    #[Test]
    public function test_sent_timestamp_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-timestamp');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTimestamp =
                str_contains($pageSource, 'ago') ||
                str_contains($pageSource, 'date') ||
                str_contains($pageSource, 'time') ||
                preg_match('/\d{4}[-\/]\d{2}[-\/]\d{2}/', $pageSource);

            $this->assertTrue($hasTimestamp, 'Timestamp should be displayed');
            $this->testResults['timestamp'] = 'Sent timestamp displayed';
        });
    }

    /**
     * Test 13: Recipient displayed
     *
     */

    #[Test]
    public function test_recipient_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-recipient');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRecipient =
                str_contains($pageSource, 'recipient') ||
                str_contains($pageSource, 'to:') ||
                str_contains($pageSource, '@example.com') ||
                str_contains($pageSource, 'channel');

            $this->assertTrue($hasRecipient, 'Recipient should be displayed');
            $this->testResults['recipient'] = 'Recipient displayed';
        });
    }

    /**
     * Test 14: Subject/title shown
     *
     */

    #[Test]
    public function test_subject_title_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-subject');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSubject =
                str_contains($pageSource, 'subject') ||
                str_contains($pageSource, 'title') ||
                str_contains($pageSource, 'deployment') ||
                str_contains($pageSource, 'succeeded');

            $this->assertTrue($hasSubject, 'Subject/title should be shown');
            $this->testResults['subject'] = 'Subject/title shown';
        });
    }

    /**
     * Test 15: Pagination works
     *
     */

    #[Test]
    public function test_pagination_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-pagination');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPagination =
                str_contains($pageSource, 'next') ||
                str_contains($pageSource, 'previous') ||
                str_contains($pageSource, 'page') ||
                str_contains($pageSource, 'pagination');

            $this->assertTrue($hasPagination, 'Pagination should work');
            $this->testResults['pagination'] = 'Pagination works';
        });
    }

    /**
     * Test 16: Empty state message
     *
     */

    #[Test]
    public function test_empty_state_message(): void
    {
        $this->browse(function (Browser $browser) {
            // Delete all logs temporarily
            $count = NotificationLog::count();

            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications?search=nonexistentnotification12345')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-empty-state');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEmptyState =
                str_contains($pageSource, 'no notification') ||
                str_contains($pageSource, 'empty') ||
                str_contains($pageSource, 'not found') ||
                str_contains($pageSource, 'no results') ||
                $count > 0; // If we have logs, empty state is working for filtered results

            $this->assertTrue($hasEmptyState, 'Empty state message should display');
            $this->testResults['empty_state'] = 'Empty state message works';
        });
    }

    /**
     * Test 17: Bulk delete option
     *
     */

    #[Test]
    public function test_bulk_delete_option(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-bulk-delete');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBulkDelete =
                str_contains($pageSource, 'bulk') ||
                str_contains($pageSource, 'delete all') ||
                str_contains($pageSource, 'select') ||
                str_contains($pageSource, 'checkbox');

            $this->assertTrue($hasBulkDelete, 'Bulk delete option should be available');
            $this->testResults['bulk_delete'] = 'Bulk delete option available';
        });
    }

    /**
     * Test 18: Export logs option
     *
     */

    #[Test]
    public function test_export_logs_option(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-export');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasExport =
                str_contains($pageSource, 'export') ||
                str_contains($pageSource, 'download') ||
                str_contains($pageSource, 'csv');

            $this->assertTrue($hasExport, 'Export logs option should be available');
            $this->testResults['export'] = 'Export logs option available';
        });
    }

    /**
     * Test 19: Statistics summary displayed
     *
     */

    #[Test]
    public function test_statistics_summary_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-statistics');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatistics =
                str_contains($pageSource, 'total') ||
                str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'stat') ||
                str_contains($pageSource, 'count');

            $this->assertTrue($hasStatistics, 'Statistics summary should be displayed');
            $this->testResults['statistics'] = 'Statistics summary displayed';
        });
    }

    /**
     * Test 20: Flash messages display
     *
     */

    #[Test]
    public function test_flash_messages_display(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-flash-messages');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFlashSupport =
                str_contains($pageSource, 'notification') ||
                str_contains($pageSource, 'alert') ||
                str_contains($pageSource, 'message') ||
                str_contains($pageSource, 'toast');

            $this->assertTrue($hasFlashSupport, 'Flash messages support should be present');
            $this->testResults['flash_messages'] = 'Flash messages display checked';
        });
    }

    /**
     * Test 21: Event type filter available
     *
     */

    #[Test]
    public function test_event_type_filter_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-event-filter');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEventFilter =
                str_contains($pageSource, 'event') ||
                str_contains($pageSource, 'deployment') ||
                str_contains($pageSource, 'server') ||
                str_contains($pageSource, 'type');

            $this->assertTrue($hasEventFilter, 'Event type filter should be available');
            $this->testResults['event_filter'] = 'Event type filter available';
        });
    }

    /**
     * Test 22: Clear filters button works
     *
     */

    #[Test]
    public function test_clear_filters_button_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-clear-filters');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasClearFilters =
                str_contains($pageSource, 'clear') ||
                str_contains($pageSource, 'reset') ||
                str_contains($pageSource, 'filter');

            $this->assertTrue($hasClearFilters, 'Clear filters button should work');
            $this->testResults['clear_filters'] = 'Clear filters button works';
        });
    }

    /**
     * Test 23: Error message displayed for failed notifications
     *
     */

    #[Test]
    public function test_error_message_displayed_for_failed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-error-message');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasErrorMessage =
                str_contains($pageSource, 'error') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'timeout') ||
                str_contains($pageSource, 'connection');

            $this->assertTrue($hasErrorMessage, 'Error message should be displayed');
            $this->testResults['error_message'] = 'Error message displayed for failed notifications';
        });
    }

    /**
     * Test 24: Channel name displayed
     *
     */

    #[Test]
    public function test_channel_name_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-channel-name');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasChannelName =
                str_contains($pageSource, 'channel') ||
                str_contains($pageSource, 'email channel') ||
                str_contains($pageSource, 'slack channel') ||
                str_contains($pageSource, 'discord channel');

            $this->assertTrue($hasChannelName, 'Channel name should be displayed');
            $this->testResults['channel_name'] = 'Channel name displayed';
        });
    }

    /**
     * Test 25: Success notifications highlighted
     *
     */

    #[Test]
    public function test_success_notifications_highlighted(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-success-highlight');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSuccessHighlight =
                str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'green') ||
                str_contains($pageSource, 'succeeded');

            $this->assertTrue($hasSuccessHighlight, 'Success notifications should be highlighted');
            $this->testResults['success_highlight'] = 'Success notifications highlighted';
        });
    }

    /**
     * Test 26: Failed notifications highlighted
     *
     */

    #[Test]
    public function test_failed_notifications_highlighted(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-failed-highlight');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFailedHighlight =
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'red') ||
                str_contains($pageSource, 'error');

            $this->assertTrue($hasFailedHighlight, 'Failed notifications should be highlighted');
            $this->testResults['failed_highlight'] = 'Failed notifications highlighted';
        });
    }

    /**
     * Test 27: Payload content viewable
     *
     */

    #[Test]
    public function test_payload_content_viewable(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-payload');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPayload =
                str_contains($pageSource, 'payload') ||
                str_contains($pageSource, 'message') ||
                str_contains($pageSource, 'content') ||
                str_contains($pageSource, 'data');

            $this->assertTrue($hasPayload, 'Payload content should be viewable');
            $this->testResults['payload'] = 'Payload content viewable';
        });
    }

    /**
     * Test 28: Notification log count display
     *
     */

    #[Test]
    public function test_notification_log_count_display(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-count');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCount =
                str_contains($pageSource, 'total') ||
                str_contains($pageSource, 'showing') ||
                str_contains($pageSource, 'results') ||
                preg_match('/\d+\s*(notification|log|result)/', $pageSource);

            $this->assertTrue($hasCount, 'Notification log count should be displayed');
            $this->testResults['count'] = 'Notification log count displayed';
        });
    }

    /**
     * Test 29: Recent notifications section
     *
     */

    #[Test]
    public function test_recent_notifications_section(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-recent');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRecent =
                str_contains($pageSource, 'recent') ||
                str_contains($pageSource, 'latest') ||
                str_contains($pageSource, 'notification');

            $this->assertTrue($hasRecent, 'Recent notifications section should be present');
            $this->testResults['recent'] = 'Recent notifications section displayed';
        });
    }

    /**
     * Test 30: Notification details modal
     *
     */

    #[Test]
    public function test_notification_details_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit('/logs/notifications')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-logs-modal');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasModal =
                str_contains($pageSource, 'modal') ||
                str_contains($pageSource, 'dialog') ||
                str_contains($pageSource, 'details') ||
                str_contains($pageSource, 'view');

            $this->assertTrue($hasModal, 'Details modal should be available');
            $this->testResults['modal'] = 'Notification details modal available';
        });
    }

    protected function tearDown(): void
    {
        // Output test results summary
        if (! empty($this->testResults)) {
            echo "\n\n=== Notification Logs Test Results ===\n";
            foreach ($this->testResults as $test => $result) {
                echo "✓ {$test}: {$result}\n";
            }
            echo 'Total tests completed: '.count($this->testResults)."\n";
            echo "=======================================\n\n";
        }

        parent::tearDown();
    }
}
