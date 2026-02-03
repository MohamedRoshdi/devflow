<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SecurityIncident;
use App\Models\SecurityPrediction;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityPrediction>
 */
class SecurityPredictionFactory extends Factory
{
    protected $model = SecurityPrediction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            SecurityPrediction::TYPE_CPU_ANOMALY,
            SecurityPrediction::TYPE_MEMORY_EXHAUSTION,
            SecurityPrediction::TYPE_BRUTE_FORCE_ESCALATION,
            SecurityPrediction::TYPE_NEW_SERVICE_DETECTED,
            SecurityPrediction::TYPE_NEW_USER_DETECTED,
            SecurityPrediction::TYPE_NEW_CRONTAB_DETECTED,
            SecurityPrediction::TYPE_PORT_ANOMALY,
            SecurityPrediction::TYPE_DISK_EXHAUSTION,
        ];

        $severities = [
            SecurityIncident::SEVERITY_CRITICAL,
            SecurityIncident::SEVERITY_HIGH,
            SecurityIncident::SEVERITY_MEDIUM,
            SecurityIncident::SEVERITY_LOW,
        ];

        $type = fake()->randomElement($types);

        return [
            'server_id' => Server::factory(),
            'prediction_type' => $type,
            'severity' => fake()->randomElement($severities),
            'status' => SecurityPrediction::STATUS_ACTIVE,
            'title' => $this->getTitleForType($type),
            'description' => fake()->sentence(15),
            'evidence' => $this->getEvidenceForType($type),
            'recommended_actions' => $this->getActionsForType($type),
            'confidence_score' => fake()->randomFloat(2, 0.5, 1.0),
            'predicted_impact_at' => fake()->optional(0.7)->dateTimeBetween('now', '+48 hours'),
            'acknowledged_at' => null,
            'resolved_at' => null,
            'acknowledged_by' => null,
        ];
    }

    private function getTitleForType(string $type): string
    {
        return match ($type) {
            SecurityPrediction::TYPE_CPU_ANOMALY => 'CPU usage anomaly detected - possible crypto miner',
            SecurityPrediction::TYPE_MEMORY_EXHAUSTION => 'Memory exhaustion predicted within 2 hours',
            SecurityPrediction::TYPE_BRUTE_FORCE_ESCALATION => 'SSH brute force attack escalating',
            SecurityPrediction::TYPE_NEW_SERVICE_DETECTED => 'New systemd service detected: '.fake()->word(),
            SecurityPrediction::TYPE_NEW_USER_DETECTED => 'New user account detected: '.fake()->userName(),
            SecurityPrediction::TYPE_NEW_CRONTAB_DETECTED => 'New crontab entry detected for '.fake()->userName(),
            SecurityPrediction::TYPE_PORT_ANOMALY => 'New listening port detected: '.fake()->numberBetween(1024, 65535),
            SecurityPrediction::TYPE_DISK_EXHAUSTION => 'Disk space exhaustion predicted within 24 hours',
            default => 'Security prediction alert',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function getEvidenceForType(string $type): array
    {
        return match ($type) {
            SecurityPrediction::TYPE_CPU_ANOMALY => [
                'baseline_cpu' => fake()->randomFloat(1, 5, 30),
                'current_cpu' => fake()->randomFloat(1, 70, 100),
                'sustained_minutes' => fake()->numberBetween(5, 60),
            ],
            SecurityPrediction::TYPE_MEMORY_EXHAUSTION => [
                'current_usage' => fake()->randomFloat(1, 80, 95),
                'trend_slope' => fake()->randomFloat(3, 0.1, 2.0),
                'predicted_100_at' => now()->addHours(2)->toIso8601String(),
            ],
            default => [
                'detected_at' => now()->toIso8601String(),
                'source' => 'guardian_analysis',
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    private function getActionsForType(string $type): array
    {
        return match ($type) {
            SecurityPrediction::TYPE_CPU_ANOMALY => [
                'Investigate high-CPU processes',
                'Check for unauthorized mining software',
                'Review recent service changes',
            ],
            SecurityPrediction::TYPE_MEMORY_EXHAUSTION => [
                'Identify memory-intensive processes',
                'Check for memory leaks',
                'Consider adding swap or increasing RAM',
            ],
            SecurityPrediction::TYPE_BRUTE_FORCE_ESCALATION => [
                'Review fail2ban configuration',
                'Consider changing SSH port',
                'Enable key-only authentication',
            ],
            default => [
                'Investigate the change',
                'Compare against baseline',
                'Verify with server administrator',
            ],
        };
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SecurityPrediction::STATUS_ACTIVE,
        ]);
    }

    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SecurityPrediction::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledged_by' => User::factory(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SecurityPrediction::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);
    }

    public function cpuAnomaly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'prediction_type' => SecurityPrediction::TYPE_CPU_ANOMALY,
            'severity' => SecurityIncident::SEVERITY_CRITICAL,
            'title' => 'CPU usage anomaly - possible crypto miner',
            'confidence_score' => fake()->randomFloat(2, 0.8, 1.0),
        ]);
    }

    public function memoryExhaustion(): static
    {
        return $this->state(fn (array $attributes): array => [
            'prediction_type' => SecurityPrediction::TYPE_MEMORY_EXHAUSTION,
            'severity' => SecurityIncident::SEVERITY_HIGH,
            'title' => 'Memory exhaustion predicted within 2 hours',
            'predicted_impact_at' => now()->addHours(2),
        ]);
    }
}
