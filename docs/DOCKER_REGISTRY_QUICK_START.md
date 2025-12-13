# Docker Registry Credentials - Quick Start Guide

## Table of Contents
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Registry Types](#registry-types)
- [API Reference](#api-reference)
- [Common Scenarios](#common-scenarios)

## Installation

1. Run the migration:
```bash
php artisan migrate
```

2. The feature is ready to use - no additional configuration needed!

## Basic Usage

### Adding a Registry

```php
use App\Services\Kubernetes\KubernetesService;
use App\Models\Project;

$service = app(KubernetesService::class);
$project = Project::findBySlug('my-project');

// Add Docker Hub credentials
$registry = $service->storeDockerRegistryCredentials($project, [
    'name' => 'Docker Hub Production',
    'registry_type' => 'docker_hub',
    'username' => 'mycompany',
    'password' => 'secret-token',
    'email' => 'devops@mycompany.com',
    'is_default' => true,
]);
```

### Testing Connection

```php
$result = $service->testDockerRegistryConnection($registry);

if ($result['success']) {
    // Connection successful
    echo "Registry is working!";
} else {
    // Connection failed
    echo "Error: " . $result['error'];
}
```

### Deploying with Registry

```php
// Registry secrets are automatically created during deployment
$deployment = $service->deployToKubernetes($project);
```

## Registry Types

### Docker Hub
```php
[
    'name' => 'Docker Hub',
    'registry_type' => 'docker_hub',
    'registry_url' => 'https://index.docker.io/v1/', // Optional, auto-filled
    'username' => 'dockeruser',
    'password' => 'dckr_pat_xxxxxxxxxxxxx',
    'email' => 'user@example.com',
]
```

### GitHub Container Registry
```php
[
    'name' => 'GitHub Packages',
    'registry_type' => 'github',
    'registry_url' => 'ghcr.io', // Optional, auto-filled
    'username' => 'githubuser',
    'token' => 'ghp_xxxxxxxxxxxxxxxxxxxx', // Personal Access Token
]
```

### GitLab Container Registry
```php
[
    'name' => 'GitLab Registry',
    'registry_type' => 'gitlab',
    'registry_url' => 'registry.gitlab.com', // Optional, auto-filled
    'username' => 'gitlabuser',
    'token' => 'glpat-xxxxxxxxxxxx', // Deploy token or PAT
]
```

### AWS ECR
```php
[
    'name' => 'AWS ECR',
    'registry_type' => 'aws_ecr',
    'registry_url' => '123456789012.dkr.ecr.us-east-1.amazonaws.com',
    'username' => 'AWS',
    'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
    'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'region' => 'us-east-1',
]
```

### Google GCR
```php
[
    'name' => 'Google Cloud',
    'registry_type' => 'google_gcr',
    'registry_url' => 'gcr.io', // Optional, auto-filled
    'username' => '_json_key',
    'service_account_json' => json_decode(file_get_contents('service-account.json'), true),
]
```

### Azure ACR
```php
[
    'name' => 'Azure Registry',
    'registry_type' => 'azure_acr',
    'registry_url' => 'myregistry.azurecr.io',
    'username' => 'myregistry',
    'password' => 'xxxxxxxxxxxxxxxx',
]
```

### Custom Registry
```php
[
    'name' => 'Private Registry',
    'registry_type' => 'custom',
    'registry_url' => 'registry.mycompany.com:5000',
    'username' => 'admin',
    'password' => 'secretpassword',
]
```

## API Reference

### KubernetesService Methods

#### `storeDockerRegistryCredentials(Project $project, array $data): DockerRegistry`
Create a new registry configuration.

**Parameters:**
- `$project` - The project to add the registry to
- `$data` - Registry configuration array

**Returns:** Created `DockerRegistry` model

**Throws:** `\InvalidArgumentException` on validation error

---

#### `getDockerRegistryCredentials(Project $project): array`
Get all active registries for a project.

**Returns:** Array of `DockerRegistry` models

---

#### `getDefaultDockerRegistry(Project $project): ?DockerRegistry`
Get the default registry for a project.

**Returns:** Default `DockerRegistry` or null

---

#### `updateDockerRegistryCredentials(DockerRegistry $registry, array $data): DockerRegistry`
Update existing registry credentials.

**Parameters:**
- `$registry` - The registry to update
- `$data` - New configuration data (partial updates allowed)

**Returns:** Updated `DockerRegistry` model

---

#### `deleteDockerRegistryCredentials(DockerRegistry $registry): bool`
Delete a registry and its Kubernetes secret.

**Returns:** `true` on success, `false` on failure

---

#### `testDockerRegistryConnection(DockerRegistry $registry): array`
Test registry credentials.

**Returns:**
```php
[
    'success' => true/false,
    'message' => 'Status message',
    'error' => 'Error details' // Only on failure
]
```

---

#### `createDockerRegistrySecrets(Project $project): array`
Create Kubernetes secrets for all active registries.

**Returns:** Array of creation results

---

### DockerRegistry Model Methods

#### `isConfigured(): bool`
Check if registry has all required fields.

#### `validateCredentials(): bool`
Validate credentials based on registry type.

#### `getDecryptedPassword(): ?string`
Get the decrypted password/token.

#### `getSecretName(): string`
Get the Kubernetes secret name for this registry.

#### `getMaskedCredentials(): array`
Get credentials with sensitive data masked.

## Common Scenarios

### Scenario 1: Multiple Registries
Use different registries for different purposes:

```php
// Public images from GitHub
$githubRegistry = $service->storeDockerRegistryCredentials($project, [
    'name' => 'GitHub Public',
    'registry_type' => 'github',
    'username' => 'myorg',
    'token' => 'ghp_xxxxx',
    'is_default' => true,
]);

// Private images from Docker Hub
$dockerRegistry = $service->storeDockerRegistryCredentials($project, [
    'name' => 'Docker Hub Private',
    'registry_type' => 'docker_hub',
    'username' => 'mycompany',
    'password' => 'dckr_pat_xxxxx',
]);

// Both secrets will be used during deployment
```

### Scenario 2: Updating Expired Token

```php
$registry = DockerRegistry::find(1);

// Update just the token
$service->updateDockerRegistryCredentials($registry, [
    'token' => 'new_token_here',
]);
```

### Scenario 3: Switching Default Registry

```php
$newDefault = DockerRegistry::find(2);

// Setting is_default=true automatically unsets other defaults
$service->updateDockerRegistryCredentials($newDefault, [
    'is_default' => true,
]);
```

### Scenario 4: Testing Before Deployment

```php
$registries = $service->getDockerRegistryCredentials($project);

foreach ($registries as $registry) {
    $result = $service->testDockerRegistryConnection($registry);

    if (!$result['success']) {
        // Handle failed registry
        Log::warning("Registry {$registry->name} failed: {$result['error']}");

        // Optionally disable it
        $registry->update(['status' => 'inactive']);
    }
}
```

### Scenario 5: Migration from Hardcoded Secrets

```php
// 1. Create registry record
$registry = $service->storeDockerRegistryCredentials($project, [
    'name' => 'Production Registry',
    'registry_type' => 'docker_hub',
    'username' => env('DOCKER_USERNAME'),
    'password' => env('DOCKER_PASSWORD'),
    'is_default' => true,
]);

// 2. Deploy - secrets will be created automatically
$service->deployToKubernetes($project);

// 3. Remove hardcoded credentials from .env
```

### Scenario 6: Conditional Registry Usage

```php
use App\Models\DockerRegistry;

// Only use GitHub registry for specific project
if ($project->framework === 'laravel') {
    $registry = DockerRegistry::factory()
        ->github()
        ->for($project)
        ->create();
}
```

## Troubleshooting

### Issue: "Registry credentials are invalid"
**Solution:** Check that the credentials format matches the registry type. For example, GitHub requires a `token` field, not `password`.

### Issue: "Failed to create registry secret"
**Solution:** Ensure the Kubernetes cluster is accessible and the namespace exists.

### Issue: Credentials not decrypting
**Solution:** Verify that `APP_KEY` hasn't changed since the credentials were encrypted.

### Issue: Default registry not being used
**Solution:** Check that the registry status is 'active' and `is_default` is true:
```php
$project->defaultDockerRegistry; // Should return a registry
```

## Best Practices

1. **Use Default Registry:** Set one registry as default for primary image source
2. **Test Connections:** Always test after adding/updating credentials
3. **Rotate Credentials:** Regularly update tokens and passwords
4. **Monitor Status:** Check `last_tested_at` timestamp to identify stale configs
5. **Secure Storage:** Never commit credentials to version control
6. **Audit Access:** Use Laravel logs to track credential usage

## Security Notes

- All credentials are encrypted at rest using AES-256-CBC
- Credentials are never logged or exposed in responses
- Use `getMaskedCredentials()` for UI display
- Kubernetes secrets are automatically cleaned up on deletion
- Registry URLs are validated before storage

## Need Help?

- Check the full implementation documentation: `DOCKER_REGISTRY_IMPLEMENTATION.md`
- Review the model: `app/Models/DockerRegistry.php`
- Review the service: `app/Services/Kubernetes/KubernetesService.php`
- Check factory examples: `database/factories/DockerRegistryFactory.php`
