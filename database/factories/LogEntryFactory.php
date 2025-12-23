<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LogEntry>
 */
class LogEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => \App\Models\Server::factory(),
            'project_id' => fake()->boolean(70) ? \App\Models\Project::factory() : null,
            'source' => fake()->randomElement(['nginx', 'php', 'laravel', 'mysql', 'system', 'docker']),
            'level' => fake()->randomElement(['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency']),
            'message' => fake()->sentence(10),
            'context' => fake()->boolean(50) ? ['user_id' => fake()->numberBetween(1, 100), 'action' => fake()->word()] : null,
            'file_path' => fake()->boolean(60) ? '/var/www/html/'.fake()->filePath() : null,
            'line_number' => fake()->boolean(60) ? fake()->numberBetween(1, 1000) : null,
            'logged_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
