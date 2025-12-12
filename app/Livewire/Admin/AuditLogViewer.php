<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogViewer extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $userId = null;

    #[Url]
    public string $action = '';

    #[Url]
    public string $actionCategory = '';

    #[Url]
    public string $modelType = '';

    #[Url]
    public ?string $fromDate = null;

    #[Url]
    public ?string $toDate = null;

    #[Url]
    public ?string $ipAddress = null;

    public ?int $expandedLogId = null;

    private AuditService $auditService;

    public function boot(AuditService $auditService): void
    {
        $this->auditService = $auditService;
    }

    public function mount(): void
    {
        // Only users with view-audit-logs permission or super-admin role can access
        $user = auth()->user();
        abort_unless(
            $user && ($user->can('view-audit-logs') || $user->hasRole('super-admin')),
            403,
            'You do not have permission to view audit logs.'
        );
    }

    #[Computed]
    public function logs()
    {
        $filters = array_filter([
            'user_id' => $this->userId,
            'action' => $this->action,
            'action_category' => $this->actionCategory,
            'model_type' => $this->modelType,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'ip_address' => $this->ipAddress,
            'limit' => 50,
        ]);

        $logs = $this->auditService->getLogsFiltered($filters);

        // Additional search filtering
        if ($this->search) {
            $logs = $logs->filter(function ($log) {
                return str_contains(strtolower($log->action), strtolower($this->search))
                    || ($log->user && str_contains(strtolower($log->user->name), strtolower($this->search)))
                    || str_contains(strtolower($log->model_name), strtolower($this->search));
            });
        }

        return $logs;
    }

    #[Computed]
    public function users()
    {
        // Optimized: Cache users list for 10 minutes
        return Cache::remember('audit_users_list', 600, function () {
            return User::orderBy('name')->get(['id', 'name']);
        });
    }

    #[Computed]
    public function actionCategories()
    {
        // Optimized: Cache action categories for 30 minutes
        return Cache::remember('audit_action_categories', 1800, function () {
            return AuditLog::selectRaw('SUBSTRING_INDEX(action, ".", 1) as category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category');
        });
    }

    #[Computed]
    public function modelTypes()
    {
        // Optimized: Cache model types for 30 minutes
        return Cache::remember('audit_model_types', 1800, function () {
            return AuditLog::select('auditable_type')
                ->distinct()
                ->orderBy('auditable_type')
                ->pluck('auditable_type')
                ->map(fn ($type) => class_basename($type))
                ->unique();
        });
    }

    #[Computed]
    public function stats()
    {
        $filters = array_filter([
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
        ]);

        return $this->auditService->getActivityStats($filters);
    }

    public function toggleExpand(int $logId): void
    {
        $this->expandedLogId = $this->expandedLogId === $logId ? null : $logId;
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'userId',
            'action',
            'actionCategory',
            'modelType',
            'fromDate',
            'toDate',
            'ipAddress',
        ]);
        $this->resetPage();
    }

    public function exportCsv(): \Illuminate\Http\Response
    {
        $filters = array_filter([
            'user_id' => $this->userId,
            'action' => $this->action,
            'action_category' => $this->actionCategory,
            'model_type' => $this->modelType,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'ip_address' => $this->ipAddress,
            'limit' => 10000,
        ]);

        $csv = $this->auditService->exportToCsv($filters);
        $filename = 'audit-logs-'.now()->format('Y-m-d-His').'.csv';

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingUserId(): void
    {
        $this->resetPage();
    }

    public function updatingActionCategory(): void
    {
        $this->resetPage();
    }

    public function updatingModelType(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.audit-log-viewer');
    }
}
