<?php

namespace Database\Factories;

use App\Models\HelpContent;
use App\Models\HelpContentRelated;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HelpContentRelated>
 */
class HelpContentRelatedFactory extends Factory
{
    protected $model = HelpContentRelated::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'help_content_id' => HelpContent::factory(),
            'related_help_content_id' => HelpContent::factory(),
            'relevance_score' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * Set high relevance score.
     */
    public function highRelevance(): static
    {
        return $this->state(fn (array $attributes) => [
            'relevance_score' => fake()->numberBetween(70, 100),
        ]);
    }

    /**
     * Set low relevance score.
     */
    public function lowRelevance(): static
    {
        return $this->state(fn (array $attributes) => [
            'relevance_score' => fake()->numberBetween(10, 40),
        ]);
    }
}
