<?php

declare(strict_types=1);

namespace Tests\Unit\Models;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Tests\TestCase;

class CoreModelsTest extends TestCase
{

    // ========================================
    // USER MODEL TESTS
    // ========================================

    #[Test]
    public function user_factory_creates_user(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
        $this->assertNotNull($user->password);
    }

    #[Test]
    public function user_has_projects_relationship(): void
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->projects);
        $this->assertInstanceOf(Project::class, $user->projects->first());
    }

    #[Test]
    public function user_has_servers_relationship(): void
    {
        $user = User::factory()->create();
        $servers = Server::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->servers);
        $this->assertInstanceOf(Server::class, $user->servers->first());
    }

    #[Test]
    public function user_has_deployments_relationship(): void
    {
        $user = User::factory()->create();
        $deployments = Deployment::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->deployments);
        $this->assertInstanceOf(Deployment::class, $user->deployments->first());
    }

    #[Test]
    public function user_has_ssh_keys_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->sshKeys());
    }

    #[Test]
    public function user_has_server_tags_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->serverTags());
    }

    #[Test]
    public function user_has_api_tokens_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->apiTokens());
    }

    #[Test]
    public function user_has_teams_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->teams());
    }

    #[Test]
    public function user_has_owned_teams_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->ownedTeams());
    }

    #[Test]
    public function user_has_current_team_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $user->currentTeam());
    }

    #[Test]
    public function user_has_team_invitations_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->teamInvitations());
    }

    #[Test]
    public function user_has_settings_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $user->settings());
    }

    #[Test]
    public function user_has_requested_approvals_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->requestedApprovals());
    }

    #[Test]
    public function user_has_approved_deployments_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->approvedDeployments());
    }

    #[Test]
    public function user_has_deployment_comments_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->deploymentComments());
    }

    #[Test]
    public function user_has_audit_logs_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->auditLogs());
    }

    #[Test]
    public function user_avatar_url_accessor_returns_default_when_no_avatar(): void
    {
        $user = User::factory()->create(['avatar' => null, 'name' => 'John Doe']);

        $this->assertStringContainsString('ui-avatars.com', $user->avatar_url);
        $this->assertStringContainsString('John+Doe', $user->avatar_url);
    }

    #[Test]
    public function user_avatar_url_accessor_returns_storage_path_when_avatar_exists(): void
    {
        $user = User::factory()->create(['avatar' => 'avatars/test.jpg']);

        $this->assertStringContainsString('storage/avatars/test.jpg', $user->avatar_url);
    }

    #[Test]
    public function user_casts_dates_correctly(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'last_login_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->last_login_at);
    }

    #[Test]
    public function user_hides_sensitive_attributes(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    // ========================================
    // SERVER MODEL TESTS
    // ========================================

    #[Test]
    public function server_factory_creates_server(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(Server::class, $server);
        $this->assertNotNull($server->name);
        $this->assertNotNull($server->ip_address);
    }

    #[Test]
    public function server_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $server->user);
        $this->assertEquals($user->id, $server->user->id);
    }

    #[Test]
    public function server_belongs_to_team(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $server->team());
    }

    #[Test]
    public function server_has_projects_relationship(): void
    {
        $server = Server::factory()->create();
        $projects = Project::factory()->count(3)->create(['server_id' => $server->id]);

        $this->assertCount(3, $server->projects);
        $this->assertInstanceOf(Project::class, $server->projects->first());
    }

    #[Test]
    public function server_has_deployments_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $server->deployments());
    }

    #[Test]
    public function server_has_metrics_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $server->metrics());
    }

    #[Test]
    public function server_has_ssh_keys_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $server->sshKeys());
    }

    #[Test]
    public function server_has_tags_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $server->tags());
    }

    #[Test]
    public function server_has_ssl_certificates_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $server->sslCertificates());
    }

    #[Test]
    public function server_has_resource_alerts_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $server->resourceAlerts());
    }

    #[Test]
    public function server_has_alert_history_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $server->alertHistory());
    }

    #[Test]
    public function server_has_backups_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $server->backups());
    }

    #[Test]
    public function server_has_backup_schedules_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $server->backupSchedules());
    }

    #[Test]
    public function server_has_firewall_rules_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $server->firewallRules());
    }

    #[Test]
    public function server_has_security_events_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $server->securityEvents());
    }

    #[Test]
    public function server_has_ssh_configuration_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $server->sshConfiguration());
    }

    #[Test]
    public function server_has_security_scans_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $server->securityScans());
    }

    #[Test]
    public function server_has_latest_security_scan_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $server->latestSecurityScan());
    }

    #[Test]
    public function server_has_latest_metric_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $server->latestMetric());
    }

    #[Test]
    public function server_has_provisioning_logs_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $server->provisioningLogs());
    }

    #[Test]
    public function server_has_latest_provisioning_log_relationship(): void
    {
        $server = Server::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $server->latestProvisioningLog());
    }

    #[Test]
    public function server_is_online_helper_works(): void
    {
        $onlineServer = Server::factory()->create(['status' => 'online']);
        $offlineServer = Server::factory()->create(['status' => 'offline']);

        $this->assertTrue($onlineServer->isOnline());
        $this->assertFalse($offlineServer->isOnline());
    }

    #[Test]
    public function server_is_offline_helper_works(): void
    {
        $offlineServer = Server::factory()->create(['status' => 'offline']);
        $onlineServer = Server::factory()->create(['status' => 'online']);

        $this->assertTrue($offlineServer->isOffline());
        $this->assertFalse($onlineServer->isOffline());
    }

    #[Test]
    public function server_status_color_accessor_returns_correct_colors(): void
    {
        $onlineServer = Server::factory()->create(['status' => 'online']);
        $offlineServer = Server::factory()->create(['status' => 'offline']);
        $maintenanceServer = Server::factory()->create(['status' => 'maintenance']);
        // Use make() for unknown status as it's not in the enum
        $unknownServer = Server::factory()->make(['status' => 'unknown']);

        $this->assertEquals('green', $onlineServer->status_color);
        $this->assertEquals('red', $offlineServer->status_color);
        $this->assertEquals('yellow', $maintenanceServer->status_color);
        $this->assertEquals('gray', $unknownServer->status_color);
    }

    #[Test]
    public function server_security_score_color_accessor_returns_correct_colors(): void
    {
        $excellentServer = Server::factory()->create(['security_score' => 95]);
        $goodServer = Server::factory()->create(['security_score' => 85]);
        $mediumServer = Server::factory()->create(['security_score' => 70]);
        $lowServer = Server::factory()->create(['security_score' => 50]);
        $criticalServer = Server::factory()->create(['security_score' => 30]);
        $unknownServer = Server::factory()->create(['security_score' => null]);

        $this->assertEquals('emerald', $excellentServer->security_score_color);
        $this->assertEquals('green', $goodServer->security_score_color);
        $this->assertEquals('yellow', $mediumServer->security_score_color);
        $this->assertEquals('orange', $lowServer->security_score_color);
        $this->assertEquals('red', $criticalServer->security_score_color);
        $this->assertEquals('gray', $unknownServer->security_score_color);
    }

    #[Test]
    public function server_security_risk_level_accessor_returns_correct_levels(): void
    {
        $excellentServer = Server::factory()->create(['security_score' => 95]);
        $goodServer = Server::factory()->create(['security_score' => 85]);
        $mediumServer = Server::factory()->create(['security_score' => 70]);
        $lowServer = Server::factory()->create(['security_score' => 50]);
        $criticalServer = Server::factory()->create(['security_score' => 30]);
        $unknownServer = Server::factory()->create(['security_score' => null]);

        $this->assertEquals('secure', $excellentServer->security_risk_level);
        $this->assertEquals('low', $goodServer->security_risk_level);
        $this->assertEquals('medium', $mediumServer->security_risk_level);
        $this->assertEquals('high', $lowServer->security_risk_level);
        $this->assertEquals('critical', $criticalServer->security_risk_level);
        $this->assertEquals('unknown', $unknownServer->security_risk_level);
    }

    #[Test]
    public function server_is_provisioned_helper_works(): void
    {
        $provisionedServer = Server::factory()->create(['provision_status' => 'completed']);
        $unprovisionedServer = Server::factory()->create(['provision_status' => 'pending']);

        $this->assertTrue($provisionedServer->isProvisioned());
        $this->assertFalse($unprovisionedServer->isProvisioned());
    }

    #[Test]
    public function server_is_provisioning_helper_works(): void
    {
        $provisioningServer = Server::factory()->create(['provision_status' => 'provisioning']);
        $completedServer = Server::factory()->create(['provision_status' => 'completed']);

        $this->assertTrue($provisioningServer->isProvisioning());
        $this->assertFalse($completedServer->isProvisioning());
    }

    #[Test]
    public function server_has_package_installed_helper_works(): void
    {
        $server = Server::factory()->create([
            'installed_packages' => ['docker', 'nginx', 'mysql'],
        ]);

        $this->assertTrue($server->hasPackageInstalled('docker'));
        $this->assertTrue($server->hasPackageInstalled('nginx'));
        $this->assertFalse($server->hasPackageInstalled('postgresql'));
    }

    #[Test]
    public function server_has_package_installed_returns_false_when_packages_null(): void
    {
        $server = Server::factory()->create(['installed_packages' => null]);

        $this->assertFalse($server->hasPackageInstalled('docker'));
    }

    #[Test]
    public function server_casts_attributes_correctly(): void
    {
        $server = Server::factory()->create([
            'docker_installed' => true,
            'ufw_installed' => true,
            'fail2ban_enabled' => false,
            'security_score' => 85,
            'cpu_cores' => 4,
            'memory_gb' => 16,
            'installed_packages' => ['nginx', 'docker'],
            'metadata' => ['key' => 'value'],
            'last_security_scan_at' => now(),
        ]);

        $this->assertIsBool($server->docker_installed);
        $this->assertIsBool($server->ufw_installed);
        $this->assertIsBool($server->fail2ban_enabled);
        $this->assertIsInt($server->security_score);
        $this->assertIsInt($server->cpu_cores);
        $this->assertIsArray($server->installed_packages);
        $this->assertIsArray($server->metadata);
        $this->assertInstanceOf(\Carbon\Carbon::class, $server->last_security_scan_at);
    }

    #[Test]
    public function server_hides_sensitive_attributes(): void
    {
        $server = Server::factory()->create([
            'ssh_key' => 'secret-key',
            'ssh_password' => 'secret-password',
        ]);
        $array = $server->toArray();

        $this->assertArrayNotHasKey('ssh_key', $array);
        $this->assertArrayNotHasKey('ssh_password', $array);
    }

    #[Test]
    public function server_factory_online_state_works(): void
    {
        $server = Server::factory()->online()->create();

        $this->assertEquals('online', $server->status);
        $this->assertNotNull($server->last_ping_at);
    }

    #[Test]
    public function server_factory_offline_state_works(): void
    {
        $server = Server::factory()->offline()->create();

        $this->assertEquals('offline', $server->status);
    }

    #[Test]
    public function server_soft_deletes_work(): void
    {
        $server = Server::factory()->create();
        $serverId = $server->id;

        $server->delete();

        $this->assertSoftDeleted('servers', ['id' => $serverId]);
        $this->assertNotNull($server->deleted_at);
    }

    // ========================================
    // PROJECT MODEL TESTS
    // ========================================

    #[Test]
    public function project_factory_creates_project(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(Project::class, $project);
        $this->assertNotNull($project->name);
        $this->assertNotNull($project->slug);
        $this->assertNotNull($project->repository_url);
    }

    #[Test]
    public function project_uses_slug_as_route_key(): void
    {
        $project = Project::factory()->create(['slug' => 'test-project']);

        $this->assertEquals('slug', $project->getRouteKeyName());
    }

    #[Test]
    public function project_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $project->user);
        $this->assertEquals($user->id, $project->user->id);
    }

    #[Test]
    public function project_belongs_to_team(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $project->team());
    }

    #[Test]
    public function project_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $project->server);
        $this->assertEquals($server->id, $project->server->id);
    }

    #[Test]
    public function project_belongs_to_template(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $project->template());
    }

    #[Test]
    public function project_has_deployments_relationship(): void
    {
        $project = Project::factory()->create();
        $deployments = Deployment::factory()->count(3)->create(['project_id' => $project->id]);

        $this->assertCount(3, $project->deployments);
        $this->assertInstanceOf(Deployment::class, $project->deployments->first());
    }

    #[Test]
    public function project_has_domains_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->domains());
    }

    #[Test]
    public function project_has_analytics_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->analytics());
    }

    #[Test]
    public function project_has_tenants_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->tenants());
    }

    #[Test]
    public function project_has_pipelines_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->pipelines());
    }

    #[Test]
    public function project_has_storage_configurations_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->storageConfigurations());
    }

    #[Test]
    public function project_has_webhook_deliveries_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->webhookDeliveries());
    }

    #[Test]
    public function project_has_database_backups_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->databaseBackups());
    }

    #[Test]
    public function project_has_backup_schedules_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->backupSchedules());
    }

    #[Test]
    public function project_has_file_backups_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->fileBackups());
    }

    #[Test]
    public function project_has_setup_tasks_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->setupTasks());
    }

    #[Test]
    public function project_has_pipeline_stages_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->pipelineStages());
    }

    #[Test]
    public function project_has_pipeline_config_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $project->pipelineConfig());
    }

    #[Test]
    public function project_has_notification_channels_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->notificationChannels());
    }

    #[Test]
    public function project_has_audit_logs_morph_relationship(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $project->auditLogs());
    }

    #[Test]
    public function project_is_setup_pending_helper_works(): void
    {
        $project = Project::factory()->create(['setup_status' => 'pending']);
        $completedProject = Project::factory()->create(['setup_status' => 'completed']);

        $this->assertTrue($project->isSetupPending());
        $this->assertFalse($completedProject->isSetupPending());
    }

    #[Test]
    public function project_is_setup_in_progress_helper_works(): void
    {
        $project = Project::factory()->create(['setup_status' => 'in_progress']);
        $completedProject = Project::factory()->create(['setup_status' => 'completed']);

        $this->assertTrue($project->isSetupInProgress());
        $this->assertFalse($completedProject->isSetupInProgress());
    }

    #[Test]
    public function project_is_setup_completed_helper_works(): void
    {
        $project = Project::factory()->create(['setup_status' => 'completed']);
        $pendingProject = Project::factory()->create(['setup_status' => 'pending']);

        $this->assertTrue($project->isSetupCompleted());
        $this->assertFalse($pendingProject->isSetupCompleted());
    }

    #[Test]
    public function project_is_setup_failed_helper_works(): void
    {
        $project = Project::factory()->create(['setup_status' => 'failed']);
        $completedProject = Project::factory()->create(['setup_status' => 'completed']);

        $this->assertTrue($project->isSetupFailed());
        $this->assertFalse($completedProject->isSetupFailed());
    }

    #[Test]
    public function project_generate_webhook_secret_creates_64_char_hex(): void
    {
        $project = Project::factory()->create();
        $secret = $project->generateWebhookSecret();

        $this->assertEquals(64, strlen($secret));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $secret);
    }

    #[Test]
    public function project_is_running_helper_works(): void
    {
        $runningProject = Project::factory()->create(['status' => 'running']);
        $stoppedProject = Project::factory()->create(['status' => 'stopped']);

        $this->assertTrue($runningProject->isRunning());
        $this->assertFalse($stoppedProject->isRunning());
    }

    #[Test]
    public function project_is_stopped_helper_works(): void
    {
        $stoppedProject = Project::factory()->create(['status' => 'stopped']);
        $runningProject = Project::factory()->create(['status' => 'running']);

        $this->assertTrue($stoppedProject->isStopped());
        $this->assertFalse($runningProject->isStopped());
    }

    #[Test]
    public function project_status_color_accessor_returns_correct_colors(): void
    {
        $runningProject = Project::factory()->create(['status' => 'running']);
        $stoppedProject = Project::factory()->create(['status' => 'stopped']);
        $buildingProject = Project::factory()->create(['status' => 'building']);
        $errorProject = Project::factory()->create(['status' => 'error']);
        // Use make() for unknown status as it's not in the enum
        $unknownProject = Project::factory()->make(['status' => 'unknown']);

        // Using HealthScoreMapper::statusToColor() - check actual mappings
        $this->assertEquals('green', $runningProject->status_color);
        $this->assertEquals('orange', $stoppedProject->status_color);  // stopped maps to orange
        $this->assertEquals('gray', $buildingProject->status_color);   // building not mapped, defaults to gray
        $this->assertEquals('red', $errorProject->status_color);
        $this->assertEquals('gray', $unknownProject->status_color);
    }

    #[Test]
    public function project_latest_deployment_relationship_works(): void
    {
        $project = Project::factory()->create();
        $oldDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'created_at' => now()->subDays(2),
        ]);
        $latestDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'created_at' => now(),
        ]);

        // Use latestDeployment relationship instead of getLatestDeployment method
        $result = $project->latestDeployment;

        $this->assertEquals($latestDeployment->id, $result->id);
    }

    #[Test]
    public function project_casts_attributes_correctly(): void
    {
        $project = Project::factory()->create([
            'env_variables' => ['APP_ENV' => 'production'],
            'metadata' => ['key' => 'value'],
            'install_commands' => ['npm install'],
            'auto_deploy' => true,
            'webhook_enabled' => false,
            'last_deployed_at' => now(),
        ]);

        $this->assertIsArray($project->env_variables);
        $this->assertIsArray($project->metadata);
        $this->assertIsArray($project->install_commands);
        $this->assertIsBool($project->auto_deploy);
        $this->assertIsBool($project->webhook_enabled);
        $this->assertInstanceOf(\Carbon\Carbon::class, $project->last_deployed_at);
    }

    #[Test]
    public function project_factory_running_state_works(): void
    {
        $project = Project::factory()->running()->create();

        $this->assertEquals('running', $project->status);
        $this->assertNotNull($project->last_deployed_at);
    }

    #[Test]
    public function project_factory_stopped_state_works(): void
    {
        $project = Project::factory()->stopped()->create();

        $this->assertEquals('stopped', $project->status);
    }

    #[Test]
    public function project_factory_laravel_state_works(): void
    {
        $project = Project::factory()->laravel()->create();

        $this->assertEquals('laravel', $project->framework);
        $this->assertEquals('8.4', $project->php_version);
    }

    #[Test]
    public function project_soft_deletes_work(): void
    {
        $project = Project::factory()->create();
        $projectId = $project->id;

        $project->delete();

        $this->assertSoftDeleted('projects', ['id' => $projectId]);
        $this->assertNotNull($project->deleted_at);
    }

    #[Test]
    public function project_notes_field_is_fillable(): void
    {
        $project = Project::factory()->create(['notes' => 'Test notes content']);

        $this->assertEquals('Test notes content', $project->notes);
    }

    #[Test]
    public function project_notes_are_sanitized_for_xss(): void
    {
        $project = Project::factory()->create([
            'notes' => '<script>alert("xss")</script>Safe notes content',
        ]);

        $this->assertStringNotContainsString('<script>', $project->notes);
        $this->assertStringContainsString('Safe notes content', $project->notes);
    }

    #[Test]
    public function project_notes_can_be_null(): void
    {
        $project = Project::factory()->create(['notes' => null]);

        $this->assertNull($project->notes);
    }

    #[Test]
    public function project_notes_strips_html_tags(): void
    {
        $project = Project::factory()->create([
            'notes' => '<div><b>Bold</b> and <i>italic</i> text</div>',
        ]);

        $this->assertEquals('Bold and italic text', $project->notes);
    }

    // ========================================
    // DEPLOYMENT MODEL TESTS
    // ========================================

    #[Test]
    public function deployment_factory_creates_deployment(): void
    {
        $deployment = Deployment::factory()->create();

        $this->assertInstanceOf(Deployment::class, $deployment);
        $this->assertNotNull($deployment->commit_hash);
        $this->assertNotNull($deployment->status);
    }

    #[Test]
    public function deployment_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $deployment = Deployment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $deployment->user);
        $this->assertEquals($user->id, $deployment->user->id);
    }

    #[Test]
    public function deployment_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $deployment->project);
        $this->assertEquals($project->id, $deployment->project->id);
    }

    #[Test]
    public function deployment_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $deployment = Deployment::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $deployment->server);
        $this->assertEquals($server->id, $deployment->server->id);
    }

    #[Test]
    public function deployment_has_rollback_of_relationship(): void
    {
        $originalDeployment = Deployment::factory()->create();
        $rollbackDeployment = Deployment::factory()->create([
            'rollback_deployment_id' => $originalDeployment->id,
        ]);

        $this->assertInstanceOf(Deployment::class, $rollbackDeployment->rollbackOf);
        $this->assertEquals($originalDeployment->id, $rollbackDeployment->rollbackOf->id);
    }

    #[Test]
    public function deployment_has_rollbacks_relationship(): void
    {
        $deployment = Deployment::factory()->create();
        $rollback1 = Deployment::factory()->create(['rollback_deployment_id' => $deployment->id]);
        $rollback2 = Deployment::factory()->create(['rollback_deployment_id' => $deployment->id]);

        $this->assertCount(2, $deployment->rollbacks);
        $this->assertInstanceOf(Deployment::class, $deployment->rollbacks->first());
    }

    #[Test]
    public function deployment_has_approvals_relationship(): void
    {
        $deployment = Deployment::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $deployment->approvals());
    }

    #[Test]
    public function deployment_has_pending_approval_relationship(): void
    {
        $deployment = Deployment::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $deployment->pendingApproval());
    }

    #[Test]
    public function deployment_has_comments_relationship(): void
    {
        $deployment = Deployment::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $deployment->comments());
    }

    #[Test]
    public function deployment_has_audit_logs_morph_relationship(): void
    {
        $deployment = Deployment::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $deployment->auditLogs());
    }

    #[Test]
    public function deployment_is_running_helper_works(): void
    {
        $runningDeployment = Deployment::factory()->create(['status' => 'running']);
        $successDeployment = Deployment::factory()->create(['status' => 'success']);

        $this->assertTrue($runningDeployment->isRunning());
        $this->assertFalse($successDeployment->isRunning());
    }

    #[Test]
    public function deployment_is_success_helper_works(): void
    {
        $successDeployment = Deployment::factory()->create(['status' => 'success']);
        $failedDeployment = Deployment::factory()->create(['status' => 'failed']);

        $this->assertTrue($successDeployment->isSuccess());
        $this->assertFalse($failedDeployment->isSuccess());
    }

    #[Test]
    public function deployment_is_failed_helper_works(): void
    {
        $failedDeployment = Deployment::factory()->create(['status' => 'failed']);
        $successDeployment = Deployment::factory()->create(['status' => 'success']);

        $this->assertTrue($failedDeployment->isFailed());
        $this->assertFalse($successDeployment->isFailed());
    }

    #[Test]
    public function deployment_status_color_accessor_returns_correct_colors(): void
    {
        $successDeployment = Deployment::factory()->create(['status' => 'success']);
        $failedDeployment = Deployment::factory()->create(['status' => 'failed']);
        $runningDeployment = Deployment::factory()->create(['status' => 'running']);
        $pendingDeployment = Deployment::factory()->create(['status' => 'pending']);
        // Use make() for unknown status as it's not in the enum
        $unknownDeployment = Deployment::factory()->make(['status' => 'unknown']);

        // Using HealthScoreMapper::statusToColor() - check actual mappings
        $this->assertEquals('green', $successDeployment->status_color);
        $this->assertEquals('red', $failedDeployment->status_color);
        $this->assertEquals('green', $runningDeployment->status_color);  // running maps to green
        $this->assertEquals('gray', $pendingDeployment->status_color);   // pending not mapped, defaults to gray
        $this->assertEquals('gray', $unknownDeployment->status_color);
    }

    #[Test]
    public function deployment_status_icon_accessor_returns_correct_icons(): void
    {
        $successDeployment = Deployment::factory()->create(['status' => 'success']);
        $failedDeployment = Deployment::factory()->create(['status' => 'failed']);
        $runningDeployment = Deployment::factory()->create(['status' => 'running']);
        $pendingDeployment = Deployment::factory()->create(['status' => 'pending']);
        // Use make() for unknown status as it's not in the enum
        $unknownDeployment = Deployment::factory()->make(['status' => 'unknown']);

        $this->assertEquals('check-circle', $successDeployment->status_icon);
        $this->assertEquals('x-circle', $failedDeployment->status_icon);
        $this->assertEquals('arrow-path', $runningDeployment->status_icon);
        // 'pending' is not mapped in HealthScoreMapper, falls to default icon
        $this->assertEquals('question-mark-circle', $pendingDeployment->status_icon);
        $this->assertEquals('question-mark-circle', $unknownDeployment->status_icon);
    }

    #[Test]
    public function deployment_requires_approval_helper_works_when_project_requires_approval(): void
    {
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $this->assertTrue($deployment->requiresApproval());
    }

    #[Test]
    public function deployment_requires_approval_helper_works_when_project_does_not_require_approval(): void
    {
        $project = Project::factory()->create(['requires_approval' => false]);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $this->assertFalse($deployment->requiresApproval());
    }

    #[Test]
    public function deployment_casts_attributes_correctly(): void
    {
        $deployment = Deployment::factory()->create([
            'metadata' => ['key' => 'value'],
            'environment_snapshot' => ['APP_ENV' => 'production'],
            'started_at' => now(),
            'completed_at' => now()->addMinutes(5),
            'duration_seconds' => 300,
        ]);

        $this->assertIsArray($deployment->metadata);
        $this->assertIsArray($deployment->environment_snapshot);
        $this->assertInstanceOf(\Carbon\Carbon::class, $deployment->started_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $deployment->completed_at);
        $this->assertIsInt($deployment->duration_seconds);
    }

    #[Test]
    public function deployment_factory_success_state_works(): void
    {
        $deployment = Deployment::factory()->success()->create();

        $this->assertEquals('success', $deployment->status);
        $this->assertNotNull($deployment->completed_at);
        $this->assertNotNull($deployment->duration_seconds);
        $this->assertNull($deployment->error_message);
    }

    #[Test]
    public function deployment_factory_failed_state_works(): void
    {
        $deployment = Deployment::factory()->failed()->create();

        $this->assertEquals('failed', $deployment->status);
        $this->assertNotNull($deployment->completed_at);
        $this->assertNotNull($deployment->error_log);
    }

    #[Test]
    public function deployment_factory_pending_state_works(): void
    {
        $deployment = Deployment::factory()->pending()->create();

        $this->assertEquals('pending', $deployment->status);
        $this->assertNull($deployment->started_at);
        $this->assertNull($deployment->completed_at);
    }

    #[Test]
    public function deployment_factory_running_state_works(): void
    {
        $deployment = Deployment::factory()->running()->create();

        $this->assertEquals('running', $deployment->status);
        $this->assertNotNull($deployment->started_at);
        $this->assertNull($deployment->completed_at);
    }
}
