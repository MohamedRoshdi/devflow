<?php

namespace Database\Factories;

use App\Models\AlertHistory;
use App\Models\ResourceAlert;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlertHistory>
 */
class AlertHistoryFactory extends Factory
{
    protected $model = AlertHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $resourceType = fake()->randomElement(['cpu', 'memory', 'disk', 'load']);
        $status = fake()->randomElement(['triggered', 'resolved']);

        $currentValue = match ($resourceType) {
            'cpu', 'memory', 'disk' => fake()->randomFloat(2, 0, 100),
            'load' => fake()->randomFloat(2, 0, 15),
            default => fake()->randomFloat(2, 0, 100),
        };

        $thresholdValue = match ($resourceType) {
            'cpu', 'memory', 'disk' => fake()->randomFloat(2, 50, 95),
            'load' => fake()->randomFloat(2, 1, 10),
            default => fake()->randomFloat(2, 50, 90),
        };

        return [
            'resource_alert_id' => ResourceAlert::factory(),
            'server_id' => Server::factory(),
            'resource_type' => $resourceType,
            'current_value' => $currentValue,
            'threshold_value' => $thresholdValue,
            'status' => $status,
            'message' => $this->generateMessage($resourceType, $currentValue, $thresholdValue, $status),
            'notified_at' => now(),
        ];
    }

    /**
     * Generate a realistic alert message.
     */
    protected function generateMessage(string $resourceType, float $currentValue, float $thresholdValue, string $status): string
    {
        $resourceLabel = match ($resourceType) {
            'cpu' => 'CPU Usage',
            'memory' => 'Memory Usage',
            'disk' => 'Disk Usage',
            'load' => 'Load Average',
            default => $resourceType,
        };

        $unit = in_array($resourceType, ['cpu', 'memory', 'disk']) ? '%' : '';
        $serverName = 'Server '.fake()->numberBetween(1, 100);

        if ($status === 'triggered') {
            return sprintf(
                '%s on %s is above %.2f%s (threshold: > %.2f%s)',
                $resourceLabel,
                $serverName,
                $currentValue,
                $unit,
                $thresholdValue,
                $unit
            );
        }

        return sprintf(
            '%s on %s has returned to normal: %.2f%s (threshold: > %.2f%s)',
            $resourceLabel,
            $serverName,
            $currentValue,
            $unit,
            $thresholdValue,
            $unit
        );
    }

    /**
     * Indicate the alert was triggered.
     */
    public function triggered(): static
    {
        return $this->state(function (array $attributes) {
            $resourceType = $attributes['resource_type'] ?? 'cpu';
            $thresholdValue = $attributes['threshold_value'] ?? 80.0;

            // Current value is above threshold for triggered alerts
            $currentValue = match ($resourceType) {
                'cpu', 'memory', 'disk' => fake()->randomFloat(2, $thresholdValue + 5, 100),
                'load' => fake()->randomFloat(2, $thresholdValue + 1, 15),
                default => fake()->randomFloat(2, $thresholdValue + 5, 100),
            };

            return [
                'status' => 'triggered',
                'current_value' => $currentValue,
                'message' => $this->generateMessage($resourceType, $currentValue, $thresholdValue, 'triggered'),
            ];
        });
    }

    /**
     * Indicate the alert was resolved.
     */
    public function resolved(): static
    {
        return $this->state(function (array $attributes) {
            $resourceType = $attributes['resource_type'] ?? 'cpu';
            $thresholdValue = $attributes['threshold_value'] ?? 80.0;

            // Current value is below threshold for resolved alerts
            $currentValue = match ($resourceType) {
                'cpu', 'memory', 'disk' => fake()->randomFloat(2, 0, $thresholdValue - 5),
                'load' => fake()->randomFloat(2, 0, max(0, $thresholdValue - 1)),
                default => fake()->randomFloat(2, 0, $thresholdValue - 5),
            };

            return [
                'status' => 'resolved',
                'current_value' => $currentValue,
                'message' => $this->generateMessage($resourceType, $currentValue, $thresholdValue, 'resolved'),
            ];
        });
    }

    /**
     * Indicate the history is for CPU alert.
     */
    public function cpu(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => 'cpu',
        ]);
    }

    /**
     * Indicate the history is for memory alert.
     */
    public function memory(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => 'memory',
        ]);
    }

    /**
     * Indicate the history is for disk alert.
     */
    public function disk(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => 'disk',
        ]);
    }

    /**
     * Indicate the history is for load alert.
     */
    public function load(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => 'load',
        ]);
    }

    /**
     * Indicate the history is recent (within the last hour).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'notified_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
            'created_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
        ]);
    }

    /**
     * Indicate the history is old (more than 24 hours ago).
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'notified_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'created_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate critical values.
     */
    public function critical(): static
    {
        return $this->state(function (array $attributes) {
            $resourceType = $attributes['resource_type'] ?? 'cpu';

            $currentValue = match ($resourceType) {
                'cpu', 'memory', 'disk' => fake()->randomFloat(2, 90, 99),
                'load' => fake()->randomFloat(2, 10, 20),
                default => fake()->randomFloat(2, 90, 99),
            };

            return [
                'status' => 'triggered',
                'current_value' => $currentValue,
            ];
        });
    }

    /**
     * Indicate warning values.
     */
    public function warning(): static
    {
        return $this->state(function (array $attributes) {
            $resourceType = $attributes['resource_type'] ?? 'cpu';

            $currentValue = match ($resourceType) {
                'cpu', 'memory', 'disk' => fake()->randomFloat(2, 70, 85),
                'load' => fake()->randomFloat(2, 5, 8),
                default => fake()->randomFloat(2, 70, 85),
            };

            return [
                'status' => 'triggered',
                'current_value' => $currentValue,
            ];
        });
    }

    /**
     * Indicate normal values.
     */
    public function normal(): static
    {
        return $this->state(function (array $attributes) {
            $resourceType = $attributes['resource_type'] ?? 'cpu';

            $currentValue = match ($resourceType) {
                'cpu', 'memory', 'disk' => fake()->randomFloat(2, 20, 60),
                'load' => fake()->randomFloat(2, 0.5, 3),
                default => fake()->randomFloat(2, 20, 60),
            };

            return [
                'status' => 'resolved',
                'current_value' => $currentValue,
            ];
        });
    }

    /**
     * Indicate the notification was not sent yet.
     */
    public function notNotified(): static
    {
        return $this->state(fn (array $attributes) => [
            'notified_at' => null,
        ]);
    }

    /**
     * Set a specific server.
     */
    public function forServer(Server $server): static
    {
        return $this->state(fn (array $attributes) => [
            'server_id' => $server->id,
        ]);
    }

    /**
     * Set a specific alert.
     */
    public function forAlert(ResourceAlert $alert): static
    {
        return $this->state(fn (array $attributes) => [
            'resource_alert_id' => $alert->id,
            'server_id' => $alert->server_id,
            'resource_type' => $alert->resource_type,
        ]);
    }
}
