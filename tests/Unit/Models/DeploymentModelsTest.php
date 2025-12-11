<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Deployment;
use App\Models\DeploymentApproval;
use App\Models\DeploymentComment;
use App\Models\User;
use Tests\TestCase;

class DeploymentModelsTest extends TestCase
{
    // ========================
    // DeploymentApproval Model Tests
    // ========================

    /** @test */
    public function deployment_approval_can_be_created_with_factory(): void
    {
        $approval = DeploymentApproval::factory()->create();

        $this->assertInstanceOf(DeploymentApproval::class, $approval);
        $this->assertDatabaseHas('deployment_approvals', [
            'id' => $approval->id,
        ]);
    }

    /** @test */
    public function deployment_approval_belongs_to_deployment(): void
    {
        $deployment = Deployment::factory()->create();
        $approval = DeploymentApproval::factory()->create(['deployment_id' => $deployment->id]);

        $this->assertInstanceOf(Deployment::class, $approval->deployment);
        $this->assertEquals($deployment->id, $approval->deployment->id);
    }

    /** @test */
    public function deployment_approval_belongs_to_requester(): void
    {
        $user = User::factory()->create();
        $approval = DeploymentApproval::factory()->create(['requested_by' => $user->id]);

        $this->assertInstanceOf(User::class, $approval->requester);
        $this->assertEquals($user->id, $approval->requester->id);
    }

    /** @test */
    public function deployment_approval_belongs_to_approver(): void
    {
        $user = User::factory()->create();
        $approval = DeploymentApproval::factory()->create(['approved_by' => $user->id]);

        $this->assertInstanceOf(User::class, $approval->approver);
        $this->assertEquals($user->id, $approval->approver->id);
    }

