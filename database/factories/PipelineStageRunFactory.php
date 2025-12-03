<?php

namespace Database\Factories;

use App\Models\{PipelineStageRun, PipelineRun, PipelineStage};
use Illuminate\Database\Eloquent\Factories\Factory;

class PipelineStageRunFactory extends Factory
{
    protected $model = PipelineStageRun::class;

    public function definition(): array
    {
        return [
            'pipeline_run_id' => PipelineRun::factory(),
            'pipeline_stage_id' => PipelineStage::factory(),
            'status' => fake()->randomElement(['pending', 'running', 'success', 'failed', 'skipped', 'cancelled']),
            'output' => fake()->optional()->paragraph(),
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'Command failed',
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
        ]);
    }
}
