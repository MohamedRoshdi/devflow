<?php

declare(strict_types=1);

namespace App\Livewire\Servers\Security;

use App\Models\SecurityIncident;
use App\Models\Server;
use App\Services\Security\IncidentResponseService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class IncidentDashboard extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $severityFilter = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $serverFilter = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $sortField = 'detected_at';

    #[Url]
    public string $sortDirection = 'desc';

    public ?int $selectedIncidentId = null;

    public bool $showIncidentModal = false;

    public bool $showReportModal = false;

    public ?string $flashMessage = null;

    public ?string $flashType = null;

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSeverityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingServerFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    /**
     * @return LengthAwarePaginator<int, SecurityIncident>
     */
    #[Computed]
    public function incidents(): LengthAwarePaginator
    {
        return SecurityIncident::query()
            ->with(['server', 'user'])
            ->when($this->search, function (Builder $query): void {
                $query->where(function (Builder $q): void {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->severityFilter, fn (Builder $q) => $q->where('severity', $this->severityFilter))
            ->when($this->statusFilter, fn (Builder $q) => $q->where('status', $this->statusFilter))
            ->when($this->serverFilter, fn (Builder $q) => $q->where('server_id', $this->serverFilter))
            ->when($this->typeFilter, fn (Builder $q) => $q->where('incident_type', $this->typeFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Server>
     */
    #[Computed]
    public function servers(): \Illuminate\Database\Eloquent\Collection
    {
        return Server::orderBy('name')->get();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'total' => SecurityIncident::count(),
            'active' => SecurityIncident::active()->count(),
            'critical' => SecurityIncident::where('severity', SecurityIncident::SEVERITY_CRITICAL)
                ->active()
                ->count(),
            'high' => SecurityIncident::where('severity', SecurityIncident::SEVERITY_HIGH)
                ->active()
                ->count(),
            'resolved_today' => SecurityIncident::whereDate('resolved_at', today())->count(),
            'auto_remediated' => SecurityIncident::where('auto_remediated', true)->count(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getIncidentTypes(): array
    {
        return [
            SecurityIncident::TYPE_MALWARE => 'Malware',
            SecurityIncident::TYPE_BACKDOOR_USER => 'Backdoor User',
            SecurityIncident::TYPE_SUSPICIOUS_PROCESS => 'Suspicious Process',
            SecurityIncident::TYPE_BRUTE_FORCE => 'Brute Force',
            SecurityIncident::TYPE_UNAUTHORIZED_ACCESS => 'Unauthorized Access',
            SecurityIncident::TYPE_HIDDEN_DIRECTORY => 'Hidden Directory',
            SecurityIncident::TYPE_OUTBOUND_ATTACK => 'Outbound Attack',
            SecurityIncident::TYPE_ROOTKIT => 'Rootkit',
            SecurityIncident::TYPE_UNAUTHORIZED_SSH_KEY => 'Unauthorized SSH Key',
            SecurityIncident::TYPE_MALICIOUS_CRON => 'Malicious Cron',
        ];
    }

    public function viewIncident(int $incidentId): void
    {
        $this->selectedIncidentId = $incidentId;
        $this->showIncidentModal = true;
    }

    public function closeIncidentModal(): void
    {
        $this->showIncidentModal = false;
        $this->selectedIncidentId = null;
    }

    public function resolveIncident(int $incidentId): void
    {
        $incident = SecurityIncident::find($incidentId);

        if (! $incident) {
            $this->flashMessage = 'Incident not found';
            $this->flashType = 'error';

            return;
        }

        $incident->resolve(auth()->id());
        $this->flashMessage = "Incident #{$incidentId} marked as resolved";
        $this->flashType = 'success';
    }

    public function markFalsePositive(int $incidentId): void
    {
        $incident = SecurityIncident::find($incidentId);

        if (! $incident) {
            $this->flashMessage = 'Incident not found';
            $this->flashType = 'error';

            return;
        }

        $incident->markAsFalsePositive(auth()->id());
        $this->flashMessage = "Incident #{$incidentId} marked as false positive";
        $this->flashType = 'info';
    }

    public function startInvestigation(int $incidentId): void
    {
        $incident = SecurityIncident::find($incidentId);

        if (! $incident) {
            $this->flashMessage = 'Incident not found';
            $this->flashType = 'error';

            return;
        }

        $incident->update([
            'status' => SecurityIncident::STATUS_INVESTIGATING,
            'user_id' => auth()->id(),
        ]);

        $this->flashMessage = "Investigation started for incident #{$incidentId}";
        $this->flashType = 'info';
    }

    public function autoRemediate(int $incidentId): void
    {
        $incident = SecurityIncident::find($incidentId);

        if (! $incident) {
            $this->flashMessage = 'Incident not found';
            $this->flashType = 'error';

            return;
        }

        $responseService = app(IncidentResponseService::class);

        try {
            $result = $responseService->autoRemediate($incident);

            if ($result['success']) {
                $this->flashMessage = "Auto-remediation completed: {$result['message']}";
                $this->flashType = 'success';
            } else {
                $this->flashMessage = "Remediation failed: {$result['message']}";
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = "Error: {$e->getMessage()}";
            $this->flashType = 'error';
        }
    }

    public function generateReport(int $incidentId): void
    {
        $incident = SecurityIncident::with('server')->find($incidentId);

        if (! $incident) {
            $this->flashMessage = 'Incident not found';
            $this->flashType = 'error';

            return;
        }

        $responseService = app(IncidentResponseService::class);
        $report = $responseService->generateIncidentReport($incident);

        // Store report in session for display
        session(['incident_report' => $report]);
        $this->selectedIncidentId = $incidentId;
        $this->showReportModal = true;
    }

    public function closeReportModal(): void
    {
        $this->showReportModal = false;
        session()->forget('incident_report');
    }

    public function bulkResolve(): void
    {
        $count = SecurityIncident::active()
            ->where('severity', '!=', SecurityIncident::SEVERITY_CRITICAL)
            ->update([
                'status' => SecurityIncident::STATUS_RESOLVED,
                'resolved_at' => now(),
                'user_id' => auth()->id(),
            ]);

        $this->flashMessage = "{$count} non-critical incidents marked as resolved";
        $this->flashType = 'success';
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->severityFilter = '';
        $this->statusFilter = '';
        $this->serverFilter = '';
        $this->typeFilter = '';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.servers.security.incident-dashboard');
    }
}
