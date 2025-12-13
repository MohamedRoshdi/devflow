<?php

declare(strict_types=1);

namespace App\Rules;

/**
 * Centralized file upload validation rules
 *
 * Provides secure file upload validation with:
 * - File type restrictions (MIME types)
 * - File size limits
 * - Extension validation
 * - Protection against malicious uploads
 *
 * @package App\Rules
 */
class FileUploadRule
{
    /**
     * Get validation rules for image uploads
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $maxSizeKB  Maximum file size in kilobytes (default: 2048 = 2MB)
     * @param  array<int, string>  $allowedMimes  Allowed MIME types
     * @return array<int, string>  Validation rules
     */
    public static function imageRules(
        bool $required = false,
        int $maxSizeKB = 2048,
        array $allowedMimes = ['jpeg', 'jpg', 'png', 'gif', 'webp']
    ): array {
        $rules = $required ? ['required'] : ['nullable'];

        $rules[] = 'image';
        $rules[] = "max:{$maxSizeKB}";
        $rules[] = 'mimes:'.implode(',', $allowedMimes);

        // Prevent double extension attacks
        $rules[] = 'regex:/^[^.]+\.(jpeg|jpg|png|gif|webp)$/i';

        return $rules;
    }

    /**
     * Get validation rules for document uploads
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $maxSizeKB  Maximum file size in kilobytes (default: 10240 = 10MB)
     * @param  array<int, string>  $allowedMimes  Allowed MIME types
     * @return array<int, string>  Validation rules
     */
    public static function documentRules(
        bool $required = false,
        int $maxSizeKB = 10240,
        array $allowedMimes = ['pdf', 'doc', 'docx', 'txt', 'md']
    ): array {
        $rules = $required ? ['required'] : ['nullable'];

        $rules[] = 'file';
        $rules[] = "max:{$maxSizeKB}";
        $rules[] = 'mimes:'.implode(',', $allowedMimes);

        // Prevent executable and script files
        $rules[] = 'regex:/^(?!.*\.(php|exe|sh|bat|cmd|com|pif|scr|vbs|js|jar|app|deb|rpm)).*$/i';

        return $rules;
    }

    /**
     * Get validation rules for archive uploads (zip, tar, etc.)
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $maxSizeKB  Maximum file size in kilobytes (default: 51200 = 50MB)
     * @param  array<int, string>  $allowedMimes  Allowed MIME types
     * @return array<int, string>  Validation rules
     */
    public static function archiveRules(
        bool $required = false,
        int $maxSizeKB = 51200,
        array $allowedMimes = ['zip', 'tar', 'gz', 'bz2', 'rar']
    ): array {
        $rules = $required ? ['required'] : ['nullable'];

        $rules[] = 'file';
        $rules[] = "max:{$maxSizeKB}";
        $rules[] = 'mimes:'.implode(',', $allowedMimes);

        return $rules;
    }

    /**
     * Get validation rules for SSH key uploads
     *
     * @param  bool  $required  Whether the field is required
     * @return array<int, string>  Validation rules
     */
    public static function sshKeyRules(bool $required = false): array
    {
        $rules = $required ? ['required'] : ['nullable'];

        $rules[] = 'string';
        $rules[] = 'max:65535'; // Max text size

        // Must not contain null bytes
        $rules[] = 'regex:/^[^\x00]+$/';

        // Must not contain suspicious patterns
        $rules[] = 'not_regex:/(`|;|\||&|\$\(|\{|\}|<|>)/';

        return $rules;
    }

    /**
     * Get validation rules for configuration file content (env, yaml, json, etc.)
     *
     * @param  bool  $required  Whether the field is required
     * @return array<int, string>  Validation rules
     */
    public static function configContentRules(bool $required = false): array
    {
        $rules = $required ? ['required'] : ['nullable'];

        $rules[] = 'string';
        $rules[] = 'max:524288'; // 512KB text limit

        // Must not contain null bytes
        $rules[] = 'regex:/^[^\x00]+$/';

        // Must not contain command injection patterns (backticks, command substitution)
        $rules[] = 'not_regex:/(`|\$\()/';

        return $rules;
    }

    /**
     * Get validation rules for avatar/profile image uploads
     *
     * @param  bool  $required  Whether the field is required
     * @return array<int, string>  Validation rules
     */
    public static function avatarRules(bool $required = false): array
    {
        return self::imageRules(
            required: $required,
            maxSizeKB: 2048, // 2MB
            allowedMimes: ['jpeg', 'jpg', 'png', 'gif', 'webp']
        );
    }

