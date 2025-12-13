# File Upload Validation - Security Implementation

## Overview

This document details the comprehensive file upload validation security measures implemented across the DevFlow Pro application. All file uploads are now properly validated to prevent security vulnerabilities including:

- Malicious file uploads (PHP, executables, scripts)
- Path traversal attacks
- Double extension attacks
- MIME type spoofing
- File size abuse
- Null byte injection
- Command injection in filenames

## Centralized Validation Rule

### FileUploadRule Class

**Location:** `/app/Rules/FileUploadRule.php`

A centralized validation rule class that provides secure, reusable file upload validation rules.

#### Key Methods

##### 1. `imageRules()` - Image Upload Validation
```php
FileUploadRule::imageRules(
    required: false,
    maxSizeKB: 2048,
    allowedMimes: ['jpeg', 'jpg', 'png', 'gif', 'webp']
)
```

**Validations:**
- File type validation (image)
- Size limit (default: 2MB)
- MIME type whitelist
- Extension validation with regex to prevent double extensions
- Example: Prevents `image.jpg.php` attacks

##### 2. `avatarRules()` - Avatar/Profile Image Validation
```php
FileUploadRule::avatarRules(required: false)
```

**Validations:**
- Pre-configured for avatar uploads
- 2MB size limit
- Allowed formats: JPEG, JPG, PNG, GIF, WebP

##### 3. `documentRules()` - Document Upload Validation
```php
FileUploadRule::documentRules(
    required: false,
    maxSizeKB: 10240,
    allowedMimes: ['pdf', 'doc', 'docx', 'txt', 'md']
)
```

**Validations:**
- Document file validation
- Size limit (default: 10MB)
- Prevents executable files (PHP, EXE, SH, BAT, etc.)
- Regex protection against dangerous extensions

##### 4. `sshKeyRules()` - SSH Key Content Validation
```php
FileUploadRule::sshKeyRules(required: false)
```

**Validations:**
- String content validation (max 65KB)
- Null byte prevention
- Command injection pattern detection
- Prevents backticks, pipes, command substitution

##### 5. `configContentRules()` - Configuration File Content Validation
```php
FileUploadRule::configContentRules(required: false)
```

**Validations:**
- Environment file content validation
- 512KB text limit
- Null byte prevention
- Command injection prevention (backticks, $())

#### Security Helper Methods

##### `sanitizeFilename()` - Secure Filename Sanitization
```php
$safeFilename = FileUploadRule::sanitizeFilename($originalFilename);
```

**Features:**
- Removes path traversal components (`../`, `./`)
- Strips null bytes
- Replaces dangerous extensions with `.txt`
- Sanitizes basename (alphanumeric, dash, underscore only)
- Adds unique 8-character hash suffix
- Output: `clean_filename_a1b2c3d4.ext`

**Blacklisted Extensions:**
- Executables: `exe`, `com`, `bat`, `cmd`, `msi`, `app`, `deb`, `rpm`
- Scripts: `php`, `php3`, `php4`, `php5`, `phtml`, `phar`, `py`, `pl`, `rb`, `sh`, `bash`, `js`, `vbs`, `jar`
- System files: `dll`, `sys`, `drv`
- Shortcuts: `lnk`, `scr`, `pif`

##### `isSuspiciousFilename()` - Filename Threat Detection
```php
if (FileUploadRule::isSuspiciousFilename($filename)) {
    // Reject upload
}
```

**Detects:**
- Path traversal attempts (`../`, `./`)
- Null byte injection (`\0`)
- Double extension attacks (`image.jpg.php`)
- Hidden malicious extensions

## Implementation Across Application

### 1. Team Avatar Uploads

#### Files Updated:
- `/app/Livewire/Teams/TeamList.php`
- `/app/Livewire/Teams/TeamSettings.php`
- `/app/Http/Requests/StoreTeamRequest.php`
- `/app/Http/Requests/UpdateTeamRequest.php`

#### Implementation Details:

**TeamList.php - Team Creation:**
```php
public function createTeam()
{
    // Validate file upload with enhanced rules
    if ($this->avatar) {
        $this->validate([
            'avatar' => FileUploadRule::avatarRules(required: false),
        ], FileUploadRule::messages(), FileUploadRule::attributes());

        // Additional security check for suspicious filenames
        $originalName = $this->avatar->getClientOriginalName();
        if (FileUploadRule::isSuspiciousFilename($originalName)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Invalid filename detected. Please rename the file.',
            ]);
            return;
        }
    }

    // Handle avatar upload with sanitized filename
    if ($this->avatar) {
        $sanitizedFilename = FileUploadRule::sanitizeFilename(
            $this->avatar->getClientOriginalName()
        );
        $data['avatar'] = $this->avatar->storeAs('teams', $sanitizedFilename, 'public');
    }
}
```

