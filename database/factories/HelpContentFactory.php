<?php

namespace Database\Factories;

use App\Models\HelpContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HelpContent>
 */
class HelpContentFactory extends Factory
{
    protected $model = HelpContent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['deployment', 'servers', 'docker', 'ssl', 'monitoring', 'backup', 'security'];
        $uiElementTypes = ['button', 'input', 'select', 'modal', 'card', 'menu', 'tooltip'];
        $icons = ['heroicon-o-rocket-launch', 'heroicon-o-server', 'heroicon-o-shield-check', 'heroicon-o-chart-bar'];

        return [
            'key' => fake()->unique()->slug(3),
            'category' => fake()->randomElement($categories),
            'ui_element_type' => fake()->randomElement($uiElementTypes),
            'icon' => fake()->randomElement($icons),
            'title' => fake()->sentence(4),
            'brief' => fake()->sentence(10),
            'details' => [
                'steps' => [
                    fake()->sentence(),
                    fake()->sentence(),
                    fake()->sentence(),
                ],
                'tips' => [
                    fake()->sentence(),
                ],
                'warnings' => [
                    fake()->sentence(),
                ],
            ],
            'docs_url' => fake()->boolean(30) ? fake()->url() : null,
            'video_url' => fake()->boolean(20) ? 'https://www.youtube.com/watch?v=' . fake()->regexify('[A-Za-z0-9]{11}') : null,
            'is_active' => true,
            'view_count' => fake()->numberBetween(0, 1000),
            'helpful_count' => fake()->numberBetween(0, 100),
            'not_helpful_count' => fake()->numberBetween(0, 50),
        ];
    }

    /**
     * Indicate that the help content is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the help content is popular.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'view_count' => fake()->numberBetween(1000, 10000),
            'helpful_count' => fake()->numberBetween(100, 500),
        ]);
    }

    /**
     * Set specific category.
     */
    public function category(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Set specific key.
     */
    public function withKey(string $key): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
        ]);
    }
}
