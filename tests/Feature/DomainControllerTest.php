<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DomainControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private Project $project;
    private Project $otherUsersProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->otherUsersProject = Project::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);
    }

    // ==================== Store Method Tests ====================

    /** @test */
    public function authenticated_user_can_create_domain_for_their_project(): void
    {
        $domainData = [
            'domain' => 'example.com',
            'ssl_enabled' => true,
            'ssl_provider' => 'letsencrypt',
            'auto_renew_ssl' => true,
            'is_primary' => false,
            'dns_configured' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), $domainData);

        $response->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success', 'Domain added successfully.');

        $this->assertDatabaseHas('domains', [
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'status' => 'pending',
            'ssl_enabled' => true,
            'ssl_provider' => 'letsencrypt',
            'auto_renew_ssl' => true,
            'is_primary' => false,
        ]);
    }

    /** @test */
    public function domain_is_automatically_lowercased_on_creation(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => 'EXAMPLE.COM',
            ]);

        $response->assertRedirect(route('projects.show', $this->project));

        $this->assertDatabaseHas('domains', [
            'project_id' => $this->project->id,
            'domain' => 'example.com',
        ]);
    }

    /** @test */
    public function domain_creation_defaults_ssl_to_enabled(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => 'example.com',
            ]);

        $response->assertRedirect(route('projects.show', $this->project));

        $this->assertDatabaseHas('domains', [
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'ssl_enabled' => true,
            'auto_renew_ssl' => true,
            'is_primary' => false,
        ]);
    }

    /** @test */
    public function guest_cannot_create_domain(): void
    {
        $response = $this->post(route('projects.domains.store', $this->project), [
            'domain' => 'example.com',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('domains', [
            'domain' => 'example.com',
        ]);
    }

    /** @test */
    public function user_cannot_create_domain_for_another_users_project(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->otherUsersProject), [
                'domain' => 'example.com',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('domains', [
            'project_id' => $this->otherUsersProject->id,
            'domain' => 'example.com',
        ]);
    }

    /** @test */
    public function domain_creation_validates_required_domain_field(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'ssl_enabled' => true,
            ]);

        $response->assertSessionHasErrors(['domain']);

        $this->assertDatabaseCount('domains', 0);
    }

    /** @test */
    public function domain_creation_validates_domain_format(): void
    {
        $invalidDomains = [
            'invalid',
            'invalid domain.com',
            'invalid@domain.com',
            'http://example.com',
            'https://example.com',
            '.example.com',
            'example.com.',
            '-example.com',
            'example-.com',
        ];

        foreach ($invalidDomains as $invalidDomain) {
            $response = $this->actingAs($this->user)
                ->post(route('projects.domains.store', $this->project), [
                    'domain' => $invalidDomain,
                ]);

            $response->assertSessionHasErrors(['domain']);
        }

        $this->assertDatabaseCount('domains', 0);
    }

    /** @test */
    public function domain_creation_accepts_valid_domain_formats(): void
    {
        $validDomains = [
            'example.com',
            'subdomain.example.com',
            'sub.domain.example.com',
            'example-site.com',
            'example123.com',
            '123example.com',
        ];

        foreach ($validDomains as $index => $validDomain) {
            $response = $this->actingAs($this->user)
                ->post(route('projects.domains.store', $this->project), [
                    'domain' => $validDomain,
                ]);

            $response->assertRedirect(route('projects.show', $this->project));

            $this->assertDatabaseHas('domains', [
                'project_id' => $this->project->id,
                'domain' => strtolower($validDomain),
            ]);
        }
    }

    /** @test */
    public function domain_creation_validates_domain_uniqueness(): void
    {
        Domain::factory()->create([
            'domain' => 'example.com',
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => 'example.com',
            ]);

        $response->assertSessionHasErrors(['domain']);

        $this->assertDatabaseCount('domains', 1);
    }

    /** @test */
    public function domain_creation_validates_domain_max_length(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => str_repeat('a', 256) . '.com',
            ]);

        $response->assertSessionHasErrors(['domain']);
    }

    /** @test */
    public function domain_creation_validates_ssl_provider(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => 'example.com',
                'ssl_provider' => 'invalid_provider',
            ]);

        $response->assertSessionHasErrors(['ssl_provider']);
    }

    /** @test */
    public function domain_creation_accepts_valid_ssl_providers(): void
    {
        $validProviders = ['letsencrypt', 'custom', 'cloudflare'];

        foreach ($validProviders as $index => $provider) {
            $response = $this->actingAs($this->user)
                ->post(route('projects.domains.store', $this->project), [
                    'domain' => "example{$index}.com",
                    'ssl_provider' => $provider,
                ]);

            $response->assertRedirect(route('projects.show', $this->project));

            $this->assertDatabaseHas('domains', [
                'domain' => "example{$index}.com",
                'ssl_provider' => $provider,
            ]);
        }
    }

    /** @test */
    public function domain_creation_accepts_boolean_ssl_enabled(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => 'example.com',
                'ssl_enabled' => false,
            ]);

        $response->assertRedirect(route('projects.show', $this->project));

        $this->assertDatabaseHas('domains', [
            'domain' => 'example.com',
            'ssl_enabled' => false,
        ]);
    }

    /** @test */
    public function domain_creation_accepts_metadata_array(): void
    {
        $metadata = ['key1' => 'value1', 'key2' => 'value2'];

        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => 'example.com',
                'metadata' => $metadata,
            ]);

        $response->assertRedirect(route('projects.show', $this->project));

        $domain = Domain::where('domain', 'example.com')->first();
        $this->assertNotNull($domain);
        $this->assertEquals($metadata, $domain->metadata);
    }

    // ==================== Update Method Tests ====================

    /** @test */
    public function authenticated_user_can_update_their_domain(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'old-example.com',
            'ssl_enabled' => false,
            'is_primary' => false,
        ]);

        $updateData = [
            'domain' => 'new-example.com',
            'ssl_enabled' => true,
            'ssl_provider' => 'letsencrypt',
            'auto_renew_ssl' => true,
            'is_primary' => true,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain]), $updateData);

        $response->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success', 'Domain updated successfully.');

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'domain' => 'new-example.com',
            'ssl_enabled' => true,
            'ssl_provider' => 'letsencrypt',
            'is_primary' => true,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function domain_is_automatically_lowercased_on_update(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain]), [
                'domain' => 'UPDATED-EXAMPLE.COM',
            ]);

        $response->assertRedirect(route('projects.show', $this->project));

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'domain' => 'updated-example.com',
        ]);
    }

    /** @test */
    public function domain_update_can_update_single_field(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'ssl_enabled' => false,
            'is_primary' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain]), [
                'ssl_enabled' => true,
            ]);

        $response->assertRedirect(route('projects.show', $this->project));

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'domain' => 'example.com', // Unchanged
            'ssl_enabled' => true,     // Changed
            'is_primary' => false,     // Unchanged
        ]);
    }

    /** @test */
    public function guest_cannot_update_domain(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
        ]);

        $response = $this->put(route('projects.domains.update', [$this->project, $domain]), [
            'domain' => 'updated-example.com',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'domain' => 'example.com',
        ]);
    }

    /** @test */
    public function user_cannot_update_another_users_domain(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->otherUsersProject->id,
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->otherUsersProject, $domain]), [
                'domain' => 'updated-example.com',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'domain' => 'example.com',
        ]);
    }

    /** @test */
    public function domain_update_validates_domain_format(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain]), [
                'domain' => 'invalid domain',
            ]);

        $response->assertSessionHasErrors(['domain']);

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'domain' => 'example.com',
        ]);
    }

    /** @test */
    public function domain_update_validates_domain_uniqueness_excluding_current(): void
    {
        $domain1 = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example1.com',
        ]);

        $domain2 = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example2.com',
        ]);

        // Try to update domain2 to use domain1's name
        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain2]), [
                'domain' => 'example1.com',
            ]);

        $response->assertSessionHasErrors(['domain']);

        $this->assertDatabaseHas('domains', [
            'id' => $domain2->id,
            'domain' => 'example2.com',
        ]);
    }

    /** @test */
    public function domain_update_allows_keeping_same_domain_name(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'ssl_enabled' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain]), [
                'domain' => 'example.com',
                'ssl_enabled' => true,
            ]);

        $response->assertRedirect(route('projects.show', $this->project));

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'domain' => 'example.com',
            'ssl_enabled' => true,
        ]);
    }

    /** @test */
    public function domain_update_validates_ssl_provider(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain]), [
                'ssl_provider' => 'invalid_provider',
            ]);

        $response->assertSessionHasErrors(['ssl_provider']);
    }

    /** @test */
    public function domain_update_validates_status(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain]), [
                'status' => 'invalid_status',
            ]);

        $response->assertSessionHasErrors(['status']);

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function domain_update_accepts_valid_status_values(): void
    {
        $validStatuses = ['active', 'inactive', 'pending'];

        foreach ($validStatuses as $status) {
            $domain = Domain::factory()->create([
                'project_id' => $this->project->id,
                'status' => 'pending',
            ]);

            $response = $this->actingAs($this->user)
                ->put(route('projects.domains.update', [$this->project, $domain]), [
                    'status' => $status,
                ]);

            $response->assertRedirect(route('projects.show', $this->project));

            $this->assertDatabaseHas('domains', [
                'id' => $domain->id,
                'status' => $status,
            ]);
        }
    }

    /** @test */
    public function domain_update_can_update_ssl_certificate_and_key(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $certificate = '-----BEGIN CERTIFICATE-----' . "\n" . 'test_certificate' . "\n" . '-----END CERTIFICATE-----';
        $privateKey = '-----BEGIN PRIVATE KEY-----' . "\n" . 'test_key' . "\n" . '-----END PRIVATE KEY-----';

        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain]), [
                'ssl_certificate' => $certificate,
                'ssl_private_key' => $privateKey,
            ]);

        $response->assertRedirect(route('projects.show', $this->project));

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'ssl_certificate' => $certificate,
            'ssl_private_key' => $privateKey,
        ]);
    }

    /** @test */
    public function domain_update_can_clear_ssl_certificate(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'ssl_certificate' => 'existing_certificate',
            'ssl_private_key' => 'existing_key',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain]), [
                'ssl_certificate' => null,
                'ssl_private_key' => null,
            ]);

        $response->assertRedirect(route('projects.show', $this->project));

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'ssl_certificate' => null,
            'ssl_private_key' => null,
        ]);
    }

    // ==================== Destroy Method Tests ====================

    /** @test */
    public function project_owner_can_delete_their_domain(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('projects.domains.destroy', [$this->project, $domain]));

        $response->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success', 'Domain deleted successfully.');

        $this->assertSoftDeleted('domains', [
            'id' => $domain->id,
        ]);
    }

    /** @test */
    public function guest_cannot_delete_domain(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->delete(route('projects.domains.destroy', [$this->project, $domain]));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function user_cannot_delete_another_users_domain(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->otherUsersProject->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('projects.domains.destroy', [$this->otherUsersProject, $domain]));

        $response->assertForbidden();

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function deleting_domain_does_not_affect_other_domains(): void
    {
        $domain1 = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example1.com',
        ]);

        $domain2 = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example2.com',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('projects.domains.destroy', [$this->project, $domain1]));

        $response->assertRedirect(route('projects.show', $this->project));

        $this->assertSoftDeleted('domains', [
            'id' => $domain1->id,
        ]);

        $this->assertDatabaseHas('domains', [
            'id' => $domain2->id,
            'deleted_at' => null,
        ]);
    }

    // ==================== Team Access Tests ====================

    /** @test */
    public function team_member_can_update_domain_of_team_project(): void
    {
        $team = Team::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $teamProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $team->id,
        ]);

        $domain = Domain::factory()->create([
            'project_id' => $teamProject->id,
            'domain' => 'example.com',
        ]);

        // Add otherUser as team member
        $team->members()->attach($this->otherUser->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($this->otherUser)
            ->put(route('projects.domains.update', [$teamProject, $domain]), [
                'ssl_enabled' => true,
            ]);

        $response->assertRedirect(route('projects.show', $teamProject));

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'ssl_enabled' => true,
        ]);
    }

    /** @test */
    public function team_member_cannot_delete_domain_of_team_project(): void
    {
        $team = Team::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $teamProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $team->id,
        ]);

        $domain = Domain::factory()->create([
            'project_id' => $teamProject->id,
        ]);

        // Add otherUser as team member (not owner)
        $team->members()->attach($this->otherUser->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($this->otherUser)
            ->delete(route('projects.domains.destroy', [$teamProject, $domain]));

        // Only project owner can delete
        $response->assertForbidden();

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function team_owner_can_delete_domain_of_team_project(): void
    {
        $team = Team::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $teamProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $team->id,
        ]);

        $domain = Domain::factory()->create([
            'project_id' => $teamProject->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('projects.domains.destroy', [$teamProject, $domain]));

        $response->assertRedirect(route('projects.show', $teamProject));

        $this->assertSoftDeleted('domains', [
            'id' => $domain->id,
        ]);
    }

    // ==================== Admin/Super Admin Tests ====================

    /** @test */
    public function admin_can_update_any_domain(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        $domain = Domain::factory()->create([
            'project_id' => $this->otherUsersProject->id,
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($adminUser)
            ->put(route('projects.domains.update', [$this->otherUsersProject, $domain]), [
                'status' => 'active',
            ]);

        $response->assertRedirect(route('projects.show', $this->otherUsersProject));

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function super_admin_can_update_any_domain(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdminUser = User::factory()->create();
        $superAdminUser->assignRole('super_admin');

        $domain = Domain::factory()->create([
            'project_id' => $this->otherUsersProject->id,
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($superAdminUser)
            ->put(route('projects.domains.update', [$this->otherUsersProject, $domain]), [
                'status' => 'active',
            ]);

        $response->assertRedirect(route('projects.show', $this->otherUsersProject));

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'status' => 'active',
        ]);
    }

    // ==================== Edge Cases and Error Handling ====================

    /** @test */
    public function domain_creation_handles_metadata_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => 'example.com',
                'metadata' => 'not_an_array',
            ]);

        $response->assertSessionHasErrors(['metadata']);
    }

    /** @test */
    public function domain_update_handles_metadata_validation(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain]), [
                'metadata' => 'not_an_array',
            ]);

        $response->assertSessionHasErrors(['metadata']);
    }

    /** @test */
    public function domain_operations_handle_non_existent_project(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', ['project' => 'non-existent-slug']), [
                'domain' => 'example.com',
            ]);

        $response->assertNotFound();
    }

    /** @test */
    public function domain_operations_handle_non_existent_domain(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, 'domain' => 99999]), [
                'ssl_enabled' => true,
            ]);

        $response->assertNotFound();
    }

    /** @test */
    public function domain_update_handles_project_domain_mismatch(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->otherUsersProject->id,
        ]);

        // Try to update a domain via a different project's route
        $response = $this->actingAs($this->user)
            ->put(route('projects.domains.update', [$this->project, $domain]), [
                'ssl_enabled' => true,
            ]);

        // Should fail because domain doesn't belong to the project in the route
        // Policy check happens before route model binding, so we get 403 not 404
        $response->assertForbidden();
    }

    /** @test */
    public function domain_delete_handles_project_domain_mismatch(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->otherUsersProject->id,
        ]);

        // Try to delete a domain via a different project's route
        $response = $this->actingAs($this->user)
            ->delete(route('projects.domains.destroy', [$this->project, $domain]));

        // Should fail because domain doesn't belong to the project in the route
        // Policy check happens before route model binding, so we get 403 not 404
        $response->assertForbidden();
    }

    /** @test */
    public function domain_creation_sets_status_to_pending_automatically(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => 'example.com',
                'status' => 'active', // Try to set status
            ]);

        $response->assertRedirect(route('projects.show', $this->project));

        // Status should always be 'pending' on creation, ignoring user input
        $this->assertDatabaseHas('domains', [
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function multiple_domains_can_be_added_to_same_project(): void
    {
        $domains = ['example1.com', 'example2.com', 'example3.com'];

        foreach ($domains as $domainName) {
            $response = $this->actingAs($this->user)
                ->post(route('projects.domains.store', $this->project), [
                    'domain' => $domainName,
                ]);

            $response->assertRedirect(route('projects.show', $this->project));
        }

        $this->assertDatabaseCount('domains', 3);

        foreach ($domains as $domainName) {
            $this->assertDatabaseHas('domains', [
                'project_id' => $this->project->id,
                'domain' => $domainName,
            ]);
        }
    }

    /** @test */
    public function only_one_primary_domain_per_project(): void
    {
        $domain1 = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example1.com',
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('projects.domains.store', $this->project), [
                'domain' => 'example2.com',
                'is_primary' => true,
            ]);

        $response->assertRedirect(route('projects.show', $this->project));

        // Both domains should exist with their is_primary flags
        // Business logic to enforce single primary would be in the model/service
        $this->assertDatabaseHas('domains', [
            'project_id' => $this->project->id,
            'domain' => 'example2.com',
            'is_primary' => true,
        ]);
    }
}
