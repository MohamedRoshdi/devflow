<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsureTeamAccess;
use App\Models\ApiToken;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->team = Team::factory()->create([
            'name' => 'Test Team',
            'owner_id' => $this->user->id,
        ]);
    }

    // ==================== AuthenticateApiToken Middleware Tests ====================

    /** @test */
    public function it_allows_request_with_valid_api_token(): void
    {
        $token = ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', 'valid-token'),
            'expires_at' => now()->addDays(30),
        ]);

        $request = Request::create('/api/v1/projects', 'GET');
        $request->headers->set('Authorization', 'Bearer valid-token');

        $middleware = new AuthenticateApiToken();
        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_rejects_request_without_api_token(): void
    {
        $request = Request::create('/api/v1/projects', 'GET');

        $middleware = new AuthenticateApiToken();
        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function it_rejects_request_with_invalid_api_token(): void
    {
        $request = Request::create('/api/v1/projects', 'GET');
        $request->headers->set('Authorization', 'Bearer invalid-token');

        $middleware = new AuthenticateApiToken();
        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function it_rejects_request_with_expired_api_token(): void
    {
        $token = ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', 'expired-token'),
            'expires_at' => now()->subDays(1),
        ]);

        $request = Request::create('/api/v1/projects', 'GET');
        $request->headers->set('Authorization', 'Bearer expired-token');

        $middleware = new AuthenticateApiToken();
        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    // ==================== EnsureTeamAccess Middleware Tests ====================

    /** @test */
    public function it_allows_team_owner_access(): void
    {
        $this->actingAs($this->user);

        $request = Request::create('/teams/' . $this->team->id, 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/teams/{team}', []);
            $route->bind(request());
            $route->setParameter('team', $this->team);
            return $route;
        });

        $middleware = new EnsureTeamAccess();
        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_allows_team_member_access(): void
    {
        $member = User::factory()->create();
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($member);

        $request = Request::create('/teams/' . $this->team->id, 'GET');
        $request->setUserResolver(function () use ($member) {
            return $member;
        });
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/teams/{team}', []);
            $route->bind(request());
            $route->setParameter('team', $this->team);
            return $route;
        });

        $middleware = new EnsureTeamAccess();
        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_denies_non_team_member_access(): void
    {
        $nonMember = User::factory()->create();
        $this->actingAs($nonMember);

        $request = Request::create('/teams/' . $this->team->id, 'GET');
        $request->setUserResolver(fn () => $nonMember);
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/teams/{team}', []);
            $route->bind(request());
            $route->setParameter('team', $this->team);
            return $route;
        });

        $middleware = new EnsureTeamAccess();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('You do not have access to this team.');

        $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });
    }
}
