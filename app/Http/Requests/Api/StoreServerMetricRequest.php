<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreServerMetricRequest extends FormRequest
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
            'cpu_usage' => ['required', 'numeric', 'min:0', 'max:100'],
            'memory_usage' => ['required', 'numeric', 'min:0', 'max:100'],
            'disk_usage' => ['required', 'numeric', 'min:0', 'max:100'],
            'network_in' => ['nullable', 'integer', 'min:0'],
            'network_out' => ['nullable', 'integer', 'min:0'],
            'load_average' => ['nullable', 'numeric', 'min:0'],
            'active_connections' => ['nullable', 'integer', 'min:0'],
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
            'cpu_usage.required' => 'CPU usage is required.',
            'cpu_usage.min' => 'CPU usage must be at least 0%.',
            'cpu_usage.max' => 'CPU usage cannot exceed 100%.',
            'memory_usage.required' => 'Memory usage is required.',
            'memory_usage.min' => 'Memory usage must be at least 0%.',
            'memory_usage.max' => 'Memory usage cannot exceed 100%.',
            'disk_usage.required' => 'Disk usage is required.',
            'disk_usage.min' => 'Disk usage must be at least 0%.',
            'disk_usage.max' => 'Disk usage cannot exceed 100%.',
            'network_in.min' => 'Network in value must be non-negative.',
            'network_out.min' => 'Network out value must be non-negative.',
            'load_average.min' => 'Load average must be non-negative.',
            'active_connections.min' => 'Active connections must be non-negative.',
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
            'cpu_usage' => 'CPU usage',
            'memory_usage' => 'memory usage',
            'disk_usage' => 'disk usage',
            'network_in' => 'network incoming traffic',
            'network_out' => 'network outgoing traffic',
            'load_average' => 'load average',
            'active_connections' => 'active connections',
        ];
    }
}
