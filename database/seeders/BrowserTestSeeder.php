<?php

namespace Database\Seeders;

use App\Models\Deployment;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * BrowserTestSeeder - Seeds essential test data for browser tests
 *
 * This seeder creates the minimum required data for browser tests to run without being skipped.
 * It creates:
 * - A test admin user
 * - A test server
 * - Multiple test projects with different states
 * - Deployments in various states (success, failed, running, pending)
 * - Domains for projects
 */
class BrowserTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get the test admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create test servers
        $server1 = Server::firstOrCreate(
            ['hostname' => 'test-server-1.example.com'],
            [
                'user_id' => $user->id,
                'name' => 'Test Server 1',
                'ip_address' => '192.168.1.100',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
                'os' => 'Ubuntu',
                'cpu_cores' => 4,
                'memory_gb' => 8,
                'disk_gb' => 100,
                'docker_installed' => true,
                'docker_version' => '24.0.7',
                'last_ping_at' => now(),
            ]
        );

        $server2 = Server::firstOrCreate(
            ['hostname' => 'test-server-2.example.com'],
            [
                'user_id' => $user->id,
                'name' => 'Test Server 2',
                'ip_address' => '192.168.1.101',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
                'os' => 'Ubuntu',
                'cpu_cores' => 8,
                'memory_gb' => 16,
                'disk_gb' => 200,
                'docker_installed' => true,
                'docker_version' => '24.0.7',
                'last_ping_at' => now(),
            ]
        );

        // Create test projects with different configurations
        $projects = [
            [
                'name' => 'Laravel Test Project',
                'slug' => 'laravel-test-project',
                'framework' => 'laravel',
                'status' => 'running',
                'server' => $server1,
                'with_domains' => true,
            ],
            [
                'name' => 'Shopware Test Project',
                'slug' => 'shopware-test-project',
                'framework' => 'shopware',
                'status' => 'stopped',
                'server' => $server1,
                'with_domains' => true,
            ],
            [
                'name' => 'Multi-Tenant Test Project',
                'slug' => 'multi-tenant-test-project',
                'framework' => 'laravel',
                'status' => 'running',
                'server' => $server2,
                'project_type' => 'multi_tenant',
                'with_domains' => true,
            ],
            [
                'name' => 'SaaS Test Project',
                'slug' => 'saas-test-project',
                'framework' => 'laravel',
                'status' => 'running',
                'server' => $server2,
                'project_type' => 'saas',
                'with_domains' => false,
            ],
        ];

        foreach ($projects as $projectData) {
            $project = Project::firstOrCreate(
                ['slug' => $projectData['slug']],
                [
                    'user_id' => $user->id,
                    'server_id' => $projectData['server']->id,
                    'name' => $projectData['name'],
                    'framework' => $projectData['framework'],
                    'status' => $projectData['status'],
                    'project_type' => $projectData['project_type'] ?? 'single_tenant',
                    'repository_url' => 'https://github.com/test/' . $projectData['slug'] . '.git',
                    'branch' => 'main',
                    'root_directory' => '/var/www/' . $projectData['slug'],
                    'php_version' => '8.4',
                    'environment' => 'production',
                    'env_variables' => json_encode([
                        'APP_NAME' => $projectData['name'],
                        'APP_ENV' => 'production',
                        'APP_DEBUG' => 'false',
                    ]),
                    'last_deployed_at' => now()->subDays(rand(1, 7)),
                    'setup_status' => 'completed',
                    'setup_completed_at' => now()->subDays(rand(7, 30)),
                    'current_commit_hash' => fake()->sha1(),
                    'current_commit_message' => 'Initial commit for ' . $projectData['name'],
                    'last_commit_at' => now()->subDays(rand(1, 7)),
                ]
            );

            // Create domains if specified
            if ($projectData['with_domains']) {
                // Create primary domain
                Domain::firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'domain' => strtolower(str_replace(' ', '-', $projectData['name'])) . '.test.com',
                    ],
                    [
                        'is_primary' => true,
                        'ssl_enabled' => true,
                        'ssl_provider' => 'letsencrypt',
                        'dns_configured' => true,
                        'status' => 'active',
                        'ssl_issued_at' => now()->subDays(30),
                        'ssl_expires_at' => now()->addDays(60),
                        'auto_renew_ssl' => true,
                    ]
                );

                // Create secondary domain
                Domain::firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'domain' => 'www.' . strtolower(str_replace(' ', '-', $projectData['name'])) . '.test.com',
                    ],
                    [
                        'is_primary' => false,
                        'ssl_enabled' => false,
                        'dns_configured' => true,
                        'status' => 'active',
                    ]
                );
            }

            // Create deployments for the project in various states
            $this->createDeploymentsForProject($project, $user, $projectData['server']);
        }

        $this->command->info('Browser test data seeded successfully!');
        $this->command->info('Test user: admin@devflow.test / password');
        $this->command->info('Created ' . Project::count() . ' projects');
        $this->command->info('Created ' . Deployment::count() . ' deployments');
        $this->command->info('Created ' . Domain::count() . ' domains');
    }

    /**
     * Create various deployments for a project
     */
    private function createDeploymentsForProject(Project $project, User $user, Server $server): void
    {
        // Create a successful deployment
        Deployment::firstOrCreate(
            [
                'project_id' => $project->id,
                'commit_hash' => fake()->sha1(),
                'status' => 'success',
            ],
            [
                'user_id' => $user->id,
                'server_id' => $server->id,
                'branch' => 'main',
                'commit_message' => 'Successfully deployed feature update',
                'triggered_by' => 'manual',
                'started_at' => now()->subHours(2),
                'completed_at' => now()->subHours(2)->addMinutes(5),
                'duration_seconds' => 300,
                'output_log' => "=== Starting Deployment ===\n" .
                    "✓ Cloning repository\n" .
                    "✓ Installing dependencies\n" .
                    "✓ Building Docker containers\n" .
                    "✓ Running migrations\n" .
                    "✓ Clearing cache\n" .
                    "=== Deployment Successful ===",
            ]
        );

        // Create a failed deployment
        Deployment::firstOrCreate(
            [
                'project_id' => $project->id,
                'commit_hash' => fake()->sha1(),
                'status' => 'failed',
            ],
            [
                'user_id' => $user->id,
                'server_id' => $server->id,
                'branch' => 'main',
                'commit_message' => 'Fix authentication bug',
                'triggered_by' => 'webhook',
                'started_at' => now()->subDays(1),
                'completed_at' => now()->subDays(1)->addMinutes(3),
                'duration_seconds' => 180,
                'output_log' => "=== Starting Deployment ===\n" .
                    "✓ Cloning repository\n" .
                    "✓ Installing dependencies\n" .
                    "✗ Building Docker containers\n" .
                    "ERROR: Docker build failed",
                'error_log' => 'Docker build failed: out of memory\n' .
                    'Container startup error on line 45\n' .
                    'Failed to allocate sufficient resources',
            ]
        );

        // Create a running deployment
        Deployment::firstOrCreate(
            [
                'project_id' => $project->id,
                'commit_hash' => fake()->sha1(),
                'status' => 'running',
            ],
            [
                'user_id' => $user->id,
                'server_id' => $server->id,
                'branch' => 'main',
                'commit_message' => 'Update dependencies and security patches',
                'triggered_by' => 'manual',
                'started_at' => now()->subMinutes(2),
                'completed_at' => null,
                'duration_seconds' => null,
                'output_log' => "=== Starting Deployment ===\n" .
                    "✓ Cloning repository\n" .
                    "✓ Installing dependencies\n" .
                    "=== Building Docker Container ===\n" .
                    "Building image...",
            ]
        );

        // Create a pending deployment
        Deployment::firstOrCreate(
            [
                'project_id' => $project->id,
                'commit_hash' => fake()->sha1(),
                'status' => 'pending',
            ],
            [
                'user_id' => $user->id,
                'server_id' => $server->id,
                'branch' => 'develop',
                'commit_message' => 'Pending deployment from develop branch',
                'triggered_by' => 'scheduled',
                'started_at' => null,
                'completed_at' => null,
                'duration_seconds' => null,
                'output_log' => null,
            ]
        );

        // Create another successful deployment (older)
        Deployment::firstOrCreate(
            [
                'project_id' => $project->id,
                'commit_hash' => fake()->sha1(),
                'status' => 'success',
                'started_at' => now()->subDays(3),
            ],
            [
                'user_id' => $user->id,
                'server_id' => $server->id,
                'branch' => 'main',
                'commit_message' => 'Initial production deployment',
                'triggered_by' => 'manual',
                'completed_at' => now()->subDays(3)->addMinutes(8),
                'duration_seconds' => 480,
                'output_log' => "=== Starting Deployment ===\n" .
                    "✓ Cloning repository\n" .
                    "✓ Installing dependencies\n" .
                    "✓ Building Docker containers\n" .
                    "✓ Running migrations\n" .
                    "✓ Seeding database\n" .
                    "✓ Clearing cache\n" .
                    "=== Deployment Successful ===",
            ]
        );
    }
}
