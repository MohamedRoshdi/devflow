<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServerRequest extends FormRequest
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
        $serverId = $this->route('server')->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'hostname' => ['sometimes', 'string', 'max:255'],
            'ip_address' => ['sometimes', 'ip', Rule::unique('servers', 'ip_address')->ignore($serverId)],
            'port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'username' => ['sometimes', 'string', 'max:100'],
            'ssh_key' => ['sometimes', 'string'],
            'ssh_password' => ['sometimes', 'string', 'max:500'],
            'status' => ['sometimes', 'string', Rule::in(['online', 'offline', 'maintenance'])],
            'os' => ['sometimes', 'string', 'max:100'],
            'cpu_cores' => ['sometimes', 'integer', 'min:1'],
            'memory_gb' => ['sometimes', 'integer', 'min:1'],
            'disk_gb' => ['sometimes', 'integer', 'min:1'],
            'docker_installed' => ['sometimes', 'boolean'],
            'docker_version' => ['sometimes', 'string', 'max:50'],
            'location_name' => ['sometimes', 'string', 'max:255'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
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
            'ip_address' => 'IP address',
            'ssh_key' => 'SSH key',
            'ssh_password' => 'SSH password',
        ];
    }
}
