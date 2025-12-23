<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function app_layout_sidebar_has_flex_class_for_proper_scrolling(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);

        // The sidebar should use md:flex (not md:block) to enable proper scrolling
        // This is critical because flex-col + overflow-y-auto requires flex display
        $response->assertSee('hidden md:flex flex-col', escape: false);
    }

    #[Test]
    public function health_route_uses_system_health_path(): void
    {
        // Verify the route is at /system-health to avoid nginx /health endpoint conflict
        $this->assertEquals('/system-health', route('health.dashboard', [], false));
    }

    #[Test]
    public function authenticated_user_sees_sidebar_navigation(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);

        // Verify key sidebar navigation items are present
        $response->assertSee('Dashboard');
        $response->assertSee('Servers');
        $response->assertSee('Projects');
    }
}
