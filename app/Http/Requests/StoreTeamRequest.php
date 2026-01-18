<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\DescriptionRule;
use App\Rules\FileUploadRule;
use App\Rules\NameRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for storing a new team
 */
class StoreTeamRequest extends FormRequest
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
            'description' => DescriptionRule::rules(required: false, maxLength: 500),
            'avatar' => FileUploadRule::avatarRules(required: false),
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
            'avatar' => 'team avatar',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(FileUploadRule::messages(), [
            'avatar.image' => 'The avatar must be an image file.',
            'avatar.max' => 'The avatar size must not exceed 2MB.',
            'avatar.mimes' => 'The avatar must be a file of type: jpeg, jpg, png, gif, webp.',
        ]);
    }
}
