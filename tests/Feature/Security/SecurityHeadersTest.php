<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for SecurityHeaders middleware
 *
 * Verifies that proper security headers are applied to responses
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $this->user->id]);
        $this->user->teams()->attach($team->id, ['role' => 'owner']);
        $this->user->update(['current_team_id' => $team->id]);
    }

    public function test_x_frame_options_header_is_set(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_x_content_type_options_header_is_set(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_x_xss_protection_header_is_set(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_referrer_policy_header_is_set(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_permissions_policy_header_is_set(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertHeader('Permissions-Policy');
        $this->assertStringContainsString(
            'camera=()',
            $response->headers->get('Permissions-Policy')
        );
    }

    public function test_headers_are_applied_to_authenticated_routes(): void
    {
        $response = $this->actingAs($this->user)->get('/projects');

        $response->assertHeader('X-Frame-Options');
        $response->assertHeader('X-Content-Type-Options');
    }

    public function test_headers_are_applied_to_public_routes(): void
    {
        $response = $this->get('/');

        // Security headers should be on all web routes
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_hsts_not_set_in_non_production(): void
    {
        // In test environment, HSTS should not be set
        $response = $this->actingAs($this->user)->get('/dashboard');

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_csp_not_set_in_non_production(): void
    {
        // In test environment, CSP should not be set (development mode)
        $response = $this->actingAs($this->user)->get('/dashboard');

        // CSP is only set in production
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
    }
}
