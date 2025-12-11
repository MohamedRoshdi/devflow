# Roles & Permissions Setup Guide

## Overview
A complete Roles & Permissions management system for DevFlow Pro with a premium dark slate theme matching the rest of the application.

## What's Included

### 1. Livewire Component
- **File**: `app/Livewire/Settings/RolesPermissions.php`
- **Features**:
  - Create, Edit, Delete roles
  - Manage permissions for each role
  - Search and filter roles
  - View users count per role
  - Bulk permission assignment
  - Real-time validation

### 2. Blade View
- **File**: `resources/views/livewire/settings/roles-permissions.blade.php`
- **Design Features**:
  - Premium dark slate theme with glassmorphism
  - Animated background orbs (3 layers)
  - Gradient hero section
  - Stats cards (Total Roles, Total Permissions, Categories)
  - Role cards with gradient effects
  - Premium modals with animated borders
  - Responsive grid layout
  - Smooth transitions and hover effects

### 3. Default Permissions Seeder
- **File**: `database/seeders/DefaultPermissionsSeeder.php`
- **Includes**: 70+ permissions across 20+ categories
- **Default Roles**:
  - **Super Admin**: Full system access
  - **Admin**: Most permissions except system-critical ones
  - **Manager**: Project and deployment management
  - **Developer**: Can deploy and view resources
  - **Viewer**: Read-only access

### 4. Route
- **URL**: `/settings/roles-permissions`
- **Name**: `settings.roles-permissions`
- **Middleware**: `auth`, `throttle:web`

### 5. Navigation
- **Location**: Settings dropdown in sidebar
- **Position**: Between "Preferences" and "System Admin"
- **Icon**: Shield with check mark (heroicons)

## Installation Steps

### Step 1: Run the Seeder
To populate the database with default roles and permissions:

```bash
php artisan db:seed --class=DefaultPermissionsSeeder
```

This will create:
- 70+ permissions
- 5 default roles (super-admin, admin, manager, developer, viewer)
- Permission assignments for each role

### Step 2: Assign Roles to Users
After running the seeder, assign roles to users either:

**Option A: Using the Web Interface**
1. Navigate to `/users` (User Management page)
2. Edit a user and assign roles

**Option B: Using Tinker**
```bash
php artisan tinker
```

```php
// Assign super-admin role to first user
$user = User::first();
$user->assignRole('super-admin');

// Or assign multiple roles
$user->syncRoles(['admin', 'manager']);
```

### Step 3: Access Roles & Permissions Page
1. Log in as an admin user
2. Go to **Settings** in the sidebar
3. Click **Roles & Permissions**

## Default Permissions by Category

### Projects (5 permissions)
- view-projects
- create-projects
- edit-projects
- delete-projects
- deploy-projects

### Servers (5 permissions)
- view-servers
- create-servers
- edit-servers
- delete-servers
- manage-server-security

### Deployments (4 permissions)
- view-deployments
- create-deployments
- approve-deployments
- rollback-deployments

### Users (4 permissions)
- view-users
- create-users
- edit-users
- delete-users

### Roles & Permissions (3 permissions)
- manage-roles
- assign-roles
- manage-permissions

### Settings (3 permissions)
- view-settings
- edit-settings
- manage-system-settings

### Analytics (2 permissions)
- view-analytics
- export-analytics

### Logs (3 permissions)
- view-logs
- delete-logs
- export-logs

### Docker (2 permissions)
- manage-docker
- view-docker-logs

### Kubernetes (2 permissions)
- manage-kubernetes
- view-kubernetes-logs

### Pipelines (5 permissions)
- view-pipelines
- create-pipelines
- edit-pipelines
- delete-pipelines
- execute-pipelines

### Scripts (5 permissions)
- view-scripts
- create-scripts
- edit-scripts
- delete-scripts
- execute-scripts

### Notifications (2 permissions)
- view-notifications
- manage-notification-channels

### Multi-Tenant (2 permissions)
- manage-tenants
- view-tenant-data

### Backups (4 permissions)
- view-backups
- create-backups
- restore-backups
- delete-backups

### Domains (5 permissions)
- view-domains
- create-domains
- edit-domains
- delete-domains
- manage-ssl

### Health Checks (2 permissions)
- view-health-checks
- manage-health-checks

### Webhooks (2 permissions)
- view-webhooks
- manage-webhooks

### Teams (5 permissions)
- view-teams
- create-teams
- edit-teams
- delete-teams
- manage-team-members

### Audit (2 permissions)
- view-audit-logs
- export-audit-logs

## Default Role Permissions

### Super Admin
- **Access**: ALL permissions (complete system control)
- **Use Case**: System owner, main administrator

### Admin
- **Access**: Most permissions except system-critical operations
- **Can**: Manage users, projects, servers, deployments, domains, backups
- **Cannot**: Access some system-critical settings (reserved for super-admin)
- **Use Case**: Trusted administrators, senior DevOps engineers

