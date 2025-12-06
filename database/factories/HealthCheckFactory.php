<?php

namespace Database\Factories;

use App\Models\HealthCheck;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HealthCheck>
 */
class HealthCheckFactory extends Factory
{
    protected $model = HealthCheck::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['healthy', 'degraded', 'down']);

        return [
            'project_id' => Project::factory(),
            'server_id' => Server::factory(),
            'name' => fake()->words(3, true).' Health Check',
            'url' => fake()->url(),
            'method' => fake()->randomElement(['GET', 'POST', 'HEAD']),
            'expected_status_code' => 200,
            'timeout_seconds' => 30,
            'check_interval_seconds' => 300,
            'status' => $status,
            'is_active' => fake()->boolean(90),
            'last_check_at' => fake()->optional()->dateTimeBetween('-1 hour', 'now'),
            'last_success_at' => $status === 'healthy' ? now() : fake()->optional()->dateTimeBetween('-1 day', 'now'),
            'last_failure_at' => $status === 'down' ? now() : null,
            'consecutive_failures' => $status === 'down' ? fake()->numberBetween(1, 10) : 0,
            'response_time_ms' => fake()->numberBetween(50, 5000),
            'last_error_message' => $status === 'down' ? fake()->sentence() : null,
            'metadata' => json_encode([]),
        ];
    }

    /**
     * Indicate that the health check is healthy.
     */
    public function healthy(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'healthy',
            'is_active' => true,
            'last_check_at' => now(),
            'last_success_at' => now(),
            'last_failure_at' => null,
            'consecutive_failures' => 0,
            'response_time_ms' => fake()->numberBetween(50, 500),
            'last_error_message' => null,
        ]);
    }

    /**
     * Indicate that the health check is degraded.
     */
    public function degraded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'degraded',
            'is_active' => true,
            'last_check_at' => now(),
            'last_success_at' => now()->subMinutes(30),
            'response_time_ms' => fake()->numberBetween(2000, 5000),
            'consecutive_failures' => fake()->numberBetween(1, 3),
        ]);
    }

    /**
     * Indicate that the health check is down.
     */
    public function down(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'down',
            'is_active' => true,
            'last_check_at' => now(),
            'last_failure_at' => now(),
            'consecutive_failures' => fake()->numberBetween(5, 20),
            'last_error_message' => fake()->randomElement([
                'Connection timeout',
                'HTTP 500 Internal Server Error',
                'DNS resolution failed',
                'Connection refused',
            ]),
        ]);
    }
}
