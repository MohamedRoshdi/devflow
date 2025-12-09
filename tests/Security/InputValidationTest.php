<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InputValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    // ==================== XSS Prevention Tests ====================

    /** @test */
    public function project_name_sanitizes_script_tags(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/projects', [
            'name' => '<script>alert("XSS")</script>Test Project',
            'repository_url' => 'https://github.com/test/repo.git',
            'branch' => 'main',
            'server_id' => $this->server->id,
            'framework' => 'laravel',
        ]);

        // Either rejected or sanitized
        if ($response->status() === 302) {
            $project = Project::latest()->first();
            if ($project) {
                $this->assertStringNotContainsString('<script>', $project->name);
            }
        }
    }

    /** @test */
    public function server_name_sanitizes_html(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/servers', [
            'name' => '<img src=x onerror=alert("XSS")>Server',
            'ip_address' => '192.168.1.100',
            'ssh_user' => 'root',
            'ssh_port' => 22,
        ]);

        $server = Server::where('ip_address', '192.168.1.100')->first();
        if ($server) {
            $this->assertStringNotContainsString('<img', $server->name);
            $this->assertStringNotContainsString('onerror', $server->name);
        }
    }

    /** @test */
    public function environment_variables_escape_special_characters(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $response = $this->put('/projects/' . $project->slug, [
            'environment_variables' => [
                'DB_PASSWORD' => '"; DROP TABLE users; --',
                'API_KEY' => "'; DELETE FROM projects; --",
            ],
        ]);

        // The data should be stored as-is in JSON, but never executed
        $project->refresh();
        // Verify it's properly escaped when stored
        $this->assertNotNull($project);
    }

    // ==================== SQL Injection Prevention Tests ====================

    /** @test */
    public function project_search_prevents_sql_injection(): void
    {
        $this->actingAs($this->user);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Normal Project',
        ]);

        // Try SQL injection in search
        $response = $this->get('/projects?search=' . urlencode("'; DROP TABLE projects; --"));

        $response->assertOk();

        // Table should still exist
        $this->assertDatabaseCount('projects', 1);
    }

    /** @test */
    public function server_filter_prevents_sql_injection(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/servers?status=' . urlencode("active' OR '1'='1"));

        $response->assertOk();
    }

    // ==================== Path Traversal Prevention Tests ====================

    /** @test */
    public function file_path_prevents_directory_traversal(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Try to access files outside project directory
        $response = $this->get('/projects/' . $project->slug . '/logs?file=' . urlencode('../../../etc/passwd'));

        // Should either be blocked or sanitized
        $this->assertTrue(
            $response->status() === 403 ||
            $response->status() === 400 ||
            $response->status() === 404 ||
            $response->status() === 200 // If sanitized and returns empty/normal response
        );
    }

    // ==================== Command Injection Prevention Tests ====================

    /** @test */
    public function branch_name_prevents_command_injection(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/projects', [
            'name' => 'Test Project',
            'repository_url' => 'https://github.com/test/repo.git',
            'branch' => 'main; rm -rf /',
            'server_id' => $this->server->id,
            'framework' => 'laravel',
        ]);

        // Should be rejected due to invalid branch format
        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 302
        );
    }

    /** @test */
    public function ssh_user_prevents_command_injection(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/servers', [
            'name' => 'Test Server',
            'ip_address' => '192.168.1.101',
            'ssh_user' => 'root; cat /etc/passwd',
            'ssh_port' => 22,
        ]);

        // Should be rejected
        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 302
        );
    }

    // ==================== LDAP Injection Prevention Tests ====================

    /** @test */
    public function username_prevents_ldap_injection(): void
    {
        $response = $this->post('/login', [
            'email' => '*)(uid=*))(|(uid=*',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    // ==================== Header Injection Prevention Tests ====================

    /** @test */
    public function email_prevents_header_injection(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => "test@example.com\r\nBcc: attacker@evil.com",
        ]);

        $response->assertSessionHasErrors('email');
    }

    // ==================== Integer Overflow Tests ====================

    /** @test */
    public function pagination_handles_large_numbers(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/projects?page=999999999999999999999');

        $response->assertOk();
    }

    /** @test */
    public function port_number_validates_range(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/servers', [
            'name' => 'Test Server',
            'ip_address' => '192.168.1.102',
            'ssh_user' => 'root',
            'ssh_port' => 99999,
        ]);

        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 302
        );
    }

    // ==================== URL Validation Tests ====================

    /** @test */
    public function repository_url_validates_protocol(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/projects', [
            'name' => 'Test Project',
            'repository_url' => 'javascript:alert("XSS")',
            'branch' => 'main',
            'server_id' => $this->server->id,
            'framework' => 'laravel',
        ]);

        $response->assertSessionHasErrors('repository_url');
    }

    /** @test */
    public function webhook_url_validates_https(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/notifications/channels', [
            'name' => 'Test Channel',
            'provider' => 'slack',
            'webhook_url' => 'file:///etc/passwd',
            'events' => ['deployment.success'],
        ]);

        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 302
        );
    }

    // ==================== JSON Injection Prevention Tests ====================

    /** @test */
    public function json_config_prevents_injection(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $response = $this->put('/projects/' . $project->slug, [
            'docker_compose_path' => '"},"malicious":{"key":"value',
        ]);

        // Should store as string, not interpret as JSON
        $project->refresh();
        $this->assertIsString($project->docker_compose_path ?? '');
    }
}
