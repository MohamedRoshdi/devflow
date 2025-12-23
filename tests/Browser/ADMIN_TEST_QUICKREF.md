# Admin Test Quick Reference

## Run Commands

```bash
# Run all admin tests
php artisan dusk tests/Browser/AdminTest.php

# Run specific test
php artisan dusk --filter test_users_list_page_loads

# Run with verbose output
php artisan dusk tests/Browser/AdminTest.php --verbose

# Re-run failed tests only
php artisan dusk:fails
```

## Test Categories

### User Management (30 tests)
Tests 1-30 cover complete user CRUD, search, filtering, roles, and security

### System Admin (10 tests)
Tests 31-40 cover system dashboard, metrics, logs, and operations

### Audit Logs (7 tests)
Tests 41-47 cover audit viewer, filtering, search, and export

### System Utilities (3 tests)
Tests 48-50 cover cache, queue, and health monitoring

## Total: 50 Tests

## Key Routes

- `/users` - User management
- `/admin/system` - System admin
- `/admin/audit-logs` - Audit logs (newly added)
- `/settings/queue` - Cache & queue
- `/dashboard` - Main dashboard

## Test User Credentials

- **Admin:** admin@devflow.test / password
- **Test User:** testuser@devflow.test / password

## Screenshots Location

All test screenshots saved to:
```
tests/Browser/screenshots/admin-*.png
```

## Test Reports Location

JSON reports saved to:
```
storage/app/test-reports/admin-user-management-*.json
```
