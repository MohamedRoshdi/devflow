<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * IMPORTANT: This seeder MUST include DefaultPermissionsSeeder to ensure
     * the system has proper roles and permissions. Without them, all actions
     * will return 403 Forbidden errors.
     */
    public function run(): void
    {
        $this->call([
            // CRITICAL: Permissions and roles MUST be seeded first
            DefaultPermissionsSeeder::class,

            // Other seeders
            ProjectTemplateSeeder::class,
            SystemSettingsSeeder::class,
        ]);

        // Create default super-admin user if no users exist
        $this->createDefaultAdminUser();
    }

    /**
     * Create a default super-admin user for initial system access.
     */
    private function createDefaultAdminUser(): void
    {
        if (User::count() > 0) {
            $this->command->info('Users already exist, skipping default admin creation.');

            return;
        }

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@devflow.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Assign super-admin role
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $admin->assignRole($superAdminRole);
            $this->command->info('Default super-admin user created: admin@devflow.local / password');
            $this->command->warn('IMPORTANT: Change this password immediately after first login!');
        } else {
            $this->command->error('super-admin role not found! Run DefaultPermissionsSeeder first.');
        }
    }
}
