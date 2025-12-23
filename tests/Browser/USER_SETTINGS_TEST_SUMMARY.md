# User Settings Test Suite Summary

## Overview
**Test File**: `tests/Browser/UserSettingsTest.php`
**Total Tests**: 50
**Test Pattern**: Uses `LoginViaUI` trait for authentication

## Test Categories

### Profile Settings (Tests 1-5)
1. User can access profile settings page
2. Profile settings page displays user information
3. User can edit their name
4. User can edit their email
5. User can view avatar upload section

### Password & Security (Tests 6-7, 25-27, 33-34, 46)
6. User can access password change section
7. Password change requires current password
25. User can access session management
26. Session management shows active sessions
27. User can view account deletion section
33. User can view two-factor authentication settings
34. Two-factor authentication shows enable option
46. Settings pages require authentication

### API Token Management (Tests 8-12)
8. User can view API token management page
9. User can create new API token
10. API token creation requires name
11. User can view existing API tokens
12. User can revoke API tokens

### SSH Key Management (Tests 13-15)
13. User can access SSH key management
14. SSH key page has add key button
15. User can view existing SSH keys

### Notification Preferences (Tests 16-17)
16. User can access notification preferences
17. Notification preferences show deployment notifications

### Theme & Display (Tests 18-20)
18. User can access theme preferences
19. Theme preferences show dark/light mode options
20. User can toggle dark mode

### Timezone & Localization (Tests 21-24)
21. User can view timezone settings
22. Timezone settings show timezone selector
23. User can change timezone
24. User can view language/locale settings

### Activity & Audit (Tests 28-30)
28. User can view email verification status
29. User can access activity log
30. Activity log shows recent activities

### Form Validation (Tests 31-32)
31. Profile form has proper validation
32. Email must be unique validation

### User Preferences (Tests 35, 48-49)
35. User preferences are persisted
48. User can view default setup preferences
49. Default preferences show SSL toggle

### Navigation & UX (Tests 36-38, 41, 50)
36. Navigation between settings sections works
37. Settings page has proper breadcrumbs
38. User can cancel profile edits
41. Settings sidebar navigation is visible
50. Settings page has consistent layout across sections

### Responsive Design (Tests 39-40)
39. Settings page is responsive on mobile
40. Settings page is responsive on tablet

### Integration Settings (Tests 42-45)
42. User can access GitHub settings from settings menu
43. User can access storage settings
44. User can access system status from settings
45. User can access health checks settings

### User Feedback (Test 47)
47. Profile update shows success message

## Test Execution

Run all user settings tests:
```bash
php artisan dusk tests/Browser/UserSettingsTest.php
```

Run specific test:
```bash
php artisan dusk --filter test_user_can_view_profile_settings
```

Run with verbose output:
```bash
php artisan dusk tests/Browser/UserSettingsTest.php --verbose
```

## Coverage Areas

✅ Profile information management (name, email, avatar)
✅ Password change functionality
✅ Two-factor authentication UI
✅ API token CRUD operations
✅ SSH key management
✅ Notification preferences
✅ Theme preferences (dark/light mode)
✅ Timezone settings
✅ Language/locale settings
✅ Session management
✅ Account deletion UI
✅ Email verification status
✅ Activity log viewing
✅ Form validation
✅ Responsive design (mobile, tablet)
✅ Navigation and UX
✅ Integration settings (GitHub, Storage, System Status)
✅ Default setup preferences
✅ User feedback messages

## Notes

- Tests use the `LoginViaUI` trait pattern for consistent authentication
- Each test includes screenshot capture for visual verification
- Tests are designed to work with existing application data
- Some tests include cleanup logic to restore original state
- All tests follow PHPStan Level 8 compliance with strict types
- Tests validate both functionality and UI/UX elements
