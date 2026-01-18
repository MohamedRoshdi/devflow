<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeploymentRequest extends FormRequest
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
            'branch' => ['sometimes', 'string', 'max:100'],
            'commit_hash' => ['sometimes', 'string', 'max:40', 'regex:/^[a-f0-9]{7,40}$|^HEAD$/i'],
            'commit_message' => ['sometimes', 'string', 'max:500'],
            'environment_snapshot' => ['sometimes', 'array'],
            'environment_snapshot.php_version' => ['sometimes', 'string', 'max:10'],
            'environment_snapshot.node_version' => ['sometimes', 'string', 'max:10'],
            'environment_snapshot.framework' => ['sometimes', 'string', 'max:50'],
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
            'commit_hash.regex' => 'The commit hash must be a valid Git commit hash (7-40 hexadecimal characters) or "HEAD".',
            'commit_message.max' => 'The commit message must not exceed 500 characters.',
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
            'commit_hash' => 'commit hash',
            'commit_message' => 'commit message',
            'environment_snapshot' => 'environment snapshot',
            'environment_snapshot.php_version' => 'PHP version',
            'environment_snapshot.node_version' => 'Node.js version',
            'environment_snapshot.framework' => 'framework',
        ];
    }
}
