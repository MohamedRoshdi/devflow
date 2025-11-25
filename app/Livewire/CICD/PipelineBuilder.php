<?php

namespace App\Livewire\CICD;

use App\Models\Pipeline;
use App\Models\Project;
use App\Services\CICD\PipelineService;
use Livewire\Component;
use Livewire\WithPagination;

class PipelineBuilder extends Component
{
    use WithPagination;

    public $showCreateModal = false;
    public $showConfigModal = false;
    public $editingPipeline = null;

    // Pipeline configuration
    public $projectId = '';
    public $name = '';
    public $provider = 'github';
    public $triggerEvents = ['push'];
    public $branchFilters = ['main'];
    public $enableTests = true;
    public $enableBuild = true;
    public $enableDeploy = true;
    public $enableSecurityScan = false;
    public $enableQualityCheck = false;
    public $deploymentStrategy = 'docker';
    public $customConfig = '';

    // Pipeline run details
    public $selectedPipeline = null;
    public $pipelineRuns = [];

    protected $rules = [
        'projectId' => 'required|exists:projects,id',
        'name' => 'required|string|max:255',
        'provider' => 'required|in:github,gitlab,bitbucket,jenkins,custom',
        'triggerEvents' => 'required|array|min:1',
        'branchFilters' => 'required|array|min:1',
    ];

    public function render()
    {
        return view('livewire.cicd.pipeline-builder', [
            'pipelines' => Pipeline::with(['project', 'lastRun'])->paginate(10),
            'projects' => Project::all(),
        ]);
    }

    public function createPipeline()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function savePipeline()
    {
        $this->validate();

        try {
            $pipelineService = app(PipelineService::class);
            $project = Project::find($this->projectId);

            $config = [
                'name' => $this->name,
                'provider' => $this->provider,
                'trigger_events' => $this->triggerEvents,
                'branch_filters' => $this->branchFilters,
                'enable_tests' => $this->enableTests,
                'enable_build' => $this->enableBuild,
                'enable_deploy' => $this->enableDeploy,
                'enable_security_scan' => $this->enableSecurityScan,
                'enable_quality_check' => $this->enableQualityCheck,
                'deployment_strategy' => $this->deploymentStrategy,
            ];

            if ($this->customConfig) {
                $config['custom'] = json_decode($this->customConfig, true);
            }

            $pipeline = $pipelineService->createPipeline($project, $config);

            $this->dispatch('notify', type: 'success', message: 'Pipeline created successfully!');
            $this->showCreateModal = false;
            $this->resetForm();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to create pipeline: ' . $e->getMessage());
        }
    }

    public function editPipeline(Pipeline $pipeline)
    {
        $this->editingPipeline = $pipeline;
        $this->projectId = $pipeline->project_id;
        $this->name = $pipeline->name;
        $this->provider = $pipeline->provider;
        $this->triggerEvents = $pipeline->trigger_events;
        $this->branchFilters = $pipeline->branch_filters;

        $config = $pipeline->configuration;
        $this->enableTests = $config['enable_tests'] ?? true;
        $this->enableBuild = $config['enable_build'] ?? true;
        $this->enableDeploy = $config['enable_deploy'] ?? true;
        $this->enableSecurityScan = $config['enable_security_scan'] ?? false;
        $this->enableQualityCheck = $config['enable_quality_check'] ?? false;
        $this->deploymentStrategy = $config['deployment_strategy'] ?? 'docker';

        $this->showCreateModal = true;
    }

    public function runPipeline(Pipeline $pipeline)
    {
        try {
            $pipelineService = app(PipelineService::class);
            $run = $pipelineService->executePipeline($pipeline, 'manual');

            $this->dispatch('notify', type: 'success', message: 'Pipeline execution started!');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to run pipeline: ' . $e->getMessage());
        }
    }

    public function showPipelineConfig(Pipeline $pipeline)
    {
        $this->selectedPipeline = $pipeline;
        $this->showConfigModal = true;
    }

    public function deletePipeline(Pipeline $pipeline)
    {
        $pipeline->delete();
        $this->dispatch('notify', type: 'success', message: 'Pipeline deleted successfully');
    }

    public function togglePipeline(Pipeline $pipeline)
    {
        $pipeline->update(['enabled' => !$pipeline->enabled]);
        $this->dispatch('notify', type: 'success', message: $pipeline->enabled ? 'Pipeline enabled' : 'Pipeline disabled');
    }

    public function downloadConfig(Pipeline $pipeline)
    {
        $filename = match($pipeline->provider) {
            'github' => '.github/workflows/devflow.yml',
            'gitlab' => '.gitlab-ci.yml',
            'bitbucket' => 'bitbucket-pipelines.yml',
            'jenkins' => 'Jenkinsfile',
            default => 'pipeline-config.yml'
        };

        return response()->streamDownload(function() use ($pipeline) {
            echo yaml_emit($pipeline->configuration);
        }, $filename);
    }

    private function resetForm()
    {
        $this->projectId = '';
        $this->name = '';
        $this->provider = 'github';
        $this->triggerEvents = ['push'];
        $this->branchFilters = ['main'];
        $this->enableTests = true;
        $this->enableBuild = true;
        $this->enableDeploy = true;
        $this->enableSecurityScan = false;
        $this->enableQualityCheck = false;
        $this->deploymentStrategy = 'docker';
        $this->customConfig = '';
        $this->editingPipeline = null;
    }
}