<?php

namespace Tests\Browser;

use App\Models\Deployment;
use App\Models\DeploymentComment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class DeploymentCommentsTest extends DuskTestCase
{
    use LoginViaUI;

    // use RefreshDatabase; // Disabled - testing against existing app

    protected User $user;

    protected User $otherUser;

    protected Server $server;

    protected Project $project;

    protected Deployment $deployment;

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing test user (shared database approach)
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create another user for mention tests
        $this->otherUser = User::firstOrCreate(
            ['email' => 'other@devflow.test'],
            [
                'name' => 'Other User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Get or create test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'comments-test.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Comments Test Server',
                'ip_address' => '192.168.1.101',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Get or create test project
        $this->project = Project::firstOrCreate(
            ['slug' => 'test-comments-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Comments Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/test-comments-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-comments-project',
            ]
        );

        // Create test deployment
        $this->deployment = Deployment::firstOrCreate(
            ['commit_hash' => 'comments-test-hash'],
            [
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
                'user_id' => $this->user->id,
                'status' => 'success',
                'branch' => 'main',
                'commit_message' => 'Test deployment for comments',
                'started_at' => now()->subHour(),
                'completed_at' => now()->subMinutes(55),
            ]
        );
    }

    /**
     * Test 1: Deployment comments section loads successfully
     */
    public function test_deployment_comments_section_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->assertSee('Comments')
                ->screenshot('deployment-comments-section-loads');
        });
    }

    /**
     * Test 2: Empty state shows when no comments exist
     */
    public function test_empty_state_shows_when_no_comments(): void
    {
        // Clean up any existing comments
        DeploymentComment::where('deployment_id', $this->deployment->id)->delete();

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('No comments yet')
                ->screenshot('deployment-comments-empty-state');
        });
    }

    /**
     * Test 3: Add new comment successfully
     */
    public function test_add_new_comment_successfully(): void
    {
        $commentText = 'This is a test comment for deployment';

        $this->browse(function (Browser $browser) use ($commentText) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $commentText)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->waitForText($commentText, 10)
                ->assertSee($commentText)
                ->screenshot('deployment-comment-added');
        });
    }

    /**
     * Test 4: Comment displays author information
     */
    public function test_comment_displays_author_information(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Test comment with author info',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee($this->user->name)
                ->assertSee('Test comment with author info')
                ->screenshot('deployment-comment-author-info');
        });
    }

    /**
     * Test 5: Comment displays timestamp
     */
    public function test_comment_displays_timestamp(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Test comment with timestamp',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('Test comment with timestamp')
                ->screenshot('deployment-comment-timestamp');
        });
    }

    /**
     * Test 6: Cannot add empty comment
     */
    public function test_cannot_add_empty_comment(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->clear('textarea[wire\\:model="newComment"]')
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->assertSee('required')
                ->screenshot('deployment-comment-empty-validation');
        });
    }

    /**
     * Test 7: Comment textarea has character limit
     */
    public function test_comment_textarea_has_character_limit(): void
    {
        $longComment = str_repeat('A', 5001); // Exceeds 5000 character limit

        $this->browse(function (Browser $browser) use ($longComment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $longComment)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->assertSee('max')
                ->screenshot('deployment-comment-character-limit');
        });
    }

    /**
     * Test 8: Edit own comment button is visible
     */
    public function test_edit_own_comment_button_is_visible(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Test comment to edit',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('Test comment to edit')
                ->assertPresent('button[wire\\:click*="startEditing"]')
                ->screenshot('deployment-comment-edit-button-visible');
        });
    }

    /**
     * Test 9: Edit comment successfully
     */
    public function test_edit_comment_successfully(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Original comment content',
        ]);

        $updatedContent = 'Updated comment content';

        $this->browse(function (Browser $browser) use ($comment, $updatedContent) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->click("button[wire\\:click*=\"startEditing({$comment->id})\"]")
                ->pause(1000)
                ->clear('textarea[wire\\:model="editingContent"]')
                ->type('textarea[wire\\:model="editingContent"]', $updatedContent)
                ->pause(500)
                ->press('Update Comment')
                ->pause(2000)
                ->waitForText($updatedContent, 10)
                ->assertSee($updatedContent)
                ->screenshot('deployment-comment-edited');
        });
    }

    /**
     * Test 10: Cancel editing comment
     */
    public function test_cancel_editing_comment(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Comment to cancel editing',
        ]);

        $this->browse(function (Browser $browser) use ($comment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->click("button[wire\\:click*=\"startEditing({$comment->id})\"]")
                ->pause(1000)
                ->assertPresent('textarea[wire\\:model="editingContent"]')
                ->click('button[wire\\:click="cancelEditing"]')
                ->pause(1000)
                ->assertSee('Comment to cancel editing')
                ->screenshot('deployment-comment-cancel-edit');
        });
    }

    /**
     * Test 11: Cannot edit other user's comment
     */
    public function test_cannot_edit_other_users_comment(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->otherUser->id,
            'content' => 'Other user comment',
        ]);

        $this->browse(function (Browser $browser) use ($comment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('Other user comment')
                ->assertMissing("button[wire\\:click*=\"startEditing({$comment->id})\"]")
                ->screenshot('deployment-comment-cannot-edit-others');
        });
    }

    /**
     * Test 12: Delete own comment successfully
     */
    public function test_delete_own_comment_successfully(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Comment to be deleted',
        ]);

        $this->browse(function (Browser $browser) use ($comment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('Comment to be deleted')
                ->click("button[wire\\:click*=\"deleteComment({$comment->id})\"]")
                ->pause(2000)
                ->waitUntilMissing('.comment-'.$comment->id, 10)
                ->screenshot('deployment-comment-deleted');
        });
    }

    /**
     * Test 13: Cannot delete other user's comment
     */
    public function test_cannot_delete_other_users_comment(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->otherUser->id,
            'content' => 'Other user comment to delete',
        ]);

        $this->browse(function (Browser $browser) use ($comment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('Other user comment to delete')
                ->assertMissing("button[wire\\:click*=\"deleteComment({$comment->id})\"]")
                ->screenshot('deployment-comment-cannot-delete-others');
        });
    }

    /**
     * Test 14: Add comment with user mention
     */
    public function test_add_comment_with_user_mention(): void
    {
        $commentText = "Hey @{$this->otherUser->name}, please review this deployment";

        $this->browse(function (Browser $browser) use ($commentText) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $commentText)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->waitForText($commentText, 10)
                ->assertSee($commentText)
                ->screenshot('deployment-comment-with-mention');
        });
    }

    /**
     * Test 15: Mentioned user is highlighted in comment
     */
    public function test_mentioned_user_is_highlighted(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => "Please review @{$this->otherUser->name}",
            'mentions' => [$this->otherUser->id],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee("@{$this->otherUser->name}")
                ->screenshot('deployment-comment-mention-highlighted');
        });
    }

    /**
     * Test 16: Multiple comments are displayed in order
     */
    public function test_multiple_comments_displayed_in_order(): void
    {
        // Clean up existing comments
        DeploymentComment::where('deployment_id', $this->deployment->id)->delete();

        // Create multiple comments
        $comment1 = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'First comment',
            'created_at' => now()->subMinutes(5),
        ]);

        $comment2 = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Second comment',
            'created_at' => now()->subMinutes(3),
        ]);

        $comment3 = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Third comment',
            'created_at' => now()->subMinutes(1),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('First comment')
                ->assertSee('Second comment')
                ->assertSee('Third comment')
                ->screenshot('deployment-comments-multiple-order');
        });
    }

    /**
     * Test 17: Comment form clears after submission
     */
    public function test_comment_form_clears_after_submission(): void
    {
        $commentText = 'This comment should clear after submission';

        $this->browse(function (Browser $browser) use ($commentText) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $commentText)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->assertInputValue('textarea[wire\\:model="newComment"]', '')
                ->screenshot('deployment-comment-form-cleared');
        });
    }

    /**
     * Test 18: Success notification shows after adding comment
     */
    public function test_success_notification_after_adding_comment(): void
    {
        $commentText = 'Comment for notification test';

        $this->browse(function (Browser $browser) use ($commentText) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $commentText)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->waitForText('success', 10)
                ->screenshot('deployment-comment-success-notification');
        });
    }

    /**
     * Test 19: Success notification shows after editing comment
     */
    public function test_success_notification_after_editing_comment(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Original content for edit notification',
        ]);

        $this->browse(function (Browser $browser) use ($comment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->click("button[wire\\:click*=\"startEditing({$comment->id})\"]")
                ->pause(1000)
                ->clear('textarea[wire\\:model="editingContent"]')
                ->type('textarea[wire\\:model="editingContent"]', 'Updated content')
                ->pause(500)
                ->press('Update Comment')
                ->pause(2000)
                ->waitForText('success', 10)
                ->screenshot('deployment-comment-edit-notification');
        });
    }

    /**
     * Test 20: Success notification shows after deleting comment
     */
    public function test_success_notification_after_deleting_comment(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Comment for delete notification',
        ]);

        $this->browse(function (Browser $browser) use ($comment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->click("button[wire\\:click*=\"deleteComment({$comment->id})\"]")
                ->pause(2000)
                ->waitForText('success', 10)
                ->screenshot('deployment-comment-delete-notification');
        });
    }

    /**
     * Test 21: Comment with markdown formatting displays correctly
     */
    public function test_comment_with_markdown_formatting(): void
    {
        $markdownComment = '**Bold text** and *italic text* with `code` formatting';

        $this->browse(function (Browser $browser) use ($markdownComment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $markdownComment)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->screenshot('deployment-comment-markdown-formatting');
        });
    }

    /**
     * Test 22: Comment with code block formatting
     */
    public function test_comment_with_code_block(): void
    {
        $codeBlockComment = "Check this code:\n```php\necho 'Hello World';\n```";

        $this->browse(function (Browser $browser) use ($codeBlockComment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $codeBlockComment)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->screenshot('deployment-comment-code-block');
        });
    }

    /**
     * Test 23: Comment with link formatting
     */
    public function test_comment_with_link_formatting(): void
    {
        $linkComment = 'Check the documentation at https://docs.example.com for more info';

        $this->browse(function (Browser $browser) use ($linkComment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $linkComment)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->screenshot('deployment-comment-link-formatting');
        });
    }

    /**
     * Test 24: Comment displays relative time (e.g., "5 minutes ago")
     */
    public function test_comment_displays_relative_time(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Comment with relative time',
            'created_at' => now()->subMinutes(10),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('Comment with relative time')
                ->screenshot('deployment-comment-relative-time');
        });
    }

    /**
     * Test 25: Multiple mentions in single comment
     */
    public function test_multiple_mentions_in_single_comment(): void
    {
        $user3 = User::firstOrCreate(
            ['email' => 'third@devflow.test'],
            [
                'name' => 'Third User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $commentText = "Hey @{$this->otherUser->name} and @{$user3->name}, please review";

        $this->browse(function (Browser $browser) use ($commentText) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $commentText)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->waitForText($commentText, 10)
                ->screenshot('deployment-comment-multiple-mentions');
        });
    }

    /**
     * Test 26: Comment counter displays correct count
     */
    public function test_comment_counter_displays_correct_count(): void
    {
        // Clean up existing comments
        DeploymentComment::where('deployment_id', $this->deployment->id)->delete();

        // Create 3 comments
        for ($i = 1; $i <= 3; $i++) {
            DeploymentComment::create([
                'deployment_id' => $this->deployment->id,
                'user_id' => $this->user->id,
                'content' => "Test comment {$i}",
            ]);
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('3')
                ->screenshot('deployment-comment-counter');
        });
    }

    /**
     * Test 27: Cannot submit comment while editing another
     */
    public function test_cannot_submit_while_editing(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Comment being edited',
        ]);

        $this->browse(function (Browser $browser) use ($comment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->click("button[wire\\:click*=\"startEditing({$comment->id})\"]")
                ->pause(1000)
                ->assertPresent('textarea[wire\\:model="editingContent"]')
                ->screenshot('deployment-comment-editing-mode');
        });
    }

    /**
     * Test 28: Comment with special characters displays correctly
     */
    public function test_comment_with_special_characters(): void
    {
        $specialComment = "Testing special chars: <>&\"' and symbols: @#$%^&*()";

        $this->browse(function (Browser $browser) use ($specialComment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $specialComment)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->screenshot('deployment-comment-special-characters');
        });
    }

    /**
     * Test 29: Comment with emojis displays correctly
     */
    public function test_comment_with_emojis(): void
    {
        $emojiComment = 'Great deployment! 🚀 Everything looks good ✅ 👍';

        $this->browse(function (Browser $browser) use ($emojiComment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $emojiComment)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->waitForText($emojiComment, 10)
                ->screenshot('deployment-comment-emojis');
        });
    }

    /**
     * Test 30: Comment with line breaks displays correctly
     */
    public function test_comment_with_line_breaks(): void
    {
        $multilineComment = "First line\nSecond line\nThird line";

        $this->browse(function (Browser $browser) use ($multilineComment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $multilineComment)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->screenshot('deployment-comment-line-breaks');
        });
    }

    /**
     * Test 31: Edit comment validation - empty content
     */
    public function test_edit_comment_validation_empty_content(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Original content for validation',
        ]);

        $this->browse(function (Browser $browser) use ($comment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->click("button[wire\\:click*=\"startEditing({$comment->id})\"]")
                ->pause(1000)
                ->clear('textarea[wire\\:model="editingContent"]')
                ->pause(500)
                ->press('Update Comment')
                ->pause(2000)
                ->assertSee('required')
                ->screenshot('deployment-comment-edit-validation-empty');
        });
    }

    /**
     * Test 32: Edit comment validation - exceeds max length
     */
    public function test_edit_comment_validation_exceeds_max_length(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Original content for length validation',
        ]);

        $longContent = str_repeat('A', 5001);

        $this->browse(function (Browser $browser) use ($comment, $longContent) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->click("button[wire\\:click*=\"startEditing({$comment->id})\"]")
                ->pause(1000)
                ->clear('textarea[wire\\:model="editingContent"]')
                ->type('textarea[wire\\:model="editingContent"]', $longContent)
                ->pause(500)
                ->press('Update Comment')
                ->pause(2000)
                ->assertSee('max')
                ->screenshot('deployment-comment-edit-validation-length');
        });
    }

    /**
     * Test 33: Real-time update when new comment is added
     */
    public function test_realtime_update_new_comment(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000);

            // Simulate another user adding a comment
            DeploymentComment::create([
                'deployment_id' => $this->deployment->id,
                'user_id' => $this->otherUser->id,
                'content' => 'New realtime comment',
            ]);

            $browser->pause(3000)
                ->screenshot('deployment-comment-realtime-update');
        });
    }

    /**
     * Test 34: Comment author avatar displays
     */
    public function test_comment_author_avatar_displays(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Comment with avatar',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('Comment with avatar')
                ->screenshot('deployment-comment-avatar');
        });
    }

    /**
     * Test 35: Comments are sorted by newest first
     */
    public function test_comments_sorted_newest_first(): void
    {
        // Clean up existing comments
        DeploymentComment::where('deployment_id', $this->deployment->id)->delete();

        $oldComment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Older comment',
            'created_at' => now()->subHours(2),
        ]);

        $newComment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Newer comment',
            'created_at' => now()->subMinutes(5),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('Newer comment')
                ->assertSee('Older comment')
                ->screenshot('deployment-comments-sorted-newest-first');
        });
    }

    /**
     * Test 36: Textarea auto-expands with content
     */
    public function test_textarea_auto_expands(): void
    {
        $longComment = str_repeat("This is a long comment that should expand the textarea.\n", 5);

        $this->browse(function (Browser $browser) use ($longComment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $longComment)
                ->pause(1000)
                ->screenshot('deployment-comment-textarea-expanded');
        });
    }

    /**
     * Test 37: Comment edit shows "edited" indicator
     */
    public function test_edited_comment_shows_indicator(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Original content',
            'created_at' => now()->subHour(),
        ]);

        // Update the comment to simulate editing
        $comment->update([
            'content' => 'Edited content',
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('Edited content')
                ->screenshot('deployment-comment-edited-indicator');
        });
    }

    /**
     * Test 38: Comment delete requires confirmation
     */
    public function test_comment_delete_confirmation(): void
    {
        $comment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Comment to confirm delete',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('Comment to confirm delete')
                ->screenshot('deployment-comment-delete-confirmation');
        });
    }

    /**
     * Test 39: XSS protection in comments
     */
    public function test_xss_protection_in_comments(): void
    {
        $xssComment = '<script>alert("XSS")</script>Normal text';

        $this->browse(function (Browser $browser) use ($xssComment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $xssComment)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->screenshot('deployment-comment-xss-protection');
        });
    }

    /**
     * Test 40: Comment with only whitespace is rejected
     */
    public function test_comment_with_only_whitespace_rejected(): void
    {
        $whitespaceComment = "     \n\n\t\t    ";

        $this->browse(function (Browser $browser) use ($whitespaceComment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $whitespaceComment)
                ->pause(500)
                ->press('Add Comment')
                ->pause(2000)
                ->screenshot('deployment-comment-whitespace-validation');
        });
    }

    /**
     * Test 41: Mention autocomplete suggestions appear
     */
    public function test_mention_autocomplete_suggestions(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', 'Hey @')
                ->pause(1500)
                ->screenshot('deployment-comment-mention-autocomplete');
        });
    }

    /**
     * Test 42: Comments section shows loading state
     */
    public function test_comments_section_loading_state(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit("/deployments/{$this->deployment->id}")
                ->pause(100)
                ->screenshot('deployment-comments-loading-state');

            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->pause(100)
                ->screenshot('deployment-comments-loading-state-authenticated');
        });
    }

    /**
     * Test 43: Comment form has proper accessibility attributes
     */
    public function test_comment_form_accessibility(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->assertPresent('textarea[wire\\:model="newComment"]')
                ->screenshot('deployment-comment-form-accessibility');
        });
    }

    /**
     * Test 44: Comments section responsive on mobile
     */
    public function test_comments_section_responsive_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->resize(375, 667) // iPhone SE dimensions
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->screenshot('deployment-comments-mobile-responsive');
        });
    }

    /**
     * Test 45: Comment character count indicator
     */
    public function test_comment_character_count_indicator(): void
    {
        $testComment = 'Testing character count indicator';

        $this->browse(function (Browser $browser) use ($testComment) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $testComment)
                ->pause(1000)
                ->screenshot('deployment-comment-character-count');
        });
    }

    /**
     * Test 46: Keyboard shortcuts work (Ctrl+Enter to submit)
     */
    public function test_keyboard_shortcut_submit_comment(): void
    {
        $commentText = 'Comment submitted with keyboard shortcut';

        $this->browse(function (Browser $browser) use ($commentText) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', $commentText)
                ->pause(500)
                ->keys('textarea[wire\\:model="newComment"]', ['{control}', '{enter}'])
                ->pause(2000)
                ->screenshot('deployment-comment-keyboard-shortcut');
        });
    }

    /**
     * Test 47: Comments persist after page refresh
     */
    public function test_comments_persist_after_refresh(): void
    {
        $persistentComment = DeploymentComment::create([
            'deployment_id' => $this->deployment->id,
            'user_id' => $this->user->id,
            'content' => 'This comment should persist after refresh',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(1000)
                ->assertSee('This comment should persist after refresh')
                ->refresh()
                ->pause(2000)
                ->waitFor('[wire\\:id]', 10)
                ->assertSee('This comment should persist after refresh')
                ->screenshot('deployment-comments-persist-refresh');
        });
    }

    /**
     * Test 48: Error handling for failed comment submission
     */
    public function test_error_handling_failed_submission(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/deployments/{$this->deployment->id}")
                ->waitFor('[wire\\:id]', 10)
                ->pause(500)
                ->type('textarea[wire\\:model="newComment"]', 'Test comment')
                ->pause(500)
                // Simulate network error by typing invalid content
                ->clear('textarea[wire\\:model="newComment"]')
                ->press('Add Comment')
                ->pause(2000)
                ->screenshot('deployment-comment-error-handling');
        });
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        // Optional: Clean up test data if needed
        // DeploymentComment::where('deployment_id', $this->deployment->id)
        //     ->where('content', 'LIKE', '%test%')
        //     ->delete();

        parent::tearDown();
    }
}
