<?php

namespace Database\Factories;

use App\Models\Deployment;
use App\Models\DeploymentApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeploymentApproval>
 */
class DeploymentApprovalFactory extends Factory
{
    protected $model = DeploymentApproval::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deployment_id' => Deployment::factory(),
            'requested_by' => User::factory(),
            'approved_by' => null,
            'status' => 'pending',
            'notes' => fake()->optional()->sentence(),
            'requested_at' => now(),
            'responded_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by' => User::factory(),
            'responded_at' => now(),
            'notes' => fake()->sentence(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'responded_at' => null,
        ]);
    }
}
