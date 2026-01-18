<?php

namespace Database\Seeders;

use App\Models\ProjectTemplate;
use Illuminate\Database\Seeder;

class ProjectTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Laravel',
                'slug' => 'laravel',
                'description' => 'Full-stack PHP framework with elegant syntax and powerful features',
                'framework' => 'laravel',
                'icon' => 'laravel',
                'color' => 'red',
                'is_system' => true,
                'default_branch' => 'main',
                'php_version' => '8.2',
                'node_version' => '20',
                'install_commands' => [
                    'composer install --no-dev --optimize-autoloader',
                    'npm install',
                ],
                'build_commands' => [
                    'npm run build',
                ],
                'post_deploy_commands' => [
                    'php artisan config:cache',
                    'php artisan route:cache',
                    'php artisan view:cache',
                    'php artisan migrate --force',
                ],
                'env_template' => [
                    'APP_NAME' => '${PROJECT_NAME}',
                    'APP_ENV' => 'production',
                    'APP_DEBUG' => 'false',
                    'APP_URL' => 'https://${DOMAIN}',
                    'DB_CONNECTION' => 'mysql',
                    'DB_HOST' => 'mysql',
                    'DB_PORT' => '3306',
                    'CACHE_DRIVER' => 'redis',
                    'SESSION_DRIVER' => 'redis',
                    'QUEUE_CONNECTION' => 'redis',
                ],
                'health_check_path' => '/api/health',
            ],
            [
                'name' => 'Node.js / Express',
                'slug' => 'nodejs-express',
                'description' => 'Fast, minimalist web framework for Node.js applications',
                'framework' => 'nodejs',
                'icon' => 'nodejs',
                'color' => 'green',
                'is_system' => true,
                'default_branch' => 'main',
                'node_version' => '20',
                'install_commands' => [
                    'npm install --production',
                ],
                'build_commands' => [
                    'npm run build || true',
                ],
                'post_deploy_commands' => [
                    'npm run migrate || true',
                ],
                'env_template' => [
                    'NODE_ENV' => 'production',
                    'PORT' => '3000',
                    'DATABASE_URL' => '${DATABASE_URL}',
                ],
                'health_check_path' => '/health',
            ],
            [
                'name' => 'Next.js',
                'slug' => 'nextjs',
                'description' => 'React framework for production with server-side rendering',
                'framework' => 'nextjs',
                'icon' => 'nextjs',
                'color' => 'gray',
                'is_system' => true,
                'default_branch' => 'main',
                'node_version' => '20',
                'install_commands' => [
                    'npm install',
                ],
                'build_commands' => [
                    'npm run build',
                ],
                'post_deploy_commands' => [],
                'env_template' => [
                    'NODE_ENV' => 'production',
                    'NEXT_PUBLIC_API_URL' => 'https://${DOMAIN}/api',
                ],
                'health_check_path' => '/api/health',
            ],
            [
                'name' => 'Nuxt.js',
                'slug' => 'nuxtjs',
                'description' => 'Vue.js framework for server-side rendering and static sites',
                'framework' => 'nuxtjs',
                'icon' => 'vuejs',
                'color' => 'emerald',
                'is_system' => true,
                'default_branch' => 'main',
                'node_version' => '20',
                'install_commands' => [
                    'npm install',
                ],
                'build_commands' => [
                    'npm run build',
                ],
                'post_deploy_commands' => [],
                'env_template' => [
                    'NODE_ENV' => 'production',
                    'NUXT_PUBLIC_API_BASE' => 'https://${DOMAIN}/api',
                ],
                'health_check_path' => '/api/health',
            ],
            [
                'name' => 'Static Site',
                'slug' => 'static',
                'description' => 'Simple static website with HTML, CSS, and JavaScript',
                'framework' => 'static',
                'icon' => 'html',
                'color' => 'orange',
                'is_system' => true,
                'default_branch' => 'main',
                'install_commands' => [],
                'build_commands' => [],
                'post_deploy_commands' => [],
                'env_template' => [],
                'health_check_path' => '/',
            ],
            [
                'name' => 'Python / Django',
                'slug' => 'python-django',
                'description' => 'High-level Python web framework for rapid development',
                'framework' => 'python',
                'icon' => 'python',
                'color' => 'blue',
                'is_system' => true,
                'default_branch' => 'main',
                'install_commands' => [
                    'pip install -r requirements.txt',
                ],
                'build_commands' => [
                    'python manage.py collectstatic --noinput',
                ],
                'post_deploy_commands' => [
                    'python manage.py migrate --noinput',
                ],
                'env_template' => [
                    'DJANGO_ENV' => 'production',
                    'DEBUG' => 'False',
                    'ALLOWED_HOSTS' => '${DOMAIN}',
                    'DATABASE_URL' => '${DATABASE_URL}',
                ],
                'health_check_path' => '/health/',
            ],
            [
                'name' => 'Go / Gin',
                'slug' => 'go-gin',
                'description' => 'High-performance HTTP web framework written in Go',
                'framework' => 'go',
                'icon' => 'go',
                'color' => 'cyan',
                'is_system' => true,
                'default_branch' => 'main',
                'install_commands' => [
                    'go mod download',
                ],
                'build_commands' => [
                    'go build -o app .',
                ],
                'post_deploy_commands' => [],
                'env_template' => [
                    'GIN_MODE' => 'release',
                    'PORT' => '8080',
                ],
                'health_check_path' => '/health',
            ],
            [
                'name' => 'Custom',
                'slug' => 'custom',
                'description' => 'Blank template for custom project configurations',
                'framework' => 'custom',
                'icon' => 'code',
                'color' => 'purple',
                'is_system' => true,
                'default_branch' => 'main',
                'install_commands' => [],
                'build_commands' => [],
                'post_deploy_commands' => [],
                'env_template' => [],
                'health_check_path' => null,
            ],
        ];

        foreach ($templates as $template) {
            ProjectTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
