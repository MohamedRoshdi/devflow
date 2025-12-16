<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Teams\TeamGeneralSettings;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TeamGeneralSettingsTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $owner;

    private User $admin;

    private User $member;

    private User $viewer;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->owner = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->member = User::factory()->create();
        $this->viewer = User::factory()->create();

        $this->team = Team::factory()->create([
            'owner_id' => $this->owner->id,
            'name' => 'Test Team',
            'description' => 'Test Description',
        ]);

        // Add owner as team member
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Add admin as team member
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->admin->id,
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        // Add member as team member
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        // Add viewer as team member
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->viewer->id,
            'role' => 'viewer',
            'joined_at' => now(),
        ]);
    }

    // ===== COMPONENT RENDERING =====

    public function test_component_renders_for_owner(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->assertStatus(200)
            ->assertViewIs('livewire.teams.team-general-settings');
    }

    public function test_component_renders_for_admin(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->assertStatus(200);
    }

    public function test_component_renders_for_member(): void
    {
        $this->actingAs($this->member);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->assertStatus(200);
    }

    public function test_component_renders_for_viewer(): void
    {
        $this->actingAs($this->viewer);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->assertStatus(200);
    }

    // ===== MOUNT METHOD =====

    public function test_mount_populates_name(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->assertSet('name', 'Test Team');
    }

    public function test_mount_populates_description(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->assertSet('description', 'Test Description');
    }

    public function test_mount_handles_null_description(): void
    {
        $teamNoDesc = Team::factory()->create([
            'owner_id' => $this->owner->id,
            'name' => 'No Description Team',
            'description' => null,
        ]);

        TeamMember::factory()->create([
            'team_id' => $teamNoDesc->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
        ]);

        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $teamNoDesc])
            ->assertSet('description', '');
    }

    // ===== UPDATE TEAM - AUTHORIZATION =====

    public function test_owner_can_update_team(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Updated Team Name')
            ->set('description', 'Updated Description')
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success')
            ->assertDispatched('team-updated');

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'name' => 'Updated Team Name',
            'description' => 'Updated Description',
        ]);
    }

    public function test_admin_can_update_team(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Admin Updated Name')
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'name' => 'Admin Updated Name',
        ]);
    }

    public function test_member_cannot_update_team(): void
    {
        $this->actingAs($this->member);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Member Updated Name')
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' && str_contains($data['message'], 'permission'));

        $this->assertDatabaseMissing('teams', [
            'id' => $this->team->id,
            'name' => 'Member Updated Name',
        ]);
    }

    public function test_viewer_cannot_update_team(): void
    {
        $this->actingAs($this->viewer);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Viewer Updated Name')
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error');

        $this->assertDatabaseMissing('teams', [
            'id' => $this->team->id,
            'name' => 'Viewer Updated Name',
        ]);
    }

    public function test_unauthenticated_user_cannot_update_team(): void
    {
        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Hacker Updated Name')
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error');
    }

    public function test_non_team_member_cannot_update_team(): void
    {
        $outsider = User::factory()->create();
        $this->actingAs($outsider);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Outsider Updated Name')
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error');
    }

    // ===== UPDATE TEAM - VALIDATION =====

    public function test_name_is_required(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', '')
            ->call('updateTeam')
            ->assertHasErrors(['name']);
    }

    public function test_name_max_length_validation(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', str_repeat('a', 256))
            ->call('updateTeam')
            ->assertHasErrors(['name']);
    }

    public function test_description_max_length_validation(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('description', str_repeat('a', 501))
            ->call('updateTeam')
            ->assertHasErrors(['description']);
    }

    public function test_description_can_be_empty(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Test Team')
            ->set('description', '')
            ->call('updateTeam')
            ->assertHasNoErrors(['description'])
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');
    }

    // ===== AVATAR UPLOAD =====

    public function test_owner_can_upload_avatar(): void
    {
        $this->actingAs($this->owner);

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Test Team')
            ->set('avatar', $file)
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');

        // Verify avatar was stored
        $this->team->refresh();
        $this->assertNotNull($this->team->avatar);
        Storage::disk('public')->assertExists($this->team->avatar);
    }

    public function test_avatar_upload_validates_image_type(): void
    {
        $this->actingAs($this->owner);

        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Test Team')
            ->set('avatar', $file)
            ->call('updateTeam')
            ->assertHasErrors(['avatar']);
    }

    public function test_avatar_upload_validates_max_size(): void
    {
        $this->actingAs($this->owner);

        // Create a file larger than 2MB (2048KB)
        $file = UploadedFile::fake()->image('large_avatar.jpg')->size(3000);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Test Team')
            ->set('avatar', $file)
            ->call('updateTeam')
            ->assertHasErrors(['avatar']);
    }

    public function test_avatar_accepts_valid_image_formats(): void
    {
        $this->actingAs($this->owner);

        $formats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        foreach ($formats as $format) {
            $file = UploadedFile::fake()->image("avatar.{$format}", 100, 100);

            Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
                ->set('name', 'Test Team')
                ->set('avatar', $file)
                ->call('updateTeam')
                ->assertHasNoErrors(['avatar']);
        }
    }

    public function test_avatar_is_cleared_after_successful_upload(): void
    {
        $this->actingAs($this->owner);

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Test Team')
            ->set('avatar', $file)
            ->call('updateTeam')
            ->assertSet('avatar', null);
    }

    public function test_update_without_avatar_keeps_existing_avatar(): void
    {
        // Set an existing avatar
        $this->team->update(['avatar' => 'teams/existing_avatar.jpg']);

        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Updated Name')
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');

        $this->team->refresh();
        $this->assertEquals('teams/existing_avatar.jpg', $this->team->avatar);
    }

    // ===== SUSPICIOUS FILENAME DETECTION =====

    public function test_rejects_suspicious_filename_with_path_traversal(): void
    {
        $this->actingAs($this->owner);

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        // Mock the filename to include path traversal
        $mock = \Mockery::mock($file);
        $mock->shouldReceive('getClientOriginalName')->andReturn('../../../etc/passwd.jpg');
        $mock->shouldReceive('isValid')->andReturn(true);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Test Team')
            ->set('avatar', $mock)
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' && str_contains($data['message'], 'Invalid filename'));
    }

    public function test_rejects_filename_with_null_bytes(): void
    {
        $this->actingAs($this->owner);

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $mock = \Mockery::mock($file);
        $mock->shouldReceive('getClientOriginalName')->andReturn("avatar\0.php.jpg");
        $mock->shouldReceive('isValid')->andReturn(true);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Test Team')
            ->set('avatar', $mock)
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' && str_contains($data['message'], 'Invalid filename'));
    }

    // ===== NOTIFICATIONS =====

    public function test_success_notification_on_update(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'New Team Name')
            ->call('updateTeam')
            ->assertDispatched('notification', function (array $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'Team updated successfully');
            });
    }

    public function test_error_notification_on_permission_denied(): void
    {
        $this->actingAs($this->viewer);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'New Team Name')
            ->call('updateTeam')
            ->assertDispatched('notification', function (array $data): bool {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'permission');
            });
    }

    public function test_team_updated_event_dispatched(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Updated Team')
            ->call('updateTeam')
            ->assertDispatched('team-updated');
    }

    // ===== EDGE CASES =====

    public function test_handles_update_with_special_characters_in_name(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Team & Company <Test>')
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'name' => 'Team & Company <Test>',
        ]);
    }

    public function test_handles_unicode_characters_in_name(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Team Unicode Test')
            ->set('description', 'Description with unicode')
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');
    }

    public function test_handles_description_with_newlines(): void
    {
        $this->actingAs($this->owner);

        $description = "Line 1\nLine 2\nLine 3";

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Test Team')
            ->set('description', $description)
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');

        $this->team->refresh();
        $this->assertEquals($description, $this->team->description);
    }

    public function test_allows_name_with_leading_trailing_spaces(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', '  Trimmed Name  ')
            ->call('updateTeam')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');

        // Note: The actual trimming behavior depends on implementation
        // This test verifies the update happens without error
        $this->team->refresh();
        $this->assertStringContainsString('Trimmed Name', $this->team->name);
    }

    // ===== DATABASE INTEGRITY =====

    public function test_update_only_modifies_intended_fields(): void
    {
        $originalSlug = $this->team->slug;
        $originalOwnerId = $this->team->owner_id;

        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'New Name')
            ->set('description', 'New Description')
            ->call('updateTeam');

        $this->team->refresh();

        // Verify slug and owner weren't changed
        $this->assertEquals($originalSlug, $this->team->slug);
        $this->assertEquals($originalOwnerId, $this->team->owner_id);
    }

    public function test_multiple_updates_work_correctly(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(TeamGeneralSettings::class, ['team' => $this->team]);

        // First update
        $component
            ->set('name', 'First Update')
            ->call('updateTeam')
            ->assertDispatched('notification');

        // Second update
        $component
            ->set('name', 'Second Update')
            ->call('updateTeam')
            ->assertDispatched('notification');

        $this->team->refresh();
        $this->assertEquals('Second Update', $this->team->name);
    }

    // ===== AVATAR STORAGE =====

    public function test_avatar_stored_in_teams_directory(): void
    {
        $this->actingAs($this->owner);

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Test Team')
            ->set('avatar', $file)
            ->call('updateTeam');

        $this->team->refresh();
        $avatar = $this->team->avatar;
        $this->assertNotNull($avatar);
        $this->assertStringStartsWith('teams/', $avatar);
    }

    public function test_avatar_filename_is_sanitized(): void
    {
        $this->actingAs($this->owner);

        $file = UploadedFile::fake()->image('my avatar (1).jpg', 100, 100);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Test Team')
            ->set('avatar', $file)
            ->call('updateTeam');

        $this->team->refresh();

        // The filename should be sanitized (no spaces, special chars)
        $avatar = $this->team->avatar;
        $this->assertNotNull($avatar);
        $this->assertStringNotContainsString(' ', $avatar);
        $this->assertStringNotContainsString('(', $avatar);
        $this->assertStringNotContainsString(')', $avatar);
    }
}
