<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Projects\PipelineSettings;
use App\Models\PipelineConfig;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PipelineSettingsTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private Server $server;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create();
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'branch' => 'main',
        ]);
    }

    // ===== COMPONENT RENDERING =====

    public function test_component_renders(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.pipeline-settings');
    }

    public function test_component_renders_without_config(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertSet('enabled', true)
            ->assertSet('auto_deploy_branches', ['main']);
    }

    public function test_component_renders_with_existing_config(): void
    {
        $config = PipelineConfig::create([
            'project_id' => $this->project->id,
            'enabled' => false,
            'auto_deploy_branches' => ['develop', 'staging'],
            'skip_patterns' => ['[skip ci]', '[ci skip]'],
            'deploy_patterns' => ['[deploy]'],
            'webhook_secret' => 'test-secret-123',
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertSet('enabled', false)
            ->assertSet('auto_deploy_branches', ['develop', 'staging'])
            ->assertSet('skip_patterns', ['[skip ci]', '[ci skip]'])
            ->assertSet('deploy_patterns', ['[deploy]'])
            ->assertSet('webhook_secret', 'test-secret-123');
    }

    // ===== TOGGLE ENABLED =====

    public function test_can_toggle_enabled_on(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'enabled' => false,
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertSet('enabled', false)
            ->call('toggleEnabled')
            ->assertSet('enabled', true)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success' &&
                str_contains($data['message'], 'enabled'));

        $this->assertDatabaseHas('pipeline_configs', [
            'project_id' => $this->project->id,
            'enabled' => true,
        ]);
    }

    public function test_can_toggle_enabled_off(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'enabled' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertSet('enabled', true)
            ->call('toggleEnabled')
            ->assertSet('enabled', false)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success' &&
                str_contains($data['message'], 'disabled'));
    }

    public function test_toggle_creates_config_if_not_exists(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->call('toggleEnabled');

        $this->assertDatabaseHas('pipeline_configs', [
            'project_id' => $this->project->id,
        ]);
    }

    // ===== AUTO-DEPLOY BRANCHES =====

    public function test_can_add_branch(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newBranch', 'develop')
            ->call('addBranch')
            ->assertSet('newBranch', '')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success' &&
                str_contains($data['message'], 'develop'));

        $config = PipelineConfig::where('project_id', $this->project->id)->first();
        $this->assertNotNull($config);
        $branches = $config->auto_deploy_branches;
        $this->assertIsArray($branches);
        $this->assertContains('develop', $branches);
    }

    public function test_add_branch_validates_empty(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newBranch', '')
            ->call('addBranch')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' &&
                str_contains($data['message'], 'cannot be empty'));
    }

    public function test_add_branch_validates_whitespace_only(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newBranch', '   ')
            ->call('addBranch')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error');
    }

    public function test_add_branch_prevents_duplicates(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'auto_deploy_branches' => ['main', 'develop'],
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newBranch', 'develop')
            ->call('addBranch')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' &&
                str_contains($data['message'], 'already exists'));
    }

    public function test_can_remove_branch(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'auto_deploy_branches' => ['main', 'develop', 'staging'],
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->call('removeBranch', 1)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success' &&
                str_contains($data['message'], 'develop'));

        $config = PipelineConfig::where('project_id', $this->project->id)->first();
        $this->assertNotNull($config);
        $branches = $config->auto_deploy_branches;
        $this->assertIsArray($branches);
        $this->assertNotContains('develop', $branches);
        $this->assertContains('main', $branches);
        $this->assertContains('staging', $branches);
    }

    public function test_remove_branch_reindexes_array(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'auto_deploy_branches' => ['main', 'develop', 'staging'],
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->call('removeBranch', 1);

        $branches = $component->get('auto_deploy_branches');
        $this->assertEquals([0, 1], array_keys($branches));
    }

    public function test_remove_nonexistent_branch_does_nothing(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'auto_deploy_branches' => ['main'],
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->call('removeBranch', 99);

        $config = PipelineConfig::where('project_id', $this->project->id)->first();
        $this->assertNotNull($config);
        $branches = $config->auto_deploy_branches;
        $this->assertIsArray($branches);
        $this->assertCount(1, $branches);
    }

    // ===== SKIP PATTERNS =====

    public function test_can_add_skip_pattern(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newSkipPattern', '[skip ci]')
            ->call('addSkipPattern')
            ->assertSet('newSkipPattern', '')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success' &&
                str_contains($data['message'], '[skip ci]'));

        $config = PipelineConfig::where('project_id', $this->project->id)->first();
        $this->assertNotNull($config);
        $patterns = $config->skip_patterns;
        $this->assertIsArray($patterns);
        $this->assertContains('[skip ci]', $patterns);
    }

    public function test_add_skip_pattern_validates_empty(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newSkipPattern', '')
            ->call('addSkipPattern')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' &&
                str_contains($data['message'], 'cannot be empty'));
    }

    public function test_add_skip_pattern_prevents_duplicates(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'skip_patterns' => ['[skip ci]'],
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newSkipPattern', '[skip ci]')
            ->call('addSkipPattern')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' &&
                str_contains($data['message'], 'already exists'));
    }

    public function test_can_remove_skip_pattern(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'skip_patterns' => ['[skip ci]', '[ci skip]', '[no deploy]'],
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->call('removeSkipPattern', 1)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success' &&
                str_contains($data['message'], '[ci skip]'));

        $config = PipelineConfig::where('project_id', $this->project->id)->first();
        $this->assertNotNull($config);
        $patterns = $config->skip_patterns;
        $this->assertIsArray($patterns);
        $this->assertNotContains('[ci skip]', $patterns);
    }

    public function test_remove_skip_pattern_reindexes_array(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'skip_patterns' => ['[skip ci]', '[ci skip]', '[no deploy]'],
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->call('removeSkipPattern', 0);

        $patterns = $component->get('skip_patterns');
        $this->assertEquals([0, 1], array_keys($patterns));
    }

    // ===== DEPLOY PATTERNS =====

    public function test_can_add_deploy_pattern(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newDeployPattern', '[deploy]')
            ->call('addDeployPattern')
            ->assertSet('newDeployPattern', '')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success' &&
                str_contains($data['message'], '[deploy]'));

        $config = PipelineConfig::where('project_id', $this->project->id)->first();
        $this->assertNotNull($config);
        $patterns = $config->deploy_patterns;
        $this->assertIsArray($patterns);
        $this->assertContains('[deploy]', $patterns);
    }

    public function test_add_deploy_pattern_validates_empty(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newDeployPattern', '')
            ->call('addDeployPattern')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' &&
                str_contains($data['message'], 'cannot be empty'));
    }

    public function test_add_deploy_pattern_prevents_duplicates(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'deploy_patterns' => ['[deploy]'],
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newDeployPattern', '[deploy]')
            ->call('addDeployPattern')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' &&
                str_contains($data['message'], 'already exists'));
    }

    public function test_can_remove_deploy_pattern(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'deploy_patterns' => ['[deploy]', '[force deploy]'],
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->call('removeDeployPattern', 0)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success' &&
                str_contains($data['message'], '[deploy]'));

        $config = PipelineConfig::where('project_id', $this->project->id)->first();
        $this->assertNotNull($config);
        $patterns = $config->deploy_patterns;
        $this->assertIsArray($patterns);
        $this->assertNotContains('[deploy]', $patterns);
        $this->assertContains('[force deploy]', $patterns);
    }

    public function test_remove_deploy_pattern_reindexes_array(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'deploy_patterns' => ['[deploy]', '[force deploy]', '[release]'],
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->call('removeDeployPattern', 1);

        $patterns = $component->get('deploy_patterns');
        $this->assertEquals([0, 1], array_keys($patterns));
    }

    // ===== WEBHOOK SECRET =====

    public function test_can_generate_webhook_secret(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertSet('webhook_secret', null)
            ->call('generateWebhookSecret')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success' &&
                str_contains($data['message'], 'generated'));

        $secret = $component->get('webhook_secret');
        $this->assertNotNull($secret);
        $this->assertEquals(64, strlen($secret)); // 32 bytes = 64 hex chars

        $this->assertDatabaseHas('pipeline_configs', [
            'project_id' => $this->project->id,
            'webhook_secret' => $secret,
        ]);
    }

    public function test_generate_secret_updates_webhook_urls(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->call('generateWebhookSecret');

        $githubUrl = $component->get('githubWebhookUrl');
        $gitlabUrl = $component->get('gitlabWebhookUrl');

        $this->assertNotEmpty($githubUrl);
        $this->assertNotEmpty($gitlabUrl);
        $this->assertStringContainsString('webhooks/github', $githubUrl);
        $this->assertStringContainsString('webhooks/gitlab', $gitlabUrl);
    }

    public function test_generate_secret_closes_regenerate_confirm(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('showRegenerateConfirm', true)
            ->call('generateWebhookSecret')
            ->assertSet('showRegenerateConfirm', false);
    }

    public function test_can_regenerate_existing_secret(): void
    {
        $originalSecret = 'original-secret-12345678901234567890123456789012345678901234';
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'webhook_secret' => $originalSecret,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertSet('webhook_secret', $originalSecret)
            ->call('generateWebhookSecret');

        $newSecret = $component->get('webhook_secret');
        $this->assertNotEquals($originalSecret, $newSecret);
    }

    // ===== REGENERATE CONFIRMATION MODAL =====

    public function test_can_open_regenerate_confirm(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertSet('showRegenerateConfirm', false)
            ->call('confirmRegenerate')
            ->assertSet('showRegenerateConfirm', true);
    }

    public function test_can_cancel_regenerate(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('showRegenerateConfirm', true)
            ->call('cancelRegenerate')
            ->assertSet('showRegenerateConfirm', false);
    }

    // ===== SECRET VISIBILITY =====

    public function test_can_toggle_secret_visibility(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'webhook_secret' => 'test-secret',
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertSet('showSecret', false)
            ->call('toggleSecretVisibility')
            ->assertSet('showSecret', true)
            ->call('toggleSecretVisibility')
            ->assertSet('showSecret', false);
    }

    // ===== WEBHOOK URL GENERATION =====

    public function test_webhook_urls_set_when_secret_exists(): void
    {
        PipelineConfig::create([
            'project_id' => $this->project->id,
            'webhook_secret' => 'test-secret-abc',
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(PipelineSettings::class, ['project' => $this->project]);

        $githubUrl = $component->get('githubWebhookUrl');
        $gitlabUrl = $component->get('gitlabWebhookUrl');

        $this->assertStringContainsString('test-secret-abc', $githubUrl);
        $this->assertStringContainsString('test-secret-abc', $gitlabUrl);
    }

    public function test_webhook_urls_empty_without_secret(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(PipelineSettings::class, ['project' => $this->project]);

        $this->assertEmpty($component->get('githubWebhookUrl'));
        $this->assertEmpty($component->get('gitlabWebhookUrl'));
    }

    // ===== DEFAULT VALUES =====

    public function test_default_values_without_config(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertSet('enabled', true)
            ->assertSet('auto_deploy_branches', ['main'])
            ->assertSet('skip_patterns', [])
            ->assertSet('deploy_patterns', [])
            ->assertSet('webhook_secret', null)
            ->assertSet('newBranch', '')
            ->assertSet('newSkipPattern', '')
            ->assertSet('newDeployPattern', '')
            ->assertSet('showSecret', false)
            ->assertSet('showRegenerateConfirm', false);
    }

    public function test_uses_project_branch_as_default_auto_deploy(): void
    {
        $this->project->update(['branch' => 'develop']);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertSet('auto_deploy_branches', ['develop']);
    }

    // ===== CONFIG PERSISTENCE =====

    public function test_save_creates_new_config(): void
    {
        $this->assertDatabaseMissing('pipeline_configs', [
            'project_id' => $this->project->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newBranch', 'develop')
            ->call('addBranch');

        $this->assertDatabaseHas('pipeline_configs', [
            'project_id' => $this->project->id,
        ]);
    }

    public function test_save_updates_existing_config(): void
    {
        $config = PipelineConfig::create([
            'project_id' => $this->project->id,
            'enabled' => true,
            'auto_deploy_branches' => ['main'],
        ]);

        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newBranch', 'develop')
            ->call('addBranch');

        $config->refresh();
        $this->assertContains('develop', $config->auto_deploy_branches);
    }

    // ===== MULTIPLE OPERATIONS =====

    public function test_can_perform_multiple_operations(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newBranch', 'develop')
            ->call('addBranch')
            ->set('newBranch', 'staging')
            ->call('addBranch')
            ->set('newSkipPattern', '[wip]')
            ->call('addSkipPattern')
            ->set('newDeployPattern', '[hotfix]')
            ->call('addDeployPattern');

        $config = PipelineConfig::where('project_id', $this->project->id)->first();
        $this->assertNotNull($config);
        $branches = $config->auto_deploy_branches;
        $skipPatterns = $config->skip_patterns;
        $deployPatterns = $config->deploy_patterns;
        $this->assertIsArray($branches);
        $this->assertIsArray($skipPatterns);
        $this->assertIsArray($deployPatterns);
        $this->assertCount(3, $branches); // main, develop, staging
        $this->assertCount(1, $skipPatterns);
        $this->assertCount(1, $deployPatterns);
    }

    public function test_operations_after_toggle(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->call('toggleEnabled')
            ->set('newBranch', 'develop')
            ->call('addBranch');

        $config = PipelineConfig::where('project_id', $this->project->id)->first();
        $this->assertNotNull($config);
        $this->assertFalse($config->enabled);
        $branches = $config->auto_deploy_branches;
        $this->assertIsArray($branches);
        $this->assertContains('develop', $branches);
    }

    // ===== EDGE CASES =====

    public function test_handles_null_arrays_in_config(): void
    {
        // Create config with null arrays (edge case)
        $config = new PipelineConfig([
            'project_id' => $this->project->id,
            'enabled' => true,
        ]);
        $config->auto_deploy_branches = null;
        $config->skip_patterns = null;
        $config->deploy_patterns = null;
        $config->save();

        $this->actingAs($this->user);

        // Should not throw any errors
        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertSet('auto_deploy_branches', [])
            ->assertSet('skip_patterns', [])
            ->assertSet('deploy_patterns', []);
    }

    public function test_branch_name_trimmed(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newBranch', '  develop  ')
            ->call('addBranch');

        $config = PipelineConfig::where('project_id', $this->project->id)->first();
        $this->assertNotNull($config);
        $branches = $config->auto_deploy_branches;
        $this->assertIsArray($branches);
        $this->assertContains('develop', $branches);
    }

    public function test_skip_pattern_trimmed(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newSkipPattern', '  [skip ci]  ')
            ->call('addSkipPattern');

        $config = PipelineConfig::where('project_id', $this->project->id)->first();
        $this->assertNotNull($config);
        $patterns = $config->skip_patterns;
        $this->assertIsArray($patterns);
        $this->assertContains('[skip ci]', $patterns);
    }

    public function test_deploy_pattern_trimmed(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->set('newDeployPattern', '  [deploy]  ')
            ->call('addDeployPattern');

        $config = PipelineConfig::where('project_id', $this->project->id)->first();
        $this->assertNotNull($config);
        $patterns = $config->deploy_patterns;
        $this->assertIsArray($patterns);
        $this->assertContains('[deploy]', $patterns);
    }

    // ===== PROJECT RELATIONSHIP =====

    public function test_config_belongs_to_correct_project(): void
    {
        $otherProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        PipelineConfig::create([
            'project_id' => $otherProject->id,
            'enabled' => false,
            'auto_deploy_branches' => ['other-branch'],
        ]);

        $this->actingAs($this->user);

        // Should not load other project's config
        Livewire::test(PipelineSettings::class, ['project' => $this->project])
            ->assertSet('enabled', true)
            ->assertSet('auto_deploy_branches', ['main']);
    }

    // ===== COMPONENT STATE =====

    public function test_pipeline_config_property_set(): void
    {
        $config = PipelineConfig::create([
            'project_id' => $this->project->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(PipelineSettings::class, ['project' => $this->project]);

        $pipelineConfig = $component->get('pipelineConfig');
        $this->assertNotNull($pipelineConfig);
        $this->assertEquals($config->id, $pipelineConfig->id);
    }

    public function test_project_property_set(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(PipelineSettings::class, ['project' => $this->project]);

        $project = $component->get('project');
        $this->assertEquals($this->project->id, $project->id);
    }
}
