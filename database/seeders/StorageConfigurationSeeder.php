<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StorageConfiguration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class StorageConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        // Default local storage
        StorageConfiguration::create([
            'name' => 'Local Storage',
            'driver' => 'local',
            'is_default' => true,
            'credentials' => Crypt::encryptString(json_encode([])),
            'status' => 'active',
            'last_tested_at' => now(),
        ]);

        // Example S3 configuration (disabled by default)
        StorageConfiguration::create([
            'name' => 'AWS S3 Production',
            'driver' => 's3',
            'is_default' => false,
            'credentials' => Crypt::encryptString(json_encode([
                'access_key_id' => 'YOUR_AWS_ACCESS_KEY_ID',
                'secret_access_key' => 'YOUR_AWS_SECRET_ACCESS_KEY',
            ])),
            'bucket' => 'your-backup-bucket',
            'region' => 'us-east-1',
            'path_prefix' => 'devflow/backups',
            'status' => 'disabled',
        ]);

        // Example DigitalOcean Spaces configuration (S3-compatible)
        StorageConfiguration::create([
            'name' => 'DigitalOcean Spaces',
            'driver' => 's3',
            'is_default' => false,
            'credentials' => Crypt::encryptString(json_encode([
                'access_key_id' => 'YOUR_SPACES_ACCESS_KEY',
                'secret_access_key' => 'YOUR_SPACES_SECRET_KEY',
            ])),
            'bucket' => 'your-space-name',
            'region' => 'nyc3',
            'endpoint' => 'https://nyc3.digitaloceanspaces.com',
            'path_prefix' => 'backups',
            'status' => 'disabled',
        ]);

        // Example Google Cloud Storage configuration (disabled by default)
        StorageConfiguration::create([
            'name' => 'Google Cloud Storage',
            'driver' => 'gcs',
            'is_default' => false,
            'credentials' => Crypt::encryptString(json_encode([
                'service_account_json' => [
                    'type' => 'service_account',
                    'project_id' => 'your-project-id',
                    'private_key_id' => 'YOUR_PRIVATE_KEY_ID',
                    'private_key' => '-----BEGIN PRIVATE KEY-----\nYOUR_PRIVATE_KEY\n-----END PRIVATE KEY-----',
                    'client_email' => 'your-service-account@your-project.iam.gserviceaccount.com',
                    'client_id' => 'YOUR_CLIENT_ID',
                ],
            ])),
            'bucket' => 'your-gcs-bucket',
            'path_prefix' => 'devflow/backups',
            'status' => 'disabled',
        ]);
    }
}
