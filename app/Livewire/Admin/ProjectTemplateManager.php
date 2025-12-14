<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\ProjectTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\{Validate, Computed};

class ProjectTemplateManager extends Component
{
    public string $activeTab = 'list';

    public ?int $editingTemplateId = null;

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public bool $showPreviewModal = false;

    public ?int $deletingTemplateId = null;

    public ?int $previewingTemplateId = null;

    // Form fields
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|regex:/^[a-z0-9-]+$/')]
    public string $slug = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|in:laravel,react,vue,nodejs,php,python,docker,custom')]
    public string $framework = 'laravel';

    #[Validate('nullable|string|max:50')]
    public string $icon = '';

    #[Validate('nullable|string|max:50')]
    public string $color = '';

    #[Validate('required|string|max:100')]
    public string $default_branch = 'main';

    #[Validate('nullable|in:8.1,8.2,8.3,8.4')]
    public string $php_version = '8.4';

    #[Validate('nullable|string|max:20')]
    public string $node_version = '';

    #[Validate('nullable|string|max:500')]
    public string $health_check_path = '';

    public bool $is_active = true;

    // Commands
    /** @var array<int, string> */
    public array $install_commands = [];

    /** @var array<int, string> */
    public array $build_commands = [];

    /** @var array<int, string> */
    public array $post_deploy_commands = [];

    // Environment template
    /** @var array<string, string> */
    public array $env_template = [];

    public string $docker_compose_template = '';

    public string $dockerfile_template = '';

    // Temporary fields for adding commands/env
    public string $newInstallCommand = '';

    public string $newBuildCommand = '';

    public string $newPostDeployCommand = '';

    public string $newEnvKey = '';

    public string $newEnvValue = '';

    public string $searchTerm = '';

    public string $frameworkFilter = 'all';

    public function mount(): void
    {
        // Only super-admin and admin users can manage project templates
        $user = auth()->user();
        abort_unless(
            $user && $user->hasRole(['super-admin', 'admin']),
            403,
            'You do not have permission to manage project templates.'
        );
    }

    #[Computed]
    public function templates()
    {
        return ProjectTemplate::query()
            ->when($this->searchTerm, fn ($q) => $q->where('name', 'like', "%{$this->searchTerm}%"))
            ->when($this->frameworkFilter !== 'all', fn ($q) => $q->where('framework', $this->frameworkFilter))
            ->with('user')
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();
    }

