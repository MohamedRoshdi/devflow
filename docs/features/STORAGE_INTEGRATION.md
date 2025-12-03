# Remote Storage Integration - DevFlow Pro

## Overview

DevFlow Pro now supports remote storage integration for backups and file management. This feature allows you to store backups to various cloud storage providers including Amazon S3, Google Cloud Storage, FTP, and SFTP servers.

## Features

- **Multiple Storage Providers**: S3, Google Cloud Storage, FTP, SFTP, and Local
- **Encrypted Credentials**: All storage credentials are encrypted using Laravel's encryption
- **At-Rest Encryption**: Optional AES-256-GCM encryption for backup files
- **Connection Testing**: Built-in connection testing before saving configurations
- **Project-Specific or Global**: Configure storage per project or globally
- **Default Storage**: Set a default storage configuration for all backups
- **DigitalOcean Spaces Support**: Full support for S3-compatible storage like DigitalOcean Spaces, MinIO, etc.

## Installation

### 1. Run Migration

```bash
php artisan migrate
```

This will create the `storage_configurations` table.

### 2. Install Storage Driver Packages

The required packages are already included in `composer.json`:

```bash
composer install
```

Packages installed:
- `league/flysystem-aws-s3-v3` - Amazon S3 support
- `google/cloud-storage` - Google Cloud Storage support
- `league/flysystem-ftp` - FTP support
- `league/flysystem-sftp-v3` - SFTP support

### 3. Seed Example Configurations (Optional)

```bash
php artisan db:seed --class=StorageConfigurationSeeder
```

## Usage

### Access Storage Settings

Navigate to: `/settings/storage` or click "Storage Settings" in the settings menu.

### Adding Storage Configuration

#### Amazon S3

1. Click "Add Storage"
2. Enter configuration name (e.g., "AWS S3 Production")
3. Select the "Amazon S3" tab
4. Enter:
   - **Access Key ID**: Your AWS access key
   - **Secret Access Key**: Your AWS secret key
   - **Bucket Name**: The S3 bucket name
   - **Region**: AWS region (e.g., us-east-1)
   - **Path Prefix**: Optional subdirectory path
5. Click "Test Connection" to verify
6. Click "Create Configuration"

#### DigitalOcean Spaces

1. Follow the same steps as S3
2. In the "Custom Endpoint" field, enter: `https://nyc3.digitaloceanspaces.com` (or your region)
3. Use your Spaces access key and secret key
4. The region should match your Spaces region (e.g., nyc3, sfo2, sgp1)

#### Google Cloud Storage

1. Click "Add Storage"
2. Enter configuration name
3. Select the "Google Cloud" tab
4. Paste your service account JSON:
   ```json
   {
     "type": "service_account",
     "project_id": "your-project",
     "private_key_id": "...",
     "private_key": "-----BEGIN PRIVATE KEY-----\n...",
     "client_email": "...",
     "client_id": "..."
   }
   ```
5. Enter bucket name
6. Click "Test Connection" to verify
7. Click "Create Configuration"

#### FTP

1. Click "Add Storage"
2. Select the "FTP" tab
3. Enter:
   - **Host**: FTP server hostname
   - **Port**: Usually 21
   - **Username**: FTP username
   - **Password**: FTP password
   - **Path**: Base directory path
   - **Passive Mode**: Enable for most servers
   - **Use SSL/TLS**: Enable if supported
4. Click "Test Connection"
5. Click "Create Configuration"

#### SFTP

1. Click "Add Storage"
2. Select the "SFTP" tab
3. Enter:
   - **Host**: SFTP server hostname
   - **Port**: Usually 22
   - **Username**: SFTP username
   - **Password**: Password OR Private Key (choose one)
   - **Private Key**: SSH private key (if not using password)
   - **Passphrase**: If private key is encrypted
   - **Path**: Base directory path
4. Click "Test Connection"
5. Click "Create Configuration"

### Encryption Options

For enhanced security, you can enable at-rest encryption:

1. Check "Enable at-rest encryption (AES-256-GCM)"
2. Click "Generate Key" to create a secure encryption key
3. **Important**: Save this key securely - you'll need it to decrypt files

## Using Storage in Backup Services

### Automatic Integration

Once configured, the default storage will be automatically used for all backups. You can also specify a storage configuration when creating backups.

### Manual Usage

```php
use App\Models\StorageConfiguration;
use App\Services\Backup\RemoteStorageService;

$config = StorageConfiguration::default()->first();
$storageService = app(RemoteStorageService::class);

// Store a backup
$storageService->store(
    localPath: '/path/to/backup.sql',
    config: $config,
    remotePath: 'backups/database/backup-2025-12-03.sql'
);

// Retrieve a backup
$storageService->retrieve(
    config: $config,
    remotePath: 'backups/database/backup-2025-12-03.sql',
    localPath: '/path/to/restore/backup.sql'
);

// Delete a backup
$storageService->delete(
    config: $config,
    remotePath: 'backups/database/backup-2025-12-03.sql'
);

// List all backups
$backups = $storageService->listBackups($config, 'backups/database');
```

