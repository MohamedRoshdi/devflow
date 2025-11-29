<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use App\Models\Project;
use App\Models\WebhookDelivery;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class ProjectWebhookSettings extends Component
{
    use WithPagination;

    public Project $project;
    public bool $webhookEnabled = false;
    public ?string $webhookSecret = null;
    public string $webhookUrl = '';
    public string $gitlabWebhookUrl = '';
    public bool $showSecret = false;
    public bool $showRegenerateConfirm = false;
    protected $paginationTheme = 'tailwind';

    public function mount(Project $project)
    {
        // All projects are shared across all users
        $this->project = $project;
        $this->webhookEnabled = $project->webhook_enabled ?? false;
        $this->webhookSecret = $project->webhook_secret;

        // Generate webhook URLs
        if ($this->webhookSecret) {
            $this->webhookUrl = route('webhooks.github', ['secret' => $this->webhookSecret]);
            $this->gitlabWebhookUrl = route('webhooks.gitlab', ['secret' => $this->webhookSecret]);
        }
    }

    public function toggleWebhook()
    {
        $this->webhookEnabled = !$this->webhookEnabled;

        // If enabling webhooks and no secret exists, generate one
        if ($this->webhookEnabled && !$this->webhookSecret) {
            $this->webhookSecret = $this->project->generateWebhookSecret();
            $this->webhookUrl = route('webhooks.github', ['secret' => $this->webhookSecret]);
            $this->gitlabWebhookUrl = route('webhooks.gitlab', ['secret' => $this->webhookSecret]);
        }

        $this->project->update([
            'webhook_enabled' => $this->webhookEnabled,
            'webhook_secret' => $this->webhookSecret,
        ]);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => $this->webhookEnabled ? 'Webhooks enabled successfully!' : 'Webhooks disabled successfully!'
        ]);
    }

    public function regenerateSecret()
    {
        $this->webhookSecret = $this->project->generateWebhookSecret();
        $this->webhookUrl = route('webhooks.github', ['secret' => $this->webhookSecret]);
        $this->gitlabWebhookUrl = route('webhooks.gitlab', ['secret' => $this->webhookSecret]);

        $this->project->update([
            'webhook_secret' => $this->webhookSecret,
        ]);

        $this->showRegenerateConfirm = false;

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Webhook secret regenerated successfully! Please update your Git provider settings.'
        ]);
    }

    public function toggleSecretVisibility()
    {
        $this->showSecret = !$this->showSecret;
    }

    public function confirmRegenerate()
    {
        $this->showRegenerateConfirm = true;
    }

    public function cancelRegenerate()
    {
        $this->showRegenerateConfirm = false;
    }

    #[Computed]
    public function recentDeliveries()
    {
        return WebhookDelivery::where('project_id', $this->project->id)
            ->with('deployment')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function getDeliveryStatusBadgeColor(string $status): string
    {
        return match($status) {
            'success' => 'green',
            'failed' => 'red',
            'ignored' => 'gray',
            'pending' => 'yellow',
            default => 'gray',
        };
    }

    public function render()
    {
        return view('livewire.projects.project-webhook-settings');
    }
}
