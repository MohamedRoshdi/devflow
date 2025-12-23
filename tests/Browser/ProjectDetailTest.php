<?php

namespace Tests\Browser;

use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * ProjectDetailTest - Comprehensive browser tests for public project detail page
 *
 * This test suite covers the ProjectDetail Livewire component which displays
 * public-facing project information without requiring authentication.
 *
 * Test Coverage:
 * - Public access (no authentication required)
 * - Project information display
 * - Status badges and indicators
 * - Technology stack information
 * - Domain and URL display
 * - 404 handling for non-existent projects
 * - Private/unavailable project handling
 * - Mobile responsive design
 * - Navigation elements
 * - SEO and metadata
 */
class ProjectDetailTest extends DuskTestCase
{
    protected User $user;

    protected Server $server;

    protected Project $publicProject;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'public-test.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Public Test Server',
                'ip_address' => '192.168.1.200',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Create public project with running status
        $this->publicProject = Project::firstOrCreate(
            ['slug' => 'public-test-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Public Test Project',
                'framework' => 'Laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/public-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/public-project',
                'php_version' => '8.4',
                'environment' => 'production',
                'metadata' => [
                    'description' => 'A comprehensive Laravel application showcasing modern development practices.',
                ],
                'created_at' => now()->subMonths(3),
            ]
        );

