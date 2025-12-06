<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServerMetric>
 */
class ServerMetricFactory extends Factory
{
    protected $model = ServerMetric::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $memoryTotal = fake()->numberBetween(2048, 65536); // 2GB to 64GB
        $memoryUsed = fake()->numberBetween(512, $memoryTotal - 512);
        $memoryUsage = round(($memoryUsed / $memoryTotal) * 100, 2);

        $diskTotal = fake()->numberBetween(50, 2000); // 50GB to 2TB
        $diskUsed = fake()->numberBetween(10, $diskTotal - 10);
        $diskUsage = round(($diskUsed / $diskTotal) * 100, 2);

        return [
            'server_id' => Server::factory(),
            'cpu_usage' => fake()->randomFloat(2, 0, 100),
            'memory_usage' => $memoryUsage,
            'memory_used_mb' => $memoryUsed,
            'memory_total_mb' => $memoryTotal,
            'disk_usage' => $diskUsage,
            'disk_used_gb' => $diskUsed,
            'disk_total_gb' => $diskTotal,
            'load_average_1' => fake()->randomFloat(2, 0, 10),
            'load_average_5' => fake()->randomFloat(2, 0, 10),
            'load_average_15' => fake()->randomFloat(2, 0, 10),
            'network_in_bytes' => fake()->numberBetween(1000000, 10000000000),
            'network_out_bytes' => fake()->numberBetween(1000000, 10000000000),
            'processes_running' => fake()->numberBetween(50, 300),
            'processes_total' => fake()->numberBetween(100, 500),
            'uptime_seconds' => fake()->numberBetween(3600, 2592000),
            'recorded_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    /**
     * Indicate healthy server metrics.
     */
    public function healthy(): static
    {
        return $this->state(function (array $attributes) {
            $memoryTotal = 8192; // 8GB
            $memoryUsed = fake()->numberBetween(2048, 4096); // 25-50% usage

            $diskTotal = 500; // 500GB
            $diskUsed = fake()->numberBetween(100, 250); // 20-50% usage

            return [
                'cpu_usage' => fake()->randomFloat(2, 10, 50),
                'memory_usage' => round(($memoryUsed / $memoryTotal) * 100, 2),
                'memory_used_mb' => $memoryUsed,
                'memory_total_mb' => $memoryTotal,
                'disk_usage' => round(($diskUsed / $diskTotal) * 100, 2),
                'disk_used_gb' => $diskUsed,
                'disk_total_gb' => $diskTotal,
                'load_average_1' => fake()->randomFloat(2, 0.5, 2),
                'recorded_at' => now(),
            ];
        });
    }

    /**
     * Indicate critical server metrics.
     */
    public function critical(): static
    {
        return $this->state(function (array $attributes) {
            $memoryTotal = 8192; // 8GB
            $memoryUsed = fake()->numberBetween(7372, 7864); // 90-96% usage

            $diskTotal = 500; // 500GB
            $diskUsed = fake()->numberBetween(450, 490); // 90-98% usage

            return [
                'cpu_usage' => fake()->randomFloat(2, 85, 99),
                'memory_usage' => round(($memoryUsed / $memoryTotal) * 100, 2),
                'memory_used_mb' => $memoryUsed,
                'memory_total_mb' => $memoryTotal,
                'disk_usage' => round(($diskUsed / $diskTotal) * 100, 2),
                'disk_used_gb' => $diskUsed,
                'disk_total_gb' => $diskTotal,
                'load_average_1' => fake()->randomFloat(2, 8, 15),
                'recorded_at' => now(),
            ];
        });
    }
}
