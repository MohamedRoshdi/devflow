<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\Process\Process;

class ProjectEnvironment extends Component
{
    #[Locked]
    public $projectId;

    public $environment;
    public $envVariables = [];
    public $showEnvModal = false;
    public $newEnvKey = '';
    public $newEnvValue = '';
    public $editingEnvKey = null;

    // Server .env file properties
    public $serverEnvVariables = [];
    public $serverEnvLoading = false;
    public $serverEnvError = null;
    public $showServerEnvModal = false;
    public $editingServerEnvKey = null;
    public $serverEnvKey = '';
    public $serverEnvValue = '';

    protected $rules = [
        'environment' => 'required|in:local,development,staging,production',
        'newEnvKey' => 'required|string|max:255',
        'newEnvValue' => 'string|max:1000',
    ];

    public function mount(Project $project)
    {
        $this->projectId = $project->id;
        $this->environment = $project->environment ?? 'production';
        $this->envVariables = $project->env_variables ? (array)$project->env_variables : [];

        // Load server .env on mount
        $this->loadServerEnv();
    }

    protected function getProject()
    {
        return Project::findOrFail($this->projectId);
    }

    public function updateEnvironment($newEnvironment = null)
    {
        if ($newEnvironment) {
            $this->environment = $newEnvironment;
        }

        $this->validate(['environment' => 'required|in:local,development,staging,production']);

        $project = $this->getProject();
        $project->update(['environment' => $this->environment]);

        session()->flash('message', 'Environment updated to ' . ucfirst($this->environment));
        $this->dispatch('environmentUpdated');
    }

    public function openEnvModal()
    {
        $this->showEnvModal = true;
        $this->newEnvKey = '';
        $this->newEnvValue = '';
        $this->editingEnvKey = null;
    }

    public function closeEnvModal()
    {
        $this->showEnvModal = false;
        $this->resetValidation();
    }

    public function addEnvVariable()
    {
        $this->validate([
            'newEnvKey' => 'required|string|max:255',
            'newEnvValue' => 'string|max:1000',
        ]);

        $this->envVariables[$this->newEnvKey] = $this->newEnvValue;
        $this->saveEnvVariables();

        $this->newEnvKey = '';
        $this->newEnvValue = '';
        session()->flash('message', 'Environment variable added successfully');
    }

    public function editEnvVariable($key)
    {
        $this->editingEnvKey = $key;
        $this->newEnvKey = $key;
        $this->newEnvValue = $this->envVariables[$key] ?? '';
        $this->showEnvModal = true;
    }

    public function updateEnvVariable()
    {
        if ($this->editingEnvKey && $this->editingEnvKey !== $this->newEnvKey) {
            unset($this->envVariables[$this->editingEnvKey]);
        }

        $this->envVariables[$this->newEnvKey] = $this->newEnvValue;
        $this->saveEnvVariables();

        $this->closeEnvModal();
        session()->flash('message', 'Environment variable updated successfully');
    }

    public function deleteEnvVariable($key)
    {
        unset($this->envVariables[$key]);
        $this->saveEnvVariables();
        session()->flash('message', 'Environment variable deleted successfully');
    }

    protected function saveEnvVariables()
    {
        $project = $this->getProject();
        $project->update(['env_variables' => $this->envVariables]);
    }

    /**
     * Load the .env file from the server
     */
    public function loadServerEnv()
    {
        $this->serverEnvLoading = true;
        $this->serverEnvError = null;
        $this->serverEnvVariables = [];

        try {
            $project = $this->getProject();
            $server = $project->server;

            if (!$server) {
                $this->serverEnvError = 'No server configured for this project';
                $this->serverEnvLoading = false;
                return;
            }

            $projectPath = "/var/www/{$project->slug}";
            $envPath = "{$projectPath}/.env";

            // Build SSH command to read the .env file
            $sshCommand = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 {$server->username}@{$server->ip_address} \"cat {$envPath} 2>/dev/null || echo '__ENV_NOT_FOUND__'\"";

            $process = Process::fromShellCommandline($sshCommand);
            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful()) {
                $this->serverEnvError = 'Failed to connect to server: ' . $process->getErrorOutput();
                $this->serverEnvLoading = false;
                return;
            }

            $output = trim($process->getOutput());

            if ($output === '__ENV_NOT_FOUND__') {
                $this->serverEnvError = 'No .env file found at ' . $envPath;
                $this->serverEnvLoading = false;
                return;
            }

            // Parse the .env file
            $this->serverEnvVariables = $this->parseEnvFile($output);

        } catch (\Exception $e) {
            $this->serverEnvError = 'Error: ' . $e->getMessage();
        }