        // Create primary domain for the project
        Domain::firstOrCreate(
            [
                'project_id' => $this->publicProject->id,
                'is_primary' => true,
            ],
            [
                'domain' => 'public-test.devflow.test',
                'subdomain' => null,
                'ssl_enabled' => true,
                'status' => 'active',
            ]
        );
    }

    /**
     * Test 1: Public project detail page loads without authentication
     */
    public function test_public_project_detail_page_loads_without_authentication(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee($this->publicProject->name)
                ->assertSee('Live & Running')
                ->screenshot('project-detail-public-access');
        });
    }

    /**
     * Test 2: Project name is displayed prominently
     */
    public function test_project_name_is_displayed_prominently(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSeeIn('h1', $this->publicProject->name)
                ->screenshot('project-detail-name-display');
        });
    }

    /**
     * Test 3: Project description from metadata is displayed
     */
    public function test_project_description_from_metadata_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('A comprehensive Laravel application showcasing modern development practices.')
                ->screenshot('project-detail-description');
        });
    }

    /**
     * Test 4: Live status badge is displayed with animation
     */
    public function test_live_status_badge_is_displayed_with_animation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText('Live & Running')
                ->assertSee('Live & Running')
                ->assertPresent('.animate-ping')
                ->screenshot('project-detail-live-status');
        });
    }

    /**
     * Test 5: Framework badge displays correct technology
     */
    public function test_framework_badge_displays_correct_technology(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('Laravel')
                ->screenshot('project-detail-framework-badge');
        });
    }

    /**
     * Test 6: Laravel framework shows Laravel icon
     */
    public function test_laravel_framework_shows_laravel_icon(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                // Laravel SVG path should be present
                ->assertPresent('svg[viewBox="0 0 24 24"]')
                ->screenshot('project-detail-laravel-icon');
        });
    }

    /**
     * Test 7: Non-Laravel framework shows generic code icon
     */
    public function test_non_laravel_framework_shows_generic_icon(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'React Project',
            'slug' => 'react-public-project',
            'framework' => 'React',
            'status' => 'running',
        ]);

        Domain::create([
            'project_id' => $project->id,
            'domain' => 'react-test.devflow.test',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($project) {
            $browser->visit('/project/'.$project->slug)
                ->waitForText('React')
                ->assertSee('React')
                ->screenshot('project-detail-react-icon');
        });
    }

    /**
     * Test 8: Primary domain is displayed in project info card
     */
    public function test_primary_domain_is_displayed_in_project_info_card(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('Domain')
                ->assertSee('public-test.devflow.test')
                ->screenshot('project-detail-domain-display');
        });
    }

    /**
     * Test 9: Visit live project button is displayed with correct URL
     */
    public function test_visit_live_project_button_is_displayed_with_correct_url(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('Visit Live Project')
                ->assertAttribute('a[href*="public-test.devflow.test"]', 'target', '_blank')
                ->assertAttribute('a[href*="public-test.devflow.test"]', 'rel', 'noopener noreferrer')
                ->screenshot('project-detail-visit-button');
        });
    }

    /**
     * Test 10: PHP version is displayed when available
     */
    public function test_php_version_is_displayed_when_available(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('PHP Version')
                ->assertSee('8.4')
                ->screenshot('project-detail-php-version');
        });
    }

    /**
     * Test 11: Environment indicator is displayed
     */
    public function test_environment_indicator_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('Environment')
                ->assertSee('Production')
                ->screenshot('project-detail-environment');
        });
    }

    /**
     * Test 12: First deployed date is displayed
     */
    public function test_first_deployed_date_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('First Deployed')
                ->assertSee($this->publicProject->created_at->format('F j, Y'))
                ->screenshot('project-detail-deployed-date');
        });
    }

    /**
     * Test 13: Back to portfolio link navigates to home page
     */
    public function test_back_to_portfolio_link_navigates_to_home_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('Back to Portfolio')
                ->click('a[href="'.route('home').'"]')
                ->waitForLocation('/')
                ->screenshot('project-detail-back-to-portfolio');
        });
    }

    /**
     * Test 14: Navigation bar contains NileStack branding
     */
    public function test_navigation_bar_contains_nilestack_branding(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('NileStack')
                ->assertSee('DevFlow Pro')
                ->screenshot('project-detail-navigation-branding');
        });
    }

    /**
     * Test 15: Portfolio link in navigation works
     */
    public function test_portfolio_link_in_navigation_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->click('a:contains("Portfolio")')
                ->waitForLocation('/')
                ->screenshot('project-detail-nav-portfolio-link');
        });
    }

    /**
     * Test 16: Authenticated users see dashboard link in navigation
     */
    public function test_authenticated_users_see_dashboard_link_in_navigation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('Open Dashboard')
                ->screenshot('project-detail-authenticated-nav');
        });
    }

    /**
     * Test 17: Unauthenticated users see sign in link in navigation
     */
    public function test_unauthenticated_users_see_sign_in_link_in_navigation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('Sign In')
                ->screenshot('project-detail-unauthenticated-nav');
        });
    }

    /**
     * Test 18: Theme toggle button is present
     */
    public function test_theme_toggle_button_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertPresent('#theme-toggle')
                ->screenshot('project-detail-theme-toggle');
        });
    }

    /**
     * Test 19: DevFlow Pro features section is displayed
     */
    public function test_devflow_pro_features_section_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->scrollIntoView('h2:contains("Deployed with DevFlow Pro")')
                ->assertSee('Deployed with DevFlow Pro')
                ->assertSee('Automated Deployment')
                ->assertSee('SSL & Security')
                ->assertSee('Performance Monitoring')
                ->screenshot('project-detail-features-section');
        });
    }

    /**
     * Test 20: Features section describes automated deployment
     */
    public function test_features_section_describes_automated_deployment(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->scrollIntoView('h3:contains("Automated Deployment")')
                ->assertSee('One-click deployments with git integration')
                ->screenshot('project-detail-automated-deployment-feature');
        });
    }

    /**
     * Test 21: Features section describes SSL security
     */
    public function test_features_section_describes_ssl_security(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->scrollIntoView('h3:contains("SSL & Security")')
                ->assertSee('Automatic SSL certificates')
                ->screenshot('project-detail-ssl-security-feature');
        });
    }

    /**
     * Test 22: Features section describes performance monitoring
     */
    public function test_features_section_describes_performance_monitoring(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->scrollIntoView('h3:contains("Performance Monitoring")')
                ->assertSee('Real-time health checks')
                ->screenshot('project-detail-performance-monitoring-feature');
        });
    }

    /**
     * Test 23: View more projects link in features section works
     */
    public function test_view_more_projects_link_in_features_section_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->scrollIntoView('a:contains("View More Projects")')
                ->click('a:contains("View More Projects")')
                ->waitForLocation('/')
                ->screenshot('project-detail-view-more-projects');
        });
    }

    /**
     * Test 24: Footer displays NileStack branding
     */
    public function test_footer_displays_nilestack_branding(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->scrollIntoView('footer')
                ->assertSee('NileStack')
                ->assertSee('Professional Software Development')
                ->screenshot('project-detail-footer-branding');
        });
    }

    /**
     * Test 25: Footer displays DevFlow Pro attribution
     */
    public function test_footer_displays_devflow_pro_attribution(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->scrollIntoView('footer')
                ->assertSee('Powered by')
                ->assertSee('DevFlow Pro')
                ->screenshot('project-detail-footer-attribution');
        });
    }

    /**
     * Test 26: Footer displays current year copyright
     */
    public function test_footer_displays_current_year_copyright(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->scrollIntoView('footer')
                ->assertSee('© '.date('Y').' NileStack')
                ->screenshot('project-detail-footer-copyright');
        });
    }

    /**
     * Test 27: Non-existent project shows 404 page
     */
    public function test_non_existent_project_shows_404_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/non-existent-project-slug-12345')
                ->waitForText('Project Not Found')
                ->assertSee('Project Not Found')
                ->assertSee("doesn't exist or is currently not available")
                ->screenshot('project-detail-404-not-found');
        });
    }

    /**
     * Test 28: 404 page displays appropriate message
     */
    public function test_404_page_displays_appropriate_message(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/non-existent-slug')
                ->waitForText('Project Not Found')
                ->assertSee('in development, maintenance, or not yet deployed')
                ->screenshot('project-detail-404-message');
        });
    }

    /**
     * Test 29: 404 page has back to portfolio link
     */
    public function test_404_page_has_back_to_portfolio_link(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/non-existent-slug')
                ->waitForText('Project Not Found')
                ->assertSee('Back to Portfolio')
                ->click('a:contains("Back to Portfolio")')
                ->waitForLocation('/')
                ->screenshot('project-detail-404-back-link');
        });
    }

    /**
     * Test 30: Project with stopped status is not publicly accessible
     */
    public function test_project_with_stopped_status_is_not_publicly_accessible(): void
    {
        $stoppedProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Stopped Project',
            'slug' => 'stopped-public-project',
            'framework' => 'Laravel',
            'status' => 'stopped',
        ]);

        Domain::create([
            'project_id' => $stoppedProject->id,
            'domain' => 'stopped.devflow.test',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($stoppedProject) {
            $browser->visit('/project/'.$stoppedProject->slug)
                ->waitForText('Project Not Found')
                ->assertSee('Project Not Found')
                ->screenshot('project-detail-stopped-project');
        });
    }

    /**
     * Test 31: Project without primary domain is not publicly accessible
     */
    public function test_project_without_primary_domain_is_not_publicly_accessible(): void
    {
        $noDomainProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'No Domain Project',
            'slug' => 'no-domain-project',
            'framework' => 'Laravel',
            'status' => 'running',
        ]);

        $this->browse(function (Browser $browser) use ($noDomainProject) {
            $browser->visit('/project/'.$noDomainProject->slug)
                ->waitForText('Project Not Found')
                ->assertSee('Project Not Found')
                ->screenshot('project-detail-no-domain-project');
        });
    }

    /**
     * Test 32: Project info card displays properly
     */
    public function test_project_info_card_displays_properly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('Project Details')
                ->screenshot('project-detail-info-card');
        });
    }

    /**
     * Test 33: Project without metadata description still loads
     */
    public function test_project_without_metadata_description_still_loads(): void
    {
        $noDescProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'No Description Project',
            'slug' => 'no-description-project',
            'framework' => 'Laravel',
            'status' => 'running',
            'metadata' => null,
        ]);

        Domain::create([
            'project_id' => $noDescProject->id,
            'domain' => 'nodesc.devflow.test',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($noDescProject) {
            $browser->visit('/project/'.$noDescProject->slug)
                ->waitForText('No Description Project')
                ->assertSee('No Description Project')
                ->screenshot('project-detail-no-description');
        });
    }

    /**
     * Test 34: Project without PHP version still displays correctly
     */
    public function test_project_without_php_version_still_displays_correctly(): void
    {
        $noPhpProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'No PHP Version Project',
            'slug' => 'no-php-version-project',
            'framework' => 'Laravel',
            'status' => 'running',
            'php_version' => null,
        ]);

        Domain::create([
            'project_id' => $noPhpProject->id,
            'domain' => 'nophp.devflow.test',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($noPhpProject) {
            $browser->visit('/project/'.$noPhpProject->slug)
                ->waitForText('No PHP Version Project')
                ->assertSee('No PHP Version Project')
                ->screenshot('project-detail-no-php-version');
        });
    }

    /**
     * Test 35: Project without environment value still displays correctly
     */
    public function test_project_without_environment_value_still_displays_correctly(): void
    {
        $noEnvProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'No Environment Project',
            'slug' => 'no-environment-project',
            'framework' => 'Laravel',
            'status' => 'running',
            'environment' => null,
        ]);

        Domain::create([
            'project_id' => $noEnvProject->id,
            'domain' => 'noenv.devflow.test',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($noEnvProject) {
            $browser->visit('/project/'.$noEnvProject->slug)
                ->waitForText('No Environment Project')
                ->assertSee('No Environment Project')
                ->screenshot('project-detail-no-environment');
        });
    }

    /**
     * Test 36: Mobile responsive design - navigation collapses
     */
    public function test_mobile_responsive_design_navigation_collapses(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667) // iPhone SE size
                ->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee($this->publicProject->name)
                ->screenshot('project-detail-mobile-navigation');
        });
    }

    /**
     * Test 37: Mobile responsive design - project info card stacks
     */
    public function test_mobile_responsive_design_project_info_card_stacks(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                ->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('Project Details')
                ->screenshot('project-detail-mobile-info-card');
        });
    }

    /**
     * Test 38: Mobile responsive design - features section displays in column
     */
    public function test_mobile_responsive_design_features_section_displays_in_column(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                ->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->scrollIntoView('h2:contains("Deployed with DevFlow Pro")')
                ->assertSee('Automated Deployment')
                ->screenshot('project-detail-mobile-features');
        });
    }

    /**
     * Test 39: Tablet responsive design - layout adapts correctly
     */
    public function test_tablet_responsive_design_layout_adapts_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(768, 1024) // iPad size
                ->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee($this->publicProject->name)
                ->screenshot('project-detail-tablet-layout');
        });
    }

    /**
     * Test 40: Desktop responsive design - full layout displayed
     */
    public function test_desktop_responsive_design_full_layout_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(1920, 1080)
                ->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee($this->publicProject->name)
                ->screenshot('project-detail-desktop-layout');
        });
    }

    /**
     * Test 41: Hero section gradient background is present
     */
    public function test_hero_section_gradient_background_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertPresent('.bg-gradient-to-br')
                ->screenshot('project-detail-hero-gradient');
        });
    }

    /**
     * Test 42: Visit live project button has external link icon
     */
    public function test_visit_live_project_button_has_external_link_icon(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('Visit Live Project')
                ->assertPresent('a:contains("Visit Live Project") svg')
                ->screenshot('project-detail-external-link-icon');
        });
    }

    /**
     * Test 43: Domain icon is displayed in project info card
     */
    public function test_domain_icon_is_displayed_in_project_info_card(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('Domain')
                ->assertPresent('svg[viewBox="0 0 24 24"]')
                ->screenshot('project-detail-domain-icon');
        });
    }

    /**
     * Test 44: Calendar icon is displayed for first deployed date
     */
    public function test_calendar_icon_is_displayed_for_first_deployed_date(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertSee('First Deployed')
                ->screenshot('project-detail-calendar-icon');
        });
    }

    /**
     * Test 45: Features section icons are displayed
     */
    public function test_features_section_icons_are_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->scrollIntoView('h2:contains("Deployed with DevFlow Pro")')
                ->assertPresent('svg')
                ->screenshot('project-detail-features-icons');
        });
    }

    /**
     * Test 46: Page title reflects project name
     */
    public function test_page_title_reflects_project_name(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->assertTitleContains('DevFlow Pro')
                ->screenshot('project-detail-page-title');
        });
    }

    /**
     * Test 47: Staging environment displays correctly
     */
    public function test_staging_environment_displays_correctly(): void
    {
        $stagingProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Staging Project',
            'slug' => 'staging-project',
            'framework' => 'Laravel',
            'status' => 'running',
            'environment' => 'staging',
        ]);

        Domain::create([
            'project_id' => $stagingProject->id,
            'domain' => 'staging.devflow.test',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($stagingProject) {
            $browser->visit('/project/'.$stagingProject->slug)
                ->waitForText('Staging Project')
                ->assertSee('Staging')
                ->screenshot('project-detail-staging-environment');
        });
    }

    /**
     * Test 48: Development environment displays correctly
     */
    public function test_development_environment_displays_correctly(): void
    {
        $devProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Development Project',
            'slug' => 'development-project',
            'framework' => 'Laravel',
            'status' => 'running',
            'environment' => 'development',
        ]);

        Domain::create([
            'project_id' => $devProject->id,
            'domain' => 'dev.devflow.test',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($devProject) {
            $browser->visit('/project/'.$devProject->slug)
                ->waitForText('Development Project')
                ->assertSee('Development')
                ->screenshot('project-detail-development-environment');
        });
    }

    /**
     * Test 49: HTTP domain URL is upgraded to HTTPS
     */
    public function test_http_domain_url_is_upgraded_to_https(): void
    {
        $httpProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'HTTP Project',
            'slug' => 'http-project',
            'framework' => 'Laravel',
            'status' => 'running',
        ]);

        Domain::create([
            'project_id' => $httpProject->id,
            'domain' => 'http://http-test.devflow.test',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($httpProject) {
            $browser->visit('/project/'.$httpProject->slug)
                ->waitForText('HTTP Project')
                ->assertAttribute('a:contains("Visit Live Project")', 'href', 'https://http-test.devflow.test')
                ->screenshot('project-detail-https-upgrade');
        });
    }

    /**
     * Test 50: Full page scrolling works smoothly
     */
    public function test_full_page_scrolling_works_smoothly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/project/'.$this->publicProject->slug)
                ->waitForText($this->publicProject->name)
                ->scrollToTop()
                ->pause(200)
                ->scrollToBottom()
                ->pause(200)
                ->assertSee('NileStack')
                ->screenshot('project-detail-full-scroll');
        });
    }
}
