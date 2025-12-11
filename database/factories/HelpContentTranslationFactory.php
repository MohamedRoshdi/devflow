<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HelpContent;
use App\Models\HelpContentTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HelpContentTranslation>
 */
class HelpContentTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<HelpContentTranslation>
     */
    protected $model = HelpContentTranslation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'help_content_id' => HelpContent::factory(),
            'locale' => fake()->randomElement(['fr', 'es', 'de', 'ar']),
            'brief' => fake()->sentence(),
            'details' => [
                'step1' => fake()->sentence(),
                'step2' => fake()->sentence(),
                'step3' => fake()->sentence(),
            ],
        ];
    }
}
