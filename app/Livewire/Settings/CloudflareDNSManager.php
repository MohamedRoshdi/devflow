<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\SystemSetting;
use App\Services\CloudflareDNSService;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Livewire\Component;

class CloudflareDNSManager extends Component
{
    public string $apiToken = '';

    public string $zoneId = '';

    /** @var array<int, array<string, mixed>> */
    public array $records = [];

    public bool $showAddModal = false;

    public string $newRecordType = 'A';

    public string $newRecordName = '';

    public string $newRecordContent = '';

    public bool $newRecordProxied = false;

    public int $newRecordTtl = 1;

    public bool $isConnected = false;

    public bool $isLoading = false;

    /** @var array<string, mixed>|null */
    public ?array $zoneInfo = null;

    public function mount(): void
    {
        $this->apiToken = (string) SystemSetting::get('cloudflare_api_token', config('services.cloudflare.api_token', ''));
        $this->zoneId = (string) SystemSetting::get('cloudflare_zone_id', '');

        if ($this->apiToken !== '' && $this->zoneId !== '') {
            $this->testConnection();
        }
    }

    public function testConnection(): void
    {
        if ($this->apiToken === '') {
            $this->dispatch('notification', type: 'error', message: 'API token is required.');

            return;
        }

        try {
            $response = Http::withToken($this->apiToken)
                ->get('https://api.cloudflare.com/client/v4/zones', ['per_page' => 1]);

            if ($response->failed()) {
                $this->isConnected = false;
                $this->dispatch('notification', type: 'error', message: 'Connection failed. Check your API token.');

                return;
            }

            $this->isConnected = true;

            if ($this->zoneId !== '') {
                $zoneResponse = Http::withToken($this->apiToken)
                    ->get("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}");

                if ($zoneResponse->ok()) {
                    /** @var array<string, mixed> */
                    $result = $zoneResponse->json('result', []);
                    $this->zoneInfo = $result;
                    $this->loadRecords();
                }
            }

            $this->dispatch('notification', type: 'success', message: 'Connected to Cloudflare successfully.');
        } catch (\Exception $e) {
            $this->isConnected = false;
            $this->dispatch('notification', type: 'error', message: 'Connection error. Please try again.');
        }
    }

    public function saveCredentials(): void
    {
        $this->validate([
            'apiToken' => 'required|string|min:10',
            'zoneId' => 'required|string|min:10',
        ], [
            'apiToken.required' => 'API token is required.',
            'zoneId.required' => 'Zone ID is required.',
        ]);

        SystemSetting::set('cloudflare_api_token', $this->apiToken, 'string');
        SystemSetting::set('cloudflare_zone_id', $this->zoneId, 'string');

        $this->dispatch('notification', type: 'success', message: 'Cloudflare credentials saved.');

        $this->testConnection();
    }

    public function loadRecords(): void
    {
        if (! $this->isConnected || $this->zoneId === '') {
            return;
        }

        $this->isLoading = true;

        try {
            $service = $this->buildService();
            $this->records = $service->listDNSRecords($this->zoneId);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to load DNS records.');
            $this->records = [];
        } finally {
            $this->isLoading = false;
        }
    }

    public function addRecord(): void
    {
        $this->validate([
            'newRecordType' => 'required|in:A,AAAA,CNAME,MX,TXT',
            'newRecordName' => 'required|string|max:255',
            'newRecordContent' => 'required|string|max:255',
            'newRecordTtl' => 'required|integer|min:1',
        ]);

        try {
            $service = $this->buildService();
            $service->createDNSRecord(
                $this->zoneId,
                $this->newRecordType,
                $this->newRecordName,
                $this->newRecordContent,
                $this->newRecordProxied,
            );

            $this->showAddModal = false;
            $this->reset(['newRecordType', 'newRecordName', 'newRecordContent', 'newRecordProxied', 'newRecordTtl']);
            $this->newRecordType = 'A';
            $this->newRecordTtl = 1;

            $this->loadRecords();
            $this->dispatch('notification', type: 'success', message: 'DNS record created successfully.');
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to create DNS record: '.$e->getMessage());
        }
    }

    public function deleteRecord(string $recordId): void
    {
        try {
            $service = $this->buildService();
            $service->deleteDNSRecord($this->zoneId, $recordId);

            $this->loadRecords();
            $this->dispatch('notification', type: 'success', message: 'DNS record deleted.');
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to delete record: '.$e->getMessage());
        }
    }

    public function toggleProxy(string $recordId): void
    {
        $record = collect($this->records)->firstWhere('id', $recordId);

        if ($record === null) {
            return;
        }

        try {
            $isProxied = (bool) ($record['proxied'] ?? false);

            Http::withToken($this->apiToken)
                ->patch("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records/{$recordId}", [
                    'proxied' => ! $isProxied,
                    'ttl' => ! $isProxied ? 1 : 3600,
                ]);

            $this->loadRecords();
            $this->dispatch('notification', type: 'success', message: 'Proxy status updated.');
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to toggle proxy: '.$e->getMessage());
        }
    }

    public function openAddModal(): void
    {
        $this->reset(['newRecordName', 'newRecordContent', 'newRecordProxied']);
        $this->newRecordType = 'A';
        $this->newRecordTtl = 1;
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
    }

    private function buildService(): CloudflareDNSService
    {
        // Temporarily override the config so the service picks up the saved token
        config(['services.cloudflare.api_token' => $this->apiToken]);

        return new CloudflareDNSService;
    }

    public function render(): View
    {
        return view('livewire.settings.cloudflare-dns-manager');
    }
}