### Manager
- **Access**: Project and deployment management
- **Can**: Create/edit projects, approve deployments, manage domains, create backups
- **Cannot**: Delete projects, manage users, access system settings
- **Use Case**: Project managers, team leads

### Developer
- **Access**: Deploy and view resources
- **Can**: View projects, deploy code, view logs, execute pipelines
- **Cannot**: Create/edit projects, manage infrastructure
- **Use Case**: Software developers, junior engineers

### Viewer
- **Access**: Read-only
- **Can**: View all resources, logs, analytics
- **Cannot**: Create, edit, or delete anything
- **Use Case**: Stakeholders, clients, read-only access

## Usage Examples

### Creating a New Role
1. Click "Create Role" button
2. Enter role name (e.g., "DevOps Engineer")
3. Select permissions from the grouped list
4. Click "Create Role"

### Editing a Role
1. Click "Edit" button on a role card
2. Modify the role name or permissions
3. Click "Update Role"

### Managing Permissions
1. Click "Permissions" button on a role card
2. Check/uncheck permissions by category
3. Click "Update Permissions"

### Deleting a Role
1. Click the delete (trash) icon on a role card
2. Confirm deletion
3. Note: Cannot delete roles assigned to users

## Checking Permissions in Code

### In Controllers
```php
// Check if user has permission
if (auth()->user()->can('create-projects')) {
    // User can create projects
}

// Check if user has role
if (auth()->user()->hasRole('admin')) {
    // User is an admin
}

// Check if user has any role
if (auth()->user()->hasAnyRole(['admin', 'manager'])) {
    // User is admin or manager
}
```

### In Blade Views
```blade
@can('create-projects')
    <button>Create Project</button>
@endcan

@role('admin')
    <a href="/admin">Admin Panel</a>
@endrole
```

### In Routes
```php
Route::middleware(['permission:create-projects'])->group(function () {
    Route::post('/projects', [ProjectController::class, 'store']);
});

Route::middleware(['role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});
```

### In Livewire Components
```php
public function mount()
{
    $this->authorize('view-projects');
}

public function deleteProject($id)
{
    if (!auth()->user()->can('delete-projects')) {
        abort(403, 'Unauthorized action.');
    }

    // Delete logic
}
```

## Security Best Practices

1. **Never hardcode roles**: Always check permissions, not role names
2. **Principle of least privilege**: Give users only the permissions they need
3. **Regular audits**: Review user permissions regularly
4. **Protect critical operations**: Always check permissions before destructive actions
5. **Use middleware**: Protect routes with permission middleware

## Troubleshooting

### Permissions not working after seeder
```bash
# Clear permission cache
php artisan permission:cache-reset
php artisan cache:clear
```

### Role not showing in UI
```bash
# Clear Livewire cache
php artisan livewire:discover
php artisan view:clear
```

### Database issues
```bash
# Check if permissions table exists
php artisan tinker
>>> \Spatie\Permission\Models\Permission::count();
>>> \Spatie\Permission\Models\Role::count();
```

## Customization

### Adding New Permissions
1. Edit `database/seeders/DefaultPermissionsSeeder.php`
2. Add new permissions to the `$permissions` array
3. Assign to appropriate roles
4. Re-run seeder: `php artisan db:seed --class=DefaultPermissionsSeeder`

### Custom Permission Categories
Permissions are grouped by the text after the first hyphen:
- `view-projects` → "projects" category
- `create-servers` → "servers" category

Follow this naming convention for auto-grouping.

### Styling Customization
All styling is in the Blade view. Key classes:
- Background orbs: `animate-float`, `animate-pulse-slow`
- Glassmorphism: `bg-slate-800/50 backdrop-blur-sm`
- Gradients: `bg-gradient-to-r from-purple-600 to-pink-600`
- Hover effects: `hover:scale-105 hover:shadow-lg`

## Component Features

### Real-time Search
- Instant search filtering without page reload
- Search by role name
- Auto-resets pagination

### Stats Dashboard
- Total Roles count
- Total Permissions count
- Permission Categories count
- Real-time updates

### Permission Grouping
- Automatic grouping by category
- Collapsible sections
- Easy bulk selection

### Validation
- Unique role names
- Required fields
- Prevent deletion of assigned roles
- Form error messages

### UX Enhancements
- Loading states
- Success/error notifications
- Confirmation dialogs
- Smooth animations
- Responsive design

## Tech Stack

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS
- **Permissions**: Spatie Laravel Permission 6.23.0
- **Icons**: Heroicons
- **Design**: Premium dark slate theme with glassmorphism

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console for JavaScript errors
3. Verify spatie/laravel-permission is installed: `composer show spatie/laravel-permission`
4. Ensure database migrations are run: `php artisan migrate`

## Credits

Built for DevFlow Pro by following the existing design system and premium dark slate theme.
