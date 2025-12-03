<?php

declare(strict_types=1);

namespace App\Livewire\SSL;

use App\Models\SSLCertificate;
use App\Models\Domain;
use App\Services\SSLManagementService;
use Livewire\Component;
use Livewire\Attributes\{Computed, On};
use Livewire\WithPagination;

class SSLManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public int $expiryDaysFilter = 90; // Show all certificates expiring within 90 days

    public ?Domain $selectedDomain = null;
    public bool $showCertificateModal = false;
    public bool $showIssueModal = false;
    public bool $isProcessing = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    #[Computed]
    public function certificates()
    {
        return SSLCertificate::query()
            ->with(['domain.project', 'server'])
            ->when($this->search, function ($query) {
                $query->where('domain_name', 'like', "%{$this->search}%");
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'expiring') {
                    $thresholdDate = now()->addDays(30);
                    $query->where('expires_at', '<=', $thresholdDate)
                        ->where('expires_at', '>=', now());
                } elseif ($this->statusFilter === 'expired') {
                    $query->where('expires_at', '<', now());
                } else {
                    $query->where('status', $this->statusFilter);
                }
            })
            ->orderBy('expires_at', 'asc')
            ->paginate(20);
    }

    #[Computed]
    public function statistics()
    {
        return [
            'total' => SSLCertificate::count(),
            'active' => SSLCertificate::where('status', 'issued')->count(),
            'expiring_soon' => SSLCertificate::where('status', 'issued')
                ->where('expires_at', '<=', now()->addDays(30))
                ->where('expires_at', '>=', now())
                ->count(),
            'expired' => SSLCertificate::where('expires_at', '<', now())->count(),
            'failed' => SSLCertificate::where('status', 'failed')->count(),
        ];
    }

    #[Computed]
    public function criticalCertificates()
    {
        return SSLCertificate::where('status', 'issued')
            ->where('expires_at', '<=', now()->addDays(7))
            ->where('expires_at', '>=', now())
            ->with(['domain', 'server'])
            ->orderBy('expires_at', 'asc')
            ->limit(5)
            ->get();
    }

    public function viewCertificate(int $certificateId): void
    {
        $certificate = SSLCertificate::with(['domain', 'server'])->findOrFail($certificateId);
        $this->selectedDomain = $certificate->domain;
        $this->showCertificateModal = true;
    }

    public function closeCertificateModal(): void
    {
        $this->showCertificateModal = false;
        $this->selectedDomain = null;
    }

    public function openIssueModal(): void
    {
        $this->showIssueModal = true;
    }

    public function closeIssueModal(): void
    {
        $this->showIssueModal = false;
    }

    public function issueCertificate(int $domainId): void
    {
        $domain = Domain::findOrFail($domainId);
        $this->isProcessing = true;

        try {
            $service = app(SSLManagementService::class);
            $service->issueCertificate($domain);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "SSL certificate issued successfully for {$domain->domain}",
            ]);

            unset($this->certificates);
            unset($this->statistics);
            $this->closeIssueModal();

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to issue certificate: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    public function renewCertificate(int $domainId): void
    {
        $domain = Domain::findOrFail($domainId);
        $this->isProcessing = true;

        try {
            $service = app(SSLManagementService::class);
            $service->renewCertificate($domain);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "SSL certificate renewed successfully for {$domain->domain}",
            ]);

            unset($this->certificates);
            unset($this->statistics);
            unset($this->criticalCertificates);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to renew certificate: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    public function renewAllExpiring(): void
    {
        if (!$this->confirm('Are you sure you want to renew all expiring certificates?')) {
            return;
        }

        $this->isProcessing = true;

        try {
            $service = app(SSLManagementService::class);
            $results = $service->renewExpiringCertificates(30);

            $successCount = count($results['success']);
            $failedCount = count($results['failed']);

            if ($successCount > 0) {
                $this->dispatch('notification', [
                    'type' => 'success',
                    'message' => "Successfully renewed {$successCount} certificate(s).",
                ]);
            }

            if ($failedCount > 0) {
                $this->dispatch('notification', [
                    'type' => 'warning',
                    'message' => "Failed to renew {$failedCount} certificate(s). Check logs for details.",
                ]);
            }

            unset($this->certificates);
            unset($this->statistics);
            unset($this->criticalCertificates);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Bulk renewal failed: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    public function revokeCertificate(int $domainId): void
    {
        if (!$this->confirm('Are you sure you want to revoke this certificate? This action cannot be undone.')) {
            return;
        }

        $domain = Domain::findOrFail($domainId);
        $this->isProcessing = true;

        try {
            $service = app(SSLManagementService::class);
            $service->revokeCertificate($domain);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "SSL certificate revoked for {$domain->domain}",
            ]);

            unset($this->certificates);
            unset($this->statistics);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to revoke certificate: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    public function checkExpiry(int $domainId): void
    {
        $domain = Domain::findOrFail($domainId);

        try {
            $service = app(SSLManagementService::class);
            $expiryDate = $service->checkExpiry($domain);

            if ($expiryDate) {
                $daysLeft = now()->diffInDays($expiryDate, false);
                $this->dispatch('notification', [
                    'type' => 'info',
                    'message' => "Certificate expires on {$expiryDate->format('Y-m-d')} ({$daysLeft} days)",
                ]);
            } else {
                $this->dispatch('notification', [
                    'type' => 'warning',
                    'message' => 'Unable to determine certificate expiry date.',
                ]);
            }

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to check expiry: ' . $e->getMessage(),
            ]);
        }
    }

    #[On('certificate-issued')]
    #[On('certificate-renewed')]
    public function refreshCertificates(): void
    {
        unset($this->certificates);
        unset($this->statistics);
        unset($this->criticalCertificates);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.ssl.ssl-manager');
    }
}
