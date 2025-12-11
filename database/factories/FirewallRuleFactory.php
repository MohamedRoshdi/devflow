<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FirewallRule;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FirewallRule>
 */
class FirewallRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<FirewallRule>
     */
    protected $model = FirewallRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'action' => fake()->randomElement(['allow', 'deny', 'reject']),
            'direction' => fake()->randomElement(['in', 'out']),
            'protocol' => fake()->randomElement(['tcp', 'udp', 'any']),
            'port' => fake()->randomElement(['22', '80', '443', '3306', '5432', null]),
            'from_ip' => fake()->optional()->ipv4(),
            'to_ip' => fake()->optional()->ipv4(),
            'description' => fake()->sentence(),
            'is_active' => fake()->boolean(80),
            'priority' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the firewall rule is active.
     *
     * @return static
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the firewall rule is inactive.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
