<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Domain>
 */
class DomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => \App\Models\Project::factory(),
            'domain' => fake()->domainName(),
            'is_primary' => fake()->boolean(30),
            'ssl_enabled' => fake()->boolean(80),
            'ssl_provider' => fake()->randomElement(['letsencrypt', 'custom', null]),
            'ssl_certificate' => fake()->boolean(70) ? fake()->text(200) : null,
            'ssl_private_key' => fake()->boolean(70) ? fake()->text(200) : null,
            'ssl_issued_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-1 year', 'now') : null,
            'ssl_expires_at' => fake()->boolean(70) ? fake()->dateTimeBetween('now', '+1 year') : null,
            'auto_renew_ssl' => fake()->boolean(70),
            'dns_configured' => fake()->boolean(80),
            'status' => fake()->randomElement(['active', 'inactive', 'pending']),
            'metadata' => fake()->boolean(50) ? ['key' => 'value'] : null,
        ];
    }
}
