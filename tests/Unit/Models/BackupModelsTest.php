<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\BackupSchedule;
use App\Models\DatabaseBackup;
use App\Models\FileBackup;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\StorageConfiguration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupModelsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Use in-memory SQLite for faster tests
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
    }

    // ==========================================
    // DatabaseBackup Model Tests
    // ==========================================

    public function test_database_backup_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $backup = DatabaseBackup::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $backup->project);
        $this->assertEquals($project->id, $backup->project->id);
    }

    public function test_database_backup_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $backup = DatabaseBackup::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $backup->server);
        $this->assertEquals($server->id, $backup->server->id);
    }

    public function test_database_backup_completed_scope(): void
    {
        DatabaseBackup::factory()->create(['status' => 'completed']);
        DatabaseBackup::factory()->create(['status' => 'failed']);
        DatabaseBackup::factory()->create(['status' => 'pending']);

        $completed = DatabaseBackup::completed()->get();

        $this->assertCount(1, $completed);
        $this->assertEquals('completed', $completed->first()->status);
    }

    public function test_database_backup_failed_scope(): void
    {
        DatabaseBackup::factory()->create(['status' => 'completed']);
        DatabaseBackup::factory()->create(['status' => 'failed']);
        DatabaseBackup::factory()->create(['status' => 'failed']);

        $failed = DatabaseBackup::failed()->get();

        $this->assertCount(2, $failed);
    }

    public function test_database_backup_for_project_scope(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        DatabaseBackup::factory()->count(3)->create(['project_id' => $project1->id]);
        DatabaseBackup::factory()->count(2)->create(['project_id' => $project2->id]);

        $project1Backups = DatabaseBackup::forProject($project1->id)->get();

        $this->assertCount(3, $project1Backups);
    }

    public function test_database_backup_verified_scope(): void
    {
        DatabaseBackup::factory()->create(['verified_at' => now()]);
        DatabaseBackup::factory()->create(['verified_at' => null]);
        DatabaseBackup::factory()->create(['verified_at' => now()->subDay()]);

        $verified = DatabaseBackup::verified()->get();

        $this->assertCount(2, $verified);
    }

    public function test_database_backup_unverified_scope(): void
    {
        DatabaseBackup::factory()->create(['verified_at' => now()]);
        DatabaseBackup::factory()->create(['verified_at' => null]);
        DatabaseBackup::factory()->create(['verified_at' => null]);

        $unverified = DatabaseBackup::unverified()->get();

        $this->assertCount(2, $unverified);
    }

    public function test_database_backup_file_size_human_accessor(): void
    {
        $backup = DatabaseBackup::factory()->create(['file_size' => 1024]);
        $this->assertEquals('1 KB', $backup->file_size_human);

        $backup = DatabaseBackup::factory()->create(['file_size' => 1048576]);
        $this->assertEquals('1 MB', $backup->file_size_human);

        $backup = DatabaseBackup::factory()->create(['file_size' => null]);
        $this->assertEquals('N/A', $backup->file_size_human);
    }

    public function test_database_backup_duration_accessor(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'started_at' => now()->subSeconds(45),
            'completed_at' => now(),
        ]);

        $this->assertEquals('45s', $backup->duration);
    }

    public function test_database_backup_duration_in_minutes(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'started_at' => now()->subMinutes(5)->subSeconds(30),
            'completed_at' => now(),
        ]);

        $this->assertEquals('5.5m', $backup->duration);
    }

    public function test_database_backup_duration_in_hours(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'started_at' => now()->subHours(2)->subMinutes(30),
            'completed_at' => now(),
        ]);

        $this->assertEquals('2.5h', $backup->duration);
    }

    public function test_database_backup_duration_is_null_when_not_completed(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $this->assertNull($backup->duration);
    }

    public function test_database_backup_status_color_accessor(): void
    {
        $backup = DatabaseBackup::factory()->create(['status' => 'completed']);
        $this->assertEquals('green', $backup->status_color);

        $backup = DatabaseBackup::factory()->create(['status' => 'running']);
        $this->assertEquals('blue', $backup->status_color);

        $backup = DatabaseBackup::factory()->create(['status' => 'failed']);
        $this->assertEquals('red', $backup->status_color);

        $backup = DatabaseBackup::factory()->create(['status' => 'pending']);
        $this->assertEquals('yellow', $backup->status_color);
    }

    public function test_database_backup_status_icon_accessor(): void
    {
        $backup = DatabaseBackup::factory()->create(['status' => 'completed']);
        $this->assertEquals('fa-check-circle', $backup->status_icon);

        $backup = DatabaseBackup::factory()->create(['status' => 'running']);
        $this->assertEquals('fa-spinner fa-spin', $backup->status_icon);

        $backup = DatabaseBackup::factory()->create(['status' => 'failed']);
        $this->assertEquals('fa-exclamation-circle', $backup->status_icon);

        $backup = DatabaseBackup::factory()->create(['status' => 'pending']);
        $this->assertEquals('fa-clock', $backup->status_icon);
    }

    public function test_database_backup_is_verified_method(): void
    {
        $backup = DatabaseBackup::factory()->create(['verified_at' => now()]);
        $this->assertTrue($backup->isVerified());

        $backup = DatabaseBackup::factory()->create(['verified_at' => null]);
        $this->assertFalse($backup->isVerified());
    }

    public function test_database_backup_status_check_methods(): void
    {
        $backup = DatabaseBackup::factory()->create(['status' => 'pending']);
        $this->assertTrue($backup->isPending());
        $this->assertFalse($backup->isRunning());
        $this->assertFalse($backup->isCompleted());
        $this->assertFalse($backup->isFailed());

        $backup = DatabaseBackup::factory()->create(['status' => 'running']);
        $this->assertTrue($backup->isRunning());
        $this->assertFalse($backup->isPending());

        $backup = DatabaseBackup::factory()->create(['status' => 'completed']);
        $this->assertTrue($backup->isCompleted());
        $this->assertFalse($backup->isFailed());

        $backup = DatabaseBackup::factory()->create(['status' => 'failed']);
        $this->assertTrue($backup->isFailed());
        $this->assertFalse($backup->isCompleted());
    }

    public function test_database_backup_casts_metadata_to_array(): void
    {
        $metadata = ['compression' => 'gzip', 'tables' => ['users', 'posts']];
        $backup = DatabaseBackup::factory()->create(['metadata' => $metadata]);

        $this->assertIsArray($backup->metadata);
        $this->assertEquals('gzip', $backup->metadata['compression']);
        $this->assertCount(2, $backup->metadata['tables']);
    }

    // ==========================================
    // FileBackup Model Tests
    // ==========================================

    public function test_file_backup_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $backup = FileBackup::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $backup->project);
        $this->assertEquals($project->id, $backup->project->id);
    }

    public function test_file_backup_belongs_to_parent_backup(): void
    {
        $parentBackup = FileBackup::factory()->create(['type' => 'full']);
        $childBackup = FileBackup::factory()->create([
            'type' => 'incremental',
            'parent_backup_id' => $parentBackup->id,
        ]);

        $this->assertInstanceOf(FileBackup::class, $childBackup->parentBackup);
        $this->assertEquals($parentBackup->id, $childBackup->parentBackup->id);
    }

    public function test_file_backup_has_many_child_backups(): void
    {
        $parentBackup = FileBackup::factory()->create(['type' => 'full']);
        FileBackup::factory()->count(3)->create([
            'type' => 'incremental',
            'parent_backup_id' => $parentBackup->id,
        ]);

        $this->assertCount(3, $parentBackup->childBackups);
    }

    public function test_file_backup_full_scope(): void
    {
        FileBackup::factory()->count(2)->create(['type' => 'full']);
        FileBackup::factory()->create(['type' => 'incremental']);

        $fullBackups = FileBackup::full()->get();

        $this->assertCount(2, $fullBackups);
    }

    public function test_file_backup_incremental_scope(): void
    {
        FileBackup::factory()->create(['type' => 'full']);
        FileBackup::factory()->count(3)->create(['type' => 'incremental']);

        $incrementalBackups = FileBackup::incremental()->get();

        $this->assertCount(3, $incrementalBackups);
    }

    public function test_file_backup_completed_scope(): void
    {
        FileBackup::factory()->create(['status' => 'completed']);
        FileBackup::factory()->create(['status' => 'failed']);

        $completed = FileBackup::completed()->get();

        $this->assertCount(1, $completed);
    }

    public function test_file_backup_failed_scope(): void
    {
        FileBackup::factory()->create(['status' => 'completed']);
        FileBackup::factory()->count(2)->create(['status' => 'failed']);

        $failed = FileBackup::failed()->get();

        $this->assertCount(2, $failed);
    }

    public function test_file_backup_for_project_scope(): void
    {
        $project = Project::factory()->create();
        FileBackup::factory()->count(4)->create(['project_id' => $project->id]);
        FileBackup::factory()->count(2)->create();

        $projectBackups = FileBackup::forProject($project->id)->get();

        $this->assertCount(4, $projectBackups);
    }

    public function test_file_backup_formatted_size_accessor(): void
    {
        $backup = FileBackup::factory()->create(['size_bytes' => 1024]);
        $this->assertEquals('1 KB', $backup->formatted_size);

        $backup = FileBackup::factory()->create(['size_bytes' => 1048576]);
        $this->assertEquals('1 MB', $backup->formatted_size);

        $backup = FileBackup::factory()->create(['size_bytes' => 1073741824]);
        $this->assertEquals('1 GB', $backup->formatted_size);
    }

    public function test_file_backup_duration_accessor(): void
    {
        $backup = FileBackup::factory()->create([
            'started_at' => now()->subSeconds(120),
            'completed_at' => now(),
        ]);

        $this->assertEquals(120, $backup->duration);
    }

    public function test_file_backup_formatted_duration_accessor(): void
    {
        $backup = FileBackup::factory()->create([
            'started_at' => now()->subSeconds(45),
            'completed_at' => now(),
        ]);
        $this->assertEquals('45s', $backup->formatted_duration);

        $backup = FileBackup::factory()->create([
            'started_at' => now()->subMinutes(2)->subSeconds(30),
            'completed_at' => now(),
        ]);
        $this->assertEquals('2m 30s', $backup->formatted_duration);

        $backup = FileBackup::factory()->create([
            'started_at' => now()->subHours(1)->subMinutes(15),
            'completed_at' => now(),
        ]);
        $this->assertEquals('1h 15m', $backup->formatted_duration);
    }

    public function test_file_backup_status_color_accessor(): void
    {
        $backup = FileBackup::factory()->create(['status' => 'completed']);
        $this->assertEquals('green', $backup->status_color);

        $backup = FileBackup::factory()->create(['status' => 'running']);
        $this->assertEquals('blue', $backup->status_color);

        $backup = FileBackup::factory()->create(['status' => 'pending']);
        $this->assertEquals('yellow', $backup->status_color);

        $backup = FileBackup::factory()->create(['status' => 'failed']);
        $this->assertEquals('red', $backup->status_color);
    }

    public function test_file_backup_type_color_accessor(): void
    {
        $backup = FileBackup::factory()->create(['type' => 'full']);
        $this->assertEquals('purple', $backup->type_color);

        $backup = FileBackup::factory()->create(['type' => 'incremental']);
        $this->assertEquals('blue', $backup->type_color);
    }

    public function test_file_backup_status_check_methods(): void
    {
        $backup = FileBackup::factory()->create(['status' => 'completed']);
        $this->assertTrue($backup->isCompleted());
        $this->assertFalse($backup->isFailed());
        $this->assertFalse($backup->isPending());
        $this->assertFalse($backup->isRunning());
    }

    public function test_file_backup_type_check_methods(): void
    {
        $backup = FileBackup::factory()->create(['type' => 'full']);
        $this->assertTrue($backup->isFull());
        $this->assertFalse($backup->isIncremental());

        $backup = FileBackup::factory()->create(['type' => 'incremental']);
        $this->assertTrue($backup->isIncremental());
        $this->assertFalse($backup->isFull());
    }

    public function test_file_backup_get_backup_chain(): void
    {
        $fullBackup = FileBackup::factory()->create(['type' => 'full']);
        $incremental1 = FileBackup::factory()->create([
            'type' => 'incremental',
            'parent_backup_id' => $fullBackup->id,
        ]);
        $incremental2 = FileBackup::factory()->create([
            'type' => 'incremental',
            'parent_backup_id' => $incremental1->id,
        ]);

        $chain = $incremental2->getBackupChain();

        $this->assertCount(3, $chain);
        $this->assertEquals($fullBackup->id, $chain[0]->id);
        $this->assertEquals($incremental1->id, $chain[1]->id);
        $this->assertEquals($incremental2->id, $chain[2]->id);
    }

    public function test_file_backup_get_root_backup(): void
    {
        $fullBackup = FileBackup::factory()->create(['type' => 'full']);
        $incremental1 = FileBackup::factory()->create([
            'type' => 'incremental',
            'parent_backup_id' => $fullBackup->id,
        ]);
        $incremental2 = FileBackup::factory()->create([
            'type' => 'incremental',
            'parent_backup_id' => $incremental1->id,
        ]);

        $root = $incremental2->getRootBackup();

        $this->assertEquals($fullBackup->id, $root->id);
    }

    public function test_file_backup_get_incremental_depth(): void
    {
        $fullBackup = FileBackup::factory()->create(['type' => 'full']);
        $this->assertEquals(0, $fullBackup->getIncrementalDepth());

        $incremental1 = FileBackup::factory()->create([
            'type' => 'incremental',
            'parent_backup_id' => $fullBackup->id,
        ]);
        $this->assertEquals(1, $incremental1->getIncrementalDepth());

        $incremental2 = FileBackup::factory()->create([
            'type' => 'incremental',
            'parent_backup_id' => $incremental1->id,
        ]);
        $this->assertEquals(2, $incremental2->getIncrementalDepth());
    }

    // ==========================================
    // ServerBackup Model Tests
    // ==========================================

    public function test_server_backup_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $backup = ServerBackup::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $backup->server);
        $this->assertEquals($server->id, $backup->server->id);
    }

    public function test_server_backup_completed_scope(): void
    {
        ServerBackup::factory()->create(['status' => 'completed']);
        ServerBackup::factory()->create(['status' => 'failed']);

        $completed = ServerBackup::completed()->get();

        $this->assertCount(1, $completed);
    }

    public function test_server_backup_failed_scope(): void
    {
        ServerBackup::factory()->create(['status' => 'completed']);
        ServerBackup::factory()->count(2)->create(['status' => 'failed']);

        $failed = ServerBackup::failed()->get();

        $this->assertCount(2, $failed);
    }

    public function test_server_backup_by_type_scope(): void
    {
        ServerBackup::factory()->count(2)->create(['type' => 'full']);
        ServerBackup::factory()->create(['type' => 'partial']);

        $fullBackups = ServerBackup::byType('full')->get();

        $this->assertCount(2, $fullBackups);
    }

    public function test_server_backup_formatted_size_accessor(): void
    {
        $backup = ServerBackup::factory()->create(['size_bytes' => null]);
        $this->assertEquals('Unknown', $backup->formatted_size);

        $backup = ServerBackup::factory()->create(['size_bytes' => 1024]);
        $this->assertEquals('1 KB', $backup->formatted_size);

        $backup = ServerBackup::factory()->create(['size_bytes' => 1048576]);
        $this->assertEquals('1 MB', $backup->formatted_size);
    }

    public function test_server_backup_duration_accessor(): void
    {
        $backup = ServerBackup::factory()->create([
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $this->assertEquals(300, $backup->duration);
    }

    public function test_server_backup_formatted_duration_accessor(): void
    {
        $backup = ServerBackup::factory()->create([
            'started_at' => now()->subSeconds(30),
            'completed_at' => now(),
        ]);
        $this->assertEquals('30s', $backup->formatted_duration);

        $backup = ServerBackup::factory()->create([
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);
        $this->assertEquals('5m 0s', $backup->formatted_duration);

        $backup = ServerBackup::factory()->create([
            'started_at' => now()->subHours(2)->subMinutes(15)->subSeconds(30),
            'completed_at' => now(),
        ]);
        $this->assertEquals('2h 15m 30s', $backup->formatted_duration);
    }

    public function test_server_backup_status_check_methods(): void
    {
        $backup = ServerBackup::factory()->create(['status' => 'completed']);
        $this->assertTrue($backup->isCompleted());
        $this->assertFalse($backup->isFailed());

        $backup = ServerBackup::factory()->create(['status' => 'failed']);
        $this->assertTrue($backup->isFailed());
        $this->assertFalse($backup->isCompleted());

        $backup = ServerBackup::factory()->create(['status' => 'running']);
        $this->assertTrue($backup->isRunning());
    }

    public function test_server_backup_status_color_accessor(): void
    {
        $backup = ServerBackup::factory()->create(['status' => 'completed']);
        $this->assertEquals('green', $backup->status_color);

        $backup = ServerBackup::factory()->create(['status' => 'running']);
        $this->assertEquals('blue', $backup->status_color);

        $backup = ServerBackup::factory()->create(['status' => 'failed']);
        $this->assertEquals('red', $backup->status_color);

        $backup = ServerBackup::factory()->create(['status' => 'pending']);
        $this->assertEquals('yellow', $backup->status_color);
    }

    // ==========================================
    // BackupSchedule Model Tests
    // ==========================================

    public function test_backup_schedule_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $schedule = BackupSchedule::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $schedule->project);
        $this->assertEquals($project->id, $schedule->project->id);
    }

    public function test_backup_schedule_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $schedule = BackupSchedule::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $schedule->server);
        $this->assertEquals($server->id, $schedule->server->id);
    }

    public function test_backup_schedule_active_scope(): void
    {
        BackupSchedule::factory()->count(2)->create(['is_active' => true]);
        BackupSchedule::factory()->create(['is_active' => false]);

        $active = BackupSchedule::active()->get();

        $this->assertCount(2, $active);
    }

    public function test_backup_schedule_due_scope(): void
    {
        BackupSchedule::factory()->create(['next_run_at' => now()->subHour()]);
        BackupSchedule::factory()->create(['next_run_at' => now()->addHour()]);

        $due = BackupSchedule::due()->get();

        $this->assertCount(1, $due);
    }

    public function test_backup_schedule_for_project_scope(): void
    {
        $project = Project::factory()->create();
        BackupSchedule::factory()->count(3)->create(['project_id' => $project->id]);
        BackupSchedule::factory()->create();

        $projectSchedules = BackupSchedule::forProject($project->id)->get();

        $this->assertCount(3, $projectSchedules);
    }

    public function test_backup_schedule_calculates_next_run_hourly(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'hourly',
            'time' => '10:00',
        ]);

        $nextRun = $schedule->calculateNextRun();

        $this->assertInstanceOf(Carbon::class, $nextRun);
        $this->assertEquals(0, $nextRun->minute);
    }

    public function test_backup_schedule_calculates_next_run_daily(): void
    {
        Carbon::setTestNow('2025-01-15 08:00:00');

        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'daily',
            'time' => '10:00',
        ]);

        $nextRun = $schedule->calculateNextRun();

        $this->assertEquals('10:00', $nextRun->format('H:i'));
    }

    public function test_backup_schedule_calculates_next_run_weekly(): void
    {
        Carbon::setTestNow('2025-01-15 10:00:00'); // Wednesday

        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'weekly',
            'time' => '14:00',
            'day_of_week' => 1, // Monday
        ]);

        $nextRun = $schedule->calculateNextRun();

        $this->assertEquals(1, $nextRun->dayOfWeek); // Monday
        $this->assertEquals('14:00', $nextRun->format('H:i'));
    }

    public function test_backup_schedule_calculates_next_run_monthly(): void
    {
        Carbon::setTestNow('2025-01-15 10:00:00');

        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'monthly',
            'time' => '09:00',
            'day_of_month' => 5,
        ]);

        $nextRun = $schedule->calculateNextRun();

        $this->assertEquals(5, $nextRun->day);
        $this->assertEquals('09:00', $nextRun->format('H:i'));
    }

    public function test_backup_schedule_updates_next_run(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'daily',
            'time' => '10:00',
            'last_run_at' => null,
        ]);

        $originalNextRun = $schedule->next_run_at;

        $schedule->updateNextRun();
        $schedule->refresh();

        $this->assertNotNull($schedule->last_run_at);
        $this->assertNotEquals($originalNextRun, $schedule->next_run_at);
    }

    public function test_backup_schedule_frequency_label_accessor(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'hourly',
        ]);
        $this->assertEquals('Every Hour', $schedule->frequency_label);

        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'daily',
            'time' => '14:00',
        ]);
        $this->assertEquals('Daily at 14:00', $schedule->frequency_label);

        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'weekly',
            'time' => '10:00',
            'day_of_week' => 1,
        ]);
        $this->assertStringContainsString('Weekly on', $schedule->frequency_label);

        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'monthly',
            'time' => '09:00',
            'day_of_month' => 15,
        ]);
        $this->assertEquals('Monthly on day 15 at 09:00', $schedule->frequency_label);
    }

    public function test_backup_schedule_sets_next_run_on_creation(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'frequency' => 'daily',
            'time' => '10:00',
        ]);

        $this->assertNotNull($schedule->next_run_at);
        $this->assertInstanceOf(Carbon::class, $schedule->next_run_at);
    }

    // ==========================================
    // StorageConfiguration Model Tests
    // ==========================================

    public function test_storage_configuration_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $config->project);
        $this->assertEquals($project->id, $config->project->id);
    }

    public function test_storage_configuration_active_scope(): void
    {
        StorageConfiguration::factory()->count(2)->create(['status' => 'active']);
        StorageConfiguration::factory()->create(['status' => 'inactive']);

        $active = StorageConfiguration::active()->get();

        $this->assertCount(2, $active);
    }

    public function test_storage_configuration_default_scope(): void
    {
        StorageConfiguration::factory()->create(['is_default' => true]);
        StorageConfiguration::factory()->count(2)->create(['is_default' => false]);

        $default = StorageConfiguration::default()->get();

        $this->assertCount(1, $default);
    }

    public function test_storage_configuration_driver_scope(): void
    {
        StorageConfiguration::factory()->count(2)->create(['driver' => 's3']);
        StorageConfiguration::factory()->create(['driver' => 'gcs']);

        $s3Configs = StorageConfiguration::driver('s3')->get();

        $this->assertCount(2, $s3Configs);
    }

    public function test_storage_configuration_encrypts_credentials(): void
    {
        $credentials = ['access_key_id' => 'test-key', 'secret_access_key' => 'test-secret'];

        $config = StorageConfiguration::factory()->create([
            'credentials' => $credentials,
        ]);

        // Raw database value should be encrypted
        $rawValue = $config->getAttributes()['credentials'];
        $this->assertNotEquals(json_encode($credentials), $rawValue);

        // Accessor should decrypt it
        $this->assertEquals($credentials, $config->credentials);
    }

    public function test_storage_configuration_get_disk_config_for_s3(): void
    {
        $config = StorageConfiguration::factory()->create([
            'driver' => 's3',
            'credentials' => [
                'access_key_id' => 'test-key',
                'secret_access_key' => 'test-secret',
            ],
            'bucket' => 'test-bucket',
            'region' => 'us-west-2',
        ]);

        $diskConfig = $config->getDiskConfig();

        $this->assertEquals('s3', $diskConfig['driver']);
        $this->assertEquals('test-key', $diskConfig['key']);
        $this->assertEquals('test-secret', $diskConfig['secret']);
        $this->assertEquals('test-bucket', $diskConfig['bucket']);
        $this->assertEquals('us-west-2', $diskConfig['region']);
    }

    public function test_storage_configuration_get_disk_config_for_gcs(): void
    {
        $config = StorageConfiguration::factory()->create([
            'driver' => 'gcs',
            'credentials' => [
                'service_account_json' => ['type' => 'service_account'],
            ],
            'bucket' => 'test-bucket',
        ]);

        $diskConfig = $config->getDiskConfig();

        $this->assertEquals('gcs', $diskConfig['driver']);
        $this->assertEquals('test-bucket', $diskConfig['bucket']);
        $this->assertIsArray($diskConfig['key_file']);
    }

    public function test_storage_configuration_get_disk_config_for_ftp(): void
    {
        $config = StorageConfiguration::factory()->create([
            'driver' => 'ftp',
            'credentials' => [
                'host' => 'ftp.example.com',
                'username' => 'user',
                'password' => 'pass',
                'port' => 21,
            ],
        ]);

        $diskConfig = $config->getDiskConfig();

        $this->assertEquals('ftp', $diskConfig['driver']);
        $this->assertEquals('ftp.example.com', $diskConfig['host']);
        $this->assertEquals('user', $diskConfig['username']);
        $this->assertEquals(21, $diskConfig['port']);
    }

    public function test_storage_configuration_driver_name_accessor(): void
    {
        $config = StorageConfiguration::factory()->create(['driver' => 's3']);
        $this->assertEquals('Amazon S3', $config->driver_name);

        $config = StorageConfiguration::factory()->create(['driver' => 'gcs']);
        $this->assertEquals('Google Cloud Storage', $config->driver_name);

        $config = StorageConfiguration::factory()->create(['driver' => 'ftp']);
        $this->assertEquals('FTP', $config->driver_name);

        $config = StorageConfiguration::factory()->create(['driver' => 'sftp']);
        $this->assertEquals('SFTP', $config->driver_name);
    }

    public function test_storage_configuration_driver_icon_accessor(): void
    {
        $config = StorageConfiguration::factory()->create(['driver' => 's3']);
        $this->assertEquals('aws', $config->driver_icon);

        $config = StorageConfiguration::factory()->create(['driver' => 'gcs']);
        $this->assertEquals('google-cloud', $config->driver_icon);
    }

    public function test_storage_configuration_is_configured_for_s3(): void
    {
        $config = StorageConfiguration::factory()->create([
            'driver' => 's3',
            'credentials' => [
                'access_key_id' => 'key',
                'secret_access_key' => 'secret',
            ],
            'bucket' => 'test-bucket',
        ]);

        $this->assertTrue($config->isConfigured());
    }

    public function test_storage_configuration_is_not_configured_when_missing_credentials(): void
    {
        $config = StorageConfiguration::factory()->create([
            'driver' => 's3',
            'credentials' => [],
            'bucket' => 'test-bucket',
        ]);

        $this->assertFalse($config->isConfigured());
    }

    public function test_storage_configuration_handles_empty_credentials_gracefully(): void
    {
        $config = StorageConfiguration::factory()->create([
            'credentials' => null,
        ]);

        $this->assertIsArray($config->credentials);
        $this->assertEmpty($config->credentials);
    }
}
