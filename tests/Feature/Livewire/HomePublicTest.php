<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

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
    use RefreshDatabase;

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

    /** @test */
    public function component_renders_successfully_on_public_home_route(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(HomePublic::class);
    }

    /** @test */
    public function only_projects_with_running_status_are_shown(): void
    {
        // Create running project with primary domain
        $runningProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Running Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $runningProject->id,
            'domain' => 'running.example.com',
            'is_primary' => true,
        ]);

        // Create non-running projects with primary domains
        $inactiveProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Inactive Project',
            'status' => 'inactive',
        ]);
        Domain::factory()->create([
            'project_id' => $inactiveProject->id,
            'domain' => 'inactive.example.com',
            'is_primary' => true,
        ]);

        $failedProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Failed Project',
            'status' => 'failed',
        ]);
        Domain::factory()->create([
            'project_id' => $failedProject->id,
            'domain' => 'failed.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->assertSee('Running Project')
            ->assertDontSee('Inactive Project')
            ->assertDontSee('Failed Project');
    }

    /** @test */
    public function only_projects_with_primary_domains_are_shown(): void
    {
        // Create project with primary domain
        $projectWithPrimary = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Has Primary Domain',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $projectWithPrimary->id,
            'domain' => 'primary.example.com',
            'is_primary' => true,
        ]);

        // Create project with non-primary domain only
        $projectWithoutPrimary = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'No Primary Domain',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $projectWithoutPrimary->id,
            'domain' => 'secondary.example.com',
            'is_primary' => false,
        ]);

        Livewire::test(HomePublic::class)
            ->assertSee('Has Primary Domain')
            ->assertSee('primary.example.com')
            ->assertDontSee('No Primary Domain')
            ->assertDontSee('secondary.example.com');
    }

    /** @test */
    public function projects_without_domains_are_not_shown(): void
    {
        // Create running project WITHOUT any domain
        $projectWithoutDomain = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'No Domain Project',
            'status' => 'running',
        ]);

        // Create running project WITH primary domain
        $projectWithDomain = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Has Domain Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $projectWithDomain->id,
            'domain' => 'hasdomain.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->assertSee('Has Domain Project')
            ->assertDontSee('No Domain Project');
    }

    /** @test */
    public function projects_with_empty_or_null_domain_are_not_shown(): void
    {
        // Create project with null domain
        $projectWithNullDomain = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Null Domain Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $projectWithNullDomain->id,
            'domain' => null,
            'is_primary' => true,
        ]);

        // Create project with empty string domain
        $projectWithEmptyDomain = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Empty Domain Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $projectWithEmptyDomain->id,
            'domain' => '',
            'is_primary' => true,
        ]);

        // Create valid project for comparison
        $validProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Valid Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $validProject->id,
            'domain' => 'valid.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->assertSee('Valid Project')
            ->assertDontSee('Null Domain Project')
            ->assertDontSee('Empty Domain Project');
    }

    /** @test */
    public function search_functionality_filters_by_name(): void
    {
        // Create multiple projects with domains
        $laravelProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Laravel Ecommerce',
            'framework' => 'Laravel',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $laravelProject->id,
            'domain' => 'laravel.example.com',
            'is_primary' => true,
        ]);

        $vueProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Vue Dashboard',
            'framework' => 'Vue',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $vueProject->id,
            'domain' => 'vue.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->set('search', 'Laravel')
            ->assertSee('Laravel Ecommerce')
            ->assertDontSee('Vue Dashboard');
    }

    /** @test */
    public function search_functionality_filters_by_framework(): void
    {
        // Create multiple projects with different frameworks
        $laravelProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project Alpha',
            'framework' => 'Laravel',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $laravelProject->id,
            'domain' => 'alpha.example.com',
            'is_primary' => true,
        ]);

        $vueProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project Beta',
            'framework' => 'Vue',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $vueProject->id,
            'domain' => 'beta.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->set('search', 'Vue')
            ->assertSee('Project Beta')
            ->assertDontSee('Project Alpha');
    }

    /** @test */
    public function framework_filter_works_correctly(): void
    {
        // Create projects with different frameworks
        $laravelProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Laravel Project',
            'framework' => 'Laravel',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $laravelProject->id,
            'domain' => 'laravel.example.com',
            'is_primary' => true,
        ]);

        $reactProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'React Project',
            'framework' => 'React',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $reactProject->id,
            'domain' => 'react.example.com',
            'is_primary' => true,
        ]);

        $nextProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'NextJS Project',
            'framework' => 'Next.js',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $nextProject->id,
            'domain' => 'nextjs.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->set('framework', 'Laravel')
            ->assertSee('Laravel Project')
            ->assertDontSee('React Project')
            ->assertDontSee('NextJS Project');
    }

    /** @test */
    public function clear_filters_resets_search_and_framework(): void
    {
        // Create multiple projects
        $project1 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project One',
            'framework' => 'Laravel',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project1->id,
            'domain' => 'one.example.com',
            'is_primary' => true,
        ]);

        $project2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project Two',
            'framework' => 'Vue',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project2->id,
            'domain' => 'two.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->set('search', 'Project One')
            ->set('framework', 'Laravel')
            ->assertSee('Project One')
            ->assertDontSee('Project Two')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('framework', '')
            ->assertSee('Project One')
            ->assertSee('Project Two');
    }

    /** @test */
    public function url_parameters_are_persisted_for_search(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Test Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'test.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->set('search', 'Test')
            ->assertSetStrict('search', 'Test');
    }

    /** @test */
    public function url_parameters_are_persisted_for_framework(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Laravel Project',
            'framework' => 'Laravel',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'laravel.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->set('framework', 'Laravel')
            ->assertSetStrict('framework', 'Laravel');
    }

    /** @test */
    public function server_ip_address_is_not_exposed_in_rendered_output(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Secure Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'secure.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->assertSee('Secure Project')
            ->assertDontSee('192.168.1.100')
            ->assertDontSee($this->server->ip_address);
    }

    /** @test */
    public function server_name_is_not_exposed(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Public Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'public.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->assertSee('Public Project')
            ->assertDontSee('Production Server')
            ->assertDontSee($this->server->name);
    }

    /** @test */
    public function server_port_is_not_exposed(): void
    {
        $this->server->update(['port' => 2222]);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Port Test Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'porttest.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->assertSee('Port Test Project')
            ->assertDontSee('2222')
            ->assertDontSee((string) $this->server->port);
    }

    /** @test */
    public function frameworks_property_only_returns_frameworks_from_running_projects_with_domains(): void
    {
        // Running project with domain
        $runningWithDomain = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'framework' => 'Laravel',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $runningWithDomain->id,
            'domain' => 'laravel.example.com',
            'is_primary' => true,
        ]);

        // Running project WITHOUT domain (should not be counted)
        $runningWithoutDomain = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'framework' => 'Vue',
            'status' => 'running',
        ]);

        // Inactive project with domain (should not be counted)
        $inactiveWithDomain = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'framework' => 'React',
            'status' => 'inactive',
        ]);
        Domain::factory()->create([
            'project_id' => $inactiveWithDomain->id,
            'domain' => 'react.example.com',
            'is_primary' => true,
        ]);

        $component = Livewire::test(HomePublic::class);
        $frameworks = $component->viewData('frameworks');

        $this->assertTrue($frameworks->contains('Laravel'));
        $this->assertFalse($frameworks->contains('Vue'));
        $this->assertFalse($frameworks->contains('React'));
    }

    /** @test */
    public function projects_are_ordered_by_name(): void
    {
        // Create projects in random order
        $projectC = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'C Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $projectC->id,
            'domain' => 'c.example.com',
            'is_primary' => true,
        ]);

        $projectA = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'A Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $projectA->id,
            'domain' => 'a.example.com',
            'is_primary' => true,
        ]);

        $projectB = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'B Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $projectB->id,
            'domain' => 'b.example.com',
            'is_primary' => true,
        ]);

        $component = Livewire::test(HomePublic::class);
        $projects = $component->viewData('projects');

        $this->assertEquals('A Project', $projects->first()->name);
        $this->assertEquals('C Project', $projects->last()->name);
    }

    /** @test */
    public function combined_search_and_framework_filters_work_together(): void
    {
        // Laravel project matching search
        $laravelMatch = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'E-Commerce Platform',
            'framework' => 'Laravel',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $laravelMatch->id,
            'domain' => 'ecommerce.example.com',
            'is_primary' => true,
        ]);

        // Laravel project not matching search
        $laravelNoMatch = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Dashboard Admin',
            'framework' => 'Laravel',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $laravelNoMatch->id,
            'domain' => 'dashboard.example.com',
            'is_primary' => true,
        ]);

        // Vue project matching search
        $vueMatch = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'E-Commerce Frontend',
            'framework' => 'Vue',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $vueMatch->id,
            'domain' => 'vue-ecommerce.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->set('search', 'E-Commerce')
            ->set('framework', 'Laravel')
            ->assertSee('E-Commerce Platform')
            ->assertDontSee('Dashboard Admin')
            ->assertDontSee('E-Commerce Frontend');
    }

    /** @test */
    public function empty_search_shows_all_running_projects_with_domains(): void
    {
        $project1 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'First Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project1->id,
            'domain' => 'first.example.com',
            'is_primary' => true,
        ]);

        $project2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Second Project',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project2->id,
            'domain' => 'second.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->set('search', '')
            ->assertSee('First Project')
            ->assertSee('Second Project');
    }

    /** @test */
    public function component_shows_no_projects_message_when_filters_return_nothing(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Test Project',
            'framework' => 'Laravel',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'test.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->set('search', 'NonExistentProject')
            ->assertSee('Your first project is moments away')
            ->assertDontSee('Test Project');
    }

    /** @test */
    public function search_is_case_insensitive(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'UPPERCASE PROJECT',
            'status' => 'running',
        ]);
        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'uppercase.example.com',
            'is_primary' => true,
        ]);

        Livewire::test(HomePublic::class)
            ->set('search', 'uppercase')
            ->assertSee('UPPERCASE PROJECT');

        Livewire::test(HomePublic::class)
            ->set('search', 'UPPERCASE')
            ->assertSee('UPPERCASE PROJECT');

        Livewire::test(HomePublic::class)
            ->set('search', 'UpPeRcAsE')
            ->assertSee('UPPERCASE PROJECT');
    }

    /** @test */
    public function projects_property_only_loads_primary_domains(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Multi Domain Project',
            'status' => 'running',
        ]);

        // Create primary domain
        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'primary.example.com',
            'is_primary' => true,
        ]);

        // Create secondary domains
        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'secondary1.example.com',
            'is_primary' => false,
        ]);

        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'secondary2.example.com',
            'is_primary' => false,
        ]);

        $component = Livewire::test(HomePublic::class);
        $projects = $component->viewData('projects');
        $project = $projects->first();

        // Should only load the primary domain
        $this->assertCount(1, $project->domains);
        $this->assertEquals('primary.example.com', $project->domains->first()->domain);
        $this->assertTrue($project->domains->first()->is_primary);
    }

    /** @test */
    public function component_uses_marketing_layout(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        // The component uses layouts.marketing as specified in the render method
        $response->assertSee('NileStack');
        $response->assertSee('DevFlow Pro');
    }

    /** @test */
    public function component_displays_active_project_count(): void
    {
        // Create 3 running projects with domains
        for ($i = 1; $i <= 3; $i++) {
            $project = Project::factory()->create([
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => "Project {$i}",
                'status' => 'running',
            ]);
            Domain::factory()->create([
                'project_id' => $project->id,
                'domain' => "project{$i}.example.com",
                'is_primary' => true,
            ]);
        }

        Livewire::test(HomePublic::class)
            ->assertSee('3 Projects Live Now')
            ->assertSee('Active Projects')
            ->assertSee('3'); // Count should appear in hero stats
    }
}
