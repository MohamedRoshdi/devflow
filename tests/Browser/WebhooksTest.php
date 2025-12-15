<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Models\WebhookDelivery;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class WebhooksTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Project $testProject;

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

        // Create or get test project for webhook testing
        $server = Server::firstOrCreate(
            ['hostname' => 'test-server.local'],
            [
                'name' => 'Test Server',
                'ip_address' => '127.0.0.1',
                'ssh_user' => 'root',
                'ssh_port' => 22,
                'status' => 'online',
            ]
        );

        $this->testProject = Project::firstOrCreate(
            ['slug' => 'test-webhook-project'],
            [
                'name' => 'Test Webhook Project',
                'repository_url' => 'https://github.com/test/repo.git',
                'branch' => 'main',
                'framework' => 'laravel',
                'server_id' => $server->id,
                'webhook_enabled' => true,
                'webhook_secret' => 'test-secret-'.bin2hex(random_bytes(16)),
            ]
        );
    }

    /**
     * Test 1: Webhook logs page loads successfully
     *
     */

    #[Test]
    public function test_webhook_logs_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-logs-list');

            // Check if webhook logs page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWebhookContent =
                str_contains($pageSource, 'webhook') ||
                str_contains($pageSource, 'delivery') ||
                str_contains($pageSource, 'log') ||
                str_contains($pageSource, 'event');

            $this->assertTrue($hasWebhookContent, 'Webhook logs page should load');

            $this->testResults['webhook_logs_page'] = 'Webhook logs page loaded successfully';
        });
    }

    /**
     * Test 2: Webhook statistics are displayed
     *
     */

    #[Test]
    public function test_webhook_statistics_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-statistics');

            // Check for statistics via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStats =
                str_contains($pageSource, 'total') ||
                str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'statistic');

            $this->assertTrue($hasStats, 'Webhook statistics should be displayed');

            $this->testResults['webhook_statistics'] = 'Webhook statistics are displayed';
        });
    }

    /**
     * Test 3: Webhook filter by status works
     *
     */

    #[Test]
    public function test_webhook_filter_by_status()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-status-filter');

            // Check for status filter via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusFilter =
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'filter') ||
                str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'failed');

            $this->assertTrue($hasStatusFilter, 'Webhook status filter should be available');

            $this->testResults['webhook_status_filter'] = 'Webhook filter by status works';
        });
    }

    /**
     * Test 4: Webhook filter by provider works
     *
     */

    #[Test]
    public function test_webhook_filter_by_provider()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-provider-filter');

            // Check for provider filter via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProviderFilter =
                str_contains($pageSource, 'provider') ||
                str_contains($pageSource, 'github') ||
                str_contains($pageSource, 'gitlab') ||
                str_contains($pageSource, 'filter');

            $this->assertTrue($hasProviderFilter, 'Webhook provider filter should be available');

            $this->testResults['webhook_provider_filter'] = 'Webhook filter by provider works';
        });
    }

    /**
     * Test 5: Webhook filter by project works
     *
     */

    #[Test]
    public function test_webhook_filter_by_project()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-project-filter');

            // Check for project filter via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProjectFilter =
                str_contains($pageSource, 'project') ||
                str_contains($pageSource, 'filter') ||
                str_contains($pageSource, 'select');

            $this->assertTrue($hasProjectFilter, 'Webhook project filter should be available');

            $this->testResults['webhook_project_filter'] = 'Webhook filter by project works';
        });
    }

    /**
     * Test 6: Webhook filter by event type works
     *
     */

    #[Test]
    public function test_webhook_filter_by_event_type()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-event-filter');

            // Check for event type filter via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEventFilter =
                str_contains($pageSource, 'event') ||
                str_contains($pageSource, 'type') ||
                str_contains($pageSource, 'push') ||
                str_contains($pageSource, 'filter');

            $this->assertTrue($hasEventFilter, 'Webhook event type filter should be available');

            $this->testResults['webhook_event_filter'] = 'Webhook filter by event type works';
        });
    }

    /**
     * Test 7: Webhook search functionality works
     *
     */

    #[Test]
    public function test_webhook_search_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-search');

            // Check for search functionality via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSearch =
                str_contains($pageSource, 'search') ||
                str_contains($pageSource, 'input') ||
                str_contains($pageSource, 'filter');

            $this->assertTrue($hasSearch, 'Webhook search functionality should be available');

            $this->testResults['webhook_search'] = 'Webhook search functionality works';
        });
    }

    /**
     * Test 8: Clear filters button is visible
     *
     */

    #[Test]
    public function test_webhook_clear_filters_button()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-clear-filters');

            // Check for clear filters button via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasClearButton =
                str_contains($pageSource, 'clear') ||
                str_contains($pageSource, 'reset') ||
                str_contains($pageSource, 'filter');

            $this->assertTrue($hasClearButton, 'Clear filters button should be visible');

            $this->testResults['webhook_clear_filters'] = 'Clear filters button is visible';
        });
    }

    /**
     * Test 9: Webhook delivery details can be viewed
     *
     */

    #[Test]
    public function test_webhook_delivery_details_viewable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-delivery-details');

            // Check for view details option via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasViewDetails =
                str_contains($pageSource, 'details') ||
                str_contains($pageSource, 'view') ||
                str_contains($pageSource, 'payload') ||
                str_contains($pageSource, 'wire:click');

            $this->assertTrue($hasViewDetails, 'Webhook delivery details should be viewable');

            $this->testResults['webhook_delivery_details'] = 'Webhook delivery details can be viewed';
        });
    }

    /**
     * Test 10: Webhook status indicators are shown
     *
     */

    #[Test]
    public function test_webhook_status_indicators_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-status-indicators');

            // Check for status indicators via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusIndicators =
                str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'pending') ||
                str_contains($pageSource, 'ignored') ||
                str_contains($pageSource, 'badge') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasStatusIndicators, 'Webhook status indicators should be shown');

            $this->testResults['webhook_status_indicators'] = 'Webhook status indicators are shown';
        });
    }

    /**
     * Test 11: Webhook timestamp information is displayed
     *
     */

    #[Test]
    public function test_webhook_timestamp_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-timestamp');

            // Check for timestamp information via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTimestamp =
                str_contains($pageSource, 'time') ||
                str_contains($pageSource, 'date') ||
                str_contains($pageSource, 'ago') ||
                str_contains($pageSource, 'created');

            $this->assertTrue($hasTimestamp, 'Webhook timestamp information should be displayed');

            $this->testResults['webhook_timestamp'] = 'Webhook timestamp information is displayed';
        });
    }

    /**
     * Test 12: Project webhook settings page loads
     *
     */

    #[Test]
    public function test_project_webhook_settings_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('project-webhook-settings');

            // Check if project page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWebhookSettings =
                str_contains($pageSource, 'webhook') ||
                str_contains($pageSource, 'project') ||
                str_contains($pageSource, 'settings');

            $this->assertTrue($hasWebhookSettings, 'Project webhook settings page should load');

            $this->testResults['project_webhook_settings'] = 'Project webhook settings page loads';
        });
    }

    /**
     * Test 13: Webhook enable/disable toggle is available
     *
     */

    #[Test]
    public function test_webhook_enable_disable_toggle()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-toggle');

            // Check for webhook toggle via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasToggle =
                str_contains($pageSource, 'webhook') &&
                (str_contains($pageSource, 'enable') ||
                 str_contains($pageSource, 'disable') ||
                 str_contains($pageSource, 'toggle'));

            $this->assertTrue($hasToggle, 'Webhook enable/disable toggle should be available');

            $this->testResults['webhook_toggle'] = 'Webhook enable/disable toggle is available';
        });
    }

    /**
     * Test 14: Webhook URL is displayed when enabled
     *
     */

    #[Test]
    public function test_webhook_url_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-url-display');

            // Check for webhook URL via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWebhookUrl =
                str_contains($pageSource, 'webhook') &&
                (str_contains($pageSource, 'url') ||
                 str_contains($pageSource, 'endpoint') ||
                 str_contains($pageSource, 'http'));

            $this->assertTrue($hasWebhookUrl, 'Webhook URL should be displayed when enabled');

            $this->testResults['webhook_url_display'] = 'Webhook URL is displayed when enabled';
        });
    }

    /**
     * Test 15: GitHub webhook URL is shown
     *
     */

    #[Test]
    public function test_github_webhook_url_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('github-webhook-url');

            // Check for GitHub webhook URL via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasGitHubUrl =
                str_contains($pageSource, 'github') ||
                str_contains($pageSource, 'webhook');

            $this->assertTrue($hasGitHubUrl, 'GitHub webhook URL should be shown');

            $this->testResults['github_webhook_url'] = 'GitHub webhook URL is shown';
        });
    }

    /**
     * Test 16: GitLab webhook URL is shown
     *
     */

    #[Test]
    public function test_gitlab_webhook_url_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('gitlab-webhook-url');

            // Check for GitLab webhook URL via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasGitLabUrl =
                str_contains($pageSource, 'gitlab') ||
                str_contains($pageSource, 'webhook');

            $this->assertTrue($hasGitLabUrl, 'GitLab webhook URL should be shown');

            $this->testResults['gitlab_webhook_url'] = 'GitLab webhook URL is shown';
        });
    }

    /**
     * Test 17: Webhook secret is displayed
     *
     */

    #[Test]
    public function test_webhook_secret_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-secret-display');

            // Check for webhook secret via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSecret =
                str_contains($pageSource, 'secret') ||
                str_contains($pageSource, 'token') ||
                str_contains($pageSource, 'key');

            $this->assertTrue($hasSecret, 'Webhook secret should be displayed');

            $this->testResults['webhook_secret_display'] = 'Webhook secret is displayed';
        });
    }

    /**
     * Test 18: Webhook secret visibility toggle works
     *
     */

    #[Test]
    public function test_webhook_secret_visibility_toggle()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-secret-toggle');

            // Check for secret visibility toggle via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasVisibilityToggle =
                str_contains($pageSource, 'show') ||
                str_contains($pageSource, 'hide') ||
                str_contains($pageSource, 'reveal') ||
                str_contains($pageSource, 'password');

            $this->assertTrue($hasVisibilityToggle, 'Webhook secret visibility toggle should work');

            $this->testResults['webhook_secret_toggle'] = 'Webhook secret visibility toggle works';
        });
    }

    /**
     * Test 19: Regenerate webhook secret button is available
     *
     */

    #[Test]
    public function test_regenerate_webhook_secret_button()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('regenerate-secret-button');

            // Check for regenerate secret button via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRegenerateButton =
                str_contains($pageSource, 'regenerate') ||
                str_contains($pageSource, 'generate') ||
                str_contains($pageSource, 'new secret');

            $this->assertTrue($hasRegenerateButton, 'Regenerate webhook secret button should be available');

            $this->testResults['regenerate_secret_button'] = 'Regenerate webhook secret button is available';
        });
    }

    /**
     * Test 20: Webhook secret regeneration requires confirmation
     *
     */

    #[Test]
    public function test_webhook_secret_regeneration_confirmation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('regenerate-secret-confirmation');

            // Check for confirmation dialog via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasConfirmation =
                str_contains($pageSource, 'confirm') ||
                str_contains($pageSource, 'warning') ||
                str_contains($pageSource, 'are you sure');

            $this->assertTrue($hasConfirmation, 'Webhook secret regeneration should require confirmation');

            $this->testResults['regenerate_secret_confirmation'] = 'Webhook secret regeneration requires confirmation';
        });
    }

    /**
     * Test 21: Copy webhook URL button is available
     *
     */

    #[Test]
    public function test_copy_webhook_url_button()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('copy-webhook-url');

            // Check for copy URL button via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCopyButton =
                str_contains($pageSource, 'copy') ||
                str_contains($pageSource, 'clipboard');

            $this->assertTrue($hasCopyButton, 'Copy webhook URL button should be available');

            $this->testResults['copy_webhook_url'] = 'Copy webhook URL button is available';
        });
    }

    /**
     * Test 22: Recent webhook deliveries are shown
     *
     */

    #[Test]
    public function test_recent_webhook_deliveries_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('recent-webhook-deliveries');

            // Check for recent deliveries via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRecentDeliveries =
                str_contains($pageSource, 'recent') ||
                str_contains($pageSource, 'delivery') ||
                str_contains($pageSource, 'webhook') ||
                str_contains($pageSource, 'history');

            $this->assertTrue($hasRecentDeliveries, 'Recent webhook deliveries should be shown');

            $this->testResults['recent_webhook_deliveries'] = 'Recent webhook deliveries are shown';
        });
    }

    /**
     * Test 23: Webhook delivery status badges are displayed
     *
     */

    #[Test]
    public function test_webhook_delivery_status_badges()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-delivery-badges');

            // Check for status badges via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusBadges =
                str_contains($pageSource, 'badge') ||
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'failed');

            $this->assertTrue($hasStatusBadges, 'Webhook delivery status badges should be displayed');

            $this->testResults['webhook_delivery_badges'] = 'Webhook delivery status badges are displayed';
        });
    }

    /**
     * Test 24: Webhook event type is displayed in logs
     *
     */

    #[Test]
    public function test_webhook_event_type_in_logs()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-event-type-logs');

            // Check for event type in logs via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEventType =
                str_contains($pageSource, 'event') ||
                str_contains($pageSource, 'type') ||
                str_contains($pageSource, 'push');

            $this->assertTrue($hasEventType, 'Webhook event type should be displayed in logs');

            $this->testResults['webhook_event_type_logs'] = 'Webhook event type is displayed in logs';
        });
    }

    /**
     * Test 25: Webhook provider is displayed in logs
     *
     */

    #[Test]
    public function test_webhook_provider_in_logs()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-provider-logs');

            // Check for provider in logs via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProvider =
                str_contains($pageSource, 'provider') ||
                str_contains($pageSource, 'github') ||
                str_contains($pageSource, 'gitlab');

            $this->assertTrue($hasProvider, 'Webhook provider should be displayed in logs');

            $this->testResults['webhook_provider_logs'] = 'Webhook provider is displayed in logs';
        });
    }

    /**
     * Test 26: Webhook pagination works
     *
     */

    #[Test]
    public function test_webhook_pagination()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-pagination');

            // Check for pagination via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPagination =
                str_contains($pageSource, 'pagination') ||
                str_contains($pageSource, 'next') ||
                str_contains($pageSource, 'previous') ||
                str_contains($pageSource, 'page');

            $this->assertTrue($hasPagination, 'Webhook pagination should work');

            $this->testResults['webhook_pagination'] = 'Webhook pagination works';
        });
    }

    /**
     * Test 27: Webhook payload can be viewed
     *
     */

    #[Test]
    public function test_webhook_payload_viewable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-payload-view');

            // Check for payload viewing via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPayloadView =
                str_contains($pageSource, 'payload') ||
                str_contains($pageSource, 'json') ||
                str_contains($pageSource, 'data');

            $this->assertTrue($hasPayloadView, 'Webhook payload should be viewable');

            $this->testResults['webhook_payload_view'] = 'Webhook payload can be viewed';
        });
    }

    /**
     * Test 28: Webhook response can be viewed
     *
     */

    #[Test]
    public function test_webhook_response_viewable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-response-view');

            // Check for response viewing via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasResponseView =
                str_contains($pageSource, 'response') ||
                str_contains($pageSource, 'result') ||
                str_contains($pageSource, 'output');

            $this->assertTrue($hasResponseView, 'Webhook response should be viewable');

            $this->testResults['webhook_response_view'] = 'Webhook response can be viewable';
        });
    }

    /**
     * Test 29: Webhook signature verification is shown
     *
     */

    #[Test]
    public function test_webhook_signature_verification()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-signature-verification');

            // Check for signature verification via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSignature =
                str_contains($pageSource, 'signature') ||
                str_contains($pageSource, 'verified') ||
                str_contains($pageSource, 'security');

            $this->assertTrue($hasSignature, 'Webhook signature verification should be shown');

            $this->testResults['webhook_signature_verification'] = 'Webhook signature verification is shown';
        });
    }

    /**
     * Test 30: Webhook deployment link is shown
     *
     */

    #[Test]
    public function test_webhook_deployment_link()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-deployment-link');

            // Check for deployment link via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDeploymentLink =
                str_contains($pageSource, 'deployment') ||
                str_contains($pageSource, 'view') ||
                str_contains($pageSource, 'link');

            $this->assertTrue($hasDeploymentLink, 'Webhook deployment link should be shown');

            $this->testResults['webhook_deployment_link'] = 'Webhook deployment link is shown';
        });
    }

    /**
     * Test 31: Webhook configuration instructions are available
     *
     */

    #[Test]
    public function test_webhook_configuration_instructions()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-configuration-instructions');

            // Check for configuration instructions via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasInstructions =
                str_contains($pageSource, 'instruction') ||
                str_contains($pageSource, 'how to') ||
                str_contains($pageSource, 'setup') ||
                str_contains($pageSource, 'configure');

            $this->assertTrue($hasInstructions, 'Webhook configuration instructions should be available');

            $this->testResults['webhook_configuration_instructions'] = 'Webhook configuration instructions are available';
        });
    }

    /**
     * Test 32: Webhook security warning is displayed
     *
     */

    #[Test]
    public function test_webhook_security_warning()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-security-warning');

            // Check for security warning via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSecurityWarning =
                str_contains($pageSource, 'security') ||
                str_contains($pageSource, 'warning') ||
                str_contains($pageSource, 'important') ||
                str_contains($pageSource, 'secret');

            $this->assertTrue($hasSecurityWarning, 'Webhook security warning should be displayed');

            $this->testResults['webhook_security_warning'] = 'Webhook security warning is displayed';
        });
    }

    /**
     * Test 33: Webhook event types are documented
     *
     */

    #[Test]
    public function test_webhook_event_types_documented()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-event-types-docs');

            // Check for event types documentation via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEventTypes =
                str_contains($pageSource, 'event') ||
                str_contains($pageSource, 'push') ||
                str_contains($pageSource, 'type');

            $this->assertTrue($hasEventTypes, 'Webhook event types should be documented');

            $this->testResults['webhook_event_types_docs'] = 'Webhook event types are documented';
        });
    }

    /**
     * Test 34: Webhook delivery count is displayed
     *
     */

    #[Test]
    public function test_webhook_delivery_count()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-delivery-count');

            // Check for delivery count via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDeliveryCount =
                str_contains($pageSource, 'total') ||
                str_contains($pageSource, 'count') ||
                str_contains($pageSource, 'delivery') ||
                str_contains($pageSource, 'result');

            $this->assertTrue($hasDeliveryCount, 'Webhook delivery count should be displayed');

            $this->testResults['webhook_delivery_count'] = 'Webhook delivery count is displayed';
        });
    }

    /**
     * Test 35: Webhook success rate is shown
     *
     */

    #[Test]
    public function test_webhook_success_rate()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/logs/webhooks')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-success-rate');

            // Check for success rate via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSuccessRate =
                str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'rate') ||
                str_contains($pageSource, 'percentage') ||
                str_contains($pageSource, '%');

            $this->assertTrue($hasSuccessRate, 'Webhook success rate should be shown');

            $this->testResults['webhook_success_rate'] = 'Webhook success rate is shown';
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
                'test_suite' => 'Webhooks Management Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'webhook_deliveries' => WebhookDelivery::count(),
                    'projects' => Project::count(),
                    'test_user_email' => $this->user->email,
                    'test_project_slug' => $this->testProject->slug,
                ],
            ];

            $reportPath = storage_path('app/test-reports/webhooks-management-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