    /**
     * Get validation rules for logo uploads
     *
     * @param  bool  $required  Whether the field is required
     * @return array<int, string>  Validation rules
     */
    public static function logoRules(bool $required = false): array
    {
        return self::imageRules(
            required: $required,
            maxSizeKB: 5120, // 5MB
            allowedMimes: ['jpeg', 'jpg', 'png', 'svg', 'webp']
        );
    }

    /**
     * Get blacklisted file extensions (never allow these)
     *
     * @return array<int, string>  Array of dangerous extensions
     */
    public static function getBlacklistedExtensions(): array
    {
        return [
            // Executables
            'exe', 'com', 'bat', 'cmd', 'msi', 'app', 'deb', 'rpm',
            // Scripts
            'php', 'php3', 'php4', 'php5', 'phtml', 'phar',
            'py', 'pl', 'rb', 'sh', 'bash', 'zsh',
            'js', 'vbs', 'vbe', 'jar',
            // System files
            'dll', 'sys', 'drv',
            // Shortcuts
            'lnk', 'scr', 'pif',
        ];
    }

    /**
     * Sanitize filename for safe storage
     * Removes path traversal, special characters, and normalizes the filename
     *
     * @param  string  $filename  Original filename
     * @return string  Sanitized filename
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove path components
        $filename = basename($filename);

        // Remove null bytes
        $filename = str_replace("\0", '', $filename);

        // Get extension and basename
        $extension = (string) pathinfo($filename, PATHINFO_EXTENSION);
        $basename = (string) pathinfo($filename, PATHINFO_FILENAME);

        // Default to safe values if empty
        if ($extension === '') {
            $extension = 'txt';
        }
        if ($basename === '') {
            $basename = 'file';
        }

        // Check against blacklist
        if (in_array(strtolower($extension), self::getBlacklistedExtensions(), true)) {
            $extension = 'txt'; // Replace dangerous extension
        }

        // Sanitize basename (remove special chars, keep alphanumeric, dash, underscore)
        $sanitized = (string) preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        $sanitized = (string) preg_replace('/_+/', '_', $sanitized); // Remove multiple underscores
        $basename = trim($sanitized, '_-') ?: 'file';

        // Generate unique suffix if needed
        $uniqueId = substr(md5(uniqid((string) mt_rand(), true)), 0, 8);

        return $basename.'_'.$uniqueId.($extension ? '.'.$extension : '');
    }

    /**
     * Validate file extension against allowed list
     *
     * @param  string  $filename  The filename to check
     * @param  array<int, string>  $allowedExtensions  Array of allowed extensions
     * @return bool  True if extension is allowed
     */
    public static function isAllowedExtension(string $filename, array $allowedExtensions): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, array_map('strtolower', $allowedExtensions), true);
    }

    /**
     * Check if filename contains suspicious patterns
     *
     * @param  string  $filename  The filename to check
     * @return bool  True if filename is suspicious
     */
    public static function isSuspiciousFilename(string $filename): bool
    {
        // Check for path traversal
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return true;
        }

        // Check for null bytes
        if (str_contains($filename, "\0")) {
            return true;
        }

        // Check for multiple extensions (e.g., image.jpg.php)
        $parts = explode('.', $filename);
        if (count($parts) > 2) {
            // Check if any part before the last is a blacklisted extension
            array_pop($parts); // Remove last extension
            foreach ($parts as $part) {
                if (in_array(strtolower($part), self::getBlacklistedExtensions(), true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get custom validation messages for file uploads
     *
     * @return array<string, string>  Validation messages
     */
    public static function messages(): array
    {
        return [
            'image' => 'The :attribute must be a valid image file.',
            'file' => 'The :attribute must be a valid file.',
            'mimes' => 'The :attribute must be a file of type: :values.',
            'max' => 'The :attribute size must not exceed :max KB.',
            'regex' => 'The :attribute has an invalid filename format.',
            'not_regex' => 'The :attribute contains invalid characters.',
        ];
    }

    /**
     * Get custom attribute names for file uploads
     *
     * @return array<string, string>  Attribute names
     */
    public static function attributes(): array
    {
        return [
            'avatar' => 'avatar image',
            'logo' => 'logo image',
            'file' => 'uploaded file',
            'document' => 'document',
            'archive' => 'archive file',
        ];
    }
}
