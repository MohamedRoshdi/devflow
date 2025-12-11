<?php

declare(strict_types=1);

namespace App\Livewire\CICD;

use App\Models\PipelineStage;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class PipelineBuilder extends Component
{
    public ?Project $project = null;

    /** @var array<string, array<int, mixed>> */
    public array $stages = [];

    // Modal states
    public bool $showStageModal = false;

    public bool $showTemplateModal = false;

    // Stage form fields
    public ?int $editingStageId = null;

    public string $stageName = '';

    public string $stageType = 'pre_deploy';

    public string $commands = '';

    public int $timeoutSeconds = 300;

    public bool $continueOnFailure = false;

    /** @var array<string, string> */
    public array $envVariables = [];

    public string $newEnvKey = '';

    public string $newEnvValue = '';

    protected function rules(): array
    {
        return [
            'stageName' => 'required|string|max:255',
            'stageType' => 'required|in:pre_deploy,deploy,post_deploy',
            'commands' => 'required|string',
            'timeoutSeconds' => 'required|integer|min:10|max:3600',
            'continueOnFailure' => 'boolean',
        ];
    }

    public function mount(?Project $project = null): void
    {
        // Check if user has permission to manage pipelines
        abort_unless(
            auth()->user()->can('create-pipelines') || auth()->user()->can('edit-pipelines'),
            403,
            'You do not have permission to manage pipelines.'
        );

        $this->project = $project;
        $this->loadStages();
    }

    public function loadStages(): void
    {
        if (! $this->project) {
            $this->stages = [
                'pre_deploy' => [],
                'deploy' => [],
                'post_deploy' => [],
            ];

            return;
        }

        $stages = $this->project->pipelineStages()->ordered()->get();

        $this->stages = [
            'pre_deploy' => $stages->where('type', 'pre_deploy')->values()->toArray(),
            'deploy' => $stages->where('type', 'deploy')->values()->toArray(),
            'post_deploy' => $stages->where('type', 'post_deploy')->values()->toArray(),
        ];
    }

    public function addStage(string $type): void
    {
        $this->resetStageForm();
        $this->stageType = $type;
        $this->showStageModal = true;
    }

    public function editStage(int $stageId): void
    {
        $stage = PipelineStage::findOrFail($stageId);

        $this->editingStageId = $stage->id;
        $this->stageName = $stage->name;
        $this->stageType = $stage->type;
        $this->commands = implode("\n", $stage->commands);
        $this->timeoutSeconds = $stage->timeout_seconds;
        $this->continueOnFailure = $stage->continue_on_failure;
        $this->envVariables = $stage->environment_variables ?? [];

        $this->showStageModal = true;
    }

    public function saveStage(): void
    {
        if (! $this->project) {
            $this->dispatch('notification', type: 'error', message: 'Please select a project first');

            return;
        }

        $this->validate();

        $commandsArray = array_filter(
            array_map('trim', explode("\n", $this->commands)),
            fn ($cmd) => ! empty($cmd)
        );

        $data = [
            'project_id' => $this->project->id,
            'name' => $this->stageName,
            'type' => $this->stageType,
            'commands' => $commandsArray,
            'timeout_seconds' => $this->timeoutSeconds,
            'continue_on_failure' => $this->continueOnFailure,
            'environment_variables' => $this->envVariables,
        ];

        if ($this->editingStageId) {
            $stage = PipelineStage::findOrFail($this->editingStageId);
            $stage->update($data);
            $message = 'Stage updated successfully!';
        } else {
            // Get the max order for this type
            $maxOrder = PipelineStage::where('project_id', $this->project->id)
                ->where('type', $this->stageType)
                ->max('order') ?? -1;

            $data['order'] = $maxOrder + 1;
            PipelineStage::create($data);
            $message = 'Stage created successfully!';
        }

        $this->dispatch('notification', type: 'success', message: $message);
        $this->showStageModal = false;
        $this->resetStageForm();
        $this->loadStages();
    }

    public function deleteStage(int $stageId): void
    {
        $stage = PipelineStage::findOrFail($stageId);
        $stage->delete();

        // Reorder remaining stages
        $this->reorderStagesAfterDelete($stage->type, $stage->order);

        $this->dispatch('notification', type: 'success', message: 'Stage deleted successfully');
        $this->loadStages();
    }

    public function toggleStage(int $stageId): void
    {
        $stage = PipelineStage::findOrFail($stageId);
        $stage->update(['enabled' => ! $stage->enabled]);

        $message = $stage->enabled ? 'Stage enabled' : 'Stage disabled';
        $this->dispatch('notification', type: 'success', message: $message);
        $this->loadStages();
    }

    #[On('stages-reordered')]
    public function updateStageOrder(array $stageIds, string $type): void
    {
        foreach ($stageIds as $index => $stageId) {
            PipelineStage::where('id', $stageId)->update(['order' => $index]);
        }

        $this->loadStages();
    }

    public function addEnvVariable(): void
    {
        if ($this->newEnvKey && $this->newEnvValue) {
            $this->envVariables[$this->newEnvKey] = $this->newEnvValue;
            $this->reset('newEnvKey', 'newEnvValue');
        }
    }

    public function removeEnvVariable(string $key): void
    {
        unset($this->envVariables[$key]);
    }

    public function applyTemplate(string $template): void
    {
        if (! $this->project) {
            $this->dispatch('notification', type: 'error', message: 'Please select a project first');

            return;
        }

        $stages = $this->getTemplateStages($template);

        foreach ($stages as $stage) {
            $maxOrder = PipelineStage::where('project_id', $this->project->id)
                ->where('type', $stage['type'])
                ->max('order') ?? -1;

            $stage['order'] = $maxOrder + 1;
            $stage['project_id'] = $this->project->id;

            PipelineStage::create($stage);
        }

        $this->showTemplateModal = false;
        $this->dispatch('notification', type: 'success', message: 'Template applied successfully!');
        $this->loadStages();
    }

    private function getTemplateStages(string $template): array
    {
        return match ($template) {
            'laravel' => [
                [
                    'name' => 'Install Composer Dependencies',
                    'type' => 'pre_deploy',
                    'commands' => ['composer install --optimize-autoloader --no-dev'],
                    'timeout_seconds' => 300,
                    'enabled' => true,
                    'continue_on_failure' => false,
                ],
                [
                    'name' => 'Install NPM Dependencies',
                    'type' => 'pre_deploy',
                    'commands' => ['npm install'],
                    'timeout_seconds' => 300,
                    'enabled' => true,
                    'continue_on_failure' => false,
                ],
                [
                    'name' => 'Build Frontend Assets',
                    'type' => 'pre_deploy',
                    'commands' => ['npm run build'],
                    'timeout_seconds' => 300,
                    'enabled' => true,
                    'continue_on_failure' => false,
                ],
                [
                    'name' => 'Run Database Migrations',
                    'type' => 'deploy',
                    'commands' => ['php artisan migrate --force'],
                    'timeout_seconds' => 300,
                    'enabled' => true,
                    'continue_on_failure' => false,
                ],
                [
                    'name' => 'Clear & Cache Config',
                    'type' => 'post_deploy',
                    'commands' => [
                        'php artisan config:cache',
                        'php artisan route:cache',
                        'php artisan view:cache',
                    ],
                    'timeout_seconds' => 60,
                    'enabled' => true,
                    'continue_on_failure' => false,
                ],
            ],
            'nodejs' => [
                [
                    'name' => 'Install Dependencies',
                    'type' => 'pre_deploy',
                    'commands' => ['npm install'],
                    'timeout_seconds' => 300,
                    'enabled' => true,
                    'continue_on_failure' => false,
                ],
                [
                    'name' => 'Run Tests',
                    'type' => 'pre_deploy',
                    'commands' => ['npm test'],
                    'timeout_seconds' => 300,
                    'enabled' => true,
                    'continue_on_failure' => false,
                ],
                [
                    'name' => 'Build Application',
                    'type' => 'deploy',
                    'commands' => ['npm run build'],
                    'timeout_seconds' => 300,
                    'enabled' => true,
                    'continue_on_failure' => false,
                ],
            ],
            'static' => [
                [
                    'name' => 'Copy Files',
                    'type' => 'deploy',
                    'commands' => ['cp -r ./* /var/www/html/'],
                    'timeout_seconds' => 60,
                    'enabled' => true,
                    'continue_on_failure' => false,
                ],
            ],
            default => [],
        };
    }

    private function reorderStagesAfterDelete(string $type, int $deletedOrder): void
    {
        if ($this->project === null) {
            return;
        }

        PipelineStage::where('project_id', $this->project->id)
            ->where('type', $type)
            ->where('order', '>', $deletedOrder)
            ->decrement('order');
    }

    private function resetStageForm(): void
    {
        $this->editingStageId = null;
        $this->stageName = '';
        $this->stageType = 'pre_deploy';
        $this->commands = '';
        $this->timeoutSeconds = 300;
        $this->continueOnFailure = false;
        $this->envVariables = [];
        $this->newEnvKey = '';
        $this->newEnvValue = '';
    }

    public function render(): View
    {
        return view('livewire.cicd.pipeline-builder');
    }
}
