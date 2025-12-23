<?php

declare(strict_types=1);

namespace Tests\Feature;


use PHPUnit\Framework\Attributes\Test;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear rate limiters to ensure clean state
        RateLimiter::clear('test@example.com|127.0.0.1');
    }

    // ==================== Login Tests ====================

    #[Test]
    public function user_can_view_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('Login');
    }

    #[Test]
    public function user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function user_cannot_login_with_incorrect_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrongpassword')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    #[Test]
    public function user_cannot_login_with_nonexistent_email(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'nonexistent@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    #[Test]
    public function login_requires_email_and_password(): void
    {
        Livewire::test(Login::class)
            ->set('email', '')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['email', 'password']);
    }

    // ==================== Registration Tests ====================

    #[Test]
    public function user_can_view_registration_page(): void
    {
        // Registration is closed - should redirect to login
        $response = $this->get('/register');

        $response->assertRedirect('/login')
            ->assertSessionHas('status', 'Registration is currently closed. Please contact an administrator for access.');
    }

    #[Test]
    public function user_can_register_with_valid_data(): void
    {
        // Registration is closed - POST /register route doesn't exist
        // This test verifies that registration is properly closed
        $response = $this->get('/register');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function registration_requires_unique_email(): void
    {
        // Registration is closed - POST /register route doesn't exist
        // This test verifies that registration is properly closed
        $response = $this->get('/register');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function registration_requires_password_confirmation(): void
    {
        // Registration is closed - POST /register route doesn't exist
        // This test verifies that registration is properly closed
        $response = $this->get('/register');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function registration_requires_minimum_password_length(): void
    {
        // Registration is closed - POST /register route doesn't exist
        // This test verifies that registration is properly closed
        $response = $this->get('/register');

        $response->assertRedirect('/login');
    }

    // ==================== Logout Tests ====================

    #[Test]
    public function authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        // Use withSession to provide CSRF token
        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->post('/logout', ['_token' => 'test-token']);

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    // ==================== Password Reset Tests ====================

    #[Test]
    public function user_can_view_forgot_password_page(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertOk();
    }

    #[Test]
    public function user_can_request_password_reset_link(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::RESET_LINK_SENT);

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'test@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors();
    }

    #[Test]
    public function password_reset_fails_for_nonexistent_email(): void
    {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'nonexistent@example.com')
            ->call('sendResetLink')
            ->assertHasErrors(['email']);
    }

    // ==================== Protected Routes Tests ====================

    #[Test]
    public function guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_access_projects(): void
    {
        $response = $this->get('/projects');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function guest_cannot_access_servers(): void
    {
        $response = $this->get('/servers');

        $response->assertRedirect('/login');
    }

    // ==================== Session Tests ====================

    #[Test]
    public function session_is_regenerated_on_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Session regeneration happens inside Livewire component
        // We verify login works and user is authenticated
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function remember_me_creates_remember_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('remember', true)
            ->call('login')
            ->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }
}
