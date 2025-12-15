<?php

declare(strict_types=1);

namespace Tests\Unit\Models;


use PHPUnit\Framework\Attributes\Test;
use App\Models\ApiToken;
use App\Models\AuditLog;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\Server;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Tests\TestCase;

class TeamAuthModelsTest extends TestCase
{

    // ========================================
    // Team Model Tests
    // ========================================

    #[Test]
    public function team_can_be_created_with_factory(): void
    {
        $team = Team::factory()->create();

        $this->assertModelExists($team);
        $this->assertNotNull($team->name);
        $this->assertNotNull($team->slug);
    }

    #[Test]
    public function team_automatically_generates_slug_on_creation(): void
    {
        $team = Team::factory()->create(['name' => 'Test Team Name']);

        $this->assertEquals('test-team-name', $team->slug);
    }

    #[Test]
    public function team_generates_unique_slug_when_duplicate_exists(): void
    {
        Team::factory()->create(['name' => 'DevFlow', 'slug' => 'devflow']);
        $team2 = Team::factory()->create(['name' => 'DevFlow']);

        $this->assertEquals('devflow-1', $team2->slug);
    }

    #[Test]
    public function team_has_owner_relationship(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $this->assertInstanceOf(User::class, $team->owner);
        $this->assertEquals($owner->id, $team->owner->id);
    }

    #[Test]
    public function team_has_members_relationship(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $team->members()->attach($user->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->assertCount(1, $team->members);
        $this->assertEquals($user->id, $team->members->first()->id);
    }

    #[Test]
    public function team_has_team_members_relationship(): void
    {
        $team = Team::factory()->create();
        TeamMember::factory()->create(['team_id' => $team->id]);

        $this->assertCount(1, $team->teamMembers);
        $this->assertInstanceOf(TeamMember::class, $team->teamMembers->first());
    }

    #[Test]
    public function team_has_invitations_relationship(): void
    {
        $team = Team::factory()->create();
        TeamInvitation::factory()->create(['team_id' => $team->id]);

        $this->assertCount(1, $team->invitations);
        $this->assertInstanceOf(TeamInvitation::class, $team->invitations->first());
    }

    #[Test]
    public function team_has_projects_relationship(): void
    {
        $team = Team::factory()->create();
        Project::factory()->create(['team_id' => $team->id]);

        $this->assertCount(1, $team->projects);
        $this->assertInstanceOf(Project::class, $team->projects->first());
    }

    #[Test]
    public function team_has_servers_relationship(): void
    {
        $team = Team::factory()->create();
        Server::factory()->create(['team_id' => $team->id]);

        $this->assertCount(1, $team->servers);
        $this->assertInstanceOf(Server::class, $team->servers->first());
    }

    #[Test]
    public function team_has_member_method_returns_true_when_user_is_member(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $team->members()->attach($user->id, ['role' => 'member']);

        $this->assertTrue($team->hasMember($user));
    }

    #[Test]
    public function team_has_member_method_returns_false_when_user_is_not_member(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($team->hasMember($user));
    }

    #[Test]
    public function team_get_member_role_returns_correct_role(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $team->members()->attach($user->id, ['role' => 'admin']);

        $this->assertEquals('admin', $team->getMemberRole($user));
    }

    #[Test]
    public function team_get_member_role_returns_null_for_non_member(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $this->assertNull($team->getMemberRole($user));
    }

    #[Test]
    public function team_is_owner_returns_true_for_owner(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($team->isOwner($owner));
    }

    #[Test]
    public function team_is_owner_returns_false_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($team->isOwner($user));
    }

    #[Test]
    public function team_avatar_url_accessor_returns_storage_path_when_avatar_exists(): void
    {
        $team = Team::factory()->create(['avatar' => 'avatars/team.jpg']);

        $this->assertStringContainsString('storage/avatars/team.jpg', $team->avatar_url);
    }

