<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * Docker Registry Model
 *
 * Manages Docker registry credentials for pulling private images
 * Supports multiple registry providers with encrypted credentials
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string $registry_type
 * @property string $registry_url
 * @property string $username
 * @property string $credentials_encrypted
 * @property string $email
 * @property bool $is_default
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_tested_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project $project
 * @property-read array<string, mixed> $credentials
 */
class DockerRegistry extends Model
{
    /** @use HasFactory<\Database\Factories\DockerRegistryFactory> */
    use HasFactory;

    /**
     * Registry types
     */
    public const TYPE_DOCKER_HUB = 'docker_hub';

    public const TYPE_GITHUB = 'github';

    public const TYPE_GITLAB = 'gitlab';

    public const TYPE_AWS_ECR = 'aws_ecr';

    public const TYPE_GOOGLE_GCR = 'google_gcr';

    public const TYPE_AZURE_ACR = 'azure_acr';

    public const TYPE_CUSTOM = 'custom';

    /**
     * Registry status
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_FAILED = 'failed';

    /**
     * Default registry URLs for known providers
     *
     * @var array<string, string>
     */
    public const REGISTRY_URLS = [
        self::TYPE_DOCKER_HUB => 'https://index.docker.io/v1/',
        self::TYPE_GITHUB => 'ghcr.io',
        self::TYPE_GITLAB => 'registry.gitlab.com',
        self::TYPE_GOOGLE_GCR => 'gcr.io',
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'registry_type',
        'registry_url',
        'username',
        'credentials_encrypted',
        'email',
        'is_default',
        'status',
        'last_tested_at',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'credentials_encrypted',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        // Ensure only one default registry per project
        static::saving(function (DockerRegistry $registry) {
            if ($registry->is_default) {
                DockerRegistry::where('project_id', $registry->project_id)
                    ->where('id', '!=', $registry->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get encrypted credentials as array
     *
     * @param  string|null  $value
     * @return array<string, mixed>
     */
    public function getCredentialsAttribute($value): array
    {
        if (empty($this->credentials_encrypted)) {
            return [];
        }

        try {
            $decrypted = Crypt::decryptString($this->credentials_encrypted);

            return json_decode($decrypted, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set credentials with encryption
     *
     * @param  array<string, mixed>|string|null  $value
     */
    public function setCredentialsAttribute($value): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        if (empty($value)) {
            $this->attributes['credentials_encrypted'] = '';
        } else {
            $this->attributes['credentials_encrypted'] = Crypt::encryptString($value);
        }
    }

    /**
     * Get the decrypted password/token for authentication
     */
    public function getDecryptedPassword(): ?string
    {
        $credentials = $this->credentials;

        return $credentials['password'] ?? $credentials['token'] ?? null;
    }

    /**
     * Get the registry secret name for Kubernetes
     */
    public function getSecretName(): string
    {
        $projectSlug = $this->project->slug ?? 'default';

        return "{$projectSlug}-registry-{$this->id}";
    }

    /**
     * Project relationship
     *
     * @return BelongsTo<Project, DockerRegistry>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope for active registries
     *
     * @param  Builder<DockerRegistry>  $query
     * @return Builder<DockerRegistry>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for default registry
     *
     * @param  Builder<DockerRegistry>  $query
     * @return Builder<DockerRegistry>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for specific registry type
     *
     * @param  Builder<DockerRegistry>  $query
     * @return Builder<DockerRegistry>
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('registry_type', $type);
    }

    /**
     * Get default registry URL for a given type
     */
    public static function getDefaultUrl(string $type): string
    {
        return self::REGISTRY_URLS[$type] ?? '';
    }

    /**
     * Get display name for the registry type
     */
    public function getTypeNameAttribute(): string
    {
        return match ($this->registry_type) {
            self::TYPE_DOCKER_HUB => 'Docker Hub',
            self::TYPE_GITHUB => 'GitHub Container Registry',
            self::TYPE_GITLAB => 'GitLab Container Registry',
            self::TYPE_AWS_ECR => 'AWS ECR',
            self::TYPE_GOOGLE_GCR => 'Google Container Registry',
            self::TYPE_AZURE_ACR => 'Azure Container Registry',
            default => 'Custom Registry',
        };
    }

    /**
     * Get icon class for the registry type
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->registry_type) {
            self::TYPE_DOCKER_HUB => 'docker',
            self::TYPE_GITHUB => 'github',
            self::TYPE_GITLAB => 'gitlab',
            self::TYPE_AWS_ECR => 'aws',
            self::TYPE_GOOGLE_GCR => 'google-cloud',
            self::TYPE_AZURE_ACR => 'azure',
            default => 'registry',
        };
    }

    /**
     * Check if registry is properly configured
     */
    public function isConfigured(): bool
    {
        if (empty($this->registry_url) || empty($this->username)) {
            return false;
        }

        $credentials = $this->credentials;

        return ! empty($credentials['password']) || ! empty($credentials['token']);
    }

    /**
     * Validate registry credentials format based on type
     */
    public function validateCredentials(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        return match ($this->registry_type) {
            self::TYPE_DOCKER_HUB => $this->validateDockerHubCredentials(),
            self::TYPE_GITHUB => $this->validateGitHubCredentials(),
            self::TYPE_GITLAB => $this->validateGitLabCredentials(),
            self::TYPE_AWS_ECR => $this->validateAwsEcrCredentials(),
            self::TYPE_GOOGLE_GCR => $this->validateGoogleGcrCredentials(),
            self::TYPE_AZURE_ACR => $this->validateAzureAcrCredentials(),
            default => true, // Custom registries just need URL, username, and password
        };
    }

    /**
     * Validate Docker Hub credentials
     */
    protected function validateDockerHubCredentials(): bool
    {
        $credentials = $this->credentials;

        return ! empty($credentials['password']);
    }

    /**
     * Validate GitHub Container Registry credentials
     */
    protected function validateGitHubCredentials(): bool
    {
        $credentials = $this->credentials;

        // GitHub requires a personal access token
        return ! empty($credentials['token']);
    }

    /**
     * Validate GitLab Container Registry credentials
     */
    protected function validateGitLabCredentials(): bool
    {
        $credentials = $this->credentials;

        // GitLab can use password or deploy token
        return ! empty($credentials['password']) || ! empty($credentials['token']);
    }

    /**
     * Validate AWS ECR credentials
     */
    protected function validateAwsEcrCredentials(): bool
    {
        $credentials = $this->credentials;

        // AWS ECR requires access key ID and secret access key
        return ! empty($credentials['aws_access_key_id'])
            && ! empty($credentials['aws_secret_access_key'])
            && ! empty($credentials['region']);
    }

    /**
     * Validate Google Container Registry credentials
     */
    protected function validateGoogleGcrCredentials(): bool
    {
        $credentials = $this->credentials;

        // GCR requires service account JSON
        return ! empty($credentials['service_account_json']);
    }

    /**
     * Validate Azure Container Registry credentials
     */
    protected function validateAzureAcrCredentials(): bool
    {
        $credentials = $this->credentials;

        // Azure ACR can use username/password or service principal
        return (! empty($credentials['password']))
            || (! empty($credentials['client_id']) && ! empty($credentials['client_secret']));
    }

    /**
     * Get all supported registry types
     *
     * @return array<string, string>
     */
    public static function getRegistryTypes(): array
    {
        return [
            self::TYPE_DOCKER_HUB => 'Docker Hub',
            self::TYPE_GITHUB => 'GitHub Container Registry',
            self::TYPE_GITLAB => 'GitLab Container Registry',
            self::TYPE_AWS_ECR => 'AWS ECR',
            self::TYPE_GOOGLE_GCR => 'Google Container Registry',
            self::TYPE_AZURE_ACR => 'Azure Container Registry',
            self::TYPE_CUSTOM => 'Custom Registry',
        ];
    }

    /**
     * Get masked credentials for display
     *
     * @return array<string, mixed>
     */
    public function getMaskedCredentials(): array
    {
        $credentials = $this->credentials;
        $masked = [];

        foreach ($credentials as $key => $value) {
            if (in_array($key, ['password', 'token', 'aws_secret_access_key', 'client_secret'])) {
                $masked[$key] = str_repeat('*', min(strlen((string) $value), 12));
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }
}
