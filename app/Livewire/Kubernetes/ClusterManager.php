<?php

namespace App\Livewire\Kubernetes;

use App\Models\KubernetesCluster;
use App\Models\Project;
use App\Services\Kubernetes\KubernetesService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class ClusterManager extends Component
{
    use WithPagination;

    public $showAddClusterModal = false;
    public $showDeployModal = false;
    public $editingCluster = null;
    public $selectedProject = null;

    // Cluster form fields
    public $name = '';
    public $endpoint = '';
    public $kubeconfig = '';
    public $namespace = '';
    public $isDefault = false;

    // Deployment settings
    public $deploymentProject = null;
    public $replicas = 3;
    public $enableAutoscaling = false;
    public $minReplicas = 2;
    public $maxReplicas = 10;
    public $cpuRequest = '100m';
    public $cpuLimit = '500m';
    public $memoryRequest = '256Mi';
    public $memoryLimit = '512Mi';
    public $serviceType = 'ClusterIP';

    protected $rules = [
        'name' => 'required|string|max:255',
        'endpoint' => 'required|url',
        'kubeconfig' => 'required|string',
        'namespace' => 'nullable|string|max:63',
        'isDefault' => 'boolean',
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.kubernetes.cluster-manager', [
            'clusters' => KubernetesCluster::paginate(10),
            'projects' => Project::all(),
        ]);
    }

    public function addCluster()
    {
        $this->resetForm();
        $this->showAddClusterModal = true;
    }

    public function editCluster(KubernetesCluster $cluster)
    {
        $this->editingCluster = $cluster;
        $this->name = $cluster->name;
        $this->endpoint = $cluster->endpoint;
        $this->kubeconfig = $cluster->kubeconfig;
        $this->namespace = $cluster->namespace;
        $this->isDefault = $cluster->is_default;
        $this->showAddClusterModal = true;
    }

    public function saveCluster()
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

    public function deleteCluster(KubernetesCluster $cluster)
    {
        // Check if cluster is in use
        if ($cluster->projects()->exists()) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete cluster that is in use by projects');
            return;
        }

        $cluster->delete();
        $this->dispatch('notify', type: 'success', message: 'Cluster deleted successfully');
    }

    public function testClusterConnection(KubernetesCluster $cluster)
    {
        try {
            $service = app(KubernetesService::class);

            // Create temp kubeconfig file
            $tempFile = tempnam(sys_get_temp_dir(), 'kubeconfig');
            file_put_contents($tempFile, decrypt($cluster->kubeconfig));

            putenv("KUBECONFIG={$tempFile}");

            $result = shell_exec('kubectl cluster-info 2>&1');

            unlink($tempFile);

            if (strpos($result, 'is running') !== false) {
                $this->dispatch('notify', type: 'success', message: 'Connection successful!');
            } else {
                $this->dispatch('notify', type: 'error', message: 'Connection failed');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Connection test failed: ' . $e->getMessage());
        }
    }

    public function showDeployToCluster(KubernetesCluster $cluster)
    {
        $this->selectedCluster = $cluster;
        $this->showDeployModal = true;
    }

    public function deployToKubernetes()
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
            $project = Project::find($this->deploymentProject);
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
            $this->dispatch('notify', type: 'error', message: 'Deployment failed: ' . $e->getMessage());
        }
    }

    #[On('refresh-clusters')]
    public function refreshClusters()
    {
        $this->resetPage();
    }

    private function resetForm()
    {
        $this->name = '';
        $this->endpoint = '';
        $this->kubeconfig = '';
        $this->namespace = '';
        $this->isDefault = false;
        $this->editingCluster = null;
    }

    private function resetDeploymentForm()
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

    private function testConnection($data)
    {
        try {
            $tempFile = tempnam(sys_get_temp_dir(), 'kubeconfig_test');
            file_put_contents($tempFile, $data['kubeconfig']);

            putenv("KUBECONFIG={$tempFile}");

            $result = shell_exec('kubectl cluster-info 2>&1');

            unlink($tempFile);

            return strpos($result, 'is running') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}