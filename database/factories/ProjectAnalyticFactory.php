<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectAnalytic>
 */
class ProjectAnalyticFactory extends Factory
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
            'metric_type' => fake()->randomElement(['deployments', 'uptime', 'response_time', 'cpu_usage', 'memory_usage']),
            'metric_value' => fake()->randomFloat(2, 0, 100),
            'metadata' => [],
            'recorded_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
