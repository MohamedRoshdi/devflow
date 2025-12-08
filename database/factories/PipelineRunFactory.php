<?php

namespace Database\Factories;

use App\Models\PipelineRun;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class PipelineRunFactory extends Factory
{
    protected $model = PipelineRun::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'deployment_id' => null,
            'run_number' => fake()->numberBetween(1, 100),
            'status' => fake()->randomElement(['pending', 'running', 'success', 'failed', 'cancelled']),
            'triggered_by' => fake()->randomElement(['manual', 'webhook', 'scheduled']),
            'trigger_data' => json_encode([]),
            'branch' => 'main',
            'commit_sha' => fake()->sha1(),
            'logs' => json_encode([]),
            'started_at' => null,
            'finished_at' => null,
        ];
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);
    }
}
