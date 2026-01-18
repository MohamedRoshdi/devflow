<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\DescriptionRule;
use App\Rules\NameRule;
use App\Rules\SlugRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for storing a new project template
 */
class StoreProjectTemplateRequest extends FormRequest
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
            'name' => NameRule::rules(required: true, maxLength: 255),
            'slug' => SlugRule::rules(required: true, maxLength: 255),
            'description' => DescriptionRule::rules(required: false, maxLength: 1000),
            'framework' => 'required|in:laravel,react,vue,nodejs,php,python,docker,custom',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
            'default_branch' => 'required|string|max:100',
            'php_version' => 'nullable|in:8.1,8.2,8.3,8.4',
            'node_version' => 'nullable|string|max:20',
            'health_check_path' => 'nullable|string|max:500',
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
            'default_branch' => 'default branch',
            'php_version' => 'PHP version',
            'node_version' => 'Node version',
            'health_check_path' => 'health check path',
        ];
    }
}