**Validation Rules Applied:**
- File type: Image only
- Size limit: 2MB (2048 KB)
- Allowed MIME types: JPEG, JPG, PNG, GIF, WebP
- Extension validation: Regex prevents double extensions
- Filename sanitization: Path components removed, dangerous chars replaced
- Unique filename: 8-character hash suffix prevents collisions

**Security Improvements:**
1. ✅ MIME type validation (prevents `image.jpg.php` uploads)
2. ✅ File size limits (prevents DoS via large files)
3. ✅ Filename sanitization (prevents path traversal)
4. ✅ Extension whitelist (only safe image formats)
5. ✅ Suspicious filename detection (catches edge cases)

### 2. SSH Key Import

#### Files Updated:
- `/app/Livewire/Settings/SSHKeyManager.php`

#### Implementation Details:

**SSHKeyManager.php - SSH Key Import:**
```php
public function importKey(): void
{
    $this->validate([
        'importKeyName' => 'required|string|max:100',
        'importPublicKey' => FileUploadRule::sshKeyRules(required: true),
        'importPrivateKey' => FileUploadRule::sshKeyRules(required: true),
    ], FileUploadRule::messages());

    // Additional validation for SSH key format
    $publicKey = trim($this->importPublicKey);
    $privateKey = trim($this->importPrivateKey);

    // Validate public key format
    if (!preg_match('/^(ssh-rsa|ssh-ed25519|ecdsa-sha2-nistp\d+)\s+/', $publicKey)) {
        session()->flash('error', 'Invalid public key format...');
        return;
    }

    // Validate private key format
    if (!str_contains($privateKey, '-----BEGIN') ||
        !str_contains($privateKey, 'PRIVATE KEY-----')) {
        session()->flash('error', 'Invalid private key format...');
        return;
    }
}
```

**Validation Rules Applied:**
- Content type: String (not file upload)
- Size limit: 65KB max
- Null byte prevention
- Command injection prevention (backticks, pipes, $())
- Format validation: SSH key header patterns
- Private key PEM format validation

**Security Improvements:**
1. ✅ Prevents null byte injection
2. ✅ Blocks command injection attempts
3. ✅ Validates SSH key format before processing
4. ✅ Size limits prevent memory exhaustion
5. ✅ Regex validation ensures proper key structure

### 3. Remote Storage Service

**Note:** The `RemoteStorageService` already implements secure file handling:

**Location:** `/app/Services/Backup/RemoteStorageService.php`

**Existing Security Features:**
- Stream-based uploads (prevents memory issues)
- File size verification after upload
- Encryption support (AES-256-GCM)
- Path prefix sanitization
- Checksum validation
- Secure temp file handling with cleanup

**No Changes Required:** Service already follows best practices for file storage.

## Testing Coverage

### Security Test Suite

**Location:** `/tests/Security/FileUploadSecurityTest.php`

**Test Coverage:**

1. **File Type Validation:**
   - ✅ Rejects PHP file uploads
   - ✅ Rejects executable files (EXE, etc.)
   - ✅ Rejects shell scripts (SH, BAT)
   - ✅ Rejects double extension files (`image.jpg.php`)

2. **File Size Validation:**
   - ✅ Rejects oversized files (100MB+)

3. **MIME Type Validation:**
   - ✅ Validates actual MIME type, not just extension
   - ✅ Detects content/extension mismatch

4. **Filename Sanitization:**
   - ✅ Sanitizes path traversal attempts (`../../etc/passwd`)
   - ✅ Removes null bytes in filenames

5. **SSH Key Validation:**
   - ✅ Validates RSA key format
   - ✅ Validates Ed25519 key format
   - ✅ Rejects invalid key formats

6. **Environment File Validation:**
   - ✅ Detects command injection in ENV content

## Usage Examples

### Example 1: Adding File Upload to Livewire Component

