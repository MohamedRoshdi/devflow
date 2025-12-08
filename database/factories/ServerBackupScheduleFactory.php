<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerBackupSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServerBackupSchedule>
 */
class ServerBackupScheduleFactory extends Factory
{
    protected $model = ServerBackupSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $frequency = fake()->randomElement(['daily', 'weekly', 'monthly']);

        return [
            'server_id' => Server::factory(),
            'type' => fake()->randomElement(['full', 'incremental', 'snapshot']),
            'frequency' => $frequency,
            'time' => fake()->time('H:i'),
            'day_of_week' => $frequency === 'weekly' ? fake()->numberBetween(0, 6) : null,
            'day_of_month' => $frequency === 'monthly' ? fake()->numberBetween(1, 28) : null,
            'retention_days' => fake()->randomElement([7, 14, 30, 60, 90]),
            'storage_driver' => fake()->randomElement(['local', 's3']),
            'is_active' => fake()->boolean(80),
            'last_run_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Indicate that the schedule is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the schedule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that this is a daily schedule.
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'daily',
            'day_of_week' => null,
            'day_of_month' => null,
        ]);
    }

    /**
     * Indicate that this is a weekly schedule.
     */
    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'weekly',
            'day_of_week' => fake()->numberBetween(0, 6),
            'day_of_month' => null,
        ]);
    }

    /**
     * Indicate that this is a monthly schedule.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
            'day_of_week' => null,
            'day_of_month' => fake()->numberBetween(1, 28),
        ]);
    }
}
