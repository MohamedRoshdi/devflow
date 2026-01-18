<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\NameRule;
use App\Rules\SlugRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for updating an existing project
 */
class UpdateProjectRequest extends FormRequest
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
        $projectId = $this->route('project')?->id ?? $this->input('id');

        return [
            'name' => NameRule::rules(required: true, maxLength: 255),
            'slug' => SlugRule::rules(required: true, maxLength: 255)."|unique:projects,slug,{$projectId},id,deleted_at,NULL",
            'server_id' => 'nullable|exists:servers,id',
            'repository_url' => ['nullable', 'regex:/^(https?:\/\/|git@)[\w\-\.]+[\/:][\w\-\.]+\/[\w\-\.]+\.git$/'],
            'branch' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\.\/]+$/'],
            'framework' => 'nullable|string|max:255',
            'deployment_method' => 'nullable|in:docker,standard',
            'php_version' => 'nullable|string|max:255',
            'node_version' => 'nullable|string|max:255',
            'root_directory' => 'nullable|string|max:255',
            'build_command' => 'nullable|string',
            'start_command' => 'nullable|string',
            'auto_deploy' => 'nullable|boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:2000',
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
            'repository_url' => 'repository URL',
            'php_version' => 'PHP version',
            'node_version' => 'Node version',
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
            'repository_url.regex' => 'The repository URL must be a valid Git URL (HTTPS or SSH format).',
            'branch.regex' => 'The branch name contains invalid characters.',
            'slug.unique' => 'A project with this slug already exists.',
        ];
    }
}
