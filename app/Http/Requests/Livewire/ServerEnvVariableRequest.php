<?php

declare(strict_types=1);

namespace App\Http\Requests\Livewire;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for server environment variable operations
 *
 * Validates environment variable key-value pairs specifically
 * for server-side .env file operations with additional security constraints.
 */
class ServerEnvVariableRequest extends FormRequest
{
    /**
     * Sensitive keys that require extra authorization
     *
     * @var array<int, string>
     */
    public const SENSITIVE_KEYS = [
        'APP_KEY',
        'DB_PASSWORD',
        'DB_USERNAME',
        'REDIS_PASSWORD',
        'MAIL_PASSWORD',
        'AWS_SECRET_ACCESS_KEY',
        'PUSHER_APP_SECRET',
        'MIX_PUSHER_APP_KEY',
        'STRIPE_SECRET',
        'PAYPAL_SECRET',
    ];

    /**
     * Protected keys that cannot be modified via UI
     *
     * @var array<int, string>
     */
    public const PROTECTED_KEYS = [
        'APP_KEY', // Should be generated via artisan
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'serverEnvKey' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Z][A-Z0-9_]*$/i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (in_array(strtoupper((string) $value), self::PROTECTED_KEYS, true)) {
                        $fail("The {$value} key cannot be modified via the UI. Use artisan commands instead.");
                    }
                },
            ],
            'serverEnvValue' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * Check if a key is sensitive and requires elevated permissions
     */
    public static function isSensitiveKey(string $key): bool
    {
        return in_array(strtoupper($key), self::SENSITIVE_KEYS, true);
    }

    /**
     * Check if a key is protected (cannot be modified)
     */
    public static function isProtectedKey(string $key): bool
    {
        return in_array(strtoupper($key), self::PROTECTED_KEYS, true);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'serverEnvKey' => 'variable name',
            'serverEnvValue' => 'variable value',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'serverEnvKey.regex' => 'Variable name must start with a letter and contain only letters, numbers, and underscores.',
            'serverEnvKey.required' => 'Variable name is required.',
            'serverEnvValue.max' => 'Variable value cannot exceed 2000 characters.',
        ];
    }
}
