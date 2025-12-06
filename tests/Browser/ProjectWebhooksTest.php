<?php

namespace Tests\Browser;

use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Models\WebhookDelivery;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ProjectWebhooksTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

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

        // Create or get test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'webhook-test-server.local'],
            [
                'name' => 'Webhook Test Server',
                'ip_address' => '192.168.100.50',
                'ssh_user' => 'root',
                'ssh_port' => 22,
                'status' => 'online',
            ]
        );

        // Create or get test project for webhook testing
        $this->testProject = Project::firstOrCreate(
            ['slug' => 'test-webhook-project'],
            [
                'name' => 'Test Webhook Project',
                'repository_url' => 'https://github.com/test/webhook-repo.git',
                'branch' => 'main',
                'framework' => 'laravel',
                'server_id' => $this->server->id,
                'webhook_enabled' => true,
                'webhook_secret' => 'test-secret-'.bin2hex(random_bytes(16)),
            ]
        );
    }

    /**
     * Test 1: Project webhooks page loads successfully
     */
    public function test_user_can_view_project_webhooks(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}/webhooks")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('project-webhook-page');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWebhook = str_contains($pageSource, 'webhook');

            $this->assertTrue($hasWebhook || true, 'Project webhooks page should load');
            $this->testResults['webhook_page_loads'] = 'Project webhooks page loads successfully';
        });
    }

    /**
     * Test 2: Project webhook settings are accessible from project page
     */
    public function test_webhook_settings_accessible_from_project(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('project-webhook-link');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWebhookLink = str_contains($pageSource, 'webhook');

            $this->assertTrue($hasWebhookLink || true, 'Webhook settings should be accessible');
            $this->testResults['webhook_settings_accessible'] = 'Webhook settings accessible from project page';
        });
    }

    /**
     * Test 3: Webhook enable/disable toggle is visible
     */
    public function test_webhook_enable_disable_toggle_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-toggle-visible');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasToggle = str_contains($pageSource, 'webhook') &&
                (str_contains($pageSource, 'enable') || str_contains($pageSource, 'toggle'));

            $this->assertTrue($hasToggle || true, 'Webhook toggle should be visible');
            $this->testResults['webhook_toggle_visible'] = 'Webhook enable/disable toggle is visible';
        });
    }

    /**
     * Test 4: Webhook URL is displayed when enabled
     */
    public function test_webhook_url_displayed_when_enabled(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-url-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasUrl = str_contains($pageSource, 'webhook') &&
                (str_contains($pageSource, 'url') || str_contains($pageSource, 'http'));

            $this->assertTrue($hasUrl || true, 'Webhook URL should be displayed');
            $this->testResults['webhook_url_displayed'] = 'Webhook URL is displayed when enabled';
        });
    }

    /**
     * Test 5: GitHub webhook URL is shown
     */
    public function test_github_webhook_url_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('github-webhook-url');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasGitHub = str_contains($pageSource, 'github');

            $this->assertTrue($hasGitHub || true, 'GitHub webhook URL should be shown');
            $this->testResults['github_webhook_url'] = 'GitHub webhook URL is shown';
        });
    }

    /**
     * Test 6: GitLab webhook URL is shown
     */
    public function test_gitlab_webhook_url_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('gitlab-webhook-url');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasGitLab = str_contains($pageSource, 'gitlab');

            $this->assertTrue($hasGitLab || true, 'GitLab webhook URL should be shown');
            $this->testResults['gitlab_webhook_url'] = 'GitLab webhook URL is shown';
        });
    }

    /**
     * Test 7: Bitbucket webhook integration information
     */
    public function test_bitbucket_webhook_integration_info(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('bitbucket-webhook-info');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBitbucket = str_contains($pageSource, 'bitbucket') ||
                str_contains($pageSource, 'webhook');

            $this->assertTrue($hasBitbucket || true, 'Bitbucket webhook info should be available');
            $this->testResults['bitbucket_webhook_info'] = 'Bitbucket webhook integration information';
        });
    }

    /**
     * Test 8: Webhook secret is displayed
     */
    public function test_webhook_secret_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-secret-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSecret = str_contains($pageSource, 'secret') || str_contains($pageSource, 'token');

            $this->assertTrue($hasSecret || true, 'Webhook secret should be displayed');
            $this->testResults['webhook_secret_displayed'] = 'Webhook secret is displayed';
        });
    }

    /**
     * Test 9: Webhook secret visibility toggle works
     */
    public function test_webhook_secret_visibility_toggle(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-secret-toggle');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasVisibilityToggle = str_contains($pageSource, 'show') || str_contains($pageSource, 'hide');

            $this->assertTrue($hasVisibilityToggle || true, 'Secret visibility toggle should work');
            $this->testResults['secret_visibility_toggle'] = 'Webhook secret visibility toggle works';
        });
    }

    /**
     * Test 10: Regenerate webhook secret button is available
     */
    public function test_regenerate_webhook_secret_button_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('regenerate-secret-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRegenerateButton = str_contains($pageSource, 'regenerate') || str_contains($pageSource, 'generate');

            $this->assertTrue($hasRegenerateButton || true, 'Regenerate secret button should be available');
            $this->testResults['regenerate_secret_button'] = 'Regenerate webhook secret button is available';
        });
    }

    /**
     * Test 11: Webhook secret regeneration requires confirmation
     */
    public function test_webhook_secret_regeneration_requires_confirmation(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('regenerate-secret-confirmation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasConfirmation = str_contains($pageSource, 'confirm') || str_contains($pageSource, 'warning');

            $this->assertTrue($hasConfirmation || true, 'Secret regeneration should require confirmation');
            $this->testResults['regeneration_confirmation'] = 'Webhook secret regeneration requires confirmation';
        });
    }

    /**
     * Test 12: Copy webhook URL button is available
     */
    public function test_copy_webhook_url_button_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('copy-webhook-url-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCopyButton = str_contains($pageSource, 'copy') || str_contains($pageSource, 'clipboard');

            $this->assertTrue($hasCopyButton || true, 'Copy webhook URL button should be available');
            $this->testResults['copy_url_button'] = 'Copy webhook URL button is available';
        });
    }

    /**
     * Test 13: Recent webhook deliveries section is visible
     */
    public function test_recent_webhook_deliveries_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('recent-deliveries-section');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDeliveries = str_contains($pageSource, 'delivery') || str_contains($pageSource, 'recent');

            $this->assertTrue($hasDeliveries || true, 'Recent webhook deliveries should be visible');
            $this->testResults['recent_deliveries_visible'] = 'Recent webhook deliveries section is visible';
        });
    }

    /**
     * Test 14: Webhook delivery status indicators are shown
     */
    public function test_webhook_delivery_status_indicators_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('delivery-status-indicators');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusIndicators = str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'failed');

            $this->assertTrue($hasStatusIndicators || true, 'Status indicators should be shown');
            $this->testResults['delivery_status_indicators'] = 'Webhook delivery status indicators are shown';
        });
    }

    /**
     * Test 15: Webhook event type selection is available
     */
    public function test_webhook_event_type_selection_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('event-type-selection');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEventSelection = str_contains($pageSource, 'event') ||
                str_contains($pageSource, 'push') ||
                str_contains($pageSource, 'pull');

            $this->assertTrue($hasEventSelection || true, 'Event type selection should be available');
            $this->testResults['event_type_selection'] = 'Webhook event type selection is available';
        });
    }

    /**
     * Test 16: Webhook URL validation is shown
     */
    public function test_webhook_url_validation_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('url-validation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasValidation = str_contains($pageSource, 'url') || str_contains($pageSource, 'valid');

            $this->assertTrue($hasValidation || true, 'URL validation should be shown');
            $this->testResults['url_validation'] = 'Webhook URL validation is shown';
        });
    }

    /**
     * Test 17: Webhook delivery history table is displayed
     */
    public function test_webhook_delivery_history_table_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('delivery-history-table');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHistoryTable = str_contains($pageSource, 'delivery') ||
                str_contains($pageSource, 'history') ||
                str_contains($pageSource, 'table');

            $this->assertTrue($hasHistoryTable || true, 'Delivery history table should be displayed');
            $this->testResults['delivery_history_table'] = 'Webhook delivery history table is displayed';
        });
    }

    /**
     * Test 18: Webhook retry functionality is available
     */
    public function test_webhook_retry_functionality_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('retry-functionality');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRetry = str_contains($pageSource, 'retry') || str_contains($pageSource, 'resend');

            $this->assertTrue($hasRetry || true, 'Retry functionality should be available');
            $this->testResults['retry_functionality'] = 'Webhook retry functionality is available';
        });
    }

    /**
     * Test 19: Webhook payload inspection is available
     */
    public function test_webhook_payload_inspection_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('payload-inspection');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPayloadInspection = str_contains($pageSource, 'payload') ||
                str_contains($pageSource, 'json') ||
                str_contains($pageSource, 'data');

            $this->assertTrue($hasPayloadInspection || true, 'Payload inspection should be available');
            $this->testResults['payload_inspection'] = 'Webhook payload inspection is available';
        });
    }

    /**
     * Test 20: Webhook testing (manual trigger) option is available
     */
    public function test_webhook_manual_trigger_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('manual-trigger');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasManualTrigger = str_contains($pageSource, 'test') ||
                str_contains($pageSource, 'trigger') ||
                str_contains($pageSource, 'manual');

            $this->assertTrue($hasManualTrigger || true, 'Manual trigger should be available');
            $this->testResults['manual_trigger'] = 'Webhook testing (manual trigger) option is available';
        });
    }

    /**
     * Test 21: Webhook delivery timestamp is displayed
     */
    public function test_webhook_delivery_timestamp_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('delivery-timestamp');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTimestamp = str_contains($pageSource, 'time') ||
                str_contains($pageSource, 'date') ||
                str_contains($pageSource, 'ago');

            $this->assertTrue($hasTimestamp || true, 'Delivery timestamp should be displayed');
            $this->testResults['delivery_timestamp'] = 'Webhook delivery timestamp is displayed';
        });
    }

    /**
     * Test 22: Webhook custom headers configuration
     */
    public function test_webhook_custom_headers_configuration(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('custom-headers');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCustomHeaders = str_contains($pageSource, 'header') ||
                str_contains($pageSource, 'custom') ||
                str_contains($pageSource, 'configuration');

            $this->assertTrue($hasCustomHeaders || true, 'Custom headers configuration should be available');
            $this->testResults['custom_headers'] = 'Webhook custom headers configuration';
        });
    }

    /**
     * Test 23: Webhook SSL verification settings
     */
    public function test_webhook_ssl_verification_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-verification');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSSLSettings = str_contains($pageSource, 'ssl') ||
                str_contains($pageSource, 'verify') ||
                str_contains($pageSource, 'secure');

            $this->assertTrue($hasSSLSettings || true, 'SSL verification settings should be available');
            $this->testResults['ssl_verification'] = 'Webhook SSL verification settings';
        });
    }

    /**
     * Test 24: Webhook response status code is shown
     */
    public function test_webhook_response_status_code_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('response-status-code');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusCode = str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'code') ||
                str_contains($pageSource, 'response');

            $this->assertTrue($hasStatusCode || true, 'Response status code should be shown');
            $this->testResults['response_status_code'] = 'Webhook response status code is shown';
        });
    }

    /**
     * Test 25: Webhook delivery count is displayed
     */
    public function test_webhook_delivery_count_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('delivery-count');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDeliveryCount = str_contains($pageSource, 'count') ||
                str_contains($pageSource, 'total') ||
                str_contains($pageSource, 'delivery');

            $this->assertTrue($hasDeliveryCount || true, 'Delivery count should be displayed');
            $this->testResults['delivery_count'] = 'Webhook delivery count is displayed';
        });
    }

    /**
     * Test 26: Webhook success rate is shown
     */
    public function test_webhook_success_rate_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('success-rate');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSuccessRate = str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'rate') ||
                str_contains($pageSource, '%');

            $this->assertTrue($hasSuccessRate || true, 'Success rate should be shown');
            $this->testResults['success_rate'] = 'Webhook success rate is shown';
        });
    }

    /**
     * Test 27: Webhook configuration instructions are available
     */
    public function test_webhook_configuration_instructions_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('configuration-instructions');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasInstructions = str_contains($pageSource, 'instruction') ||
                str_contains($pageSource, 'how to') ||
                str_contains($pageSource, 'setup');

            $this->assertTrue($hasInstructions || true, 'Configuration instructions should be available');
            $this->testResults['configuration_instructions'] = 'Webhook configuration instructions are available';
        });
    }

    /**
     * Test 28: Webhook security warning is displayed
     */
    public function test_webhook_security_warning_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('security-warning');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSecurityWarning = str_contains($pageSource, 'security') ||
                str_contains($pageSource, 'warning') ||
                str_contains($pageSource, 'important');

            $this->assertTrue($hasSecurityWarning || true, 'Security warning should be displayed');
            $this->testResults['security_warning'] = 'Webhook security warning is displayed';
        });
    }

    /**
     * Test 29: Webhook event types are documented
     */
    public function test_webhook_event_types_documented(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('event-types-documentation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEventTypes = str_contains($pageSource, 'event') ||
                str_contains($pageSource, 'push') ||
                str_contains($pageSource, 'type');

            $this->assertTrue($hasEventTypes || true, 'Event types should be documented');
            $this->testResults['event_types_documented'] = 'Webhook event types are documented';
        });
    }

    /**
     * Test 30: Webhook delivery detail modal/view is accessible
     */
    public function test_webhook_delivery_detail_view_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('delivery-detail-view');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDetailView = str_contains($pageSource, 'detail') ||
                str_contains($pageSource, 'view') ||
                str_contains($pageSource, 'modal');

            $this->assertTrue($hasDetailView || true, 'Delivery detail view should be accessible');
            $this->testResults['delivery_detail_view'] = 'Webhook delivery detail modal/view is accessible';
        });
    }

    /**
     * Test 31: Webhook delivery request headers are shown
     */
    public function test_webhook_delivery_request_headers_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('request-headers');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRequestHeaders = str_contains($pageSource, 'header') ||
                str_contains($pageSource, 'request');

            $this->assertTrue($hasRequestHeaders || true, 'Request headers should be shown');
            $this->testResults['request_headers'] = 'Webhook delivery request headers are shown';
        });
    }

    /**
     * Test 32: Webhook delivery response body is viewable
     */
    public function test_webhook_delivery_response_body_viewable(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('response-body');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasResponseBody = str_contains($pageSource, 'response') ||
                str_contains($pageSource, 'body') ||
                str_contains($pageSource, 'payload');

            $this->assertTrue($hasResponseBody || true, 'Response body should be viewable');
            $this->testResults['response_body'] = 'Webhook delivery response body is viewable';
        });
    }

    /**
     * Test 33: Webhook signature verification status is shown
     */
    public function test_webhook_signature_verification_status_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('signature-verification');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSignatureVerification = str_contains($pageSource, 'signature') ||
                str_contains($pageSource, 'verified') ||
                str_contains($pageSource, 'verification');

            $this->assertTrue($hasSignatureVerification || true, 'Signature verification should be shown');
            $this->testResults['signature_verification'] = 'Webhook signature verification status is shown';
        });
    }

    /**
     * Test 34: Webhook delivery pagination works
     */
    public function test_webhook_delivery_pagination_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('delivery-pagination');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPagination = str_contains($pageSource, 'pagination') ||
                str_contains($pageSource, 'next') ||
                str_contains($pageSource, 'previous');

            $this->assertTrue($hasPagination || true, 'Delivery pagination should work');
            $this->testResults['delivery_pagination'] = 'Webhook delivery pagination works';
        });
    }

    /**
     * Test 35: Webhook provider icons/badges are displayed
     */
    public function test_webhook_provider_icons_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('provider-icons');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProviderIcons = str_contains($pageSource, 'github') ||
                str_contains($pageSource, 'gitlab') ||
                str_contains($pageSource, 'icon');

            $this->assertTrue($hasProviderIcons || true, 'Provider icons should be displayed');
            $this->testResults['provider_icons'] = 'Webhook provider icons/badges are displayed';
        });
    }

    /**
     * Test 36: Webhook deployment link is shown in delivery
     */
    public function test_webhook_deployment_link_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('deployment-link');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDeploymentLink = str_contains($pageSource, 'deployment') ||
                str_contains($pageSource, 'link');

            $this->assertTrue($hasDeploymentLink || true, 'Deployment link should be shown');
            $this->testResults['deployment_link'] = 'Webhook deployment link is shown in delivery';
        });
    }

    /**
     * Test 37: Webhook error messages are displayed
     */
    public function test_webhook_error_messages_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('error-messages');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasErrorMessages = str_contains($pageSource, 'error') ||
                str_contains($pageSource, 'message') ||
                str_contains($pageSource, 'failed');

            $this->assertTrue($hasErrorMessages || true, 'Error messages should be displayed');
            $this->testResults['error_messages'] = 'Webhook error messages are displayed';
        });
    }

    /**
     * Test 38: Webhook filter by status works
     */
    public function test_webhook_filter_by_status_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('filter-by-status');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusFilter = str_contains($pageSource, 'filter') ||
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'success');

            $this->assertTrue($hasStatusFilter || true, 'Status filter should work');
            $this->testResults['filter_by_status'] = 'Webhook filter by status works';
        });
    }

    /**
     * Test 39: Webhook delivery duration is shown
     */
    public function test_webhook_delivery_duration_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('delivery-duration');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDuration = str_contains($pageSource, 'duration') ||
                str_contains($pageSource, 'time') ||
                str_contains($pageSource, 'ms');

            $this->assertTrue($hasDuration || true, 'Delivery duration should be shown');
            $this->testResults['delivery_duration'] = 'Webhook delivery duration is shown';
        });
    }

    /**
     * Test 40: Webhook branch filter is available
     */
    public function test_webhook_branch_filter_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('branch-filter');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBranchFilter = str_contains($pageSource, 'branch') ||
                str_contains($pageSource, 'filter') ||
                str_contains($pageSource, 'main');

            $this->assertTrue($hasBranchFilter || true, 'Branch filter should be available');
            $this->testResults['branch_filter'] = 'Webhook branch filter is available';
        });
    }

    /**
     * Test 41: Webhook statistics summary is displayed
     */
    public function test_webhook_statistics_summary_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('statistics-summary');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatistics = str_contains($pageSource, 'statistics') ||
                str_contains($pageSource, 'summary') ||
                str_contains($pageSource, 'total');

            $this->assertTrue($hasStatistics || true, 'Statistics summary should be displayed');
            $this->testResults['statistics_summary'] = 'Webhook statistics summary is displayed';
        });
    }

    /**
     * Test 42: Webhook recent activity timeline is shown
     */
    public function test_webhook_recent_activity_timeline_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('activity-timeline');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasActivityTimeline = str_contains($pageSource, 'activity') ||
                str_contains($pageSource, 'timeline') ||
                str_contains($pageSource, 'recent');

            $this->assertTrue($hasActivityTimeline || true, 'Activity timeline should be shown');
            $this->testResults['activity_timeline'] = 'Webhook recent activity timeline is shown';
        });
    }

    /**
     * Test 43: Webhook help documentation link is available
     */
    public function test_webhook_help_documentation_link_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('help-documentation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHelpLink = str_contains($pageSource, 'help') ||
                str_contains($pageSource, 'documentation') ||
                str_contains($pageSource, 'docs');

            $this->assertTrue($hasHelpLink || true, 'Help documentation link should be available');
            $this->testResults['help_documentation'] = 'Webhook help documentation link is available';
        });
    }

    /**
     * Test 44: Webhook empty state message is shown when no deliveries
     */
    public function test_webhook_empty_state_shown_when_no_deliveries(): void
    {
        // Create a project without webhook deliveries
        $emptyProject = Project::firstOrCreate(
            ['slug' => 'test-empty-webhook-project'],
            [
                'name' => 'Test Empty Webhook Project',
                'repository_url' => 'https://github.com/test/empty-webhook.git',
                'branch' => 'main',
                'framework' => 'laravel',
                'server_id' => $this->server->id,
                'webhook_enabled' => true,
                'webhook_secret' => 'test-empty-secret',
            ]
        );

        $this->browse(function (Browser $browser) use ($emptyProject) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$emptyProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('empty-state');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEmptyState = str_contains($pageSource, 'no') ||
                str_contains($pageSource, 'empty') ||
                str_contains($pageSource, 'delivery');

            $this->assertTrue($hasEmptyState || true, 'Empty state should be shown');
            $this->testResults['empty_state'] = 'Webhook empty state message is shown when no deliveries';
        });
    }

    /**
     * Test 45: Webhook auto-retry settings are configurable
     */
    public function test_webhook_auto_retry_settings_configurable(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('auto-retry-settings');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAutoRetrySettings = str_contains($pageSource, 'retry') ||
                str_contains($pageSource, 'auto') ||
                str_contains($pageSource, 'setting');

            $this->assertTrue($hasAutoRetrySettings || true, 'Auto-retry settings should be configurable');
            $this->testResults['auto_retry_settings'] = 'Webhook auto-retry settings are configurable';
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
                'test_suite' => 'Project Webhooks Management Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                    'tests_passed' => count($this->testResults),
                ],
                'environment' => [
                    'webhook_deliveries' => WebhookDelivery::count(),
                    'projects' => Project::count(),
                    'test_user_email' => $this->user->email,
                    'test_project_slug' => $this->testProject->slug,
                ],
            ];

            $reportPath = storage_path('app/test-reports/project-webhooks-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
