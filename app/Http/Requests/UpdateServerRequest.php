<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\NameRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for updating an existing server
 */
class UpdateServerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => NameRule::rules(required: false, maxLength: 255),
            'hostname' => 'nullable|string|max:255',
            'ip_address' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'ssh_password' => 'nullable|string',
            'ssh_key' => 'nullable|string',
            'auth_method' => 'nullable|in:password,key',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
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
            'ip_address' => 'IP address',
            'ssh_password' => 'SSH password',
            'ssh_key' => 'SSH key',
            'auth_method' => 'authentication method',
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
            'username.regex' => 'The username must only contain letters, numbers, underscores, and hyphens.',
        ];
    }
}
