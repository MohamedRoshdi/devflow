<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pipeline>
 */
class PipelineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(3, true) . ' Pipeline',
            'provider' => fake()->randomElement(['github', 'gitlab', 'bitbucket', 'jenkins', 'custom']),
            'configuration' => [
                'stages' => [],
            ],
            'triggers' => [
                'push' => true,
                'pull_request' => false,
            ],
            'is_active' => true,
        ];
    }
}
