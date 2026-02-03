<?php

declare(strict_types=1);

namespace App\Livewire\Servers\Security;

use App\Models\RemediationLog;
use App\Models\SecurityIncident;
use App\Models\SecurityPrediction;
use App\Models\Server;
use App\Services\Security\SecurityGuardianService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

class SecurityGuardianDashboard extends Component
{
    use AuthorizesRequests;

    public Server $server;

    public bool $isScanning = false;

    public ?string $flashMessage = null;

    public ?string $flashType = null;

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
    }

    public function runGuardianScan(): void
    {
        $this->isScanning = true;

        try {
            $service = app(SecurityGuardianService::class);
            $results = $service->runFullScan($this->server, $this->server->auto_remediation_enabled);

            $threatCount = count($results['threats'] ?? []);
            $predictionCount = count($results['predictions'] ?? []);
            $incidentCount = count($results['incidents'] ?? []);

            $this->flashMessage = "Guardian scan complete: {$threatCount} threats, {$incidentCount} incidents, {$predictionCount} predictions";
            $this->flashType = $threatCount > 0 ? 'warning' : 'success';
            $this->server->refresh();
        } catch (\Exception $e) {
            $this->flashMessage = 'Scan failed: '.$e->getMessage();
            $this->flashType = 'error';
        }

        $this->isScanning = false;
    }

    public function toggleGuardian(): void
    {
        $this->server->update(['guardian_enabled' => ! $this->server->guardian_enabled]);
        $this->server->refresh();

        $status = $this->server->guardian_enabled ? 'enabled' : 'disabled';
        $this->flashMessage = "Security Guardian {$status}";
        $this->flashType = 'success';
    }

    public function toggleAutoRemediation(): void
    {
        $this->server->update(['auto_remediation_enabled' => ! $this->server->auto_remediation_enabled]);
        $this->server->refresh();

        $status = $this->server->auto_remediation_enabled ? 'enabled' : 'disabled';
        $this->flashMessage = "Auto-remediation {$status}";
        $this->flashType = 'success';
    }

    /**
     * @return array<string, mixed>
     */
    public function getOverviewProperty(): array
    {
        $service = app(SecurityGuardianService::class);

        return $service->getServerSecurityOverview($this->server);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\SecurityIncident>
     */
    public function getActiveIncidentsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->server->securityIncidents()
            ->active()
            ->orderBy('severity')
            ->orderBy('detected_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\SecurityPrediction>
     */
    public function getActivePredictionsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->server->securityPredictions()
            ->where('status', SecurityPrediction::STATUS_ACTIVE)
            ->orderBy('severity')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\RemediationLog>
     */
    public function getRecentRemediationsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->server->remediationLogs()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function acknowledgeIncident(int $incidentId): void
    {
        $incident = SecurityIncident::find($incidentId);
        if ($incident && $incident->server_id === $this->server->id) {
            $incident->update(['status' => SecurityIncident::STATUS_INVESTIGATING]);
            $this->flashMessage = 'Incident marked as investigating';
            $this->flashType = 'info';
        }
    }

    public function acknowledgePrediction(int $predictionId): void
    {
        $prediction = SecurityPrediction::find($predictionId);
        if ($prediction && $prediction->server_id === $this->server->id) {
            $prediction->acknowledge(auth()->id());
            $this->flashMessage = 'Prediction acknowledged';
            $this->flashType = 'info';
        }
    }

    public function render(): View
    {
        return view('livewire.servers.security.security-guardian-dashboard');
    }
}