    /** @test */
    public function deployment_approval_casts_datetime_attributes(): void
    {
        $approval = DeploymentApproval::factory()->create([
            'requested_at' => now()->subHours(2),
            'responded_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $approval->requested_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $approval->responded_at);
    }

    /** @test */
    public function deployment_approval_is_pending_returns_true_when_pending(): void
    {
        $approval = DeploymentApproval::factory()->create(['status' => 'pending']);

        $this->assertTrue($approval->isPending());
    }

    /** @test */
    public function deployment_approval_is_pending_returns_false_when_not_pending(): void
    {
        $approval = DeploymentApproval::factory()->create(['status' => 'approved']);

        $this->assertFalse($approval->isPending());
    }

    /** @test */
    public function deployment_approval_is_approved_returns_true_when_approved(): void
    {
        $approval = DeploymentApproval::factory()->create(['status' => 'approved']);

        $this->assertTrue($approval->isApproved());
    }

    /** @test */
    public function deployment_approval_is_approved_returns_false_when_not_approved(): void
    {
        $approval = DeploymentApproval::factory()->create(['status' => 'rejected']);

        $this->assertFalse($approval->isApproved());
    }

    /** @test */
    public function deployment_approval_is_rejected_returns_true_when_rejected(): void
    {
        $approval = DeploymentApproval::factory()->create(['status' => 'rejected']);

        $this->assertTrue($approval->isRejected());
    }

    /** @test */
    public function deployment_approval_is_rejected_returns_false_when_not_rejected(): void
    {
        $approval = DeploymentApproval::factory()->create(['status' => 'approved']);

        $this->assertFalse($approval->isRejected());
    }

    /** @test */
    public function deployment_approval_status_color_returns_correct_colors(): void
    {
        $approved = DeploymentApproval::factory()->create(['status' => 'approved']);
        $this->assertEquals('green', $approved->status_color);

        $rejected = DeploymentApproval::factory()->create(['status' => 'rejected']);
        $this->assertEquals('red', $rejected->status_color);

        $pending = DeploymentApproval::factory()->create(['status' => 'pending']);
        $this->assertEquals('yellow', $pending->status_color);

        $unknown = DeploymentApproval::factory()->create(['status' => 'unknown']);
        $this->assertEquals('gray', $unknown->status_color);
    }

    /** @test */
    public function deployment_approval_status_icon_returns_correct_icons(): void
    {
        $approved = DeploymentApproval::factory()->create(['status' => 'approved']);
        $this->assertEquals('check-circle', $approved->status_icon);

        $rejected = DeploymentApproval::factory()->create(['status' => 'rejected']);
        $this->assertEquals('x-circle', $rejected->status_icon);

        $pending = DeploymentApproval::factory()->create(['status' => 'pending']);
        $this->assertEquals('clock', $pending->status_icon);

        $unknown = DeploymentApproval::factory()->create(['status' => 'unknown']);
        $this->assertEquals('question-mark-circle', $unknown->status_icon);
    }

    // ========================
    // DeploymentComment Model Tests
    // ========================

    /** @test */
    public function deployment_comment_can_be_created_with_factory(): void
    {
        $comment = DeploymentComment::factory()->create();

        $this->assertInstanceOf(DeploymentComment::class, $comment);
        $this->assertDatabaseHas('deployment_comments', [
            'id' => $comment->id,
        ]);
    }

    /** @test */
    public function deployment_comment_belongs_to_deployment(): void
    {
        $deployment = Deployment::factory()->create();
        $comment = DeploymentComment::factory()->create(['deployment_id' => $deployment->id]);

        $this->assertInstanceOf(Deployment::class, $comment->deployment);
        $this->assertEquals($deployment->id, $comment->deployment->id);
    }

    /** @test */
    public function deployment_comment_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $comment = DeploymentComment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($user->id, $comment->user->id);
    }

    /** @test */
    public function deployment_comment_casts_mentions_as_array(): void
    {
        $mentions = [1, 2, 3];
        $comment = DeploymentComment::factory()->create(['mentions' => $mentions]);

        $this->assertIsArray($comment->mentions);
        $this->assertEquals($mentions, $comment->mentions);
    }

    /** @test */
    public function deployment_comment_extract_mentions_finds_usernames(): void
    {
        $user1 = User::factory()->create(['name' => 'JohnDoe']);
        $user2 = User::factory()->create(['name' => 'JaneSmith']);

        $comment = DeploymentComment::factory()->create([
            'content' => 'Hey @JohnDoe and @JaneSmith, check this deployment!',
        ]);

        $mentions = $comment->extractMentions();

        $this->assertIsArray($mentions);
        $this->assertContains($user1->id, $mentions);
        $this->assertContains($user2->id, $mentions);
    }

    /** @test */
    public function deployment_comment_extract_mentions_returns_empty_array_when_no_mentions(): void
    {
        $comment = DeploymentComment::factory()->create([
            'content' => 'This is a comment without any mentions',
        ]);

        $mentions = $comment->extractMentions();

        $this->assertIsArray($mentions);
        $this->assertEmpty($mentions);
    }

    /** @test */
    public function deployment_comment_formatted_content_highlights_mentions(): void
    {
        $user = User::factory()->create(['name' => 'JohnDoe']);

        $comment = DeploymentComment::factory()->create([
            'content' => 'Hey @JohnDoe, please review this.',
            'mentions' => [$user->id],
        ]);

        $formatted = $comment->formatted_content;

        $this->assertStringContainsString('<span class="text-blue-600 font-semibold">@JohnDoe</span>', $formatted);
    }

    /** @test */
    public function deployment_comment_formatted_content_returns_original_when_no_mentions(): void
    {
        $comment = DeploymentComment::factory()->create([
            'content' => 'This is a regular comment',
            'mentions' => [],
        ]);

        $formatted = $comment->formatted_content;

        $this->assertEquals('This is a regular comment', $formatted);
    }

    /** @test */
    public function deployment_comment_formatted_content_handles_multiple_mentions(): void
    {
        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);

        $comment = DeploymentComment::factory()->create([
            'content' => '@Alice and @Bob need to see this',
            'mentions' => [$user1->id, $user2->id],
        ]);

        $formatted = $comment->formatted_content;

        $this->assertStringContainsString('<span class="text-blue-600 font-semibold">@Alice</span>', $formatted);
        $this->assertStringContainsString('<span class="text-blue-600 font-semibold">@Bob</span>', $formatted);
    }
}