        $this->serverEnvLoading = false;
    }

    /**
     * Parse .env file content into key-value array
     */
    protected function parseEnvFile(string $content): array
    {
        $variables = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse KEY=value format
            if (str_contains($line, '=')) {
                $pos = strpos($line, '=');
                $key = trim(substr($line, 0, $pos));
                $value = trim(substr($line, $pos + 1));

                // Remove surrounding quotes if present
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                if (!empty($key)) {
                    $variables[$key] = $value;
                }
            }
        }

        return $variables;
    }

    /**
     * Open modal to edit a server environment variable
     */
    public function editServerEnvVariable($key)
    {
        $this->editingServerEnvKey = $key;
        $this->serverEnvKey = $key;
        $this->serverEnvValue = $this->serverEnvVariables[$key] ?? '';
        $this->showServerEnvModal = true;
    }

    /**
     * Open modal to add a new server environment variable
     */
    public function openServerEnvModal()
    {
        $this->editingServerEnvKey = null;
        $this->serverEnvKey = '';
        $this->serverEnvValue = '';
        $this->showServerEnvModal = true;
    }

    /**
     * Close server env modal
     */
    public function closeServerEnvModal()
    {
        $this->showServerEnvModal = false;
        $this->serverEnvKey = '';
        $this->serverEnvValue = '';
        $this->editingServerEnvKey = null;
    }

    /**
     * Save a server environment variable
     */
    public function saveServerEnvVariable()
    {
        $this->validate([
            'serverEnvKey' => 'required|string|max:255|regex:/^[A-Z][A-Z0-9_]*$/i',
            'serverEnvValue' => 'nullable|string|max:2000',
        ], [
            'serverEnvKey.regex' => 'Variable name must start with a letter and contain only letters, numbers, and underscores.',
        ]);

        try {
            $project = $this->getProject();
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";
            $envPath = "{$projectPath}/.env";

            $key = strtoupper(trim($this->serverEnvKey));
            $value = $this->serverEnvValue;

            // Escape special characters for sed
            $escapedValue = str_replace(['/', '&', '\\', '"', "'", "\n"], ['\\/', '\\&', '\\\\', '\\"', "\\'", '\\n'], $value);

            // Build SSH command to update or add the variable
            $updateCommand = "cd {$projectPath} && " .
                "if grep -q '^{$key}=' .env 2>/dev/null; then " .
                "sed -i 's|^{$key}=.*|{$key}={$escapedValue}|' .env; " .
                "else echo '{$key}={$value}' >> .env; fi && echo 'SUCCESS'";

            $sshCommand = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 {$server->username}@{$server->ip_address} \"{$updateCommand}\"";

            $process = Process::fromShellCommandline($sshCommand);
            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful() || !str_contains($process->getOutput(), 'SUCCESS')) {
                session()->flash('error', 'Failed to save variable: ' . $process->getErrorOutput());
                return;
            }

            // Reload the env variables
            $this->loadServerEnv();
            $this->closeServerEnvModal();

            session()->flash('message', "Environment variable '{$key}' saved successfully");

        } catch (\Exception $e) {
            session()->flash('error', 'Error saving variable: ' . $e->getMessage());
        }
    }

    /**
     * Delete a server environment variable
     */
    public function deleteServerEnvVariable($key)
    {
        try {
            $project = $this->getProject();
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            // Build SSH command to delete the variable
            $deleteCommand = "cd {$projectPath} && sed -i '/^{$key}=/d' .env && echo 'SUCCESS'";

            $sshCommand = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 {$server->username}@{$server->ip_address} \"{$deleteCommand}\"";

            $process = Process::fromShellCommandline($sshCommand);
            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful() || !str_contains($process->getOutput(), 'SUCCESS')) {
                session()->flash('error', 'Failed to delete variable: ' . $process->getErrorOutput());
                return;
            }

            // Reload the env variables
            $this->loadServerEnv();

            session()->flash('message', "Environment variable '{$key}' deleted successfully");

        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting variable: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.projects.project-environment', [
            'project' => $this->getProject(),
        ]);
    }
}
