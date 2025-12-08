<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $subdomain = fake()->unique()->slug();
        
        return [
            'project_id' => Project::factory(),
            'name' => fake()->company(),
            'subdomain' => $subdomain,
            'database' => 'tenant_' . $subdomain,
            'admin_email' => fake()->safeEmail(),
            'admin_password' => 'password',
            'plan' => fake()->randomElement(['free', 'starter', 'professional', 'premium', 'enterprise']),
            'status' => 'active',
            'custom_config' => null,
            'features' => null,
            'trial_ends_at' => null,
            'last_deployed_at' => null,
        ];
    }

    public function onTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->subDays(1),
        ]);
    }

    public function withCustomConfig(): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_config' => [
                'theme' => 'light',
                'language' => 'en',
                'timezone' => 'UTC',
            ],
        ]);
    }

    public function withFeatures(): static
    {
        return $this->state(fn (array $attributes) => [
            'features' => [
                'api_access' => true,
                'webhooks' => true,
                'custom_domain' => false,
            ],
        ]);
    }
}
