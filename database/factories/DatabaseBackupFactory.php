<?php

namespace Database\Factories;

use App\Models\DatabaseBackup;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DatabaseBackup>
 */
class DatabaseBackupFactory extends Factory
{
    protected $model = DatabaseBackup::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'running', 'completed', 'failed']);
        $databaseName = fake()->word().'_db';
        $timestamp = now()->format('Y-m-d_H-i-s');

        return [
            'project_id' => Project::factory(),
            'server_id' => Server::factory(),
            'database_type' => fake()->randomElement(['mysql', 'postgresql', 'sqlite']),
            'database_name' => $databaseName,
            'type' => fake()->randomElement(['manual', 'scheduled', 'pre_deploy']),
            'file_name' => "{$databaseName}_{$timestamp}.sql.gz",
            'file_path' => "/backups/{$databaseName}_{$timestamp}.sql.gz",
            'file_size' => $status === 'completed' ? fake()->numberBetween(1024, 1048576) : null,
            'checksum' => $status === 'completed' ? hash('sha256', fake()->uuid()) : null,
            'storage_disk' => fake()->randomElement(['local', 's3']),
            'status' => $status,
            'started_at' => $status !== 'pending' ? fake()->dateTimeBetween('-1 hour', 'now') : null,
            'completed_at' => $status === 'completed' ? fake()->dateTimeBetween('-30 minutes', 'now') : null,
            'verified_at' => $status === 'completed' && fake()->boolean(70) ? fake()->dateTimeBetween('-20 minutes', 'now') : null,
            'error_message' => $status === 'failed' ? fake()->sentence() : null,
            'metadata' => $status === 'completed' ? [
                'tables_count' => fake()->numberBetween(5, 50),
                'total_rows' => fake()->numberBetween(100, 100000),
                'compression_ratio' => fake()->randomFloat(2, 0.3, 0.8),
            ] : null,
            'created_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(30),
            'verified_at' => now()->subMinutes(15),
            'file_size' => fake()->numberBetween(10240, 1048576),
            'checksum' => hash('sha256', fake()->uuid()),
            'metadata' => [
                'tables_count' => fake()->numberBetween(5, 50),
                'total_rows' => fake()->numberBetween(100, 100000),
                'compression_ratio' => fake()->randomFloat(2, 0.3, 0.8),
            ],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => now()->subHour(),
            'error_message' => 'Backup process failed: '.fake()->sentence(),
        ]);
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
