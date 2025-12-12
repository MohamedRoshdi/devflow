<?php

declare(strict_types=1);

namespace App\Livewire\MultiTenant;

use App\Models\Project;
use App\Models\Tenant;
use App\Services\MultiTenant\MultiTenantService;
use Livewire\Component;
use Livewire\WithPagination;

class TenantManager extends Component
{
    use WithPagination;

    public ?int $selectedProject = null;

    public bool $showCreateModal = false;

    public bool $showDeployModal = false;

    public bool $showDetailsModal = false;

    public ?Tenant $editingTenant = null;

    // Tenant form fields
    public string $tenantName = '';

    public string $subdomain = '';

    public string $database = '';

    public string $adminEmail = '';

    public string $adminPassword = '';

    public string $plan = 'basic';

    public string $status = 'active';

    /** @var array<string, mixed> */
    public array $customConfig = [];

    // Deployment settings
    /** @var array<int, int> */
    public array $selectedTenants = [];

    public string $deploymentType = 'code_and_migrations';

    public bool $clearCache = true;

    public bool $maintenanceMode = false;

    /** @var array<string, string|array<int, string>> */
    protected $rules = [
        'selectedProject' => 'required|exists:projects,id',
        'tenantName' => 'required|string|max:255',
        'subdomain' => 'required|string|max:63|regex:/^[a-z0-9-]+$/',
        'adminEmail' => 'required|email',
        'adminPassword' => 'required|min:8',
        'plan' => 'required|in:basic,pro,enterprise',
    ];

    public function mount(mixed $project = null): void
    {
        // Handle both route parameter name 'project' and direct ID
        if ($project) {
            $this->selectedProject = is_numeric($project) ? (int) $project : (int) $project;
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $projects = Project::where('project_type', 'multi_tenant')->get();

        $tenants = null;
        if ($this->selectedProject) {
            $tenants = Tenant::where('project_id', $this->selectedProject)->paginate(10);
        }

        return view('livewire.multi-tenant.tenant-manager', [
            'projects' => $projects,
            'tenants' => $tenants,
        ]);
    }

    public function selectProject(int $projectId): void
    {
        $this->selectedProject = $projectId;
        $this->resetPage();
    }

    public function createTenant(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function saveTenant(): void
    {
        $this->validate();

        try {
            $service = app(MultiTenantService::class);
            $project = Project::find($this->selectedProject);

            $tenant = $service->createTenant($project, [
                'name' => $this->tenantName,
                'subdomain' => $this->subdomain,
                'database' => $this->database ?: "tenant_{$this->subdomain}",
                'admin_email' => $this->adminEmail,
                'admin_password' => bcrypt($this->adminPassword),
                'plan' => $this->plan,
                'status' => $this->status,
                'custom_config' => $this->customConfig,
            ]);

            $this->dispatch('notify', type: 'success', message: 'Tenant created successfully!');
            $this->showCreateModal = false;
            $this->resetForm();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to create tenant: '.$e->getMessage());
        }
    }

    public function editTenant(Tenant $tenant): void
    {
        $this->editingTenant = $tenant;
        $this->tenantName = $tenant->name;
        $this->subdomain = $tenant->subdomain;
        $this->database = $tenant->database;
        $this->adminEmail = $tenant->admin_email;
        $this->plan = $tenant->plan;
        $this->status = $tenant->status;
        $this->customConfig = $tenant->custom_config ?? [];
        $this->showCreateModal = true;
    }

    public function deleteTenant(Tenant $tenant): void
    {
        try {
            $service = app(MultiTenantService::class);
            $service->deleteTenant($tenant);

            $this->dispatch('notify', type: 'success', message: 'Tenant deleted successfully');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to delete tenant: '.$e->getMessage());
        }
    }

    public function toggleTenantStatus(Tenant $tenant): void
    {
        $newStatus = $tenant->status === 'active' ? 'suspended' : 'active';
        $tenant->update(['status' => $newStatus]);

        $this->dispatch('notify', type: 'success', message: "Tenant {$newStatus}");
    }

    public function showDeployToTenants(): void
    {
        $this->selectedTenants = [];
        $this->showDeployModal = true;
    }

    public function deployToSelectedTenants(): void
    {
        $this->validate([
            'selectedTenants' => 'required|array|min:1',
            'deploymentType' => 'required',
        ]);

        try {
            $service = app(MultiTenantService::class);
            $project = Project::find($this->selectedProject);

            $results = $service->deployToTenants($project, $this->selectedTenants, [
                'deployment_type' => $this->deploymentType,
                'clear_cache' => $this->clearCache,
                'maintenance_mode' => $this->maintenanceMode,
            ]);

            $successful = collect($results)->filter(fn ($r) => $r['status'] === 'success')->count();
            $failed = collect($results)->filter(fn ($r) => $r['status'] === 'failed')->count();

            $message = "Deployment completed: {$successful} successful, {$failed} failed";
            $this->dispatch('notify', type: 'success', message: $message);

            $this->showDeployModal = false;
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Deployment failed: '.$e->getMessage());
        }
    }

    public function toggleTenantSelection(int $tenantId): void
    {
        if (in_array($tenantId, $this->selectedTenants)) {
            $this->selectedTenants = array_values(array_diff($this->selectedTenants, [$tenantId]));
        } else {
            $this->selectedTenants[] = $tenantId;
        }
    }

    public function selectAllTenants(): void
    {
        $project = Project::find($this->selectedProject);
        if ($project) {
            $this->selectedTenants = $project->tenants->pluck('id')->toArray();
        }
    }

    public function clearSelection(): void
    {
        $this->selectedTenants = [];
    }

    public function showTenantDetails(Tenant $tenant): void
    {
        $this->editingTenant = $tenant;
        $this->showDetailsModal = true;
    }

    public function resetTenantData(Tenant $tenant): void
    {
        try {
            $service = app(MultiTenantService::class);
            $service->resetTenant($tenant);

            $this->dispatch('notify', type: 'success', message: 'Tenant data reset successfully');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to reset tenant: '.$e->getMessage());
        }
    }

    public function backupTenant(Tenant $tenant): void
    {
        try {
            $service = app(MultiTenantService::class);
            $backupPath = $service->backupTenant($tenant);

            $this->dispatch('notify', type: 'success', message: 'Tenant backed up successfully');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to backup tenant: '.$e->getMessage());
        }
    }

    private function resetForm(): void
    {
        $this->tenantName = '';
        $this->subdomain = '';
        $this->database = '';
        $this->adminEmail = '';
        $this->adminPassword = '';
        $this->plan = 'basic';
        $this->status = 'active';
        $this->customConfig = [];
        $this->editingTenant = null;
    }
}
