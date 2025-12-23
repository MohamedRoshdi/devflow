<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider = fake()->randomElement(['github', 'gitlab', 'bitbucket']);
        $status = fake()->randomElement(['success', 'failed', 'pending', 'ignored']);

        return [
            'project_id' => Project::factory(),
            'provider' => $provider,
            'event_type' => fake()->randomElement(['push', 'pull_request', 'release', 'deployment']),
            'payload' => [
                'ref' => 'refs/heads/main',
                'repository' => [
                    'name' => fake()->word(),
                    'url' => fake()->url(),
                ],
                'commits' => [
                    [
                        'id' => fake()->sha1(),
                        'message' => fake()->sentence(),
                        'author' => fake()->name(),
                    ],
                ],
            ],
            'signature' => hash_hmac('sha256', 'test-payload', 'secret'),
            'status' => $status,
            'response' => $status === 'success' ? 'Webhook processed successfully' : null,
            'deployment_id' => null,
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the webhook was successful.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'response' => 'Webhook processed successfully',
        ]);
    }

    /**
     * Indicate that the webhook failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'response' => 'Failed to process webhook: '.fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the webhook was ignored.
     */
    public function ignored(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ignored',
            'response' => 'Webhook ignored: not a deployment event',
        ]);
    }

    /**
     * Indicate that the webhook is from GitHub.
     */
    public function github(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'github',
        ]);
    }

    /**
     * Indicate that the webhook is from GitLab.
     */
    public function gitlab(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'gitlab',
        ]);
    }

    /**
     * Indicate that the webhook triggered a deployment.
     */
    public function withDeployment(): static
    {
        return $this->state(fn (array $attributes) => [
            'deployment_id' => Deployment::factory(),
            'status' => 'success',
        ]);
    }
}
