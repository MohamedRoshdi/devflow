<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Home\HomePublic;
use App\Models\Server;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests for HomePublic Livewire component.
 *
 * HomePublic is a marketing landing page that displays promotional content
 * for DevFlow Pro. It does not display project listings, search functionality,
 * or server information.
 */
class HomePublicTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'status' => 'online',
            'ip_address' => '192.168.1.100',
            'name' => 'Production Server',
        ]);
    }

    #[Test]
    public function component_renders_successfully_on_public_home_route(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(HomePublic::class);
    }

    #[Test]
    public function component_uses_marketing_layout(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        // The component uses layouts.marketing as specified in the render method
        $response->assertSee('NileStack');
        $response->assertSee('DevFlow Pro');
    }

    #[Test]
    public function component_displays_marketing_content(): void
    {
        // The home page is a marketing page, so it should show marketing content
        // regardless of projects in the database
        Livewire::test(HomePublic::class)
            ->assertSee('Deploy production apps in minutes, not days')
            ->assertSee('Platform Status: Operational')
            ->assertSee('DevFlow Pro');
    }

    #[Test]
    public function server_information_is_not_exposed_to_public(): void
    {
        // Security test: ensure server details are not leaked on public marketing page
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertDontSee('192.168.1.100');
        $response->assertDontSee('Production Server');
    }

    #[Test]
    public function marketing_page_is_accessible_without_authentication(): void
    {
        // Ensure the marketing page is publicly accessible
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertDontSee('Login required');
    }
}
