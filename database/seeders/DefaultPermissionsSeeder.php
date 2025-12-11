<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DefaultPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions grouped by category
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

        // Create default roles with their permissions

        // Super Admin - Full access to everything
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Admin - Most permissions except system-critical ones
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'view-projects', 'create-projects', 'edit-projects', 'delete-projects', 'deploy-projects',
            'view-servers', 'create-servers', 'edit-servers', 'delete-servers',
            'view-deployments', 'create-deployments', 'approve-deployments', 'rollback-deployments',
            'view-users', 'create-users', 'edit-users', 'delete-users',
            'view-settings', 'edit-settings',
            'view-analytics', 'export-analytics',
            'view-logs', 'export-logs',
            'manage-docker', 'view-docker-logs',
            'view-pipelines', 'create-pipelines', 'edit-pipelines', 'delete-pipelines', 'execute-pipelines',
            'view-scripts', 'create-scripts', 'edit-scripts', 'delete-scripts', 'execute-scripts',
            'view-notifications', 'manage-notification-channels',
            'view-backups', 'create-backups', 'restore-backups', 'delete-backups',
            'view-domains', 'create-domains', 'edit-domains', 'delete-domains', 'manage-ssl',
            'view-health-checks', 'manage-health-checks',
            'view-webhooks', 'manage-webhooks',
            'view-teams', 'create-teams', 'edit-teams', 'manage-team-members',
            'view-audit-logs', 'export-audit-logs',
        ]);

        // Manager - Project and deployment management
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'view-projects', 'create-projects', 'edit-projects', 'deploy-projects',
            'view-servers', 'edit-servers',
            'view-deployments', 'create-deployments', 'approve-deployments', 'rollback-deployments',
            'view-analytics',
            'view-logs',
            'view-docker-logs',
            'view-pipelines', 'create-pipelines', 'edit-pipelines', 'execute-pipelines',
            'view-scripts', 'execute-scripts',
            'view-notifications',
            'view-backups', 'create-backups',
            'view-domains', 'create-domains', 'edit-domains',
            'view-health-checks',
            'view-webhooks',
        ]);

        // Developer - Can deploy and view resources
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

        // Viewer - Read-only access
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions([
            'view-projects',
            'view-servers',
            'view-deployments',
            'view-analytics',
            'view-logs',
            'view-docker-logs',
            'view-pipelines',
            'view-scripts',
            'view-notifications',
            'view-backups',
            'view-domains',
            'view-health-checks',
        ]);

        $this->command->info('Default permissions and roles created successfully!');
        $this->command->info('Roles created: super-admin, admin, manager, developer, viewer');
        $this->command->info('Total permissions: '.count($permissions));
    }
}
