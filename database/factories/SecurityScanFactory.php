<?php

namespace Database\Factories;

use App\Models\SecurityScan;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SecurityScan>
 */
class SecurityScanFactory extends Factory
{
    protected $model = SecurityScan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $score = fake()->numberBetween(0, 100);

        return [
            'server_id' => Server::factory(),
            'status' => fake()->randomElement([
                SecurityScan::STATUS_PENDING,
                SecurityScan::STATUS_RUNNING,
                SecurityScan::STATUS_COMPLETED,
                SecurityScan::STATUS_FAILED,
            ]),
            'score' => $score,
            'risk_level' => SecurityScan::getRiskLevelFromScore($score),
            'findings' => [
                [
                    'category' => 'Firewall',
                    'severity' => 'medium',
                    'message' => 'UFW firewall is not enabled',
                ],
                [
                    'category' => 'SSH',
                    'severity' => 'high',
                    'message' => 'Root login is enabled via SSH',
                ],
            ],
            'recommendations' => [
                [
                    'priority' => 'high',
                    'title' => 'Enable UFW Firewall',
                    'description' => 'Configure and enable UFW firewall to protect your server from unauthorized access.',
                ],
                [
                    'priority' => 'medium',
                    'title' => 'Disable Root SSH Login',
                    'description' => 'Disable direct root login via SSH to enhance server security.',
                ],
                [
                    'priority' => 'low',
                    'title' => 'Install Fail2ban',
                    'description' => 'Install fail2ban for intrusion prevention and automatic IP blocking.',
                ],
            ],
            'started_at' => fake()->optional()->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => fake()->optional()->dateTimeBetween('now', '+1 hour'),
            'triggered_by' => fake()->optional()->randomElement([null, User::factory()]),
        ];
    }

    /**
     * Indicate that the scan is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SecurityScan::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the scan is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SecurityScan::STATUS_RUNNING,
            'started_at' => now()->subMinutes(5),
            'completed_at' => null,
        ]);
    }
}
