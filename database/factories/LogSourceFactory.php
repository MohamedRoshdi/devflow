<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LogSource>
 */
class LogSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sources = [
            ['name' => 'Laravel Application Logs', 'type' => 'file', 'path' => '/var/www/*/storage/logs/laravel.log'],
            ['name' => 'Nginx Access Logs', 'type' => 'file', 'path' => '/var/log/nginx/access.log'],
            ['name' => 'Nginx Error Logs', 'type' => 'file', 'path' => '/var/log/nginx/error.log'],
            ['name' => 'PHP-FPM Logs', 'type' => 'file', 'path' => '/var/log/php8.4-fpm.log'],
            ['name' => 'MySQL Error Logs', 'type' => 'file', 'path' => '/var/log/mysql/error.log'],
            ['name' => 'System Logs', 'type' => 'file', 'path' => '/var/log/syslog'],
            ['name' => 'Docker Container Logs', 'type' => 'docker', 'path' => 'container_name'],
        ];

        $source = fake()->randomElement($sources);

        return [
            'server_id' => \App\Models\Server::factory(),
            'project_id' => fake()->boolean(60) ? \App\Models\Project::factory() : null,
            'name' => $source['name'],
            'type' => $source['type'],
            'path' => $source['path'],
            'is_active' => fake()->boolean(80),
            'last_synced_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-7 days', 'now') : null,
            'last_position' => fake()->numberBetween(0, 1000000),
        ];
    }
}
