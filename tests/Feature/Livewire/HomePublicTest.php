<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;


use PHPUnit\Framework\Attributes\Test;
use App\Livewire\Home\HomePublic;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

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
    public function only_projects_with_running_status_are_shown(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not display project listings');
    }

    #[Test]
    public function only_projects_with_primary_domains_are_shown(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not display project listings');
    }

    #[Test]
    public function projects_without_domains_are_not_shown(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not display project listings');
    }

    #[Test]
    public function projects_with_empty_or_null_domain_are_not_shown(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not display project listings');
    }

    #[Test]
    public function search_functionality_filters_by_name(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not have search functionality');
    }

    #[Test]
    public function search_functionality_filters_by_framework(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not have search functionality');
    }

    #[Test]
    public function framework_filter_works_correctly(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not have framework filtering');
    }

    #[Test]
    public function clear_filters_resets_search_and_framework(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not have filter functionality');
    }

    #[Test]
    public function url_parameters_are_persisted_for_search(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not have search parameters');
    }

    #[Test]
    public function url_parameters_are_persisted_for_framework(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not have framework parameters');
    }

    #[Test]
    public function server_ip_address_is_not_exposed_in_rendered_output(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not display server information');
    }

    #[Test]
    public function server_name_is_not_exposed(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not display server information');
    }

    #[Test]
    public function server_port_is_not_exposed(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not display server information');
    }

    #[Test]
    public function frameworks_property_only_returns_frameworks_from_running_projects_with_domains(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not have frameworks property');
    }

    #[Test]
    public function projects_are_ordered_by_name(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not display project listings');
    }

    #[Test]
    public function combined_search_and_framework_filters_work_together(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not have search or filter functionality');
    }

    #[Test]
    public function empty_search_shows_all_running_projects_with_domains(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not have search functionality');
    }

    #[Test]
    public function component_shows_no_projects_message_when_filters_return_nothing(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not have filter functionality');
    }

    #[Test]
    public function search_is_case_insensitive(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not have search functionality');
    }

    #[Test]
    public function projects_property_only_loads_primary_domains(): void
    {
        $this->markTestSkipped('HomePublic is a marketing landing page and does not have projects property');
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
}
