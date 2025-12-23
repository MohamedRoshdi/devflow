<?php

namespace Database\Factories;

use App\Models\ServerTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServerTag>
 */
class ServerTagFactory extends Factory
{
    protected $model = ServerTag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->randomElement([
                'Production',
                'Staging',
                'Development',
                'Testing',
                'Database',
                'Web Server',
                'API Server',
                'Cache Server',
                'Load Balancer',
                'Backup Server',
            ]),
            'color' => fake()->hexColor(),
        ];
    }
}
