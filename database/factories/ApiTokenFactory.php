<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiToken>
 */
class ApiTokenFactory extends Factory
{
    protected $model = ApiToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true).' Token',
            'token' => hash('sha256', Str::random(40)),
            'abilities' => ['projects:read'],
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }

    /**
     * Indicate that the token has specific abilities.
     */
    public function withAbilities(array $abilities): static
    {
        return $this->state(fn (array $attributes) => [
            'abilities' => $abilities,
        ]);
    }

    /**
     * Indicate that the token is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Indicate that the token expires in the future.
     */
    public function expiresIn(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays($days),
        ]);
    }

    /**
     * Indicate that the token was recently used.
     */
    public function recentlyUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => now()->subMinutes(rand(1, 60)),
        ]);
    }
}
