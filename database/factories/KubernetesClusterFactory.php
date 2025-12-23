<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KubernetesCluster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KubernetesCluster>
 */
class KubernetesClusterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = KubernetesCluster::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Kubernetes Cluster',
            'api_server_url' => fake()->url(),
            'kubeconfig' => $this->generateKubeconfig(),
            'namespace' => fake()->randomElement(['default', 'production', 'staging', 'development']),
            'context' => 'kubernetes-admin@' . fake()->slug(),
            'is_active' => fake()->boolean(80),
            'metadata' => [
                'version' => fake()->randomElement(['1.28', '1.29', '1.30']),
                'provider' => fake()->randomElement(['aws', 'gcp', 'azure', 'digitalocean', 'on-premise']),
                'region' => fake()->city(),
            ],
        ];
    }

    /**
     * Indicate that the cluster is active.
     *
     * @return static
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the cluster is inactive.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the cluster is for production.
     *
     * @return static
     */
    public function production(): static
    {
        return $this->state(fn (array $attributes) => [
            'namespace' => 'production',
            'is_active' => true,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'environment' => 'production',
            ]),
        ]);
    }

    /**
     * Indicate that the cluster is for staging.
     *
     * @return static
     */
    public function staging(): static
    {
        return $this->state(fn (array $attributes) => [
            'namespace' => 'staging',
            'is_active' => true,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'environment' => 'staging',
            ]),
        ]);
    }

    /**
     * Indicate that the cluster is for development.
     *
     * @return static
     */
    public function development(): static
    {
        return $this->state(fn (array $attributes) => [
            'namespace' => 'development',
            'is_active' => true,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'environment' => 'development',
            ]),
        ]);
    }

    /**
     * Generate a sample kubeconfig content.
     *
     * @return string
     */
    private function generateKubeconfig(): string
    {
        $clusterName = fake()->slug();
        $userName = 'admin-' . fake()->userName();
        $contextName = "{$userName}@{$clusterName}";

        return <<<YAML
apiVersion: v1
clusters:
- cluster:
    certificate-authority-data: LS0tLS1CRUdJTiBDRVJUSUZJQ0FURS0tLS0tCk1JSUMvakNDQWVhZ0F3SUJBZ0lCQURBTkJna3Foa2lHOXcwQkFRc0ZBREFWTVJNd0VRWURWUVFERXdwcmRXSmwKY201bGRHVnpNQjRYRFRJME1ERXdNVEF3TURBd01Gb1hEVE0wTURFeE1qQXdNREF3TUZvd0ZURVRNQkVHQTFVRQpBeE1LYTNWaVpYSnVaWFJsY3pDQ0FTSXdEUVlKS29aSWh2Y05BUUVCQlFBRGdnRVBBRENDQVFvQ2dnRUJBTQpabHIxRlF2U2c1Q2M3eE0wM1pBPT0KLS0tLS1FTkQgQ0VSVElGSUNBVEUtLS0tLQo=
    server: {$this->faker->url()}
  name: {$clusterName}
contexts:
- context:
    cluster: {$clusterName}
    user: {$userName}
  name: {$contextName}
current-context: {$contextName}
kind: Config
preferences: {}
users:
- name: {$userName}
  user:
    client-certificate-data: LS0tLS1CRUdJTiBDRVJUSUZJQ0FURS0tLS0tCk1JSURJVENDQWdtZ0F3SUJBZ0lJYVVRcE8wdTJrM0l3RFFZSktvWklodmNOQVFFTEJRQXdGVEVUTUJFR0ExVUUKQXhNS2EzVmlaWEp1WlhSbGN6QWVGdzB5TkRBeE1ERXdNREF3TURCYUZ3MHlOVEF4TURFd01EQXdNREJhTURReApGekFWQmdOVkJBb1REbk41YzNSbGJUcHRZWE4wWlhKek1Sa3dGd1lEVlFRREV4QnJkV0psY201bGRHVnpMV0ZrCmJXbHVNSUlCSWpBTkJna3Foa2lHOXcwQkFRRUZBQU9DQVE4QU1JSUJDZ0tDQVFFQT0KLS0tLS1FTkQgQ0VSVElGSUNBVEUtLS0tLQo=
    client-key-data: LS0tLS1CRUdJTiBSU0EgUFJJVkFURSBLRVktLS0tLQpNSUlFcFFJQkFBS0NBUUVBeWJHRnhYdHBuOXFLK21IZG54VGxnMGRvPQotLS0tLUVORCBSU0EgUFJJVkFURSBLRVktLS0tLQo=
YAML;
    }
}
