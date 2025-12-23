<?php

namespace Database\Factories;

use App\Models\FailedJob;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FailedJob>
 */
class FailedJobFactory extends Factory
{
    protected $model = FailedJob::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jobClasses = [
            'App\\Jobs\\ProcessDeployment',
            'App\\Jobs\\SendNotification',
            'App\\Jobs\\GenerateReport',
            'App\\Jobs\\BackupDatabase',
            'App\\Jobs\\CleanupLogs',
            'App\\Jobs\\SyncFiles',
        ];

        $exceptions = [
            "RuntimeException: Database connection failed in /app/Jobs/BackupDatabase.php:45\nStack trace:\n#0 /vendor/laravel/framework/src/Illuminate/Queue/Worker.php(439): App\\Jobs\\BackupDatabase->handle()\n#1 /vendor/laravel/framework/src/Illuminate/Queue/Worker.php(389): Illuminate\\Queue\\Worker->process()\n",
            "ErrorException: Disk is full in /app/Services/FileService.php:123\nStack trace:\n#0 /app/Jobs/SyncFiles.php(56): App\\Services\\FileService->upload()\n#1 /vendor/laravel/framework/src/Illuminate/Queue/Worker.php(439): App\\Jobs\\SyncFiles->handle()\n",
            "Exception: External API timeout in /app/Services/NotificationService.php:89\nStack trace:\n#0 /app/Jobs/SendNotification.php(34): App\\Services\\NotificationService->send()\n#1 /vendor/laravel/framework/src/Illuminate/Queue/Worker.php(439): App\\Jobs\\SendNotification->handle()\n",
            "PDOException: SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded in /app/Jobs/ProcessDeployment.php:78\nStack trace:\n#0 /vendor/laravel/framework/src/Illuminate/Database/Connection.php(742): PDO->query()\n",
            "GuzzleHttp\\Exception\\ConnectException: Connection refused in /app/Services/ApiService.php:45\nStack trace:\n#0 /vendor/guzzlehttp/guzzle/src/Handler/CurlHandler.php(43): GuzzleHttp\\Handler\\CurlHandler->__invoke()\n",
        ];

        $jobClass = fake()->randomElement($jobClasses);
        $exception = fake()->randomElement($exceptions);

        $command = new \stdClass();
        $command->class = $jobClass;

        return [
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => fake()->randomElement(['default', 'emails', 'notifications', 'deployments', 'backups']),
            'payload' => json_encode([
                'uuid' => (string) Str::uuid(),
                'displayName' => $jobClass,
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'maxTries' => 3,
                'maxExceptions' => null,
                'failOnTimeout' => false,
                'backoff' => null,
                'timeout' => null,
                'retryUntil' => null,
                'data' => [
                    'commandName' => $jobClass,
                    'command' => serialize($command),
                ],
            ]),
            'exception' => $exception,
            'failed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the job failed recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'failed_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the job failed on a specific queue.
     */
    public function onQueue(string $queue): static
    {
        return $this->state(fn (array $attributes) => [
            'queue' => $queue,
        ]);
    }

    /**
     * Indicate that the job is a deployment job.
     */
    public function deployment(): static
    {
        return $this->state(function (array $attributes) {
            $command = new \stdClass();
            $command->class = 'App\\Jobs\\ProcessDeployment';

            return [
                'queue' => 'deployments',
                'payload' => json_encode([
                    'uuid' => (string) Str::uuid(),
                    'displayName' => 'App\\Jobs\\ProcessDeployment',
                    'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                    'maxTries' => 3,
                    'data' => [
                        'commandName' => 'App\\Jobs\\ProcessDeployment',
                        'command' => serialize($command),
                    ],
                ]),
                'exception' => "RuntimeException: Deployment failed: Git pull error\nStack trace:\n#0 /app/Jobs/ProcessDeployment.php:56): App\\Services\\GitService->pull()\n",
            ];
        });
    }

    /**
     * Indicate that the job is a notification job.
     */
    public function notification(): static
    {
        return $this->state(function (array $attributes) {
            $command = new \stdClass();
            $command->class = 'App\\Jobs\\SendNotification';

            return [
                'queue' => 'notifications',
                'payload' => json_encode([
                    'uuid' => (string) Str::uuid(),
                    'displayName' => 'App\\Jobs\\SendNotification',
                    'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                    'maxTries' => 3,
                    'data' => [
                        'commandName' => 'App\\Jobs\\SendNotification',
                        'command' => serialize($command),
                    ],
                ]),
                'exception' => "Exception: Notification service unavailable\nStack trace:\n#0 /app/Jobs/SendNotification.php:34): App\\Services\\NotificationService->send()\n",
            ];
        });
    }
}
