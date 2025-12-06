<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Project;
use App\Models\StorageConfiguration;
use App\Services\Backup\RemoteStorageService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class StorageSettings extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $activeTab = 's3';

    // Common fields
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|in:local,s3,gcs,ftp,sftp')]
    public string $driver = 's3';

    #[Validate('nullable|integer|exists:projects,id')]
    public ?int $project_id = null;

    #[Validate('nullable|string|max:255')]
    public string $bucket = '';

    #[Validate('nullable|string|max:255')]
    public string $region = '';

    #[Validate('nullable|string|max:500')]
    public string $endpoint = '';

    #[Validate('nullable|string|max:500')]
    public string $path_prefix = '';

    public bool $enable_encryption = false;

    public string $encryption_key = '';

    // S3 Credentials
    public string $s3_access_key = '';

    public string $s3_secret_key = '';

    // GCS Credentials
    public string $gcs_service_account = '';

    // FTP Credentials
    public string $ftp_host = '';

    public string $ftp_port = '21';

    public string $ftp_username = '';

    public string $ftp_password = '';

    public string $ftp_path = '/';

    public bool $ftp_passive = true;

    public bool $ftp_ssl = false;

    // SFTP Credentials
    public string $sftp_host = '';

    public string $sftp_port = '22';

    public string $sftp_username = '';

    public string $sftp_password = '';

    public string $sftp_private_key = '';

    public string $sftp_passphrase = '';

    public string $sftp_path = '/';

    /**
     * @var array<string, mixed>
     */
    public array $testResults = [];

    public bool $isTesting = false;

    private RemoteStorageService $storageService;

    public function boot(RemoteStorageService $storageService): void
    {
        $this->storageService = $storageService;
    }

    #[Computed]
    public function storageConfigs()
    {
        return StorageConfiguration::with('project')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function projects()
    {
        return Project::orderBy('name')->get();
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'editingId', 'name', 'driver', 'project_id', 'bucket', 'region',
            'endpoint', 'path_prefix', 'enable_encryption', 'encryption_key',
            's3_access_key', 's3_secret_key', 'gcs_service_account',
            'ftp_host', 'ftp_port', 'ftp_username', 'ftp_password', 'ftp_path',
            'ftp_passive', 'ftp_ssl', 'sftp_host', 'sftp_port', 'sftp_username',
            'sftp_password', 'sftp_private_key', 'sftp_passphrase', 'sftp_path',
            'testResults',
        ]);
        $this->activeTab = 's3';
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $config = StorageConfiguration::findOrFail($id);

        $this->editingId = $config->id;
        $this->name = $config->name;
        $this->driver = $config->driver;
        $this->activeTab = $config->driver;
        $this->project_id = $config->project_id;
        $this->bucket = $config->bucket ?? '';
        $this->region = $config->region ?? '';
        $this->endpoint = $config->endpoint ?? '';
        $this->path_prefix = $config->path_prefix ?? '';
        $this->enable_encryption = ! empty($config->encryption_key);
        $this->encryption_key = $config->encryption_key ?? '';

        $credentials = $config->credentials;

        // Load driver-specific credentials
        match ($config->driver) {
            's3' => $this->loadS3Credentials($credentials),
            'gcs' => $this->loadGcsCredentials($credentials),
            'ftp' => $this->loadFtpCredentials($credentials),
            'sftp' => $this->loadSftpCredentials($credentials),
            default => null,
        };

        $this->showModal = true;
    }

    private function loadS3Credentials(array $credentials): void
    {
        $this->s3_access_key = $credentials['access_key_id'] ?? '';
        $this->s3_secret_key = $credentials['secret_access_key'] ?? '';
    }

    private function loadGcsCredentials(array $credentials): void
    {
        $this->gcs_service_account = is_array($credentials['service_account_json'] ?? null)
            ? json_encode($credentials['service_account_json'], JSON_PRETTY_PRINT)
            : ($credentials['service_account_json'] ?? '');
    }

    private function loadFtpCredentials(array $credentials): void
    {
        $this->ftp_host = $credentials['host'] ?? '';
        $this->ftp_port = (string) ($credentials['port'] ?? 21);
        $this->ftp_username = $credentials['username'] ?? '';
        $this->ftp_password = $credentials['password'] ?? '';
        $this->ftp_path = $credentials['path'] ?? '/';
        $this->ftp_passive = (bool) ($credentials['passive'] ?? true);
        $this->ftp_ssl = (bool) ($credentials['ssl'] ?? false);
    }

    private function loadSftpCredentials(array $credentials): void
    {
        $this->sftp_host = $credentials['host'] ?? '';
        $this->sftp_port = (string) ($credentials['port'] ?? 22);
        $this->sftp_username = $credentials['username'] ?? '';
        $this->sftp_password = $credentials['password'] ?? '';
        $this->sftp_private_key = $credentials['private_key'] ?? '';
        $this->sftp_passphrase = $credentials['passphrase'] ?? '';
        $this->sftp_path = $credentials['path'] ?? '/';
    }

    public function generateEncryptionKey(): void
    {
        $this->encryption_key = base64_encode(random_bytes(32));
        $this->enable_encryption = true;

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Encryption key generated successfully',
        ]);
    }

    public function save(): void
    {
        $this->validate();

        try {
            $credentials = $this->getCredentialsForDriver();

            $data = [
                'name' => $this->name,
                'driver' => $this->driver,
                'project_id' => $this->project_id,
                'credentials' => $credentials,
                'bucket' => $this->bucket ?: null,
                'region' => $this->region ?: null,
                'endpoint' => $this->endpoint ?: null,
                'path_prefix' => $this->path_prefix ?: null,
                'encryption_key' => $this->enable_encryption ? $this->encryption_key : null,
                'status' => 'active',
            ];

            if ($this->editingId) {
                $config = StorageConfiguration::findOrFail($this->editingId);
                $config->update($data);
                $message = 'Storage configuration updated successfully';
            } else {
                StorageConfiguration::create($data);
                $message = 'Storage configuration created successfully';
            }

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => $message,
            ]);

            $this->showModal = false;
            $this->reset();
            unset($this->storageConfigs);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to save configuration: '.$e->getMessage(),
            ]);
        }
    }

    private function getCredentialsForDriver(): array
    {
        return match ($this->driver) {
            's3' => [
                'access_key_id' => $this->s3_access_key,
                'secret_access_key' => $this->s3_secret_key,
            ],
            'gcs' => [
                'service_account_json' => json_decode($this->gcs_service_account, true) ?? $this->gcs_service_account,
            ],
            'ftp' => [
                'host' => $this->ftp_host,
                'port' => (int) $this->ftp_port,
                'username' => $this->ftp_username,
                'password' => $this->ftp_password,
                'path' => $this->ftp_path,
                'passive' => $this->ftp_passive,
                'ssl' => $this->ftp_ssl,
            ],
            'sftp' => [
                'host' => $this->sftp_host,
                'port' => (int) $this->sftp_port,
                'username' => $this->sftp_username,
                'password' => $this->sftp_password ?: null,
                'private_key' => $this->sftp_private_key ?: null,
                'passphrase' => $this->sftp_passphrase ?: null,
                'path' => $this->sftp_path,
            ],
            default => [],
        };
    }

    public function testConnection(int $id): void
    {
        $this->isTesting = true;
        $this->testResults = [];

        try {
            $config = StorageConfiguration::findOrFail($id);
            $results = $this->storageService->testConnection($config);

            $this->testResults = $results;

            if ($results['success']) {
                $this->dispatch('notification', [
                    'type' => 'success',
                    'message' => 'Connection test successful!',
                ]);
            } else {
                $this->dispatch('notification', [
                    'type' => 'error',
                    'message' => 'Connection test failed: '.($results['error'] ?? 'Unknown error'),
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Test failed: '.$e->getMessage(),
            ]);
        } finally {
            $this->isTesting = false;
        }
    }

    public function setAsDefault(int $id): void
    {
        try {
            // Remove default from all configs
            StorageConfiguration::where('is_default', true)->update(['is_default' => false]);

            // Set new default
            $config = StorageConfiguration::findOrFail($id);
            $config->update(['is_default' => true]);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "{$config->name} is now the default storage",
            ]);

            unset($this->storageConfigs);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to set default: '.$e->getMessage(),
            ]);
        }
    }

    public function delete(int $id): void
    {
        try {
            $config = StorageConfiguration::findOrFail($id);
            $name = $config->name;
            $config->delete();

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "Storage configuration '{$name}' deleted successfully",
            ]);

            unset($this->storageConfigs);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to delete: '.$e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.settings.storage-settings');
    }
}
