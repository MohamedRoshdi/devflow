<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\StorageConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

class StorageConfigurationFactory extends Factory
{
    protected $model = StorageConfiguration::class;

    public function definition(): array
    {
        $driver = $this->faker->randomElement(['s3', 'gcs', 'ftp', 'sftp', 'local']);

        return [
            'project_id' => null,
            'name' => $this->faker->words(2, true).' Storage',
            'driver' => $driver,
            'is_default' => false,
            'credentials' => $this->getCredentialsForDriver($driver),
            'bucket' => in_array($driver, ['s3', 'gcs']) ? $this->faker->slug : null,
            'region' => $driver === 's3' ? $this->faker->randomElement(['us-east-1', 'us-west-2', 'eu-west-1']) : null,
            'endpoint' => null,
            'path_prefix' => 'backups/'.$this->faker->slug,
            'encryption_key' => null,
            'status' => 'active',
            'last_tested_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Get credentials for a specific driver as array.
     * The model's setCredentialsAttribute will handle encryption.
     *
     * @return array<string, mixed>
     */
    private function getCredentialsForDriver(string $driver): array
    {
        return match ($driver) {
            's3' => [
                'access_key_id' => 'AKIA'.strtoupper($this->faker->bothify('???????????????')),
                'secret_access_key' => $this->faker->bothify('****************************************'),
            ],
            'gcs' => [
                'service_account_json' => [
                    'type' => 'service_account',
                    'project_id' => $this->faker->slug,
                    'private_key_id' => $this->faker->uuid,
                    'private_key' => '-----BEGIN PRIVATE KEY-----\nMOCK_KEY\n-----END PRIVATE KEY-----',
                    'client_email' => $this->faker->email,
                    'client_id' => $this->faker->numerify('###############'),
                ],
            ],
            'ftp' => [
                'host' => $this->faker->domainName,
                'port' => 21,
                'username' => $this->faker->userName,
                'password' => $this->faker->password,
                'path' => '/backups',
                'passive' => true,
                'ssl' => false,
            ],
            'sftp' => [
                'host' => $this->faker->domainName,
                'port' => 22,
                'username' => $this->faker->userName,
                'password' => $this->faker->password,
                'private_key' => null,
                'passphrase' => null,
                'path' => '/backups',
            ],
            default => [],
        };
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function s3(): static
    {
        return $this->state(fn (array $attributes) => [
            'driver' => 's3',
            'credentials' => $this->getCredentialsForDriver('s3'),
            'bucket' => 'devflow-backups-'.$this->faker->slug,
            'region' => 'us-east-1',
        ]);
    }

    public function gcs(): static
    {
        return $this->state(fn (array $attributes) => [
            'driver' => 'gcs',
            'credentials' => $this->getCredentialsForDriver('gcs'),
            'bucket' => 'devflow-backups-'.$this->faker->slug,
            'region' => null,
        ]);
    }

    public function ftp(): static
    {
        return $this->state(fn (array $attributes) => [
            'driver' => 'ftp',
            'credentials' => $this->getCredentialsForDriver('ftp'),
            'bucket' => null,
            'region' => null,
        ]);
    }

    public function sftp(): static
    {
        return $this->state(fn (array $attributes) => [
            'driver' => 'sftp',
            'credentials' => $this->getCredentialsForDriver('sftp'),
            'bucket' => null,
            'region' => null,
        ]);
    }

    public function withEncryption(): static
    {
        return $this->state(fn (array $attributes) => [
            'encryption_key' => base64_encode(random_bytes(32)),
        ]);
    }
}
