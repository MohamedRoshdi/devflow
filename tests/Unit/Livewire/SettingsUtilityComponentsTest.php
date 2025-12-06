<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;

use App\Livewire\Logs\LogSourceManager;
use App\Livewire\Logs\LogViewer;
use App\Livewire\Logs\NotificationLogs;
use App\Livewire\Logs\WebhookLogs;
use App\Livewire\Settings\ApiTokenManager;
use App\Livewire\Settings\DefaultSetupPreferences;
use App\Livewire\Settings\GitHubSettings;
use App\Livewire\Settings\HealthCheckManager;
use App\Livewire\Settings\QueueMonitor;
use App\Livewire\Settings\SSHKeyManager;
use App\Livewire\Settings\StorageSettings;
use App\Livewire\Settings\SystemStatus;
use App\Livewire\Teams\TeamList;
use App\Livewire\Teams\TeamSettings;
use App\Livewire\Teams\TeamSwitcher;
use App\Livewire\Users\UserList;
use App\Models\ApiToken;
use App\Models\GitHubConnection;
use App\Models\GitHubRepository;
use App\Models\HealthCheck;
use App\Models\LogEntry;
use App\Models\LogSource;
use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Project;
use App\Models\Server;
use App\Models\SSHKey;
use App\Models\StorageConfiguration;
use App\Models\Team;
use App\Models\User;
use App\Models\UserSettings;
use App\Models\WebhookDelivery;
use App\Services\GitHubService;
use App\Services\HealthCheckService;
use App\Services\NotificationService;
use App\Services\QueueMonitorService;
use App\Services\SSHKeyService;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsUtilityComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
    }

    // ==================== API Token Manager Tests ====================

    /** @test */
    public function api_token_manager_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ApiTokenManager::class)
            ->assertStatus(200)
            ->assertSet('showCreateModal', false)
            ->assertSet('showTokenModal', false);
    }

    /** @test */
    public function api_token_manager_loads_tokens(): void
    {
        $user = User::factory()->create();
        ApiToken::factory()->count(3)->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(ApiTokenManager::class)
            ->assertCount('tokens', 3);
    }

    /** @test */
    public function api_token_manager_opens_create_modal(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ApiTokenManager::class)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSet('newTokenName', '')
            ->assertSet('newTokenAbilities', []);
    }

    /** @test */
    public function api_token_manager_validates_token_creation(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', '')
            ->set('newTokenAbilities', [])
            ->call('createToken')
            ->assertHasErrors(['newTokenName', 'newTokenAbilities']);
    }

    /** @test */
    public function api_token_manager_creates_token(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'Test Token')
            ->set('newTokenAbilities', ['projects:read', 'projects:write'])
            ->set('newTokenExpiration', 'never')
            ->call('createToken')
            ->assertSet('showCreateModal', false)
            ->assertSet('showTokenModal', true)
            ->assertNotNull('createdTokenPlain')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'name' => 'Test Token',
        ]);
    }

    /** @test */
    public function api_token_manager_creates_token_with_expiration(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'Expiring Token')
            ->set('newTokenAbilities', ['projects:read'])
            ->set('newTokenExpiration', '30')
            ->call('createToken');

        $token = ApiToken::where('user_id', $user->id)->first();
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->greaterThan(now()));
    }

    /** @test */
    public function api_token_manager_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = ApiToken::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(ApiTokenManager::class)
            ->call('revokeToken', $token->id)
            ->assertDispatched('notification');

        $this->assertDatabaseMissing('api_tokens', ['id' => $token->id]);
    }

    /** @test */
    public function api_token_manager_regenerates_token(): void
    {
        $user = User::factory()->create();
        $token = ApiToken::factory()->create(['user_id' => $user->id]);
        $oldToken = $token->token;

        Livewire::actingAs($user)
            ->test(ApiTokenManager::class)
            ->call('regenerateToken', $token->id)
            ->assertSet('showTokenModal', true)
            ->assertNotNull('createdTokenPlain')
            ->assertDispatched('notification');

        $token->refresh();
        $this->assertNotEquals($oldToken, $token->token);
    }

    // ==================== SSH Key Manager Tests ====================

    /** @test */
    public function ssh_key_manager_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SSHKeyManager::class)
            ->assertStatus(200)
            ->assertSet('showCreateModal', false);
    }

    /** @test */
    public function ssh_key_manager_loads_keys(): void
    {
        $user = User::factory()->create();
        SSHKey::factory()->count(2)->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(SSHKeyManager::class)
            ->assertCount('keys', 2);
    }

    /** @test */
    public function ssh_key_manager_opens_create_modal(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SSHKeyManager::class)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSet('newKeyName', '')
            ->assertSet('newKeyType', 'ed25519');
    }

    /** @test */
    public function ssh_key_manager_validates_key_generation(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SSHKeyManager::class)
            ->set('newKeyName', '')
            ->set('newKeyType', 'invalid')
            ->call('generateKey')
            ->assertHasErrors(['newKeyName', 'newKeyType']);
    }

    /** @test */
    public function ssh_key_manager_generates_key(): void
    {
        $user = User::factory()->create();
        $sshKeyService = $this->mock(SSHKeyService::class);
        $sshKeyService->shouldReceive('generateKeyPair')
            ->once()
            ->andReturn([
                'success' => true,
                'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5...',
                'private_key' => '-----BEGIN OPENSSH PRIVATE KEY-----...',
                'fingerprint' => 'SHA256:abc123',
            ]);

        Livewire::actingAs($user)
            ->test(SSHKeyManager::class)
            ->set('newKeyName', 'Test Key')
            ->set('newKeyType', 'ed25519')
            ->call('generateKey')
            ->assertNotNull('generatedKey');

        $this->assertDatabaseHas('ssh_keys', [
            'user_id' => $user->id,
            'name' => 'Test Key',
            'type' => 'ed25519',
        ]);
    }

    /** @test */
    public function ssh_key_manager_imports_key(): void
    {
        $user = User::factory()->create();
        $sshKeyService = $this->mock(SSHKeyService::class);
        $sshKeyService->shouldReceive('importKey')
            ->once()
            ->andReturn([
                'success' => true,
                'type' => 'rsa',
                'fingerprint' => 'SHA256:xyz789',
            ]);

        Livewire::actingAs($user)
            ->test(SSHKeyManager::class)
            ->set('importKeyName', 'Imported Key')
            ->set('importPublicKey', 'ssh-rsa AAAAB3NzaC1yc2EA...')
            ->set('importPrivateKey', '-----BEGIN RSA PRIVATE KEY-----...')
            ->call('importKey')
            ->assertSet('showImportModal', false);

        $this->assertDatabaseHas('ssh_keys', [
            'user_id' => $user->id,
            'name' => 'Imported Key',
        ]);
    }

    /** @test */
    public function ssh_key_manager_deletes_key(): void
    {
        $user = User::factory()->create();
        $key = SSHKey::factory()->create(['user_id' => $user->id]);
        $sshKeyService = $this->mock(SSHKeyService::class);
        $sshKeyService->shouldReceive('removeKeyFromServer')->andReturn(['success' => true]);

        Livewire::actingAs($user)
            ->test(SSHKeyManager::class)
            ->call('deleteKey', $key->id);

        $this->assertDatabaseMissing('ssh_keys', ['id' => $key->id]);
    }

    // ==================== GitHub Settings Tests ====================

    /** @test */
    public function github_settings_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GitHubSettings::class)
            ->assertStatus(200)
            ->assertSet('search', '')
            ->assertSet('visibilityFilter', 'all');
    }

    /** @test */
    public function github_settings_shows_connection(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($user)
            ->test(GitHubSettings::class);

        $this->assertNotNull($component->connection);
    }

    /** @test */
    public function github_settings_syncs_repositories(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $gitHubService = $this->mock(GitHubService::class);
        $gitHubService->shouldReceive('syncRepositories')
            ->once()
            ->with($connection)
            ->andReturn(5);

        Livewire::actingAs($user)
            ->test(GitHubSettings::class)
            ->call('syncRepositories')
            ->assertSet('syncing', false)
            ->assertDispatched('notification');
    }

    /** @test */
    public function github_settings_links_repository_to_project(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create(['user_id' => $user->id, 'is_active' => true]);
        $project = Project::factory()->create();
        $repository = GitHubRepository::factory()->create([
            'github_connection_id' => $connection->id,
        ]);

        Livewire::actingAs($user)
            ->test(GitHubSettings::class)
            ->set('selectedRepoId', $repository->id)
            ->set('selectedProjectId', $project->id)
            ->call('linkToProject')
            ->assertSet('showLinkModal', false)
            ->assertDispatched('notification');

        $repository->refresh();
        $this->assertEquals($project->id, $repository->project_id);
    }

    /** @test */
    public function github_settings_unlinks_repository(): void
    {
        $user = User::factory()->create();
        $connection = GitHubConnection::factory()->create(['user_id' => $user->id, 'is_active' => true]);
        $project = Project::factory()->create();
        $repository = GitHubRepository::factory()->create([
            'github_connection_id' => $connection->id,
            'project_id' => $project->id,
        ]);

        Livewire::actingAs($user)
            ->test(GitHubSettings::class)
            ->call('unlinkProject', $repository->id)
            ->assertDispatched('notification');

        $repository->refresh();
        $this->assertNull($repository->project_id);
    }

    // ==================== Storage Settings Tests ====================

    /** @test */
    public function storage_settings_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(StorageSettings::class)
            ->assertStatus(200)
            ->assertSet('showModal', false)
            ->assertSet('activeTab', 's3');
    }

    /** @test */
    public function storage_settings_opens_create_modal(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(StorageSettings::class)
            ->call('openCreateModal')
            ->assertSet('showModal', true)
            ->assertSet('name', '')
            ->assertSet('driver', 's3');
    }

    /** @test */
    public function storage_settings_validates_configuration(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(StorageSettings::class)
            ->set('name', '')
            ->set('driver', 'invalid')
            ->call('save')
            ->assertHasErrors(['name', 'driver']);
    }

    /** @test */
    public function storage_settings_creates_configuration(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(StorageSettings::class)
            ->set('name', 'S3 Storage')
            ->set('driver', 's3')
            ->set('bucket', 'my-bucket')
            ->set('region', 'us-east-1')
            ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
            ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
            ->call('save')
            ->assertSet('showModal', false)
            ->assertDispatched('notification');

        $this->assertDatabaseHas('storage_configurations', [
            'name' => 'S3 Storage',
            'driver' => 's3',
            'bucket' => 'my-bucket',
        ]);
    }

    /** @test */
    public function storage_settings_generates_encryption_key(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(StorageSettings::class)
            ->call('generateEncryptionKey')
            ->assertSet('enable_encryption', true)
            ->assertNotEmpty('encryption_key')
            ->assertDispatched('notification');
    }

    /** @test */
    public function storage_settings_sets_default_storage(): void
    {
        $user = User::factory()->create();
        $config = StorageConfiguration::factory()->create(['is_default' => false]);

        Livewire::actingAs($user)
            ->test(StorageSettings::class)
            ->call('setAsDefault', $config->id)
            ->assertDispatched('notification');

        $config->refresh();
        $this->assertTrue($config->is_default);
    }

    /** @test */
    public function storage_settings_deletes_configuration(): void
    {
        $user = User::factory()->create();
        $config = StorageConfiguration::factory()->create();

        Livewire::actingAs($user)
            ->test(StorageSettings::class)
            ->call('delete', $config->id)
            ->assertDispatched('notification');

        $this->assertDatabaseMissing('storage_configurations', ['id' => $config->id]);
    }

    // ==================== Queue Monitor Tests ====================

    /** @test */
    public function queue_monitor_renders(): void
    {
        $user = User::factory()->create();
        $queueMonitor = $this->mock(QueueMonitorService::class);
        $queueMonitor->shouldReceive('getQueueStatistics')->andReturn([
            'pending_jobs' => 0,
            'processing_jobs' => 0,
            'failed_jobs' => 0,
            'worker_status' => ['is_running' => true, 'worker_count' => 2],
            'queues' => [],
        ]);
        $queueMonitor->shouldReceive('getFailedJobs')->andReturn([]);

        Livewire::actingAs($user)
            ->test(QueueMonitor::class)
            ->assertStatus(200)
            ->assertSet('showJobDetails', false);
    }

    /** @test */
    public function queue_monitor_refreshes_stats(): void
    {
        $user = User::factory()->create();
        $queueMonitor = $this->mock(QueueMonitorService::class);
        $queueMonitor->shouldReceive('getQueueStatistics')->twice()->andReturn([
            'pending_jobs' => 5,
            'failed_jobs' => 2,
            'worker_status' => ['is_running' => true],
            'queues' => [],
        ]);
        $queueMonitor->shouldReceive('getFailedJobs')->twice()->andReturn([]);

        Livewire::actingAs($user)
            ->test(QueueMonitor::class)
            ->call('refreshStats')
            ->assertSet('isLoading', false)
            ->assertDispatched('notification');
    }

    /** @test */
    public function queue_monitor_retries_job(): void
    {
        $user = User::factory()->create();
        $queueMonitor = $this->mock(QueueMonitorService::class);
        $queueMonitor->shouldReceive('getQueueStatistics')->andReturn([
            'worker_status' => ['is_running' => true],
            'queues' => [],
        ]);
        $queueMonitor->shouldReceive('getFailedJobs')->andReturn([]);
        $queueMonitor->shouldReceive('retryFailedJob')->with(1)->andReturn(true);

        Livewire::actingAs($user)
            ->test(QueueMonitor::class)
            ->call('retryJob', 1)
            ->assertDispatched('notification');
    }

    /** @test */
    public function queue_monitor_retries_all_failed_jobs(): void
    {
        $user = User::factory()->create();
        $queueMonitor = $this->mock(QueueMonitorService::class);
        $queueMonitor->shouldReceive('getQueueStatistics')->andReturn([
            'worker_status' => ['is_running' => true],
            'queues' => [],
        ]);
        $queueMonitor->shouldReceive('getFailedJobs')->andReturn([]);
        $queueMonitor->shouldReceive('retryAllFailedJobs')->andReturn(true);

        Livewire::actingAs($user)
            ->test(QueueMonitor::class)
            ->call('retryAllFailed')
            ->assertDispatched('notification');
    }

    /** @test */
    public function queue_monitor_deletes_job(): void
    {
        $user = User::factory()->create();
        $queueMonitor = $this->mock(QueueMonitorService::class);
        $queueMonitor->shouldReceive('getQueueStatistics')->andReturn([
            'worker_status' => ['is_running' => true],
            'queues' => [],
        ]);
        $queueMonitor->shouldReceive('getFailedJobs')->andReturn([]);
        $queueMonitor->shouldReceive('deleteFailedJob')->with(1)->andReturn(true);

        Livewire::actingAs($user)
            ->test(QueueMonitor::class)
            ->call('deleteJob', 1)
            ->assertDispatched('notification');
    }

    /** @test */
    public function queue_monitor_clears_all_failed_jobs(): void
    {
        $user = User::factory()->create();
        $queueMonitor = $this->mock(QueueMonitorService::class);
        $queueMonitor->shouldReceive('getQueueStatistics')->andReturn([
            'worker_status' => ['is_running' => true],
            'queues' => [],
        ]);
        $queueMonitor->shouldReceive('getFailedJobs')->andReturn([]);
        $queueMonitor->shouldReceive('clearAllFailedJobs')->andReturn(true);

        Livewire::actingAs($user)
            ->test(QueueMonitor::class)
            ->call('clearAllFailed')
            ->assertDispatched('notification');
    }

    // ==================== Health Check Manager Tests ====================

    /** @test */
    public function health_check_manager_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(HealthCheckManager::class)
            ->assertStatus(200)
            ->assertSet('showCreateModal', false);
    }

    /** @test */
    public function health_check_manager_opens_create_modal(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(HealthCheckManager::class)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSet('check_type', 'http')
            ->assertSet('is_active', true);
    }

    /** @test */
    public function health_check_manager_validates_health_check(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(HealthCheckManager::class)
            ->set('check_type', 'http')
            ->set('target_url', '')
            ->set('expected_status', 999)
            ->call('saveCheck')
            ->assertHasErrors(['target_url', 'expected_status']);
    }

    /** @test */
    public function health_check_manager_creates_health_check(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        Livewire::actingAs($user)
            ->test(HealthCheckManager::class)
            ->set('project_id', $project->id)
            ->set('check_type', 'http')
            ->set('target_url', 'https://example.com')
            ->set('expected_status', 200)
            ->set('interval_minutes', 5)
            ->set('timeout_seconds', 30)
            ->set('is_active', true)
            ->call('saveCheck')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('health_checks', [
            'project_id' => $project->id,
            'check_type' => 'http',
            'target_url' => 'https://example.com',
        ]);
    }

    /** @test */
    public function health_check_manager_deletes_health_check(): void
    {
        $user = User::factory()->create();
        $healthCheck = HealthCheck::factory()->create();

        Livewire::actingAs($user)
            ->test(HealthCheckManager::class)
            ->call('deleteCheck', $healthCheck->id)
            ->assertDispatched('notification');

        $this->assertDatabaseMissing('health_checks', ['id' => $healthCheck->id]);
    }

    /** @test */
    public function health_check_manager_runs_check(): void
    {
        $user = User::factory()->create();
        $healthCheck = HealthCheck::factory()->create();
        $healthCheckService = $this->mock(HealthCheckService::class);
        $healthCheckService->shouldReceive('runCheck')->once()->with($healthCheck);

        Livewire::actingAs($user)
            ->test(HealthCheckManager::class)
            ->call('runCheck', $healthCheck->id)
            ->assertDispatched('notification');
    }

    /** @test */
    public function health_check_manager_creates_notification_channel(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(HealthCheckManager::class)
            ->set('channel_type', 'email')
            ->set('channel_name', 'Admin Email')
            ->set('channel_email', 'admin@example.com')
            ->set('channel_is_active', true)
            ->call('saveChannel')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('notification_channels', [
            'user_id' => $user->id,
            'type' => 'email',
            'name' => 'Admin Email',
        ]);
    }

    /** @test */
    public function health_check_manager_tests_notification_channel(): void
    {
        $user = User::factory()->create();
        $channel = NotificationChannel::factory()->create(['user_id' => $user->id]);
        $notificationService = $this->mock(NotificationService::class);
        $notificationService->shouldReceive('sendTestNotification')->once()->with($channel)->andReturn(true);

        Livewire::actingAs($user)
            ->test(HealthCheckManager::class)
            ->call('testChannel', $channel->id)
            ->assertDispatched('notification');
    }

    // ==================== System Status Tests ====================

    /** @test */
    public function system_status_renders(): void
    {
        $user = User::factory()->create();
        $queueMonitor = $this->mock(QueueMonitorService::class);
        $queueMonitor->shouldReceive('getQueueStatistics')->andReturn([
            'worker_status' => ['is_running' => true, 'worker_count' => 2],
            'pending_jobs' => 0,
            'failed_jobs' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(SystemStatus::class)
            ->assertStatus(200)
            ->assertSet('isLoading', false);
    }

    /** @test */
    public function system_status_loads_reverb_status(): void
    {
        $user = User::factory()->create();
        $queueMonitor = $this->mock(QueueMonitorService::class);
        $queueMonitor->shouldReceive('getQueueStatistics')->andReturn([
            'worker_status' => ['is_running' => true],
        ]);

        $component = Livewire::actingAs($user)
            ->test(SystemStatus::class);

        $this->assertIsArray($component->reverbStatus);
        $this->assertArrayHasKey('running', $component->reverbStatus);
    }

    /** @test */
    public function system_status_loads_cache_stats(): void
    {
        $user = User::factory()->create();
        $queueMonitor = $this->mock(QueueMonitorService::class);
        $queueMonitor->shouldReceive('getQueueStatistics')->andReturn([
            'worker_status' => ['is_running' => true],
        ]);

        $component = Livewire::actingAs($user)
            ->test(SystemStatus::class);

        $this->assertIsArray($component->cacheStats);
        $this->assertArrayHasKey('driver', $component->cacheStats);
    }

    /** @test */
    public function system_status_refreshes_stats(): void
    {
        $user = User::factory()->create();
        $queueMonitor = $this->mock(QueueMonitorService::class);
        $queueMonitor->shouldReceive('getQueueStatistics')->twice()->andReturn([
            'worker_status' => ['is_running' => true],
        ]);

        Livewire::actingAs($user)
            ->test(SystemStatus::class)
            ->call('refreshStats')
            ->assertSet('isLoading', false)
            ->assertDispatched('notification');
    }

    // ==================== Default Setup Preferences Tests ====================

    /** @test */
    public function default_setup_preferences_renders(): void
    {
        $user = User::factory()->create();
        UserSettings::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(DefaultSetupPreferences::class)
            ->assertStatus(200)
            ->assertSet('isSaving', false);
    }

    /** @test */
    public function default_setup_preferences_loads_settings(): void
    {
        $user = User::factory()->create();
        UserSettings::factory()->create([
            'user_id' => $user->id,
            'default_enable_ssl' => true,
            'default_enable_webhooks' => false,
            'theme' => 'dark',
        ]);

        Livewire::actingAs($user)
            ->test(DefaultSetupPreferences::class)
            ->assertSet('defaultEnableSsl', true)
            ->assertSet('defaultEnableWebhooks', false)
            ->assertSet('theme', 'dark');
    }

    /** @test */
    public function default_setup_preferences_saves_settings(): void
    {
        $user = User::factory()->create();
        $settings = UserSettings::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(DefaultSetupPreferences::class)
            ->set('defaultEnableSsl', false)
            ->set('defaultEnableWebhooks', true)
            ->set('theme', 'light')
            ->call('save')
            ->assertDispatched('notification');

        $settings->refresh();
        $this->assertFalse($settings->default_enable_ssl);
        $this->assertTrue($settings->default_enable_webhooks);
        $this->assertEquals('light', $settings->theme);
    }

    // ==================== Team List Tests ====================

    /** @test */
    public function team_list_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TeamList::class)
            ->assertStatus(200)
            ->assertSet('showCreateModal', false);
    }

    /** @test */
    public function team_list_opens_create_modal(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TeamList::class)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSet('name', '')
            ->assertSet('description', '');
    }

    /** @test */
    public function team_list_validates_team_creation(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TeamList::class)
            ->set('name', '')
            ->call('createTeam')
            ->assertHasErrors(['name']);
    }

    /** @test */
    public function team_list_creates_team(): void
    {
        $user = User::factory()->create();
        $teamService = $this->mock(TeamService::class);
        $team = Team::factory()->make(['name' => 'New Team']);
        $teamService->shouldReceive('createTeam')->once()->andReturn($team);

        Livewire::actingAs($user)
            ->test(TeamList::class)
            ->set('name', 'New Team')
            ->set('description', 'Team description')
            ->call('createTeam');
    }

    /** @test */
    public function team_list_switches_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user->id, ['role' => 'member']);

        Livewire::actingAs($user)
            ->test(TeamList::class)
            ->call('switchTeam', $team->id);

        $user->refresh();
        $this->assertEquals($team->id, $user->current_team_id);
    }

    /** @test */
    public function team_list_deletes_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $team->members()->attach($user->id, ['role' => 'owner']);

        $teamService = $this->mock(TeamService::class);
        $teamService->shouldReceive('deleteTeam')->once();

        Livewire::actingAs($user)
            ->test(TeamList::class)
            ->call('deleteTeam', $team->id)
            ->assertDispatched('notification');
    }

    // ==================== Team Settings Tests ====================

    /** @test */
    public function team_settings_renders(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user->id, ['role' => 'admin']);

        Livewire::actingAs($user)
            ->test(TeamSettings::class, ['team' => $team])
            ->assertStatus(200)
            ->assertSet('activeTab', 'general');
    }

    /** @test */
    public function team_settings_updates_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $team->members()->attach($user->id, ['role' => 'owner']);

        Livewire::actingAs($user)
            ->test(TeamSettings::class, ['team' => $team])
            ->set('name', 'Updated Team Name')
            ->set('description', 'Updated description')
            ->call('updateTeam')
            ->assertDispatched('notification');

        $team->refresh();
        $this->assertEquals('Updated Team Name', $team->name);
    }

    /** @test */
    public function team_settings_invites_member(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $team->members()->attach($user->id, ['role' => 'owner']);

        $teamService = $this->mock(TeamService::class);
        $teamService->shouldReceive('inviteMember')->once();

        Livewire::actingAs($user)
            ->test(TeamSettings::class, ['team' => $team])
            ->set('inviteEmail', 'newmember@example.com')
            ->set('inviteRole', 'member')
            ->call('inviteMember')
            ->assertDispatched('notification');
    }

    /** @test */
    public function team_settings_removes_member(): void
    {
        $user = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $team->members()->attach($user->id, ['role' => 'owner']);
        $team->members()->attach($member->id, ['role' => 'member']);

        $teamService = $this->mock(TeamService::class);
        $teamService->shouldReceive('removeMember')->once();

        Livewire::actingAs($user)
            ->test(TeamSettings::class, ['team' => $team])
            ->call('removeMember', $member->id)
            ->assertDispatched('notification');
    }

    /** @test */
    public function team_settings_updates_member_role(): void
    {
        $user = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $team->members()->attach($user->id, ['role' => 'owner']);
        $team->members()->attach($member->id, ['role' => 'member']);

        $teamService = $this->mock(TeamService::class);
        $teamService->shouldReceive('updateRole')->once();

        Livewire::actingAs($user)
            ->test(TeamSettings::class, ['team' => $team])
            ->call('updateRole', $member->id, 'admin')
            ->assertDispatched('notification');
    }

    // ==================== Team Switcher Tests ====================

    /** @test */
    public function team_switcher_renders(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user->id, ['role' => 'member']);
        $user->update(['current_team_id' => $team->id]);

        Livewire::actingAs($user)
            ->test(TeamSwitcher::class)
            ->assertStatus(200)
            ->assertSet('showDropdown', false);
    }

    /** @test */
    public function team_switcher_toggles_dropdown(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TeamSwitcher::class)
            ->call('toggleDropdown')
            ->assertSet('showDropdown', true)
            ->call('toggleDropdown')
            ->assertSet('showDropdown', false);
    }

    /** @test */
    public function team_switcher_switches_team(): void
    {
        $user = User::factory()->create();
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();
        $team1->members()->attach($user->id, ['role' => 'member']);
        $team2->members()->attach($user->id, ['role' => 'member']);
        $user->update(['current_team_id' => $team1->id]);

        Livewire::actingAs($user)
            ->test(TeamSwitcher::class)
            ->call('switchTeam', $team2->id);

        $user->refresh();
        $this->assertEquals($team2->id, $user->current_team_id);
    }

    // ==================== User List Tests ====================

    /** @test */
    public function user_list_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UserList::class)
            ->assertStatus(200)
            ->assertSet('search', '')
            ->assertSet('showCreateModal', false);
    }

    /** @test */
    public function user_list_searches_users(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        Livewire::actingAs($user)
            ->test(UserList::class)
            ->set('search', 'John')
            ->assertSee('John Doe');
    }

    /** @test */
    public function user_list_creates_user(): void
    {
        $admin = User::factory()->create();
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        Livewire::actingAs($admin)
            ->test(UserList::class)
            ->set('name', 'New User')
            ->set('email', 'newuser@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('saveUser');

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);
    }

    /** @test */
    public function user_list_updates_user(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['name' => 'Old Name']);

        Livewire::actingAs($admin)
            ->test(UserList::class)
            ->call('editUser', $user->id)
            ->set('name', 'New Name')
            ->call('updateUser');

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
    }

    /** @test */
    public function user_list_deletes_user(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UserList::class)
            ->call('deleteUser', $user->id);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function user_list_cannot_delete_self(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UserList::class)
            ->call('deleteUser', $user->id);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    // ==================== Log Viewer Tests ====================

    /** @test */
    public function log_viewer_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LogViewer::class)
            ->assertStatus(200)
            ->assertSet('source', 'all')
            ->assertSet('level', 'all');
    }

    /** @test */
    public function log_viewer_filters_by_server(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();
        LogEntry::factory()->create(['server_id' => $server->id]);
        LogEntry::factory()->create();

        Livewire::actingAs($user)
            ->test(LogViewer::class)
            ->set('server_id', $server->id)
            ->assertCount('logs', 1);
    }

    /** @test */
    public function log_viewer_filters_by_level(): void
    {
        $user = User::factory()->create();
        LogEntry::factory()->create(['level' => 'error']);
        LogEntry::factory()->create(['level' => 'warning']);

        Livewire::actingAs($user)
            ->test(LogViewer::class)
            ->set('level', 'error')
            ->assertCount('logs', 1);
    }

    /** @test */
    public function log_viewer_searches_logs(): void
    {
        $user = User::factory()->create();
        LogEntry::factory()->create(['message' => 'Database connection failed']);
        LogEntry::factory()->create(['message' => 'Request completed successfully']);

        Livewire::actingAs($user)
            ->test(LogViewer::class)
            ->set('search', 'Database')
            ->assertCount('logs', 1);
    }

    /** @test */
    public function log_viewer_clears_filters(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();

        Livewire::actingAs($user)
            ->test(LogViewer::class)
            ->set('server_id', $server->id)
            ->set('level', 'error')
            ->set('search', 'test')
            ->call('clearFilters')
            ->assertSet('server_id', null)
            ->assertSet('level', 'all')
            ->assertSet('search', '');
    }

    // ==================== Notification Logs Tests ====================

    /** @test */
    public function notification_logs_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationLogs::class)
            ->assertStatus(200)
            ->assertSet('showDetails', false);
    }

    /** @test */
    public function notification_logs_filters_by_status(): void
    {
        $user = User::factory()->create();
        NotificationLog::factory()->create(['status' => 'success']);
        NotificationLog::factory()->create(['status' => 'failed']);

        Livewire::actingAs($user)
            ->test(NotificationLogs::class)
            ->set('statusFilter', 'success')
            ->assertSee('success');
    }

    /** @test */
    public function notification_logs_views_details(): void
    {
        $user = User::factory()->create();
        $log = NotificationLog::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationLogs::class)
            ->call('viewDetails', $log->id)
            ->assertSet('showDetails', true)
            ->assertNotEmpty('selectedLog');
    }

    /** @test */
    public function notification_logs_clears_filters(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationLogs::class)
            ->set('search', 'test')
            ->set('statusFilter', 'failed')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('statusFilter', '');
    }

    // ==================== Webhook Logs Tests ====================

    /** @test */
    public function webhook_logs_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WebhookLogs::class)
            ->assertStatus(200)
            ->assertSet('showDetails', false);
    }

    /** @test */
    public function webhook_logs_filters_by_provider(): void
    {
        $user = User::factory()->create();
        WebhookDelivery::factory()->create(['provider' => 'github']);
        WebhookDelivery::factory()->create(['provider' => 'gitlab']);

        Livewire::actingAs($user)
            ->test(WebhookLogs::class)
            ->set('providerFilter', 'github')
            ->assertSee('github');
    }

    /** @test */
    public function webhook_logs_views_details(): void
    {
        $user = User::factory()->create();
        $delivery = WebhookDelivery::factory()->create();

        Livewire::actingAs($user)
            ->test(WebhookLogs::class)
            ->call('viewDetails', $delivery->id)
            ->assertSet('showDetails', true)
            ->assertNotEmpty('selectedDelivery');
    }

    /** @test */
    public function webhook_logs_clears_filters(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WebhookLogs::class)
            ->set('search', 'test')
            ->set('providerFilter', 'github')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('providerFilter', '');
    }

    // ==================== Log Source Manager Tests ====================

    /** @test */
    public function log_source_manager_renders(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();

        Livewire::actingAs($user)
            ->test(LogSourceManager::class, ['server' => $server])
            ->assertStatus(200)
            ->assertSet('showAddModal', false);
    }

    /** @test */
    public function log_source_manager_opens_add_modal(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();

        Livewire::actingAs($user)
            ->test(LogSourceManager::class, ['server' => $server])
            ->call('openAddModal')
            ->assertSet('showAddModal', true)
            ->assertSet('name', '')
            ->assertSet('type', 'file');
    }

    /** @test */
    public function log_source_manager_validates_source(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();

        Livewire::actingAs($user)
            ->test(LogSourceManager::class, ['server' => $server])
            ->set('name', '')
            ->set('path', '')
            ->call('addSource')
            ->assertHasErrors(['name', 'path']);
    }

    /** @test */
    public function log_source_manager_adds_source(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();

        Livewire::actingAs($user)
            ->test(LogSourceManager::class, ['server' => $server])
            ->set('name', 'Laravel Log')
            ->set('type', 'file')
            ->set('path', '/var/log/laravel.log')
            ->call('addSource')
            ->assertSet('showAddModal', false)
            ->assertDispatched('notification');

        $this->assertDatabaseHas('log_sources', [
            'server_id' => $server->id,
            'name' => 'Laravel Log',
            'path' => '/var/log/laravel.log',
        ]);
    }

    /** @test */
    public function log_source_manager_toggles_source(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $source = LogSource::factory()->create([
            'server_id' => $server->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(LogSourceManager::class, ['server' => $server])
            ->call('toggleSource', $source->id)
            ->assertDispatched('notification');

        $source->refresh();
        $this->assertFalse($source->is_active);
    }

    /** @test */
    public function log_source_manager_removes_source(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $source = LogSource::factory()->create(['server_id' => $server->id]);

        Livewire::actingAs($user)
            ->test(LogSourceManager::class, ['server' => $server])
            ->call('removeSource', $source->id)
            ->assertDispatched('notification');

        $this->assertDatabaseMissing('log_sources', ['id' => $source->id]);
    }
}
