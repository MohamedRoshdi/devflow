<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:projects,slug', 'regex:/^[a-z0-9-]+$/'],
            'repository_url' => ['required', 'url', 'max:500'],
            'branch' => ['required', 'string', 'max:100'],
            'framework' => ['required', 'string', Rule::in(['laravel', 'shopware', 'symfony', 'wordpress', 'nextjs', 'vue', 'react', 'custom'])],
            'project_type' => ['required', 'string', Rule::in(['single_tenant', 'multi_tenant', 'saas', 'microservice'])],
            'environment' => ['nullable', 'string', Rule::in(['production', 'staging', 'development'])],
            'php_version' => ['nullable', 'string', 'max:10'],
            'node_version' => ['nullable', 'string', 'max:10'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'root_directory' => ['nullable', 'string', 'max:500'],
            'build_command' => ['nullable', 'string', 'max:1000'],
            'start_command' => ['nullable', 'string', 'max:1000'],
            'install_commands' => ['nullable', 'array'],
            'install_commands.*' => ['string', 'max:1000'],
            'build_commands' => ['nullable', 'array'],
            'build_commands.*' => ['string', 'max:1000'],
            'post_deploy_commands' => ['nullable', 'array'],
            'post_deploy_commands.*' => ['string', 'max:1000'],
            'env_variables' => ['nullable', 'array'],
            'env_variables.*' => ['string', 'max:5000'],
            'health_check_url' => ['nullable', 'url', 'max:500'],
            'auto_deploy' => ['nullable', 'boolean'],
            'webhook_enabled' => ['nullable', 'boolean'],
            'server_id' => ['required', 'integer', 'exists:servers,id'],
            'template_id' => ['nullable', 'integer', 'exists:project_templates,id'],
            'metadata' => ['nullable', 'array'],
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
            'server_id' => 'server',
            'template_id' => 'project template',
            'env_variables' => 'environment variables',
        ];
    }
}
