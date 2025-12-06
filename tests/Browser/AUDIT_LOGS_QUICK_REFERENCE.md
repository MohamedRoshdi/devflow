# Audit Logs Tests - Quick Reference

## File Location
`/home/roshdy/Work/projects/DEVFLOW_PRO/tests/Browser/AuditLogsTest.php`

## Total Tests: 45

## Quick Test Commands

### Run All Audit Logs Tests
```bash
php artisan dusk tests/Browser/AuditLogsTest.php
```

### Run Individual Test Categories

#### Page Access (2 tests)
```bash
php artisan dusk --filter test_audit_logs_page_loads
php artisan dusk --filter test_audit_logs_list_displays
```

#### Filtering Tests (11 tests)
```bash
php artisan dusk --filter "test_filter"
```

#### Search Tests (2 tests)
```bash
php artisan dusk --filter "test_search"
```

#### Export Tests (4 tests)
```bash
php artisan dusk --filter "test_export"
php artisan dusk --filter "test_bulk_export"
```

#### Display Tests (11 tests)
```bash
php artisan dusk --filter "test_.*_display"
php artisan dusk --filter "test_view_audit_log_details"
```

#### Activity Tracking (8 tests)
```bash
php artisan dusk --filter "test_.*_logging"
php artisan dusk --filter "test_.*_activity"
```

#### Security Test (1 test)
```bash
php artisan dusk --filter test_non_admin_cannot_access
```

## Test List (45 Tests)

### Basic Functionality
1. test_audit_logs_page_loads_successfully
2. test_audit_logs_list_displays_entries
3. test_audit_log_pagination_works

### Filtering
4. test_filter_audit_logs_by_user
5. test_filter_audit_logs_by_action_type
6. test_filter_audit_logs_by_date_range
7. test_filter_by_action_category
8. test_filter_by_model_type
9. test_filter_by_ip_address
10. test_clear_filters_functionality
11. test_security_events_filter
12. test_filter_by_today_activity
13. test_filter_by_last_7_days
14. test_filter_by_last_30_days

### Search
15. test_search_audit_logs_functionality
16. test_search_by_action_name

### Export
17. test_export_audit_logs_to_csv
18. test_export_audit_logs_to_json
19. test_export_filtered_results
20. test_bulk_export_functionality

### Display & UI
21. test_view_audit_log_details
22. test_audit_log_timestamp_display
23. test_user_name_display_in_logs
24. test_action_description_display
25. test_ip_address_display_in_logs
26. test_old_values_display_in_change_logs
27. test_new_values_display_in_change_logs
28. test_model_identifier_display
29. test_audit_log_detail_modal
30. test_audit_log_count_display
31. test_date_range_picker_functionality

### Activity Tracking
32. test_user_activity_tracking_displayed
33. test_server_action_logging_visible
34. test_project_change_logging_visible
35. test_deployment_activity_logging_visible
36. test_security_event_logging_visible
37. test_api_access_logging_visible
38. test_audit_log_retention_settings_visible
39. test_real_time_log_updates

### Statistics
40. test_audit_log_statistics_display
41. test_activity_timeline_view
42. test_recent_activity_section
43. test_user_activity_summary
44. test_action_type_breakdown

### Security
45. test_non_admin_cannot_access_audit_logs

## Screenshot Locations
All screenshots saved to: `tests/Browser/screenshots/`

Pattern: `audit-logs-{feature-name}.png`

## Test Users
- **Admin User**: admin@devflow.test (password: password)
- **Regular User**: testuser@devflow.test (password: password)

## Sample Data Created
- Test servers
- Test projects
- Test deployments
- Various audit log entries (server, project, deployment, user, security events)

## Common Test Patterns

### Basic Page Load
```php
$this->loginViaUI($browser, $this->adminUser)
    ->visit('/admin/audit-logs')
    ->pause(2000)
    ->waitFor('body', 15)
    ->screenshot('test-name');
```

### Check Page Content
```php
$pageSource = strtolower($browser->driver->getPageSource());
$hasContent = str_contains($pageSource, 'expected-content');
$this->assertTrue($hasContent, 'Message');
```

## Expected Route
`/admin/audit-logs` - Main audit logs page

## Key Assertions
- Page loads successfully
- Log entries are displayed
- Filters work correctly
- Export functions available
- Proper access control (admin only)
- Timestamps and user info displayed
- Change tracking (old vs new values)

## Test Execution Time
Estimated: ~15-20 minutes for full suite (45 tests @ ~20-30 seconds each)

## Dependencies
- Laravel Dusk
- Spatie Permission
- LoginViaUI trait
- Chrome/Chromium driver
- Test database

## Environment Requirements
- `.env.dusk.local` configured
- Chrome driver running
- Test database available
- Proper permissions set

---
Last Updated: December 6, 2025