```php
use App\Rules\FileUploadRule;
use Livewire\Component;
use Livewire\WithFileUploads;

class MyComponent extends Component
{
    use WithFileUploads;

    public mixed $logo = null;

    public function uploadLogo(): void
    {
        // Validate file upload
        if ($this->logo) {
            $this->validate([
                'logo' => FileUploadRule::logoRules(required: false),
            ], FileUploadRule::messages(), FileUploadRule::attributes());

            // Check for suspicious filename
            $originalName = $this->logo->getClientOriginalName();
            if (FileUploadRule::isSuspiciousFilename($originalName)) {
                session()->flash('error', 'Invalid filename detected.');
                return;
            }

            // Store with sanitized filename
            $sanitizedFilename = FileUploadRule::sanitizeFilename($originalName);
            $path = $this->logo->storeAs('logos', $sanitizedFilename, 'public');
        }
    }
}
```

### Example 2: Adding File Upload to Form Request

```php
use App\Rules\FileUploadRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'document' => FileUploadRule::documentRules(required: true),
        ];
    }

    public function messages(): array
    {
        return FileUploadRule::messages();
    }

    public function attributes(): array
    {
        return FileUploadRule::attributes();
    }
}
```

### Example 3: Custom File Upload Validation

```php
use App\Rules\FileUploadRule;

class CustomUpload
{
    public function validateUpload($file): bool
    {
        // Check file extension
        $filename = $file->getClientOriginalName();
        if (!FileUploadRule::isAllowedExtension($filename, ['pdf', 'docx'])) {
            return false;
        }

        // Check for suspicious patterns
        if (FileUploadRule::isSuspiciousFilename($filename)) {
            return false;
        }

        return true;
    }
}
```

## Security Checklist for New File Uploads

When adding new file upload functionality, ensure:

- [ ] Use `FileUploadRule` validation methods
- [ ] Set appropriate file size limits
- [ ] Use MIME type whitelist (not blacklist)
- [ ] Validate file extension with regex
- [ ] Check for suspicious filenames using `isSuspiciousFilename()`
- [ ] Sanitize filename using `sanitizeFilename()`
- [ ] Store files outside web root when possible
- [ ] Use Laravel's Storage facade with proper disk configuration
- [ ] Add unique hash suffix to prevent filename collisions
- [ ] Never trust client-provided filename
- [ ] Validate file content, not just extension
- [ ] Log file upload events for audit trail

## PHPStan Compliance

All file upload validation code is PHPStan Level 8 compliant:

```bash
./vendor/bin/phpstan analyse app/Rules/FileUploadRule.php \
    app/Livewire/Teams/TeamList.php \
    app/Livewire/Teams/TeamSettings.php \
    app/Livewire/Settings/SSHKeyManager.php \
    app/Http/Requests/StoreTeamRequest.php \
    app/Http/Requests/UpdateTeamRequest.php \
    --level=8

# [OK] No errors
```

**Type Safety Features:**
- Strict type declarations (`declare(strict_types=1)`)
- Proper PHPDoc annotations
- Type casting for all path operations
- Null-safe operators where applicable
- Return type declarations on all methods

## Files Modified

### New Files Created:
1. `/app/Rules/FileUploadRule.php` - Centralized validation rule class

### Files Updated:
1. `/app/Livewire/Teams/TeamList.php` - Team avatar upload validation
2. `/app/Livewire/Teams/TeamSettings.php` - Team avatar update validation
3. `/app/Livewire/Settings/SSHKeyManager.php` - SSH key import validation
4. `/app/Http/Requests/StoreTeamRequest.php` - Team creation form validation
5. `/app/Http/Requests/UpdateTeamRequest.php` - Team update form validation

### Existing Security Tests:
- `/tests/Security/FileUploadSecurityTest.php` - Comprehensive security test suite

## Summary

The file upload validation implementation provides:

✅ **Centralized Validation** - Single source of truth for file upload rules
✅ **Multi-Layer Security** - MIME, extension, size, content, and filename validation
✅ **Path Traversal Protection** - Prevents directory escape attacks
✅ **Extension Validation** - Whitelist approach with regex validation
✅ **Filename Sanitization** - Automatic cleaning of dangerous filenames
✅ **Command Injection Prevention** - Blocks malicious content patterns
✅ **Null Byte Protection** - Prevents null byte injection attacks
✅ **Double Extension Detection** - Catches `file.jpg.php` style attacks
✅ **Type Safety** - PHPStan Level 8 compliant
✅ **Reusability** - Easy to apply to new upload features
✅ **Comprehensive Testing** - Security test suite included

All file uploads in the application now follow security best practices and are protected against common file upload vulnerabilities.
