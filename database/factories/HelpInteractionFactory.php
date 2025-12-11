<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HelpContent;
use App\Models\HelpInteraction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HelpInteraction>
 */
class HelpInteractionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<HelpInteraction>
     */
    protected $model = HelpInteraction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'help_content_id' => HelpContent::factory(),
            'interaction_type' => fake()->randomElement(['viewed', 'helpful', 'not_helpful']),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
