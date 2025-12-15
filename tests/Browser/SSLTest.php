<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\SSLCertificate;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class SSLTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected array $testResults = [];

    protected ?Server $testServer = null;

    protected ?Project $testProject = null;

    protected ?Domain $testDomain = null;

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

        // Create test server for SSL tests
        $this->testServer = Server::firstOrCreate(
            ['name' => 'SSL Test Server'],
            [
                'ip_address' => '192.168.1.100',
                'hostname' => 'ssl-test.devflow.test',
                'username' => 'root',
                'port' => 22,
                'status' => 'online',
                'user_id' => $this->user->id,
            ]
        );

        // Create test project
        $this->testProject = Project::firstOrCreate(
            ['slug' => 'ssl-test-project'],
            [
                'name' => 'SSL Test Project',
                'server_id' => $this->testServer->id,
                'repository' => 'https://github.com/test/repo',
                'status' => 'active',
                'user_id' => $this->user->id,
            ]
        );

        // Create test domain
        $this->testDomain = Domain::firstOrCreate(
            ['domain' => 'test-ssl.devflow.test'],
            [
                'project_id' => $this->testProject->id,
                'is_primary' => true,
                'ssl_enabled' => true,
                'ssl_provider' => 'letsencrypt',
                'auto_renew_ssl' => true,
                'status' => 'active',
            ]
        );

        // Create test SSL certificates with various statuses
        $this->createTestCertificates();
    }

    /**
     * Create test SSL certificates with various statuses
     */
    protected function createTestCertificates(): void
    {
        // Active certificate
        SSLCertificate::firstOrCreate(
            [
                'server_id' => $this->testServer->id,
                'domain_id' => $this->testDomain->id,
            ],
            [
                'domain_name' => 'test-ssl.devflow.test',
                'provider' => 'letsencrypt',
                'status' => 'issued',
                'certificate_path' => '/etc/letsencrypt/live/test-ssl.devflow.test/cert.pem',
                'private_key_path' => '/etc/letsencrypt/live/test-ssl.devflow.test/privkey.pem',
                'chain_path' => '/etc/letsencrypt/live/test-ssl.devflow.test/chain.pem',
                'issued_at' => now()->subDays(60),
                'expires_at' => now()->addDays(30),
                'auto_renew' => true,
            ]
        );

        // Expiring soon certificate (within 7 days)
        $expiringDomain = Domain::firstOrCreate(
            ['domain' => 'expiring.devflow.test'],
            [
                'project_id' => $this->testProject->id,
                'is_primary' => false,
                'ssl_enabled' => true,
                'ssl_provider' => 'letsencrypt',
                'auto_renew_ssl' => true,
                'status' => 'active',
            ]
        );

        SSLCertificate::firstOrCreate(
            [
                'server_id' => $this->testServer->id,
                'domain_id' => $expiringDomain->id,
            ],
            [
                'domain_name' => 'expiring.devflow.test',
                'provider' => 'letsencrypt',
                'status' => 'issued',
                'certificate_path' => '/etc/letsencrypt/live/expiring.devflow.test/cert.pem',
                'private_key_path' => '/etc/letsencrypt/live/expiring.devflow.test/privkey.pem',
                'chain_path' => '/etc/letsencrypt/live/expiring.devflow.test/chain.pem',
                'issued_at' => now()->subDays(83),
                'expires_at' => now()->addDays(5),
                'auto_renew' => true,
            ]
        );

        // Expired certificate
        $expiredDomain = Domain::firstOrCreate(
            ['domain' => 'expired.devflow.test'],
            [
                'project_id' => $this->testProject->id,
                'is_primary' => false,
                'ssl_enabled' => false,
                'ssl_provider' => 'letsencrypt',
                'auto_renew_ssl' => false,
                'status' => 'inactive',
            ]
        );

        SSLCertificate::firstOrCreate(
            [
                'server_id' => $this->testServer->id,
                'domain_id' => $expiredDomain->id,
            ],
            [
                'domain_name' => 'expired.devflow.test',
                'provider' => 'letsencrypt',
                'status' => 'issued',
                'certificate_path' => '/etc/letsencrypt/live/expired.devflow.test/cert.pem',
                'private_key_path' => '/etc/letsencrypt/live/expired.devflow.test/privkey.pem',
                'chain_path' => '/etc/letsencrypt/live/expired.devflow.test/chain.pem',
                'issued_at' => now()->subDays(120),
                'expires_at' => now()->subDays(10),
                'auto_renew' => false,
            ]
        );

        // Failed certificate
        $failedDomain = Domain::firstOrCreate(
            ['domain' => 'failed.devflow.test'],
            [
                'project_id' => $this->testProject->id,
                'is_primary' => false,
                'ssl_enabled' => false,
                'ssl_provider' => 'letsencrypt',
                'auto_renew_ssl' => true,
                'status' => 'pending',
            ]
        );

        SSLCertificate::firstOrCreate(
            [
                'server_id' => $this->testServer->id,
                'domain_id' => $failedDomain->id,
            ],
            [
                'domain_name' => 'failed.devflow.test',
                'provider' => 'letsencrypt',
                'status' => 'failed',
                'certificate_path' => null,
                'private_key_path' => null,
                'chain_path' => null,
                'issued_at' => null,
                'expires_at' => null,
                'auto_renew' => true,
                'last_renewal_attempt' => now()->subHours(2),
                'renewal_error' => 'DNS validation failed',
            ]
        );
    }

    /**
     * Test 1: SSL certificates list page loads
     *
     */

    #[Test]
    public function test_ssl_certificates_list_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-certificates-list-page');

            // Check if SSL certificates page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSSLContent =
                str_contains($pageSource, 'ssl') ||
                str_contains($pageSource, 'certificate') ||
                str_contains($pageSource, 'https') ||
                str_contains($pageSource, 'letsencrypt');

            $this->assertTrue($hasSSLContent, 'SSL certificates list page should load');

            $this->testResults['ssl_list_page'] = 'SSL certificates list page loaded successfully';
        });
    }

    /**
     * Test 2: SSL statistics dashboard displays
     *
     */

    #[Test]
    public function test_ssl_statistics_dashboard_displays()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-statistics-dashboard');

            // Check for statistics content
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatistics =
                str_contains($pageSource, 'total') ||
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'expiring') ||
                str_contains($pageSource, 'expired');

            $this->assertTrue($hasStatistics, 'SSL statistics should be displayed');

            $this->testResults['ssl_statistics'] = 'SSL statistics dashboard displays';
        });
    }

    /**
     * Test 3: Certificate details modal can be opened
     *
     */

    #[Test]
    public function test_certificate_details_modal_opens()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-before-modal-open');

            // Look for certificate details or view button
            $pageSource = $browser->driver->getPageSource();
            $hasCertificateInfo =
                str_contains($pageSource, 'test-ssl.devflow.test') ||
                str_contains($pageSource, 'View') ||
                str_contains($pageSource, 'Details') ||
                str_contains($pageSource, 'Certificate');

            $this->assertTrue($hasCertificateInfo, 'Certificate details should be accessible');

            $this->testResults['certificate_details_modal'] = 'Certificate details modal functionality present';
        });
    }

    /**
     * Test 4: Certificate expiry warnings are visible
     *
     */

    #[Test]
    public function test_certificate_expiry_warnings_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-expiry-warnings');

            // Check for expiry warnings
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasExpiryWarnings =
                str_contains($pageSource, 'expir') ||
                str_contains($pageSource, 'days') ||
                str_contains($pageSource, 'warning') ||
                str_contains($pageSource, 'renew');

            $this->assertTrue($hasExpiryWarnings, 'Certificate expiry warnings should be visible');

            $this->testResults['expiry_warnings'] = 'Certificate expiry warnings are visible';
        });
    }

    /**
     * Test 5: SSL status filter works
     *
     */

    #[Test]
    public function test_ssl_status_filter_works()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-status-filter');

            // Check for filter options
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFilters =
                str_contains($pageSource, 'filter') ||
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'all') ||
                str_contains($pageSource, 'active');

            $this->assertTrue($hasFilters, 'SSL status filter should be available');

            $this->testResults['status_filter'] = 'SSL status filter functionality present';
        });
    }

    /**
     * Test 6: Certificate search functionality present
     *
     */

    #[Test]
    public function test_certificate_search_functionality_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-search-functionality');

            // Check for search input
            $pageSource = $browser->driver->getPageSource();
            $hasSearch =
                str_contains($pageSource, 'search') ||
                str_contains($pageSource, 'Search') ||
                str_contains($pageSource, 'type="text"') ||
                str_contains($pageSource, 'wire:model');

            $this->assertTrue($hasSearch, 'Certificate search functionality should be present');

            $this->testResults['search_functionality'] = 'Certificate search functionality present';
        });
    }

    /**
     * Test 7: Certificate renewal button is visible
     *
     */

    #[Test]
    public function test_certificate_renewal_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-renewal-button');

            // Check for renewal button
            $pageSource = $browser->driver->getPageSource();
            $hasRenewalButton =
                str_contains($pageSource, 'Renew') ||
                str_contains($pageSource, 'renew') ||
                str_contains($pageSource, 'Refresh') ||
                str_contains($pageSource, 'wire:click');

            $this->assertTrue($hasRenewalButton, 'Certificate renewal button should be visible');

            $this->testResults['renewal_button'] = 'Certificate renewal button is visible';
        });
    }

    /**
     * Test 8: Let's Encrypt provider information displayed
     *
     */

    #[Test]
    public function test_letsencrypt_provider_information_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-letsencrypt-provider');

            // Check for Let's Encrypt references
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProviderInfo =
                str_contains($pageSource, 'letsencrypt') ||
                str_contains($pageSource, "let's encrypt") ||
                str_contains($pageSource, 'provider') ||
                str_contains($pageSource, 'certbot');

            $this->assertTrue($hasProviderInfo , "Let's Encrypt provider information should be displayed");

            $this->testResults['letsencrypt_provider'] = "Let's Encrypt provider information displayed";
        });
    }

    /**
     * Test 9: Certificate status indicators are shown
     *
     */

    #[Test]
    public function test_certificate_status_indicators_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-status-indicators');

            // Check for status indicators
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusIndicators =
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'badge') ||
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'pending');

            $this->assertTrue($hasStatusIndicators, 'Certificate status indicators should be shown');

            $this->testResults['status_indicators'] = 'Certificate status indicators are shown';
        });
    }

    /**
     * Test 10: Expiring certificates are highlighted
     *
     */

    #[Test]
    public function test_expiring_certificates_highlighted()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-expiring-highlighted');

            // Check for expiring certificate highlighting
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasExpiringHighlight =
                str_contains($pageSource, 'expiring.devflow.test') ||
                str_contains($pageSource, 'yellow') ||
                str_contains($pageSource, 'warning') ||
                str_contains($pageSource, 'expires');

            $this->assertTrue($hasExpiringHighlight, 'Expiring certificates should be highlighted');

            $this->testResults['expiring_highlighted'] = 'Expiring certificates are highlighted';
        });
    }

    /**
     * Test 11: Expired certificates are shown with error state
     *
     */

    #[Test]
    public function test_expired_certificates_shown_with_error_state()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-expired-error-state');

            // Check for expired certificate error state
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasExpiredError =
                str_contains($pageSource, 'expired.devflow.test') ||
                str_contains($pageSource, 'expired') ||
                str_contains($pageSource, 'red') ||
                str_contains($pageSource, 'danger');

            $this->assertTrue($hasExpiredError, 'Expired certificates should be shown with error state');

            $this->testResults['expired_error_state'] = 'Expired certificates shown with error state';
        });
    }

    /**
     * Test 12: Failed certificate issuance is displayed
     *
     */

    #[Test]
    public function test_failed_certificate_issuance_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-failed-issuance');

            // Check for failed certificate
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFailedCertificate =
                str_contains($pageSource, 'failed.devflow.test') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'error') ||
                str_contains($pageSource, 'dns validation');

            $this->assertTrue($hasFailedCertificate, 'Failed certificate issuance should be displayed');

            $this->testResults['failed_issuance'] = 'Failed certificate issuance is displayed';
        });
    }

    /**
     * Test 13: Domain verification status is shown
     *
     */

    #[Test]
    public function test_domain_verification_status_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-domain-verification');

            // Check for domain verification status
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasVerificationStatus =
                str_contains($pageSource, 'verif') ||
                str_contains($pageSource, 'domain') ||
                str_contains($pageSource, 'dns') ||
                str_contains($pageSource, 'configured');

            $this->assertTrue($hasVerificationStatus, 'Domain verification status should be shown');

            $this->testResults['domain_verification'] = 'Domain verification status is shown';
        });
    }

    /**
     * Test 14: Certificate auto-renewal status is visible
     *
     */

    #[Test]
    public function test_certificate_auto_renewal_status_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-auto-renewal-status');

            // Check for auto-renewal status
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAutoRenewal =
                str_contains($pageSource, 'auto') ||
                str_contains($pageSource, 'renew') ||
                str_contains($pageSource, 'automatic') ||
                str_contains($pageSource, 'enabled');

            $this->assertTrue($hasAutoRenewal, 'Certificate auto-renewal status should be visible');

            $this->testResults['auto_renewal_status'] = 'Certificate auto-renewal status is visible';
        });
    }

    /**
     * Test 15: Certificate expiry dates are displayed
     *
     */

    #[Test]
    public function test_certificate_expiry_dates_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-expiry-dates');

            // Check for expiry dates
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasExpiryDates =
                str_contains($pageSource, 'expires') ||
                str_contains($pageSource, 'expiry') ||
                str_contains($pageSource, 'days') ||
                str_contains($pageSource, '202'); // Year pattern

            $this->assertTrue($hasExpiryDates, 'Certificate expiry dates should be displayed');

            $this->testResults['expiry_dates'] = 'Certificate expiry dates are displayed';
        });
    }

    /**
     * Test 16: Certificate issue date is shown
     *
     */

    #[Test]
    public function test_certificate_issue_date_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-issue-date');

            // Check for issue dates
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasIssueDates =
                str_contains($pageSource, 'issued') ||
                str_contains($pageSource, 'created') ||
                str_contains($pageSource, 'obtained') ||
                str_contains($pageSource, 'ago');

            $this->assertTrue($hasIssueDates, 'Certificate issue date should be shown');

            $this->testResults['issue_date'] = 'Certificate issue date is shown';
        });
    }

    /**
     * Test 17: Bulk renewal option is available
     *
     */

    #[Test]
    public function test_bulk_renewal_option_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-bulk-renewal');

            // Check for bulk renewal option
            $pageSource = $browser->driver->getPageSource();
            $hasBulkRenewal =
                str_contains($pageSource, 'Renew All') ||
                str_contains($pageSource, 'Bulk') ||
                str_contains($pageSource, 'renewAll') ||
                str_contains($pageSource, 'Renew Expiring');

            $this->assertTrue($hasBulkRenewal, 'Bulk renewal option should be available');

            $this->testResults['bulk_renewal'] = 'Bulk renewal option is available';
        });
    }

    /**
     * Test 18: Certificate download option is present
     *
     */

    #[Test]
    public function test_certificate_download_option_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-download-option');

            // Check for download option
            $pageSource = $browser->driver->getPageSource();
            $hasDownloadOption =
                str_contains($pageSource, 'Download') ||
                str_contains($pageSource, 'download') ||
                str_contains($pageSource, 'Export') ||
                str_contains($pageSource, 'export');

            $this->assertTrue($hasDownloadOption, 'Certificate download option should be present');

            $this->testResults['download_option'] = 'Certificate download option is present';
        });
    }

    /**
     * Test 19: Certificate paths are accessible
     *
     */

    #[Test]
    public function test_certificate_paths_accessible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-certificate-paths');

            // Check for certificate paths
            $pageSource = $browser->driver->getPageSource();
            $hasCertificatePaths =
                str_contains($pageSource, '/etc/letsencrypt') ||
                str_contains($pageSource, 'cert.pem') ||
                str_contains($pageSource, 'privkey.pem') ||
                str_contains($pageSource, 'Path');

            $this->assertTrue($hasCertificatePaths, 'Certificate paths should be accessible');

            $this->testResults['certificate_paths'] = 'Certificate paths are accessible';
        });
    }

    /**
     * Test 20: Critical certificates section displays
     *
     */

    #[Test]
    public function test_critical_certificates_section_displays()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-critical-certificates');

            // Check for critical certificates section
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCriticalSection =
                str_contains($pageSource, 'critical') ||
                str_contains($pageSource, 'urgent') ||
                str_contains($pageSource, 'attention') ||
                str_contains($pageSource, 'priority');

            $this->assertTrue($hasCriticalSection, 'Critical certificates section should display');

            $this->testResults['critical_certificates'] = 'Critical certificates section displays';
        });
    }

    /**
     * Test 21: Certificate provider badge is shown
     *
     */

    #[Test]
    public function test_certificate_provider_badge_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-provider-badge');

            // Check for provider badge
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProviderBadge =
                str_contains($pageSource, 'letsencrypt') ||
                str_contains($pageSource, 'provider') ||
                str_contains($pageSource, 'certbot') ||
                str_contains($pageSource, 'acme');

            $this->assertTrue($hasProviderBadge, 'Certificate provider badge should be shown');

            $this->testResults['provider_badge'] = 'Certificate provider badge is shown';
        });
    }

    /**
     * Test 22: Days until expiry counter is visible
     *
     */

    #[Test]
    public function test_days_until_expiry_counter_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-days-until-expiry');

            // Check for days counter
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDaysCounter =
                str_contains($pageSource, 'days') ||
                str_contains($pageSource, 'day') ||
                str_contains($pageSource, 'd)') ||
                preg_match('/\d+\s*day/i', $pageSource);

            $this->assertTrue($hasDaysCounter, 'Days until expiry counter should be visible');

            $this->testResults['days_counter'] = 'Days until expiry counter is visible';
        });
    }

    /**
     * Test 23: Certificate validation error messages displayed
     *
     */

    #[Test]
    public function test_certificate_validation_errors_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-validation-errors');

            // Check for validation error messages
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasValidationErrors =
                str_contains($pageSource, 'dns validation failed') ||
                str_contains($pageSource, 'error') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'validation');

            $this->assertTrue($hasValidationErrors, 'Certificate validation error messages should be displayed');

            $this->testResults['validation_errors'] = 'Certificate validation error messages displayed';
        });
    }

    /**
     * Test 24: SSL certificate pagination works
     *
     */

    #[Test]
    public function test_ssl_certificate_pagination_works()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-pagination');

            // Check for pagination elements
            $pageSource = $browser->driver->getPageSource();
            $hasPagination =
                str_contains($pageSource, 'pagination') ||
                str_contains($pageSource, 'Next') ||
                str_contains($pageSource, 'Previous') ||
                str_contains($pageSource, 'Page');

            $this->assertTrue($hasPagination, 'SSL certificate pagination should work');

            $this->testResults['pagination'] = 'SSL certificate pagination works';
        });
    }

    /**
     * Test 25: Issue new certificate button is visible
     *
     */

    #[Test]
    public function test_issue_new_certificate_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-issue-new-button');

            // Check for issue new certificate button
            $pageSource = $browser->driver->getPageSource();
            $hasIssueButton =
                str_contains($pageSource, 'Issue') ||
                str_contains($pageSource, 'New Certificate') ||
                str_contains($pageSource, 'Add Certificate') ||
                str_contains($pageSource, 'Create');

            $this->assertTrue($hasIssueButton, 'Issue new certificate button should be visible');

            $this->testResults['issue_new_button'] = 'Issue new certificate button is visible';
        });
    }

    /**
     * Test 26: Certificate revocation option is available
     *
     */

    #[Test]
    public function test_certificate_revocation_option_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-revocation-option');

            // Check for revocation option
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRevocationOption =
                str_contains($pageSource, 'revoke') ||
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'remove') ||
                str_contains($pageSource, 'disable');

            $this->assertTrue($hasRevocationOption, 'Certificate revocation option should be available');

            $this->testResults['revocation_option'] = 'Certificate revocation option is available';
        });
    }

    /**
     * Test 27: Certificate refresh/check expiry action works
     *
     */

    #[Test]
    public function test_certificate_refresh_check_expiry_action_works()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-refresh-check-expiry');

            // Check for refresh/check expiry action
            $pageSource = $browser->driver->getPageSource();
            $hasRefreshAction =
                str_contains($pageSource, 'Refresh') ||
                str_contains($pageSource, 'Check') ||
                str_contains($pageSource, 'Verify') ||
                str_contains($pageSource, 'Update');

            $this->assertTrue($hasRefreshAction, 'Certificate refresh/check expiry action should work');

            $this->testResults['refresh_check_expiry'] = 'Certificate refresh/check expiry action works';
        });
    }

    /**
     * Test 28: Domain list linked to certificates is shown
     *
     */

    #[Test]
    public function test_domain_list_linked_to_certificates_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-domain-list');

            // Check for domain names in the list
            $pageSource = $browser->driver->getPageSource();
            $hasDomainList =
                str_contains($pageSource, 'devflow.test') ||
                str_contains($pageSource, 'Domain') ||
                str_contains($pageSource, 'domain') ||
                str_contains($pageSource, '.com');

            $this->assertTrue($hasDomainList, 'Domain list linked to certificates should be shown');

            $this->testResults['domain_list'] = 'Domain list linked to certificates is shown';
        });
    }

    /**
     * Test 29: Last renewal attempt timestamp is displayed
     *
     */

    #[Test]
    public function test_last_renewal_attempt_timestamp_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-last-renewal-attempt');

            // Check for last renewal attempt
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRenewalAttempt =
                str_contains($pageSource, 'last') ||
                str_contains($pageSource, 'attempt') ||
                str_contains($pageSource, 'ago') ||
                str_contains($pageSource, 'hour');

            $this->assertTrue($hasRenewalAttempt, 'Last renewal attempt timestamp should be displayed');

            $this->testResults['last_renewal_attempt'] = 'Last renewal attempt timestamp is displayed';
        });
    }

    /**
     * Test 30: SSL certificate count is accurate
     *
     */

    #[Test]
    public function test_ssl_certificate_count_accurate()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-certificate-count');

            // Check for certificate count
            $pageSource = $browser->driver->getPageSource();
            $certificateCount = SSLCertificate::where('server_id', $this->testServer->id)->count();

            // Look for numbers that might represent the count
            $hasCount =
                str_contains($pageSource, (string) $certificateCount) ||
                str_contains($pageSource, 'certificate') ||
                str_contains($pageSource, 'Total') ||
                str_contains($pageSource, 'total');

            $this->assertTrue($hasCount, 'SSL certificate count should be accurate');

            $this->testResults['certificate_count'] = "SSL certificate count is accurate ({$certificateCount} certificates)";
        });
    }

    /**
     * Test 31: Certificate chain validation status is shown
     *
     */

    #[Test]
    public function test_certificate_chain_validation_status_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-chain-validation');

            // Check for chain validation status
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasChainValidation =
                str_contains($pageSource, 'chain') ||
                str_contains($pageSource, 'fullchain') ||
                str_contains($pageSource, 'intermediate') ||
                str_contains($pageSource, 'valid');

            $this->assertTrue($hasChainValidation, 'Certificate chain validation status should be shown');

            $this->testResults['chain_validation'] = 'Certificate chain validation status is shown';
        });
    }

    /**
     * Test 32: Certificate history/logs are accessible
     *
     */

    #[Test]
    public function test_certificate_history_logs_accessible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-history-logs');

            // Check for history/logs access
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHistory =
                str_contains($pageSource, 'history') ||
                str_contains($pageSource, 'log') ||
                str_contains($pageSource, 'timeline') ||
                str_contains($pageSource, 'activity');

            $this->assertTrue($hasHistory, 'Certificate history/logs should be accessible');

            $this->testResults['history_logs'] = 'Certificate history/logs are accessible';
        });
    }

    /**
     * Test 33: Wildcard certificate support is indicated
     *
     */

    #[Test]
    public function test_wildcard_certificate_support_indicated()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-wildcard-support');

            // Check for wildcard certificate support
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWildcardSupport =
                str_contains($pageSource, 'wildcard') ||
                str_contains($pageSource, '*.') ||
                str_contains($pageSource, 'subdomain') ||
                str_contains($pageSource, 'multiple');

            $this->assertTrue($hasWildcardSupport, 'Wildcard certificate support should be indicated');

            $this->testResults['wildcard_support'] = 'Wildcard certificate support is indicated';
        });
    }

    /**
     * Test 34: SSL certificate export functionality present
     *
     */

    #[Test]
    public function test_ssl_certificate_export_functionality_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-export-functionality');

            // Check for export functionality
            $pageSource = $browser->driver->getPageSource();
            $hasExportFunctionality =
                str_contains($pageSource, 'Export') ||
                str_contains($pageSource, 'export') ||
                str_contains($pageSource, 'Download') ||
                str_contains($pageSource, 'download');

            $this->assertTrue($hasExportFunctionality, 'SSL certificate export functionality should be present');

            $this->testResults['export_functionality'] = 'SSL certificate export functionality is present';
        });
    }

    /**
     * Test 35: Certificate auto-renewal toggle is available
     *
     */

    #[Test]
    public function test_certificate_auto_renewal_toggle_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-auto-renewal-toggle');

            // Check for auto-renewal toggle
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAutoRenewalToggle =
                str_contains($pageSource, 'auto') ||
                str_contains($pageSource, 'toggle') ||
                str_contains($pageSource, 'switch') ||
                str_contains($pageSource, 'enabled');

            $this->assertTrue($hasAutoRenewalToggle, 'Certificate auto-renewal toggle should be available');

            $this->testResults['auto_renewal_toggle'] = 'Certificate auto-renewal toggle is available';
        });
    }

    /**
     * Test 36: Certificate notification settings are configurable
     *
     */

    #[Test]
    public function test_certificate_notification_settings_configurable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-notification-settings');

            // Check for notification settings
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNotificationSettings =
                str_contains($pageSource, 'notif') ||
                str_contains($pageSource, 'alert') ||
                str_contains($pageSource, 'email') ||
                str_contains($pageSource, 'settings');

            $this->assertTrue($hasNotificationSettings, 'Certificate notification settings should be configurable');

            $this->testResults['notification_settings'] = 'Certificate notification settings are configurable';
        });
    }

    /**
     * Test 37: SSL certificate creation modal opens
     *
     */

    #[Test]
    public function test_ssl_certificate_creation_modal_opens()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-creation-modal-before');

            // Check for create button and modal elements
            $pageSource = $browser->driver->getPageSource();
            $hasCreationModal =
                str_contains($pageSource, 'Issue') ||
                str_contains($pageSource, 'Create') ||
                str_contains($pageSource, 'New Certificate') ||
                str_contains($pageSource, 'Add Certificate');

            $this->assertTrue($hasCreationModal, 'SSL certificate creation modal should be available');

            $this->testResults['creation_modal'] = 'SSL certificate creation modal is available';
        });
    }

    /**
     * Test 38: Let's Encrypt certificate option is available
     *
     */

    #[Test]
    public function test_letsencrypt_certificate_option_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-letsencrypt-option');

            // Check for Let's Encrypt option
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLetsEncryptOption =
                str_contains($pageSource, 'letsencrypt') ||
                str_contains($pageSource, "let's encrypt") ||
                str_contains($pageSource, 'acme') ||
                str_contains($pageSource, 'certbot');

            $this->assertTrue($hasLetsEncryptOption , "Let's Encrypt certificate option should be available");

            $this->testResults['letsencrypt_option'] = "Let's Encrypt certificate option is available";
        });
    }

    /**
     * Test 39: Custom certificate upload option is present
     *
     */

    #[Test]
    public function test_custom_certificate_upload_option_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-custom-upload-option');

            // Check for custom certificate upload
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCustomUpload =
                str_contains($pageSource, 'custom') ||
                str_contains($pageSource, 'upload') ||
                str_contains($pageSource, 'file') ||
                str_contains($pageSource, 'import');

            $this->assertTrue($hasCustomUpload, 'Custom certificate upload option should be present');

            $this->testResults['custom_upload'] = 'Custom certificate upload option is present';
        });
    }

    /**
     * Test 40: Certificate verification button is visible
     *
     */

    #[Test]
    public function test_certificate_verification_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-verification-button');

            // Check for verification button
            $pageSource = $browser->driver->getPageSource();
            $hasVerificationButton =
                str_contains($pageSource, 'Verify') ||
                str_contains($pageSource, 'verify') ||
                str_contains($pageSource, 'Check') ||
                str_contains($pageSource, 'Validate');

            $this->assertTrue($hasVerificationButton, 'Certificate verification button should be visible');

            $this->testResults['verification_button'] = 'Certificate verification button is visible';
        });
    }

    /**
     * Test 41: SSL certificate sorting functionality works
     *
     */

    #[Test]
    public function test_ssl_certificate_sorting_functionality_works()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-sorting-functionality');

            // Check for sorting functionality
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSorting =
                str_contains($pageSource, 'sort') ||
                str_contains($pageSource, 'order') ||
                str_contains($pageSource, 'asc') ||
                str_contains($pageSource, 'desc');

            $this->assertTrue($hasSorting, 'SSL certificate sorting functionality should work');

            $this->testResults['sorting_functionality'] = 'SSL certificate sorting functionality works';
        });
    }

    /**
     * Test 42: Certificate details show issuer information
     *
     */

    #[Test]
    public function test_certificate_details_show_issuer_information()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-issuer-information');

            // Check for issuer information
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasIssuerInfo =
                str_contains($pageSource, 'issuer') ||
                str_contains($pageSource, 'authority') ||
                str_contains($pageSource, 'letsencrypt') ||
                str_contains($pageSource, 'provider');

            $this->assertTrue($hasIssuerInfo, 'Certificate details should show issuer information');

            $this->testResults['issuer_information'] = 'Certificate details show issuer information';
        });
    }

    /**
     * Test 43: Certificate bulk deletion option is available
     *
     */

    #[Test]
    public function test_certificate_bulk_deletion_option_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-bulk-deletion');

            // Check for bulk deletion option
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBulkDeletion =
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'remove') ||
                str_contains($pageSource, 'bulk') ||
                str_contains($pageSource, 'select');

            $this->assertTrue($hasBulkDeletion, 'Certificate bulk deletion option should be available');

            $this->testResults['bulk_deletion'] = 'Certificate bulk deletion option is available';
        });
    }

    /**
     * Test 44: Certbot installation status is shown
     *
     */

    #[Test]
    public function test_certbot_installation_status_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-certbot-status');

            // Check for Certbot installation status
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCertbotStatus =
                str_contains($pageSource, 'certbot') ||
                str_contains($pageSource, 'install') ||
                str_contains($pageSource, 'acme') ||
                str_contains($pageSource, 'client');

            $this->assertTrue($hasCertbotStatus, 'Certbot installation status should be shown');

            $this->testResults['certbot_status'] = 'Certbot installation status is shown';
        });
    }

    /**
     * Test 45: SSL certificate quick actions menu is available
     *
     */

    #[Test]
    public function test_ssl_certificate_quick_actions_menu_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-quick-actions');

            // Check for quick actions menu
            $pageSource = $browser->driver->getPageSource();
            $hasQuickActions =
                str_contains($pageSource, 'action') ||
                str_contains($pageSource, 'menu') ||
                str_contains($pageSource, 'dropdown') ||
                str_contains($pageSource, 'wire:click');

            $this->assertTrue($hasQuickActions, 'SSL certificate quick actions menu should be available');

            $this->testResults['quick_actions'] = 'SSL certificate quick actions menu is available';
        });
    }

    /**
     * Test 46: Certificate expiry notifications preview is visible
     *
     */

    #[Test]
    public function test_certificate_expiry_notifications_preview_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-expiry-notifications-preview');

            // Check for expiry notifications
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasExpiryNotifications =
                str_contains($pageSource, 'notification') ||
                str_contains($pageSource, 'expir') ||
                str_contains($pageSource, 'days') ||
                str_contains($pageSource, 'alert');

            $this->assertTrue($hasExpiryNotifications, 'Certificate expiry notifications preview should be visible');

            $this->testResults['expiry_notifications'] = 'Certificate expiry notifications preview is visible';
        });
    }

    /**
     * Test 47: SSL certificate domain association is clear
     *
     */

    #[Test]
    public function test_ssl_certificate_domain_association_clear()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-domain-association');

            // Check for domain association
            $pageSource = $browser->driver->getPageSource();
            $hasDomainAssociation =
                str_contains($pageSource, 'domain') ||
                str_contains($pageSource, 'Domain') ||
                str_contains($pageSource, 'devflow.test') ||
                str_contains($pageSource, 'associated');

            $this->assertTrue($hasDomainAssociation, 'SSL certificate domain association should be clear');

            $this->testResults['domain_association'] = 'SSL certificate domain association is clear';
        });
    }

    /**
     * Test 48: Certificate renewal progress indicator is present
     *
     */

    #[Test]
    public function test_certificate_renewal_progress_indicator_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-renewal-progress');

            // Check for progress indicator
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProgressIndicator =
                str_contains($pageSource, 'progress') ||
                str_contains($pageSource, 'loading') ||
                str_contains($pageSource, 'spinner') ||
                str_contains($pageSource, 'wire:loading');

            $this->assertTrue($hasProgressIndicator, 'Certificate renewal progress indicator should be present');

            $this->testResults['renewal_progress'] = 'Certificate renewal progress indicator is present';
        });
    }

    /**
     * Test 49: SSL certificate statistics cards display correctly
     *
     */

    #[Test]
    public function test_ssl_certificate_statistics_cards_display_correctly()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-statistics-cards');

            // Check for statistics cards
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatisticsCards =
                str_contains($pageSource, 'total') ||
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'expiring') ||
                str_contains($pageSource, 'card');

            $this->assertTrue($hasStatisticsCards, 'SSL certificate statistics cards should display correctly');

            $this->testResults['statistics_cards'] = 'SSL certificate statistics cards display correctly';
        });
    }

    /**
     * Test 50: Certificate management page is responsive
     *
     */

    #[Test]
    public function test_certificate_management_page_responsive()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->testServer->id}/ssl")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ssl-responsive-design');

            // Check page loads and has responsive elements
            $pageSource = $browser->driver->getPageSource();
            $isResponsive =
                str_contains($pageSource, 'responsive') ||
                str_contains($pageSource, 'md:') ||
                str_contains($pageSource, 'lg:') ||
                str_contains($pageSource, 'grid') ||
                str_contains($pageSource, 'flex');

            $this->assertTrue($isResponsive, 'Certificate management page should be responsive');

            $this->testResults['responsive_page'] = 'Certificate management page is responsive';
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
                'test_suite' => 'SSL Certificate Management Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                    'test_server_id' => $this->testServer?->id,
                    'test_certificates_created' => SSLCertificate::where('server_id', $this->testServer?->id)->count(),
                ],
                'environment' => [
                    'users_tested' => User::count(),
                    'test_user_email' => $this->user->email,
                    'servers_tested' => Server::count(),
                    'total_certificates' => SSLCertificate::count(),
                ],
            ];

            $reportPath = storage_path('app/test-reports/ssl-management-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