    public function updatedName(): void
    {
        if (! $this->editingTemplateId) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal(int $templateId): void
    {
        $template = ProjectTemplate::findOrFail($templateId);

        $this->editingTemplateId = $template->id;
        $this->name = $template->name;
        $this->slug = $template->slug;
        $this->description = $template->description ?? '';
        $this->framework = $template->framework;
        $this->icon = $template->icon ?? '';
        $this->color = $template->color ?? '';
        $this->default_branch = $template->default_branch;
        $this->php_version = $template->php_version ?? '8.4';
        $this->node_version = $template->node_version ?? '';
        $this->health_check_path = $template->health_check_path ?? '';
        $this->is_active = $template->is_active;
        $this->install_commands = $template->install_commands ?? [];
        $this->build_commands = $template->build_commands ?? [];
        $this->post_deploy_commands = $template->post_deploy_commands ?? [];
        $envTemplate = $template->env_template ?? [];
        $this->env_template = is_array($envTemplate) ? array_map('strval', $envTemplate) : [];
        $this->docker_compose_template = $template->docker_compose_template ?? '';
        $this->dockerfile_template = $template->dockerfile_template ?? '';

        $this->showEditModal = true;
    }

    public function createTemplate(): void
    {
        $this->validate();

        try {
            ProjectTemplate::create([
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description ?: null,
                'framework' => $this->framework,
                'icon' => $this->icon ?: null,
                'color' => $this->color ?: null,
                'is_system' => false,
                'is_active' => $this->is_active,
                'user_id' => (int) auth()->id(),
                'default_branch' => $this->default_branch,
                'php_version' => $this->php_version ?: null,
                'node_version' => $this->node_version ?: null,
                'install_commands' => ! empty($this->install_commands) ? $this->install_commands : null,
                'build_commands' => ! empty($this->build_commands) ? $this->build_commands : null,
                'post_deploy_commands' => ! empty($this->post_deploy_commands) ? $this->post_deploy_commands : null,
                'env_template' => ! empty($this->env_template) ? $this->env_template : null,
                'docker_compose_template' => $this->docker_compose_template ?: null,
                'dockerfile_template' => $this->dockerfile_template ?: null,
                'health_check_path' => $this->health_check_path ?: null,
            ]);

            session()->flash('message', 'Template created successfully!');
            $this->showCreateModal = false;
            $this->resetForm();
            unset($this->templates);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create template: '.$e->getMessage());
        }
    }

    public function updateTemplate(): void
    {
        $this->validate();

        try {
            $template = ProjectTemplate::findOrFail($this->editingTemplateId);

            if ($template->is_system) {
                session()->flash('error', 'Cannot edit system templates');

                return;
            }

            $template->update([
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description ?: null,
                'framework' => $this->framework,
                'icon' => $this->icon ?: null,
                'color' => $this->color ?: null,
                'is_active' => $this->is_active,
                'default_branch' => $this->default_branch,
                'php_version' => $this->php_version ?: null,
                'node_version' => $this->node_version ?: null,
                'install_commands' => ! empty($this->install_commands) ? $this->install_commands : null,
                'build_commands' => ! empty($this->build_commands) ? $this->build_commands : null,
                'post_deploy_commands' => ! empty($this->post_deploy_commands) ? $this->post_deploy_commands : null,
                'env_template' => ! empty($this->env_template) ? $this->env_template : null,
                'docker_compose_template' => $this->docker_compose_template ?: null,
                'dockerfile_template' => $this->dockerfile_template ?: null,
                'health_check_path' => $this->health_check_path ?: null,
            ]);

            session()->flash('message', 'Template updated successfully!');
            $this->showEditModal = false;
            $this->resetForm();
            unset($this->templates);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update template: '.$e->getMessage());
        }
    }

    public function openDeleteModal(int $templateId): void
    {
        $template = ProjectTemplate::findOrFail($templateId);

        if ($template->is_system) {
            session()->flash('error', 'Cannot delete system templates');

            return;
        }

        $this->deletingTemplateId = $templateId;
        $this->showDeleteModal = true;
    }

    public function deleteTemplate(): void
    {
        try {
            $template = ProjectTemplate::findOrFail($this->deletingTemplateId);

            if ($template->is_system) {
                session()->flash('error', 'Cannot delete system templates');

                return;
            }

            $template->delete();

            session()->flash('message', 'Template deleted successfully!');
            $this->showDeleteModal = false;
            $this->deletingTemplateId = null;
            unset($this->templates);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete template: '.$e->getMessage());
        }
    }

    public function cloneTemplate(int $templateId): void
    {
        try {
            $original = ProjectTemplate::findOrFail($templateId);

            $clone = $original->replicate();
            $clone->name = $original->name.' (Copy)';
            $clone->slug = $original->slug.'-copy-'.time();
            $clone->is_system = false;
            $clone->user_id = (int) auth()->id();
            $clone->save();

            session()->flash('message', 'Template cloned successfully!');
            unset($this->templates);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clone template: '.$e->getMessage());
        }
    }

    public function openPreviewModal(int $templateId): void
    {
        $this->previewingTemplateId = $templateId;
        $this->showPreviewModal = true;
    }

    public function toggleTemplateStatus(int $templateId): void
    {
        try {
            $template = ProjectTemplate::findOrFail($templateId);
            $template->update(['is_active' => ! $template->is_active]);

            session()->flash('message', 'Template status updated!');
            unset($this->templates);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update template status: '.$e->getMessage());
        }
    }

    // Command Management
    public function addInstallCommand(): void
    {
        if ($this->newInstallCommand) {
            $this->install_commands[] = $this->newInstallCommand;
            $this->newInstallCommand = '';
        }
    }

    public function removeInstallCommand(int $index): void
    {
        unset($this->install_commands[$index]);
        $this->install_commands = array_values($this->install_commands);
    }

    public function addBuildCommand(): void
    {
        if ($this->newBuildCommand) {
            $this->build_commands[] = $this->newBuildCommand;
            $this->newBuildCommand = '';
        }
    }

    public function removeBuildCommand(int $index): void
    {
        unset($this->build_commands[$index]);
        $this->build_commands = array_values($this->build_commands);
    }

    public function addPostDeployCommand(): void
    {
        if ($this->newPostDeployCommand) {
            $this->post_deploy_commands[] = $this->newPostDeployCommand;
            $this->newPostDeployCommand = '';
        }
    }

    public function removePostDeployCommand(int $index): void
    {
        unset($this->post_deploy_commands[$index]);
        $this->post_deploy_commands = array_values($this->post_deploy_commands);
    }

    // Environment Template Management
    public function addEnvVariable(): void
    {
        if ($this->newEnvKey && $this->newEnvValue) {
            $this->env_template[$this->newEnvKey] = $this->newEnvValue;
            $this->newEnvKey = '';
            $this->newEnvValue = '';
        }
    }

    public function removeEnvVariable(string $key): void
    {
        unset($this->env_template[$key]);
    }

    private function resetForm(): void
    {
        $this->editingTemplateId = null;
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->framework = 'laravel';
        $this->icon = '';
        $this->color = '';
        $this->default_branch = 'main';
        $this->php_version = '8.4';
        $this->node_version = '';
        $this->health_check_path = '';
        $this->is_active = true;
        $this->install_commands = [];
        $this->build_commands = [];
        $this->post_deploy_commands = [];
        $this->env_template = [];
        $this->docker_compose_template = '';
        $this->dockerfile_template = '';
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.project-template-manager');
    }
}
