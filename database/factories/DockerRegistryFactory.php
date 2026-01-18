<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DockerRegistry;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DockerRegistry>
 */
class DockerRegistryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<DockerRegistry>
     */
    protected $model = DockerRegistry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $registryTypes = [
            DockerRegistry::TYPE_DOCKER_HUB,
            DockerRegistry::TYPE_GITHUB,
            DockerRegistry::TYPE_GITLAB,
            DockerRegistry::TYPE_CUSTOM,
        ];

        $registryType = fake()->randomElement($registryTypes);
        $words = fake()->words(2);
        $registryName = is_array($words) ? implode(' ', $words).' Registry' : 'Default Registry';

        return [
            'project_id' => Project::factory(),
            'name' => $registryName,
            'registry_type' => $registryType,
            'registry_url' => $this->getRegistryUrl($registryType),
            'username' => fake()->userName(),
            'credentials' => $this->getCredentialsForType($registryType),
            'email' => fake()->safeEmail(),
            'is_default' => false,
            'status' => DockerRegistry::STATUS_ACTIVE,
            'last_tested_at' => fake()->optional(0.7)->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Get registry URL for a given type
     */
    protected function getRegistryUrl(string $type): string
    {
        return match ($type) {
            DockerRegistry::TYPE_DOCKER_HUB => 'https://index.docker.io/v1/',
            DockerRegistry::TYPE_GITHUB => 'ghcr.io',
            DockerRegistry::TYPE_GITLAB => 'registry.gitlab.com',
            DockerRegistry::TYPE_GOOGLE_GCR => 'gcr.io',
            default => 'registry.'.fake()->domainName(),
        };
    }

    /**
     * Get credentials for a given registry type
     *
     * @return array<string, mixed>
     */
    protected function getCredentialsForType(string $type): array
    {
        return match ($type) {
            DockerRegistry::TYPE_DOCKER_HUB => [
                'password' => fake()->password(16),
            ],
            DockerRegistry::TYPE_GITHUB => [
                'token' => 'ghp_'.fake()->regexify('[A-Za-z0-9]{36}'),
            ],
            DockerRegistry::TYPE_GITLAB => [
                'token' => fake()->regexify('[A-Za-z0-9_-]{20}'),
            ],
            DockerRegistry::TYPE_AWS_ECR => [
                'aws_access_key_id' => 'AKIA'.fake()->regexify('[A-Z0-9]{16}'),
                'aws_secret_access_key' => fake()->regexify('[A-Za-z0-9+/]{40}'),
                'region' => fake()->randomElement(['us-east-1', 'us-west-2', 'eu-west-1']),
            ],
            DockerRegistry::TYPE_GOOGLE_GCR => [
                'service_account_json' => [
                    'type' => 'service_account',
                    'project_id' => fake()->slug(),
                    'private_key_id' => fake()->uuid(),
                    'private_key' => fake()->text(1000),
                    'client_email' => fake()->email(),
                ],
            ],
            DockerRegistry::TYPE_AZURE_ACR => [
                'password' => fake()->password(32),
                'client_id' => fake()->uuid(),
                'client_secret' => fake()->regexify('[A-Za-z0-9~._-]{34}'),
            ],
            default => [
                'password' => fake()->password(16),
            ],
        };
    }

    /**
     * Indicate that the registry is the default for the project.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the registry is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DockerRegistry::STATUS_INACTIVE,
        ]);
    }

    /**
     * Indicate that the registry has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DockerRegistry::STATUS_FAILED,
        ]);
    }

    /**
     * Create a Docker Hub registry.
     */
    public function dockerHub(): static
    {
        return $this->state(fn (array $attributes) => [
            'registry_type' => DockerRegistry::TYPE_DOCKER_HUB,
            'registry_url' => 'https://index.docker.io/v1/',
            'credentials' => [
                'password' => fake()->password(16),
            ],
        ]);
    }

    /**
     * Create a GitHub Container Registry.
     */
    public function github(): static
    {
        return $this->state(fn (array $attributes) => [
            'registry_type' => DockerRegistry::TYPE_GITHUB,
            'registry_url' => 'ghcr.io',
            'credentials' => [
                'token' => 'ghp_'.fake()->regexify('[A-Za-z0-9]{36}'),
            ],
        ]);
    }

    /**
     * Create a GitLab Container Registry.
     */
    public function gitlab(): static
    {
        return $this->state(fn (array $attributes) => [
            'registry_type' => DockerRegistry::TYPE_GITLAB,
            'registry_url' => 'registry.gitlab.com',
            'credentials' => [
                'token' => fake()->regexify('[A-Za-z0-9_-]{20}'),
            ],
        ]);
    }

    /**
     * Create an AWS ECR registry.
     */
    public function awsEcr(): static
    {
        return $this->state(fn (array $attributes) => [
            'registry_type' => DockerRegistry::TYPE_AWS_ECR,
            'registry_url' => fake()->regexify('[0-9]{12}').'.dkr.ecr.us-east-1.amazonaws.com',
            'username' => 'AWS',
            'credentials' => [
                'aws_access_key_id' => 'AKIA'.fake()->regexify('[A-Z0-9]{16}'),
                'aws_secret_access_key' => fake()->regexify('[A-Za-z0-9+/]{40}'),
                'region' => 'us-east-1',
            ],
        ]);
    }

    /**
     * Create a Google Container Registry.
     */
    public function googleGcr(): static
    {
        return $this->state(fn (array $attributes) => [
            'registry_type' => DockerRegistry::TYPE_GOOGLE_GCR,
            'registry_url' => 'gcr.io',
            'username' => '_json_key',
            'credentials' => [
                'service_account_json' => [
                    'type' => 'service_account',
                    'project_id' => fake()->slug(),
                    'private_key_id' => fake()->uuid(),
                    'private_key' => fake()->text(1000),
                    'client_email' => fake()->email(),
                ],
            ],
        ]);
    }

    /**
     * Create an Azure Container Registry.
     */
    public function azureAcr(): static
    {
        return $this->state(fn (array $attributes) => [
            'registry_type' => DockerRegistry::TYPE_AZURE_ACR,
            'registry_url' => fake()->slug().'.azurecr.io',
            'credentials' => [
                'password' => fake()->password(32),
            ],
        ]);
    }

    /**
     * Create a custom registry.
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'registry_type' => DockerRegistry::TYPE_CUSTOM,
            'registry_url' => 'registry.'.fake()->domainName(),
            'credentials' => [
                'password' => fake()->password(16),
            ],
        ]);
    }
}
