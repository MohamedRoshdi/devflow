<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use App\Models\SSLCertificate;
use App\Services\SSLService;
use Livewire\Component;
use Livewire\Attributes\{Computed, Locked, On};
use Illuminate\Support\Facades\Log;

class SSLManager extends Component
{
    #[Locked]
    public Server $server;

    public bool $showIssueModal = false;
    public string $newDomain = '';
    public string $newEmail = '';
    public bool $issuingCertificate = false;
    public bool $installingCertbot = false;

    public function mount(Server $server): void
    {
        $this->server = $server;
        $this->newEmail = config('mail.from.address', 'admin@example.com');
    }

    #[Computed]
    public function certificates()
    {
        return SSLCertificate::where('server_id', $this->server->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function stats()
    {
        $certificates = $this->certificates();

        return [
            'total' => $certificates->count(),
            'active' => $certificates->where('status', 'issued')->count(),
            'expiring_soon' => $certificates->filter(fn($cert) => $cert->isExpiringSoon(30))->count(),
            'expired' => $certificates->filter(fn($cert) => $cert->isExpired())->count(),
        ];
    }

    #[Computed]
    public function certbotInstalled()
    {
        $sslService = app(SSLService::class);
        return $sslService->checkCertbotInstalled($this->server);
    }

    public function openIssueModal(): void
    {
        $this->showIssueModal = true;
        $this->reset('newDomain', 'issuingCertificate');
        $this->newEmail = config('mail.from.address', 'admin@example.com');
    }

    public function closeIssueModal(): void
    {
        $this->showIssueModal = false;
        $this->reset('newDomain', 'newEmail', 'issuingCertificate');
    }

    public function installCertbot(): void
    {
        $this->installingCertbot = true;

        try {
            $sslService = app(SSLService::class);
            $result = $sslService->installCertbot($this->server);

            if ($result['success']) {
                session()->flash('message', $result['message']);
                unset($this->certbotInstalled);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Certbot installation failed', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to install certbot: ' . $e->getMessage());
        } finally {
            $this->installingCertbot = false;
        }
    }

    public function issueCertificate(): void
    {
        $this->validate([
            'newDomain' => 'required|string|max:255|regex:/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i',
            'newEmail' => 'required|email|max:255',
        ]);

        $this->issuingCertificate = true;

        try {
            $sslService = app(SSLService::class);
            $result = $sslService->issueCertificate($this->server, $this->newDomain, $this->newEmail);

            if ($result['success']) {
                session()->flash('message', "SSL certificate issued successfully for {$this->newDomain}");
                $this->closeIssueModal();
                unset($this->certificates, $this->stats);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('SSL certificate issuance failed', [
                'server_id' => $this->server->id,
                'domain' => $this->newDomain,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to issue certificate: ' . $e->getMessage());
        } finally {
            $this->issuingCertificate = false;
        }
    }

    public function renewCertificate(int $certificateId): void
    {
        try {
            $certificate = SSLCertificate::findOrFail($certificateId);

            if ($certificate->server_id !== $this->server->id) {
                session()->flash('error', 'Certificate does not belong to this server');
                return;
            }

            $sslService = app(SSLService::class);
            $result = $sslService->renewCertificate($certificate);

            if ($result['success']) {
                session()->flash('message', "Certificate renewed successfully for {$certificate->domain_name}");
                unset($this->certificates, $this->stats);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('SSL certificate renewal failed', [
                'certificate_id' => $certificateId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to renew certificate: ' . $e->getMessage());
        }
    }

    public function revokeCertificate(int $certificateId): void
    {
        try {
            $certificate = SSLCertificate::findOrFail($certificateId);

            if ($certificate->server_id !== $this->server->id) {
                session()->flash('error', 'Certificate does not belong to this server');
                return;
            }

            $sslService = app(SSLService::class);
            $result = $sslService->revokeCertificate($certificate);

            if ($result['success']) {
                session()->flash('message', "Certificate revoked successfully for {$certificate->domain_name}");
                unset($this->certificates, $this->stats);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('SSL certificate revocation failed', [
                'certificate_id' => $certificateId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to revoke certificate: ' . $e->getMessage());
        }
    }

    public function deleteCertificate(int $certificateId): void
    {
        try {
            $certificate = SSLCertificate::findOrFail($certificateId);

            if ($certificate->server_id !== $this->server->id) {
                session()->flash('error', 'Certificate does not belong to this server');
                return;
            }

            $certificate->delete();

            session()->flash('message', "Certificate record deleted for {$certificate->domain_name}");
            unset($this->certificates, $this->stats);
        } catch (\Exception $e) {
            Log::error('SSL certificate deletion failed', [
                'certificate_id' => $certificateId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to delete certificate: ' . $e->getMessage());
        }
    }

    public function toggleAutoRenew(int $certificateId): void
    {
        try {
            $certificate = SSLCertificate::findOrFail($certificateId);

            if ($certificate->server_id !== $this->server->id) {
                session()->flash('error', 'Certificate does not belong to this server');
                return;
            }

            $certificate->update([
                'auto_renew' => !$certificate->auto_renew,
            ]);

            $status = $certificate->auto_renew ? 'enabled' : 'disabled';
            session()->flash('message', "Auto-renewal {$status} for {$certificate->domain_name}");
            unset($this->certificates);
        } catch (\Exception $e) {
            Log::error('Toggle auto-renew failed', [
                'certificate_id' => $certificateId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to toggle auto-renewal: ' . $e->getMessage());
        }
    }

    public function setupAutoRenewal(): void
    {
        try {
            $sslService = app(SSLService::class);
            $result = $sslService->setupAutoRenewal($this->server);

            if ($result['success']) {
                session()->flash('message', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Auto-renewal setup failed', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to setup auto-renewal: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.servers.s-s-l-manager')->layout('layouts.app');
    }
}
