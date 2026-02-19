<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerCommandHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerCommandHistory>
 */
class ServerCommandHistoryFactory extends Factory
{
    protected $model = ServerCommandHistory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-30 days', 'now');
        $durationMs = $this->faker->numberBetween(50, 30000);
        $completedAt = (clone $startedAt)->modify("+{$durationMs} milliseconds");

        return [
            'server_id' => Server::factory(),
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                'reboot',
                'restart_service',
                'clear_cache',
                'deploy',
                'health_check',
                'get_metrics',
                'get_info',
                'ping',
            ]),
            'command' => $this->faker->randomElement([
                'systemctl restart nginx',
                'docker-compose up -d',
                'php artisan cache:clear',
                'echo "health check"',
                'uptime',
                'df -h',
            ]),
            'execution_type' => $this->faker->randomElement(['local', 'ssh']),
            'status' => $this->faker->randomElement(['success', 'failed', 'pending', 'running']),
            'output' => $this->faker->optional(0.7)->sentence(),
            'error_output' => $this->faker->optional(0.2)->sentence(),
            'exit_code' => $this->faker->randomElement([0, 0, 0, 1, 127]),
            'duration_ms' => $durationMs,
            'metadata' => $this->faker->optional(0.3)->passthrough([
                'service' => $this->faker->word(),
                'reason' => $this->faker->sentence(),
            ]),
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'exit_code' => 0,
            'error_output' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'exit_code' => 1,
            'error_output' => $this->faker->sentence(),
        ]);
    }

    public function local(): static
    {
        return $this->state(fn (array $attributes) => [
            'execution_type' => 'local',
        ]);
    }

    public function ssh(): static
    {
        return $this->state(fn (array $attributes) => [
            'execution_type' => 'ssh',
        ]);
    }
}
