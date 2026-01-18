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
        $status = fake()->randomElement(['healthy', 'degraded', 'down', 'unknown']);
        $checkType = fake()->randomElement(['http', 'tcp', 'ping', 'ssl_expiry']);

        return [
            'project_id' => Project::factory(),
            'server_id' => null,
            'check_type' => $checkType,
            'target_url' => fake()->url(),
            'expected_status' => 200,
            'interval_minutes' => fake()->randomElement([1, 5, 10, 15, 30]),
            'timeout_seconds' => fake()->randomElement([5, 10, 30, 60]),
            'status' => $status,
            'is_active' => fake()->boolean(90),
            'last_check_at' => fake()->optional()->dateTimeBetween('-1 hour', 'now'),
            'last_success_at' => $status === 'healthy' ? now() : fake()->optional()->dateTimeBetween('-1 day', 'now'),
            'last_failure_at' => $status === 'down' ? now() : null,
            'consecutive_failures' => $status === 'down' ? fake()->numberBetween(1, 10) : 0,
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
            'consecutive_failures' => fake()->numberBetween(1, 4),
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
        ]);
    }

    /**
     * Indicate that the health check is for HTTP.
     */
    public function http(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_type' => 'http',
            'target_url' => fake()->url(),
            'expected_status' => 200,
        ]);
    }

    /**
     * Indicate that the health check is for TCP.
     */
    public function tcp(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_type' => 'tcp',
            'target_url' => fake()->domainName().':'.fake()->numberBetween(1, 65535),
            'expected_status' => 200,
        ]);
    }

    /**
     * Indicate that the health check is for ping.
     */
    public function ping(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_type' => 'ping',
            'target_url' => fake()->domainName(),
            'expected_status' => 200,
        ]);
    }

    /**
     * Indicate that the health check is for SSL expiry.
     */
    public function sslExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_type' => 'ssl_expiry',
            'target_url' => 'https://'.fake()->domainName(),
            'expected_status' => 200,
        ]);
    }
}
