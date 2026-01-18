<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SecurityIncident;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SecurityIncident>
 */
class SecurityIncidentFactory extends Factory
{
    protected $model = SecurityIncident::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $incidentTypes = [
            SecurityIncident::TYPE_MALWARE,
            SecurityIncident::TYPE_BACKDOOR_USER,
            SecurityIncident::TYPE_SUSPICIOUS_PROCESS,
            SecurityIncident::TYPE_OUTBOUND_ATTACK,
            SecurityIncident::TYPE_UNAUTHORIZED_SSH_KEY,
            SecurityIncident::TYPE_MALICIOUS_CRON,
            SecurityIncident::TYPE_ROOTKIT,
            SecurityIncident::TYPE_BRUTE_FORCE,
            SecurityIncident::TYPE_HIDDEN_DIRECTORY,
        ];

        $severities = [
            SecurityIncident::SEVERITY_CRITICAL,
            SecurityIncident::SEVERITY_HIGH,
            SecurityIncident::SEVERITY_MEDIUM,
            SecurityIncident::SEVERITY_LOW,
        ];

        $statuses = [
            SecurityIncident::STATUS_DETECTED,
            SecurityIncident::STATUS_INVESTIGATING,
            SecurityIncident::STATUS_MITIGATING,
            SecurityIncident::STATUS_RESOLVED,
            SecurityIncident::STATUS_FALSE_POSITIVE,
        ];

        $incidentType = fake()->randomElement($incidentTypes);

