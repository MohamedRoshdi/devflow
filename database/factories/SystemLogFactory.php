<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Server;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SystemLog>
 */
class SystemLogFactory extends Factory
{
    protected $model = SystemLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $logTypes = SystemLog::getLogTypes();
        $logLevels = SystemLog::getLogLevels();

        return [
            'server_id' => Server::factory(),
            'user_id' => fake()->boolean(30) ? User::factory() : null,
            'log_type' => fake()->randomElement($logTypes),
            'level' => fake()->randomElement($logLevels),
            'source' => fake()->randomElement([
                'syslog',
                'auth',
                'docker:nginx',
                'docker:app',
                'kernel',
                'nginx',
                'php-fpm',
                'mysql',
                'redis',
            ]),
            'message' => fake()->sentence(12),
            'metadata' => [
                'hostname' => fake()->domainName(),
                'pid' => fake()->numberBetween(100, 99999),
                'raw_line' => fake()->sentence(20),
            ],
            'ip_address' => fake()->boolean(50) ? fake()->ipv4() : null,
            'logged_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Create a critical log entry.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => fake()->randomElement([
                SystemLog::LEVEL_EMERGENCY,
                SystemLog::LEVEL_ALERT,
                SystemLog::LEVEL_CRITICAL,
            ]),
            'message' => fake()->randomElement([
                'System is unusable: kernel panic',
                'Database connection lost',
                'Disk full on /var/log',
                'Out of memory: killed process',
                'Critical security breach detected',
            ]),
        ]);
    }

    /**
     * Create an error log entry.
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => SystemLog::LEVEL_ERROR,
            'message' => fake()->randomElement([
                'Failed to connect to database',
                'Permission denied on file access',
                'Docker container failed to start',
                'Nginx configuration test failed',
                'PHP Fatal error: Uncaught exception',
            ]),
        ]);
    }

    /**
     * Create a warning log entry.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => SystemLog::LEVEL_WARNING,
            'message' => fake()->randomElement([
                'Disk usage above 80%',
                'Memory usage high',
                'Failed login attempt',
                'SSL certificate expires in 7 days',
                'Deprecated function called',
            ]),
        ]);
    }

    /**
     * Create an info log entry.
     */
    public function info(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => SystemLog::LEVEL_INFO,
            'message' => fake()->randomElement([
                'User logged in successfully',
                'Service started',
                'Backup completed',
                'Configuration reloaded',
                'Health check passed',
            ]),
        ]);
    }

    /**
     * Create a system log entry.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_type' => SystemLog::TYPE_SYSTEM,
            'source' => 'syslog',
        ]);
    }

    /**
     * Create an auth log entry.
     */
    public function auth(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_type' => SystemLog::TYPE_AUTH,
            'source' => 'auth',
            'ip_address' => fake()->ipv4(),
            'message' => fake()->randomElement([
                'Accepted password for root from ' . fake()->ipv4(),
                'Failed password for invalid user from ' . fake()->ipv4(),
                'Session opened for user by (uid=0)',
                'pam_unix(sshd:session): session closed',
            ]),
        ]);
    }

    /**
     * Create a Docker log entry.
     */
    public function docker(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_type' => SystemLog::TYPE_DOCKER,
            'source' => 'docker:' . fake()->randomElement(['nginx', 'app', 'postgres', 'redis', 'queue']),
            'metadata' => [
                'container' => fake()->randomElement(['nginx', 'app', 'postgres', 'redis', 'queue']),
                'raw_line' => fake()->sentence(15),
            ],
        ]);
    }

    /**
     * Create a Nginx log entry.
     */
    public function nginx(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_type' => SystemLog::TYPE_NGINX,
            'source' => 'nginx',
            'message' => fake()->randomElement([
                '[error] 1234#1234: *1 connect() failed (111: Connection refused)',
                '[warn] 1234#1234: conflicting server name',
                '[notice] signal process started',
            ]),
        ]);
    }

    /**
     * Create logs for a specific server.
     */
    public function forServer(Server $server): static
    {
        return $this->state(fn (array $attributes) => [
            'server_id' => $server->id,
        ]);
    }

    /**
     * Create recent logs.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'logged_at' => fake()->dateTimeBetween('-24 hours', 'now'),
        ]);
    }
}
