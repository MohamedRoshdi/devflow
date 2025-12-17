<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use App\Livewire\Settings\StorageSettings;
use App\Models\Project;
use App\Models\StorageConfiguration;
use App\Models\User;
use App\Services\Backup\RemoteStorageService;

use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Comprehensive unit tests for the StorageSettings Livewire component
 *
 * Tests cover:
 * - Component rendering and authentication
 * - Storage driver selection (local, s3, gcs, ftp, sftp)
 * - S3 configuration (bucket, key, secret, region, endpoint)
 * - GCS configuration (service account JSON)
 * - FTP configuration (host, port, credentials, passive, SSL)
 * - SFTP configuration (host, port, credentials, private key)
 * - Connection testing for all drivers
 * - Validation for each driver's required fields
 * - Save settings functionality (create and update)
 * - Set as default functionality
 * - Encryption key generation
 * - Delete configuration
 * - Error handling for invalid credentials
 * - Modal operations (open/close)
 * */
#[CoversClass(\App\Livewire\Settings\StorageSettings::class)]
class StorageSettingsTest extends TestCase
{
    

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->actingAs($this->user);
    }

    // ==========================================
    // Component Rendering Tests
    // ==========================================

    #[Test]
    public function storage_settings_component_renders_for_authenticated_user(): void
    {
        Livewire::test(StorageSettings::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.settings.storage-settings');
    }

    #[Test]
    public function storage_settings_component_initializes_with_default_values(): void
    {
        $component = Livewire::test(StorageSettings::class);

        $component
            ->assertSet('showModal', false)
            ->assertSet('editingId', null)
            ->assertSet('activeTab', 's3')
            ->assertSet('driver', 's3')
            ->assertSet('name', '')
            ->assertSet('bucket', '')
            ->assertSet('region', '')
            ->assertSet('endpoint', '')
            ->assertSet('path_prefix', '')
            ->assertSet('enable_encryption', false)
            ->assertSet('encryption_key', '')
            ->assertSet('isTesting', false);
    }

    // ==========================================
    // Storage Configuration Listing Tests
    // ==========================================

    #[Test]
    public function storage_configs_computed_property_returns_configurations(): void
    {
        StorageConfiguration::factory()->count(3)->create();
        StorageConfiguration::factory()->default()->create(['name' => 'Default Storage']);

        $component = Livewire::test(StorageSettings::class);

        $configs = $component->get('storageConfigs');

        $this->assertCount(4, $configs);
        $this->assertEquals('Default Storage', $configs->first()->name);
    }

    #[Test]
    public function storage_configs_ordered_by_default_first_then_name(): void
    {
        StorageConfiguration::factory()->create(['name' => 'Zebra Storage', 'is_default' => false]);
        StorageConfiguration::factory()->create(['name' => 'Alpha Storage', 'is_default' => false]);
        StorageConfiguration::factory()->create(['name' => 'Default Storage', 'is_default' => true]);

        $component = Livewire::test(StorageSettings::class);

        $configs = $component->get('storageConfigs');

        $this->assertEquals('Default Storage', $configs->first()->name);
        $this->assertEquals('Alpha Storage', $configs->skip(1)->first()->name);
    }

    #[Test]
    public function projects_computed_property_returns_ordered_projects(): void
    {
        Project::factory()->create(['name' => 'Zebra Project']);
        Project::factory()->create(['name' => 'Alpha Project']);

        $component = Livewire::test(StorageSettings::class);

        $projects = $component->get('projects');

        $this->assertGreaterThanOrEqual(2, $projects->count());
        $this->assertEquals('Alpha Project', $projects->where('name', 'Alpha Project')->first()->name);
    }

    // ==========================================
    // Modal Operations Tests
    // ==========================================

    #[Test]
    public function open_create_modal_resets_all_fields(): void
    {
        $component = Livewire::test(StorageSettings::class)
            ->set('name', 'Old Name')
            ->set('bucket', 'old-bucket')
            ->set('s3_access_key', 'old-key')
            ->call('openCreateModal');

        $component
            ->assertSet('showModal', true)
            ->assertSet('editingId', null)
            ->assertSet('name', '')
            ->assertSet('bucket', '')
            ->assertSet('s3_access_key', '')
            ->assertSet('activeTab', 's3');
    }

    #[Test]
    public function open_edit_modal_loads_s3_configuration(): void
    {
        $config = StorageConfiguration::factory()->s3()->create([
            'name' => 'Test S3',
            'bucket' => 'test-bucket',
            'region' => 'us-west-2',
            'endpoint' => 'https://s3.custom.com',
            'path_prefix' => 'backups/',
        ]);

        $component = Livewire::test(StorageSettings::class)
            ->call('openEditModal', $config->id);

        $component
            ->assertSet('showModal', true)
            ->assertSet('editingId', $config->id)
            ->assertSet('name', 'Test S3')
            ->assertSet('driver', 's3')
            ->assertSet('activeTab', 's3')
            ->assertSet('bucket', 'test-bucket')
            ->assertSet('region', 'us-west-2')
            ->assertSet('endpoint', 'https://s3.custom.com')
            ->assertSet('path_prefix', 'backups/')
            ->assertSet('s3_access_key', function ($value) {
                return !empty($value);
            })
            ->assertSet('s3_secret_key', function ($value) {
                return !empty($value);
            });
    }

    #[Test]
    public function open_edit_modal_loads_gcs_configuration(): void
    {
        $config = StorageConfiguration::factory()->gcs()->create([
            'name' => 'Test GCS',
            'bucket' => 'gcs-bucket',
        ]);

        $component = Livewire::test(StorageSettings::class)
            ->call('openEditModal', $config->id);

        $component
            ->assertSet('showModal', true)
            ->assertSet('driver', 'gcs')
            ->assertSet('activeTab', 'gcs')
            ->assertSet('bucket', 'gcs-bucket')
            ->assertSet('gcs_service_account', function ($value) {
                return !empty($value);
            });
    }

    #[Test]
    public function open_edit_modal_loads_ftp_configuration(): void
    {
        $config = StorageConfiguration::factory()->ftp()->create([
            'name' => 'Test FTP',
        ]);

        $component = Livewire::test(StorageSettings::class)
            ->call('openEditModal', $config->id);

        $component
            ->assertSet('showModal', true)
            ->assertSet('driver', 'ftp')
            ->assertSet('activeTab', 'ftp')
            ->assertSet('ftp_host', function ($value) {
                return !empty($value);
            })
            ->assertSet('ftp_port', '21')
            ->assertSet('ftp_passive', true)
            ->assertSet('ftp_ssl', false);
    }

    #[Test]
    public function open_edit_modal_loads_sftp_configuration(): void
    {
        $config = StorageConfiguration::factory()->sftp()->create([
            'name' => 'Test SFTP',
        ]);

        $component = Livewire::test(StorageSettings::class)
            ->call('openEditModal', $config->id);

        $component
            ->assertSet('showModal', true)
            ->assertSet('driver', 'sftp')
            ->assertSet('activeTab', 'sftp')
            ->assertSet('sftp_host', function ($value) {
                return !empty($value);
            })
            ->assertSet('sftp_port', '22');
    }

    #[Test]
    public function open_edit_modal_loads_encryption_settings(): void
    {
        $config = StorageConfiguration::factory()->withEncryption()->s3()->create();

        $component = Livewire::test(StorageSettings::class)
            ->call('openEditModal', $config->id);

        $component
            ->assertSet('enable_encryption', true)
            ->assertSet('encryption_key', function ($value) {
                return !empty($value);
            });
    }

    // ==========================================
    // S3 Configuration Tests
    // ==========================================

    #[Test]
    public function can_create_s3_storage_configuration(): void
    {
        $component = Livewire::test(StorageSettings::class)
            ->set('name', 'AWS S3 Production')
            ->set('driver', 's3')
            ->set('bucket', 'my-production-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save');

        $component->assertDispatched('notification');

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'AWS S3 Production',
            'driver' => 's3',
            'bucket' => 'my-production-bucket',
            'region' => 'us-east-1',
            'status' => 'active',
        ]);

        $config = StorageConfiguration::where('name', 'AWS S3 Production')->first();
        $this->assertNotNull($config);
        $this->assertEquals('AKIAIOSFODNN7EXAMPLE', $config->credentials['access_key_id']);
        $this->assertEquals('wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY', $config->credentials['secret_access_key']);
    }

    #[Test]
    public function can_update_s3_storage_configuration(): void
    {
        $config = StorageConfiguration::factory()->s3()->create([
            'name' => 'Old S3 Name',
            'bucket' => 'old-bucket',
        ]);

        $component = Livewire::test(StorageSettings::class)
            ->call('openEditModal', $config->id)
            ->set('name', 'Updated S3 Name')
            ->set('bucket', 'updated-bucket')
            ->set('region', 'eu-west-1')
            ->call('save');

        $component->assertDispatched('notification');

        $this->assertDatabaseHas('storage_configurations', [
            'id' => $config->id,
            'name' => 'Updated S3 Name',
            'bucket' => 'updated-bucket',
            'region' => 'eu-west-1',
        ]);
    }

    #[Test]
    public function s3_configuration_validates_required_fields(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('driver', 's3')
            ->set('bucket', '')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    #[Test]
    public function s3_configuration_accepts_custom_endpoint(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'MinIO Storage')
            ->set('driver', 's3')
            ->set('bucket', 'minio-bucket')
            ->set('region', 'us-east-1')
            ->set('endpoint', 'https://minio.example.com')
            ->set('s3_access_key', 'minioadmin')
            ->set('s3_secret_key', 'minioadmin')
            ->call('save');

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'MinIO Storage',
            'endpoint' => 'https://minio.example.com',
        ]);
    }

    #[Test]
    public function s3_configuration_stores_path_prefix(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'S3 with Prefix')
            ->set('driver', 's3')
            ->set('bucket', 'shared-bucket')
            ->set('region', 'us-east-1')
            ->set('path_prefix', 'devflow/backups')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save');

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'S3 with Prefix',
            'path_prefix' => 'devflow/backups',
        ]);
    }

    // ==========================================
    // GCS Configuration Tests
    // ==========================================

    #[Test]
    public function can_create_gcs_storage_configuration(): void
    {
        $serviceAccountJson = json_encode([
            'type' => 'service_account',
            'project_id' => 'test-project',
            'private_key_id' => 'key-id',
            'private_key' => '-----BEGIN PRIVATE KEY-----\nMOCK\n-----END PRIVATE KEY-----',
            'client_email' => 'test@test-project.iam.gserviceaccount.com',
            'client_id' => '123456789',
        ]);

        $component = Livewire::test(StorageSettings::class)
            ->set('name', 'Google Cloud Storage')
            ->set('driver', 'gcs')
            ->set('bucket', 'gcs-backup-bucket')
            ->set('gcs_service_account', $serviceAccountJson)
            ->call('save');

        $component->assertDispatched('notification');

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'Google Cloud Storage',
            'driver' => 'gcs',
            'bucket' => 'gcs-backup-bucket',
        ]);

        $config = StorageConfiguration::where('name', 'Google Cloud Storage')->first();
        $this->assertNotNull($config);
        $this->assertIsArray($config->credentials['service_account_json']);
        $this->assertEquals('test-project', $config->credentials['service_account_json']['project_id']);
    }

    #[Test]
    public function gcs_configuration_validates_required_fields(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('driver', 'gcs')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    // ==========================================
    // FTP Configuration Tests
    // ==========================================

    #[Test]
    public function can_create_ftp_storage_configuration(): void
    {
        $component = Livewire::test(StorageSettings::class)
            ->set('name', 'FTP Backup Server')
            ->set('driver', 'ftp')
            ->set('ftp_host', 'ftp.example.com')
            ->set('ftp_port', '21')
            ->set('ftp_username', 'ftpuser')
            ->set('ftp_password', 'ftppass123')
            ->set('ftp_path', '/backups')
            ->set('ftp_passive', true)
            ->set('ftp_ssl', false)
            ->call('save');

        $component->assertDispatched('notification');

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'FTP Backup Server',
            'driver' => 'ftp',
        ]);

        $config = StorageConfiguration::where('name', 'FTP Backup Server')->first();
        $this->assertNotNull($config);
        $this->assertEquals('ftp.example.com', $config->credentials['host']);
        $this->assertEquals(21, $config->credentials['port']);
        $this->assertEquals('ftpuser', $config->credentials['username']);
        $this->assertEquals('ftppass123', $config->credentials['password']);
        $this->assertEquals('/backups', $config->credentials['path']);
        $this->assertTrue($config->credentials['passive']);
        $this->assertFalse($config->credentials['ssl']);
    }

    #[Test]
    public function ftp_configuration_with_ssl_enabled(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Secure FTP')
            ->set('driver', 'ftp')
            ->set('ftp_host', 'ftp.secure.com')
            ->set('ftp_port', '990')
            ->set('ftp_username', 'secureuser')
            ->set('ftp_password', 'securepass')
            ->set('ftp_path', '/')
            ->set('ftp_passive', false)
            ->set('ftp_ssl', true)
            ->call('save');

        $config = StorageConfiguration::where('name', 'Secure FTP')->first();
        $this->assertTrue($config->credentials['ssl']);
        $this->assertFalse($config->credentials['passive']);
    }

    // ==========================================
    // SFTP Configuration Tests
    // ==========================================

    #[Test]
    public function can_create_sftp_storage_configuration_with_password(): void
    {
        $component = Livewire::test(StorageSettings::class)
            ->set('name', 'SFTP Server')
            ->set('driver', 'sftp')
            ->set('sftp_host', 'sftp.example.com')
            ->set('sftp_port', '22')
            ->set('sftp_username', 'sftpuser')
            ->set('sftp_password', 'sftppass123')
            ->set('sftp_path', '/var/backups')
            ->call('save');

        $component->assertDispatched('notification');

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'SFTP Server',
            'driver' => 'sftp',
        ]);

        $config = StorageConfiguration::where('name', 'SFTP Server')->first();
        $this->assertNotNull($config);
        $this->assertEquals('sftp.example.com', $config->credentials['host']);
        $this->assertEquals(22, $config->credentials['port']);
        $this->assertEquals('sftpuser', $config->credentials['username']);
        $this->assertEquals('sftppass123', $config->credentials['password']);
        $this->assertEquals('/var/backups', $config->credentials['path']);
    }

    #[Test]
    public function can_create_sftp_storage_configuration_with_private_key(): void
    {
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\nMOCK_PRIVATE_KEY\n-----END RSA PRIVATE KEY-----";

        $component = Livewire::test(StorageSettings::class)
            ->set('name', 'SFTP with Key')
            ->set('driver', 'sftp')
            ->set('sftp_host', 'sftp.secure.com')
            ->set('sftp_port', '2222')
            ->set('sftp_username', 'keyuser')
            ->set('sftp_password', '')
            ->set('sftp_private_key', $privateKey)
            ->set('sftp_passphrase', 'keypassphrase')
            ->set('sftp_path', '/home/keyuser/backups')
            ->call('save');

        $component->assertDispatched('notification');

        $config = StorageConfiguration::where('name', 'SFTP with Key')->first();
        $this->assertNotNull($config);
        $this->assertEquals($privateKey, $config->credentials['private_key']);
        $this->assertEquals('keypassphrase', $config->credentials['passphrase']);
        $this->assertNull($config->credentials['password']);
    }

    // ==========================================
    // Encryption Tests
    // ==========================================

    #[Test]
    public function can_generate_encryption_key(): void
    {
        $component = Livewire::test(StorageSettings::class)
            ->assertSet('enable_encryption', false)
            ->assertSet('encryption_key', '')
            ->call('generateEncryptionKey');

        $component
            ->assertSet('enable_encryption', true)
            ->assertSet('encryption_key', function ($value) {
                return !empty($value) && strlen($value) > 40;
            })
            ->assertDispatched('notification');
    }

    #[Test]
    public function encryption_key_is_stored_when_enabled(): void
    {
        $encryptionKey = base64_encode(random_bytes(32));

        Livewire::test(StorageSettings::class)
            ->set('name', 'Encrypted Storage')
            ->set('driver', 's3')
            ->set('bucket', 'encrypted-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->set('enable_encryption', true)
            ->set('encryption_key', $encryptionKey)
            ->call('save');

        $config = StorageConfiguration::where('name', 'Encrypted Storage')->first();
        $this->assertNotNull($config);
        $this->assertEquals($encryptionKey, $config->encryption_key);
    }

    #[Test]
    public function encryption_key_is_not_stored_when_disabled(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Unencrypted Storage')
            ->set('driver', 's3')
            ->set('bucket', 'unencrypted-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->set('enable_encryption', false)
            ->set('encryption_key', 'should-not-be-saved')
            ->call('save');

        $config = StorageConfiguration::where('name', 'Unencrypted Storage')->first();
        $this->assertNotNull($config);
        $this->assertNull($config->encryption_key);
    }

    // ==========================================
    // Connection Testing Tests
    // ==========================================

    #[Test]
    public function test_connection_calls_remote_storage_service(): void
    {
        $config = StorageConfiguration::factory()->s3()->create();

        $mockService = Mockery::mock(RemoteStorageService::class);
        $mockService->shouldReceive('testConnection')
            ->once()
            ->with(Mockery::type(StorageConfiguration::class))
            ->andReturn([
                'success' => true,
                'tests' => ['list' => true, 'write' => true, 'read' => true, 'delete' => true],
                'timing' => ['list' => '45ms', 'write' => '120ms', 'read' => '80ms', 'delete' => '60ms'],
                'error' => null,
            ]);

        $this->app->instance(RemoteStorageService::class, $mockService);

        $component = Livewire::test(StorageSettings::class)
            ->call('testConnection', $config->id);

        $component
            ->assertSet('isTesting', false)
            ->assertSet('testResults', function ($results) {
                return $results['success'] === true;
            })
            ->assertDispatched('notification');
    }

    #[Test]
    public function test_connection_handles_failure(): void
    {
        $config = StorageConfiguration::factory()->s3()->create();

        $mockService = Mockery::mock(RemoteStorageService::class);
        $mockService->shouldReceive('testConnection')
            ->once()
            ->andReturn([
                'success' => false,
                'tests' => ['list' => false],
                'timing' => [],
                'error' => 'Connection timeout',
            ]);

        $this->app->instance(RemoteStorageService::class, $mockService);

        $component = Livewire::test(StorageSettings::class)
            ->call('testConnection', $config->id);

        $component
            ->assertSet('testResults', function ($results) {
                return $results['success'] === false && $results['error'] === 'Connection timeout';
            })
            ->assertDispatched('notification');
    }

    #[Test]
    public function test_connection_sets_testing_flag(): void
    {
        $config = StorageConfiguration::factory()->s3()->create();

        $mockService = Mockery::mock(RemoteStorageService::class);
        $mockService->shouldReceive('testConnection')
            ->andReturn([
                'success' => true,
                'tests' => [],
                'timing' => [],
                'error' => null,
            ]);

        $this->app->instance(RemoteStorageService::class, $mockService);

        Livewire::test(StorageSettings::class)
            ->assertSet('isTesting', false)
            ->call('testConnection', $config->id)
            ->assertSet('isTesting', false);
    }

    #[Test]
    public function test_connection_handles_exception(): void
    {
        $config = StorageConfiguration::factory()->s3()->create();

        $mockService = Mockery::mock(RemoteStorageService::class);
        $mockService->shouldReceive('testConnection')
            ->once()
            ->andThrow(new \Exception('Network error'));

        $this->app->instance(RemoteStorageService::class, $mockService);

        $component = Livewire::test(StorageSettings::class)
            ->call('testConnection', $config->id);

        $component
            ->assertSet('isTesting', false)
            ->assertDispatched('notification');
    }

    // ==========================================
    // Set As Default Tests
    // ==========================================

    #[Test]
    public function can_set_configuration_as_default(): void
    {
        $currentDefault = StorageConfiguration::factory()->default()->create();
        $newDefault = StorageConfiguration::factory()->create(['is_default' => false]);

        Livewire::test(StorageSettings::class)
            ->call('setAsDefault', $newDefault->id)
            ->assertDispatched('notification');

        $this->assertDatabaseHas('storage_configurations', [
            'id' => $newDefault->id,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('storage_configurations', [
            'id' => $currentDefault->id,
            'is_default' => false,
        ]);
    }

    #[Test]
    public function only_one_configuration_can_be_default(): void
    {
        $config1 = StorageConfiguration::factory()->default()->create();
        $config2 = StorageConfiguration::factory()->create();
        $config3 = StorageConfiguration::factory()->create();

        Livewire::test(StorageSettings::class)
            ->call('setAsDefault', $config3->id);

        $defaultCount = StorageConfiguration::where('is_default', true)->count();
        $this->assertEquals(1, $defaultCount);

        $this->assertTrue($config3->fresh()->is_default);
        $this->assertFalse($config1->fresh()->is_default);
        $this->assertFalse($config2->fresh()->is_default);
    }

    #[Test]
    public function set_as_default_handles_nonexistent_configuration(): void
    {
        $component = Livewire::test(StorageSettings::class)
            ->call('setAsDefault', 99999);

        $component->assertDispatched('notification');
    }

    // ==========================================
    // Delete Configuration Tests
    // ==========================================

    #[Test]
    public function can_delete_storage_configuration(): void
    {
        $config = StorageConfiguration::factory()->create(['name' => 'To Be Deleted']);

        Livewire::test(StorageSettings::class)
            ->call('delete', $config->id)
            ->assertDispatched('notification');

        $this->assertDatabaseMissing('storage_configurations', [
            'id' => $config->id,
        ]);
    }

    #[Test]
    public function delete_handles_nonexistent_configuration(): void
    {
        $component = Livewire::test(StorageSettings::class)
            ->call('delete', 99999);

        $component->assertDispatched('notification');
    }

    // ==========================================
    // Project Association Tests
    // ==========================================

    #[Test]
    public function can_create_storage_configuration_for_specific_project(): void
    {
        $project = Project::factory()->create(['name' => 'Test Project']);

        Livewire::test(StorageSettings::class)
            ->set('name', 'Project Storage')
            ->set('driver', 's3')
            ->set('project_id', $project->id)
            ->set('bucket', 'project-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save');

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'Project Storage',
            'project_id' => $project->id,
        ]);
    }

    #[Test]
    public function can_create_global_storage_configuration(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Global Storage')
            ->set('driver', 's3')
            ->set('project_id', null)
            ->set('bucket', 'global-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save');

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'Global Storage',
            'project_id' => null,
        ]);
    }

    #[Test]
    public function project_id_validates_existence(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Test Storage')
            ->set('driver', 's3')
            ->set('project_id', 99999)
            ->set('bucket', 'test-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save')
            ->assertHasErrors(['project_id']);
    }

    // ==========================================
    // Validation Tests
    // ==========================================

    #[Test]
    public function name_is_required(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', '')
            ->set('driver', 's3')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    #[Test]
    public function driver_is_required(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Test Storage')
            ->set('driver', '')
            ->call('save')
            ->assertHasErrors(['driver']);
    }

    #[Test]
    public function driver_must_be_valid_option(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Test Storage')
            ->set('driver', 'invalid_driver')
            ->call('save')
            ->assertHasErrors(['driver']);
    }

    #[Test]
    public function name_has_max_length_validation(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', str_repeat('a', 256))
            ->set('driver', 's3')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    #[Test]
    public function bucket_has_max_length_validation(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Test Storage')
            ->set('driver', 's3')
            ->set('bucket', str_repeat('a', 256))
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save')
            ->assertHasErrors(['bucket']);
    }

    #[Test]
    public function region_has_max_length_validation(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Test Storage')
            ->set('driver', 's3')
            ->set('bucket', 'test-bucket')
            ->set('region', str_repeat('a', 256))
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save')
            ->assertHasErrors(['region']);
    }

    #[Test]
    public function endpoint_has_max_length_validation(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Test Storage')
            ->set('driver', 's3')
            ->set('bucket', 'test-bucket')
            ->set('region', 'us-east-1')
            ->set('endpoint', str_repeat('a', 501))
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save')
            ->assertHasErrors(['endpoint']);
    }

    #[Test]
    public function path_prefix_has_max_length_validation(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Test Storage')
            ->set('driver', 's3')
            ->set('bucket', 'test-bucket')
            ->set('region', 'us-east-1')
            ->set('path_prefix', str_repeat('a', 501))
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save')
            ->assertHasErrors(['path_prefix']);
    }

    // ==========================================
    // Save Functionality Tests
    // ==========================================

    #[Test]
    public function save_closes_modal_on_success(): void
    {
        $component = Livewire::test(StorageSettings::class)
            ->set('showModal', true)
            ->set('name', 'New Storage')
            ->set('driver', 's3')
            ->set('bucket', 'new-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save');

        $component->assertSet('showModal', false);
    }

    #[Test]
    public function save_resets_form_on_success(): void
    {
        $component = Livewire::test(StorageSettings::class)
            ->set('name', 'Test Storage')
            ->set('driver', 's3')
            ->set('bucket', 'test-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save');

        $component
            ->assertSet('name', '')
            ->assertSet('bucket', '')
            ->assertSet('region', '')
            ->assertSet('s3_access_key', '')
            ->assertSet('s3_secret_key', '');
    }

    #[Test]
    public function save_refreshes_storage_configs_list(): void
    {
        $initialCount = StorageConfiguration::count();

        Livewire::test(StorageSettings::class)
            ->set('name', 'New Storage')
            ->set('driver', 's3')
            ->set('bucket', 'new-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save');

        $this->assertEquals($initialCount + 1, StorageConfiguration::count());
    }

    #[Test]
    public function save_dispatches_correct_notification_for_create(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'New Storage')
            ->set('driver', 's3')
            ->set('bucket', 'new-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save')
            ->assertDispatched('notification', function ($name, array $params) {
                return $params[0]['type'] === 'success'
                    && str_contains($params[0]['message'], 'created');
            });
    }

    #[Test]
    public function save_dispatches_correct_notification_for_update(): void
    {
        $config = StorageConfiguration::factory()->s3()->create();

        Livewire::test(StorageSettings::class)
            ->call('openEditModal', $config->id)
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertDispatched('notification', function ($name, array $params) {
                return $params[0]['type'] === 'success'
                    && str_contains($params[0]['message'], 'updated');
            });
    }

    #[Test]
    public function save_handles_exception_gracefully(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Test Storage')
            ->set('driver', 'invalid_driver')
            ->call('save')
            ->assertHasErrors();
    }

    // ==========================================
    // Credentials Storage Tests
    // ==========================================

    #[Test]
    public function credentials_are_encrypted_in_database(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Secure Storage')
            ->set('driver', 's3')
            ->set('bucket', 'secure-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save');

        $config = StorageConfiguration::where('name', 'Secure Storage')->first();

        $rawCredentials = $config->getAttributes()['credentials'];
        $this->assertNotEquals('AKIAIOSFODNN7EXAMPLE', $rawCredentials);

        $decryptedCredentials = $config->credentials;
        $this->assertEquals('AKIAIOSFODNN7EXAMPLE', $decryptedCredentials['access_key_id']);
    }

    #[Test]
    public function empty_optional_fields_are_stored_as_null(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Basic Storage')
            ->set('driver', 's3')
            ->set('bucket', 'basic-bucket')
            ->set('region', 'us-east-1')
            ->set('endpoint', '')
            ->set('path_prefix', '')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save');

        $config = StorageConfiguration::where('name', 'Basic Storage')->first();
        $this->assertNull($config->endpoint);
        $this->assertNull($config->path_prefix);
    }

    // ==========================================
    // Active Tab Tests
    // ==========================================

    #[Test]
    public function active_tab_changes_based_on_driver(): void
    {
        $s3Config = StorageConfiguration::factory()->s3()->create();
        $gcsConfig = StorageConfiguration::factory()->gcs()->create();
        $ftpConfig = StorageConfiguration::factory()->ftp()->create();

        Livewire::test(StorageSettings::class)
            ->call('openEditModal', $s3Config->id)
            ->assertSet('activeTab', 's3')
            ->call('openEditModal', $gcsConfig->id)
            ->assertSet('activeTab', 'gcs')
            ->call('openEditModal', $ftpConfig->id)
            ->assertSet('activeTab', 'ftp');
    }

    // ==========================================
    // Edge Cases and Error Handling Tests
    // ==========================================

    #[Test]
    public function handles_malformed_json_in_gcs_service_account(): void
    {
        $config = StorageConfiguration::factory()->gcs()->create();

        Livewire::test(StorageSettings::class)
            ->call('openEditModal', $config->id)
            ->set('gcs_service_account', 'invalid json {')
            ->call('save');

        $updatedConfig = StorageConfiguration::find($config->id);
        $this->assertNotNull($updatedConfig);
    }

    #[Test]
    public function handles_zero_port_numbers(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Custom Port FTP')
            ->set('driver', 'ftp')
            ->set('ftp_host', 'ftp.example.com')
            ->set('ftp_port', '0')
            ->set('ftp_username', 'user')
            ->set('ftp_password', 'pass')
            ->call('save');

        $config = StorageConfiguration::where('name', 'Custom Port FTP')->first();
        $this->assertEquals(0, $config->credentials['port']);
    }

    #[Test]
    public function handles_very_large_port_numbers(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'High Port SFTP')
            ->set('driver', 'sftp')
            ->set('sftp_host', 'sftp.example.com')
            ->set('sftp_port', '65535')
            ->set('sftp_username', 'user')
            ->set('sftp_password', 'pass')
            ->call('save');

        $config = StorageConfiguration::where('name', 'High Port SFTP')->first();
        $this->assertEquals(65535, $config->credentials['port']);
    }

    #[Test]
    public function local_driver_stores_minimal_configuration(): void
    {
        Livewire::test(StorageSettings::class)
            ->set('name', 'Local Storage')
            ->set('driver', 'local')
            ->call('save');

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'Local Storage',
            'driver' => 'local',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
