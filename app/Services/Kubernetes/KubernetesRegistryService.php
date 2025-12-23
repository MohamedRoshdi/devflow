<?php

declare(strict_types=1);

namespace App\Services\Kubernetes;

use App\Models\DockerRegistry;
use App\Models\KubernetesCluster;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Handles Docker registry operations for Kubernetes.
 *
 * Responsible for managing Docker registry credentials,
 * creating Kubernetes secrets, and testing registry connections.
 */
class KubernetesRegistryService
{
    protected string $kubectlPath = '/usr/local/bin/kubectl';

    /**
     * Create or update Docker registry secrets in Kubernetes
     *
     * @return array<int, array<string, mixed>>
     */
    public function createDockerRegistrySecrets(Project $project): array
    {
        $results = [];
        $registries = $project->dockerRegistries()->active()->get();

        foreach ($registries as $registry) {
            try {
                $result = $this->createRegistrySecret($project, $registry);
                $results[] = $result;
            } catch (\Exception $e) {
                Log::error('KubernetesRegistryService: Failed to create registry secret', [
                    'project_id' => $project->id,
                    'registry_id' => $registry->id,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'success' => false,
                    'registry_id' => $registry->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Create a single Docker registry secret
     *
     * @return array<string, mixed>
     */
    public function createRegistrySecret(Project $project, DockerRegistry $registry): array
    {
        if (! $registry->isConfigured()) {
            throw new \Exception("Registry {$registry->name} is not properly configured");
        }

        if (! $registry->validateCredentials()) {
            throw new \Exception("Registry {$registry->name} has invalid credentials");
        }

        $secretName = $registry->getSecretName();
        $namespace = $project->slug;

        // Delete existing secret if it exists
        Process::run(
            sprintf(
                '%s delete secret %s -n %s --ignore-not-found=true',
                $this->kubectlPath,
                $secretName,
                $namespace
            )
        );

        // Create new secret
        $command = sprintf(
            '%s create secret docker-registry %s -n %s --docker-server=%s --docker-username=%s --docker-password=%s',
            $this->kubectlPath,
            $secretName,
            $namespace,
            escapeshellarg($registry->registry_url),
            escapeshellarg($registry->username),
            escapeshellarg($registry->getDecryptedPassword() ?? '')
        );

        if ($registry->email) {
            $command .= sprintf(' --docker-email=%s', escapeshellarg($registry->email));
        }

        $result = Process::run($command);

        if (! $result->successful()) {
            throw new \Exception("Failed to create registry secret: {$result->errorOutput()}");
        }

        return [
            'success' => true,
            'registry_id' => $registry->id,
            'secret_name' => $secretName,
            'output' => $result->output(),
        ];
    }

    /**
     * Build Docker config JSON for registry authentication
     *
     * @return array<string, mixed>
     */
    public function buildDockerConfigJson(DockerRegistry $registry): array
    {
        $auth = base64_encode($registry->username.':'.$registry->getDecryptedPassword());

        return [
            'auths' => [
                $registry->registry_url => [
                    'username' => $registry->username,
                    'password' => $registry->getDecryptedPassword(),
                    'email' => $registry->email ?? '',
                    'auth' => $auth,
                ],
            ],
        ];
    }

    /**
     * Get image pull secrets for a project
     *
     * @return array<int, array<string, string>>
     */
    public function getImagePullSecrets(Project $project): array
    {
        $secrets = [];

        $registries = $project->dockerRegistries()->active()->get();

        foreach ($registries as $registry) {
            $secrets[] = [
                'name' => $registry->getSecretName(),
            ];
        }

        return $secrets;
    }

    /**
     * Store Docker registry credentials for a project
     *
     * @param  array<string, mixed>  $credentialsData
     */
    public function storeDockerRegistryCredentials(Project $project, array $credentialsData): DockerRegistry
    {
        // Validate required fields
        $this->validateRegistryData($credentialsData);

        // Extract credentials based on registry type
        $credentials = $this->extractCredentials($credentialsData);

        // Create registry record
        $registry = $project->dockerRegistries()->create([
            'name' => $credentialsData['name'],
            'registry_type' => $credentialsData['registry_type'],
            'registry_url' => $credentialsData['registry_url'] ?? DockerRegistry::getDefaultUrl($credentialsData['registry_type']),
            'username' => $credentialsData['username'],
            'credentials' => $credentials,
            'email' => $credentialsData['email'] ?? null,
            'is_default' => $credentialsData['is_default'] ?? false,
            'status' => DockerRegistry::STATUS_ACTIVE,
        ]);

        return $registry;
    }

    /**
     * Retrieve Docker registry credentials for a project
     *
     * @return array<int, DockerRegistry>
     */
    public function getDockerRegistryCredentials(Project $project): array
    {
        return $project->dockerRegistries()->active()->get()->all();
    }

    /**
     * Get the default Docker registry for a project
     */
    public function getDefaultDockerRegistry(Project $project): ?DockerRegistry
    {
        return $project->defaultDockerRegistry;
    }

    /**
     * Update Docker registry credentials
     *
     * @param  array<string, mixed>  $credentialsData
     */
    public function updateDockerRegistryCredentials(DockerRegistry $registry, array $credentialsData): DockerRegistry
    {
        // Validate the data
        $this->validateRegistryData($credentialsData, false);

        // Extract credentials if password/token is being updated
        if (isset($credentialsData['password']) || isset($credentialsData['token']) || isset($credentialsData['credentials'])) {
            $credentials = $this->extractCredentials($credentialsData);
            $registry->credentials = $credentials;
        }

        // Update other fields
        $registry->fill([
            'name' => $credentialsData['name'] ?? $registry->name,
            'registry_url' => $credentialsData['registry_url'] ?? $registry->registry_url,
            'username' => $credentialsData['username'] ?? $registry->username,
            'email' => $credentialsData['email'] ?? $registry->email,
            'is_default' => $credentialsData['is_default'] ?? $registry->is_default,
        ]);

        $registry->save();

        return $registry;
    }

    /**
     * Delete Docker registry credentials
     */
    public function deleteDockerRegistryCredentials(DockerRegistry $registry, ?KubernetesCluster $cluster = null): bool
    {
        try {
            // Delete the Kubernetes secret if it exists
            $project = $registry->project;
            $secretName = $registry->getSecretName();

            if ($project && ($cluster || $project->kubernetesCluster)) {
                $targetCluster = $cluster ?? $project->kubernetesCluster;
                $this->setupKubectlContext($targetCluster);

                Process::run(
                    sprintf(
                        '%s delete secret %s -n %s --ignore-not-found=true',
                        $this->kubectlPath,
                        $secretName,
                        $project->slug
                    )
                );
            }

            // Delete the database record
            return (bool) $registry->delete();
        } catch (\Exception $e) {
            Log::error('KubernetesRegistryService: Failed to delete registry credentials', [
                'registry_id' => $registry->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Test Docker registry connection
     *
     * @return array<string, mixed>
     */
    public function testDockerRegistryConnection(DockerRegistry $registry): array
    {
        try {
            if (! $registry->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Registry is not properly configured',
                ];
            }

            if (! $registry->validateCredentials()) {
                return [
                    'success' => false,
                    'message' => 'Registry credentials are invalid',
                ];
            }

            // Attempt to login to the registry
            $password = $registry->getDecryptedPassword();
            $command = sprintf(
                'echo %s | docker login %s --username %s --password-stdin',
                escapeshellarg($password ?? ''),
                escapeshellarg($registry->registry_url),
                escapeshellarg($registry->username)
            );

            $result = Process::run($command);

            if ($result->successful()) {
                // Logout after successful test
                Process::run("docker logout {$registry->registry_url}");

                // Update last tested timestamp
                $registry->update([
                    'last_tested_at' => now(),
                    'status' => DockerRegistry::STATUS_ACTIVE,
                ]);

                return [
                    'success' => true,
                    'message' => 'Successfully connected to registry',
                ];
            }

            $registry->update(['status' => DockerRegistry::STATUS_FAILED]);

            return [
                'success' => false,
                'message' => 'Failed to connect to registry',
                'error' => $result->errorOutput(),
            ];
        } catch (\Exception $e) {
            Log::error('KubernetesRegistryService: Failed to test registry connection', [
                'registry_id' => $registry->id,
                'error' => $e->getMessage(),
            ]);

            $registry->update(['status' => DockerRegistry::STATUS_FAILED]);

            return [
                'success' => false,
                'message' => 'Exception occurred during connection test',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate registry data
     *
     * @param  array<string, mixed>  $data
     */
    protected function validateRegistryData(array $data, bool $isCreate = true): void
    {
        if ($isCreate) {
            if (empty($data['name'])) {
                throw new \InvalidArgumentException('Registry name is required');
            }

            if (empty($data['registry_type'])) {
                throw new \InvalidArgumentException('Registry type is required');
            }

            if (empty($data['username'])) {
                throw new \InvalidArgumentException('Username is required');
            }
        }

        // Validate registry type
        if (isset($data['registry_type']) && ! in_array($data['registry_type'], array_keys(DockerRegistry::getRegistryTypes()))) {
            throw new \InvalidArgumentException('Invalid registry type');
        }

        // Validate registry URL format
        if (isset($data['registry_url']) && ! $this->isValidRegistryUrl($data['registry_url'])) {
            throw new \InvalidArgumentException('Invalid registry URL format');
        }
    }

    /**
     * Validate registry URL format
     */
    protected function isValidRegistryUrl(string $url): bool
    {
        // Allow domain names with or without protocol
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return true;
        }

        // Also allow simple domain names without protocol
        $pattern = '/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}(:[0-9]{1,5})?$/i';

        return preg_match($pattern, $url) === 1;
    }

    /**
     * Extract credentials from data based on registry type
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractCredentials(array $data): array
    {
        $registryType = $data['registry_type'] ?? DockerRegistry::TYPE_DOCKER_HUB;

        return match ($registryType) {
            DockerRegistry::TYPE_DOCKER_HUB => [
                'password' => $data['password'] ?? '',
            ],
            DockerRegistry::TYPE_GITHUB => [
                'token' => $data['token'] ?? $data['password'] ?? '',
            ],
            DockerRegistry::TYPE_GITLAB => [
                'token' => $data['token'] ?? null,
                'password' => $data['password'] ?? null,
            ],
            DockerRegistry::TYPE_AWS_ECR => [
                'aws_access_key_id' => $data['aws_access_key_id'] ?? '',
                'aws_secret_access_key' => $data['aws_secret_access_key'] ?? '',
                'region' => $data['region'] ?? 'us-east-1',
            ],
            DockerRegistry::TYPE_GOOGLE_GCR => [
                'service_account_json' => $data['service_account_json'] ?? [],
            ],
            DockerRegistry::TYPE_AZURE_ACR => [
                'password' => $data['password'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'client_secret' => $data['client_secret'] ?? null,
            ],
            default => [
                'password' => $data['password'] ?? '',
            ],
        };
    }

    /**
     * Setup kubectl context for the cluster
     */
    protected function setupKubectlContext(KubernetesCluster $cluster): void
    {
        $kubeconfigPath = "/tmp/kubeconfig-{$cluster->id}";
        file_put_contents($kubeconfigPath, $cluster->kubeconfig);
        putenv("KUBECONFIG={$kubeconfigPath}");
    }

    /**
     * Get kubectl path
     */
    public function getKubectlPath(): string
    {
        return $this->kubectlPath;
    }
}
