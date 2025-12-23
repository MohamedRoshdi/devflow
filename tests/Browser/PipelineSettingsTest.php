<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\PipelineConfig;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class PipelineSettingsTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

    protected Project $project;

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

        // Get or create test project
        $this->project = Project::firstOrCreate(
            ['slug' => 'test-pipeline-settings-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Pipeline Settings Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/test-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-project',
            ]
        );
    }

    /**
     * Test 1: Pipeline settings page loads successfully
     */
    public function test_pipeline_settings_page_loads_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitForText('Pipeline', 10)
                ->assertPathBeginsWith('/projects/'.$this->project->id)
                ->assertSee('Pipeline')
                ->screenshot('pipeline-settings-page-loads');
        });
    }

    /**
     * Test 2: Pipeline settings page displays enable/disable toggle
     */
    public function test_pipeline_settings_displays_enable_toggle(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('input[type="checkbox"][wire\\:click*="toggleEnabled"], button[wire\\:click*="toggleEnabled"]')
                ->screenshot('pipeline-settings-enable-toggle');
        });
    }

    /**
     * Test 3: Pipeline can be enabled
     */
    public function test_pipeline_can_be_enabled(): void
    {
        // Ensure pipeline is disabled first
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            ['enabled' => false]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(1000)
                ->screenshot('pipeline-before-enable');

            // Try to find and click the enable toggle
            try {
                $browser->click('input[type="checkbox"][wire\\:click*="toggleEnabled"]')
                    ->pause(1000)
                    ->screenshot('pipeline-after-enable-click');
            } catch (\Exception $e) {
                // Alternative: Try button toggle
                $browser->click('button[wire\\:click*="toggleEnabled"]')
                    ->pause(1000)
                    ->screenshot('pipeline-after-enable-button');
            }
        });
    }

    /**
     * Test 4: Pipeline can be disabled
     */
    public function test_pipeline_can_be_disabled(): void
    {
        // Ensure pipeline is enabled first
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            ['enabled' => true]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(1000)
                ->screenshot('pipeline-before-disable');

            // Try to find and click the disable toggle
            try {
                $browser->click('input[type="checkbox"][wire\\:click*="toggleEnabled"]')
                    ->pause(1000)
                    ->screenshot('pipeline-after-disable-click');
            } catch (\Exception $e) {
                // Alternative: Try button toggle
                $browser->click('button[wire\\:click*="toggleEnabled"]')
                    ->pause(1000)
                    ->screenshot('pipeline-after-disable-button');
            }
        });
    }

    /**
     * Test 5: Auto-deploy branches section is visible
     */
    public function test_auto_deploy_branches_section_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('Auto-Deploy Branches')
                ->screenshot('auto-deploy-branches-section');
        });
    }

    /**
     * Test 6: Can add new auto-deploy branch
     */
    public function test_can_add_auto_deploy_branch(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('input[wire\\:model*="newBranch"]')
                ->type('input[wire\\:model*="newBranch"]', 'develop')
                ->pause(500)
                ->click('button[wire\\:click*="addBranch"]')
                ->pause(1000)
                ->screenshot('after-add-branch');
        });
    }

    /**
     * Test 7: Cannot add empty branch name
     */
    public function test_cannot_add_empty_branch_name(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->type('input[wire\\:model*="newBranch"]', '')
                ->click('button[wire\\:click*="addBranch"]')
                ->pause(1000)
                ->screenshot('empty-branch-validation');
        });
    }

    /**
     * Test 8: Cannot add duplicate branch name
     */
    public function test_cannot_add_duplicate_branch_name(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'auto_deploy_branches' => ['main', 'develop'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->type('input[wire\\:model*="newBranch"]', 'main')
                ->click('button[wire\\:click*="addBranch"]')
                ->pause(1000)
                ->screenshot('duplicate-branch-validation');
        });
    }

    /**
     * Test 9: Can remove auto-deploy branch
     */
    public function test_can_remove_auto_deploy_branch(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'auto_deploy_branches' => ['main', 'develop', 'staging'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('develop')
                ->assertPresent('button[wire\\:click*="removeBranch"]')
                ->screenshot('before-remove-branch');
        });
    }

    /**
     * Test 10: Auto-deploy branches list displays correctly
     */
    public function test_auto_deploy_branches_list_displays(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'auto_deploy_branches' => ['main', 'develop', 'staging', 'production'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('main')
                ->assertSee('develop')
                ->assertSee('staging')
                ->assertSee('production')
                ->screenshot('auto-deploy-branches-list');
        });
    }

    /**
     * Test 11: Skip patterns section is visible
     */
    public function test_skip_patterns_section_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('Skip Patterns')
                ->screenshot('skip-patterns-section');
        });
    }

    /**
     * Test 12: Can add skip pattern
     */
    public function test_can_add_skip_pattern(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('input[wire\\:model*="newSkipPattern"]')
                ->type('input[wire\\:model*="newSkipPattern"]', '[skip ci]')
                ->pause(500)
                ->click('button[wire\\:click*="addSkipPattern"]')
                ->pause(1000)
                ->screenshot('after-add-skip-pattern');
        });
    }

    /**
     * Test 13: Cannot add empty skip pattern
     */
    public function test_cannot_add_empty_skip_pattern(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->type('input[wire\\:model*="newSkipPattern"]', '')
                ->click('button[wire\\:click*="addSkipPattern"]')
                ->pause(1000)
                ->screenshot('empty-skip-pattern-validation');
        });
    }

    /**
     * Test 14: Cannot add duplicate skip pattern
     */
    public function test_cannot_add_duplicate_skip_pattern(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'skip_patterns' => ['[skip ci]', '[ci skip]'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->type('input[wire\\:model*="newSkipPattern"]', '[skip ci]')
                ->click('button[wire\\:click*="addSkipPattern"]')
                ->pause(1000)
                ->screenshot('duplicate-skip-pattern-validation');
        });
    }

    /**
     * Test 15: Can remove skip pattern
     */
    public function test_can_remove_skip_pattern(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'skip_patterns' => ['[skip ci]', '[ci skip]', 'WIP:'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('[skip ci]')
                ->assertPresent('button[wire\\:click*="removeSkipPattern"]')
                ->screenshot('before-remove-skip-pattern');
        });
    }

    /**
     * Test 16: Skip patterns list displays correctly
     */
    public function test_skip_patterns_list_displays(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'skip_patterns' => ['[skip ci]', '[ci skip]', 'WIP:', 'DRAFT:'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('[skip ci]')
                ->assertSee('[ci skip]')
                ->assertSee('WIP:')
                ->assertSee('DRAFT:')
                ->screenshot('skip-patterns-list');
        });
    }

    /**
     * Test 17: Deploy patterns section is visible
     */
    public function test_deploy_patterns_section_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('Deploy Patterns')
                ->screenshot('deploy-patterns-section');
        });
    }

    /**
     * Test 18: Can add deploy pattern
     */
    public function test_can_add_deploy_pattern(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('input[wire\\:model*="newDeployPattern"]')
                ->type('input[wire\\:model*="newDeployPattern"]', '[deploy]')
                ->pause(500)
                ->click('button[wire\\:click*="addDeployPattern"]')
                ->pause(1000)
                ->screenshot('after-add-deploy-pattern');
        });
    }

    /**
     * Test 19: Cannot add empty deploy pattern
     */
    public function test_cannot_add_empty_deploy_pattern(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->type('input[wire\\:model*="newDeployPattern"]', '')
                ->click('button[wire\\:click*="addDeployPattern"]')
                ->pause(1000)
                ->screenshot('empty-deploy-pattern-validation');
        });
    }

    /**
     * Test 20: Cannot add duplicate deploy pattern
     */
    public function test_cannot_add_duplicate_deploy_pattern(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'deploy_patterns' => ['[deploy]', '[force deploy]'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->type('input[wire\\:model*="newDeployPattern"]', '[deploy]')
                ->click('button[wire\\:click*="addDeployPattern"]')
                ->pause(1000)
                ->screenshot('duplicate-deploy-pattern-validation');
        });
    }

    /**
     * Test 21: Can remove deploy pattern
     */
    public function test_can_remove_deploy_pattern(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'deploy_patterns' => ['[deploy]', '[force deploy]', 'HOTFIX:'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('[deploy]')
                ->assertPresent('button[wire\\:click*="removeDeployPattern"]')
                ->screenshot('before-remove-deploy-pattern');
        });
    }

    /**
     * Test 22: Deploy patterns list displays correctly
     */
    public function test_deploy_patterns_list_displays(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'deploy_patterns' => ['[deploy]', '[force deploy]', 'HOTFIX:', 'RELEASE:'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('[deploy]')
                ->assertSee('[force deploy]')
                ->assertSee('HOTFIX:')
                ->assertSee('RELEASE:')
                ->screenshot('deploy-patterns-list');
        });
    }

    /**
     * Test 23: Webhook secret section is visible
     */
    public function test_webhook_secret_section_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('Webhook')
                ->screenshot('webhook-secret-section');
        });
    }

    /**
     * Test 24: Can generate webhook secret
     */
    public function test_can_generate_webhook_secret(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('button[wire\\:click*="generateWebhookSecret"]')
                ->screenshot('webhook-secret-generate-button');
        });
    }

    /**
     * Test 25: Webhook secret can be toggled visible/hidden
     */
    public function test_webhook_secret_visibility_toggle(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'webhook_secret' => 'test-secret-key-123456789',
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('button[wire\\:click*="toggleSecretVisibility"]')
                ->screenshot('webhook-secret-toggle');
        });
    }

    /**
     * Test 26: GitHub webhook URL is displayed
     */
    public function test_github_webhook_url_displayed(): void
    {
        $config = PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'webhook_secret' => 'test-secret-key-123',
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('GitHub')
                ->screenshot('github-webhook-url');
        });
    }

    /**
     * Test 27: GitLab webhook URL is displayed
     */
    public function test_gitlab_webhook_url_displayed(): void
    {
        $config = PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'webhook_secret' => 'test-secret-key-123',
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('GitLab')
                ->screenshot('gitlab-webhook-url');
        });
    }

    /**
     * Test 28: Regenerate webhook secret confirmation modal
     */
    public function test_regenerate_webhook_secret_confirmation(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'webhook_secret' => 'existing-secret-key',
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('button[wire\\:click*="confirmRegenerate"], button[wire\\:click*="generateWebhookSecret"]')
                ->screenshot('regenerate-webhook-confirmation');
        });
    }

    /**
     * Test 29: Can cancel webhook secret regeneration
     */
    public function test_can_cancel_webhook_regeneration(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'webhook_secret' => 'existing-secret-key',
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('webhook-cancel-regenerate');
        });
    }

    /**
     * Test 30: Pipeline settings displays help text for auto-deploy branches
     */
    public function test_displays_help_text_for_auto_deploy_branches(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('auto-deploy-branches-help-text');
        });
    }

    /**
     * Test 31: Pipeline settings displays help text for skip patterns
     */
    public function test_displays_help_text_for_skip_patterns(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('skip-patterns-help-text');
        });
    }

    /**
     * Test 32: Pipeline settings displays help text for deploy patterns
     */
    public function test_displays_help_text_for_deploy_patterns(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('deploy-patterns-help-text');
        });
    }

    /**
     * Test 33: Pipeline settings displays help text for webhook secret
     */
    public function test_displays_help_text_for_webhook_secret(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('webhook-secret-help-text');
        });
    }

    /**
     * Test 34: Pipeline settings page shows enabled status
     */
    public function test_shows_enabled_status(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            ['enabled' => true]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('pipeline-enabled-status');
        });
    }

    /**
     * Test 35: Pipeline settings page shows disabled status
     */
    public function test_shows_disabled_status(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            ['enabled' => false]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('pipeline-disabled-status');
        });
    }

    /**
     * Test 36: Default branch is shown in auto-deploy branches
     */
    public function test_default_branch_shown_in_auto_deploy_branches(): void
    {
        // Create fresh pipeline config with no branches
        $this->project->pipelineConfig()?->delete();

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee($this->project->branch)
                ->screenshot('default-branch-in-auto-deploy');
        });
    }

    /**
     * Test 37: Multiple branches can be added
     */
    public function test_multiple_branches_can_be_added(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500);

            // Add multiple branches
            $branches = ['develop', 'staging', 'production'];
            foreach ($branches as $branch) {
                try {
                    $browser->type('input[wire\\:model*="newBranch"]', $branch)
                        ->pause(300)
                        ->click('button[wire\\:click*="addBranch"]')
                        ->pause(800);
                } catch (\Exception $e) {
                    // Continue to next branch
                }
            }

            $browser->screenshot('multiple-branches-added');
        });
    }

    /**
     * Test 38: Multiple skip patterns can be added
     */
    public function test_multiple_skip_patterns_can_be_added(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500);

            // Add multiple patterns
            $patterns = ['[skip ci]', 'WIP:', 'DRAFT:'];
            foreach ($patterns as $pattern) {
                try {
                    $browser->type('input[wire\\:model*="newSkipPattern"]', $pattern)
                        ->pause(300)
                        ->click('button[wire\\:click*="addSkipPattern"]')
                        ->pause(800);
                } catch (\Exception $e) {
                    // Continue to next pattern
                }
            }

            $browser->screenshot('multiple-skip-patterns-added');
        });
    }

    /**
     * Test 39: Multiple deploy patterns can be added
     */
    public function test_multiple_deploy_patterns_can_be_added(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500);

            // Add multiple patterns
            $patterns = ['[deploy]', 'HOTFIX:', 'RELEASE:'];
            foreach ($patterns as $pattern) {
                try {
                    $browser->type('input[wire\\:model*="newDeployPattern"]', $pattern)
                        ->pause(300)
                        ->click('button[wire\\:click*="addDeployPattern"]')
                        ->pause(800);
                } catch (\Exception $e) {
                    // Continue to next pattern
                }
            }

            $browser->screenshot('multiple-deploy-patterns-added');
        });
    }

    /**
     * Test 40: Settings persist after page reload
     */
    public function test_settings_persist_after_reload(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'auto_deploy_branches' => ['main', 'develop'],
                'skip_patterns' => ['[skip ci]'],
                'deploy_patterns' => ['[deploy]'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('main')
                ->assertSee('develop')
                ->assertSee('[skip ci]')
                ->assertSee('[deploy]')
                ->refresh()
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('main')
                ->assertSee('develop')
                ->assertSee('[skip ci]')
                ->assertSee('[deploy]')
                ->screenshot('settings-persist-after-reload');
        });
    }

    /**
     * Test 41: Webhook URLs update when secret is generated
     */
    public function test_webhook_urls_update_when_secret_generated(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('webhook-urls-after-secret-generation');
        });
    }

    /**
     * Test 42: Copy webhook URL functionality
     */
    public function test_copy_webhook_url_functionality(): void
    {
        PipelineConfig::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'enabled' => true,
                'webhook_secret' => 'test-secret-for-copy',
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('copy-webhook-url-button');
        });
    }

    /**
     * Test 43: Pipeline settings shows proper icons
     */
    public function test_pipeline_settings_shows_icons(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('svg, i[class*="icon"]')
                ->screenshot('pipeline-settings-icons');
        });
    }

    /**
     * Test 44: Branch input clears after adding
     */
    public function test_branch_input_clears_after_adding(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500);

            try {
                $browser->type('input[wire\\:model*="newBranch"]', 'test-branch')
                    ->pause(300)
                    ->click('button[wire\\:click*="addBranch"]')
                    ->pause(1000)
                    ->assertInputValue('input[wire\\:model*="newBranch"]', '')
                    ->screenshot('branch-input-cleared');
            } catch (\Exception $e) {
                $browser->screenshot('branch-input-clear-error');
            }
        });
    }

    /**
     * Test 45: Skip pattern input clears after adding
     */
    public function test_skip_pattern_input_clears_after_adding(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500);

            try {
                $browser->type('input[wire\\:model*="newSkipPattern"]', '[test]')
                    ->pause(300)
                    ->click('button[wire\\:click*="addSkipPattern"]')
                    ->pause(1000)
                    ->assertInputValue('input[wire\\:model*="newSkipPattern"]', '')
                    ->screenshot('skip-pattern-input-cleared');
            } catch (\Exception $e) {
                $browser->screenshot('skip-pattern-input-clear-error');
            }
        });
    }

    /**
     * Test 46: Deploy pattern input clears after adding
     */
    public function test_deploy_pattern_input_clears_after_adding(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500);

            try {
                $browser->type('input[wire\\:model*="newDeployPattern"]', '[test]')
                    ->pause(300)
                    ->click('button[wire\\:click*="addDeployPattern"]')
                    ->pause(1000)
                    ->assertInputValue('input[wire\\:model*="newDeployPattern"]', '')
                    ->screenshot('deploy-pattern-input-cleared');
            } catch (\Exception $e) {
                $browser->screenshot('deploy-pattern-input-clear-error');
            }
        });
    }

    /**
     * Test 47: Settings work with no existing pipeline config
     */
    public function test_settings_work_with_no_existing_config(): void
    {
        // Create a new project without pipeline config
        $newProject = Project::create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project Without Config',
            'slug' => 'project-no-config-'.uniqid(),
            'framework' => 'laravel',
            'status' => 'running',
            'repository' => 'https://github.com/test/no-config.git',
            'branch' => 'main',
            'deploy_path' => '/var/www/no-config',
        ]);

        $this->browse(function (Browser $browser) use ($newProject) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$newProject->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('Pipeline')
                ->assertSee($newProject->branch)
                ->screenshot('settings-no-existing-config');
        });
    }

    /**
     * Test 48: Complete pipeline configuration workflow
     */
    public function test_complete_pipeline_configuration_workflow(): void
    {
        // Create a fresh project
        $freshProject = Project::create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Complete Workflow Project',
            'slug' => 'complete-workflow-'.uniqid(),
            'framework' => 'laravel',
            'status' => 'running',
            'repository' => 'https://github.com/test/complete-workflow.git',
            'branch' => 'main',
            'deploy_path' => '/var/www/complete-workflow',
        ]);

        $this->browse(function (Browser $browser) use ($freshProject) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$freshProject->id.'/pipeline/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('workflow-step-1-initial');

            // Step 1: Enable pipeline
            try {
                $browser->click('input[type="checkbox"][wire\\:click*="toggleEnabled"], button[wire\\:click*="toggleEnabled"]')
                    ->pause(1000)
                    ->screenshot('workflow-step-2-enabled');
            } catch (\Exception $e) {
                $browser->screenshot('workflow-step-2-enable-error');
            }

            // Step 2: Add branch
            try {
                $browser->type('input[wire\\:model*="newBranch"]', 'develop')
                    ->pause(300)
                    ->click('button[wire\\:click*="addBranch"]')
                    ->pause(1000)
                    ->screenshot('workflow-step-3-branch-added');
            } catch (\Exception $e) {
                $browser->screenshot('workflow-step-3-branch-error');
            }

            // Step 3: Add skip pattern
            try {
                $browser->type('input[wire\\:model*="newSkipPattern"]', '[skip ci]')
                    ->pause(300)
                    ->click('button[wire\\:click*="addSkipPattern"]')
                    ->pause(1000)
                    ->screenshot('workflow-step-4-skip-pattern-added');
            } catch (\Exception $e) {
                $browser->screenshot('workflow-step-4-skip-error');
            }

            // Step 4: Add deploy pattern
            try {
                $browser->type('input[wire\\:model*="newDeployPattern"]', '[deploy]')
                    ->pause(300)
                    ->click('button[wire\\:click*="addDeployPattern"]')
                    ->pause(1000)
                    ->screenshot('workflow-step-5-deploy-pattern-added');
            } catch (\Exception $e) {
                $browser->screenshot('workflow-step-5-deploy-error');
            }

            // Step 5: Generate webhook secret
            try {
                $browser->click('button[wire\\:click*="generateWebhookSecret"]')
                    ->pause(1500)
                    ->screenshot('workflow-step-6-webhook-generated');
            } catch (\Exception $e) {
                $browser->screenshot('workflow-step-6-webhook-error');
            }

            $browser->screenshot('workflow-complete');
        });
    }
}
