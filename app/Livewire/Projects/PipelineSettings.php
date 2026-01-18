<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\PipelineConfig;
use App\Models\Project;
use Livewire\Component;

class PipelineSettings extends Component
{
    public Project $project;

    public ?PipelineConfig $pipelineConfig = null;

    // Configuration fields
    public bool $enabled = true;

    /** @var array<int, string> */
    public array $auto_deploy_branches = [];

    /** @var array<int, string> */
    public array $skip_patterns = [];

    /** @var array<int, string> */
    public array $deploy_patterns = [];

    public ?string $webhook_secret = null;

    // UI state
    public string $newBranch = '';

    public string $newSkipPattern = '';

    public string $newDeployPattern = '';

    public bool $showSecret = false;

    public bool $showRegenerateConfirm = false;

    // Webhook URLs
    public string $githubWebhookUrl = '';

    public string $gitlabWebhookUrl = '';

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->pipelineConfig = $project->pipelineConfig;

        // Load existing config or set defaults
        if ($this->pipelineConfig) {
            $this->enabled = $this->pipelineConfig->enabled;
            $branches = $this->pipelineConfig->auto_deploy_branches ?? [];
            $this->auto_deploy_branches = is_array($branches) ? $branches : [];
            $skip = $this->pipelineConfig->skip_patterns ?? [];
            $this->skip_patterns = is_array($skip) ? $skip : [];
            $deploy = $this->pipelineConfig->deploy_patterns ?? [];
            $this->deploy_patterns = is_array($deploy) ? $deploy : [];
            $this->webhook_secret = $this->pipelineConfig->webhook_secret;
        } else {
            // Default: deploy from the project's main branch
            $this->auto_deploy_branches = [$project->branch];
        }

        // Generate webhook URLs if secret exists
        if ($this->webhook_secret) {
            $this->updateWebhookUrls();
        }
    }

    public function toggleEnabled()
    {
        $this->enabled = ! $this->enabled;
        $this->saveConfig();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => $this->enabled ? 'Pipeline CI/CD enabled successfully!' : 'Pipeline CI/CD disabled successfully!',
        ]);
    }

    public function addBranch()
    {
        $branch = trim($this->newBranch);

        if (empty($branch)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Branch name cannot be empty',
            ]);

            return;
        }

        if (in_array($branch, $this->auto_deploy_branches)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Branch already exists in the list',
            ]);

            return;
        }

        $this->auto_deploy_branches[] = $branch;
        $this->newBranch = '';
        $this->saveConfig();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => "Branch '{$branch}' added successfully",
        ]);
    }

    public function removeBranch(int $index)
    {
        if (isset($this->auto_deploy_branches[$index])) {
            $branch = $this->auto_deploy_branches[$index];
            unset($this->auto_deploy_branches[$index]);
            $this->auto_deploy_branches = array_values($this->auto_deploy_branches); // Re-index
            $this->saveConfig();

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "Branch '{$branch}' removed successfully",
            ]);
        }
    }

    public function addSkipPattern()
    {
        $pattern = trim($this->newSkipPattern);

        if (empty($pattern)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Skip pattern cannot be empty',
            ]);

            return;
        }

        if (in_array($pattern, $this->skip_patterns)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Pattern already exists',
            ]);

            return;
        }

        $this->skip_patterns[] = $pattern;
        $this->newSkipPattern = '';
        $this->saveConfig();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => "Skip pattern '{$pattern}' added successfully",
        ]);
    }

    public function removeSkipPattern(int $index)
    {
        if (isset($this->skip_patterns[$index])) {
            $pattern = $this->skip_patterns[$index];
            unset($this->skip_patterns[$index]);
            $this->skip_patterns = array_values($this->skip_patterns);
            $this->saveConfig();

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "Skip pattern '{$pattern}' removed successfully",
            ]);
        }
    }

    public function addDeployPattern()
    {
        $pattern = trim($this->newDeployPattern);

        if (empty($pattern)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Deploy pattern cannot be empty',
            ]);

            return;
        }

        if (in_array($pattern, $this->deploy_patterns)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Pattern already exists',
            ]);

            return;
        }

        $this->deploy_patterns[] = $pattern;
        $this->newDeployPattern = '';
        $this->saveConfig();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => "Deploy pattern '{$pattern}' added successfully",
        ]);
    }

    public function removeDeployPattern(int $index)
    {
        if (isset($this->deploy_patterns[$index])) {
            $pattern = $this->deploy_patterns[$index];
            unset($this->deploy_patterns[$index]);
            $this->deploy_patterns = array_values($this->deploy_patterns);
            $this->saveConfig();

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "Deploy pattern '{$pattern}' removed successfully",
            ]);
        }
    }

    public function generateWebhookSecret()
    {
        if (! $this->pipelineConfig) {
            $this->pipelineConfig = new PipelineConfig(['project_id' => $this->project->id]);
        }

        $this->webhook_secret = $this->pipelineConfig->generateWebhookSecret();
        $this->updateWebhookUrls();
        $this->saveConfig();

        $this->showRegenerateConfirm = false;

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Webhook secret generated successfully! Please update your Git provider settings.',
        ]);
    }

    public function confirmRegenerate()
    {
        $this->showRegenerateConfirm = true;
    }

    public function cancelRegenerate()
    {
        $this->showRegenerateConfirm = false;
    }

    public function toggleSecretVisibility()
    {
        $this->showSecret = ! $this->showSecret;
    }

    private function updateWebhookUrls()
    {
        if ($this->webhook_secret) {
            $this->githubWebhookUrl = route('webhooks.github', ['secret' => $this->webhook_secret]);
            $this->gitlabWebhookUrl = route('webhooks.gitlab', ['secret' => $this->webhook_secret]);
        }
    }

    private function saveConfig()
    {
        $data = [
            'project_id' => $this->project->id,
            'enabled' => $this->enabled,
            'auto_deploy_branches' => $this->auto_deploy_branches,
            'skip_patterns' => $this->skip_patterns,
            'deploy_patterns' => $this->deploy_patterns,
            'webhook_secret' => $this->webhook_secret,
        ];

        if ($this->pipelineConfig) {
            $this->pipelineConfig->update($data);
        } else {
            $this->pipelineConfig = PipelineConfig::create($data);
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.pipeline-settings');
    }
}
