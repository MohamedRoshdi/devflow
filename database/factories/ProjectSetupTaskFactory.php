<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectSetupTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectSetupTask>
 */
class ProjectSetupTaskFactory extends Factory
{
    protected $model = ProjectSetupTask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'task_type' => $this->faker->randomElement(ProjectSetupTask::getAllTypes()),
            'status' => ProjectSetupTask::STATUS_PENDING,
            'message' => null,
            'result_data' => null,
            'progress' => 0,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the task is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectSetupTask::STATUS_PENDING,
            'progress' => 0,
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the task is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectSetupTask::STATUS_RUNNING,
            'progress' => $this->faker->numberBetween(1, 99),
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the task is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectSetupTask::STATUS_COMPLETED,
            'progress' => 100,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'message' => 'Task completed successfully',
        ]);
    }

    /**
     * Indicate that the task has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectSetupTask::STATUS_FAILED,
            'progress' => $this->faker->numberBetween(0, 99),
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'message' => 'Task failed: ' . $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the task is skipped.
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectSetupTask::STATUS_SKIPPED,
            'progress' => 0,
            'started_at' => null,
            'completed_at' => now(),
            'message' => 'Skipped by user',
        ]);
    }

    /**
     * Set the task type to SSL.
     */
    public function ssl(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_type' => ProjectSetupTask::TYPE_SSL,
        ]);
    }

    /**
     * Set the task type to webhook.
     */
    public function webhook(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
        ]);
    }

    /**
     * Set the task type to health check.
     */
    public function healthCheck(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_type' => ProjectSetupTask::TYPE_HEALTH_CHECK,
        ]);
    }

    /**
     * Set the task type to backup.
     */
    public function backup(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_type' => ProjectSetupTask::TYPE_BACKUP,
        ]);
    }

    /**
     * Set the task type to notifications.
     */
    public function notifications(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
        ]);
    }

    /**
     * Set the task type to deployment.
     */
    public function deployment(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_type' => ProjectSetupTask::TYPE_DEPLOYMENT,
        ]);
    }

    /**
     * Set result data for the task.
     */
    public function withResultData(array $data): static
    {
        return $this->state(fn (array $attributes) => [
            'result_data' => $data,
        ]);
    }

    /**
     * Set a specific progress value.
     */
    public function withProgress(int $progress): static
    {
        return $this->state(fn (array $attributes) => [
            'progress' => min(100, max(0, $progress)),
        ]);
    }

    /**
     * Set a custom message.
     */
    public function withMessage(string $message): static
    {
        return $this->state(fn (array $attributes) => [
            'message' => $message,
        ]);
    }
}
