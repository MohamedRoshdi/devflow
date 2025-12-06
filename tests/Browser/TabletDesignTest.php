<?php

namespace Tests\Browser;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

/**
 * Comprehensive Tablet Responsive Design Test Suite
 * Tests tablet viewport sizes (768px-1024px) for DevFlow Pro
 *
 * Tablet Sizes Covered:
 * - iPad Mini (768x1024)
 * - iPad Air (820x1180)
 * - iPad Pro (1024x1366)
 * - Surface Pro 7 (912x1368)
 * - Samsung Galaxy Tab (800x1280)
 */
class TabletDesignTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

    protected Project $project;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create or get test user
        $this->user = User::firstOrCreate(
            ['email' => 'tablet-test@devflow.test'],
            [
                'name' => 'Tablet Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Ensure test data exists
        $this->ensureTestDataExists();
    }

    /**
     * Ensure minimal test data exists
     */
    protected function ensureTestDataExists(): void
    {
        $this->server = Server::firstOrCreate(
            ['hostname' => 'tablet-test.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Tablet Test Server',
                'ip_address' => '192.168.1.200',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        $this->project = Project::firstOrCreate(
            ['slug' => 'tablet-test-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Tablet Test Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/tablet-test.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/tablet-test',
            ]
        );
    }

    // ========================================
    // TABLET VIEWPORT SIZE TESTS
    // ========================================

    /**
     * Test 1: iPad Mini portrait layout (768x1024)
     */
    public function test_tablet_ipad_mini_portrait_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/dashboard')
                ->waitForText('Welcome Back!')
                ->assertVisible('.sidebar')
                ->assertSee('DevFlow Pro')
                ->screenshot('tablet-ipad-mini-portrait');
        });
    }

    /**
     * Test 2: iPad Mini landscape layout (1024x768)
     */
    public function test_tablet_ipad_mini_landscape_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 768)
                ->visit('/dashboard')
                ->waitForText('Welcome Back!')
                ->assertVisible('.sidebar')
                ->assertSee('Quick Actions')
                ->screenshot('tablet-ipad-mini-landscape');
        });
    }

    /**
     * Test 3: iPad Air portrait layout (820x1180)
     */
    public function test_tablet_ipad_air_portrait_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/dashboard')
                ->waitForText('Welcome Back!')
                ->assertPresent('.grid')
                ->assertSee('Total Servers')
                ->screenshot('tablet-ipad-air-portrait');
        });
    }

    /**
     * Test 4: iPad Air landscape layout (1180x820)
     */
    public function test_tablet_ipad_air_landscape_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1180, 820)
                ->visit('/dashboard')
                ->waitForText('Welcome Back!')
                ->assertVisible('.sidebar')
                ->assertSee('Recent Activity')
                ->screenshot('tablet-ipad-air-landscape');
        });
    }

    /**
     * Test 5: iPad Pro portrait layout (1024x1366)
     */
    public function test_tablet_ipad_pro_portrait_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 1366)
                ->visit('/dashboard')
                ->waitForText('Welcome Back!')
                ->assertVisible('.sidebar')
                ->assertSee('Deployment Timeline')
                ->screenshot('tablet-ipad-pro-portrait');
        });
    }

    /**
     * Test 6: iPad Pro landscape layout (1366x1024)
     */
    public function test_tablet_ipad_pro_landscape_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1366, 1024)
                ->visit('/dashboard')
                ->waitForText('Welcome Back!')
                ->assertVisible('.sidebar')
                ->assertSee('Server Health')
                ->screenshot('tablet-ipad-pro-landscape');
        });
    }

    /**
     * Test 7: Surface Pro 7 portrait layout (912x1368)
     */
    public function test_tablet_surface_pro_portrait_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(912, 1368)
                ->visit('/dashboard')
                ->waitForText('Welcome Back!')
                ->assertPresent('.grid')
                ->screenshot('tablet-surface-pro-portrait');
        });
    }

    /**
     * Test 8: Surface Pro 7 landscape layout (1368x912)
     */
    public function test_tablet_surface_pro_landscape_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1368, 912)
                ->visit('/dashboard')
                ->waitForText('Welcome Back!')
                ->assertVisible('.sidebar')
                ->screenshot('tablet-surface-pro-landscape');
        });
    }

    /**
     * Test 9: Samsung Galaxy Tab portrait (800x1280)
     */
    public function test_tablet_galaxy_tab_portrait_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(800, 1280)
                ->visit('/dashboard')
                ->waitForText('Welcome Back!')
                ->assertSee('Total Projects')
                ->screenshot('tablet-galaxy-tab-portrait');
        });
    }

    /**
     * Test 10: Samsung Galaxy Tab landscape (1280x800)
     */
    public function test_tablet_galaxy_tab_landscape_layout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1280, 800)
                ->visit('/dashboard')
                ->waitForText('Welcome Back!')
                ->assertVisible('.sidebar')
                ->screenshot('tablet-galaxy-tab-landscape');
        });
    }

    // ========================================
    // TABLET SIDEBAR VISIBILITY TESTS
    // ========================================

    /**
     * Test 11: Sidebar is visible on tablet portrait
     */
    public function test_tablet_sidebar_visible_in_portrait(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/dashboard')
                ->waitForText('DevFlow Pro')
                ->assertVisible('.sidebar, aside, nav[class*="sidebar"]')
                ->screenshot('tablet-sidebar-portrait');
        });
    }

    /**
     * Test 12: Sidebar is fully visible on tablet landscape
     */
    public function test_tablet_sidebar_fully_visible_in_landscape(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 768)
                ->visit('/dashboard')
                ->waitForText('DevFlow Pro')
                ->assertVisible('.sidebar, aside, nav[class*="sidebar"]')
                ->screenshot('tablet-sidebar-landscape');
        });
    }

    /**
     * Test 13: Sidebar navigation links are accessible on tablet
     */
    public function test_tablet_sidebar_navigation_links_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/dashboard')
                ->waitForText('Dashboard')
                ->assertSeeLink('Dashboard')
                ->assertSeeLink('Servers')
                ->assertSeeLink('Projects')
                ->assertSeeLink('Deployments')
                ->screenshot('tablet-sidebar-links');
        });
    }

    // ========================================
    // TABLET TWO-COLUMN LAYOUT TESTS
    // ========================================

    /**
     * Test 14: Dashboard displays two-column stats grid on tablet
     */
    public function test_tablet_dashboard_two_column_stats_grid(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/dashboard')
                ->waitForText('Total Servers')
                ->assertPresent('.grid')
                ->screenshot('tablet-two-column-stats');
        });
    }

    /**
     * Test 15: Projects grid shows 2-3 columns on tablet
     */
    public function test_tablet_projects_grid_two_three_columns(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/projects')
                ->waitFor('h1')
                ->pause(1000)
                ->screenshot('tablet-projects-grid');
        });
    }

    /**
     * Test 16: Settings page displays two-column form on tablet
     */
    public function test_tablet_settings_two_column_form(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 768)
                ->visit('/settings')
                ->pause(1000)
                ->screenshot('tablet-settings-two-column');
        });
    }

    /**
     * Test 17: Server metrics display in two columns on tablet
     */
    public function test_tablet_server_metrics_two_columns(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(912, 1368)
                ->visit('/servers')
                ->pause(1000)
                ->screenshot('tablet-server-metrics');
        });
    }

    // ========================================
    // TABLET NAVIGATION PATTERN TESTS
    // ========================================

    /**
     * Test 18: Tablet navigation menu works correctly
     */
    public function test_tablet_navigation_menu_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/dashboard')
                ->waitForText('Servers')
                ->clickLink('Servers')
                ->waitForLocation('/servers')
                ->assertPathIs('/servers')
                ->screenshot('tablet-navigation');
        });
    }

    /**
     * Test 19: Breadcrumbs are visible on tablet
     */
    public function test_tablet_breadcrumbs_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/projects')
                ->pause(1000)
                ->screenshot('tablet-breadcrumbs');
        });
    }

    /**
     * Test 20: Top navigation bar fits on tablet
     */
    public function test_tablet_top_navigation_bar_fits(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 768)
                ->visit('/dashboard')
                ->waitForText('DevFlow Pro')
                ->assertVisible('header, .header, nav')
                ->screenshot('tablet-top-nav');
        });
    }

    // ========================================
    // TABLET SPLIT-VIEW COMPATIBILITY TESTS
    // ========================================

    /**
     * Test 21: Deployment details shows split view on tablet
     */
    public function test_tablet_deployment_details_split_view(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 1366)
                ->visit('/deployments')
                ->pause(1000)
                ->screenshot('tablet-deployment-split-view');
        });
    }

    /**
     * Test 22: Log viewer displays with sidebar on tablet
     */
    public function test_tablet_log_viewer_with_sidebar(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(912, 1368)
                ->visit('/logs')
                ->pause(1000)
                ->screenshot('tablet-log-viewer-sidebar');
        });
    }

    /**
     * Test 23: Server details shows split panel layout on tablet
     */
    public function test_tablet_server_details_split_panel(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/servers')
                ->pause(1000)
                ->screenshot('tablet-server-split-panel');
        });
    }

    // ========================================
    // TABLET GRID LAYOUT TESTS
    // ========================================

    /**
     * Test 24: Dashboard grid adapts to tablet (2-3 columns)
     */
    public function test_tablet_dashboard_grid_adapts(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/dashboard')
                ->waitForText('Total Servers')
                ->assertPresent('.grid')
                ->screenshot('tablet-dashboard-grid');
        });
    }

    /**
     * Test 25: Projects card grid shows 2 columns on small tablet
     */
    public function test_tablet_projects_card_grid_two_columns(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/projects')
                ->pause(1000)
                ->screenshot('tablet-projects-two-col');
        });
    }

    /**
     * Test 26: Projects card grid shows 3 columns on large tablet
     */
    public function test_tablet_projects_card_grid_three_columns(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 1366)
                ->visit('/projects')
                ->pause(1000)
                ->screenshot('tablet-projects-three-col');
        });
    }

    /**
     * Test 27: Server cards arrange in tablet grid
     */
    public function test_tablet_server_cards_grid_arrangement(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/servers')
                ->pause(1000)
                ->screenshot('tablet-server-cards-grid');
        });
    }

    // ========================================
    // TABLET MODAL SIZING TESTS
    // ========================================

    /**
     * Test 28: Modal sizing is appropriate for tablet portrait
     */
    public function test_tablet_modal_sizing_portrait(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/projects')
                ->pause(1000)
                ->screenshot('tablet-modal-portrait');
        });
    }

    /**
     * Test 29: Modal sizing is appropriate for tablet landscape
     */
    public function test_tablet_modal_sizing_landscape(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 768)
                ->visit('/projects')
                ->pause(1000)
                ->screenshot('tablet-modal-landscape');
        });
    }

    /**
     * Test 30: Large modals fit on tablet screen
     */
    public function test_tablet_large_modals_fit_screen(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/settings')
                ->pause(1000)
                ->screenshot('tablet-large-modal');
        });
    }

    // ========================================
    // TABLET TABLE COLUMN VISIBILITY TESTS
    // ========================================

    /**
     * Test 31: Deployment table shows essential columns on tablet
     */
    public function test_tablet_deployment_table_essential_columns(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/deployments')
                ->pause(1000)
                ->screenshot('tablet-deployment-table');
        });
    }

    /**
     * Test 32: Server table displays properly on tablet
     */
    public function test_tablet_server_table_displays_properly(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 768)
                ->visit('/servers')
                ->pause(1000)
                ->screenshot('tablet-server-table');
        });
    }

    /**
     * Test 33: User list table is readable on tablet
     */
    public function test_tablet_user_list_table_readable(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/users')
                ->pause(1000)
                ->screenshot('tablet-user-list-table');
        });
    }

    /**
     * Test 34: Table horizontal scroll works on tablet if needed
     */
    public function test_tablet_table_horizontal_scroll(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/deployments')
                ->pause(1000)
                ->screenshot('tablet-table-scroll');
        });
    }

    // ========================================
    // TABLET FORM LAYOUT TESTS
    // ========================================

    /**
     * Test 35: Create project form shows side-by-side fields on tablet
     */
    public function test_tablet_create_project_form_side_by_side(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 768)
                ->visit('/projects/create')
                ->pause(1000)
                ->screenshot('tablet-create-project-form');
        });
    }

    /**
     * Test 36: Server creation form adapts to tablet layout
     */
    public function test_tablet_server_creation_form_adapts(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/servers/create')
                ->pause(1000)
                ->screenshot('tablet-server-form');
        });
    }

    /**
     * Test 37: Settings form displays two columns on tablet
     */
    public function test_tablet_settings_form_two_columns(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(912, 1368)
                ->visit('/settings')
                ->pause(1000)
                ->screenshot('tablet-settings-form-cols');
        });
    }

    /**
     * Test 38: Form inputs are appropriately sized for tablet
     */
    public function test_tablet_form_inputs_appropriately_sized(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/projects/create')
                ->pause(1000)
                ->screenshot('tablet-form-input-sizes');
        });
    }

    // ========================================
    // TABLET CHART/GRAPH SCALING TESTS
    // ========================================

    /**
     * Test 39: Dashboard charts scale properly on tablet portrait
     */
    public function test_tablet_dashboard_charts_scale_portrait(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/dashboard')
                ->waitForText('Deployment Timeline')
                ->screenshot('tablet-charts-portrait');
        });
    }

    /**
     * Test 40: Dashboard charts scale properly on tablet landscape
     */
    public function test_tablet_dashboard_charts_scale_landscape(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 768)
                ->visit('/dashboard')
                ->waitForText('Deployment Timeline')
                ->screenshot('tablet-charts-landscape');
        });
    }

    /**
     * Test 41: Analytics charts are readable on tablet
     */
    public function test_tablet_analytics_charts_readable(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/analytics')
                ->pause(1000)
                ->screenshot('tablet-analytics-charts');
        });
    }

    /**
     * Test 42: Server metrics graphs display correctly on tablet
     */
    public function test_tablet_server_metrics_graphs(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(912, 1368)
                ->visit('/servers')
                ->pause(1000)
                ->screenshot('tablet-server-metrics-graphs');
        });
    }

    // ========================================
    // TABLET CARD GRID ARRANGEMENT TESTS
    // ========================================

    /**
     * Test 43: Stat cards arrange in 2 columns on small tablet
     */
    public function test_tablet_stat_cards_two_column_arrangement(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/dashboard')
                ->waitForText('Total Servers')
                ->screenshot('tablet-stat-cards-2col');
        });
    }

    /**
     * Test 44: Stat cards arrange in 3 columns on large tablet
     */
    public function test_tablet_stat_cards_three_column_arrangement(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 1366)
                ->visit('/dashboard')
                ->waitForText('Total Servers')
                ->screenshot('tablet-stat-cards-3col');
        });
    }

    /**
     * Test 45: Project cards maintain spacing on tablet
     */
    public function test_tablet_project_cards_maintain_spacing(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/projects')
                ->pause(1000)
                ->screenshot('tablet-project-cards-spacing');
        });
    }

    // ========================================
    // TABLET INTERACTION TESTS
    // ========================================

    /**
     * Test 46: Touch-friendly buttons are adequately sized on tablet
     */
    public function test_tablet_touch_friendly_button_sizes(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/dashboard')
                ->waitForText('Quick Actions')
                ->screenshot('tablet-touch-buttons');
        });
    }

    /**
     * Test 47: Dropdown menus position correctly on tablet
     */
    public function test_tablet_dropdown_menu_positioning(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 768)
                ->visit('/dashboard')
                ->pause(1000)
                ->screenshot('tablet-dropdown-position');
        });
    }

    /**
     * Test 48: Context menus work on tablet
     */
    public function test_tablet_context_menus_work(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/projects')
                ->pause(1000)
                ->screenshot('tablet-context-menu');
        });
    }

    /**
     * Test 49: Tooltip behavior works on tablet
     */
    public function test_tablet_tooltip_behavior(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(912, 1368)
                ->visit('/dashboard')
                ->waitForText('Total Servers')
                ->pause(1000)
                ->screenshot('tablet-tooltips');
        });
    }

    /**
     * Test 50: Notification panel displays correctly on tablet
     */
    public function test_tablet_notification_panel_displays(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/notifications')
                ->pause(1000)
                ->screenshot('tablet-notifications');
        });
    }

    /**
     * Test 51: Team management grid works on tablet
     */
    public function test_tablet_team_management_grid(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 768)
                ->visit('/teams')
                ->pause(1000)
                ->screenshot('tablet-team-management');
        });
    }

    /**
     * Test 52: Dashboard activity feed scrolls on tablet
     */
    public function test_tablet_activity_feed_scrolls(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(820, 1180)
                ->visit('/dashboard')
                ->waitForText('Recent Activity')
                ->scrollIntoView('.flow-root')
                ->screenshot('tablet-activity-feed-scroll');
        });
    }

    /**
     * Test 53: Quick actions remain accessible on tablet
     */
    public function test_tablet_quick_actions_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/dashboard')
                ->waitForText('Quick Actions')
                ->assertSee('New Project')
                ->assertSee('Add Server')
                ->assertSee('Deploy All')
                ->assertSee('Clear Caches')
                ->screenshot('tablet-quick-actions');
        });
    }

    /**
     * Test 54: Multi-select controls work on tablet
     */
    public function test_tablet_multi_select_controls(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(912, 1368)
                ->visit('/projects')
                ->pause(1000)
                ->screenshot('tablet-multi-select');
        });
    }

    /**
     * Test 55: Page footer displays correctly on tablet
     */
    public function test_tablet_page_footer_displays(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(1024, 1366)
                ->visit('/dashboard')
                ->pause(500)
                ->script('window.scrollTo(0, document.body.scrollHeight);')
                ->pause(500)
                ->screenshot('tablet-footer');
        });
    }

    /**
     * Cleanup after tests
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
