<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreServerRequest extends FormRequest
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
            'hostname' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip', 'unique:servers,ip_address'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:100'],
            'ssh_key' => ['nullable', 'string'],
            'ssh_password' => ['nullable', 'string', 'max:500'],
            'os' => ['nullable', 'string', 'max:100'],
            'cpu_cores' => ['nullable', 'integer', 'min:1'],
            'memory_gb' => ['nullable', 'integer', 'min:1'],
            'disk_gb' => ['nullable', 'integer', 'min:1'],
            'docker_installed' => ['nullable', 'boolean'],
            'docker_version' => ['nullable', 'string', 'max:50'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
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
            'ip_address' => 'IP address',
            'ssh_key' => 'SSH key',
            'ssh_password' => 'SSH password',
        ];
    }
}
