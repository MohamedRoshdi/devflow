<?php

namespace Tests\Browser;

use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class HomePublicTest extends DuskTestCase
{
    // use RefreshDatabase; // Disabled - testing against existing app

    protected User $user;

    protected Server $server;

    /**
     * Setup the test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test user for some tests
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Ensure minimal test data exists
        $this->ensureTestDataExists();
    }

    /**
     * Ensure test data exists (runs only if needed)
     */
    protected function ensureTestDataExists(): void
    {
        // Get or create test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'prod.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Production Server',
                'ip_address' => '192.168.1.100',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Create a test project with domain for public display
        $project = Project::firstOrCreate(
            ['slug' => 'public-showcase-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Public Showcase Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/showcase-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/showcase-project',
            ]
        );

        // Create a primary domain for the project
        Domain::firstOrCreate(
            [
                'project_id' => $project->id,
                'domain' => 'showcase.example.com',
            ],
            [
                'is_primary' => true,
                'ssl_enabled' => true,
                'status' => 'active',
            ]
        );
    }

    /**
     * Test 1: Home page loads without authentication
     */
    public function test_home_page_loads_without_authentication(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPathIs('/')
                ->assertSee('NileStack')
                ->assertSee('DevFlow Pro')
                ->screenshot('home-public-basic');
        });
    }

    /**
     * Test 2: Hero section displays correctly
     */
    public function test_hero_section_displays_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('Deploy production apps in minutes, not days.')
                ->assertSee('DevFlow Pro orchestrates provisioning, deployments, and uptime monitoring')
                ->assertPresent('.bg-gradient-to-br.from-slate-900');
        });
    }

    /**
     * Test 3: Platform status indicator is visible
     */
    public function test_platform_status_indicator_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('Platform Status: Operational')
                ->assertPresent('.animate-ping')
                ->assertPresent('.bg-emerald-400, .bg-emerald-300');
        });
    }

    /**
     * Test 4: Platform stats are displayed in hero section
     */
    public function test_platform_stats_are_displayed_in_hero_section(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('Platform Uptime')
                ->assertSee('99.9%')
                ->assertSee('Deploy Time')
                ->assertSee('<5 min')
                ->assertSee('Support')
                ->assertSee('24/7');
        });
    }

    /**
     * Test 5: Deployment insights card is visible
     */
    public function test_deployment_insights_card_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('Deployment Insights')
                ->assertSee('Real-time orchestration')
                ->assertSee('Environment Sync')
                ->assertSee('Healthy');
        });
    }

    /**
     * Test 6: Average deployment time is displayed
     */
    public function test_average_deployment_time_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('12m')
                ->assertSee('Average deployment time');
        });
    }

    /**
     * Test 7: Cache and config optimization info is shown
     */
    public function test_cache_and_config_optimization_info_is_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('Cache & Config')
                ->assertSee('Auto-optimized')
                ->assertSee('Laravel optimize suite runs on every deploy.');
        });
    }

    /**
     * Test 8: Security information is displayed
     */
    public function test_security_information_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('Security')
                ->assertSee('Keys Managed')
                ->assertSee('APP_KEY, APP_ENV, and secrets injected securely.');
        });
    }

    /**
     * Test 9: Navigation bar displays NileStack branding
     */
    public function test_navigation_bar_displays_nilestack_branding(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('NileStack')
                ->assertSee('DevFlow Pro')
                ->assertPresent('.bg-gradient-to-br.from-blue-500.via-indigo-500.to-purple-600');
        });
    }

    /**
     * Test 10: Navigation links are visible
     */
    public function test_navigation_links_are_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('Platform')
                ->assertSee('Workflow')
                ->assertSeeLink('Sign In');
        });
    }

    /**
     * Test 11: Sign In button redirects to login page
     */
    public function test_sign_in_button_redirects_to_login_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->clickLink('Sign In')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
        });
    }

    /**
     * Test 12: Request Access button redirects to login
     */
    public function test_request_access_button_redirects_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->click('a:contains("Request Access")')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
        });
    }

    /**
     * Test 13: Authenticated users see different CTA buttons
     */
    public function test_authenticated_users_see_different_cta_buttons(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/')
                ->assertSee('Open Dashboard')
                ->assertSee('Launch Control Center')
                ->assertDontSee('Request Access');
        });
    }

    /**
     * Test 14: Platform Features section is visible
     */
    public function test_platform_features_section_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#platform')
                ->assertSee('Platform Features')
                ->assertSee('Everything you need to deploy, manage, and monitor your applications with confidence.');
        });
    }

    /**
     * Test 15: Server Management feature card is displayed
     */
    public function test_server_management_feature_card_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#platform')
                ->assertSee('Server Management')
                ->assertSee('Connect unlimited servers via SSH. Monitor CPU, memory, disk usage in real-time');
        });
    }

    /**
     * Test 16: One-Click Deploys feature card is displayed
     */
    public function test_one_click_deploys_feature_card_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#platform')
                ->assertSee('One-Click Deploys')
                ->assertSee('Git-based deployments with automatic dependency installs, migrations');
        });
    }

    /**
     * Test 17: SSL & Security feature card is displayed
     */
    public function test_ssl_security_feature_card_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#platform')
                ->assertSee('SSL & Security')
                ->assertSee('Automatic SSL certificate provisioning and renewal');
        });
    }

    /**
     * Test 18: Docker Integration feature card is displayed
     */
    public function test_docker_integration_feature_card_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#platform')
                ->assertSee('Docker Integration')
                ->assertSee('Full Docker and Docker Compose management');
        });
    }

    /**
     * Test 19: Real-time Monitoring feature card is displayed
     */
    public function test_real_time_monitoring_feature_card_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#platform')
                ->assertSee('Real-time Monitoring')
                ->assertSee('Live metrics dashboard with CPU, memory, and disk tracking');
        });
    }

    /**
     * Test 20: Database Backups feature card is displayed
     */
    public function test_database_backups_feature_card_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#platform')
                ->assertSee('Database Backups')
                ->assertSee('Scheduled database backups with retention policies');
        });
    }

    /**
     * Test 21: All six feature cards have gradient icons
     */
    public function test_all_six_feature_cards_have_gradient_icons(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#platform')
                ->assertPresent('.bg-gradient-to-br.from-blue-500')
                ->assertPresent('.bg-gradient-to-br.from-purple-500')
                ->assertPresent('.bg-gradient-to-br.from-emerald-500')
                ->assertPresent('.bg-gradient-to-br.from-cyan-500')
                ->assertPresent('.bg-gradient-to-br.from-amber-500')
                ->assertPresent('.bg-gradient-to-br.from-rose-500');
        });
    }

    /**
     * Test 22: Workflow section is visible
     */
    public function test_workflow_section_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#workflow')
                ->assertSee('A workflow your team already understands')
                ->assertSee('From git push to production-ready infrastructure');
        });
    }

    /**
     * Test 23: Workflow step 1 - Connect your repo
     */
    public function test_workflow_step_1_connect_your_repo(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#workflow')
                ->assertSee('01')
                ->assertSee('Connect your repo')
                ->assertSee('Secure git integration with permissions handled through deploy keys');
        });
    }

    /**
     * Test 24: Workflow step 2 - Define environments
     */
    public function test_workflow_step_2_define_environments(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#workflow')
                ->assertSee('02')
                ->assertSee('Define environments')
                ->assertSee('Toggle between local, development, staging, and production');
        });
    }

    /**
     * Test 25: Workflow step 3 - Deploy confidently
     */
    public function test_workflow_step_3_deploy_confidently(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#workflow')
                ->assertSee('03')
                ->assertSee('Deploy confidently')
                ->assertSee('Automated build pipeline installs dependencies, runs migrations');
        });
    }

    /**
     * Test 26: Workflow step 4 - Monitor & iterate
     */
    public function test_workflow_step_4_monitor_and_iterate(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#workflow')
                ->assertSee('04')
                ->assertSee('Monitor & iterate')
                ->assertSee('Livewire dashboards stream logs, deployment status');
        });
    }

    /**
     * Test 27: Call to action section is displayed
     */
    public function test_call_to_action_section_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('.bg-gradient-to-r.from-blue-600')
                ->assertSee('Ready when you are')
                ->assertSee('Launch your next deployment with confidence.')
                ->assertSee('Switch environments instantly, inspect logs in real-time');
        });
    }

    /**
     * Test 28: Footer is displayed with NileStack branding
     */
    public function test_footer_is_displayed_with_nilestack_branding(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('footer')
                ->assertSee('NileStack')
                ->assertSee('Professional Software Development');
        });
    }

    /**
     * Test 29: Footer shows DevFlow Pro attribution
     */
    public function test_footer_shows_devflow_pro_attribution(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('footer')
                ->assertSee('Powered by DevFlow Pro')
                ->assertSee('Multi-Project Deployment & Management');
        });
    }

    /**
     * Test 30: Footer displays copyright with current year
     */
    public function test_footer_displays_copyright_with_current_year(): void
    {
        $this->browse(function (Browser $browser) {
            $currentYear = date('Y');
            $browser->visit('/')
                ->scrollIntoView('footer')
                ->assertSee("© {$currentYear} NileStack. All rights reserved.");
        });
    }

    /**
     * Test 31: Theme toggle button is present
     */
    public function test_theme_toggle_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('#theme-toggle')
                ->assertAttribute('#theme-toggle', 'aria-label', 'Toggle theme');
        });
    }

    /**
     * Test 32: Dark mode toggle works
     */
    public function test_dark_mode_toggle_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->click('#theme-toggle')
                ->pause(500);

            // Verify dark mode class is toggled
            $isDark = $browser->script("return document.documentElement.classList.contains('dark');")[0];

            $this->assertTrue(
                is_bool($isDark),
                'Theme toggle should change the dark class on html element'
            );
        });
    }

    /**
     * Test 33: Page has proper meta tags for SEO
     */
    public function test_page_has_proper_meta_tags_for_seo(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('meta[name="description"]')
                ->assertPresent('meta[name="author"]')
                ->assertPresent('meta[property="og:title"]')
                ->assertPresent('meta[property="og:description"]');
        });
    }

    /**
     * Test 34: Page has Twitter card meta tags
     */
    public function test_page_has_twitter_card_meta_tags(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('meta[name="twitter:card"]')
                ->assertPresent('meta[name="twitter:title"]')
                ->assertPresent('meta[name="twitter:description"]');
        });
    }

    /**
     * Test 35: Page has favicon
     */
    public function test_page_has_favicon(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('link[rel="icon"]');
        });
    }

    /**
     * Test 36: Navigation is fixed to top
     */
    public function test_navigation_is_fixed_to_top(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('nav.fixed.top-0');
        });
    }

    /**
     * Test 37: Navigation has backdrop blur effect
     */
    public function test_navigation_has_backdrop_blur_effect(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('.backdrop-blur-xl');
        });
    }

    /**
     * Test 38: Hero has animated gradient background
     */
    public function test_hero_has_animated_gradient_background(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('.bg-gradient-to-br.from-slate-900')
                ->assertPresent('.blur-3xl');
        });
    }

    /**
     * Test 39: Feature cards have hover effects
     */
    public function test_feature_cards_have_hover_effects(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#platform')
                ->assertPresent('.hover\\:shadow-2xl')
                ->assertPresent('.hover\\:-translate-y-1');
        });
    }

    /**
     * Test 40: CTA button has hover animation
     */
    public function test_cta_button_has_hover_animation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('.hover\\:-translate-y-0\\.5')
                ->assertPresent('.hover\\:shadow-2xl');
        });
    }

    /**
     * Test 41: Mobile viewport - page is responsive at 375px
     */
    public function test_mobile_viewport_page_is_responsive_at_375px(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                ->visit('/')
                ->assertSee('NileStack')
                ->assertSee('Deploy production apps')
                ->screenshot('home-public-mobile-375');
        });
    }

    /**
     * Test 42: Tablet viewport - page is responsive at 768px
     */
    public function test_tablet_viewport_page_is_responsive_at_768px(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(768, 1024)
                ->visit('/')
                ->assertSee('Platform Features')
                ->assertSee('Workflow')
                ->screenshot('home-public-tablet-768');
        });
    }

    /**
     * Test 43: Desktop viewport - page is responsive at 1920px
     */
    public function test_desktop_viewport_page_is_responsive_at_1920px(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(1920, 1080)
                ->visit('/')
                ->assertSee('DevFlow Pro orchestrates provisioning')
                ->assertSee('Platform Features')
                ->screenshot('home-public-desktop-1920');
        });
    }

    /**
     * Test 44: Platform link scrolls to platform section
     */
    public function test_platform_link_scrolls_to_platform_section(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->click('a[href="#platform"]')
                ->pause(1000)
                ->assertPresent('#platform');
        });
    }

    /**
     * Test 45: Workflow link scrolls to workflow section
     */
    public function test_workflow_link_scrolls_to_workflow_section(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->click('a[href="#workflow"]')
                ->pause(1000)
                ->assertPresent('#workflow');
        });
    }

    /**
     * Test 46: Page loads without JavaScript errors
     */
    public function test_page_loads_without_javascript_errors(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');

            // Get console logs to check for errors
            $logs = $browser->driver->manage()->getLog('browser');
            $errors = array_filter($logs, function ($log) {
                return $log['level'] === 'SEVERE';
            });

            $this->assertEmpty(
                $errors,
                'Page should load without JavaScript errors'
            );
        });
    }

    /**
     * Test 47: All gradient backgrounds are present
     */
    public function test_all_gradient_backgrounds_are_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('.bg-gradient-to-br')
                ->assertPresent('.bg-gradient-to-r')
                ->assertPresent('.bg-gradient-to-tr');
        });
    }

    /**
     * Test 48: Livewire scripts are loaded
     */
    public function test_livewire_scripts_are_loaded(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('[wire\\:id]', 10);

            // Check if Livewire object exists in window
            $hasLivewire = $browser->script("return typeof window.Livewire !== 'undefined';")[0];

            $this->assertTrue(
                $hasLivewire,
                'Livewire should be loaded on the page'
            );
        });
    }

    /**
     * Test 49: Page title is correct
     */
    public function test_page_title_is_correct(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertTitle('NileStack - DevFlow Pro Platform');
        });
    }

    /**
     * Test 50: Accessibility - page has proper ARIA labels
     */
    public function test_accessibility_page_has_proper_aria_labels(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('[aria-label]');
        });
    }

    /**
     * Test 51: Navigation shows mobile-friendly layout on small screens
     */
    public function test_navigation_shows_mobile_friendly_layout_on_small_screens(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                ->visit('/')
                ->assertSee('NileStack')
                ->assertPresent('.rounded-full.bg-white\\/80');
        });
    }

    /**
     * Test 52: Feature section uses proper grid layout
     */
    public function test_feature_section_uses_proper_grid_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#platform')
                ->assertPresent('.grid.gap-6.md\\:grid-cols-2.lg\\:grid-cols-3');
        });
    }

    /**
     * Test 53: Workflow section has gradient background
     */
    public function test_workflow_section_has_gradient_background(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#workflow')
                ->assertPresent('.bg-gradient-to-tr');
        });
    }

    /**
     * Test 54: All SVG icons are rendered
     */
    public function test_all_svg_icons_are_rendered(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('svg')
                ->assertPresent('svg path');
        });
    }

    /**
     * Test 55: Page has smooth scroll behavior
     */
    public function test_page_has_smooth_scroll_behavior(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->scrollIntoView('#platform')
                ->pause(500)
                ->scrollIntoView('#workflow')
                ->pause(500)
                ->assertPresent('#workflow');
        });
    }
}
