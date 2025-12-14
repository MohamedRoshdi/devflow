<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\StorageSettings;
use App\Models\Project;
use App\Models\Server;
use App\Models\StorageConfiguration;
use App\Models\User;
use App\Services\Backup\RemoteStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class StorageSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function mockStorageService(bool $testSuccess = true, string $error = 'Connection failed'): void
    {
        $this->mock(RemoteStorageService::class, function (MockInterface $mock) use ($testSuccess, $error): void {
            $mock->shouldReceive('testConnection')->andReturn([
                'success' => $testSuccess,
                'error' => $testSuccess ? null : $error,
                'latency_ms' => 50,
            ]);
        });
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->assertStatus(200);
    }

    public function test_shows_storage_configurations(): void
    {
        StorageConfiguration::factory()->count(3)->create();
        $this->mockStorageService();

        $component = Livewire::actingAs($this->user)
            ->test(StorageSettings::class);

        $configs = $component->viewData('storageConfigs');
        $this->assertCount(3, $configs);
    }

    public function test_configs_ordered_by_default_first(): void
    {
        StorageConfiguration::factory()->create(['is_default' => false, 'name' => 'Zebra']);
        StorageConfiguration::factory()->create(['is_default' => true, 'name' => 'Alpha']);
        $this->mockStorageService();

        $component = Livewire::actingAs($this->user)
            ->test(StorageSettings::class);

        $configs = $component->viewData('storageConfigs');
        $this->assertTrue($configs->first()->is_default);
    }

    public function test_shows_available_projects(): void
    {
        $server = Server::factory()->create();
        Project::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);
        $this->mockStorageService();

        $component = Livewire::actingAs($this->user)
            ->test(StorageSettings::class);

        $projects = $component->viewData('projects');
        $this->assertCount(3, $projects);
    }

    // ==================== CREATE MODAL TESTS ====================

    public function test_can_open_create_modal(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('openCreateModal')
            ->assertSet('showModal', true)
            ->assertSet('editingId', null)
            ->assertSet('activeTab', 's3');
    }

    public function test_create_modal_resets_fields(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->set('name', 'Old Name')
            ->set('driver', 'ftp')
            ->call('openCreateModal')
            ->assertSet('name', '')
            ->assertSet('driver', 's3');
    }

    // ==================== EDIT MODAL TESTS ====================

    public function test_can_open_edit_modal(): void
    {
        $config = StorageConfiguration::factory()->create([
            'name' => 'My S3 Config',
            'driver' => 's3',
            'bucket' => 'my-bucket',
            'credentials' => ['access_key_id' => 'AK123', 'secret_access_key' => 'secret'],
        ]);
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('openEditModal', $config->id)
            ->assertSet('showModal', true)
            ->assertSet('editingId', $config->id)
            ->assertSet('name', 'My S3 Config')
            ->assertSet('driver', 's3')
            ->assertSet('bucket', 'my-bucket')
            ->assertSet('s3_access_key', 'AK123');
    }

    public function test_edit_loads_ftp_credentials(): void
    {
        $config = StorageConfiguration::factory()->create([
            'driver' => 'ftp',
            'credentials' => [
                'host' => 'ftp.example.com',
                'port' => 21,
                'username' => 'ftpuser',
                'password' => 'ftppass',
                'path' => '/uploads',
                'passive' => true,
                'ssl' => true,
            ],
        ]);
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('openEditModal', $config->id)
            ->assertSet('ftp_host', 'ftp.example.com')
            ->assertSet('ftp_port', '21')
            ->assertSet('ftp_username', 'ftpuser')
            ->assertSet('ftp_passive', true)
            ->assertSet('ftp_ssl', true);
    }

    public function test_edit_loads_sftp_credentials(): void
    {
        $config = StorageConfiguration::factory()->create([
            'driver' => 'sftp',
            'credentials' => [
                'host' => 'sftp.example.com',
                'port' => 22,
                'username' => 'sftpuser',
                'password' => 'sftppass',
                'private_key' => 'key-content',
                'path' => '/backup',
            ],
        ]);
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('openEditModal', $config->id)
            ->assertSet('sftp_host', 'sftp.example.com')
            ->assertSet('sftp_port', '22')
            ->assertSet('sftp_username', 'sftpuser')
            ->assertSet('sftp_private_key', 'key-content');
    }

    public function test_edit_loads_gcs_credentials(): void
    {
        $config = StorageConfiguration::factory()->create([
            'driver' => 'gcs',
            'credentials' => [
                'service_account_json' => ['project_id' => 'my-project'],
            ],
        ]);
        $this->mockStorageService();

        $component = Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('openEditModal', $config->id);

        $this->assertNotEmpty($component->get('gcs_service_account'));
    }

    // ==================== SAVE TESTS ====================

    public function test_can_create_s3_config(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->set('name', 'My S3 Storage')
            ->set('driver', 's3')
            ->set('bucket', 'my-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIATEST')
            ->set('s3_secret_key', 'secretkey')
            ->call('save')
            ->assertSet('showModal', false)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'My S3 Storage',
            'driver' => 's3',
            'bucket' => 'my-bucket',
            'region' => 'us-east-1',
        ]);
    }

    public function test_can_create_ftp_config(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->set('name', 'My FTP Storage')
            ->set('driver', 'ftp')
            ->set('ftp_host', 'ftp.example.com')
            ->set('ftp_port', '21')
            ->set('ftp_username', 'user')
            ->set('ftp_password', 'pass')
            ->call('save')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'My FTP Storage',
            'driver' => 'ftp',
        ]);
    }

    public function test_can_update_config(): void
    {
        $config = StorageConfiguration::factory()->create([
            'name' => 'Old Name',
            'driver' => 's3',
        ]);
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('openEditModal', $config->id)
            ->set('name', 'New Name')
            ->call('save')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'updated');
            });

        $this->assertDatabaseHas('storage_configurations', [
            'id' => $config->id,
            'name' => 'New Name',
        ]);
    }

    public function test_save_requires_name(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->set('name', '')
            ->set('driver', 's3')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_save_requires_valid_driver(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->set('name', 'Test')
            ->set('driver', 'invalid')
            ->call('save')
            ->assertHasErrors(['driver']);
    }

    public function test_save_with_encryption(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->set('name', 'Encrypted Storage')
            ->set('driver', 's3')
            ->set('enable_encryption', true)
            ->set('encryption_key', 'my-secret-key')
            ->call('save');

        $config = StorageConfiguration::where('name', 'Encrypted Storage')->first();
        $this->assertNotNull($config);
        $this->assertEquals('my-secret-key', $config->encryption_key);
    }

    public function test_save_without_encryption(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->set('name', 'Plain Storage')
            ->set('driver', 's3')
            ->set('enable_encryption', false)
            ->set('encryption_key', 'ignored-key')
            ->call('save');

        $config = StorageConfiguration::where('name', 'Plain Storage')->first();
        $this->assertNotNull($config);
        $this->assertNull($config->encryption_key);
    }

    public function test_save_handles_exception(): void
    {
        $this->mockStorageService();

        // Create an invalid state that will cause an exception
        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->set('editingId', 99999) // Non-existent ID
            ->set('name', 'Test')
            ->set('driver', 's3')
            ->call('save')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    // ==================== ENCRYPTION KEY TESTS ====================

    public function test_can_generate_encryption_key(): void
    {
        $this->mockStorageService();

        $component = Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->assertSet('encryption_key', '')
            ->call('generateEncryptionKey')
            ->assertSet('enable_encryption', true)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success';
            });

        $key = $component->get('encryption_key');
        $this->assertNotEmpty($key);
        // Base64 of 32 bytes should be ~44 chars
        $this->assertGreaterThan(40, strlen($key));
    }

    // ==================== TEST CONNECTION TESTS ====================

    public function test_can_test_connection_success(): void
    {
        $config = StorageConfiguration::factory()->create();
        $this->mockStorageService(testSuccess: true);

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('testConnection', $config->id)
            ->assertSet('isTesting', false)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'successful');
            });
    }

    public function test_can_test_connection_failure(): void
    {
        $config = StorageConfiguration::factory()->create();
        $this->mockStorageService(testSuccess: false, error: 'Connection refused');

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('testConnection', $config->id)
            ->assertSet('isTesting', false)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    public function test_test_connection_stores_results(): void
    {
        $config = StorageConfiguration::factory()->create();
        $this->mockStorageService(testSuccess: true);

        $component = Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('testConnection', $config->id);

        $results = $component->get('testResults');
        $this->assertArrayHasKey('success', $results);
        $this->assertTrue($results['success']);
    }

    public function test_test_connection_handles_exception(): void
    {
        $config = StorageConfiguration::factory()->create();

        $this->mock(RemoteStorageService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('testConnection')
                ->andThrow(new \Exception('Network error'));
        });

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('testConnection', $config->id)
            ->assertSet('isTesting', false)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    // ==================== SET DEFAULT TESTS ====================

    public function test_can_set_as_default(): void
    {
        $config1 = StorageConfiguration::factory()->create(['is_default' => true]);
        $config2 = StorageConfiguration::factory()->create(['is_default' => false]);
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('setAsDefault', $config2->id)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success';
            });

        $freshConfig1 = $config1->fresh();
        $freshConfig2 = $config2->fresh();
        $this->assertNotNull($freshConfig1);
        $this->assertNotNull($freshConfig2);
        $this->assertFalse($freshConfig1->is_default);
        $this->assertTrue($freshConfig2->is_default);
    }

    public function test_set_default_removes_previous(): void
    {
        StorageConfiguration::factory()->create(['is_default' => true]);
        StorageConfiguration::factory()->create(['is_default' => true]);
        $newDefault = StorageConfiguration::factory()->create(['is_default' => false]);
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('setAsDefault', $newDefault->id);

        $defaultCount = StorageConfiguration::where('is_default', true)->count();
        $this->assertEquals(1, $defaultCount);
    }

    // ==================== DELETE TESTS ====================

    public function test_can_delete_config(): void
    {
        $config = StorageConfiguration::factory()->create(['name' => 'Delete Me']);
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('delete', $config->id)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'deleted');
            });

        $this->assertDatabaseMissing('storage_configurations', ['id' => $config->id]);
    }

    public function test_delete_handles_invalid_id(): void
    {
        $this->mockStorageService();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->call('delete', 99999);
    }

    // ==================== DEFAULT VALUES TESTS ====================

    public function test_default_values(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->assertSet('showModal', false)
            ->assertSet('editingId', null)
            ->assertSet('activeTab', 's3')
            ->assertSet('name', '')
            ->assertSet('driver', 's3')
            ->assertSet('enable_encryption', false)
            ->assertSet('isTesting', false);
    }

    // ==================== DRIVER SPECIFIC TESTS ====================

    public function test_s3_driver_saves_correct_credentials(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->set('name', 'S3 Test')
            ->set('driver', 's3')
            ->set('s3_access_key', 'ACCESS123')
            ->set('s3_secret_key', 'SECRET456')
            ->call('save');

        $config = StorageConfiguration::where('name', 'S3 Test')->first();
        $this->assertNotNull($config);
        $credentials = $config->credentials;
        $this->assertEquals('ACCESS123', $credentials['access_key_id']);
        $this->assertEquals('SECRET456', $credentials['secret_access_key']);
    }

    public function test_ftp_driver_saves_correct_credentials(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->set('name', 'FTP Test')
            ->set('driver', 'ftp')
            ->set('ftp_host', 'ftp.test.com')
            ->set('ftp_port', '2121')
            ->set('ftp_username', 'testuser')
            ->set('ftp_password', 'testpass')
            ->set('ftp_path', '/uploads')
            ->set('ftp_passive', false)
            ->set('ftp_ssl', true)
            ->call('save');

        $config = StorageConfiguration::where('name', 'FTP Test')->first();
        $this->assertNotNull($config);
        $credentials = $config->credentials;
        $this->assertEquals('ftp.test.com', $credentials['host']);
        $this->assertEquals(2121, $credentials['port']);
        $this->assertFalse($credentials['passive']);
        $this->assertTrue($credentials['ssl']);
    }

    public function test_sftp_driver_saves_correct_credentials(): void
    {
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->set('name', 'SFTP Test')
            ->set('driver', 'sftp')
            ->set('sftp_host', 'sftp.test.com')
            ->set('sftp_port', '2222')
            ->set('sftp_username', 'sftpuser')
            ->set('sftp_private_key', '-----BEGIN RSA PRIVATE KEY-----')
            ->set('sftp_path', '/backups')
            ->call('save');

        $config = StorageConfiguration::where('name', 'SFTP Test')->first();
        $this->assertNotNull($config);
        $credentials = $config->credentials;
        $this->assertEquals('sftp.test.com', $credentials['host']);
        $this->assertEquals(2222, $credentials['port']);
        $this->assertEquals('-----BEGIN RSA PRIVATE KEY-----', $credentials['private_key']);
    }

    // ==================== PROJECT ASSOCIATION TESTS ====================

    public function test_can_associate_with_project(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);
        $this->mockStorageService();

        Livewire::actingAs($this->user)
            ->test(StorageSettings::class)
            ->set('name', 'Project Storage')
            ->set('driver', 's3')
            ->set('project_id', $project->id)
            ->call('save');

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'Project Storage',
            'project_id' => $project->id,
        ]);
    }

    public function test_configs_include_project_relation(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);
        StorageConfiguration::factory()->create(['project_id' => $project->id]);
        $this->mockStorageService();

        $component = Livewire::actingAs($this->user)
            ->test(StorageSettings::class);

        $configs = $component->viewData('storageConfigs');
        $this->assertNotNull($configs->first()->project);
    }
}
