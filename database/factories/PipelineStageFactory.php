<?php

namespace Database\Factories;

use App\Models\PipelineStage;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class PipelineStageFactory extends Factory
{
    protected $model = PipelineStage::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(['pre_deploy', 'deploy', 'post_deploy']),
            'order' => fake()->numberBetween(1, 10),
            'commands' => ['echo "test"', 'ls -la'],
            'timeout_seconds' => 600,
            'continue_on_failure' => false,
            'is_enabled' => true,
        ];
    }

    public function preDeploy(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'pre_deploy',
        ]);
    }

    public function deploy(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deploy',
        ]);
    }

    public function postDeploy(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'post_deploy',
        ]);
    }
}
