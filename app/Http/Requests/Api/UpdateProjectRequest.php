<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
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
        $projectId = $this->route('project')->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('projects', 'slug')->ignore($projectId), 'regex:/^[a-z0-9-]+$/'],
            'repository_url' => ['sometimes', 'url', 'max:500'],
            'branch' => ['sometimes', 'string', 'max:100'],
            'framework' => ['sometimes', 'string', Rule::in(['laravel', 'shopware', 'symfony', 'wordpress', 'nextjs', 'vue', 'react', 'custom'])],
            'project_type' => ['sometimes', 'string', Rule::in(['single_tenant', 'multi_tenant', 'saas', 'microservice'])],
            'environment' => ['sometimes', 'string', Rule::in(['production', 'staging', 'development'])],
            'php_version' => ['sometimes', 'string', 'max:10'],
            'node_version' => ['sometimes', 'string', 'max:10'],
            'port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'root_directory' => ['sometimes', 'string', 'max:500'],
            'build_command' => ['sometimes', 'string', 'max:1000'],
            'start_command' => ['sometimes', 'string', 'max:1000'],
            'install_commands' => ['sometimes', 'array'],
            'install_commands.*' => ['string', 'max:1000'],
            'build_commands' => ['sometimes', 'array'],
            'build_commands.*' => ['string', 'max:1000'],
            'post_deploy_commands' => ['sometimes', 'array'],
            'post_deploy_commands.*' => ['string', 'max:1000'],
            'env_variables' => ['sometimes', 'array'],
            'env_variables.*' => ['string', 'max:5000'],
            'health_check_url' => ['sometimes', 'url', 'max:500'],
            'auto_deploy' => ['sometimes', 'boolean'],
            'webhook_enabled' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(['running', 'stopped', 'building', 'error'])],
            'server_id' => ['sometimes', 'integer', 'exists:servers,id'],
            'metadata' => ['sometimes', 'array'],
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
            'env_variables' => 'environment variables',
        ];
    }
}
