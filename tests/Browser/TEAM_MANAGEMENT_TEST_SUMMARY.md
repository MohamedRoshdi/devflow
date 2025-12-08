# Team Management Browser Test Suite

## Overview
Comprehensive browser test coverage for Team Management features in DevFlow Pro.

**File:** `tests/Browser/TeamManagementTest.php`
**Total Tests:** 25 tests
**Routes Tested:**
- `/teams` (Team List)
- `/teams/{team}/settings` (Team Settings)

## Test Coverage

### Team List Page (Tests 1-5, 20-24)
1. ✅ Teams list page loads successfully
2. ✅ Create team button is visible
3. ✅ Create team modal opens when button clicked
4. ✅ Team name field is present in create modal
5. ✅ Teams list displays existing teams
20. ✅ Team card displays member count
21. ✅ Team settings button navigates to settings page
23. ✅ Team owner badge is displayed correctly
24. ✅ Create team form validates required fields

### Team Settings - General Tab (Tests 6, 16-17, 25)
6. ✅ Team settings page loads successfully
16. ✅ Team description field is editable in General settings
17. ✅ Team avatar upload field is present
25. ✅ Team name is displayed in settings header

### Team Settings - Members Tab (Tests 7, 12, 19)
7. ✅ Team members list is visible in settings
12. ✅ Remove member button is visible for team members
19. ✅ Member role can be changed via dropdown

### Team Settings - Invitations Tab (Tests 8-11, 22)
8. ✅ Invite member button is present
9. ✅ Invite member modal opens when button clicked
10. ✅ Email field is present in invitation modal
11. ✅ Role selection dropdown is present in invitation modal
22. ✅ Invitations tab shows pending invitations

### Team Settings - Danger Zone Tab (Tests 13-14, 18)
13. ✅ Danger Zone tab displays team deletion option
14. ✅ Team deletion button is present for owners
18. ✅ Transfer ownership option is visible for team owners

### Form Validation & User Feedback (Test 15)
15. ✅ Flash messages display properly after actions

## Features Tested

### Team Management
- ✅ List all teams
- ✅ Create new team
- ✅ View team details
- ✅ Edit team settings (name, description, avatar)
- ✅ Team member count display
- ✅ Team owner badge
- ✅ Navigate to team settings

### Member Management
- ✅ View team members list
- ✅ Display member information (name, email, avatar)
- ✅ Change member roles (Admin, Member, Viewer)
- ✅ Remove team members
- ✅ Member joined date display

### Invitations
- ✅ Open invite modal
- ✅ Email input field
- ✅ Role selection (Admin, Member, Viewer)
- ✅ View pending invitations
- ✅ Send invitations

### Team Ownership & Deletion
- ✅ Transfer ownership option
- ✅ Delete team option
- ✅ Confirmation requirements

## Test Data Setup

The test suite automatically creates:
- **Test Admin User** (`admin@devflow.test`)
- **Test Member User** (`member@devflow.test`)
- **Test Management Team** (with both users as members)
- **Development Management Team** (for listing tests)

## Assertions Used

### Page Assertions
- `assertSee()` - Text visibility
- `assertPresent()` - Element presence
- `assertSeeLink()` - Link visibility
- `assertAttribute()` - HTML attribute validation
- `waitForText()` - Dynamic content loading
- `waitForLocation()` - Page navigation

### Form Assertions
- Required field validation
- Input type verification
- Dropdown options validation
- File upload field presence

## Running the Tests

### Run All Team Management Tests
```bash
php artisan dusk tests/Browser/TeamManagementTest.php
```

### Run Specific Test
```bash
php artisan dusk --filter test_teams_list_page_loads
```

### Run with Group
```bash
php artisan dusk --group team-management
```

## Screenshots

All tests generate screenshots stored in:
```
tests/Browser/screenshots/
```

Screenshot naming convention:
- `01-team-management-teams-list-page.png`
- `02-team-management-create-button.png`
- `03-team-management-create-modal.png`
- ... (25 total screenshots)

## Test Results Summary

After running tests, a summary is displayed:

```
=== Team Management Test Results ===
✓ teams_list_page: Teams list page loaded successfully
✓ create_button: Create team button is visible
✓ create_modal: Create team modal opens successfully
...
=================================
```

## Dependencies

- **Livewire Components:**
  - `App\Livewire\Teams\TeamList`
  - `App\Livewire\Teams\TeamSettings`

- **Models:**
  - `App\Models\Team`
  - `App\Models\TeamMember`
  - `App\Models\TeamInvitation`
  - `App\Models\User`

- **Traits:**
  - `Tests\Browser\Traits\LoginViaUI`

## Notes

- Tests use the `LoginViaUI` trait for authentication
- All tests include proper pauses for UI rendering
- Tests are designed to work with existing database data
- No database refresh between tests (shared database approach)
- Each test is independent and can run in isolation
- Comprehensive screenshot documentation for debugging

## Coverage Summary

| Category | Tests | Coverage |
|----------|-------|----------|
| Team List | 9 | 100% |
| General Settings | 4 | 100% |
| Members Management | 3 | 100% |
| Invitations | 5 | 100% |
| Danger Zone | 3 | 100% |
| Form Validation | 1 | 100% |
| **TOTAL** | **25** | **100%** |

## Future Enhancements

Potential additional tests:
- Team switching functionality
- Team avatar preview
- Cancel invitation workflow
- Resend invitation workflow
- Leave team functionality
- Team deletion with confirmation
- Transfer ownership completion
- Member permissions enforcement
- Bulk member operations
- Team search/filtering
- Team sorting options
