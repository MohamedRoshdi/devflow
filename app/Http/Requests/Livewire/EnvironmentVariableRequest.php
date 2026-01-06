<?php

declare(strict_types=1);

namespace App\Http\Requests\Livewire;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for environment variable operations
 *
 * Validates environment variable key-value pairs for both
 * local and server .env operations.
 */
class EnvironmentVariableRequest extends FormRequest
{
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
        return self::keyValueRules();
    }

    /**
     * Get rules for environment key validation
     *
     * @return array<string, mixed>
     */
    public static function keyRules(): array
    {
        return [
            'required',
            'string',
            'max:255',
            'regex:/^[A-Z][A-Z0-9_]*$/i',
        ];
    }

    /**
     * Get rules for environment value validation
     *
     * @return array<string, mixed>
     */
    public static function valueRules(): array
    {
        return [
            'nullable',
            'string',
            'max:2000',
        ];
    }

    /**
     * Get rules for key-value pair validation
     *
     * @return array<string, array<string, mixed>>
     */
    public static function keyValueRules(): array
    {
        return [
            'key' => self::keyRules(),
            'value' => self::valueRules(),
        ];
    }

    /**
     * Get rules for environment selection
     *
     * @return array<string, mixed>
     */
    public static function environmentRules(): array
    {
        return [
            'environment' => 'required|in:local,development,staging,production',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'key' => 'variable name',
            'value' => 'variable value',
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
            'key.regex' => 'Variable name must start with a letter and contain only letters, numbers, and underscores.',
            'key.required' => 'Variable name is required.',
            'value.max' => 'Variable value cannot exceed 2000 characters.',
        ];
    }
}
