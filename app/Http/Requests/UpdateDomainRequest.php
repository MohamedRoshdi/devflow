<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled in the controller via policies
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $domainId = $this->route('domain')->id;

        return [
            'domain' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i',
                Rule::unique('domains', 'domain')->ignore($domainId),
            ],
            'ssl_enabled' => ['sometimes', 'boolean'],
            'ssl_provider' => ['sometimes', 'string', Rule::in(['letsencrypt', 'custom', 'cloudflare'])],
            'ssl_certificate' => ['sometimes', 'nullable', 'string'],
            'ssl_private_key' => ['sometimes', 'nullable', 'string'],
            'auto_renew_ssl' => ['sometimes', 'boolean'],
            'is_primary' => ['sometimes', 'boolean'],
            'dns_configured' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'pending'])],
            'metadata' => ['sometimes', 'array'],
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
            'domain.regex' => 'Please enter a valid domain name (e.g., example.com or subdomain.example.com).',
            'domain.unique' => 'This domain is already registered in the system.',
            'ssl_provider.in' => 'The SSL provider must be one of: Let\'s Encrypt, Custom, or Cloudflare.',
            'status.in' => 'The status must be one of: active, inactive, or pending.',
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
            'ssl_enabled' => 'SSL enabled',
            'ssl_provider' => 'SSL provider',
            'ssl_certificate' => 'SSL certificate',
            'ssl_private_key' => 'SSL private key',
            'auto_renew_ssl' => 'automatic SSL renewal',
            'is_primary' => 'primary domain',
            'dns_configured' => 'DNS configuration status',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert domain to lowercase for consistency
        if ($this->has('domain')) {
            $this->merge([
                'domain' => strtolower($this->input('domain')),
            ]);
        }
    }
}
