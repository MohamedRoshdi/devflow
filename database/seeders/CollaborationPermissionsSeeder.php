<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Collaboration Permissions Seeder
 *
 * IMPORTANT: This seeder ADDS collaboration-specific permissions to existing roles.
 * It uses givePermissionTo() instead of syncPermissions() to avoid removing
 * permissions granted by DefaultPermissionsSeeder.
 *
 * Run this AFTER DefaultPermissionsSeeder.
 */
class CollaborationPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create collaboration-specific permissions
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

        // Add collaboration permissions to roles (without removing existing ones)
        $this->addPermissionsToSuperAdmin();
        $this->addPermissionsToAdmin();
        $this->addPermissionsToManager();
        $this->addPermissionsToDeveloper();
        $this->addPermissionsToViewer();

        $this->command->info('Collaboration permissions added to roles successfully!');
    }

    private function addPermissionsToSuperAdmin(): void
    {
        $role = Role::where('name', 'super-admin')->first();
        if (! $role) {
            return;
        }

        // Super-admin gets all new permissions
        $role->givePermissionTo([
            'approve_deployments',
            'approve_all_deployments',
            'request_deployment_approval',
            'view_audit_logs',
            'view_all_audit_logs',
            'export_audit_logs',
            'add_deployment_comments',
            'edit_own_comments',
            'delete_own_comments',
            'manage_all_comments',
            'manage_notification_channels',
            'view_notification_channels',
            'test_notification_channels',
        ]);
    }

    private function addPermissionsToAdmin(): void
    {
        $role = Role::where('name', 'admin')->first();
        if (! $role) {
            return;
        }

        // Admin gets most collaboration permissions
        $role->givePermissionTo([
            'approve_deployments',
            'approve_all_deployments',
            'request_deployment_approval',
            'view_audit_logs',
            'view_all_audit_logs',
            'export_audit_logs',
            'add_deployment_comments',
            'edit_own_comments',
            'delete_own_comments',
            'manage_all_comments',
            'manage_notification_channels',
            'view_notification_channels',
            'test_notification_channels',
        ]);
    }

    private function addPermissionsToManager(): void
    {
        $role = Role::where('name', 'manager')->first();
        if (! $role) {
            return;
        }

        $role->givePermissionTo([
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
        ]);
    }

    private function addPermissionsToDeveloper(): void
    {
        $role = Role::where('name', 'developer')->first();
        if (! $role) {
            return;
        }

        $role->givePermissionTo([
            'request_deployment_approval',
            'view_audit_logs',
            'add_deployment_comments',
            'edit_own_comments',
            'delete_own_comments',
            'view_notification_channels',
        ]);
    }

    private function addPermissionsToViewer(): void
    {
        $role = Role::where('name', 'viewer')->first();
        if (! $role) {
            return;
        }

        $role->givePermissionTo([
            'view_audit_logs',
            'add_deployment_comments',
            'view_notification_channels',
        ]);
    }
}
