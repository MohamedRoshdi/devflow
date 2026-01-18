<?php

declare(strict_types=1);

namespace App\Livewire\Scripts;

use App\Models\DeploymentScript;
use App\Models\Project;
use App\Services\CustomScripts\DeploymentScriptService;
use Livewire\Component;
use Livewire\WithPagination;

class ScriptManager extends Component
{
    use WithPagination;

    public bool $showCreateModal = false;

    public bool $showTestModal = false;

    public bool $showTemplateModal = false;

    public ?DeploymentScript $editingScript = null;

    // Script form fields
    public string $name = '';

    public string $description = '';

    public string $type = 'deployment';

    public string $language = 'bash';

    public string $content = '';

    public int $timeout = 600;

    public bool $retryOnFailure = false;

    public int $maxRetries = 3;

    public bool $enabled = true;

    // Test execution
    public ?int $testProject = null;

    public string $testOutput = '';

    public bool $testRunning = false;

    // Templates
    public string $selectedTemplate = '';

    /**
     * @var array<string, string>
     */
    protected array $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'type' => 'required|in:deployment,rollback,maintenance,backup,custom',
        'language' => 'required|in:bash,sh,python,php,node,ruby',
        'content' => 'required|string',
        'timeout' => 'required|integer|min:10|max:3600',
        'retryOnFailure' => 'boolean',
        'maxRetries' => 'required_if:retryOnFailure,true|integer|min:1|max:10',
        'enabled' => 'boolean',
    ];

    public function render(): \Illuminate\Contracts\View\View
    {
        $scriptService = app(DeploymentScriptService::class);

        return view('livewire.scripts.script-manager', [
            'scripts' => DeploymentScript::paginate(10),
            'projects' => Project::select('id', 'name', 'slug')->orderBy('name')->get(),
            'templates' => $scriptService->getAvailableTemplates(),
        ]);
    }

    public function createScript(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function editScript(DeploymentScript $script): void
    {
        $this->editingScript = $script;
        $this->name = $script->name;
        $this->description = $script->description;
        $this->type = $script->type;
        $this->language = $script->language;
        $this->content = $script->content;
        $this->timeout = $script->timeout;
        $this->retryOnFailure = $script->retry_on_failure;
        $this->maxRetries = $script->max_retries;
        $this->enabled = $script->enabled;

        $this->showCreateModal = true;
    }

    public function saveScript(): void
    {
        $this->validate();

        try {
            $scriptService = app(DeploymentScriptService::class);

            $data = [
                'name' => $this->name,
                'description' => $this->description,
                'type' => $this->type,
                'language' => $this->language,
                'content' => $this->content,
                'timeout' => $this->timeout,
                'retry_on_failure' => $this->retryOnFailure,
                'max_retries' => $this->maxRetries,
                'enabled' => $this->enabled,
            ];

            if ($this->editingScript) {
                $this->editingScript->update($data);
                $this->dispatch('notify', type: 'success', message: 'Script updated successfully');
            } else {
                $scriptService->createScript($data);
                $this->dispatch('notify', type: 'success', message: 'Script created successfully');
            }

            $this->showCreateModal = false;
            $this->resetForm();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to save script: '.$e->getMessage());
        }
    }

    public function deleteScript(DeploymentScript $script): void
    {
        $script->delete();
        $this->dispatch('notify', type: 'success', message: 'Script deleted successfully');
    }

    public function toggleScript(DeploymentScript $script): void
    {
        $script->update(['enabled' => ! $script->enabled]);
        $this->dispatch('notify', type: 'success', message: $script->enabled ? 'Script enabled' : 'Script disabled');
    }

    public function testScript(DeploymentScript $script): void
    {
        $this->editingScript = $script;
        $this->testOutput = '';
        $this->showTestModal = true;
    }

    public function runTest(): void
    {
        $this->validate(['testProject' => 'required|exists:projects,id']);

        $this->testRunning = true;
        $this->testOutput = '';

        try {
            $scriptService = app(DeploymentScriptService::class);
            $project = Project::find($this->testProject);

            // Create a mock deployment for testing
            $deployment = new \App\Models\Deployment([
                'id' => 0,
                'commit_hash' => 'test_commit',
                'branch' => 'test',
            ]);

            $result = $scriptService->executeScript(
                $project,
                $this->editingScript,
                $deployment
            );

            $this->testOutput = $result['success']
                ? "✅ Script executed successfully\n\n".$result['output']
                : "❌ Script failed\n\n".$result['error'];

        } catch (\Exception $e) {
            $this->testOutput = '❌ Error: '.$e->getMessage();
        }

        $this->testRunning = false;
    }

    public function useTemplate(string $templateKey): void
    {
        $scriptService = app(DeploymentScriptService::class);
        $templates = $scriptService->getAvailableTemplates();

        if (isset($templates[$templateKey])) {
            $template = $templates[$templateKey];
            $this->name = $template['name'];
            $this->description = $template['description'];
            $this->type = $template['type'];
            $this->language = $template['language'];
            $this->content = $template['content'];
            $this->timeout = $template['timeout'];

            $this->showTemplateModal = false;
            $this->showCreateModal = true;
        }
    }

    public function downloadScript(DeploymentScript $script): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $extension = match ($script->language) {
            'bash', 'sh' => 'sh',
            'python' => 'py',
            'php' => 'php',
            'node' => 'js',
            'ruby' => 'rb',
            default => 'txt',
        };

        $filename = str_replace(' ', '_', strtolower($script->name)).'.'.$extension;

        return response()->streamDownload(function () use ($script) {
            echo $script->content;
        }, $filename);
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->description = '';
        $this->type = 'deployment';
        $this->language = 'bash';
        $this->content = '';
        $this->timeout = 600;
        $this->retryOnFailure = false;
        $this->maxRetries = 3;
        $this->enabled = true;
        $this->editingScript = null;
    }
}
