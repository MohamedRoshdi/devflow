<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GitHubConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GitHubConnection>
 */
class GitHubConnectionFactory extends Factory
{
    protected $model = GitHubConnection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'access_token' => 'gho_'.fake()->uuid(),
            'refresh_token' => 'ghr_'.fake()->uuid(),
            'token_expires_at' => now()->addDays(30),
            'github_user_id' => (string) fake()->randomNumber(6),
            'github_username' => fake()->userName(),
            'github_avatar' => 'https://avatars.githubusercontent.com/u/'.fake()->randomNumber(6),
            'scopes' => ['repo', 'user', 'admin:repo_hook'],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the connection is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the token is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'token_expires_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that there is no refresh token.
     */
    public function withoutRefreshToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'refresh_token' => null,
        ]);
    }
}
