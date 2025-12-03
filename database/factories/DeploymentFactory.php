<?php

namespace Database\Factories;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deployment>
 */
class DeploymentFactory extends Factory
{
    protected $model = Deployment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'running', 'success', 'failed', 'cancelled']);
        $startedAt = fake()->dateTimeBetween('-7 days', 'now');
        $completedAt = in_array($status, ['success', 'failed', 'cancelled'])
            ? fake()->dateTimeBetween($startedAt, 'now')
            : null;

        $durationSeconds = $completedAt
            ? $completedAt->getTimestamp() - $startedAt->getTimestamp()
            : null;

        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'server_id' => Server::factory(),
            'branch' => fake()->randomElement(['main', 'master', 'develop', 'staging']),
            'commit_hash' => fake()->sha1(),
            'commit_message' => fake()->sentence(),
            'status' => $status,
            'triggered_by' => fake()->randomElement(['manual', 'webhook', 'scheduled', 'rollback']),
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'duration_seconds' => $durationSeconds,
            'output' => $status === 'success' ? "Deployment completed successfully\n" : null,
            'error_message' => $status === 'failed' ? fake()->sentence() : null,
            'rollback_deployment_id' => null,
        ];
    }

    /**
     * Indicate that the deployment is successful.
     */
    public function success(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = $attributes['started_at'] ?? now()->subMinutes(5);
            $completedAt = fake()->dateTimeBetween($startedAt, 'now');

            return [
                'status' => 'success',
                'completed_at' => $completedAt,
                'duration_seconds' => $completedAt->getTimestamp() - $startedAt->getTimestamp(),
                'output' => "Deployment completed successfully\nAll services started\n",
                'error_message' => null,
            ];
        });
    }

    /**
     * Indicate that the deployment failed.
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = $attributes['started_at'] ?? now()->subMinutes(5);
            $completedAt = fake()->dateTimeBetween($startedAt, 'now');

            return [
                'status' => 'failed',
                'completed_at' => $completedAt,
                'duration_seconds' => $completedAt->getTimestamp() - $startedAt->getTimestamp(),
                'error_message' => fake()->randomElement([
                    'Docker build failed: out of memory',
                    'Git pull failed: authentication error',
                    'Container startup failed: port already in use',
                ]),
            ];
        });
    }

    /**
     * Indicate that the deployment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'duration_seconds' => null,
        ]);
    }

    /**
     * Indicate that the deployment is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now(),
            'completed_at' => null,
            'duration_seconds' => null,
        ]);
    }
}
