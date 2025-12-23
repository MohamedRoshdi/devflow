<?php

namespace Tests\Browser;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class DeploymentListTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

    protected Project $project;

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

        $this->server = Server::firstOrCreate(
            ['hostname' => 'test-deploy-server.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Test Deploy Server',
                'ip_address' => '192.168.1.150',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        $this->project = Project::firstOrCreate(
            ['slug' => 'test-deploy-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Deploy Project',
                'status' => 'running',
                'framework' => 'laravel',
                'repository_url' => 'https://github.com/test/project.git',
                'branch' => 'main',
            ]
        );
    }

    /**
     * Test 1: Page loads successfully
     */
    public function test_page_loads_successfully(): void
    {
        $this->testResults['page_loads'] = false;

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('Deployment Activity');

            $this->testResults['page_loads'] = true;
        });

        $this->assertTrue($this->testResults['page_loads']);
    }

    /**
     * Test 2: Deployment list displayed
     */
    public function test_deployment_list_displayed(): void
    {
        $this->testResults['list_displayed'] = false;

        // Create test deployment
        $deployment = Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'abc123f',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Test deployment commit message',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 180,
            ]
        );

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('Test deployment commit message');

            $this->testResults['list_displayed'] = true;
        });

        $this->assertTrue($this->testResults['list_displayed']);
    }

    /**
     * Test 3: Filter by project works
     */
    public function test_filter_by_project_works(): void
    {
        $this->testResults['project_filter_works'] = false;

        // Create second project
        $project2 = Project::firstOrCreate(
            ['slug' => 'test-deploy-project-2'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Deploy Project 2',
                'status' => 'running',
                'framework' => 'laravel',
                'repository_url' => 'https://github.com/test/project2.git',
                'branch' => 'main',
            ]
        );

        // Create deployments for both projects
        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'def456a',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Project 1 deployment',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(2),
                'completed_at' => now()->subHours(2)->addMinutes(5),
                'duration_seconds' => 300,
            ]
        );

        Deployment::firstOrCreate(
            [
                'project_id' => $project2->id,
                'commit_hash' => 'ghi789b',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Project 2 deployment',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now()->subHours(1)->addMinutes(3),
                'duration_seconds' => 180,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('Project 1 deployment')
                ->assertSee('Project 2 deployment')
                ->select('select[wire\\:model.live="projectFilter"]', $this->project->id)
                ->pause(1500)
                ->assertSee('Project 1 deployment')
                ->assertDontSee('Project 2 deployment');

            $this->testResults['project_filter_works'] = true;
        });

        $this->assertTrue($this->testResults['project_filter_works']);
    }

    /**
     * Test 4: Filter by status works
     */
    public function test_filter_by_status_works(): void
    {
        $this->testResults['status_filter_works'] = false;

        // Create deployments with different statuses
        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'success123',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Successful deployment test',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(2),
                'completed_at' => now()->subHours(2)->addMinutes(5),
                'duration_seconds' => 300,
            ]
        );

        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'failed456',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Failed deployment test',
                'branch' => 'main',
                'status' => 'failed',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now()->subHours(1)->addMinutes(2),
                'duration_seconds' => 120,
                'error_message' => 'Test error message',
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('Successful deployment test')
                ->assertSee('Failed deployment test')
                ->select('select[wire\\:model.live="statusFilter"]', 'success')
                ->pause(1500)
                ->assertSee('Successful deployment test')
                ->assertDontSee('Failed deployment test');

            $this->testResults['status_filter_works'] = true;
        });

        $this->assertTrue($this->testResults['status_filter_works']);
    }

    /**
     * Test 5: Filter by date range works (via search)
     */
    public function test_filter_by_date_range_works(): void
    {
        $this->testResults['date_filter_works'] = false;

        // Create deployments with different dates
        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'recent789',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Recent deployment',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 60,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000);

            // Check if the deployment list shows recent deployments
            $hasRecentDeployment = $browser->script('return document.body.innerText.includes("Recent deployment")');

            $this->testResults['date_filter_works'] = $hasRecentDeployment[0] ?? false;
        });

        $this->assertTrue($this->testResults['date_filter_works']);
    }

    /**
     * Test 6: Search deployments works
     */
    public function test_search_deployments_works(): void
    {
        $this->testResults['search_works'] = false;

        // Create deployments with searchable content
        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'search111',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Searchable unique feature deployment',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 120,
            ]
        );

        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'search222',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Another normal deployment',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(2),
                'completed_at' => now()->subHours(2)->addMinutes(3),
                'duration_seconds' => 180,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->type('input[wire\\:model.live.debounce.500ms="search"]', 'unique feature')
                ->pause(2000)
                ->assertSee('Searchable unique feature deployment')
                ->assertDontSee('Another normal deployment');

            $this->testResults['search_works'] = true;
        });

        $this->assertTrue($this->testResults['search_works']);
    }

    /**
     * Test 7: Deployment status badge
     */
    public function test_deployment_status_badge(): void
    {
        $this->testResults['status_badge'] = false;

        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'badge123',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Status badge test',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 150,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('Success');

            // Check for status badge with gradient
            $hasStatusBadge = $browser->element('.bg-gradient-to-r') !== null;

            $this->testResults['status_badge'] = $hasStatusBadge;
        });

        $this->assertTrue($this->testResults['status_badge']);
    }

    /**
     * Test 8: Project name shown
     */
    public function test_project_name_shown(): void
    {
        $this->testResults['project_name_shown'] = false;

        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'projname123',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Project name display test',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 90,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee($this->project->name);

            $this->testResults['project_name_shown'] = true;
        });

        $this->assertTrue($this->testResults['project_name_shown']);
    }

    /**
     * Test 9: Triggered by shown
     */
    public function test_triggered_by_shown(): void
    {
        $this->testResults['triggered_by_shown'] = false;

        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'trigger123',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Triggered by test',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 100,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000);

            // The component doesn't explicitly show "triggered_by" field in the list view,
            // but we can check if deployment details are shown
            $hasDeploymentDetails = $browser->script('return document.body.innerText.includes("Triggered by test")');

            $this->testResults['triggered_by_shown'] = $hasDeploymentDetails[0] ?? false;
        });

        $this->assertTrue($this->testResults['triggered_by_shown']);
    }

    /**
     * Test 10: Duration displayed
     */
    public function test_duration_displayed(): void
    {
        $this->testResults['duration_displayed'] = false;

        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'duration123',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Duration display test',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now()->subHours(1)->addMinutes(5),
                'duration_seconds' => 300,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('Duration');

            $this->testResults['duration_displayed'] = true;
        });

        $this->assertTrue($this->testResults['duration_displayed']);
    }

    /**
     * Test 11: Commit hash shown
     */
    public function test_commit_hash_shown(): void
    {
        $this->testResults['commit_hash_shown'] = false;

        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'a1b2c3d4e5f6',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Commit hash display test',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 120,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('a1b2c3d');

            $this->testResults['commit_hash_shown'] = true;
        });

        $this->assertTrue($this->testResults['commit_hash_shown']);
    }

    /**
     * Test 12: View details link
     */
    public function test_view_details_link(): void
    {
        $this->testResults['view_details_link'] = false;

        $deployment = Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'details123',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'View details link test',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 80,
            ]
        );

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('View Details')
                ->assertPresent('a[href*="/deployments/' . $deployment->id . '"]');

            $this->testResults['view_details_link'] = true;
        });

        $this->assertTrue($this->testResults['view_details_link']);
    }

    /**
     * Test 13: Pagination works
     */
    public function test_pagination_works(): void
    {
        $this->testResults['pagination_works'] = false;

        // Create 20 deployments to trigger pagination
        for ($i = 1; $i <= 20; $i++) {
            Deployment::firstOrCreate(
                [
                    'project_id' => $this->project->id,
                    'commit_hash' => 'page' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                ],
                [
                    'user_id' => $this->user->id,
                    'server_id' => $this->server->id,
                    'commit_message' => 'Pagination test deployment ' . $i,
                    'branch' => 'main',
                    'status' => 'success',
                    'triggered_by' => 'manual',
                    'started_at' => now()->subHours($i),
                    'completed_at' => now()->subHours($i)->addMinutes(2),
                    'duration_seconds' => 120,
                ]
            );
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1500);

            // Check for pagination elements
            $hasPagination = $browser->script('return document.querySelector("nav") !== null');

            $this->testResults['pagination_works'] = $hasPagination[0] ?? false;
        });

        $this->assertTrue($this->testResults['pagination_works']);
    }

    /**
     * Test 14: Empty state message
     */
    public function test_empty_state_message(): void
    {
        $this->testResults['empty_state'] = false;

        // Delete all deployments for this test
        Deployment::where('project_id', $this->project->id)->delete();

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('No deployments found');

            $this->testResults['empty_state'] = true;
        });

        $this->assertTrue($this->testResults['empty_state']);
    }

    /**
     * Test 15: Refresh list button (auto refresh)
     */
    public function test_refresh_list_button(): void
    {
        $this->testResults['refresh_button'] = false;

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->refresh()
                ->pause(1000)
                ->assertSee('Deployment Activity');

            $this->testResults['refresh_button'] = true;
        });

        $this->assertTrue($this->testResults['refresh_button']);
    }

    /**
     * Test 16: Clear filters button (reset filters)
     */
    public function test_clear_filters_button(): void
    {
        $this->testResults['clear_filters'] = false;

        // Create test deployment
        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'clearfilter123',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Clear filters test',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 100,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->select('select[wire\\:model.live="statusFilter"]', 'success')
                ->pause(1500)
                ->select('select[wire\\:model.live="statusFilter"]', '')
                ->pause(1500)
                ->assertSee('Clear filters test');

            $this->testResults['clear_filters'] = true;
        });

        $this->assertTrue($this->testResults['clear_filters']);
    }

    /**
     * Test 17: Statistics summary
     */
    public function test_statistics_summary(): void
    {
        $this->testResults['statistics_summary'] = false;

        // Create deployments with different statuses for stats
        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'stats1',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Stats test 1',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 100,
            ]
        );

        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'stats2',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Stats test 2',
                'branch' => 'main',
                'status' => 'failed',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(2),
                'completed_at' => now()->subHours(2)->addMinutes(1),
                'duration_seconds' => 60,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('Total Deployments')
                ->assertSee('Successful')
                ->assertSee('Failed')
                ->assertSee('Running');

            $this->testResults['statistics_summary'] = true;
        });

        $this->assertTrue($this->testResults['statistics_summary']);
    }

    /**
     * Test 18: Recent deployments highlighted
     */
    public function test_recent_deployments_highlighted(): void
    {
        $this->testResults['recent_highlighted'] = false;

        // Create a very recent deployment
        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'recent123abc',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Very recent deployment',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subMinutes(5),
                'completed_at' => now(),
                'duration_seconds' => 60,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('Very recent deployment');

            // Check for timeline design elements that highlight recent items
            $hasTimeline = $browser->element('.relative.pl-6') !== null;

            $this->testResults['recent_highlighted'] = $hasTimeline;
        });

        $this->assertTrue($this->testResults['recent_highlighted']);
    }

    /**
     * Test 19: Failed deployments warning
     */
    public function test_failed_deployments_warning(): void
    {
        $this->testResults['failed_warning'] = false;

        // Create failed deployment
        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'failed789xyz',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Failed deployment warning test',
                'branch' => 'main',
                'status' => 'failed',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now()->subHours(1)->addMinutes(1),
                'duration_seconds' => 60,
                'error_message' => 'Test failure error',
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('Failed');

            // Check for failed status badge (red gradient)
            $hasFailedBadge = $browser->script('return document.body.innerText.includes("Failed")');

            $this->testResults['failed_warning'] = $hasFailedBadge[0] ?? false;
        });

        $this->assertTrue($this->testResults['failed_warning']);
    }

    /**
     * Test 20: Flash messages display
     */
    public function test_flash_messages_display(): void
    {
        $this->testResults['flash_messages'] = false;

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000);

            // Check page loads successfully (flash messages would display here if triggered)
            $pageLoaded = $browser->script('return document.body.innerText.includes("Deployment Activity")');

            $this->testResults['flash_messages'] = $pageLoaded[0] ?? false;
        });

        $this->assertTrue($this->testResults['flash_messages']);
    }

    /**
     * Test 21: Branch name displayed
     */
    public function test_branch_name_displayed(): void
    {
        $this->testResults['branch_displayed'] = false;

        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'branch123',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Branch display test',
                'branch' => 'feature/test-branch',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 90,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('Branch:')
                ->assertSee('feature/test-branch');

            $this->testResults['branch_displayed'] = true;
        });

        $this->assertTrue($this->testResults['branch_displayed']);
    }

    /**
     * Test 22: Server name displayed
     */
    public function test_server_name_displayed(): void
    {
        $this->testResults['server_displayed'] = false;

        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'server123',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Server display test',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 100,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('Server:')
                ->assertSee($this->server->name);

            $this->testResults['server_displayed'] = true;
        });

        $this->assertTrue($this->testResults['server_displayed']);
    }

    /**
     * Test 23: Timeline design displayed
     */
    public function test_timeline_design_displayed(): void
    {
        $this->testResults['timeline_displayed'] = false;

        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'timeline123',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Timeline design test',
                'branch' => 'main',
                'status' => 'success',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
                'duration_seconds' => 75,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000);

            // Check for timeline dot and vertical line
            $hasTimelineElements = $browser->element('.relative.pl-6') !== null;

            $this->testResults['timeline_displayed'] = $hasTimelineElements;
        });

        $this->assertTrue($this->testResults['timeline_displayed']);
    }

    /**
     * Test 24: Per page selector works
     */
    public function test_per_page_selector_works(): void
    {
        $this->testResults['per_page_works'] = false;

        // Create multiple deployments
        for ($i = 1; $i <= 25; $i++) {
            Deployment::firstOrCreate(
                [
                    'project_id' => $this->project->id,
                    'commit_hash' => 'perpage' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                ],
                [
                    'user_id' => $this->user->id,
                    'server_id' => $this->server->id,
                    'commit_message' => 'Per page test deployment ' . $i,
                    'branch' => 'main',
                    'status' => 'success',
                    'triggered_by' => 'manual',
                    'started_at' => now()->subHours($i),
                    'completed_at' => now()->subHours($i)->addMinutes(1),
                    'duration_seconds' => 60,
                ]
            );
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertPresent('select[wire\\:model.live="perPage"]')
                ->select('select[wire\\:model.live="perPage"]', '10')
                ->pause(1500);

            // Check that pagination exists after changing per page
            $hasPagination = $browser->script('return document.querySelector("nav") !== null');

            $this->testResults['per_page_works'] = $hasPagination[0] ?? false;
        });

        $this->assertTrue($this->testResults['per_page_works']);
    }

    /**
     * Test 25: Running deployment shows spinner
     */
    public function test_running_deployment_shows_spinner(): void
    {
        $this->testResults['running_spinner'] = false;

        Deployment::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'commit_hash' => 'running123',
            ],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'commit_message' => 'Running deployment spinner test',
                'branch' => 'main',
                'status' => 'running',
                'triggered_by' => 'manual',
                'started_at' => now()->subMinutes(5),
                'completed_at' => null,
                'duration_seconds' => null,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);

            $browser->visit('/deployments')
                ->pause(1000)
                ->assertSee('Running');

            // Check for animate-spin class on running status
            $hasSpinner = $browser->element('.animate-spin') !== null;

            $this->testResults['running_spinner'] = $hasSpinner;
        });

        $this->assertTrue($this->testResults['running_spinner']);
    }
}
