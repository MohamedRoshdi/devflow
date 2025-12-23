<?php

declare(strict_types=1);

namespace Tests\Unit\Seeders;

use App\Models\User;
use Database\Seeders\CollaborationPermissionsSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DefaultPermissionsSeeder;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class RolesPermissionsSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear permission cache before each test
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clean up permissions and roles for isolated tests
        // Note: Using transactions will roll these back after each test
        $this->cleanupPermissionsAndRoles();
    }

    protected function cleanupPermissionsAndRoles(): void
    {
        // Clear pivot table first
        \DB::table('role_has_permissions')->delete();
        \DB::table('model_has_permissions')->delete();
        \DB::table('model_has_roles')->delete();

        // Then clear roles and permissions
        Role::query()->delete();
        Permission::query()->delete();
    }

    /**
     * Create a mock command that can be used with seeders.
     */
    protected function createMockCommand(): Command
    {
        $command = \Mockery::mock(Command::class);
        $command->shouldReceive('info')->andReturnNull();
        $command->shouldReceive('warn')->andReturnNull();
        $command->shouldReceive('error')->andReturnNull();
        $command->shouldReceive('getOutput')->andReturn(new NullOutput);

        return $command;
    }

    /**
     * Run a seeder with a mock command.
     */
    protected function runSeeder(string $seederClass): void
    {
        $seeder = new $seederClass;
        $seeder->setCommand($this->createMockCommand());
        $seeder->run();
    }

    // ========================================
    // DEFAULT PERMISSIONS SEEDER TESTS
    // ========================================

    #[Test]
    public function default_permissions_seeder_creates_all_core_permissions(): void
    {
        $this->runSeeder(DefaultPermissionsSeeder::class);

        // Check core permissions exist
        $corePermissions = [
            'view-projects',
            'create-projects',
            'edit-projects',
            'delete-projects',
            'deploy-projects',
            'view-servers',
            'create-servers',
            'edit-servers',
            'delete-servers',
            'manage-server-security',
            'view-deployments',
            'create-deployments',
            'approve-deployments',
            'rollback-deployments',
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'manage-roles',
            'assign-roles',
            'manage-permissions',
        ];

        foreach ($corePermissions as $permission) {
            $this->assertTrue(
                Permission::where('name', $permission)->exists(),
                "Permission '{$permission}' should exist"
            );
        }
    }

    #[Test]
    public function default_permissions_seeder_creates_all_roles(): void
    {
        $this->runSeeder(DefaultPermissionsSeeder::class);

        $expectedRoles = ['super-admin', 'admin', 'manager', 'developer', 'viewer'];

        foreach ($expectedRoles as $roleName) {
            $this->assertTrue(
                Role::where('name', $roleName)->exists(),
                "Role '{$roleName}' should exist"
            );
        }
    }

    #[Test]
    public function super_admin_role_has_all_permissions(): void
    {
        $this->runSeeder(DefaultPermissionsSeeder::class);

        $superAdmin = Role::findByName('super-admin');
        $totalPermissions = Permission::count();

        $this->assertEquals(
            $totalPermissions,
            $superAdmin->permissions->count(),
            'Super-admin should have all permissions'
        );
    }

    #[Test]
    public function viewer_role_has_only_view_permissions(): void
    {
        $this->runSeeder(DefaultPermissionsSeeder::class);

        $viewer = Role::findByName('viewer');
        $permissions = $viewer->permissions->pluck('name')->toArray();

        // Viewer should have view permissions
        $this->assertContains('view-projects', $permissions);
        $this->assertContains('view-servers', $permissions);
        $this->assertContains('view-deployments', $permissions);

        // Viewer should NOT have create/edit/delete permissions
        $this->assertNotContains('create-projects', $permissions);
        $this->assertNotContains('edit-projects', $permissions);
        $this->assertNotContains('delete-projects', $permissions);
        $this->assertNotContains('deploy-projects', $permissions);
    }

    #[Test]
    public function developer_role_can_deploy_but_not_delete(): void
    {
        $this->runSeeder(DefaultPermissionsSeeder::class);

        $developer = Role::findByName('developer');
        $permissions = $developer->permissions->pluck('name')->toArray();

        // Developer should be able to deploy
        $this->assertContains('deploy-projects', $permissions);
        $this->assertContains('view-projects', $permissions);

        // Developer should NOT be able to delete
        $this->assertNotContains('delete-projects', $permissions);
        $this->assertNotContains('delete-servers', $permissions);
    }

    #[Test]
    public function seeder_is_idempotent(): void
    {
        // Run twice
        $this->runSeeder(DefaultPermissionsSeeder::class);
        $firstRunPermissions = Permission::count();
        $firstRunRoles = Role::count();

        $this->runSeeder(DefaultPermissionsSeeder::class);
        $secondRunPermissions = Permission::count();
        $secondRunRoles = Role::count();

        $this->assertEquals($firstRunPermissions, $secondRunPermissions);
        $this->assertEquals($firstRunRoles, $secondRunRoles);
    }

    // ========================================
    // COLLABORATION PERMISSIONS SEEDER TESTS
    // ========================================

    #[Test]
    public function collaboration_seeder_adds_permissions_without_removing_existing(): void
    {
        // First run DefaultPermissionsSeeder
        $this->runSeeder(DefaultPermissionsSeeder::class);

        $managerBefore = Role::findByName('manager');
        $permissionsBefore = $managerBefore->permissions->count();

        // Now run CollaborationPermissionsSeeder
        $this->runSeeder(CollaborationPermissionsSeeder::class);

        $managerAfter = Role::findByName('manager');
        $permissionsAfter = $managerAfter->permissions->count();

        // Should have MORE permissions after running collaboration seeder
        $this->assertGreaterThanOrEqual($permissionsBefore, $permissionsAfter);

        // Original permissions should still exist
        $permissions = $managerAfter->permissions->pluck('name')->toArray();
        $this->assertContains('view-projects', $permissions);
        $this->assertContains('deploy-projects', $permissions);
    }

    #[Test]
    public function collaboration_seeder_creates_collaboration_specific_permissions(): void
    {
        // First run DefaultPermissionsSeeder
        $this->runSeeder(DefaultPermissionsSeeder::class);

        // Now run CollaborationPermissionsSeeder
        $this->runSeeder(CollaborationPermissionsSeeder::class);

        $collaborationPermissions = [
            'approve_deployments',
            'approve_all_deployments',
            'request_deployment_approval',
            'add_deployment_comments',
            'edit_own_comments',
            'delete_own_comments',
            'manage_all_comments',
        ];

        foreach ($collaborationPermissions as $permission) {
            $this->assertTrue(
                Permission::where('name', $permission)->exists(),
                "Collaboration permission '{$permission}' should exist"
            );
        }
    }

    // ========================================
    // DATABASE SEEDER TESTS
    // ========================================

    #[Test]
    public function database_seeder_creates_default_admin_user(): void
    {
        // Ensure no users exist
        User::query()->delete();

        $this->runSeeder(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@devflow.local')->first();

        $this->assertNotNull($admin, 'Default admin user should be created');
        $this->assertEquals('Admin', $admin->name);
        $this->assertTrue($admin->hasRole('super-admin'), 'Default admin should have super-admin role');
    }

    #[Test]
    public function database_seeder_does_not_create_admin_if_users_exist(): void
    {
        // Create an existing user first
        User::factory()->create(['email' => 'existing@example.com']);

        $this->runSeeder(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@devflow.local')->first();

        $this->assertNull($admin, 'Default admin should not be created if users already exist');
    }

    #[Test]
    public function database_seeder_runs_permissions_seeder_first(): void
    {
        // Ensure no permissions exist
        Permission::query()->delete();
        Role::query()->delete();
        User::query()->delete();

        $this->runSeeder(DatabaseSeeder::class);

        // Permissions and roles should exist
        $this->assertGreaterThan(0, Permission::count());
        $this->assertGreaterThan(0, Role::count());

        // Super-admin role should exist for the admin user
        $this->assertTrue(Role::where('name', 'super-admin')->exists());
    }

    #[Test]
    public function fresh_database_setup_prevents_403_errors(): void
    {
        // Simulate a fresh database setup
        Permission::query()->delete();
        Role::query()->delete();
        User::query()->delete();

        $this->runSeeder(DatabaseSeeder::class);

        // Get the created admin user
        $admin = User::where('email', 'admin@devflow.local')->first();

        // Admin should have necessary permissions through super-admin role
        $this->assertTrue($admin->hasRole('super-admin'));
        $this->assertTrue($admin->can('view-projects'));
        $this->assertTrue($admin->can('create-projects'));
        $this->assertTrue($admin->can('view-servers'));
        $this->assertTrue($admin->can('manage-roles'));
    }
}
