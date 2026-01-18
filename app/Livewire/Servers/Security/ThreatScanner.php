<?php

declare(strict_types=1);

namespace App\Livewire\Servers\Security;

use App\Models\SecurityIncident;
use App\Models\Server;
use App\Services\Security\IncidentResponseService;
use App\Services\Security\ThreatDetectionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ThreatScanner extends Component
{
    use AuthorizesRequests;

    public Server $server;

    public bool $isScanning = false;

    public ?float $scanTime = null;

    /** @var array<int, array<string, mixed>> */
    public array $threats = [];

    /** @var array<int, SecurityIncident> */
    public array $createdIncidents = [];

    public ?string $flashMessage = null;

    public ?string $flashType = null;

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
    }

    public function runThreatScan(): void
    {
        $this->isScanning = true;
        $this->threats = [];
        $this->createdIncidents = [];
        $this->flashMessage = null;

        try {
            $service = app(ThreatDetectionService::class);
            $result = $service->scanServer($this->server);

            $this->threats = $result['threats'];
            $this->scanTime = $result['scan_time'];

            if (empty($this->threats)) {
                $this->flashMessage = 'No threats detected. Server appears secure.';
                $this->flashType = 'success';
            } else {
                // Create incidents from threats
                $incidents = $service->createIncidentsFromThreats(
                    $this->server,
                    $this->threats,
                    auth()->id()
                );
                $this->createdIncidents = $incidents;

                $this->flashMessage = count($this->threats).' threat(s) detected and '.count($incidents).' incident(s) created.';
                $this->flashType = 'warning';
            }

            $this->server->refresh();
        } catch (\Exception $e) {
            $this->flashMessage = 'Scan failed: '.$e->getMessage();
            $this->flashType = 'error';
        }

        $this->isScanning = false;
    }

    public function remediate(string $action, int $incidentId): void
    {
        $incident = SecurityIncident::find($incidentId);

        if (! $incident || $incident->server_id !== $this->server->id) {
            $this->flashMessage = 'Incident not found';
            $this->flashType = 'error';

            return;
        }

        $responseService = app(IncidentResponseService::class);

        try {
            $result = match ($action) {
                'kill_process' => $responseService->killProcess(
                    $this->server,
                    (int) ($incident->affected_items['pid'] ?? 0)
                ),
                'remove_directory' => $responseService->removeDirectory(
                    $this->server,
                    $incident->affected_items['path'] ?? ''
                ),
                'block_outbound_ssh' => $responseService->blockOutboundSSH($this->server),
                'harden_ssh' => $responseService->hardenSSH($this->server),
                'install_fail2ban' => $responseService->installFail2ban($this->server),
                default => ['success' => false, 'message' => 'Unknown action'],
            };

            $incident->addRemediationAction($action, $result['success'], $result['message']);

            if ($result['success']) {
                $this->flashMessage = $result['message'];
                $this->flashType = 'success';
            } else {
                $this->flashMessage = 'Action failed: '.$result['message'];
                $this->flashType = 'error';
            }
        } catch (\Exception $e) {
            $this->flashMessage = 'Error: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function resolveIncident(int $incidentId): void
    {
        $incident = SecurityIncident::find($incidentId);

        if (! $incident || $incident->server_id !== $this->server->id) {
            $this->flashMessage = 'Incident not found';
            $this->flashType = 'error';

            return;
        }

        $incident->resolve(auth()->id());
        $this->flashMessage = 'Incident marked as resolved';
        $this->flashType = 'success';
        $this->server->refresh();
    }

    public function markFalsePositive(int $incidentId): void
    {
        $incident = SecurityIncident::find($incidentId);

        if (! $incident || $incident->server_id !== $this->server->id) {
            $this->flashMessage = 'Incident not found';
            $this->flashType = 'error';

            return;
        }

        $incident->markAsFalsePositive(auth()->id());
        $this->flashMessage = 'Incident marked as false positive';
        $this->flashType = 'info';
        $this->server->refresh();
    }

    public function toggleAutoRemediation(): void
    {
        $this->server->update([
            'auto_remediation_enabled' => ! $this->server->auto_remediation_enabled,
        ]);
        $this->server->refresh();

        $status = $this->server->auto_remediation_enabled ? 'enabled' : 'disabled';
        $this->flashMessage = "Auto-remediation {$status}";
        $this->flashType = 'info';
    }

    public function enableLockdown(): void
    {
        $responseService = app(IncidentResponseService::class);

        try {
            $result = $responseService->enableLockdownMode($this->server);

            if ($result['success']) {
                $this->flashMessage = 'Lockdown mode enabled. Only SSH access is allowed.';
                $this->flashType = 'warning';
            } else {
                $this->flashMessage = 'Failed to enable lockdown: '.$result['message'];
                $this->flashType = 'error';
            }

            $this->server->refresh();
        } catch (\Exception $e) {
            $this->flashMessage = 'Error: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function disableLockdown(): void
    {
        $responseService = app(IncidentResponseService::class);

        try {
            $result = $responseService->disableLockdownMode($this->server);

            if ($result['success']) {
                $this->flashMessage = 'Lockdown mode disabled. Normal traffic restored.';
                $this->flashType = 'success';
            } else {
                $this->flashMessage = 'Failed to disable lockdown: '.$result['message'];
                $this->flashType = 'error';
            }

            $this->server->refresh();
        } catch (\Exception $e) {
            $this->flashMessage = 'Error: '.$e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function autoRemediateAll(): void
    {
        $responseService = app(IncidentResponseService::class);
        $remediated = 0;
        $failed = 0;

        $activeIncidents = $this->server->securityIncidents()
            ->active()
            ->whereIn('severity', ['critical', 'high'])
            ->get();

        foreach ($activeIncidents as $incident) {
            try {
                $result = $responseService->autoRemediate($incident);
                if ($result['success']) {
                    $remediated++;
                } else {
                    $failed++;
                }
            } catch (\Exception) {
                $failed++;
            }
        }

        $this->flashMessage = "Auto-remediation complete: {$remediated} succeeded, {$failed} failed";
        $this->flashType = $failed > 0 ? 'warning' : 'success';
        $this->server->refresh();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, SecurityIncident>
     */
    public function getActiveIncidentsProperty()
    {
        return $this->server->securityIncidents()
            ->active()
            ->orderBy('detected_at', 'desc')
            ->get();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.security.threat-scanner');
    }
}
