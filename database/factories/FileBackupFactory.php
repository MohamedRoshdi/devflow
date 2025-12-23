<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FileBackup;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FileBackup>
 */
class FileBackupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = FileBackup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'filename' => 'project_full_'.date('Y-m-d_His').'.tar.gz',
            'type' => 'full',
            'source_path' => '/var/www/project',
            'storage_disk' => 'local',
            'storage_path' => 'file-backups/'.date('Y/m/d').'/backup.tar.gz',
            'size_bytes' => $this->faker->numberBetween(1024 * 1024, 1024 * 1024 * 100), // 1MB to 100MB
            'files_count' => $this->faker->numberBetween(10, 1000),
            'checksum' => $this->faker->sha256(),
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
            'error_message' => null,
            'manifest' => [
                'file1.php',
                'file2.js',
                'file3.css',
            ],
            'exclude_patterns' => [
                'storage/logs/*',
                'node_modules/*',
                'vendor/*',
                '.git/*',
            ],
            'parent_backup_id' => null,
        ];
    }

    /**
     * Indicate that the backup is a full backup.
     */
    public function full(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'full',
            'parent_backup_id' => null,
        ]);
    }

    /**
     * Indicate that the backup is an incremental backup.
     */
    public function incremental(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'incremental',
            'parent_backup_id' => FileBackup::factory()->full(),
        ]);
    }

    /**
     * Indicate that the backup is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the backup is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the backup has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => 'Backup failed: '.$this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the backup is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Set a specific storage disk.
     */
    public function onDisk(string $disk): static
    {
        return $this->state(fn (array $attributes) => [
            'storage_disk' => $disk,
            'storage_path' => 'file-backups/'.date('Y/m/d').'/backup_'.$disk.'.tar.gz',
        ]);
    }
}
