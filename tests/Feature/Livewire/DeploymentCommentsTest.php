<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Deployments\DeploymentComments;
use App\Models\Deployment;
use App\Models\DeploymentComment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Notifications\UserMentionedInComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DeploymentCommentsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Deployment $deployment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);
        $this->deployment = Deployment::factory()->create([
            'project_id' => $project->id,
        ]);
    }

    // ===== COMPONENT RENDERING =====

    public function test_component_renders(): void
    {
        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->assertStatus(200)
            ->assertViewIs('livewire.deployments.deployment-comments');
    }

    public function test_component_shows_comments(): void
    {
        DeploymentComment::factory()->count(3)->create([
            'deployment_id' => $this->deployment->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment]);

        $comments = $component->viewData('comments');
        $this->assertCount(3, $comments);
    }

    public function test_component_shows_empty_state(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment]);

        $comments = $component->viewData('comments');
        $this->assertCount(0, $comments);
    }

    // ===== ADD COMMENT =====

    public function test_can_add_comment(): void
    {
        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('newComment', 'This is a test comment')
            ->call('addComment')
            ->assertSet('newComment', '')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');

        $this->assertDatabaseHas('deployment_comments', [
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test comment',
        ]);
    }

    public function test_add_comment_validates_required(): void
    {
        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('newComment', '')
            ->call('addComment')
            ->assertHasErrors(['newComment' => 'required']);
    }

    public function test_add_comment_validates_max_length(): void
    {
        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('newComment', str_repeat('a', 5001))
            ->call('addComment')
            ->assertHasErrors(['newComment' => 'max']);
    }

    public function test_add_comment_accepts_max_length(): void
    {
        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('newComment', str_repeat('a', 5000))
            ->call('addComment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('deployment_comments', [
            'deployment_id' => $this->deployment->id,
        ]);
    }

    // ===== MENTIONS =====

    public function test_add_comment_extracts_mentions(): void
    {
        $mentionedUser = User::factory()->create(['name' => 'JohnDoe']);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('newComment', 'Hey @JohnDoe check this out')
            ->call('addComment');

        $comment = DeploymentComment::where('deployment_id', $this->deployment->id)->first();
        $this->assertNotNull($comment);
        $mentions = $comment->mentions;
        $this->assertIsArray($mentions);
        $this->assertContains($mentionedUser->id, $mentions);
    }

    public function test_add_comment_notifies_mentioned_users(): void
    {
        Notification::fake();

        $mentionedUser = User::factory()->create(['name' => 'JaneSmith']);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('newComment', 'Hello @JaneSmith please review')
            ->call('addComment');

        Notification::assertSentTo($mentionedUser, UserMentionedInComment::class);
    }

    public function test_add_comment_does_not_notify_self(): void
    {
        Notification::fake();

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('newComment', 'Note to @' . $this->user->name . ' (myself)')
            ->call('addComment');

        Notification::assertNotSentTo($this->user, UserMentionedInComment::class);
    }

    public function test_add_comment_without_mentions(): void
    {
        Notification::fake();

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('newComment', 'A comment without any mentions')
            ->call('addComment');

        Notification::assertNothingSent();

        $comment = DeploymentComment::where('deployment_id', $this->deployment->id)->first();
        $this->assertNotNull($comment);
        $this->assertEmpty($comment->mentions);
    }

    // ===== EDIT COMMENT =====

    public function test_can_start_editing_own_comment(): void
    {
        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Original content',
        ]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->call('startEditing', $comment->id)
            ->assertSet('editingCommentId', $comment->id)
            ->assertSet('editingContent', 'Original content');
    }

    public function test_cannot_start_editing_other_users_comment(): void
    {
        $otherUser = User::factory()->create();
        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->call('startEditing', $comment->id)
            ->assertSet('editingCommentId', null)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' &&
                str_contains($data['message'], 'only edit your own'));
    }

    public function test_can_update_own_comment(): void
    {
        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Original content',
        ]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('editingCommentId', $comment->id)
            ->set('editingContent', 'Updated content')
            ->call('updateComment')
            ->assertSet('editingCommentId', null)
            ->assertSet('editingContent', '')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');

        $this->assertDatabaseHas('deployment_comments', [
            'id' => $comment->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_update_comment_validates_required(): void
    {
        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('editingCommentId', $comment->id)
            ->set('editingContent', '')
            ->call('updateComment')
            ->assertHasErrors(['editingContent' => 'required']);
    }

    public function test_update_comment_validates_max_length(): void
    {
        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('editingCommentId', $comment->id)
            ->set('editingContent', str_repeat('a', 5001))
            ->call('updateComment')
            ->assertHasErrors(['editingContent' => 'max']);
    }

    public function test_cannot_update_other_users_comment(): void
    {
        $otherUser = User::factory()->create();
        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $otherUser->id,
            'content' => 'Original content',
        ]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('editingCommentId', $comment->id)
            ->set('editingContent', 'Hacked content')
            ->call('updateComment')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error');

        $this->assertDatabaseHas('deployment_comments', [
            'id' => $comment->id,
            'content' => 'Original content',
        ]);
    }

    public function test_update_comment_extracts_new_mentions(): void
    {
        $mentionedUser = User::factory()->create(['name' => 'NewUser']);
        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Original without mentions',
            'mentions' => [],
        ]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('editingCommentId', $comment->id)
            ->set('editingContent', 'Updated with @NewUser mention')
            ->call('updateComment');

        $comment->refresh();
        $mentions = $comment->mentions;
        $this->assertIsArray($mentions);
        $this->assertContains($mentionedUser->id, $mentions);
    }

    public function test_can_cancel_editing(): void
    {
        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('editingCommentId', $comment->id)
            ->set('editingContent', 'Some content')
            ->call('cancelEditing')
            ->assertSet('editingCommentId', null)
            ->assertSet('editingContent', '');
    }

    // ===== DELETE COMMENT =====

    public function test_can_delete_own_comment(): void
    {
        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->call('deleteComment', $comment->id)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');

        $this->assertDatabaseMissing('deployment_comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_cannot_delete_other_users_comment(): void
    {
        $otherUser = User::factory()->create();
        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->call('deleteComment', $comment->id)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' &&
                str_contains($data['message'], 'only delete your own'));

        $this->assertDatabaseHas('deployment_comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_admin_can_delete_other_users_comment(): void
    {
        // Create the permission if it doesn't exist
        Permission::firstOrCreate(['name' => 'manage_all_comments', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->givePermissionTo('manage_all_comments');

        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->call('deleteComment', $comment->id)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');

        $this->assertDatabaseMissing('deployment_comments', [
            'id' => $comment->id,
        ]);
    }

    // ===== EVENTS =====

    public function test_comment_added_event_refreshes_comments(): void
    {
        DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
        ]);

        $this->actingAs($this->user);

        // Manually dispatch the event - this would typically come from another component
        $component = Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->dispatch('comment-added');

        // The component should handle the event without error and still render
        $component->assertStatus(200);
    }

    // ===== COMPUTED PROPERTY =====

    public function test_comments_ordered_by_latest(): void
    {
        $oldComment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'created_at' => now()->subHour(),
        ]);

        $newComment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'created_at' => now(),
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment]);

        $comments = $component->viewData('comments');
        $this->assertEquals($newComment->id, $comments->first()->id);
        $this->assertEquals($oldComment->id, $comments->last()->id);
    }

    public function test_comments_eager_loads_user(): void
    {
        DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment]);

        $comments = $component->viewData('comments');
        $this->assertTrue($comments->first()->relationLoaded('user'));
    }

    // ===== DEFAULT VALUES =====

    public function test_default_property_values(): void
    {
        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->assertSet('newComment', '')
            ->assertSet('editingCommentId', null)
            ->assertSet('editingContent', '');
    }

    // ===== ISOLATION =====

    public function test_comments_belong_to_deployment(): void
    {
        // Create another deployment with comments
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);
        $otherDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
        ]);

        DeploymentComment::factory()->count(2)->create([
            'deployment_id' => $otherDeployment->id,
        ]);

        DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment]);

        $comments = $component->viewData('comments');
        $this->assertCount(1, $comments);
        $this->assertEquals($this->deployment->id, $comments->first()->deployment_id);
    }

    // ===== MODEL METHODS =====

    public function test_extract_mentions_with_multiple_users(): void
    {
        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);

        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('newComment', 'Hey @Alice and @Bob, please check this')
            ->call('addComment');

        $comment = DeploymentComment::where('deployment_id', $this->deployment->id)->first();
        $this->assertNotNull($comment);
        $mentions = $comment->mentions;
        $this->assertIsArray($mentions);
        $this->assertCount(2, $mentions);
        $this->assertContains($user1->id, $mentions);
        $this->assertContains($user2->id, $mentions);
    }

    public function test_formatted_content_includes_mention_styling(): void
    {
        $mentionedUser = User::factory()->create(['name' => 'TestUser']);

        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Hello @TestUser',
            'mentions' => [$mentionedUser->id],
        ]);

        $formatted = $comment->formatted_content;
        $this->assertStringContainsString('text-blue-600', $formatted);
        $this->assertStringContainsString('@TestUser', $formatted);
    }

    // ===== EDGE CASES =====

    public function test_delete_nonexistent_comment_throws(): void
    {
        $this->actingAs($this->user);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->call('deleteComment', 99999);
    }

    public function test_start_editing_nonexistent_comment_throws(): void
    {
        $this->actingAs($this->user);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->call('startEditing', 99999);
    }

    public function test_update_nonexistent_comment_throws(): void
    {
        $this->actingAs($this->user);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('editingCommentId', 99999)
            ->set('editingContent', 'Test')
            ->call('updateComment');
    }

    public function test_multiple_comments_from_same_user(): void
    {
        $this->actingAs($this->user);

        Livewire::test(DeploymentComments::class, ['deployment' => $this->deployment])
            ->set('newComment', 'First comment')
            ->call('addComment')
            ->set('newComment', 'Second comment')
            ->call('addComment')
            ->set('newComment', 'Third comment')
            ->call('addComment');

        $comments = DeploymentComment::where('deployment_id', $this->deployment->id)
            ->where('user_id', $this->user->id)
            ->get();

        $this->assertCount(3, $comments);
    }
}
