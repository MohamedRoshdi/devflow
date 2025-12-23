<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uniqueId = fake()->unique()->numerify('######');
        $name = fake()->words(2, true).' Project '.$uniqueId;

        return [
            'user_id' => User::factory(),
            'server_id' => Server::factory(),
            'team_id' => null,
            'template_id' => null,
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'repository_url' => 'https://github.com/'.fake()->userName().'/'.fake()->slug(2).'.git',
            'branch' => 'main',
            'framework' => fake()->randomElement(['laravel', 'symfony', 'wordpress', 'shopware', 'custom']),
            'project_type' => fake()->randomElement(['single_tenant', 'multi_tenant', 'saas', 'microservice']),
            'environment' => fake()->randomElement(['development', 'staging', 'production']),
            'php_version' => fake()->randomElement(['8.1', '8.2', '8.3', '8.4']),
            'node_version' => fake()->optional()->randomElement(['18', '20', '22']),
            'port' => fake()->numberBetween(3000, 9000),
            'root_directory' => '/var/www/html',
            'build_command' => 'npm run build',
            'start_command' => 'npm start',
            'install_commands' => json_encode(['composer install', 'npm install']),
            'build_commands' => json_encode(['npm run build']),
            'post_deploy_commands' => json_encode(['php artisan migrate --force']),
            'env_variables' => json_encode([
                'APP_NAME' => $name,
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
            ]),
            'status' => fake()->randomElement(['running', 'stopped', 'building', 'error', 'deploying']),
            'setup_status' => 'completed',
            'setup_config' => null,
            'setup_completed_at' => now(),
            'health_check_url' => fake()->optional()->url(),
            'last_deployed_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
            'storage_used_mb' => fake()->numberBetween(100, 5000),
            'latitude' => fake()->optional()->latitude(),
            'longitude' => fake()->optional()->longitude(),
            'auto_deploy' => fake()->boolean(30),
            'webhook_secret' => fake()->optional()->sha256(),
            'webhook_enabled' => fake()->boolean(50),
            'metadata' => json_encode([]),
            'current_commit_hash' => fake()->sha1(),
            'current_commit_message' => fake()->sentence(),
            'last_commit_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Indicate that the project is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'last_deployed_at' => now(),
        ]);
    }

    /**
     * Indicate that the project is stopped.
     */
    public function stopped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'stopped',
        ]);
    }

    /**
     * Indicate that the project is a Laravel project.
     */
    public function laravel(): static
    {
        return $this->state(fn (array $attributes) => [
            'framework' => 'laravel',
            'php_version' => '8.4',
        ]);
    }
}
