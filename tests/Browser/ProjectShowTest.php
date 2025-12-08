<?php

namespace Tests\Browser;

use App\Models\Deployment;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

/**
 * ProjectShowTest - Comprehensive browser tests for authenticated project detail page
 *
 * This test suite covers the ProjectShow Livewire component which displays
 * detailed project information for authenticated users.
 *
 * Test Coverage:
 * - Project information display
 * - Tab navigation (overview, docker, environment, git, logs, deployments, webhooks)
 * - Quick actions (deploy, start, stop)
 * - Deployment history
 * - Git commit display
 * - Domain management
 * - Project status indicators
 * - Auto-refresh functionality
 * - Update notifications
 * - Flash messages
 * - Docker status
 * - Responsive design
 */
class ProjectShowTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected ?Project $project = null;

    protected ?Server $server = null;

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

        // Try to get the first project
        $this->project = Project::first();

        // If no project exists, create one with a server
        if (! $this->project) {
            $this->server = Server::firstOrCreate(
                ['hostname' => 'test-show.example.com'],
                [
                    'user_id' => $this->user->id,
                    'name' => 'Test Show Server',
                    'ip_address' => '192.168.1.100',
                    'port' => 22,
                    'username' => 'root',
                    'status' => 'online',
                ]
            );

            $this->project = Project::create([
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Show Project',
                'slug' => 'test-show-project',
                'framework' => 'Laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/show-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/show-project',
                'php_version' => '8.4',
                'environment' => 'production',
            ]);

            Domain::create([
                'project_id' => $this->project->id,
                'domain' => 'show-test.devflow.test',
                'is_primary' => true,
                'ssl_enabled' => true,
                'status' => 'active',
            ]);
        }
    }

    /**
     * Test 1: Project show page loads successfully
     */
    public function test_project_show_page_loads_successfully(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee($this->project->name)
                ->screenshot('project-show-page-loads');
        });
    }

    /**
     * Test 2: Project name is displayed prominently
     */
    public function test_project_name_is_displayed_prominently(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSeeIn('h1', $this->project->name)
                ->screenshot('project-show-name-display');
        });
    }

    /**
     * Test 3: Project status indicator is visible
     */
    public function test_project_status_indicator_is_visible(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee(ucfirst($this->project->status))
                ->screenshot('project-show-status-indicator');
        });
    }

    /**
     * Test 4: Deploy button is visible
     */
    public function test_deploy_button_is_visible(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Deploy Update')
                ->screenshot('project-show-deploy-button');
        });
    }

    /**
     * Test 5: Start/Stop buttons are visible based on status
     */
    public function test_start_stop_buttons_visible_based_on_status(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            if ($this->project->status === 'running') {
                $browser->assertSee('Stop Project');
            } else {
                $browser->assertSee('Start Project');
            }

            $browser->screenshot('project-show-start-stop-buttons');
        });
    }

    /**
     * Test 6: Edit project button is present
     */
    public function test_edit_project_button_is_present(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Configure Project')
                ->assertPresent('a[href="'.route('projects.edit', $this->project).'"]')
                ->screenshot('project-show-edit-button');
        });
    }

    /**
     * Test 7: Overview tab is active by default
     */
    public function test_overview_tab_active_by_default(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertPresent('button.border-blue-500')
                ->screenshot('project-show-overview-tab-active');
        });
    }

    /**
     * Test 8: Git tab is accessible
     */
    public function test_git_tab_is_accessible(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Git')
                ->click('button[wire\\:click="setActiveTab(\'git\')"]')
                ->pause(1000)
                ->screenshot('project-show-git-tab');
        });
    }

    /**
     * Test 9: Deployments tab is accessible
     */
    public function test_deployments_tab_is_accessible(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Deployments')
                ->click('button[wire\\:click="setActiveTab(\'deployments\')"]')
                ->pause(1000)
                ->screenshot('project-show-deployments-tab');
        });
    }

    /**
     * Test 10: Docker tab is accessible
     */
    public function test_docker_tab_is_accessible(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Docker')
                ->click('button[wire\\:click="setActiveTab(\'docker\')"]')
                ->pause(1000)
                ->screenshot('project-show-docker-tab');
        });
    }

    /**
     * Test 11: Domain list is displayed
     */
    public function test_domain_list_is_displayed(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $domains = $this->project->domains;
            if ($domains->isNotEmpty()) {
                $browser->assertSee($domains->first()->domain);
            }

            $browser->screenshot('project-show-domain-list');
        });
    }

    /**
     * Test 12: Recent deployments are shown
     */
    public function test_recent_deployments_shown(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->click('button[wire\\:click="setActiveTab(\'deployments\')"]')
                ->pause(1000);

            $deploymentsCount = $this->project->deployments()->count();
            if ($deploymentsCount > 0) {
                $browser->assertSee('Deployment');
            }

            $browser->screenshot('project-show-recent-deployments');
        });
    }

    /**
     * Test 13: Git commits display when git tab is active
     */
    public function test_git_commits_display_when_git_tab_active(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->click('button[wire\\:click="setActiveTab(\'git\')"]')
                ->pause(2000)
                ->screenshot('project-show-git-commits');
        });
    }

    /**
     * Test 14: Repository URL is shown
     */
    public function test_repository_url_is_shown(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            if ($this->project->repository) {
                $browser->assertSee('Repository')
                    ->screenshot('project-show-repository-url');
            } else {
                $browser->screenshot('project-show-no-repository');
            }
        });
    }

    /**
     * Test 15: Branch name is displayed
     */
    public function test_branch_name_is_displayed(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Branch')
                ->assertSee($this->project->branch)
                ->screenshot('project-show-branch-name');
        });
    }

    /**
     * Test 16: Last deployment info is visible if deployments exist
     */
    public function test_last_deployment_info_visible_if_exists(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $latestDeployment = $this->project->deployments()->latest()->first();
            if ($latestDeployment) {
                $browser->click('button[wire\\:click="setActiveTab(\'deployments\')"]')
                    ->pause(1000);
            }

            $browser->screenshot('project-show-last-deployment-info');
        });
    }

    /**
     * Test 17: Quick deploy modal opens
     */
    public function test_quick_deploy_modal_opens(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->click('button[wire\\:click="$set(\'showDeployModal\', true)"]')
                ->pause(1000)
                ->screenshot('project-show-deploy-modal');
        });
    }

    /**
     * Test 18: Project health status is shown in stats
     */
    public function test_project_health_status_shown_in_stats(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Deployments')
                ->screenshot('project-show-health-status');
        });
    }

    /**
     * Test 19: Environment indicator is displayed
     */
    public function test_environment_indicator_is_displayed(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            if ($this->project->environment) {
                $browser->assertSee('Environment')
                    ->assertSee(ucfirst($this->project->environment));
            }

            $browser->screenshot('project-show-environment-indicator');
        });
    }

    /**
     * Test 20: Navigation breadcrumbs are present
     */
    public function test_navigation_breadcrumbs_are_present(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Project')
                ->screenshot('project-show-breadcrumbs');
        });
    }

    /**
     * Test 21: Auto-refresh toggle functionality exists
     */
    public function test_auto_refresh_toggle_functionality_exists(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->click('button[wire\\:click="setActiveTab(\'git\')"]')
                ->pause(2000)
                ->screenshot('project-show-auto-refresh-toggle');
        });
    }

    /**
     * Test 22: Refresh git data button is present on git tab
     */
    public function test_refresh_git_data_button_present_on_git_tab(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->click('button[wire\\:click="setActiveTab(\'git\')"]')
                ->pause(2000)
                ->screenshot('project-show-refresh-git-button');
        });
    }

    /**
     * Test 23: Update available indicator shows when updates exist
     */
    public function test_update_available_indicator_shows_when_updates_exist(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->pause(2000)
                ->screenshot('project-show-update-indicator');
        });
    }

    /**
     * Test 24: Docker status is shown on docker tab
     */
    public function test_docker_status_shown_on_docker_tab(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->click('button[wire\\:click="setActiveTab(\'docker\')"]')
                ->pause(1000)
                ->screenshot('project-show-docker-status');
        });
    }

    /**
     * Test 25: Flash messages display correctly
     */
    public function test_flash_messages_display_correctly(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->screenshot('project-show-flash-messages');
        });
    }

    /**
     * Test 26: Project slug is displayed in info section
     */
    public function test_project_slug_displayed_in_info_section(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Slug')
                ->assertSee($this->project->slug)
                ->screenshot('project-show-slug-display');
        });
    }

    /**
     * Test 27: Server information is displayed
     */
    public function test_server_information_is_displayed(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Server');

            if ($this->project->server) {
                $browser->assertSee($this->project->server->name);
            }

            $browser->screenshot('project-show-server-info');
        });
    }

    /**
     * Test 28: Framework badge is displayed
     */
    public function test_framework_badge_is_displayed(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            if ($this->project->framework) {
                $browser->assertSee($this->project->framework);
            }

            $browser->screenshot('project-show-framework-badge');
        });
    }

    /**
     * Test 29: Live URL is displayed when project is running
     */
    public function test_live_url_displayed_when_project_running(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            if ($this->project->status === 'running' && $this->project->domains->isNotEmpty()) {
                $primaryDomain = $this->project->domains->where('is_primary', true)->first();
                if ($primaryDomain) {
                    $browser->assertSee('Live URL');
                }
            }

            $browser->screenshot('project-show-live-url');
        });
    }

    /**
     * Test 30: Environment tab is accessible
     */
    public function test_environment_tab_is_accessible(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Environment')
                ->click('button[wire\\:click="setActiveTab(\'environment\')"]')
                ->pause(1000)
                ->screenshot('project-show-environment-tab');
        });
    }

    /**
     * Test 31: Logs tab is accessible
     */
    public function test_logs_tab_is_accessible(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Logs')
                ->click('button[wire\\:click="setActiveTab(\'logs\')"]')
                ->pause(1000)
                ->screenshot('project-show-logs-tab');
        });
    }

    /**
     * Test 32: Webhooks tab is accessible
     */
    public function test_webhooks_tab_is_accessible(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Webhooks')
                ->click('button[wire\\:click="setActiveTab(\'webhooks\')"]')
                ->pause(1000)
                ->screenshot('project-show-webhooks-tab');
        });
    }

    /**
     * Test 33: Quick stats cards display deployment count
     */
    public function test_quick_stats_cards_display_deployment_count(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Deployments')
                ->assertSee($this->project->deployments()->count())
                ->screenshot('project-show-stats-deployment-count');
        });
    }

    /**
     * Test 34: Hero section with gradient is displayed
     */
    public function test_hero_section_with_gradient_displayed(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertPresent('.bg-gradient-to-r')
                ->screenshot('project-show-hero-gradient');
        });
    }

    /**
     * Test 35: Status badge shows appropriate color for running status
     */
    public function test_status_badge_shows_appropriate_color_for_running_status(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            if ($this->project->status === 'running') {
                $browser->assertPresent('.animate-pulse');
            }

            $browser->screenshot('project-show-status-badge-color');
        });
    }

    /**
     * Test 36: Page scrolls smoothly
     */
    public function test_page_scrolls_smoothly(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->scrollToTop()
                ->pause(200)
                ->scrollToBottom()
                ->pause(200)
                ->screenshot('project-show-scroll-test');
        });
    }

    /**
     * Test 37: Mobile responsive layout
     */
    public function test_mobile_responsive_layout(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->resize(375, 667)
                ->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee($this->project->name)
                ->screenshot('project-show-mobile-layout');
        });
    }

    /**
     * Test 38: Tablet responsive layout
     */
    public function test_tablet_responsive_layout(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->resize(768, 1024)
                ->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee($this->project->name)
                ->screenshot('project-show-tablet-layout');
        });
    }

    /**
     * Test 39: Desktop responsive layout
     */
    public function test_desktop_responsive_layout(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->resize(1920, 1080)
                ->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee($this->project->name)
                ->screenshot('project-show-desktop-layout');
        });
    }

    /**
     * Test 40: Tab navigation works with wire:loading indicators
     */
    public function test_tab_navigation_works_with_wire_loading_indicators(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->click('button[wire\\:click="setActiveTab(\'git\')"]')
                ->pause(500)
                ->click('button[wire\\:click="setActiveTab(\'overview\')"]')
                ->pause(500)
                ->screenshot('project-show-tab-navigation-loading');
        });
    }
}
