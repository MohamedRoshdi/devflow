<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScheduledDeployment>
 */
class ScheduledDeploymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => \App\Models\Project::factory(),
            'user_id' => \App\Models\User::factory(),
            'branch' => 'main',
            'scheduled_at' => fake()->dateTimeBetween('now', '+7 days'),
            'timezone' => 'UTC',
            'status' => 'pending',
            'notes' => fake()->optional()->sentence(),
            'notify_before' => fake()->boolean(),
            'notify_minutes' => fake()->randomElement([15, 30, 60, 120]),
            'notified' => false,
        ];
    }
}
