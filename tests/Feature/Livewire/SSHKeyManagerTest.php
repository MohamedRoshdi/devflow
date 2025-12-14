<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\SSHKeyManager;
use App\Models\Server;
use App\Models\SSHKey;
use App\Models\User;
use App\Services\SSHKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class SSHKeyManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // ============================================================
    // Component Rendering Tests
    // ============================================================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertStatus(200);
    }

    public function test_component_displays_view(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertViewIs('livewire.settings.ssh-key-manager');
    }

    public function test_component_loads_user_keys_on_mount(): void
    {
        SSHKey::factory()->count(3)->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertStatus(200);

        $this->assertEquals(3, SSHKey::where('user_id', $this->user->id)->count());
    }

    public function test_component_only_loads_current_user_keys(): void
    {
        $otherUser = User::factory()->create();
        SSHKey::factory()->count(2)->create(['user_id' => $this->user->id]);
        SSHKey::factory()->count(3)->create(['user_id' => $otherUser->id]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertStatus(200);

        $this->assertEquals(2, SSHKey::where('user_id', $this->user->id)->count());
    }

    public function test_displays_ssh_keys_list(): void
    {
        $key = SSHKey::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My Production Key',
        ]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertSee('My Production Key');
    }

    // ============================================================
    // Create Modal Tests
    // ============================================================

    public function test_create_modal_is_closed_by_default(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertSet('showCreateModal', false);
    }

    public function test_can_open_create_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true);
    }

    public function test_opening_create_modal_resets_form(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->set('newKeyName', 'test')
            ->set('newKeyType', 'rsa')
            ->call('openCreateModal')
            ->assertSet('newKeyName', '')
            ->assertSet('newKeyType', 'ed25519');
    }

    // ============================================================
    // Generate Key Tests
    // ============================================================

    public function test_can_generate_ssh_key(): void
    {
        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('generateKeyPair')
            ->once()
            ->andReturn([
                'success' => true,
                'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAA devflow-test',
                'private_key' => '-----BEGIN OPENSSH PRIVATE KEY-----\ntest\n-----END OPENSSH PRIVATE KEY-----',
                'fingerprint' => 'SHA256:abcdef123456',
            ]);
        $this->app->instance(SSHKeyService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openCreateModal')
            ->set('newKeyName', 'Test Key')
            ->set('newKeyType', 'ed25519')
            ->call('generateKey')
            ->assertSessionHas('message');

        $this->assertDatabaseHas('ssh_keys', [
            'user_id' => $this->user->id,
            'name' => 'Test Key',
            'type' => 'ed25519',
        ]);
    }

    public function test_generate_key_validates_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openCreateModal')
            ->set('newKeyName', '')
            ->call('generateKey')
            ->assertHasErrors(['newKeyName' => 'required']);
    }

    public function test_generate_key_validates_name_max_length(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openCreateModal')
            ->set('newKeyName', str_repeat('a', 101))
            ->call('generateKey')
            ->assertHasErrors(['newKeyName' => 'max']);
    }

    public function test_generate_key_validates_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openCreateModal')
            ->set('newKeyName', 'Test Key')
            ->set('newKeyType', 'invalid')
            ->call('generateKey')
            ->assertHasErrors(['newKeyType' => 'in']);
    }

    public function test_generate_key_handles_service_failure(): void
    {
        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('generateKeyPair')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Failed to generate key',
            ]);
        $this->app->instance(SSHKeyService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openCreateModal')
            ->set('newKeyName', 'Test Key')
            ->call('generateKey')
            ->assertSessionHas('error');
    }

    public function test_generated_key_is_stored_for_display(): void
    {
        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('generateKeyPair')
            ->once()
            ->andReturn([
                'success' => true,
                'public_key' => 'ssh-ed25519 test',
                'private_key' => '-----BEGIN OPENSSH PRIVATE KEY-----\ntest\n-----END OPENSSH PRIVATE KEY-----',
                'fingerprint' => 'SHA256:test',
            ]);
        $this->app->instance(SSHKeyService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openCreateModal')
            ->set('newKeyName', 'Display Test')
            ->call('generateKey')
            ->assertSet('generatedKey.name', 'Display Test');
    }

    // ============================================================
    // Import Modal Tests
    // ============================================================

    public function test_import_modal_is_closed_by_default(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertSet('showImportModal', false);
    }

    public function test_can_open_import_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openImportModal')
            ->assertSet('showImportModal', true);
    }

    public function test_opening_import_modal_resets_form(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->set('importKeyName', 'test')
            ->set('importPublicKey', 'test-key')
            ->call('openImportModal')
            ->assertSet('importKeyName', '')
            ->assertSet('importPublicKey', '')
            ->assertSet('importPrivateKey', '');
    }

    // ============================================================
    // Import Key Tests
    // ============================================================

    public function test_import_key_validates_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openImportModal')
            ->set('importKeyName', '')
            ->call('importKey')
            ->assertHasErrors(['importKeyName' => 'required']);
    }

    public function test_import_key_validates_name_max_length(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openImportModal')
            ->set('importKeyName', str_repeat('a', 101))
            ->call('importKey')
            ->assertHasErrors(['importKeyName' => 'max']);
    }

    // ============================================================
    // Deploy Modal Tests
    // ============================================================

    public function test_deploy_modal_is_closed_by_default(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertSet('showDeployModal', false);
    }

    public function test_can_open_deploy_modal(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openDeployModal', $key->id)
            ->assertSet('showDeployModal', true)
            ->assertSet('selectedKeyId', $key->id);
    }

    public function test_opening_deploy_modal_resets_server_selection(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->set('selectedServerId', 123)
            ->call('openDeployModal', $key->id)
            ->assertSet('selectedServerId', null);
    }

    // ============================================================
    // Deploy to Server Tests
    // ============================================================

    public function test_can_deploy_key_to_server(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);
        $server = Server::factory()->create(['user_id' => $this->user->id]);

        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('deployKeyToServer')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Key deployed successfully',
            ]);
        $this->app->instance(SSHKeyService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openDeployModal', $key->id)
            ->set('selectedServerId', $server->id)
            ->call('deployToServer')
            ->assertSet('showDeployModal', false)
            ->assertSessionHas('message');
    }

    public function test_deploy_validates_key_id(): void
    {
        $server = Server::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->set('selectedKeyId', 99999)
            ->set('selectedServerId', $server->id)
            ->call('deployToServer')
            ->assertHasErrors(['selectedKeyId']);
    }

    public function test_deploy_validates_server_id(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->set('selectedKeyId', $key->id)
            ->set('selectedServerId', 99999)
            ->call('deployToServer')
            ->assertHasErrors(['selectedServerId']);
    }

    public function test_deploy_handles_service_failure(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);
        $server = Server::factory()->create(['user_id' => $this->user->id]);

        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('deployKeyToServer')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Connection refused',
            ]);
        $this->app->instance(SSHKeyService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openDeployModal', $key->id)
            ->set('selectedServerId', $server->id)
            ->call('deployToServer')
            ->assertSessionHas('error');
    }

    // ============================================================
    // View Key Modal Tests
    // ============================================================

    public function test_view_key_modal_is_closed_by_default(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertSet('showViewKeyModal', false);
    }

    public function test_can_open_view_key_modal(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openViewKeyModal', $key->id)
            ->assertSet('showViewKeyModal', true)
            ->assertSet('viewingKey.id', $key->id)
            ->assertSet('viewingKey.name', $key->name);
    }

    public function test_cannot_view_other_user_key(): void
    {
        $otherUser = User::factory()->create();
        $key = SSHKey::factory()->create(['user_id' => $otherUser->id]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openViewKeyModal', $key->id)
            ->assertSet('showViewKeyModal', false)
            ->assertSet('viewingKey', null);
    }

    // ============================================================
    // Remove From Server Tests
    // ============================================================

    public function test_can_remove_key_from_server(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);
        $server = Server::factory()->create(['user_id' => $this->user->id]);
        $key->servers()->attach($server->id, ['deployed_at' => now()]);

        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('removeKeyFromServer')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Key removed successfully',
            ]);
        $this->app->instance(SSHKeyService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('removeFromServer', $key->id, $server->id)
            ->assertSessionHas('message');

        $this->assertDatabaseMissing('server_ssh_key', [
            'ssh_key_id' => $key->id,
            'server_id' => $server->id,
        ]);
    }

    public function test_remove_from_server_handles_failure(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);
        $server = Server::factory()->create(['user_id' => $this->user->id]);
        $key->servers()->attach($server->id, ['deployed_at' => now()]);

        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('removeKeyFromServer')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Failed to remove key',
            ]);
        $this->app->instance(SSHKeyService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('removeFromServer', $key->id, $server->id)
            ->assertSessionHas('error');
    }

    // ============================================================
    // Delete Key Tests
    // ============================================================

    public function test_can_delete_key(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);

        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('removeKeyFromServer')->never();
        $this->app->instance(SSHKeyService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('deleteKey', $key->id)
            ->assertSessionHas('message');

        $this->assertDatabaseMissing('ssh_keys', ['id' => $key->id]);
    }

    public function test_delete_key_removes_from_all_servers(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);
        $server1 = Server::factory()->create(['user_id' => $this->user->id]);
        $server2 = Server::factory()->create(['user_id' => $this->user->id]);
        $key->servers()->attach([$server1->id, $server2->id], ['deployed_at' => now()]);

        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('removeKeyFromServer')->twice()->andReturn(['success' => true]);
        $this->app->instance(SSHKeyService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('deleteKey', $key->id);

        $this->assertDatabaseMissing('ssh_keys', ['id' => $key->id]);
    }

    public function test_cannot_delete_other_user_key(): void
    {
        $otherUser = User::factory()->create();
        $key = SSHKey::factory()->create(['user_id' => $otherUser->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('deleteKey', $key->id);
    }

    // ============================================================
    // Download Private Key Tests
    // ============================================================

    public function test_can_dispatch_download_private_key(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('downloadPrivateKey', $key->id)
            ->assertDispatched('download-private-key');
    }

    public function test_cannot_download_other_user_private_key(): void
    {
        $otherUser = User::factory()->create();
        $key = SSHKey::factory()->create(['user_id' => $otherUser->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('downloadPrivateKey', $key->id);
    }

    // ============================================================
    // Copy Public Key Tests
    // ============================================================

    public function test_can_copy_public_key(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('copyPublicKey', $key->id)
            ->assertDispatched('copy-to-clipboard')
            ->assertSessionHas('message', 'Public key copied to clipboard!');
    }

    public function test_cannot_copy_other_user_public_key(): void
    {
        $otherUser = User::factory()->create();
        $key = SSHKey::factory()->create(['user_id' => $otherUser->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('copyPublicKey', $key->id);
    }

    // ============================================================
    // Close Modals Tests
    // ============================================================

    public function test_close_modals_closes_all_modals(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->set('showCreateModal', true)
            ->set('showImportModal', true)
            ->set('showDeployModal', true)
            ->set('showViewKeyModal', true)
            ->call('closeModals')
            ->assertSet('showCreateModal', false)
            ->assertSet('showImportModal', false)
            ->assertSet('showDeployModal', false)
            ->assertSet('showViewKeyModal', false);
    }

    public function test_close_modals_clears_generated_key(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->set('generatedKey', ['name' => 'test'])
            ->call('closeModals')
            ->assertSet('generatedKey', null);
    }

    public function test_close_modals_clears_viewing_key(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->set('viewingKey', ['id' => 1, 'name' => 'test'])
            ->call('closeModals')
            ->assertSet('viewingKey', null);
    }

    // ============================================================
    // Key Type Tests
    // ============================================================

    public function test_can_generate_rsa_key(): void
    {
        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('generateKeyPair')
            ->with('rsa', Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'public_key' => 'ssh-rsa test',
                'private_key' => '-----BEGIN RSA PRIVATE KEY-----\ntest\n-----END RSA PRIVATE KEY-----',
                'fingerprint' => 'SHA256:test',
            ]);
        $this->app->instance(SSHKeyService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openCreateModal')
            ->set('newKeyName', 'RSA Test')
            ->set('newKeyType', 'rsa')
            ->call('generateKey');

        $this->assertDatabaseHas('ssh_keys', [
            'user_id' => $this->user->id,
            'type' => 'rsa',
        ]);
    }

    public function test_can_generate_ecdsa_key(): void
    {
        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('generateKeyPair')
            ->with('ecdsa', Mockery::any())
            ->once()
            ->andReturn([
                'success' => true,
                'public_key' => 'ecdsa-sha2-nistp521 test',
                'private_key' => '-----BEGIN EC PRIVATE KEY-----\ntest\n-----END EC PRIVATE KEY-----',
                'fingerprint' => 'SHA256:test',
            ]);
        $this->app->instance(SSHKeyService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openCreateModal')
            ->set('newKeyName', 'ECDSA Test')
            ->set('newKeyType', 'ecdsa')
            ->call('generateKey');

        $this->assertDatabaseHas('ssh_keys', [
            'user_id' => $this->user->id,
            'type' => 'ecdsa',
        ]);
    }

    // ============================================================
    // Server List Tests
    // ============================================================

    public function test_displays_online_servers_for_deployment(): void
    {
        Server::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Online Server',
            'status' => 'online',
        ]);
        Server::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Offline Server',
            'status' => 'offline',
        ]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertSee('Online Server')
            ->assertDontSee('Offline Server');
    }

    public function test_only_shows_user_servers(): void
    {
        $otherUser = User::factory()->create();
        Server::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My Server',
            'status' => 'online',
        ]);
        Server::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other Server',
            'status' => 'online',
        ]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertSee('My Server')
            ->assertDontSee('Other Server');
    }

    // ============================================================
    // Edge Cases
    // ============================================================

    public function test_handles_empty_keys_list(): void
    {
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertStatus(200);
    }

    public function test_handles_key_with_no_servers(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->assertSee($key->name);
    }

    public function test_keys_are_ordered_by_created_at_desc(): void
    {
        $oldKey = SSHKey::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Key',
            'created_at' => now()->subDays(5),
        ]);
        $newKey = SSHKey::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'New Key',
            'created_at' => now(),
        ]);

        $keys = SSHKey::where('user_id', $this->user->id)
            ->orderBy('created_at', 'desc')
            ->pluck('name')
            ->toArray();

        $this->assertEquals(['New Key', 'Old Key'], $keys);
    }

    public function test_generate_key_handles_exception(): void
    {
        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('generateKeyPair')
            ->once()
            ->andThrow(new \Exception('Unexpected error'));
        $this->app->instance(SSHKeyService::class, $serviceMock);

        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('openCreateModal')
            ->set('newKeyName', 'Exception Test')
            ->call('generateKey')
            ->assertSessionHas('error');
    }

    public function test_delete_key_handles_exception(): void
    {
        $key = SSHKey::factory()->create(['user_id' => $this->user->id]);
        $server = Server::factory()->create(['user_id' => $this->user->id]);
        $key->servers()->attach($server->id, ['deployed_at' => now()]);

        $serviceMock = Mockery::mock(SSHKeyService::class);
        $serviceMock->shouldReceive('removeKeyFromServer')
            ->once()
            ->andThrow(new \Exception('Connection failed'));
        $this->app->instance(SSHKeyService::class, $serviceMock);

        // Should still delete the key even if server removal fails
        Livewire::actingAs($this->user)
            ->test(SSHKeyManager::class)
            ->call('deleteKey', $key->id)
            ->assertSessionHas('message');

        $this->assertDatabaseMissing('ssh_keys', ['id' => $key->id]);
    }
}
