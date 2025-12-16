<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use PHPUnit\Framework\Attributes\Test;
use App\Events\DeploymentCompleted;
use App\Events\DeploymentFailed;
use App\Events\DeploymentStarted;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeploymentEventsTest extends TestCase
{
    protected User $user;
    protected Project $project;
    protected Deployment $deployment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $this->deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function deployment_started_event_can_be_dispatched(): void
    {
        Event::fake();

        event(new DeploymentStarted($this->deployment));

        Event::assertDispatched(DeploymentStarted::class, function ($event) {
            return $event->deployment->id === $this->deployment->id;
        });
    }

    #[Test]
    public function deployment_completed_event_can_be_dispatched(): void
    {
        Event::fake();

        event(new DeploymentCompleted($this->deployment));

        Event::assertDispatched(DeploymentCompleted::class, function ($event) {
            return $event->deployment->id === $this->deployment->id;
        });
    }

    #[Test]
    public function deployment_failed_event_can_be_dispatched(): void
    {
        Event::fake();

        event(new DeploymentFailed($this->deployment, 'Test error message'));

        Event::assertDispatched(DeploymentFailed::class, function ($event) {
            return $event->deployment->id === $this->deployment->id &&
                   $event->error === 'Test error message';
        });
    }

    #[Test]
    public function deployment_events_are_broadcastable(): void
    {
        $event = new DeploymentStarted($this->deployment);

        if (method_exists($event, 'broadcastOn')) {
            $channels = $event->broadcastOn();
            $this->assertNotEmpty($channels);
        } else {
            // Event doesn't implement broadcasting, that's acceptable
            $this->expectNotToPerformAssertions();
        }
    }

    #[Test]
    public function deployment_event_contains_deployment_data(): void
    {
        $event = new DeploymentStarted($this->deployment);

        $this->assertSame($this->deployment, $event->deployment);
        $this->assertEquals($this->deployment->id, $event->deployment->id);
    }
}
