<?php

declare(strict_types=1);

namespace App\Livewire\Kubernetes;

use App\Models\KubernetesCluster;
use App\Models\Project;
use App\Services\Kubernetes\KubernetesService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ClusterManager extends Component
{
    use WithPagination;

    public bool $showAddClusterModal = false;

    public bool $showDeployModal = false;

    public ?KubernetesCluster $editingCluster = null;

    public ?KubernetesCluster $selectedCluster = null;

    public ?Project $selectedProject = null;

    // Cluster form fields
    public string $name = '';

    public string $endpoint = '';

    public string $kubeconfig = '';

    public string $namespace = '';

    public bool $isDefault = false;

    // Deployment settings
    public ?int $deploymentProject = null;

    public int $replicas = 3;

    public bool $enableAutoscaling = false;

    public int $minReplicas = 2;

    public int $maxReplicas = 10;

    public string $cpuRequest = '100m';

    public string $cpuLimit = '500m';

    public string $memoryRequest = '256Mi';

    public string $memoryLimit = '512Mi';

    public string $serviceType = 'ClusterIP';

    /** @var array<string, string> */
    protected array $rules = [
        'name' => 'required|string|max:255',
        'endpoint' => 'required|url',
        'kubeconfig' => 'required|string',
        'namespace' => 'nullable|string|max:63',
        'isDefault' => 'boolean',
    ];

    public function mount(): void
    {
        $this->resetForm();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.kubernetes.cluster-manager', [
            'clusters' => KubernetesCluster::paginate(10),
            'projects' => Project::select('id', 'name', 'slug', 'framework')->orderBy('name')->get(),
        ]);
    }

    public function addCluster(): void
    {
        $this->resetForm();
        $this->showAddClusterModal = true;
    }

    public function editCluster(KubernetesCluster $cluster): void
    {
        $this->editingCluster = $cluster;
        $this->name = $cluster->name;
        $this->endpoint = $cluster->endpoint;
        $this->kubeconfig = $cluster->kubeconfig ?? '';
        $this->namespace = $cluster->namespace ?? 'default';
        $this->isDefault = $cluster->is_default;
        $this->showAddClusterModal = true;
    }

    public function saveCluster(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'endpoint' => $this->endpoint,
            'kubeconfig' => encrypt($this->kubeconfig),
            'namespace' => $this->namespace ?: 'default',
            'is_default' => $this->isDefault,
        ];

        if ($this->isDefault) {
            KubernetesCluster::where('is_default', true)->update(['is_default' => false]);
        }

        if ($this->editingCluster) {
            $this->editingCluster->update($data);
            $this->dispatch('notify', type: 'success', message: 'Cluster updated successfully');
        } else {
            // Test connection before saving
            if ($this->testConnection($data)) {
                KubernetesCluster::create($data);
                $this->dispatch('notify', type: 'success', message: 'Cluster added successfully');
            } else {
                $this->addError('kubeconfig', 'Failed to connect to cluster. Please check your configuration.');

                return;
            }
        }

        $this->showAddClusterModal = false;
        $this->resetForm();
    }

    public function deleteCluster(KubernetesCluster $cluster): void
    {
        // Check if cluster is in use
        if ($cluster->projects()->exists()) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete cluster that is in use by projects');

            return;
        }

        $cluster->delete();
        $this->dispatch('notify', type: 'success', message: 'Cluster deleted successfully');
    }

    public function testClusterConnection(KubernetesCluster $cluster): void
    {
        try {
            $service = app(KubernetesService::class);

            // Create temp kubeconfig file with secure permissions
            $tempFile = tempnam(sys_get_temp_dir(), 'kubeconfig');
            if ($tempFile === false) {
                throw new \RuntimeException('Failed to create temp file');
            }

            file_put_contents($tempFile, decrypt($cluster->kubeconfig));
            chmod($tempFile, 0600);

            // Validate the temp file path
            if (! preg_match('/^[a-zA-Z0-9\/._-]+$/', $tempFile)) {
                @unlink($tempFile);
                throw new \RuntimeException('Invalid temp file path');
            }

            $escapedTempFile = escapeshellarg($tempFile);
            putenv("KUBECONFIG={$tempFile}");

            $result = shell_exec("kubectl cluster-info --kubeconfig={$escapedTempFile} 2>&1");

            @unlink($tempFile);

            if (strpos($result, 'is running') !== false) {
                $this->dispatch('notify', type: 'success', message: 'Connection successful!');
            } else {
                $this->dispatch('notify', type: 'error', message: 'Connection failed');
            }
        } catch (\Exception $e) {
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
            $this->dispatch('notify', type: 'error', message: 'Connection test failed: '.$e->getMessage());
        }
    }

    public function showDeployToCluster(KubernetesCluster $cluster): void
    {
        $this->selectedCluster = $cluster;
        $this->showDeployModal = true;
    }

    public function deployToKubernetes(): void
    {
        $this->validate([
            'deploymentProject' => 'required|exists:projects,id',
            'replicas' => 'required|integer|min:1|max:100',
            'cpuRequest' => 'required|string',
            'cpuLimit' => 'required|string',
            'memoryRequest' => 'required|string',
            'memoryLimit' => 'required|string',
        ]);

        try {
            $project = Project::findOrFail($this->deploymentProject);
            $service = app(KubernetesService::class);

            $result = $service->deployToKubernetes($project, [
                'replicas' => $this->replicas,
                'enable_autoscaling' => $this->enableAutoscaling,
                'min_replicas' => $this->minReplicas,
                'max_replicas' => $this->maxReplicas,
                'cpu_request' => $this->cpuRequest,
                'cpu_limit' => $this->cpuLimit,
                'memory_request' => $this->memoryRequest,
                'memory_limit' => $this->memoryLimit,
                'service_type' => $this->serviceType,
            ]);

            if ($result['success']) {
                $this->dispatch('notify', type: 'success', message: "Deployment to Kubernetes started for {$project->name}");
                $this->showDeployModal = false;
                $this->resetDeploymentForm();
            } else {
                $this->dispatch('notify', type: 'error', message: 'Deployment failed. Check logs for details.');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Deployment failed: '.$e->getMessage());
        }
    }

    #[On('refresh-clusters')]
    public function refreshClusters(): void
    {
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->endpoint = '';
        $this->kubeconfig = '';
        $this->namespace = '';
        $this->isDefault = false;
        $this->editingCluster = null;
    }

    private function resetDeploymentForm(): void
    {
        $this->deploymentProject = null;
        $this->replicas = 3;
        $this->enableAutoscaling = false;
        $this->minReplicas = 2;
        $this->maxReplicas = 10;
        $this->cpuRequest = '100m';
        $this->cpuLimit = '500m';
        $this->memoryRequest = '256Mi';
        $this->memoryLimit = '512Mi';
        $this->serviceType = 'ClusterIP';
    }

    private function testConnection(array $data): bool
    {
        try {
            $tempFile = tempnam(sys_get_temp_dir(), 'kubeconfig_test');
            if ($tempFile === false) {
                return false;
            }

            file_put_contents($tempFile, $data['kubeconfig']);
            chmod($tempFile, 0600);

            // Validate the temp file path
            if (! preg_match('/^[a-zA-Z0-9\/._-]+$/', $tempFile)) {
                @unlink($tempFile);

                return false;
            }

            $escapedTempFile = escapeshellarg($tempFile);
            putenv("KUBECONFIG={$tempFile}");

            $result = shell_exec("kubectl cluster-info --kubeconfig={$escapedTempFile} 2>&1");

            @unlink($tempFile);

            return strpos($result, 'is running') !== false;
        } catch (\Exception $e) {
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }

            return false;
        }
    }
}
