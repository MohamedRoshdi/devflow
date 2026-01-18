<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerBackup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServerBackup>
 */
class ServerBackupFactory extends Factory
{
    protected $model = ServerBackup::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'running', 'completed', 'failed']);
        $type = fake()->randomElement(['full', 'incremental', 'snapshot']);
        $timestamp = now()->format('Y-m-d_H-i-s');

        return [
            'server_id' => Server::factory(),
            'type' => $type,
            'status' => $status,
            'size_bytes' => $status === 'completed' ? fake()->numberBetween(1073741824, 10737418240) : null, // 1GB to 10GB
            'storage_path' => $status === 'completed' ? "backups/servers/server_1_{$type}_{$timestamp}.tar.gz" : null,
            'storage_driver' => fake()->randomElement(['local', 's3']),
            'started_at' => $status !== 'pending' ? fake()->dateTimeBetween('-2 hours', 'now') : null,
            'completed_at' => $status === 'completed' ? fake()->dateTimeBetween('-1 hour', 'now') : null,
            'error_message' => $status === 'failed' ? 'Backup failed: '.fake()->sentence() : null,
            'metadata' => $status === 'completed' ? [
                'directories' => ['/etc', '/var/www', '/opt'],
                'method' => $type === 'snapshot' ? 'lvm' : ($type === 'incremental' ? 'rsync' : 'tar'),
            ] : null,
        ];
    }

    public function completed(): static
    {
        $timestamp = now()->format('Y-m-d_H-i-s');

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(30),
            'size_bytes' => fake()->numberBetween(1073741824, 10737418240),
            'storage_path' => "backups/servers/server_{$attributes['server_id']}_full_{$timestamp}.tar.gz",
            'metadata' => [
                'directories' => ['/etc', '/var/www', '/opt', '/home'],
                'method' => 'tar',
            ],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(30),
            'error_message' => 'Backup process failed: '.fake()->sentence(),
        ]);
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now()->subMinutes(15),
            'completed_at' => null,
        ]);
    }

    public function full(): static
    {
        $timestamp = now()->format('Y-m-d_H-i-s');

        return $this->state(fn (array $attributes) => [
            'type' => 'full',
            'storage_path' => "backups/servers/server_{$attributes['server_id']}_full_{$timestamp}.tar.gz",
            'metadata' => [
                'directories' => ['/etc', '/var/www', '/opt', '/home'],
                'method' => 'tar',
            ],
        ]);
    }

    public function incremental(): static
    {
        $timestamp = now()->format('Y-m-d_H-i-s');

        return $this->state(fn (array $attributes) => [
            'type' => 'incremental',
            'storage_path' => "backups/servers/incremental/{$attributes['server_id']}/{$timestamp}",
            'metadata' => [
                'method' => 'rsync',
                'incremental' => true,
            ],
        ]);
    }

    public function snapshot(): static
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $snapshotName = "backup_snapshot_{$timestamp}";

        return $this->state(fn (array $attributes) => [
            'type' => 'snapshot',
            'storage_path' => "lvm://{$snapshotName}",
            'size_bytes' => 10737418240, // 10GB
            'metadata' => [
                'method' => 'lvm',
                'snapshot_name' => $snapshotName,
            ],
        ]);
    }

    public function s3(): static
    {
        return $this->state(fn (array $attributes) => [
            'storage_driver' => 's3',
        ]);
    }

    public function local(): static
    {
        return $this->state(fn (array $attributes) => [
            'storage_driver' => 'local',
        ]);
    }
}
