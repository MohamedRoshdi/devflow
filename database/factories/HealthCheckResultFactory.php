<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HealthCheck;
use App\Models\HealthCheckResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthCheckResult>
 */
class HealthCheckResultFactory extends Factory
{
    protected $model = HealthCheckResult::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['success', 'failure', 'timeout']);
        $responseTime = $status === 'success'
            ? $this->faker->numberBetween(50, 500)
            : $this->faker->numberBetween(500, 5000);

        $statusCode = match ($status) {
            'success' => 200,
            'failure' => $this->faker->randomElement([500, 503, 404]),
            'timeout' => null,
        };

        return [
            'health_check_id' => HealthCheck::factory(),
            'status' => $status,
            'response_time_ms' => $responseTime,
            'status_code' => $statusCode,
            'error_message' => $status !== 'success' ? $this->faker->sentence() : null,
            'checked_at' => now(),
        ];
    }

    /**
     * Indicate that the health check was successful.
     *
     * @return $this
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'response_time_ms' => $this->faker->numberBetween(50, 500),
            'status_code' => 200,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the health check failed.
     *
     * @return $this
     */
    public function failure(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failure',
            'response_time_ms' => $this->faker->numberBetween(500, 5000),
            'status_code' => $this->faker->randomElement([500, 503, 404]),
            'error_message' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the health check timed out.
     *
     * @return $this
     */
    public function timeout(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'timeout',
            'response_time_ms' => 10000,
            'status_code' => null,
            'error_message' => 'Request timed out',
        ]);
    }
}
