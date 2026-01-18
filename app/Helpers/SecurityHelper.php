<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Security Helper
 *
 * Provides secure input validation and sanitization methods for shell commands
 * and file operations to prevent command injection and path traversal attacks.
 */
class SecurityHelper
{
    /**
     * Validate and sanitize a slug for use in shell commands
     *
     * Project slugs must match the pattern: ^[a-z0-9-]+$
     * This prevents command injection via special shell characters
     *
     * @param string $slug The slug to validate
     * @return string The validated slug
     * @throws \InvalidArgumentException if slug contains invalid characters
     */
    public static function sanitizeSlug(string $slug): string
    {
        // Only allow lowercase alphanumeric characters and hyphens
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new \InvalidArgumentException('Invalid slug format. Only lowercase letters, numbers, and hyphens are allowed.');
        }

        // Additional safety: prevent path traversal attempts
        if (str_contains($slug, '..')) {
            throw new \InvalidArgumentException('Slug contains invalid path traversal characters.');
        }

        return $slug;
    }

    /**
     * Validate branch name
     *
     * Git branch names can contain alphanumeric, hyphens, underscores, dots, and slashes
     * This prevents command injection via branch names
     *
     * @param string $branch The branch name to validate
     * @return string The validated branch name
     * @throws \InvalidArgumentException if branch name contains invalid characters
     */
    public static function sanitizeBranchName(string $branch): string
    {
        // Git branch name validation - allow alphanumeric, hyphens, underscores, dots, slashes
        if (!preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $branch)) {
            throw new \InvalidArgumentException('Invalid branch name format.');
        }

        // Prevent certain dangerous patterns
        if (str_starts_with($branch, '-') || str_contains($branch, '..')) {
            throw new \InvalidArgumentException('Branch name contains invalid pattern.');
        }

        return $branch;
    }

    /**
     * Sanitize file path to prevent path traversal attacks
     *
     * Removes path traversal sequences and validates the path
     *
     * @param string $path The path to sanitize
     * @return string The sanitized path
     * @throws \InvalidArgumentException if path is invalid
     */
    public static function sanitizePath(string $path): string
    {
        // Prevent path traversal
        $path = str_replace(['..', "\0"], '', $path);

        // Resolve to real path if it exists
        $realPath = realpath($path);
        if ($realPath === false) {
            throw new \InvalidArgumentException('Invalid path: path does not exist or is not accessible.');
        }

        return $realPath;
    }

    /**
     * Validate repository URL
     *
     * Ensures repository URL is a valid Git URL
     *
     * @param string $url The repository URL to validate
     * @return string The validated URL
     * @throws \InvalidArgumentException if URL is invalid
     */
    public static function sanitizeRepositoryUrl(string $url): string
    {
        // Allow common Git URL patterns: https, http, git, ssh
        $validPatterns = [
            '/^https?:\/\/.+\.git$/',           // HTTPS URLs
            '/^git@.+:.+\.git$/',                // SSH URLs (git@github.com:user/repo.git)
            '/^ssh:\/\/.+\.git$/',               // SSH protocol URLs
        ];

        foreach ($validPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return $url;
            }
        }

        throw new \InvalidArgumentException('Invalid repository URL format.');
    }

    /**
     * Validate commit hash
     *
     * Git commit hashes are 40-character hexadecimal strings
     *
     * @param string $hash The commit hash to validate
     * @return string The validated commit hash
     * @throws \InvalidArgumentException if hash is invalid
     */
    public static function sanitizeCommitHash(string $hash): string
    {
        // Git commit hashes are 40 hex characters (or 7+ for short hashes)
        if (!preg_match('/^[a-f0-9]{7,40}$/i', $hash)) {
            throw new \InvalidArgumentException('Invalid commit hash format.');
        }

        return strtolower($hash);
    }

    /**
     * Escape shell argument for safe execution
     *
     * This is a wrapper around escapeshellarg with additional validation
     *
     * @param string $arg The argument to escape
     * @return string The escaped argument
     */
    public static function escapeShellArg(string $arg): string
    {
        // Remove null bytes which can cause issues
        $arg = str_replace("\0", '', $arg);

        return escapeshellarg($arg);
    }

    /**
     * Validate IP address
     *
     * @param string $ip The IP address to validate
     * @return string The validated IP address
     * @throws \InvalidArgumentException if IP is invalid
     */
    public static function sanitizeIpAddress(string $ip): string
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address format.');
        }

        return $ip;
    }

    /**
     * Validate port number
     *
     * @param int $port The port number to validate
     * @return int The validated port number
     * @throws \InvalidArgumentException if port is invalid
     */
    public static function sanitizePort(int $port): int
    {
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException('Invalid port number. Must be between 1 and 65535.');
        }

        return $port;
    }

    /**
     * Validate Docker container/image name
     *
     * Docker names must match: ^[a-zA-Z0-9][a-zA-Z0-9_.-]*$
     *
     * @param string $name The Docker name to validate
     * @return string The validated name
     * @throws \InvalidArgumentException if name is invalid
     */
    public static function sanitizeDockerName(string $name): string
    {
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]*$/', $name)) {
            throw new \InvalidArgumentException('Invalid Docker name format.');
        }

        return $name;
    }
}