    #[Test]
    public function team_avatar_url_accessor_generates_initials_avatar_when_no_avatar(): void
    {
        $team = Team::factory()->create(['name' => 'DevFlow Pro', 'avatar' => null]);

        $this->assertStringContainsString('ui-avatars.com', $team->avatar_url);
        $this->assertStringContainsString('name=DP', $team->avatar_url);
    }

    #[Test]
    public function team_settings_are_cast_to_array(): void
    {
        $settings = ['theme' => 'dark', 'notifications' => true];
        $team = Team::factory()->create(['settings' => $settings]);

        $this->assertIsArray($team->settings);
        $this->assertEquals('dark', $team->settings['theme']);
    }

    #[Test]
    public function team_is_personal_is_cast_to_boolean(): void
    {
        $team = Team::factory()->create(['is_personal' => true]);

        $this->assertIsBool($team->is_personal);
        $this->assertTrue($team->is_personal);
    }

    #[Test]
    public function team_soft_deletes(): void
    {
        $team = Team::factory()->create();
        $teamId = $team->id;

        $team->delete();

        $this->assertSoftDeleted('teams', ['id' => $teamId]);
    }

    // ========================================
    // TeamMember Model Tests
    // ========================================

    #[Test]
    public function team_member_can_be_created_with_factory(): void
    {
        $teamMember = TeamMember::factory()->create();

        $this->assertModelExists($teamMember);
        $this->assertNotNull($teamMember->team_id);
        $this->assertNotNull($teamMember->user_id);
    }

    #[Test]
    public function team_member_has_team_relationship(): void
    {
        $team = Team::factory()->create();
        $teamMember = TeamMember::factory()->create(['team_id' => $team->id]);

        $this->assertInstanceOf(Team::class, $teamMember->team);
        $this->assertEquals($team->id, $teamMember->team->id);
    }

