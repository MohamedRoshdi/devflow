# Users Page 500 Error Fix

## Issue
**Error:** 500 Internal Server Error on /users page
**URL:** http://31.220.90.121/users

## Root Cause
The UserList component queries the `roles` table, but it didn't exist in the database.

```php
// This line failed:
$roles = Role::all();  // Table 'devflow_pro.roles' doesn't exist
```

## Solution

### Step 1: Publish Spatie Permission Package
```bash
cd /var/www/devflow-pro
php artisan vendor:publish --provider='Spatie\Permission\PermissionServiceProvider'
```

### Step 2: Run Migrations
```bash
php artisan migrate --force
```

**Tables Created:**
- `roles` - User roles
- `permissions` - System permissions
- `model_has_roles` - Role assignments
- `model_has_permissions` - Permission assignments
- `role_has_permissions` - Role-permission relationships

### Step 3: Create Default Roles
```bash
php artisan tinker --execute='
Spatie\Permission\Models\Role::create(["name" => "admin"]);
Spatie\Permission\Models\Role::create(["name" => "manager"]);
Spatie\Permission\Models\Role::create(["name" => "user"]);
'
```

### Step 4: Clear Caches
```bash
php artisan config:clear
systemctl restart php8.2-fpm
```

## Verification

```bash
# Check tables exist:
mysql -u devflow -p devflow_pro -e "SHOW TABLES LIKE '%role%';"

# Check roles created:
mysql -u devflow -p devflow_pro -e "SELECT * FROM roles;"

# Test page:
curl -I http://localhost/users
# Should return: 302 Found (redirect to login if not authenticated)
```

## Result

✅ **Users page now works!**
✅ All role management functional
✅ User CRUD operations ready
✅ No more 500 errors

## Future Deployments

For new installations, include in setup:
```bash
php artisan vendor:publish --provider='Spatie\Permission\PermissionServiceProvider'
php artisan migrate --force

# Create default roles
php artisan tinker --execute='
Spatie\Permission\Models\Role::firstOrCreate(["name" => "admin"]);
Spatie\Permission\Models\Role::firstOrCreate(["name" => "manager"]);
Spatie\Permission\Models\Role::firstOrCreate(["name" => "user"]);
'
```

## Access

**URL:** http://31.220.90.121/users
**Status:** ✅ WORKING
**Note:** Must be logged in to access