## Testing Connections

The "Test Connection" button performs the following checks:

1. **List Files**: Verifies it can list files in the storage
2. **Write Test**: Creates a test file
3. **Read Test**: Reads the test file back
4. **Delete Test**: Deletes the test file

All tests must pass for the configuration to be considered valid.

## Setting Default Storage

Click "Set Default" on any storage configuration to make it the default for all backups. Only one storage can be set as default at a time.

## Security Considerations

### Credential Encryption

All storage credentials are encrypted using Laravel's encryption before being stored in the database. The encryption key is stored in your `.env` file:

```env
APP_KEY=base64:your-encryption-key-here
```

**Important**: Never commit your `.env` file to version control.

### At-Rest Encryption

When enabling at-rest encryption:
- Files are encrypted using AES-256-GCM before upload
- The encryption key is stored encrypted in the database
- **You must save the encryption key separately** - losing it means you cannot decrypt your backups

### IAM Best Practices

For S3/GCS:
- Use IAM roles with minimal required permissions
- Only grant read/write access to specific buckets
- Enable bucket versioning for backup recovery
- Use bucket policies to restrict access

Example S3 IAM Policy:
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject",
        "s3:ListBucket"
      ],
      "Resource": [
        "arn:aws:s3:::your-backup-bucket/*",
        "arn:aws:s3:::your-backup-bucket"
      ]
    }
  ]
}
```

## Troubleshooting

### Connection Test Fails

1. **S3/DigitalOcean Spaces**:
   - Verify access key and secret key
   - Check bucket name is correct
   - Ensure region matches bucket region
   - For DigitalOcean Spaces, verify custom endpoint is correct
   - Check IAM permissions

2. **Google Cloud Storage**:
   - Verify service account JSON is valid
   - Ensure service account has Storage Object Admin role
   - Check bucket name is correct

3. **FTP/SFTP**:
   - Verify hostname is reachable
   - Check username and password
   - For SFTP with private key, ensure key format is correct
   - Check firewall rules allow connections

### Slow Uploads

- For large files, ensure your PHP settings allow:
  ```ini
  max_execution_time = 300
  memory_limit = 512M
  upload_max_filesize = 100M
  post_max_size = 100M
  ```

### Permission Errors

- For FTP/SFTP, ensure the user has write permissions to the specified path
- For S3/GCS, verify IAM permissions include PutObject

## Database Schema

```sql
CREATE TABLE storage_configurations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    driver ENUM('local', 's3', 'gcs', 'ftp', 'sftp'),
    is_default BOOLEAN DEFAULT FALSE,
    credentials TEXT,  -- Encrypted JSON
    bucket VARCHAR(255) NULL,
    region VARCHAR(255) NULL,
    endpoint VARCHAR(500) NULL,
    path_prefix VARCHAR(500) NULL,
    encryption_key VARCHAR(255) NULL,
    status ENUM('active', 'testing', 'disabled'),
    last_tested_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## API Reference

### RemoteStorageService Methods

#### `store(string $localPath, StorageConfiguration $config, string $remotePath): bool`

Stores a local file to remote storage.

**Parameters:**
- `$localPath`: Full path to local file
- `$config`: StorageConfiguration instance
- `$remotePath`: Destination path in remote storage

**Returns:** `bool` - Success status

---

#### `retrieve(StorageConfiguration $config, string $remotePath, string $localPath): bool`

Downloads a file from remote storage.

**Parameters:**
- `$config`: StorageConfiguration instance
- `$remotePath`: Path to file in remote storage
- `$localPath`: Where to save the file locally

**Returns:** `bool` - Success status

---

#### `delete(StorageConfiguration $config, string $remotePath): bool`

Deletes a file from remote storage.

**Parameters:**
- `$config`: StorageConfiguration instance
- `$remotePath`: Path to file in remote storage

**Returns:** `bool` - Success status

---

#### `testConnection(StorageConfiguration $config): array`

Tests storage connection and returns detailed results.

**Returns:**
```php
[
    'success' => true|false,
    'tests' => [
        'list' => true|false,
        'write' => true|false,
        'read' => true|false,
        'delete' => true|false,
    ],
    'timing' => [
        'list' => '50.25ms',
        'write' => '125.50ms',
        'read' => '75.10ms',
        'delete' => '45.30ms',
    ],
    'error' => 'Error message if any'
]
```

---

#### `listBackups(StorageConfiguration $config, string $prefix = ''): array`

Lists all backup files in storage.

**Returns:**
```php
[
    [
        'path' => 'backups/db-2025-12-03.sql',
        'name' => 'db-2025-12-03.sql',
        'size' => 1048576,
        'last_modified' => 1733184000,
    ],
    // ...
]
```

## Support

For issues or questions:
- GitHub Issues: [devflow-pro/issues](https://github.com/your-repo/devflow-pro/issues)
- Documentation: [docs.devflow.com](https://docs.devflow.com)

## License

MIT License - See LICENSE file for details
