<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Server;
use App\Services\ServerConnectivityService;

/**
 * Shared form fields and validation for Server Create/Edit components.
 *
 * Provides common properties, validation rules, and connection testing.
 * Components should define their own rules() method, using baseServerRules()
 * and authRules() helpers.
 */
trait HasServerFormFields
{
    public string $name = '';

    public string $hostname = '';

    public string $ip_address = '';

    public int $port = 22;

    public string $username = 'root';

    public string $ssh_password = '';

    public string $ssh_key = '';

    public string $auth_method = 'password';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public string $location_name = '';

    /**
     * Test SSH connection with current form values
     */
    public function testConnection(): void
    {
        $this->validate();

        try {
            $tempServer = new Server([
                'ip_address' => $this->ip_address,
                'port' => $this->port,
                'username' => $this->username,
                'ssh_password' => $this->getPasswordForTest(),
                'ssh_key' => $this->getKeyForTest(),
            ]);

            $connectivityService = app(ServerConnectivityService::class);
            $result = $connectivityService->testConnection($tempServer);

            if ($result['reachable']) {
                session()->flash('connection_test', $result['message'].' (Latency: '.$result['latency_ms'].'ms)');
            } else {
                session()->flash('connection_error', $result['message']);
            }
        } catch (\Exception $e) {
            session()->flash('connection_error', 'Connection failed: '.$e->getMessage());
        }
    }

    /**
     * Get password for connection test (override in Edit for existing credentials)
     */
    protected function getPasswordForTest(): ?string
    {
        return $this->auth_method === 'password' ? $this->ssh_password : null;
    }

    /**
     * Get SSH key for connection test (override in Edit for existing credentials)
     */
    protected function getKeyForTest(): ?string
    {
        return $this->auth_method === 'key' ? $this->ssh_key : null;
    }

    /**
     * Get GPS coordinates (placeholder for JS integration)
     */
    public function getLocation(): void
    {
        $this->location_name = 'Auto-detected location';
    }

    /**
     * Base validation rules shared between Create and Edit
     *
     * @return array<string, mixed>
     */
    protected function baseServerRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'auth_method' => 'required|in:password,key',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get username validation rule
     *
     * @param bool $withRegex Include regex validation (for create)
     */
    protected function usernameRule(bool $withRegex = true): string|array
    {
        if ($withRegex) {
            return ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-]+$/'];
        }

        return 'required|string|max:255';
    }

    /**
     * Get auth rules for create (required based on method)
     *
     * @return array<string, string>
     */
    protected function authRulesForCreate(): array
    {
        return [
            'ssh_password' => 'nullable|string|required_if:auth_method,password',
            'ssh_key' => 'nullable|string|required_if:auth_method,key',
        ];
    }

    /**
     * Get auth rules for edit (optional since existing may be used)
     *
     * @return array<string, string>
     */
    protected function authRulesForEdit(): array
    {
        return [
            'ssh_password' => 'nullable|string',
            'ssh_key' => 'nullable|string',
        ];
    }
}
