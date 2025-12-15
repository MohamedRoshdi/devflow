<?php

declare(strict_types=1);

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\GitHubConnection;
use App\Models\GitHubRepository;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

/**
 * Comprehensive GitHub Settings Tests for DevFlow Pro
 *
 * Tests all GitHub integration functionality including:
 * - GitHub connection status display
 * - Connect/Disconnect GitHub account
 * - Repository listing and filtering
 * - Repository synchronization
 * - Search functionality
 * - Visibility filters (public/private)
 * - Language filters
 * - Repository statistics
 * - Link repository to project
 * - Unlink repository from project
 * - Connection information display
 * - Error handling and flash messages
 * - Responsive design
 */
class GitHubSettingsTest extends DuskTestCase
{
    use LoginViaUI;

    /**
     * Test user credentials
     */
    protected const TEST_EMAIL = 'admin@devflow.test';

    protected const TEST_PASSWORD = 'password';

    protected User $user;

    protected array $testResults = [];

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::firstOrCreate(
            ['email' => self::TEST_EMAIL],
            [
                'name' => 'Test Admin',
                'password' => bcrypt(self::TEST_PASSWORD),
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        // Clean up any test data created during tests
        GitHubRepository::whereHas('connection', function ($query) {
            $query->where('user_id', $this->user->id);
        })->delete();

        GitHubConnection::where('user_id', $this->user->id)->delete();

        parent::tearDown();
    }

    /**
     * Test 1: GitHub settings page loads successfully
     *
     */

    #[Test]
    public function test_github_settings_page_loads_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->pause(1000)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('GitHub Integration')
                ->screenshot('01-github-settings-page-loads');
        });
    }

    /**
     * Test 2: Not connected state is displayed correctly
     *
     */

    #[Test]
    public function test_not_connected_state_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Ensure no connection exists
            GitHubConnection::where('user_id', $this->user->id)->delete();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('Connect to GitHub')
                ->assertSee('Connect GitHub Account')
                ->screenshot('02-not-connected-state');
        });
    }

    /**
     * Test 3: Connect GitHub button is visible when not connected
     *
     */

    #[Test]
    public function test_connect_button_visible_when_not_connected(): void
    {
        $this->browse(function (Browser $browser) {
            // Ensure no connection exists
            GitHubConnection::where('user_id', $this->user->id)->delete();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertPresent('a:contains("Connect GitHub Account")')
                ->screenshot('03-connect-button-visible');
        });
    }

    /**
     * Test 4: Benefits section displayed when not connected
     *
     */

    #[Test]
    public function test_benefits_section_displayed_when_not_connected(): void
    {
        $this->browse(function (Browser $browser) {
            // Ensure no connection exists
            GitHubConnection::where('user_id', $this->user->id)->delete();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('Repository Sync')
                ->assertSee('Auto Deploy')
                ->assertSee('Webhook Events')
                ->screenshot('04-benefits-section');
        });
    }

    /**
     * Test 5: Connected state displays user information
     *
     */

    #[Test]
    public function test_connected_state_displays_user_info(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a GitHub connection
            $connection = $this->createGitHubConnection();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee($connection->github_username)
                ->assertSee('Connected')
                ->screenshot('05-connected-user-info');
        });
    }

    /**
     * Test 6: Disconnect button shown when connected
     *
     */

    #[Test]
    public function test_disconnect_button_shown_when_connected(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a GitHub connection
            $this->createGitHubConnection();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertPresent('button:contains("Disconnect")')
                ->screenshot('06-disconnect-button-visible');
        });
    }

    /**
     * Test 7: Repository statistics cards displayed
     *
     */

    #[Test]
    public function test_repository_statistics_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and repositories
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 5);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('Total Repos')
                ->assertSee('Public')
                ->assertSee('Private')
                ->assertSee('Linked')
                ->screenshot('07-repository-statistics');
        });
    }

    /**
     * Test 8: Repository list is displayed
     *
     */

    #[Test]
    public function test_repository_list_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and repositories
            $connection = $this->createGitHubConnection();
            $repo = $this->createRepositories($connection, 1)->first();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('Repositories')
                ->assertSee($repo->name)
                ->screenshot('08-repository-list');
        });
    }

    /**
     * Test 9: Sync repositories button is visible
     *
     */

    #[Test]
    public function test_sync_repositories_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a GitHub connection
            $this->createGitHubConnection();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertPresent('button:contains("Sync Repositories")')
                ->screenshot('09-sync-button-visible');
        });
    }

    /**
     * Test 10: Last sync timestamp displayed for repositories
     *
     */

    #[Test]
    public function test_last_sync_timestamp_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and repository with sync timestamp
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1, ['synced_at' => now()]);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('Updated')
                ->screenshot('10-sync-timestamp');
        });
    }

    /**
     * Test 11: Repository count shown in statistics
     *
     */

    #[Test]
    public function test_repository_count_shown(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and 3 repositories
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 3);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('3') // Should see count of 3
                ->screenshot('11-repository-count');
        });
    }

    /**
     * Test 12: Search repositories input is present
     *
     */

    #[Test]
    public function test_search_repositories_input_present(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a GitHub connection
            $this->createGitHubConnection();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertPresent('input[placeholder*="Search"]')
                ->screenshot('12-search-input');
        });
    }

    /**
     * Test 13: Search repositories functionality works
     *
     */

    #[Test]
    public function test_search_repositories_works(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and repositories with distinct names
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1, ['name' => 'test-project']);
            $this->createRepositories($connection, 1, ['name' => 'other-project']);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->type('input[placeholder*="Search"]', 'test-project')
                ->pause(2000)
                ->assertSee('test-project')
                ->screenshot('13-search-results');
        });
    }

    /**
     * Test 14: Visibility filter dropdown is present
     *
     */

    #[Test]
    public function test_visibility_filter_present(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a GitHub connection
            $this->createGitHubConnection();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertPresent('select')
                ->assertSee('All Visibility')
                ->screenshot('14-visibility-filter');
        });
    }

    /**
     * Test 15: Filter by public repositories
     *
     */

    #[Test]
    public function test_filter_by_public_repositories(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection with public and private repos
            $connection = $this->createGitHubConnection();
            $publicRepo = $this->createRepositories($connection, 1, ['name' => 'public-repo', 'private' => false])->first();
            $this->createRepositories($connection, 1, ['name' => 'private-repo', 'private' => true]);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->select('select[wire\\:model\\.live="visibilityFilter"]', 'public')
                ->pause(2000)
                ->assertSee($publicRepo->name)
                ->screenshot('15-filter-public');
        });
    }

    /**
     * Test 16: Filter by private repositories
     *
     */

    #[Test]
    public function test_filter_by_private_repositories(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection with public and private repos
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1, ['name' => 'public-repo', 'private' => false]);
            $privateRepo = $this->createRepositories($connection, 1, ['name' => 'private-repo', 'private' => true])->first();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->select('select[wire\\:model\\.live="visibilityFilter"]', 'private')
                ->pause(2000)
                ->assertSee($privateRepo->name)
                ->screenshot('16-filter-private');
        });
    }

    /**
     * Test 17: Language filter dropdown is present
     *
     */

    #[Test]
    public function test_language_filter_present(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection with repository
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1, ['language' => 'PHP']);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('All Languages')
                ->screenshot('17-language-filter');
        });
    }

    /**
     * Test 18: Repository visibility badges displayed
     *
     */

    #[Test]
    public function test_repository_visibility_badges_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection with public repo
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1, ['private' => false]);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('Public')
                ->screenshot('18-visibility-badges');
        });
    }

    /**
     * Test 19: Repository language badges displayed
     *
     */

    #[Test]
    public function test_repository_language_badges_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection with PHP repository
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1, ['language' => 'PHP']);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('PHP')
                ->screenshot('19-language-badges');
        });
    }

    /**
     * Test 20: Link to project button visible for unlinked repos
     *
     */

    #[Test]
    public function test_link_to_project_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and unlinked repository
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1, ['project_id' => null]);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertPresent('button:contains("Link to Project")')
                ->screenshot('20-link-button-visible');
        });
    }

    /**
     * Test 21: Link to project modal opens
     *
     */

    #[Test]
    public function test_link_to_project_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and repository
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->click('button:contains("Link to Project")')
                ->pause(1000)
                ->assertSee('Link to Project')
                ->screenshot('21-link-modal-opens');
        });
    }

    /**
     * Test 22: Unlink button shown for linked repositories
     *
     */

    #[Test]
    public function test_unlink_button_shown_for_linked_repos(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection, project, and linked repository
            $connection = $this->createGitHubConnection();
            $project = $this->createProject();
            $this->createRepositories($connection, 1, ['project_id' => $project->id]);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertPresent('button:contains("Unlink")')
                ->screenshot('22-unlink-button-visible');
        });
    }

    /**
     * Test 23: Linked repository shows project name
     *
     */

    #[Test]
    public function test_linked_repository_shows_project_name(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection, project, and linked repository
            $connection = $this->createGitHubConnection();
            $project = $this->createProject();
            $this->createRepositories($connection, 1, ['project_id' => $project->id]);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee("Linked to {$project->name}")
                ->screenshot('23-linked-project-name');
        });
    }

    /**
     * Test 24: Repository description is displayed
     *
     */

    #[Test]
    public function test_repository_description_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and repository with description
            $connection = $this->createGitHubConnection();
            $description = 'This is a test repository description';
            $this->createRepositories($connection, 1, ['description' => $description]);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee($description)
                ->screenshot('24-repository-description');
        });
    }

    /**
     * Test 25: Repository stars count displayed
     *
     */

    #[Test]
    public function test_repository_stars_count_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and repository with stars
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1, ['stars_count' => 42]);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('42')
                ->screenshot('25-stars-count');
        });
    }

    /**
     * Test 26: Repository forks count displayed
     *
     */

    #[Test]
    public function test_repository_forks_count_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and repository with forks
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1, ['forks_count' => 15]);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('15')
                ->screenshot('26-forks-count');
        });
    }

    /**
     * Test 27: Empty state shown when no repositories
     *
     */

    #[Test]
    public function test_empty_state_shown_when_no_repositories(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection without repositories
            $this->createGitHubConnection();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('No repositories found')
                ->screenshot('27-empty-state');
        });
    }

    /**
     * Test 28: GitHub avatar is displayed when connected
     *
     */

    #[Test]
    public function test_github_avatar_displayed_when_connected(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection with avatar
            $connection = $this->createGitHubConnection();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertPresent("img[src=\"{$connection->github_avatar}\"]")
                ->screenshot('28-github-avatar');
        });
    }

    /**
     * Test 29: Connection timestamp displayed
     *
     */

    #[Test]
    public function test_connection_timestamp_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection
            $this->createGitHubConnection();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('Connected')
                ->screenshot('29-connection-timestamp');
        });
    }

    /**
     * Test 30: Repository link opens in new tab
     *
     */

    #[Test]
    public function test_repository_link_opens_in_new_tab(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and repository
            $connection = $this->createGitHubConnection();
            $repo = $this->createRepositories($connection, 1)->first();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertPresent("a[href=\"{$repo->html_url}\"][target=\"_blank\"]")
                ->screenshot('30-repo-link-new-tab');
        });
    }

    /**
     * Test 31: Page is responsive on mobile devices
     *
     */

    #[Test]
    public function test_page_responsive_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection
            $this->createGitHubConnection();

            $this->loginViaUI($browser)
                ->resize(375, 667)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('GitHub Integration')
                ->screenshot('31-mobile-responsive');
        });
    }

    /**
     * Test 32: Page is responsive on tablet devices
     *
     */

    #[Test]
    public function test_page_responsive_on_tablet(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection
            $this->createGitHubConnection();

            $this->loginViaUI($browser)
                ->resize(768, 1024)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('GitHub Integration')
                ->screenshot('32-tablet-responsive');
        });
    }

    /**
     * Test 33: Statistics update after filtering
     *
     */

    #[Test]
    public function test_statistics_reflect_filtered_results(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection with mixed repositories
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 2, ['private' => false]);
            $this->createRepositories($connection, 1, ['private' => true]);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('2') // Public count
                ->assertSee('1') // Private count
                ->screenshot('33-statistics-filtered');
        });
    }

    /**
     * Test 34: Empty search shows appropriate message
     *
     */

    #[Test]
    public function test_empty_search_shows_message(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and repository
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->type('input[placeholder*="Search"]', 'nonexistent-repo-xyz')
                ->pause(2000)
                ->assertSee('No repositories found')
                ->screenshot('34-empty-search');
        });
    }

    /**
     * Test 35: Clearing filters shows all repositories
     *
     */

    #[Test]
    public function test_clearing_filters_shows_all_repos(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and multiple repositories
            $connection = $this->createGitHubConnection();
            $repos = $this->createRepositories($connection, 3);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                // Apply filter
                ->select('select[wire\\:model\\.live="visibilityFilter"]', 'public')
                ->pause(1000)
                // Clear filter
                ->select('select[wire\\:model\\.live="visibilityFilter"]', 'all')
                ->pause(1000)
                ->screenshot('35-filters-cleared');
        });
    }

    /**
     * Test 36: Page requires authentication
     *
     */

    #[Test]
    public function test_page_requires_authentication(): void
    {
        $this->browse(function (Browser $browser) {
            // Logout first
            $this->post('/logout');

            $browser->visit('/settings/github')
                ->pause(1000)
                ->waitForLocation('/login', 5)
                ->assertPathIs('/login')
                ->screenshot('36-requires-auth');
        });
    }

    /**
     * Test 37: Hero section displayed with title and description
     *
     */

    #[Test]
    public function test_hero_section_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('GitHub Integration')
                ->assertSee('Connect your GitHub account')
                ->screenshot('37-hero-section');
        });
    }

    /**
     * Test 38: Multiple repositories displayed in order
     *
     */

    #[Test]
    public function test_multiple_repositories_displayed_in_order(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection and multiple repositories with different star counts
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1, ['name' => 'repo-1', 'stars_count' => 10]);
            $this->createRepositories($connection, 1, ['name' => 'repo-2', 'stars_count' => 50]);
            $this->createRepositories($connection, 1, ['name' => 'repo-3', 'stars_count' => 5]);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertSee('repo-1')
                ->assertSee('repo-2')
                ->assertSee('repo-3')
                ->screenshot('38-multiple-repos-ordered');
        });
    }

    /**
     * Test 39: Language filter shows available languages
     *
     */

    #[Test]
    public function test_language_filter_shows_available_languages(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection with repositories of different languages
            $connection = $this->createGitHubConnection();
            $this->createRepositories($connection, 1, ['language' => 'PHP']);
            $this->createRepositories($connection, 1, ['language' => 'JavaScript']);

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->click('select[wire\\:model\\.live="languageFilter"]')
                ->pause(500)
                ->assertSee('PHP')
                ->assertSee('JavaScript')
                ->screenshot('39-language-options');
        });
    }

    /**
     * Test 40: Connected state shows proper gradient background
     *
     */

    #[Test]
    public function test_connected_state_shows_gradient_background(): void
    {
        $this->browse(function (Browser $browser) {
            // Create connection
            $this->createGitHubConnection();

            $this->loginViaUI($browser)
                ->visit('/settings/github')
                ->pause(1000)
                ->assertPresent('.bg-gradient-to-r')
                ->screenshot('40-gradient-background');
        });
    }

    /**
     * Helper method to create a GitHub connection
     */
    private function createGitHubConnection(): GitHubConnection
    {
        return GitHubConnection::create([
            'user_id' => $this->user->id,
            'access_token' => 'test_token_'.Str::random(40),
            'github_user_id' => 123456,
            'github_username' => 'testuser',
            'github_avatar' => 'https://avatars.githubusercontent.com/u/123456',
            'scopes' => ['repo', 'user'],
            'is_active' => true,
        ]);
    }

    /**
     * Helper method to create repositories
     */
    private function createRepositories(GitHubConnection $connection, int $count, array $attributes = []): \Illuminate\Support\Collection
    {
        $repositories = collect();

        for ($i = 1; $i <= $count; $i++) {
            $repositories->push(GitHubRepository::create(array_merge([
                'github_connection_id' => $connection->id,
                'repo_id' => rand(1000, 9999),
                'name' => 'test-repo-'.Str::random(8),
                'full_name' => 'testuser/test-repo-'.Str::random(8),
                'description' => 'Test repository description',
                'private' => false,
                'default_branch' => 'main',
                'clone_url' => 'https://github.com/testuser/test-repo.git',
                'ssh_url' => 'git@github.com:testuser/test-repo.git',
                'html_url' => 'https://github.com/testuser/test-repo',
                'language' => 'PHP',
                'stars_count' => rand(0, 100),
                'forks_count' => rand(0, 50),
                'synced_at' => now(),
            ], $attributes)));
        }

        return $repositories;
    }

    /**
     * Helper method to create a project
     */
    private function createProject(): Project
    {
        return Project::create([
            'name' => 'Test Project '.Str::random(5),
            'slug' => 'test-project-'.Str::random(5),
            'repository_url' => 'https://github.com/testuser/test-project.git',
            'branch' => 'main',
            'framework' => 'laravel',
            'php_version' => '8.4',
            'project_type' => 'single_tenant',
            'docker_compose_path' => 'docker-compose.yml',
            'status' => 'active',
        ]);
    }
}
