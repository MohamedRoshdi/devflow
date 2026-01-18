<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Deployment;
use App\Models\TenantDeployment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantDeployment>
 */
class TenantDeploymentFactory extends Factory
{
    protected $model = TenantDeployment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'deployment_id' => Deployment::factory(),
            'status' => fake()->randomElement(['pending', 'running', 'success', 'failed']),
            'output' => fake()->optional()->paragraph(),
        ];
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'output' => 'Tenant deployment completed successfully',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'output' => 'Tenant deployment failed: Database migration error',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'output' => null,
        ]);
    }
}
