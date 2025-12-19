<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProjectTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectTemplate>
 */
class ProjectTemplateFactory extends Factory
{
    protected $model = ProjectTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $framework = fake()->randomElement(['laravel', 'react', 'vue', 'nextjs', 'nodejs', 'static']);
        $uniqueSuffix = fake()->unique()->numberBetween(10000, 99999);
        $name = ucfirst($framework) . ' Template ' . $uniqueSuffix;

        return [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'description' => fake()->sentence(),
            'framework' => $framework,
            'icon' => fake()->randomElement(['🚀', '⚡', '🎯', '🔥', '💻']),
            'color' => fake()->hexColor(),
            'is_system' => false,
            'is_active' => true,
            'user_id' => null,
            'default_branch' => 'main',
            'php_version' => $framework === 'laravel' ? '8.3' : null,
            'node_version' => in_array($framework, ['react', 'vue', 'nextjs', 'nodejs']) ? '20' : null,
            'install_commands' => $this->getInstallCommands($framework),
            'build_commands' => $this->getBuildCommands($framework),
            'post_deploy_commands' => $this->getPostDeployCommands($framework),
            'env_template' => $this->getEnvTemplate($framework),
            'docker_compose_template' => null,
            'dockerfile_template' => null,
            'health_check_path' => '/health',
        ];
    }

    /**
     * Indicate that the template is a system template.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
            'user_id' => null,
        ]);
    }

    /**
     * Indicate that the template is a custom user template.
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => false,
            'user_id' => User::factory(),
        ]);
    }

    /**
     * Indicate that the template is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the template is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a Laravel template.
     */
    public function laravel(): static
    {
        $uniqueSuffix = fake()->unique()->numberBetween(10000, 99999);

        return $this->state(fn (array $attributes) => [
            'name' => 'Laravel Template ' . $uniqueSuffix,
            'slug' => 'laravel-template-' . $uniqueSuffix,
            'framework' => 'laravel',
            'php_version' => '8.4',
            'node_version' => '20',
            'install_commands' => [
                'composer install --optimize-autoloader --no-dev',
                'npm install',
            ],
            'build_commands' => [
                'npm run build',
                'php artisan optimize',
            ],
            'post_deploy_commands' => [
                'php artisan migrate --force',
                'php artisan config:cache',
                'php artisan route:cache',
                'php artisan view:cache',
            ],
            'health_check_path' => '/api/health',
        ]);
    }

    /**
     * Create a React template.
     */
    public function react(): static
    {
        $uniqueSuffix = fake()->unique()->numberBetween(10000, 99999);

        return $this->state(fn (array $attributes) => [
            'name' => 'React Template ' . $uniqueSuffix,
            'slug' => 'react-template-' . $uniqueSuffix,
            'framework' => 'react',
            'php_version' => null,
            'node_version' => '20',
            'install_commands' => ['npm install'],
            'build_commands' => ['npm run build'],
            'post_deploy_commands' => [],
            'health_check_path' => '/',
        ]);
    }

    /**
     * Create a Vue template.
     */
    public function vue(): static
    {
        $uniqueSuffix = fake()->unique()->numberBetween(10000, 99999);

        return $this->state(fn (array $attributes) => [
            'name' => 'Vue Template ' . $uniqueSuffix,
            'slug' => 'vue-template-' . $uniqueSuffix,
            'framework' => 'vue',
            'php_version' => null,
            'node_version' => '20',
            'install_commands' => ['npm install'],
            'build_commands' => ['npm run build'],
            'post_deploy_commands' => [],
            'health_check_path' => '/',
        ]);
    }

    /**
     * Create a Next.js template.
     */
    public function nextjs(): static
    {
        $uniqueSuffix = fake()->unique()->numberBetween(10000, 99999);

        return $this->state(fn (array $attributes) => [
            'name' => 'Next.js Template ' . $uniqueSuffix,
            'slug' => 'nextjs-template-' . $uniqueSuffix,
            'framework' => 'nextjs',
            'php_version' => null,
            'node_version' => '20',
            'install_commands' => ['npm install'],
            'build_commands' => ['npm run build'],
            'post_deploy_commands' => [],
            'health_check_path' => '/api/health',
        ]);
    }

    /**
     * Get install commands based on framework.
     *
     * @return array<int, string>
     */
    private function getInstallCommands(string $framework): array
    {
        return match($framework) {
            'laravel' => ['composer install', 'npm install'],
            'react', 'vue', 'nextjs', 'nodejs' => ['npm install'],
            'static' => [],
            default => ['npm install'],
        };
    }

    /**
     * Get build commands based on framework.
     *
     * @return array<int, string>
     */
    private function getBuildCommands(string $framework): array
    {
        return match($framework) {
            'laravel' => ['npm run build', 'php artisan optimize'],
            'react', 'vue', 'nextjs' => ['npm run build'],
            'nodejs' => [],
            'static' => [],
            default => ['npm run build'],
        };
    }

    /**
     * Get post-deploy commands based on framework.
     *
     * @return array<int, string>
     */
    private function getPostDeployCommands(string $framework): array
    {
        return match($framework) {
            'laravel' => ['php artisan migrate --force', 'php artisan config:cache'],
            default => [],
        };
    }

    /**
     * Get environment template based on framework.
     *
     * @return array<string, string>|null
     */
    private function getEnvTemplate(string $framework): ?array
    {
        return match($framework) {
            'laravel' => [
                'APP_NAME' => 'Laravel',
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_URL' => 'https://example.com',
            ],
            'react', 'vue' => [
                'VITE_APP_NAME' => 'App',
                'VITE_API_URL' => 'https://api.example.com',
            ],
            'nextjs' => [
                'NEXT_PUBLIC_API_URL' => 'https://api.example.com',
            ],
            default => null,
        };
    }
}