        return [
            'server_id' => Server::factory(),
            'user_id' => fake()->optional(0.7)->passthrough(User::factory()),
            'incident_type' => $incidentType,
            'severity' => fake()->randomElement($severities),
            'status' => fake()->randomElement($statuses),
            'title' => $this->getTitleForType($incidentType),
            'description' => fake()->sentence(10),
            'findings' => $this->getFindingsForType($incidentType),
            'affected_items' => $this->getAffectedItemsForType($incidentType),
            'remediation_actions' => [],
            'detected_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'resolved_at' => null,
            'auto_remediated' => false,
        ];
    }

    /**
     * Get a title based on incident type.
     */
    private function getTitleForType(string $type): string
    {
        return match ($type) {
            SecurityIncident::TYPE_MALWARE => 'Malware detected in '.fake()->randomElement(['/tmp', '/var/tmp', '/home']),
            SecurityIncident::TYPE_BACKDOOR_USER => 'Backdoor user detected: '.fake()->userName(),
            SecurityIncident::TYPE_SUSPICIOUS_PROCESS => 'Suspicious process: '.fake()->randomElement(['httpd6', 'sshd2', 'kworker']),
            SecurityIncident::TYPE_OUTBOUND_ATTACK => 'Outbound SSH brute-force attack detected',
            SecurityIncident::TYPE_UNAUTHORIZED_SSH_KEY => 'Unauthorized SSH key added',
            SecurityIncident::TYPE_MALICIOUS_CRON => 'Malicious cron job detected',
            SecurityIncident::TYPE_ROOTKIT => 'Potential rootkit detected',
            SecurityIncident::TYPE_BRUTE_FORCE => 'SSH brute-force attack from '.fake()->ipv4(),
            SecurityIncident::TYPE_HIDDEN_DIRECTORY => 'Hidden directory with suspicious content',
            default => 'Security incident detected',
        };
    }

    /**
     * Get findings based on incident type.
     *
     * @return array<string, mixed>
     */
    private function getFindingsForType(string $type): array
    {
        return match ($type) {
            SecurityIncident::TYPE_MALWARE => [
                'scan_type' => 'malware',
                'detection_method' => 'signature_match',
                'threat_level' => 'high',
            ],
            SecurityIncident::TYPE_BACKDOOR_USER => [
                'username' => fake()->userName(),
                'uid' => 0,
                'shell' => '/bin/bash',
            ],
            SecurityIncident::TYPE_SUSPICIOUS_PROCESS => [
                'process_name' => fake()->randomElement(['httpd6', 'sshd2', 'kworker']),
                'pid' => fake()->numberBetween(1000, 65535),
                'cpu_usage' => fake()->randomFloat(1, 0, 100),
            ],
            SecurityIncident::TYPE_OUTBOUND_ATTACK => [
                'target_count' => fake()->numberBetween(10, 1000),
                'target_port' => 22,
                'protocol' => 'TCP',
            ],
            default => [
                'detected_by' => 'automated_scan',
                'confidence' => fake()->randomFloat(2, 0.7, 1.0),
            ],
        };
    }

    /**
     * Get affected items based on incident type.
     *
     * @return array<string, mixed>
     */
    private function getAffectedItemsForType(string $type): array
    {
        return match ($type) {
            SecurityIncident::TYPE_MALWARE, SecurityIncident::TYPE_HIDDEN_DIRECTORY => [
                'path' => '/tmp/.'.fake()->regexify('[a-z0-9]{10}'),
                'size' => fake()->numberBetween(1000, 10000000),
            ],
            SecurityIncident::TYPE_BACKDOOR_USER => [
                'username' => fake()->userName(),
                'home_directory' => '/home/'.fake()->userName(),
            ],
            SecurityIncident::TYPE_SUSPICIOUS_PROCESS => [
                'pid' => fake()->numberBetween(1000, 65535),
                'command' => '/tmp/.hidden/malware --daemon',
            ],
            SecurityIncident::TYPE_OUTBOUND_ATTACK => [
                'source_port' => fake()->numberBetween(30000, 65535),
                'connections' => fake()->numberBetween(5, 50),
            ],
            default => [],
        };
    }

    /**
     * Indicate that the incident is critical.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => SecurityIncident::SEVERITY_CRITICAL,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);
    }

    /**
     * Indicate that the incident is high severity.
     */
    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => SecurityIncident::SEVERITY_HIGH,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);
    }

    /**
     * Indicate that the incident has been resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SecurityIncident::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Indicate that the incident was auto-remediated.
     */
    public function autoRemediated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SecurityIncident::STATUS_RESOLVED,
            'resolved_at' => now(),
            'auto_remediated' => true,
            'remediation_actions' => [
                [
                    'action' => 'auto_remediate',
                    'success' => true,
                    'message' => 'Automatically remediated by system',
                    'timestamp' => now()->toISOString(),
                ],
            ],
        ]);
    }

    /**
     * Indicate that the incident is a false positive.
     */
    public function falsePositive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SecurityIncident::STATUS_FALSE_POSITIVE,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Create a malware incident.
     */
    public function malware(): static
    {
        return $this->state(fn (array $attributes) => [
            'incident_type' => SecurityIncident::TYPE_MALWARE,
            'title' => 'Malware detected in /tmp/.hidden_malware',
            'severity' => SecurityIncident::SEVERITY_CRITICAL,
        ]);
    }

    /**
     * Create a backdoor user incident.
     */
    public function backdoorUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'incident_type' => SecurityIncident::TYPE_BACKDOOR_USER,
            'title' => 'Backdoor user detected: web-data',
            'severity' => SecurityIncident::SEVERITY_CRITICAL,
            'affected_items' => [
                'username' => 'web-data',
                'uid' => 0,
                'home_directory' => '/home/web-data',
            ],
        ]);
    }

    /**
     * Create an outbound attack incident.
     */
    public function outboundAttack(): static
    {
        return $this->state(fn (array $attributes) => [
            'incident_type' => SecurityIncident::TYPE_OUTBOUND_ATTACK,
            'title' => 'Outbound SSH brute-force attack detected',
            'severity' => SecurityIncident::SEVERITY_HIGH,
        ]);
    }

    /**
     * Create a suspicious process incident.
     */
    public function suspiciousProcess(): static
    {
        return $this->state(fn (array $attributes) => [
            'incident_type' => SecurityIncident::TYPE_SUSPICIOUS_PROCESS,
            'title' => 'Suspicious process: [kswapd1]',
            'severity' => SecurityIncident::SEVERITY_CRITICAL,
            'affected_items' => [
                'pid' => fake()->numberBetween(1000, 65535),
                'command' => '/tmp/.hidden/httpd6',
            ],
        ]);
    }
}
