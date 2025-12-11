<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDomainRequest extends FormRequest
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
        return [
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i',
                Rule::unique('domains', 'domain'),
            ],
            'ssl_enabled' => ['nullable', 'boolean'],
            'ssl_provider' => ['nullable', 'string', Rule::in(['letsencrypt', 'custom', 'cloudflare'])],
            'auto_renew_ssl' => ['nullable', 'boolean'],
            'is_primary' => ['nullable', 'boolean'],
            'dns_configured' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
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
            'domain.required' => 'Please enter a domain name.',
            'domain.regex' => 'Please enter a valid domain name (e.g., example.com or subdomain.example.com).',
            'domain.unique' => 'This domain is already registered in the system.',
            'ssl_provider.in' => 'The SSL provider must be one of: Let\'s Encrypt, Custom, or Cloudflare.',
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

        // Set default values
        $this->merge([
            'ssl_enabled' => $this->input('ssl_enabled', true),
            'auto_renew_ssl' => $this->input('auto_renew_ssl', true),
            'is_primary' => $this->input('is_primary', false),
        ]);
    }
}
