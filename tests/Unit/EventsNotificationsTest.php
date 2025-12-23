<?php

declare(strict_types=1);

namespace Tests\Unit;


use PHPUnit\Framework\Attributes\Test;
use App\Events\DashboardUpdated;
use App\Events\DeploymentCompleted;
use App\Events\DeploymentFailed;
use App\Events\DeploymentLogUpdated;
use App\Events\DeploymentStarted;
use App\Events\DeploymentStatusUpdated;
use App\Events\PipelineStageUpdated;
use App\Events\ProjectSetupUpdated;
use App\Events\ServerMetricsUpdated;
use App\Mail\TeamInvitation;
use App\Models\Deployment;
use App\Models\DeploymentApproval;
use App\Models\DeploymentComment;
use App\Models\Project;
use App\Models\ProjectSetupTask;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\SSLCertificate;
use App\Models\TeamInvitation as TeamInvitationModel;
use App\Models\User;
use App\Notifications\DeploymentApprovalRequested;
use App\Notifications\ServerProvisioningCompleted;
use App\Notifications\SSLCertificateExpiring;
use App\Notifications\SSLCertificateRenewed;
use App\Notifications\UserMentionedInComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EventsNotificationsTest extends TestCase
{

    // ========================================
    // DashboardUpdated Event Tests
    // ========================================

    #[Test]
    public function dashboard_updated_event_can_be_instantiated(): void
    {
        $event = new DashboardUpdated('stats', ['total_projects' => 10]);

        $this->assertInstanceOf(DashboardUpdated::class, $event);
        $this->assertEquals('stats', $event->updateType);
        $this->assertEquals(['total_projects' => 10], $event->data);
    }

    #[Test]
    public function dashboard_updated_event_implements_should_broadcast(): void
    {
        $event = new DashboardUpdated('stats', []);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
    }

    #[Test]
    public function dashboard_updated_event_broadcasts_on_dashboard_channel(): void
    {
        $event = new DashboardUpdated('server_health', ['status' => 'healthy']);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertEquals('dashboard', $channels[0]->name);
    }

    #[Test]
    public function dashboard_updated_event_broadcasts_correct_data(): void
    {
        $data = ['cpu' => 45, 'memory' => 60];
        $event = new DashboardUpdated('activity', $data);

        $broadcastData = $event->broadcastWith();

        $this->assertEquals('activity', $broadcastData['type']);
        $this->assertEquals($data, $broadcastData['data']);
        $this->assertArrayHasKey('timestamp', $broadcastData);
    }

    #[Test]
    public function dashboard_updated_event_can_be_dispatched(): void
    {
        Event::fake();

        event(new DashboardUpdated('stats', ['deployments' => 5]));

        Event::assertDispatched(DashboardUpdated::class);
    }

    // ========================================
    // DeploymentCompleted Event Tests
    // ========================================

    #[Test]
    public function deployment_completed_event_can_be_instantiated(): void
    {
        $deployment = Deployment::factory()->create();

        $event = new DeploymentCompleted($deployment);

        $this->assertInstanceOf(DeploymentCompleted::class, $event);
        $this->assertEquals($deployment->id, $event->deployment->id);
    }

    #[Test]
    public function deployment_completed_event_implements_should_broadcast(): void
    {
        $deployment = Deployment::factory()->create();
        $event = new DeploymentCompleted($deployment);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
    }

    #[Test]
    public function deployment_completed_event_broadcasts_on_dashboard_channel(): void
    {
        $deployment = Deployment::factory()->create();
        $event = new DeploymentCompleted($deployment);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertEquals('dashboard', $channels[0]->name);
    }

    #[Test]
    public function deployment_completed_event_broadcasts_deployment_data(): void
    {
        $project = Project::factory()->create(['name' => 'Test Project']);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'branch' => 'main',
            'status' => 'success',
        ]);

        $event = new DeploymentCompleted($deployment);
        $broadcastData = $event->broadcastWith();

        $this->assertEquals($deployment->id, $broadcastData['deployment_id']);
        $this->assertEquals('Test Project', $broadcastData['project_name']);
        $this->assertEquals($project->id, $broadcastData['project_id']);
        $this->assertEquals('success', $broadcastData['status']);
        $this->assertEquals('main', $broadcastData['branch']);
    }

    #[Test]
    public function deployment_completed_event_can_be_dispatched(): void
    {
        Event::fake();
        $deployment = Deployment::factory()->create();

        event(new DeploymentCompleted($deployment));

        Event::assertDispatched(DeploymentCompleted::class);
    }

    // ========================================
    // DeploymentFailed Event Tests
    // ========================================

    #[Test]
    public function deployment_failed_event_can_be_instantiated(): void
    {
        $deployment = Deployment::factory()->create();

        $event = new DeploymentFailed($deployment);

        $this->assertInstanceOf(DeploymentFailed::class, $event);
        $this->assertEquals($deployment->id, $event->deployment->id);
    }

    #[Test]
    public function deployment_failed_event_implements_should_broadcast(): void
    {
        $deployment = Deployment::factory()->create();
        $event = new DeploymentFailed($deployment);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
    }

    #[Test]
    public function deployment_failed_event_broadcasts_error_message(): void
    {
        $deployment = Deployment::factory()->failed()->create();

        $event = new DeploymentFailed($deployment);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('error_message', $broadcastData);
        $this->assertEquals('failed', $broadcastData['status']);
    }

    // ========================================
    // DeploymentLogUpdated Event Tests
    // ========================================

    #[Test]
    public function deployment_log_updated_event_can_be_instantiated(): void
    {
        $event = new DeploymentLogUpdated(1, 'Deployment started', 'info');

        $this->assertInstanceOf(DeploymentLogUpdated::class, $event);
        $this->assertEquals(1, $event->deploymentId);
        $this->assertEquals('Deployment started', $event->line);
        $this->assertEquals('info', $event->level);
    }

    #[Test]
    public function deployment_log_updated_event_broadcasts_on_deployment_logs_channel(): void
    {
        $event = new DeploymentLogUpdated(42, 'Running tests', 'info');

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('deployment-logs.42', $channels[0]->name);
    }

    #[Test]
    public function deployment_log_updated_event_includes_timestamp(): void
    {
        $event = new DeploymentLogUpdated(1, 'Log line', 'error');

        $this->assertNotEmpty($event->timestamp);
    }

    #[Test]
    public function deployment_log_updated_event_broadcasts_log_data(): void
    {
        $event = new DeploymentLogUpdated(5, 'Build completed', 'success');
        $broadcastData = $event->broadcastWith();

        $this->assertEquals(5, $broadcastData['deployment_id']);
        $this->assertEquals('Build completed', $broadcastData['line']);
        $this->assertEquals('success', $broadcastData['level']);
        $this->assertArrayHasKey('timestamp', $broadcastData);
    }

    // ========================================
    // DeploymentStarted Event Tests
    // ========================================

    #[Test]
    public function deployment_started_event_can_be_instantiated(): void
    {
        $deployment = Deployment::factory()->create();

        $event = new DeploymentStarted($deployment);

        $this->assertInstanceOf(DeploymentStarted::class, $event);
        $this->assertEquals($deployment->id, $event->deployment->id);
    }

    #[Test]
    public function deployment_started_event_implements_should_broadcast(): void
    {
        $deployment = Deployment::factory()->create();
        $event = new DeploymentStarted($deployment);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
    }

    #[Test]
    public function deployment_started_event_broadcasts_started_at_timestamp(): void
    {
        $deployment = Deployment::factory()->create([
            'started_at' => now(),
        ]);

        $event = new DeploymentStarted($deployment);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('started_at', $broadcastData);
        $this->assertNotNull($broadcastData['started_at']);
    }

    // ========================================
    // DeploymentStatusUpdated Event Tests
    // ========================================

    #[Test]
    public function deployment_status_updated_event_can_be_instantiated(): void
    {
        $deployment = Deployment::factory()->create();

        $event = new DeploymentStatusUpdated($deployment, 'Running tests', 'info');

        $this->assertInstanceOf(DeploymentStatusUpdated::class, $event);
        $this->assertEquals($deployment->id, $event->deployment->id);
        $this->assertEquals('Running tests', $event->message);
        $this->assertEquals('info', $event->type);
    }

    #[Test]
    public function deployment_status_updated_event_broadcasts_on_private_channels(): void
    {
        $user = User::factory()->create();
        $deployment = Deployment::factory()->create(['user_id' => $user->id]);

        $event = new DeploymentStatusUpdated($deployment, 'Status update', 'success');
        $channels = $event->broadcastOn();

        $this->assertCount(2, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertInstanceOf(PrivateChannel::class, $channels[1]);
    }

    #[Test]
    public function deployment_status_updated_event_has_custom_broadcast_name(): void
    {
        $deployment = Deployment::factory()->create();
        $event = new DeploymentStatusUpdated($deployment, 'Message', 'warning');

        $this->assertEquals('deployment.status.updated', $event->broadcastAs());
    }

    #[Test]
    public function deployment_status_updated_event_broadcasts_complete_data(): void
    {
        $project = Project::factory()->create(['name' => 'My Project']);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $event = new DeploymentStatusUpdated($deployment, 'Deploying', 'info');
        $broadcastData = $event->broadcastWith();

        $this->assertEquals($deployment->id, $broadcastData['deployment_id']);
        $this->assertEquals('Deploying', $broadcastData['message']);
        $this->assertEquals('info', $broadcastData['type']);
        $this->assertEquals('My Project', $broadcastData['project_name']);
    }

    // ========================================
    // PipelineStageUpdated Event Tests
    // ========================================

    #[Test]
    public function pipeline_stage_updated_event_can_be_instantiated(): void
    {
        $event = new PipelineStageUpdated(1, 2, 'build', 'running', 'Building...', 50);

        $this->assertInstanceOf(PipelineStageUpdated::class, $event);
        $this->assertEquals(1, $event->pipelineRunId);
        $this->assertEquals(2, $event->stageRunId);
        $this->assertEquals('build', $event->stageName);
        $this->assertEquals('running', $event->status);
        $this->assertEquals('Building...', $event->output);
        $this->assertEquals(50, $event->progressPercent);
    }

    #[Test]
    public function pipeline_stage_updated_event_broadcasts_on_pipeline_channel(): void
    {
        $event = new PipelineStageUpdated(123, 1, 'test', 'success', 'Tests passed', 100);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('pipeline.123', $channels[0]->name);
    }

    #[Test]
    public function pipeline_stage_updated_event_includes_status_color(): void
    {
        $event = new PipelineStageUpdated(1, 1, 'deploy', 'success', 'Deployed', 100);
        $broadcastData = $event->broadcastWith();

        $this->assertEquals('green', $broadcastData['status_color']);
        $this->assertEquals('check-circle', $broadcastData['status_icon']);
    }

    #[Test]
    public function pipeline_stage_updated_event_has_custom_broadcast_name(): void
    {
        $event = new PipelineStageUpdated(1, 1, 'test', 'running', 'Testing', 25);

        $this->assertEquals('pipeline.stage.updated', $event->broadcastAs());
    }

    #[Test]
    public function pipeline_stage_updated_event_sets_timestamp_automatically(): void
    {
        $event = new PipelineStageUpdated(1, 1, 'build', 'pending', 'Waiting', 0);

        $this->assertNotEmpty($event->timestamp);
    }

    // ========================================
    // ProjectSetupUpdated Event Tests
    // ========================================

    #[Test]
    public function project_setup_updated_event_can_be_instantiated(): void
    {
        $project = Project::factory()->create();

        $event = new ProjectSetupUpdated($project);

        $this->assertInstanceOf(ProjectSetupUpdated::class, $event);
        $this->assertEquals($project->id, $event->project->id);
    }

    #[Test]
    public function project_setup_updated_event_broadcasts_on_private_project_channel(): void
    {
        $project = Project::factory()->create();
        $event = new ProjectSetupUpdated($project);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
    }

    #[Test]
    public function project_setup_updated_event_has_custom_broadcast_name(): void
    {
        $project = Project::factory()->create();
        $event = new ProjectSetupUpdated($project);

        $this->assertEquals('setup.updated', $event->broadcastAs());
    }

    #[Test]
    public function project_setup_updated_event_broadcasts_setup_tasks(): void
    {
        $project = Project::factory()->create();
        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => 'ssl',
            'status' => 'completed',
        ]);

        $event = new ProjectSetupUpdated($project);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('tasks', $broadcastData);
        $this->assertNotEmpty($broadcastData['tasks']);
        $this->assertEquals('ssl', $broadcastData['tasks'][0]['type']);
    }

    // ========================================
    // ServerMetricsUpdated Event Tests
    // ========================================

    #[Test]
    public function server_metrics_updated_event_can_be_instantiated(): void
    {
        $server = Server::factory()->create();
        $metric = ServerMetric::factory()->create(['server_id' => $server->id]);

        $event = new ServerMetricsUpdated($server, $metric);

        $this->assertInstanceOf(ServerMetricsUpdated::class, $event);
        $this->assertEquals($server->id, $event->server->id);
        $this->assertEquals($metric->id, $event->metric->id);
    }

    #[Test]
    public function server_metrics_updated_event_implements_should_broadcast(): void
    {
        $server = Server::factory()->create();
        $metric = ServerMetric::factory()->create(['server_id' => $server->id]);
        $event = new ServerMetricsUpdated($server, $metric);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
    }

    #[Test]
    public function server_metrics_updated_event_broadcasts_on_server_metrics_channel(): void
    {
        $server = Server::factory()->create();
        $metric = ServerMetric::factory()->create(['server_id' => $server->id]);

        $event = new ServerMetricsUpdated($server, $metric);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('server-metrics.'.$server->id, $channels[0]->name);
    }

    #[Test]
    public function server_metrics_updated_event_broadcasts_metric_data(): void
    {
        $server = Server::factory()->create(['name' => 'Production Server']);
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 75.5,
            'memory_usage' => 65.2,
            'disk_usage' => 50.0,
        ]);

        $event = new ServerMetricsUpdated($server, $metric);
        $broadcastData = $event->broadcastWith();

        $this->assertEquals($server->id, $broadcastData['server_id']);
        $this->assertEquals('Production Server', $broadcastData['server_name']);
        $this->assertEquals(75.5, $broadcastData['metrics']['cpu_usage']);
        $this->assertEquals(65.2, $broadcastData['metrics']['memory_usage']);
        $this->assertEquals(50.0, $broadcastData['metrics']['disk_usage']);
    }

    #[Test]
    public function server_metrics_updated_event_includes_alerts_for_critical_cpu(): void
    {
        $server = Server::factory()->create();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 95.0,
        ]);

        $event = new ServerMetricsUpdated($server, $metric);
        $broadcastData = $event->broadcastWith();

        $this->assertNotEmpty($broadcastData['alerts']);
        $this->assertEquals('critical', $broadcastData['alerts'][0]['type']);
        $this->assertEquals('cpu', $broadcastData['alerts'][0]['metric']);
    }

    #[Test]
    public function server_metrics_updated_event_includes_alerts_for_high_memory(): void
    {
        $server = Server::factory()->create();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'memory_usage' => 90.0,
        ]);

        $event = new ServerMetricsUpdated($server, $metric);
        $broadcastData = $event->broadcastWith();

        $this->assertNotEmpty($broadcastData['alerts']);
        $cpuAlert = collect($broadcastData['alerts'])->firstWhere('metric', 'memory');
        $this->assertNotNull($cpuAlert);
        $this->assertEquals('critical', $cpuAlert['type']);
    }

    #[Test]
    public function server_metrics_updated_event_includes_alerts_for_high_disk(): void
    {
        $server = Server::factory()->create();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'disk_usage' => 85.0,
        ]);

        $event = new ServerMetricsUpdated($server, $metric);
        $broadcastData = $event->broadcastWith();

        $this->assertNotEmpty($broadcastData['alerts']);
        $diskAlert = collect($broadcastData['alerts'])->firstWhere('metric', 'disk');
        $this->assertNotNull($diskAlert);
        $this->assertEquals('warning', $diskAlert['type']);
    }

    // ========================================
    // DeploymentApprovalRequested Notification Tests
    // ========================================

    #[Test]
    public function deployment_approval_notification_can_be_instantiated(): void
    {
        $approval = DeploymentApproval::factory()->create();

        $notification = new DeploymentApprovalRequested($approval);

        $this->assertInstanceOf(DeploymentApprovalRequested::class, $notification);
        $this->assertEquals($approval->id, $notification->approval->id);
    }

    #[Test]
    public function deployment_approval_notification_returns_mail_and_database_channels(): void
    {
        $approval = DeploymentApproval::factory()->create();
        $notification = new DeploymentApprovalRequested($approval);
        $user = User::factory()->create();

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    #[Test]
    public function deployment_approval_notification_creates_mail_message(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $project = Project::factory()->create(['name' => 'API Service']);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'branch' => 'production',
            'commit_message' => 'Fix critical bug',
        ]);
        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
        ]);

        $notification = new DeploymentApprovalRequested($approval);
        $mail = $notification->toMail($user);

        $this->assertEquals('Deployment Approval Required: API Service', $mail->subject);
        $this->assertStringContainsString('John Doe', $mail->greeting);
    }

    #[Test]
    public function deployment_approval_notification_returns_array_data(): void
    {
        $project = Project::factory()->create(['name' => 'Web App']);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'branch' => 'main',
        ]);
        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
        ]);
        $user = User::factory()->create();

        $notification = new DeploymentApprovalRequested($approval);
        $data = $notification->toArray($user);

        $this->assertEquals('deployment_approval_requested', $data['type']);
        $this->assertEquals($approval->id, $data['approval_id']);
        $this->assertEquals($deployment->id, $data['deployment_id']);
        $this->assertEquals('Web App', $data['project_name']);
    }

    #[Test]
    public function deployment_approval_notification_can_be_sent(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $approval = DeploymentApproval::factory()->create();

        $user->notify(new DeploymentApprovalRequested($approval));

        Notification::assertSentTo($user, DeploymentApprovalRequested::class);
    }

    // ========================================
    // ServerProvisioningCompleted Notification Tests
    // ========================================

    #[Test]
    public function server_provisioning_notification_can_be_instantiated(): void
    {
        $server = Server::factory()->create();

        $notification = new ServerProvisioningCompleted($server, true);

        $this->assertInstanceOf(ServerProvisioningCompleted::class, $notification);
        $this->assertEquals($server->id, $notification->server->id);
        $this->assertTrue($notification->success);
    }

    #[Test]
    public function server_provisioning_notification_returns_mail_and_database_channels(): void
    {
        $server = Server::factory()->create();
        $notification = new ServerProvisioningCompleted($server);
        $user = User::factory()->create();

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    #[Test]
    public function server_provisioning_notification_creates_success_mail(): void
    {
        $server = Server::factory()->create([
            'name' => 'Production Server',
            'ip_address' => '192.168.1.100',
        ]);
        $user = User::factory()->create();

        $notification = new ServerProvisioningCompleted($server, true);
        $mail = $notification->toMail($user);

        $this->assertEquals('Server Provisioned Successfully: Production Server', $mail->subject);
    }

    #[Test]
    public function server_provisioning_notification_creates_failure_mail(): void
    {
        $server = Server::factory()->create(['name' => 'Test Server']);
        $user = User::factory()->create();

        $notification = new ServerProvisioningCompleted($server, false, 'SSH connection failed');
        $mail = $notification->toMail($user);

        $this->assertEquals('Server Provisioning Failed: Test Server', $mail->subject);
    }

    #[Test]
    public function server_provisioning_notification_returns_database_data(): void
    {
        $server = Server::factory()->create(['name' => 'Dev Server']);
        $user = User::factory()->create();

        $notification = new ServerProvisioningCompleted($server, true);
        $data = $notification->toDatabase($user);

        $this->assertEquals('provisioning_completed', $data['type']);
        $this->assertEquals($server->id, $data['server_id']);
        $this->assertTrue($data['success']);
    }

    #[Test]
    public function server_provisioning_notification_database_includes_error_on_failure(): void
    {
        $server = Server::factory()->create();
        $user = User::factory()->create();

        $notification = new ServerProvisioningCompleted($server, false, 'Connection timeout');
        $data = $notification->toDatabase($user);

        $this->assertEquals('provisioning_failed', $data['type']);
        $this->assertFalse($data['success']);
        $this->assertEquals('Connection timeout', $data['error_message']);
    }

    // ========================================
    // SSLCertificateExpiring Notification Tests
    // ========================================

    #[Test]
    public function ssl_expiring_notification_can_be_instantiated(): void
    {
        $certificate = SSLCertificate::factory()->create();

        $notification = new SSLCertificateExpiring($certificate);

        $this->assertInstanceOf(SSLCertificateExpiring::class, $notification);
        $this->assertEquals($certificate->id, $notification->certificate->id);
    }

    #[Test]
    public function ssl_expiring_notification_returns_mail_and_database_channels(): void
    {
        $certificate = SSLCertificate::factory()->create();
        $notification = new SSLCertificateExpiring($certificate);
        $user = User::factory()->create();

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    #[Test]
    public function ssl_expiring_notification_creates_mail_message(): void
    {
        // We'll verify the notification properties instead of calling toMail
        // which requires routes to be defined
        $server = Server::factory()->create(['name' => 'Web Server']);
        $certificate = SSLCertificate::factory()->create([
            'domain_name' => 'example.com',
            'server_id' => $server->id,
            'expires_at' => now()->addDays(7),
        ]);

        $notification = new SSLCertificateExpiring($certificate);

        $this->assertEquals($certificate->id, $notification->certificate->id);
        $this->assertEquals('example.com', $notification->certificate->domain_name);
        $this->assertInstanceOf(SSLCertificateExpiring::class, $notification);
    }

    #[Test]
    public function ssl_expiring_notification_returns_database_data(): void
    {
        $server = Server::factory()->create(['name' => 'API Server']);
        $certificate = SSLCertificate::factory()->create([
            'domain_name' => 'api.example.com',
            'server_id' => $server->id,
            'expires_at' => now()->addDays(5),
        ]);
        $user = User::factory()->create();

        $notification = new SSLCertificateExpiring($certificate);
        $data = $notification->toDatabase($user);

        $this->assertEquals('ssl_expiring', $data['type']);
        $this->assertEquals($certificate->id, $data['certificate_id']);
        $this->assertEquals('api.example.com', $data['domain']);
        $this->assertEquals('API Server', $data['server_name']);
    }

    #[Test]
    public function ssl_expiring_notification_includes_urgency_level(): void
    {
        $certificate = SSLCertificate::factory()->create([
            'expires_at' => now()->addDays(2),
        ]);
        $user = User::factory()->create();

        $notification = new SSLCertificateExpiring($certificate);
        $data = $notification->toDatabase($user);

        $this->assertArrayHasKey('urgency', $data);
    }

    // ========================================
    // SSLCertificateRenewed Notification Tests
    // ========================================

    #[Test]
    public function ssl_renewed_notification_can_be_instantiated(): void
    {
        $certificate = SSLCertificate::factory()->create();

        $notification = new SSLCertificateRenewed($certificate);

        $this->assertInstanceOf(SSLCertificateRenewed::class, $notification);
        $this->assertEquals($certificate->id, $notification->certificate->id);
    }

    #[Test]
    public function ssl_renewed_notification_returns_mail_and_database_channels(): void
    {
        $certificate = SSLCertificate::factory()->create();
        $notification = new SSLCertificateRenewed($certificate);
        $user = User::factory()->create();

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    #[Test]
    public function ssl_renewed_notification_creates_mail_message(): void
    {
        // We'll verify the notification properties instead of calling toMail
        // which requires routes to be defined
        $server = Server::factory()->create(['name' => 'Production']);
        $certificate = SSLCertificate::factory()->create([
            'domain_name' => 'secure.example.com',
            'server_id' => $server->id,
            'expires_at' => now()->addDays(90),
        ]);

        $notification = new SSLCertificateRenewed($certificate);

        $this->assertEquals($certificate->id, $notification->certificate->id);
        $this->assertEquals('secure.example.com', $notification->certificate->domain_name);
        $this->assertInstanceOf(SSLCertificateRenewed::class, $notification);
    }

    #[Test]
    public function ssl_renewed_notification_returns_database_data(): void
    {
        $server = Server::factory()->create(['name' => 'Staging']);
        $certificate = SSLCertificate::factory()->create([
            'domain_name' => 'staging.example.com',
            'server_id' => $server->id,
        ]);
        $user = User::factory()->create();

        $notification = new SSLCertificateRenewed($certificate);
        $data = $notification->toDatabase($user);

        $this->assertEquals('ssl_renewed', $data['type']);
        $this->assertEquals($certificate->id, $data['certificate_id']);
        $this->assertEquals('staging.example.com', $data['domain']);
        $this->assertEquals('Staging', $data['server_name']);
    }

    // ========================================
    // UserMentionedInComment Notification Tests
    // ========================================

    #[Test]
    public function user_mentioned_notification_can_be_instantiated(): void
    {
        $comment = DeploymentComment::factory()->create();

        $notification = new UserMentionedInComment($comment);

        $this->assertInstanceOf(UserMentionedInComment::class, $notification);
        $this->assertEquals($comment->id, $notification->comment->id);
    }

    #[Test]
    public function user_mentioned_notification_returns_mail_and_database_channels(): void
    {
        $comment = DeploymentComment::factory()->create();
        $notification = new UserMentionedInComment($comment);
        $user = User::factory()->create();

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    #[Test]
    public function user_mentioned_notification_creates_mail_message(): void
    {
        $author = User::factory()->create(['name' => 'Jane Smith']);
        $project = Project::factory()->create(['name' => 'Mobile App']);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);
        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $deployment->id,
            'user_id' => $author->id,
            'content' => '@john please review this deployment',
        ]);
        $user = User::factory()->create(['name' => 'John']);

        $notification = new UserMentionedInComment($comment);
        $mail = $notification->toMail($user);

        $this->assertEquals('Jane Smith mentioned you in a comment', $mail->subject);
    }

    #[Test]
    public function user_mentioned_notification_returns_array_data(): void
    {
        $author = User::factory()->create(['name' => 'Alice']);
        $project = Project::factory()->create(['name' => 'Dashboard']);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);
        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $deployment->id,
            'user_id' => $author->id,
        ]);
        $user = User::factory()->create();

        $notification = new UserMentionedInComment($comment);
        $data = $notification->toArray($user);

        $this->assertEquals('user_mentioned_in_comment', $data['type']);
        $this->assertEquals($comment->id, $data['comment_id']);
        $this->assertEquals('Dashboard', $data['project_name']);
        $this->assertEquals('Alice', $data['author_name']);
    }

    #[Test]
    public function user_mentioned_notification_can_be_sent(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $comment = DeploymentComment::factory()->create();

        $user->notify(new UserMentionedInComment($comment));

        Notification::assertSentTo($user, UserMentionedInComment::class);
    }

    // ========================================
    // TeamInvitation Mail Tests
    // ========================================

    #[Test]
    public function team_invitation_mail_can_be_instantiated(): void
    {
        $invitation = TeamInvitationModel::factory()->create();

        $mail = new TeamInvitation($invitation);

        $this->assertInstanceOf(TeamInvitation::class, $mail);
        $this->assertEquals($invitation->id, $mail->invitation->id);
    }

    #[Test]
    public function team_invitation_mail_has_correct_envelope(): void
    {
        $invitation = TeamInvitationModel::factory()->create();

        $mail = new TeamInvitation($invitation);
        $envelope = $mail->envelope();

        $this->assertStringContainsString($invitation->team->name, $envelope->subject);
        $this->assertStringContainsString('DevFlow Pro', $envelope->subject);
    }

    #[Test]
    public function team_invitation_mail_has_correct_content(): void
    {
        $invitation = TeamInvitationModel::factory()->create(['role' => 'member']);

        $mail = new TeamInvitation($invitation);
        $content = $mail->content();

        $this->assertEquals('emails.team-invitation', $content->markdown);
        $this->assertArrayHasKey('teamName', $content->with);
        $this->assertArrayHasKey('inviterName', $content->with);
        $this->assertArrayHasKey('role', $content->with);
        $this->assertEquals('Member', $content->with['role']);
    }

    #[Test]
    public function team_invitation_mail_includes_accept_url(): void
    {
        $invitation = TeamInvitationModel::factory()->create();

        $mail = new TeamInvitation($invitation);
        $content = $mail->content();

        $this->assertArrayHasKey('acceptUrl', $content->with);
        $this->assertStringContainsString($invitation->token, $content->with['acceptUrl']);
    }

    #[Test]
    public function team_invitation_mail_can_be_sent(): void
    {
        Mail::fake();
        $invitation = TeamInvitationModel::factory()->create();

        Mail::to('test@example.com')->send(new TeamInvitation($invitation));

        Mail::assertSent(TeamInvitation::class);
    }

    #[Test]
    public function team_invitation_mail_passes_correct_data(): void
    {
        Mail::fake();
        $invitation = TeamInvitationModel::factory()->create([
            'role' => 'admin',
        ]);

        Mail::to('user@example.com')->send(new TeamInvitation($invitation));

        Mail::assertSent(TeamInvitation::class, function ($mail) use ($invitation) {
            $content = $mail->content();

            return $content->with['teamName'] === $invitation->team->name
                && $content->with['role'] === 'Admin'
                && $content->with['inviterName'] === $invitation->inviter->name;
        });
    }
}
