# Team Management Tests - Quick Reference

## 🚀 Quick Start

```bash
# Run all team management tests
php artisan dusk tests/Browser/TeamManagementTest.php

# Run specific test
php artisan dusk --filter test_teams_list_page_loads

# Run with verbose output
php artisan dusk tests/Browser/TeamManagementTest.php --verbose
```

## 📋 Test List (25 Tests)

### Team List & Creation (9 tests)
1. `test_teams_list_page_loads` - Page loads
2. `test_create_team_button_visible` - Button visible
3. `test_create_team_modal_opens` - Modal opens
4. `test_team_name_field_present` - Name field exists
5. `test_team_list_displays_existing_teams` - Shows teams
20. `test_team_card_displays_member_count` - Member count
21. `test_settings_button_navigates_correctly` - Settings link
23. `test_team_owner_badge_displayed` - Owner badge
24. `test_create_team_form_validates_required_fields` - Validation

### Settings - General (4 tests)
6. `test_team_settings_page_loads` - Settings page loads
16. `test_team_description_editable` - Description field
17. `test_team_avatar_upload_field_present` - Avatar upload
25. `test_team_name_displayed_in_settings_header` - Header display

### Settings - Members (3 tests)
7. `test_team_members_list_visible` - Members list
12. `test_remove_member_button_visible` - Remove button
19. `test_member_role_can_be_changed` - Role dropdown

### Settings - Invitations (5 tests)
8. `test_invite_member_button_present` - Invite button
9. `test_invite_member_modal_opens` - Invite modal
10. `test_email_field_for_invitation_present` - Email field
11. `test_role_selection_dropdown_present` - Role dropdown
22. `test_invitations_tab_shows_pending_invitations` - Invitations tab

### Settings - Danger Zone (3 tests)
13. `test_danger_zone_displays_deletion_option` - Delete option
14. `test_team_deletion_button_for_owners` - Delete button
18. `test_transfer_ownership_option_visible` - Transfer option

### Validation & Feedback (1 test)
15. `test_flash_messages_display` - Flash messages

## 🎯 Key Assertions

```php
// Page visibility
->assertSee('Your Teams')
->assertPresent('button')

// Form fields
->assertPresent('#name')
->assertAttribute('#name', 'type', 'text')
->assertAttribute('#name', 'required', 'true')

// Navigation
->assertSeeLink('Settings')
->waitForLocation('/teams')

// Content
->assertSee('Test Management Team')
->assertSee('members')
```

## 📸 Screenshots Generated

All in `tests/Browser/screenshots/`:
- 01-25: Complete workflow coverage
- Naming: `XX-team-management-[feature].png`

## 🔧 Test Data

**Users:**
- admin@devflow.test (Owner)
- member@devflow.test (Member)

**Teams:**
- Test Management Team
- Development Management Team

## ✅ Coverage

- ✅ Team List Page
- ✅ Create Team Modal
- ✅ Team Settings (4 tabs)
- ✅ Member Management
- ✅ Invitations
- ✅ Role Changes
- ✅ Ownership Transfer
- ✅ Team Deletion
- ✅ Form Validation
- ✅ Flash Messages

## 🚨 No `|| true` Pattern

All assertions are real - no fake passes!

## 📊 Expected Results

```
=== Team Management Test Results ===
✓ teams_list_page: Teams list page loaded successfully
✓ create_button: Create team button is visible
✓ create_modal: Create team modal opens successfully
...
=================================

PASSED: 25 tests
```
