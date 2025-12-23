<?php

namespace Database\Factories;

use App\Models\ResourceAlert;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResourceAlert>
 */
class ResourceAlertFactory extends Factory
{
    protected $model = ResourceAlert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $resourceTypes = ['cpu', 'memory', 'disk', 'load'];
        $resourceType = fake()->randomElement($resourceTypes);
        $thresholdType = fake()->randomElement(['above', 'below']);

        $thresholdValue = match ($resourceType) {
            'cpu', 'memory', 'disk' => fake()->randomFloat(2, 50, 95),
            'load' => fake()->randomFloat(2, 1, 10),
            default => fake()->randomFloat(2, 50, 90),
        };

        return [
            'server_id' => Server::factory(),
            'resource_type' => $resourceType,
            'threshold_type' => $thresholdType,
            'threshold_value' => $thresholdValue,
            'notification_channels' => fake()->randomElements(['mail', 'slack', 'database'], fake()->numberBetween(1, 3)),
            'is_active' => true,
            'cooldown_minutes' => fake()->randomElement([5, 10, 15, 30, 60]),
            'last_triggered_at' => null,
        ];
    }

    /**
     * Indicate the alert is for CPU usage.
     */
    public function cpu(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => fake()->randomFloat(2, 70, 90),
        ]);
    }

    /**
     * Indicate the alert is for memory usage.
     */
    public function memory(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => 'memory',
            'threshold_type' => 'above',
            'threshold_value' => fake()->randomFloat(2, 75, 95),
        ]);
    }

    /**
     * Indicate the alert is for disk usage.
     */
    public function disk(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => 'disk',
            'threshold_type' => 'above',
            'threshold_value' => fake()->randomFloat(2, 80, 95),
        ]);
    }

    /**
     * Indicate the alert is for load average.
     */
    public function load(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => 'load',
            'threshold_type' => 'above',
            'threshold_value' => fake()->randomFloat(2, 3, 8),
        ]);
    }

    /**
     * Indicate the alert is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate the alert is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate the alert was recently triggered.
     */
    public function recentlyTriggered(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_triggered_at' => now()->subMinutes(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate the alert is in cooldown.
     */
    public function inCooldown(): static
    {
        return $this->state(function (array $attributes) {
            $cooldown = $attributes['cooldown_minutes'] ?? 10;
            return [
                'cooldown_minutes' => $cooldown,
                'last_triggered_at' => now()->subMinutes(fake()->numberBetween(1, $cooldown - 1)),
            ];
        });
    }

    /**
     * Indicate the alert cooldown has expired.
     */
    public function cooldownExpired(): static
    {
        return $this->state(function (array $attributes) {
            $cooldown = $attributes['cooldown_minutes'] ?? 10;
            return [
                'cooldown_minutes' => $cooldown,
                'last_triggered_at' => now()->subMinutes($cooldown + fake()->numberBetween(5, 30)),
            ];
        });
    }

    /**
     * Indicate the alert uses "above" threshold type.
     */
    public function above(): static
    {
        return $this->state(fn (array $attributes) => [
            'threshold_type' => 'above',
        ]);
    }

    /**
     * Indicate the alert uses "below" threshold type.
     */
    public function below(): static
    {
        return $this->state(fn (array $attributes) => [
            'threshold_type' => 'below',
        ]);
    }

    /**
     * Indicate the alert has specific notification channels.
     */
    public function withChannels(array $channels): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_channels' => $channels,
        ]);
    }

    /**
     * Indicate the alert has email notifications only.
     */
    public function emailOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_channels' => ['mail'],
        ]);
    }

    /**
     * Indicate the alert has Slack notifications only.
     */
    public function slackOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_channels' => ['slack'],
        ]);
    }

    /**
     * Indicate critical threshold values.
     */
    public function critical(): static
    {
        return $this->state(function (array $attributes) {
            $resourceType = $attributes['resource_type'] ?? 'cpu';

            $thresholdValue = match ($resourceType) {
                'cpu', 'memory', 'disk' => fake()->randomFloat(2, 90, 98),
                'load' => fake()->randomFloat(2, 8, 15),
                default => fake()->randomFloat(2, 90, 95),
            };

            return [
                'threshold_type' => 'above',
                'threshold_value' => $thresholdValue,
            ];
        });
    }

    /**
     * Indicate warning threshold values.
     */
    public function warning(): static
    {
        return $this->state(function (array $attributes) {
            $resourceType = $attributes['resource_type'] ?? 'cpu';

            $thresholdValue = match ($resourceType) {
                'cpu', 'memory', 'disk' => fake()->randomFloat(2, 70, 85),
                'load' => fake()->randomFloat(2, 3, 6),
                default => fake()->randomFloat(2, 70, 80),
            };

            return [
                'threshold_type' => 'above',
                'threshold_value' => $thresholdValue,
            ];
        });
    }
}
