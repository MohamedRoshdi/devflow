<?php

namespace Database\Factories;

use App\Models\BackupSchedule;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupScheduleFactory extends Factory
{
    protected $model = BackupSchedule::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'server_id' => Server::factory(),
            'database_type' => fake()->randomElement(['mysql', 'postgresql', 'sqlite']),
            'database_name' => fake()->word().'_db',
            'frequency' => 'daily',
            'time' => '02:00:00',
            'day_of_week' => null,
            'day_of_month' => null,
            'retention_days' => 30,
            'retention_daily' => 7,
            'retention_weekly' => 4,
            'retention_monthly' => 3,
            'storage_disk' => 'local',
            'encrypt' => false,
            'notify_on_failure' => true,
            'is_active' => true,
            'last_run_at' => null,
            'next_run_at' => now()->addDay(),
        ];
    }

    public function mysql(): static
    {
        return $this->state(fn (array $attributes) => [
            'database_type' => 'mysql',
        ]);
    }

    public function postgresql(): static
    {
        return $this->state(fn (array $attributes) => [
            'database_type' => 'postgresql',
        ]);
    }

    public function s3(): static
    {
        return $this->state(fn (array $attributes) => [
            'storage_disk' => 's3',
        ]);
    }
}
