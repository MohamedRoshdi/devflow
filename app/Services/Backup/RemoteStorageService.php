<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Models\StorageConfiguration;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class RemoteStorageService
{
    /**
     * Store file to remote storage
     */
    public function store(string $localPath, StorageConfiguration $config, string $remotePath): bool
    {
        try {
            if (!file_exists($localPath)) {
                throw new \RuntimeException("Local file not found: {$localPath}");
            }

            // Optionally encrypt file before upload
            $uploadPath = $localPath;
            if (!empty($config->encryption_key)) {
                $uploadPath = $this->encryptFile($localPath, $config->encryption_key);
            }

            // Get disk instance
            $disk = $this->getDiskFromConfig($config);

            // Add path prefix if configured
            if (!empty($config->path_prefix)) {
                $remotePath = trim($config->path_prefix, '/') . '/' . ltrim($remotePath, '/');
            }

            // Stream upload to remote
            $stream = fopen($uploadPath, 'r');
            if ($stream === false) {
                throw new \RuntimeException("Failed to open file for reading: {$uploadPath}");
            }

            $result = $disk->put($remotePath, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            // Cleanup encrypted temp file
            if ($uploadPath !== $localPath && file_exists($uploadPath)) {
                unlink($uploadPath);
            }

            // Verify upload (checksum)
            if ($result && $disk->exists($remotePath)) {
                $remoteSize = $disk->size($remotePath);
                $localSize = filesize($localPath);

                if ($remoteSize !== $localSize && empty($config->encryption_key)) {
                    throw new \RuntimeException("File size mismatch after upload");
                }

                Log::info("File stored successfully", [
                    'config' => $config->name,
                    'remote_path' => $remotePath,
                    'size' => $remoteSize,
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to store file to remote storage", [
                'config' => $config->name,
                'error' => $e->getMessage(),
                'local_path' => $localPath,
                'remote_path' => $remotePath,
            ]);

            throw $e;
        }
    }

    /**
     * Retrieve file from remote storage
     */
    public function retrieve(StorageConfiguration $config, string $remotePath, string $localPath): bool
    {
        try {
            // Get disk instance
            $disk = $this->getDiskFromConfig($config);

            // Add path prefix if configured
            if (!empty($config->path_prefix)) {
                $remotePath = trim($config->path_prefix, '/') . '/' . ltrim($remotePath, '/');
            }

            if (!$disk->exists($remotePath)) {
                throw new \RuntimeException("Remote file not found: {$remotePath}");
            }

            // Stream download from remote
            $stream = $disk->readStream($remotePath);
            if ($stream === false) {
                throw new \RuntimeException("Failed to read remote file: {$remotePath}");
            }

            // Ensure local directory exists
            $localDir = dirname($localPath);
            if (!is_dir($localDir)) {
                mkdir($localDir, 0755, true);
            }

            // Write to local file
            $downloadPath = $localPath;
            if (!empty($config->encryption_key)) {
                $downloadPath = $localPath . '.encrypted';
            }

            $localStream = fopen($downloadPath, 'w');
            if ($localStream === false) {
                throw new \RuntimeException("Failed to open local file for writing: {$downloadPath}");
            }

            stream_copy_to_stream($stream, $localStream);

            fclose($localStream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            // Decrypt if encrypted
            if (!empty($config->encryption_key)) {
                $decryptedPath = $this->decryptFile($downloadPath, $config->encryption_key);
                rename($decryptedPath, $localPath);
                if (file_exists($downloadPath)) {
                    unlink($downloadPath);
                }
            }

            // Verify download
            if (file_exists($localPath)) {
                Log::info("File retrieved successfully", [
                    'config' => $config->name,
                    'remote_path' => $remotePath,
                    'local_path' => $localPath,
                    'size' => filesize($localPath),
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to retrieve file from remote storage", [
                'config' => $config->name,
                'error' => $e->getMessage(),
                'remote_path' => $remotePath,
                'local_path' => $localPath,
            ]);

            throw $e;
        }
    }

    /**
     * Delete file from remote storage
     */
    public function delete(StorageConfiguration $config, string $remotePath): bool
    {
        try {
            // Get disk instance
            $disk = $this->getDiskFromConfig($config);

            // Add path prefix if configured
            if (!empty($config->path_prefix)) {
                $remotePath = trim($config->path_prefix, '/') . '/' . ltrim($remotePath, '/');
            }

            if (!$disk->exists($remotePath)) {
                return true; // Already deleted
            }

            $result = $disk->delete($remotePath);

            Log::info("File deleted from remote storage", [
                'config' => $config->name,
                'remote_path' => $remotePath,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to delete file from remote storage", [
                'config' => $config->name,
                'error' => $e->getMessage(),
                'remote_path' => $remotePath,
            ]);

            throw $e;
        }
    }

    /**
     * Test connection to remote storage
     */
    public function testConnection(StorageConfiguration $config): array
    {
        $results = [
            'success' => false,
            'tests' => [],
            'timing' => [],
            'error' => null,
        ];

        $testFileName = 'devflow_test_' . time() . '.txt';
        $testContent = 'DevFlow Pro Storage Test - ' . now()->toIso8601String();

        try {
            $disk = $this->getDiskFromConfig($config);
            $testPath = ($config->path_prefix ? trim($config->path_prefix, '/') . '/' : '') . $testFileName;

            // Test 1: List files
            $start = microtime(true);
            try {
                $files = $disk->files($config->path_prefix ?: '/');
                $results['tests']['list'] = true;
                $results['timing']['list'] = round((microtime(true) - $start) * 1000, 2) . 'ms';
            } catch (\Exception $e) {
                $results['tests']['list'] = false;
                $results['error'] = "List files failed: " . $e->getMessage();
                return $results;
            }

            // Test 2: Write test file
            $start = microtime(true);
            try {
                $disk->put($testPath, $testContent);
                $results['tests']['write'] = true;
                $results['timing']['write'] = round((microtime(true) - $start) * 1000, 2) . 'ms';
            } catch (\Exception $e) {
                $results['tests']['write'] = false;
                $results['error'] = "Write test failed: " . $e->getMessage();
                return $results;
            }

            // Test 3: Read test file
            $start = microtime(true);
            try {
                $content = $disk->get($testPath);
                $results['tests']['read'] = ($content === $testContent);
                $results['timing']['read'] = round((microtime(true) - $start) * 1000, 2) . 'ms';

                if ($content !== $testContent) {
                    $results['error'] = "Read test failed: Content mismatch";
                    return $results;
                }
            } catch (\Exception $e) {
                $results['tests']['read'] = false;
                $results['error'] = "Read test failed: " . $e->getMessage();
                return $results;
            }

            // Test 4: Delete test file
            $start = microtime(true);
            try {
                $disk->delete($testPath);
                $results['tests']['delete'] = !$disk->exists($testPath);
                $results['timing']['delete'] = round((microtime(true) - $start) * 1000, 2) . 'ms';

                if ($disk->exists($testPath)) {
                    $results['error'] = "Delete test failed: File still exists";
                    return $results;
                }
            } catch (\Exception $e) {
                $results['tests']['delete'] = false;
                $results['error'] = "Delete test failed: " . $e->getMessage();
                return $results;
            }

            $results['success'] = true;

            // Update last tested timestamp
            $config->update(['last_tested_at' => now()]);
        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
            Log::error("Storage connection test failed", [
                'config' => $config->name,
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * List backup files in storage
     */
    public function listBackups(StorageConfiguration $config, string $prefix = ''): array
    {
        try {
            $disk = $this->getDiskFromConfig($config);

            $searchPath = $config->path_prefix ? trim($config->path_prefix, '/') . '/' : '';
            if (!empty($prefix)) {
                $searchPath .= ltrim($prefix, '/');
            }

            $files = $disk->files($searchPath);

            return collect($files)->map(function ($file) use ($disk) {
                return [
                    'path' => $file,
                    'name' => basename($file),
                    'size' => $disk->size($file),
                    'last_modified' => $disk->lastModified($file),
                ];
            })->sortByDesc('last_modified')->values()->toArray();
        } catch (\Exception $e) {
            Log::error("Failed to list backups", [
                'config' => $config->name,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Create Laravel disk instance from StorageConfiguration
     */
    public function getDiskFromConfig(StorageConfiguration $config): Filesystem
    {
        $diskConfig = $config->getDiskConfig();

        // Register temporary disk
        config(['filesystems.disks.temp_' . $config->id => $diskConfig]);

        return Storage::disk('temp_' . $config->id);
    }

    /**
     * Encrypt file using AES-256-GCM
     */
    public function encryptFile(string $path, string $key): string
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $data = file_get_contents($path);
        if ($data === false) {
            throw new \RuntimeException("Failed to read file: {$path}");
        }

        // Generate initialization vector
        $iv = random_bytes(16);

        // Encrypt data
        $encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($encrypted === false) {
            throw new \RuntimeException("Encryption failed");
        }

        // Combine IV + tag + encrypted data
        $encryptedData = $iv . $tag . $encrypted;

        // Write to temporary encrypted file
        $encryptedPath = $path . '.encrypted';
        if (file_put_contents($encryptedPath, $encryptedData) === false) {
            throw new \RuntimeException("Failed to write encrypted file");
        }

        return $encryptedPath;
    }

    /**
     * Decrypt file using AES-256-GCM
     */
    public function decryptFile(string $path, string $key): string
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Encrypted file not found: {$path}");
        }

        $encryptedData = file_get_contents($path);
        if ($encryptedData === false) {
            throw new \RuntimeException("Failed to read encrypted file: {$path}");
        }

        // Extract IV, tag, and encrypted data
        $iv = substr($encryptedData, 0, 16);
        $tag = substr($encryptedData, 16, 16);
        $encrypted = substr($encryptedData, 32);

        // Decrypt data
        $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($decrypted === false) {
            throw new \RuntimeException("Decryption failed");
        }

        // Write to temporary decrypted file
        $decryptedPath = str_replace('.encrypted', '.decrypted', $path);
        if (file_put_contents($decryptedPath, $decrypted) === false) {
            throw new \RuntimeException("Failed to write decrypted file");
        }

        return $decryptedPath;
    }
}
