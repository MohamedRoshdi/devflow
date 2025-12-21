<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF middleware for web route tests
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    public function test_authenticated_user_can_add_domain_to_project(): void
    {
        $domainData = [
            'domain' => 'example.com',
            'ssl_enabled' => true,
            'auto_renew_ssl' => true,
            'is_primary' => false,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), $domainData);

        $response->assertRedirect(route('projects.show', $this->project));
        $response->assertSessionHas('success', 'Domain added successfully.');

        $this->assertDatabaseHas('domains', [
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'status' => 'pending',
            'ssl_enabled' => true,
        ]);
    }

    public function test_domain_creation_requires_valid_domain(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => '',
                'ssl_enabled' => true,
            ]);

        $response->assertSessionHasErrors(['domain']);
    }

    public function test_domain_creation_validates_domain_length(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => str_repeat('a', 256) . '.com',
                'ssl_enabled' => true,
            ]);

        $response->assertSessionHasErrors(['domain']);
    }

    public function test_authenticated_user_can_update_domain(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'oldexample.com',
        ]);

        $updateData = [
            'domain' => 'newexample.com',
            'ssl_enabled' => true,
            'auto_renew_ssl' => true,
            'is_primary' => true,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain]), $updateData);

        $response->assertRedirect(route('projects.show', $this->project));
        $response->assertSessionHas('success', 'Domain updated successfully.');

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'domain' => 'newexample.com',
            'is_primary' => true,
        ]);
    }

    public function test_authenticated_user_can_delete_domain(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('projects.domains.destroy', [$this->project, $domain]));

        $response->assertRedirect(route('projects.show', $this->project));
        $response->assertSessionHas('success', 'Domain deleted successfully.');

        // Domain uses SoftDeletes, so check for soft deletion
        $this->assertSoftDeleted('domains', [
            'id' => $domain->id,
        ]);
    }

    public function test_guest_cannot_create_domain(): void
    {
        $response = $this->post(route('projects.domains.store', $this->project), [
            'domain' => 'example.com',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_update_domain(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->put(route('projects.domains.update', [$this->project, $domain]), [
            'domain' => 'newexample.com',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_delete_domain(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->delete(route('projects.domains.destroy', [$this->project, $domain]));

        $response->assertRedirect(route('login'));
    }

    public function test_domain_ssl_settings_are_optional(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => 'example.com',
            ]);

        $response->assertRedirect(route('projects.show', $this->project));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('domains', [
            'project_id' => $this->project->id,
            'domain' => 'example.com',
        ]);
    }

    public function test_multiple_domains_can_be_added_to_project(): void
    {
        $domains = ['example1.com', 'example2.com', 'example3.com'];

        foreach ($domains as $domainName) {
            $this->actingAs($this->user)
                ->post(route('projects.domains.store', $this->project), [
                    'domain' => $domainName,
                    'ssl_enabled' => true,
                ]);
        }

        $this->assertDatabaseCount('domains', 3);

        foreach ($domains as $domainName) {
            $this->assertDatabaseHas('domains', [
                'project_id' => $this->project->id,
                'domain' => $domainName,
            ]);
        }
    }

    public function test_primary_domain_flag_can_be_set(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => 'primary.com',
                'is_primary' => true,
            ]);

        $response->assertRedirect(route('projects.show', $this->project));

        $this->assertDatabaseHas('domains', [
            'project_id' => $this->project->id,
            'domain' => 'primary.com',
            'is_primary' => true,
        ]);
    }
}
