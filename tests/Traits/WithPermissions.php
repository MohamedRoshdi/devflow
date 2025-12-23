<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait WithPermissions
{
    /**
     * Set up permissions and roles for testing.
     */
    protected function setUpPermissions(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all required permissions
        $permissions = [
            // Projects permissions
            'view-projects',
            'create-projects',
            'edit-projects',
            'delete-projects',
            'deploy-projects',

            // Servers permissions
            'view-servers',
            'create-servers',
            'edit-servers',
            'delete-servers',
            'manage-server-security',

            // Deployments permissions
            'view-deployments',
            'create-deployments',
            'approve-deployments',
            'rollback-deployments',

            // Users permissions
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // Roles & Permissions
            'manage-roles',
            'assign-roles',
            'manage-permissions',

            // Settings permissions
            'view-settings',
            'edit-settings',
            'manage-system-settings',

            // Analytics permissions
            'view-analytics',
            'export-analytics',

            // Logs permissions
            'view-logs',
            'delete-logs',
            'export-logs',

            // Docker permissions
            'manage-docker',
            'view-docker-logs',

            // Kubernetes permissions
            'manage-kubernetes',
            'view-kubernetes-logs',

            // Pipelines permissions
            'view-pipelines',
            'create-pipelines',
            'edit-pipelines',
            'delete-pipelines',
            'execute-pipelines',

            // Scripts permissions
            'view-scripts',
            'create-scripts',
            'edit-scripts',
            'delete-scripts',
            'execute-scripts',

            // Notifications permissions
            'view-notifications',
            'manage-notification-channels',

            // Multi-tenant permissions
            'manage-tenants',
            'view-tenant-data',

            // Backups permissions
            'view-backups',
            'create-backups',
            'restore-backups',
            'delete-backups',

            // Domains permissions
            'view-domains',
            'create-domains',
            'edit-domains',
            'delete-domains',
            'manage-ssl',

            // Health Checks permissions
            'view-health-checks',
            'manage-health-checks',

            // Webhooks permissions
            'view-webhooks',
            'manage-webhooks',

            // Teams permissions
            'view-teams',
            'create-teams',
            'edit-teams',
            'delete-teams',
            'manage-team-members',

            // Audit permissions
            'view-audit-logs',
            'export-audit-logs',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create super-admin role with all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Create admin role
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // Create developer role
        $developer = Role::firstOrCreate(['name' => 'developer', 'guard_name' => 'web']);
        $developer->syncPermissions([
            'view-projects', 'deploy-projects',
            'view-servers',
            'view-deployments', 'create-deployments',
            'view-analytics',
            'view-logs',
            'view-docker-logs',
            'view-pipelines', 'execute-pipelines',
            'view-scripts', 'execute-scripts',
            'view-notifications',
            'view-backups',
            'view-domains',
            'view-health-checks',
        ]);

        // Create viewer role
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions([
            'view-projects',
            'view-servers',
            'view-deployments',
            'view-analytics',
            'view-logs',
        ]);
    }

    /**
     * Create a user with all permissions (super admin).
     */
    protected function createUserWithAllPermissions(): User
    {
        $this->setUpPermissions();
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    /**
     * Create a user with admin role.
     */
    protected function createAdminUser(): User
    {
        $this->setUpPermissions();
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    /**
     * Create a user with developer role.
     */
    protected function createDeveloperUser(): User
    {
        $this->setUpPermissions();
        $user = User::factory()->create();
        $user->assignRole('developer');

        return $user;
    }

    /**
     * Create a user with viewer role.
     */
    protected function createViewerUser(): User
    {
        $this->setUpPermissions();
        $user = User::factory()->create();
        $user->assignRole('viewer');

        return $user;
    }

    /**
     * Create a user with specific permissions.
     */
    protected function createUserWithPermissions(array $permissions): User
    {
        $this->setUpPermissions();
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    /**
     * Assign all permissions to an existing user.
     */
    protected function giveAllPermissions(User $user): User
    {
        $this->setUpPermissions();
        $user->assignRole('super-admin');

        return $user;
    }

    /**
     * Assign admin role to an existing user.
     */
    protected function giveAdminRole(User $user): User
    {
        $this->setUpPermissions();
        $user->assignRole('admin');

        return $user;
    }
}
