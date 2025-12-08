<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['sent', 'failed', 'pending']);

        return [
            'notification_channel_id' => NotificationChannel::factory(),
            'event_type' => fake()->randomElement([
                'deployment.started',
                'deployment.completed',
                'deployment.failed',
                'health_check.failed',
                'backup.completed',
            ]),
            'payload' => [
                'project_id' => fake()->numberBetween(1, 100),
                'message' => fake()->sentence(),
                'timestamp' => now()->toIso8601String(),
            ],
            'status' => $status,
            'error_message' => $status === 'failed' ? fake()->sentence() : null,
        ];
    }

    /**
     * Indicate that the notification was sent successfully.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the notification was sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the notification failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the notification is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'error_message' => null,
        ]);
    }
}