    #[Test]
    public function team_member_has_user_relationship(): void
    {
        $user = User::factory()->create();
        $teamMember = TeamMember::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $teamMember->user);
        $this->assertEquals($user->id, $teamMember->user->id);
    }

    #[Test]
    public function team_member_has_inviter_relationship(): void
    {
        $inviter = User::factory()->create();
        $teamMember = TeamMember::factory()->create(['invited_by' => $inviter->id]);

        $this->assertInstanceOf(User::class, $teamMember->inviter);
        $this->assertEquals($inviter->id, $teamMember->inviter->id);
    }

    #[Test]
    public function team_member_permissions_are_cast_to_array(): void
    {
        $permissions = ['deploy_projects', 'manage_servers'];
        $teamMember = TeamMember::factory()->create(['permissions' => $permissions]);

        $this->assertIsArray($teamMember->permissions);
        $this->assertCount(2, $teamMember->permissions);
    }

    #[Test]
    public function team_member_joined_at_is_cast_to_datetime(): void
    {
        $teamMember = TeamMember::factory()->create(['joined_at' => now()]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $teamMember->joined_at);
    }

    #[Test]
    public function team_member_has_permission_returns_true_for_owner_role(): void
    {
        $teamMember = TeamMember::factory()->create(['role' => 'owner']);

        $this->assertTrue($teamMember->hasPermission('any_permission'));
    }

    #[Test]
    public function team_member_has_permission_returns_true_for_admin_role(): void
    {
        $teamMember = TeamMember::factory()->create(['role' => 'admin']);

        $this->assertTrue($teamMember->hasPermission('any_permission'));
    }

    #[Test]
    public function team_member_has_permission_checks_custom_permissions(): void
    {
        // Use 'member' role but with custom permissions - these override default role permissions
        $teamMember = TeamMember::factory()->create([
            'role' => 'member',
            'permissions' => ['deploy_projects', 'view_logs'],
        ]);

        $this->assertTrue($teamMember->hasPermission('deploy_projects'));
        $this->assertFalse($teamMember->hasPermission('delete_servers'));
    }

    #[Test]
    public function team_member_has_permission_uses_default_member_permissions(): void
    {
        $teamMember = TeamMember::factory()->create(['role' => 'member', 'permissions' => null]);

        $this->assertTrue($teamMember->hasPermission('view_projects'));
        $this->assertTrue($teamMember->hasPermission('view_deployments'));
        $this->assertFalse($teamMember->hasPermission('delete_projects'));
    }

    #[Test]
    public function team_member_has_permission_uses_default_viewer_permissions(): void
    {
        $teamMember = TeamMember::factory()->create(['role' => 'viewer', 'permissions' => null]);

        $this->assertTrue($teamMember->hasPermission('view_projects'));
        $this->assertFalse($teamMember->hasPermission('view_servers'));
    }

    // ========================================
    // TeamInvitation Model Tests
    // ========================================

    #[Test]
    public function team_invitation_can_be_created_with_factory(): void
    {
        $invitation = TeamInvitation::factory()->create();

        $this->assertModelExists($invitation);
        $this->assertNotNull($invitation->email);
        $this->assertNotNull($invitation->token);
    }

    #[Test]
    public function team_invitation_automatically_generates_token_on_creation(): void
    {
        $invitation = TeamInvitation::factory()->create(['token' => null]);

        $this->assertNotNull($invitation->token);
        $this->assertEquals(64, strlen($invitation->token));
    }

    #[Test]
    public function team_invitation_automatically_sets_expires_at_on_creation(): void
    {
        $invitation = TeamInvitation::factory()->create(['expires_at' => null]);

        $this->assertNotNull($invitation->expires_at);
        $this->assertTrue($invitation->expires_at->isFuture());
    }

    #[Test]
    public function team_invitation_has_team_relationship(): void
    {
        $team = Team::factory()->create();
        $invitation = TeamInvitation::factory()->create(['team_id' => $team->id]);

        $this->assertInstanceOf(Team::class, $invitation->team);
        $this->assertEquals($team->id, $invitation->team->id);
    }

    #[Test]
    public function team_invitation_has_inviter_relationship(): void
    {
        $inviter = User::factory()->create();
        $invitation = TeamInvitation::factory()->create(['invited_by' => $inviter->id]);

        $this->assertInstanceOf(User::class, $invitation->inviter);
        $this->assertEquals($inviter->id, $invitation->inviter->id);
    }

    #[Test]
    public function team_invitation_expires_at_is_cast_to_datetime(): void
    {
        $invitation = TeamInvitation::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invitation->expires_at);
    }

    #[Test]
    public function team_invitation_accepted_at_is_cast_to_datetime(): void
    {
        $invitation = TeamInvitation::factory()->create(['accepted_at' => now()]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invitation->accepted_at);
    }

    #[Test]
    public function team_invitation_is_expired_returns_true_when_expired(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($invitation->isExpired());
    }

    #[Test]
    public function team_invitation_is_expired_returns_false_when_not_expired(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'expires_at' => now()->addDay(),
        ]);

        $this->assertFalse($invitation->isExpired());
    }

    #[Test]
    public function team_invitation_is_accepted_returns_true_when_accepted(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'accepted_at' => now(),
        ]);

        $this->assertTrue($invitation->isAccepted());
    }

    #[Test]
    public function team_invitation_is_accepted_returns_false_when_not_accepted(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'accepted_at' => null,
        ]);

        $this->assertFalse($invitation->isAccepted());
    }

    #[Test]
    public function team_invitation_is_pending_returns_true_when_pending(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'expires_at' => now()->addDay(),
            'accepted_at' => null,
        ]);

        $this->assertTrue($invitation->isPending());
    }

    #[Test]
    public function team_invitation_is_pending_returns_false_when_expired(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'expires_at' => now()->subDay(),
            'accepted_at' => null,
        ]);

        $this->assertFalse($invitation->isPending());
    }

    #[Test]
    public function team_invitation_is_pending_returns_false_when_accepted(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'expires_at' => now()->addDay(),
            'accepted_at' => now(),
        ]);

        $this->assertFalse($invitation->isPending());
    }

    // ========================================
    // ApiToken Model Tests
    // ========================================

    #[Test]
    public function api_token_can_be_created_with_factory(): void
    {
        $token = ApiToken::factory()->create();

        $this->assertModelExists($token);
        $this->assertNotNull($token->name);
        $this->assertNotNull($token->token);
    }

    #[Test]
    public function api_token_has_user_relationship(): void
    {
        $user = User::factory()->create();
        $token = ApiToken::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $token->user);
        $this->assertEquals($user->id, $token->user->id);
    }

    #[Test]
    public function api_token_has_team_relationship(): void
    {
        $team = Team::factory()->create();
        $token = ApiToken::factory()->create(['team_id' => $team->id]);

        $this->assertInstanceOf(Team::class, $token->team);
        $this->assertEquals($team->id, $token->team->id);
    }

    #[Test]
    public function api_token_abilities_are_cast_to_array(): void
    {
        $abilities = ['projects:read', 'servers:write'];
        $token = ApiToken::factory()->create(['abilities' => $abilities]);

        $this->assertIsArray($token->abilities);
        $this->assertCount(2, $token->abilities);
    }

    #[Test]
    public function api_token_last_used_at_is_cast_to_datetime(): void
    {
        $token = ApiToken::factory()->create(['last_used_at' => now()]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $token->last_used_at);
    }

    #[Test]
    public function api_token_expires_at_is_cast_to_datetime(): void
    {
        $token = ApiToken::factory()->create(['expires_at' => now()->addMonth()]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $token->expires_at);
    }

    #[Test]
    public function api_token_hides_token_in_array(): void
    {
        $token = ApiToken::factory()->create(['token' => 'secret_token_value']);

        $array = $token->toArray();

        $this->assertArrayNotHasKey('token', $array);
    }

    #[Test]
    public function api_token_can_method_returns_true_when_abilities_empty(): void
    {
        $token = ApiToken::factory()->create(['abilities' => null]);

        $this->assertTrue($token->can('any_ability'));
    }

    #[Test]
    public function api_token_can_method_returns_true_for_specific_ability(): void
    {
        $token = ApiToken::factory()->create(['abilities' => ['projects:read', 'servers:write']]);

        $this->assertTrue($token->can('projects:read'));
    }

    #[Test]
    public function api_token_can_method_returns_false_for_missing_ability(): void
    {
        $token = ApiToken::factory()->create(['abilities' => ['projects:read']]);

        $this->assertFalse($token->can('servers:delete'));
    }

    #[Test]
    public function api_token_can_method_supports_wildcard_abilities(): void
    {
        $token = ApiToken::factory()->create(['abilities' => ['projects:*']]);

        $this->assertTrue($token->can('projects:read'));
        $this->assertTrue($token->can('projects:write'));
        $this->assertTrue($token->can('projects:delete'));
        $this->assertFalse($token->can('servers:read'));
    }

    #[Test]
    public function api_token_can_method_supports_super_admin_wildcard(): void
    {
        $token = ApiToken::factory()->create(['abilities' => ['*']]);

        $this->assertTrue($token->can('projects:read'));
        $this->assertTrue($token->can('servers:delete'));
        $this->assertTrue($token->can('any:ability'));
    }

    #[Test]
    public function api_token_has_expired_returns_false_when_no_expiry(): void
    {
        $token = ApiToken::factory()->create(['expires_at' => null]);

        $this->assertFalse($token->hasExpired());
    }

    #[Test]
    public function api_token_has_expired_returns_true_when_expired(): void
    {
        $token = ApiToken::factory()->create(['expires_at' => now()->subDay()]);

        $this->assertTrue($token->hasExpired());
    }

    #[Test]
    public function api_token_has_expired_returns_false_when_not_expired(): void
    {
        $token = ApiToken::factory()->create(['expires_at' => now()->addDay()]);

        $this->assertFalse($token->hasExpired());
    }

    #[Test]
    public function api_token_update_last_used_at_updates_timestamp(): void
    {
        $token = ApiToken::factory()->create(['last_used_at' => null]);

        $token->updateLastUsedAt();

        $this->assertNotNull($token->fresh()->last_used_at);
    }

    #[Test]
    public function api_token_active_scope_includes_non_expired_tokens(): void
    {
        ApiToken::factory()->create(['expires_at' => now()->addDay()]);
        ApiToken::factory()->create(['expires_at' => null]);
        ApiToken::factory()->create(['expires_at' => now()->subDay()]);

        $activeTokens = ApiToken::active()->get();

        $this->assertCount(2, $activeTokens);
    }

    // ========================================
    // AuditLog Model Tests
    // ========================================

    #[Test]
    public function audit_log_can_be_created(): void
    {
        $auditLog = AuditLog::create([
            'user_id' => User::factory()->create()->id,
            'action' => 'project.created',
            'auditable_type' => Project::class,
            'auditable_id' => 1,
            'old_values' => [],
            'new_values' => ['name' => 'Test Project'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        $this->assertModelExists($auditLog);
    }

    #[Test]
    public function audit_log_has_user_relationship(): void
    {
        $user = User::factory()->create();
        $auditLog = AuditLog::create([
            'user_id' => $user->id,
            'action' => 'test.action',
            'auditable_type' => Project::class,
            'auditable_id' => 1,
        ]);

        $this->assertInstanceOf(User::class, $auditLog->user);
        $this->assertEquals($user->id, $auditLog->user->id);
    }

    #[Test]
    public function audit_log_has_auditable_polymorphic_relationship(): void
    {
        $project = Project::factory()->create();
        $auditLog = AuditLog::create([
            'user_id' => User::factory()->create()->id,
            'action' => 'project.created',
            'auditable_type' => Project::class,
            'auditable_id' => $project->id,
        ]);

        $this->assertInstanceOf(Project::class, $auditLog->auditable);
        $this->assertEquals($project->id, $auditLog->auditable->id);
    }

    #[Test]
    public function audit_log_old_values_are_cast_to_array(): void
    {
        $oldValues = ['status' => 'draft'];
        $auditLog = AuditLog::create([
            'user_id' => User::factory()->create()->id,
            'action' => 'project.updated',
            'auditable_type' => Project::class,
            'auditable_id' => 1,
            'old_values' => $oldValues,
            'new_values' => ['status' => 'active'],
        ]);

        $this->assertIsArray($auditLog->old_values);
        $this->assertEquals('draft', $auditLog->old_values['status']);
    }

    #[Test]
    public function audit_log_new_values_are_cast_to_array(): void
    {
        $newValues = ['status' => 'active'];
        $auditLog = AuditLog::create([
            'user_id' => User::factory()->create()->id,
            'action' => 'project.updated',
            'auditable_type' => Project::class,
            'auditable_id' => 1,
            'old_values' => ['status' => 'draft'],
            'new_values' => $newValues,
        ]);

        $this->assertIsArray($auditLog->new_values);
        $this->assertEquals('active', $auditLog->new_values['status']);
    }

    #[Test]
    public function audit_log_action_name_accessor_returns_formatted_action(): void
    {
        $auditLog = AuditLog::create([
            'user_id' => User::factory()->create()->id,
            'action' => 'project.created',
            'auditable_type' => Project::class,
            'auditable_id' => 1,
        ]);

        $this->assertEquals('project created', $auditLog->action_name);
    }

    #[Test]
    public function audit_log_action_category_accessor_returns_category(): void
    {
        $auditLog = AuditLog::create([
            'user_id' => User::factory()->create()->id,
            'action' => 'deployment.started',
            'auditable_type' => Project::class,
            'auditable_id' => 1,
        ]);

        $this->assertEquals('deployment', $auditLog->action_category);
    }

    #[Test]
    public function audit_log_action_type_accessor_returns_type(): void
    {
        $auditLog = AuditLog::create([
            'user_id' => User::factory()->create()->id,
            'action' => 'server.updated',
            'auditable_type' => Server::class,
            'auditable_id' => 1,
        ]);

        $this->assertEquals('updated', $auditLog->action_type);
    }

    #[Test]
    public function audit_log_model_name_accessor_returns_model_basename(): void
    {
        $auditLog = AuditLog::create([
            'user_id' => User::factory()->create()->id,
            'action' => 'project.created',
            'auditable_type' => Project::class,
            'auditable_id' => 1,
        ]);

        $this->assertEquals('Project', $auditLog->model_name);
    }

    #[Test]
    public function audit_log_changes_summary_accessor_returns_changes(): void
    {
        $auditLog = AuditLog::create([
            'user_id' => User::factory()->create()->id,
            'action' => 'project.updated',
            'auditable_type' => Project::class,
            'auditable_id' => 1,
            'old_values' => ['status' => 'draft', 'name' => 'Old Name'],
            'new_values' => ['status' => 'active', 'name' => 'New Name'],
        ]);

        $changes = $auditLog->changes_summary;

        $this->assertIsArray($changes);
        $this->assertArrayHasKey('status', $changes);
        $this->assertEquals('draft', $changes['status']['old']);
        $this->assertEquals('active', $changes['status']['new']);
    }

    // ========================================
    // NotificationChannel Model Tests
    // ========================================

    #[Test]
    public function notification_channel_can_be_created_with_factory(): void
    {
        $channel = NotificationChannel::factory()->create();

        $this->assertModelExists($channel);
        $this->assertNotNull($channel->type);
    }

    #[Test]
    public function notification_channel_has_user_relationship(): void
    {
        // Test the relationship method exists and returns correct type
        // Note: user_id column may not exist in older migrations
        $user = User::factory()->create();
        $channel = NotificationChannel::factory()->make(['user_id' => $user->id]);

        // Test that the relationship method returns a BelongsTo relation
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $channel->user());

        // Test that setting user_id on the model links correctly
        $channel->user_id = $user->id;
        $this->assertEquals($user->id, $channel->user->id);
    }

    #[Test]
    public function notification_channel_has_project_relationship(): void
    {
        $project = Project::factory()->create();
        $channel = NotificationChannel::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $channel->project);
        $this->assertEquals($project->id, $channel->project->id);
    }

    #[Test]
    public function notification_channel_config_is_cast_to_array(): void
    {
        $config = ['webhook_url' => 'https://example.com/webhook'];
        $channel = NotificationChannel::factory()->create(['config' => $config]);

        $this->assertIsArray($channel->config);
        $this->assertEquals('https://example.com/webhook', $channel->config['webhook_url']);
    }

    #[Test]
    public function notification_channel_is_active_is_cast_to_boolean(): void
    {
        // Use make() instead of create() to test cast without database persistence
        // Note: is_active column may not exist in older migrations
        $channel = NotificationChannel::factory()->make(['is_active' => true]);

        $this->assertIsBool($channel->is_active);
        $this->assertTrue($channel->is_active);
    }

    #[Test]
    public function notification_channel_enabled_is_cast_to_boolean(): void
    {
        $channel = NotificationChannel::factory()->create(['enabled' => false]);

        $this->assertIsBool($channel->enabled);
        $this->assertFalse($channel->enabled);
    }

    #[Test]
    public function notification_channel_events_are_cast_to_array(): void
    {
        $events = ['deployment.success', 'deployment.failed'];
        $channel = NotificationChannel::factory()->create(['events' => $events]);

        $this->assertIsArray($channel->events);
        $this->assertCount(2, $channel->events);
    }

    #[Test]
    public function notification_channel_metadata_is_cast_to_array(): void
    {
        $metadata = ['color' => 'blue', 'icon' => 'bell'];
        $channel = NotificationChannel::factory()->create(['metadata' => $metadata]);

        $this->assertIsArray($channel->metadata);
        $this->assertEquals('blue', $channel->metadata['color']);
    }

    #[Test]
    public function notification_channel_webhook_secret_is_encrypted_on_set(): void
    {
        $channel = NotificationChannel::factory()->create(['webhook_secret' => 'my_secret']);

        $this->assertNotEquals('my_secret', $channel->getAttributes()['webhook_secret']);
    }

    #[Test]
    public function notification_channel_webhook_secret_is_decrypted_on_get(): void
    {
        $channel = NotificationChannel::factory()->create(['webhook_secret' => 'my_secret']);

        $this->assertEquals('my_secret', $channel->webhook_secret);
    }

    #[Test]
    public function notification_channel_webhook_secret_is_hidden_in_array(): void
    {
        $channel = NotificationChannel::factory()->create(['webhook_secret' => 'my_secret']);

        $array = $channel->toArray();

        $this->assertArrayNotHasKey('webhook_secret', $array);
    }

    #[Test]
    public function notification_channel_should_notify_for_event_returns_true_when_enabled_and_event_matches(): void
    {
        $channel = NotificationChannel::factory()->create([
            'enabled' => true,
            'events' => ['deployment.success', 'deployment.failed'],
        ]);

        $this->assertTrue($channel->shouldNotifyForEvent('deployment.success'));
    }

    #[Test]
    public function notification_channel_should_notify_for_event_returns_false_when_disabled(): void
    {
        $channel = NotificationChannel::factory()->create([
            'enabled' => false,
            'events' => ['deployment.success'],
        ]);

        $this->assertFalse($channel->shouldNotifyForEvent('deployment.success'));
    }

    #[Test]
    public function notification_channel_should_notify_for_event_returns_false_when_event_not_in_list(): void
    {
        $channel = NotificationChannel::factory()->create([
            'enabled' => true,
            'events' => ['deployment.success'],
        ]);

        $this->assertFalse($channel->shouldNotifyForEvent('deployment.failed'));
    }

    #[Test]
    public function notification_channel_type_icon_accessor_returns_correct_icon_for_email(): void
    {
        $channel = NotificationChannel::factory()->create(['type' => 'email']);

        $this->assertEquals('envelope', $channel->type_icon);
    }

    #[Test]
    public function notification_channel_type_icon_accessor_returns_correct_icon_for_slack(): void
    {
        $channel = NotificationChannel::factory()->create(['type' => 'slack']);

        $this->assertEquals('chat-bubble-left-right', $channel->type_icon);
    }

    #[Test]
    public function notification_channel_type_icon_accessor_returns_default_icon(): void
    {
        $channel = NotificationChannel::factory()->create(['type' => 'unknown']);

        $this->assertEquals('bell', $channel->type_icon);
    }

    #[Test]
    public function notification_channel_type_label_accessor_returns_correct_label(): void
    {
        $channel = NotificationChannel::factory()->create(['type' => 'slack']);

        $this->assertEquals('Slack', $channel->type_label);
    }

    #[Test]
    public function notification_channel_config_summary_accessor_returns_email_summary(): void
    {
        $channel = NotificationChannel::factory()->create([
            'type' => 'email',
            'config' => ['email' => 'test@example.com'],
        ]);

        $this->assertEquals('test@example.com', $channel->config_summary);
    }

    #[Test]
    public function notification_channel_config_summary_accessor_returns_webhook_summary(): void
    {
        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/XXXXXXXX'],
        ]);

        $this->assertStringContainsString('Webhook:', $channel->config_summary);
        $this->assertStringContainsString('https://hooks.slack.com/servi', $channel->config_summary);
    }
}
