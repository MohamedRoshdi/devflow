<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CollaborationPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Deployment Approvals
            'approve_deployments' => 'Approve deployments for owned projects',
            'approve_all_deployments' => 'Approve any deployment in the system',
            'request_deployment_approval' => 'Request deployment approvals',

            // Audit Logs
            'view_audit_logs' => 'View audit logs',
            'view_all_audit_logs' => 'View all audit logs in the system',
            'export_audit_logs' => 'Export audit logs to CSV',

            // Comments
            'add_deployment_comments' => 'Add comments on deployments',
            'edit_own_comments' => 'Edit own comments',
            'delete_own_comments' => 'Delete own comments',
            'manage_all_comments' => 'Manage all comments (edit/delete any)',

            // Notification Channels
            'manage_notification_channels' => 'Create and manage notification channels',
            'view_notification_channels' => 'View notification channels',
            'test_notification_channels' => 'Send test notifications',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        // Create or update roles with permissions
        $this->setupAdminRole();
        $this->setupManagerRole();
        $this->setupDeveloperRole();
        $this->setupViewerRole();
    }

    private function setupAdminRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // Admin has all permissions
        $role->syncPermissions(Permission::all());
    }

    private function setupManagerRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        $permissions = [
            'approve_deployments',
            'request_deployment_approval',
            'view_audit_logs',
            'export_audit_logs',
            'add_deployment_comments',
            'edit_own_comments',
            'delete_own_comments',
            'manage_notification_channels',
            'view_notification_channels',
            'test_notification_channels',
        ];

        $role->syncPermissions($permissions);
    }

    private function setupDeveloperRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'developer', 'guard_name' => 'web']);

        $permissions = [
            'request_deployment_approval',
            'view_audit_logs',
            'add_deployment_comments',
            'edit_own_comments',
            'delete_own_comments',
            'view_notification_channels',
        ];

        $role->syncPermissions($permissions);
    }

    private function setupViewerRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        $permissions = [
            'view_audit_logs',
            'add_deployment_comments',
            'view_notification_channels',
        ];

        $role->syncPermissions($permissions);
    }
}
