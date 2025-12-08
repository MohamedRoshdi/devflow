<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationChannel>
 */
class NotificationChannelFactory extends Factory
{
    protected $model = NotificationChannel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['slack', 'discord', 'email']);

        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(3, true).' Channel',
            'type' => $type,
            'webhook_url' => 'https://hooks.example.com/'.fake()->uuid(),
            'enabled' => true,
            'events' => ['deployment_success', 'deployment_failed'],
            'metadata' => null,
            'config' => json_encode($this->getConfigForType($type)),
        ];
    }

    /**
     * Get configuration based on channel type.
     *
     * @param  string  $type
     * @return array<string, mixed>
     */
    private function getConfigForType(string $type): array
    {
        return match ($type) {
            'email' => [
                'email' => fake()->safeEmail(),
            ],
            'slack' => [
                'webhook_url' => 'https://hooks.slack.com/services/'.fake()->uuid(),
            ],
            'discord' => [
                'webhook_url' => 'https://discord.com/api/webhooks/'.fake()->uuid(),
            ],
            default => [],
        };
    }

    /**
     * Indicate that the channel is for email notifications.
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'email',
            'config' => [
                'email' => fake()->safeEmail(),
            ],
        ]);
    }

    /**
     * Indicate that the channel is for Slack notifications.
     */
    public function slack(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'slack',
            'config' => [
                'webhook_url' => 'https://hooks.slack.com/services/'.fake()->uuid(),
            ],
        ]);
    }

    /**
     * Indicate that the channel is for Discord notifications.
     */
    public function discord(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'discord',
            'config' => [
                'webhook_url' => 'https://discord.com/api/webhooks/'.fake()->uuid(),
            ],
        ]);
    }

    /**
     * Indicate that the channel is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'enabled' => false,
        ]);
    }

    /**
     * Indicate that the channel is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'enabled' => true,
        ]);
    }

    /**
     * Indicate that the channel is project-specific.
     */
    public function forProject(?int $projectId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $projectId ?? Project::factory(),
        ]);
    }
}
