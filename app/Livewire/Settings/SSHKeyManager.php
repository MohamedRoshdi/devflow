<?php

namespace App\Livewire\Settings;

use App\Models\SSHKey;
use App\Models\Server;
use App\Services\SSHKeyService;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Crypt;

class SSHKeyManager extends Component
{
    public $keys;
    public bool $showCreateModal = false;
    public bool $showImportModal = false;
    public bool $showDeployModal = false;
    public bool $showViewKeyModal = false;

    // Generate Key Form
    public string $newKeyName = '';
    public string $newKeyType = 'ed25519';

    // Import Key Form
    public string $importKeyName = '';
    public string $importPublicKey = '';
    public string $importPrivateKey = '';

    // Deploy Key Form
    public $selectedKeyId = null;
    public $selectedServerId = null;

    // View Key Data
    public $viewingKey = null;

    // Generated Key (for display after generation)
    public $generatedKey = null;

    public function mount(): void
    {
        $this->loadKeys();
    }

    public function loadKeys(): void
    {
        $this->keys = SSHKey::where('user_id', auth()->id())
            ->with(['servers'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->reset(['newKeyName', 'newKeyType', 'generatedKey']);
        $this->showCreateModal = true;
    }

    public function openImportModal(): void
    {
        $this->reset(['importKeyName', 'importPublicKey', 'importPrivateKey']);
        $this->showImportModal = true;
    }

    public function openDeployModal(int $keyId): void
    {
        $this->selectedKeyId = $keyId;
        $this->selectedServerId = null;
        $this->showDeployModal = true;
    }

    public function openViewKeyModal(int $keyId): void
    {
        $key = SSHKey::where('id', $keyId)
            ->where('user_id', auth()->id())
            ->first();

        if ($key) {
            $this->viewingKey = [
                'id' => $key->id,
                'name' => $key->name,
                'type' => $key->type,
                'public_key' => $key->public_key,
                'fingerprint' => $key->fingerprint,
                'created_at' => $key->created_at->format('Y-m-d H:i:s'),
            ];
            $this->showViewKeyModal = true;
        }
    }

    public function generateKey(): void
    {
        $this->validate([
            'newKeyName' => 'required|string|max:100',
            'newKeyType' => 'required|in:rsa,ed25519,ecdsa',
        ]);

        try {
            $sshKeyService = app(SSHKeyService::class);

            // Generate key pair
            $result = $sshKeyService->generateKeyPair(
                $this->newKeyType,
                "devflow-{$this->newKeyName}"
            );

            if (!$result['success']) {
                session()->flash('error', $result['error'] ?? 'Failed to generate SSH key');
                return;
            }

            // Save to database
            $key = SSHKey::create([
                'user_id' => auth()->id(),
                'name' => $this->newKeyName,
                'type' => $this->newKeyType,
                'public_key' => $result['public_key'],
                'private_key_encrypted' => Crypt::encryptString($result['private_key']),
                'fingerprint' => $result['fingerprint'],
            ]);

            // Store generated key for display
            $this->generatedKey = [
                'id' => $key->id,
                'name' => $key->name,
                'public_key' => $result['public_key'],
                'private_key' => $result['private_key'],
                'fingerprint' => $result['fingerprint'],
            ];

            $this->loadKeys();
            session()->flash('message', "SSH key '{$this->newKeyName}' generated successfully!");

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to generate SSH key: ' . $e->getMessage());
        }
    }

    public function importKey(): void
    {
        $this->validate([
            'importKeyName' => 'required|string|max:100',
            'importPublicKey' => 'required|string',
            'importPrivateKey' => 'required|string',
        ]);

        try {
            $sshKeyService = app(SSHKeyService::class);

            // Validate and import key
            $result = $sshKeyService->importKey(
                $this->importPublicKey,
                $this->importPrivateKey
            );

            if (!$result['success']) {
                session()->flash('error', $result['error'] ?? 'Failed to import SSH key');
                return;
            }

            // Check for duplicate fingerprint
            $exists = SSHKey::where('user_id', auth()->id())
                ->where('fingerprint', $result['fingerprint'])
                ->exists();

            if ($exists) {
                session()->flash('error', 'An SSH key with this fingerprint already exists');
                return;
            }

            // Save to database
            SSHKey::create([
                'user_id' => auth()->id(),
                'name' => $this->importKeyName,
                'type' => $result['type'],
                'public_key' => trim($this->importPublicKey),
                'private_key_encrypted' => Crypt::encryptString(trim($this->importPrivateKey)),
                'fingerprint' => $result['fingerprint'],
            ]);

            $this->loadKeys();
            $this->showImportModal = false;
            session()->flash('message', "SSH key '{$this->importKeyName}' imported successfully!");

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to import SSH key: ' . $e->getMessage());
        }
    }

    public function deployToServer(): void
    {
        $this->validate([
            'selectedKeyId' => 'required|exists:ssh_keys,id',
            'selectedServerId' => 'required|exists:servers,id',
        ]);

        try {
            $key = SSHKey::where('id', $this->selectedKeyId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $server = Server::where('id', $this->selectedServerId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $sshKeyService = app(SSHKeyService::class);
            $result = $sshKeyService->deployKeyToServer($key, $server);

            if (!$result['success']) {
                session()->flash('error', $result['error'] ?? 'Failed to deploy SSH key');
                return;
            }

            // Update pivot table
            $key->servers()->syncWithoutDetaching([
                $server->id => ['deployed_at' => now()]
            ]);

            $this->loadKeys();
            $this->showDeployModal = false;
            session()->flash('message', $result['message'] ?? 'SSH key deployed successfully!');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to deploy SSH key: ' . $e->getMessage());
        }
    }

    public function removeFromServer(int $keyId, int $serverId): void
    {
        try {
            $key = SSHKey::where('id', $keyId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $server = Server::where('id', $serverId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $sshKeyService = app(SSHKeyService::class);
            $result = $sshKeyService->removeKeyFromServer($key, $server);

            if (!$result['success']) {
                session()->flash('error', $result['error'] ?? 'Failed to remove SSH key');
                return;
            }

            // Remove from pivot table
            $key->servers()->detach($server->id);

            $this->loadKeys();
            session()->flash('message', $result['message'] ?? 'SSH key removed from server successfully!');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove SSH key: ' . $e->getMessage());
        }
    }

    public function deleteKey(int $id): void
    {
        try {
            $key = SSHKey::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Remove from all servers first
            $sshKeyService = app(SSHKeyService::class);
            foreach ($key->servers as $server) {
                try {
                    $sshKeyService->removeKeyFromServer($key, $server);
                } catch (\Exception $e) {
                    // Continue even if removal fails
                    \Log::warning("Failed to remove SSH key from server {$server->id}: " . $e->getMessage());
                }
            }

            $keyName = $key->name;
            $key->delete();

            $this->loadKeys();
            session()->flash('message', "SSH key '{$keyName}' deleted successfully!");

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete SSH key: ' . $e->getMessage());
        }
    }

    public function downloadPrivateKey(int $id): void
    {
        try {
            $key = SSHKey::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $privateKey = $key->decrypted_private_key;

            if (!$privateKey) {
                session()->flash('error', 'Could not decrypt private key');
                return;
            }

            $filename = "id_{$key->type}_{$key->name}";
            $this->dispatch('download-private-key', [
                'filename' => $filename,
                'content' => $privateKey,
            ]);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to download private key: ' . $e->getMessage());
        }
    }

    public function copyPublicKey(int $id): void
    {
        try {
            $key = SSHKey::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $this->dispatch('copy-to-clipboard', [
                'text' => $key->public_key,
            ]);

            session()->flash('message', 'Public key copied to clipboard!');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to copy public key: ' . $e->getMessage());
        }
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->showImportModal = false;
        $this->showDeployModal = false;
        $this->showViewKeyModal = false;
        $this->generatedKey = null;
        $this->viewingKey = null;
    }

    public function render()
    {
        $servers = Server::where('user_id', auth()->id())
            ->where('status', 'online')
            ->orderBy('name')
            ->get();

        return view('livewire.settings.ssh-key-manager', [
            'servers' => $servers,
        ]);
    }
}
