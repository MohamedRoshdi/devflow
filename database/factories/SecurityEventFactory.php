<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SecurityEvent;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityEvent>
 */
class SecurityEventFactory extends Factory
{
    protected $model = SecurityEvent::class;

    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'event_type' => fake()->randomElement([
                SecurityEvent::TYPE_FIREWALL_ENABLED,
                SecurityEvent::TYPE_FIREWALL_DISABLED,
                SecurityEvent::TYPE_RULE_ADDED,
                SecurityEvent::TYPE_RULE_DELETED,
                SecurityEvent::TYPE_IP_BANNED,
                SecurityEvent::TYPE_IP_UNBANNED,
                SecurityEvent::TYPE_SSH_CONFIG_CHANGED,
                SecurityEvent::TYPE_SECURITY_SCAN,
            ]),
            'source_ip' => fake()->ipv4(),
            'details' => fake()->sentence(),
            'metadata' => [
                'action' => fake()->word(),
                'affected_rules' => fake()->randomNumber(2),
            ],
            'user_id' => User::factory(),
        ];
    }

    public function firewallEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => SecurityEvent::TYPE_FIREWALL_ENABLED,
            'details' => 'Firewall has been enabled',
        ]);
    }

    public function firewallDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => SecurityEvent::TYPE_FIREWALL_DISABLED,
            'details' => 'Firewall has been disabled',
        ]);
    }

    public function ipBanned(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => SecurityEvent::TYPE_IP_BANNED,
            'details' => 'IP address has been banned',
        ]);
    }

    public function sshConfigChanged(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => SecurityEvent::TYPE_SSH_CONFIG_CHANGED,
            'details' => 'SSH configuration has been changed',
        ]);
    }

    public function securityScan(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => SecurityEvent::TYPE_SECURITY_SCAN,
            'details' => 'Security scan completed',
        ]);
    }
}
