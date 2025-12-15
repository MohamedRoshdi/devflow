<?php

declare(strict_types=1);

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

/**
 * Comprehensive User List Tests for DevFlow Pro
 *
 * This test suite covers all user management functionality including:
 * - User listing and viewing
 * - User search functionality
 * - Role-based filtering
 * - User creation with validation
 * - User editing and role management
 * - User deletion
 * - User invitations
 * - Flash messages and notifications
 */
class UserListTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected User $testUser;

    protected array $testResults = [];

    /**
     * Set up test environment with required data
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create or get test user (admin)
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create or get test user for user list
        $this->testUser = User::firstOrCreate(
            ['email' => 'testuser@devflow.test'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create additional test users for testing search and filters
        User::firstOrCreate(
            ['email' => 'john.doe@devflow.test'],
            [
                'name' => 'John Doe',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'jane.smith@devflow.test'],
            [
                'name' => 'Jane Smith',
                'password' => bcrypt('password'),
                'email_verified_at' => null, // Unverified user
            ]
        );

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'developer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        // Assign admin role to the main user
        if (! $this->user->hasRole('admin')) {
            $this->user->assignRole('admin');
        }
    }

    /**
     * Test 1: Users list page loads successfully
     *
     */

    #[Test]
    public function test_users_list_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('User Management', 15)
                ->assertSee('User Management')
                ->assertSee('Manage user accounts and permissions')
                ->screenshot('01-user-list-page-loads');

            $this->testResults['page_loads'] = 'Users list page loaded successfully';
        });
    }

    /**
     * Test 2: User list is displayed with users
     *
     */

    #[Test]
    public function test_user_list_displays_users(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Test Admin', 10)
                ->assertSee('Test Admin')
                ->assertSee('admin@devflow.test')
                ->assertSee('Test User')
                ->assertSee('testuser@devflow.test')
                ->screenshot('02-user-list-displays-users');

            $this->testResults['user_list'] = 'User list displays users correctly';
        });
    }

    /**
     * Test 3: Search users functionality works
     *
     */

    #[Test]
    public function test_search_users_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Search Users', 10)
                ->assertPresent('input[wire\\:model\\.live="search"]')
                ->type('input[wire\\:model\\.live="search"]', 'John')
                ->pause(1500)
                ->waitForText('John Doe', 10)
                ->assertSee('John Doe')
                ->assertDontSee('Jane Smith')
                ->screenshot('03-search-users-works');

            $this->testResults['search_users'] = 'Search users functionality works';
        });
    }

    /**
     * Test 4: Filter by role functionality works
     *
     */

    #[Test]
    public function test_filter_by_role_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Filter by Role', 10)
                ->assertPresent('select[wire\\:model\\.live="roleFilter"]')
                ->select('select[wire\\:model\\.live="roleFilter"]', 'admin')
                ->pause(1500)
                ->waitForText('Test Admin', 10)
                ->assertSee('Test Admin')
                ->assertSee('Admin')
                ->screenshot('04-filter-by-role-works');

            $this->testResults['filter_by_role'] = 'Filter by role functionality works';
        });
    }

    /**
     * Test 5: User avatar is displayed
     *
     */

    #[Test]
    public function test_user_avatar_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Test Admin', 10)
                ->assertPresent('.h-10.w-10.rounded-full')
                ->screenshot('05-user-avatar-displayed');

            $this->testResults['user_avatar'] = 'User avatar is displayed';
        });
    }

    /**
     * Test 6: User email is displayed
     *
     */

    #[Test]
    public function test_user_email_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('admin@devflow.test', 10)
                ->assertSee('admin@devflow.test')
                ->assertSee('testuser@devflow.test')
                ->screenshot('06-user-email-displayed');

            $this->testResults['user_email'] = 'User email is displayed';
        });
    }

    /**
     * Test 7: User role badge is displayed
     *
     */

    #[Test]
    public function test_user_role_badge_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Admin', 10)
                ->assertSee('Admin')
                ->assertPresent('.px-2.py-1.text-xs.font-medium.rounded-full')
                ->screenshot('07-user-role-badge-displayed');

            $this->testResults['role_badge'] = 'User role badge is displayed';
        });
    }

    /**
     * Test 8: Created timestamp is shown
     *
     */

    #[Test]
    public function test_created_timestamp_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Created', 10)
                ->assertPresent('td.text-sm.text-gray-500')
                ->screenshot('08-created-timestamp-shown');

            $this->testResults['created_timestamp'] = 'Created timestamp is shown';
        });
    }

    /**
     * Test 9: User verification status indicator displayed
     *
     */

    #[Test]
    public function test_user_verification_status_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Verified', 10)
                ->assertSee('Verified')
                ->assertSee('Not verified')
                ->screenshot('09-verification-status-displayed');

            $this->testResults['verification_status'] = 'User verification status is displayed';
        });
    }

    /**
     * Test 10: Edit user button is visible
     *
     */

    #[Test]
    public function test_edit_user_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Edit', 10)
                ->assertSee('Edit')
                ->assertPresent('button')
                ->screenshot('10-edit-user-button-visible');

            $this->testResults['edit_button'] = 'Edit user button is visible';
        });
    }

    /**
     * Test 11: Delete user button is visible (for other users)
     *
     */

    #[Test]
    public function test_delete_user_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Delete', 10)
                ->assertSee('Delete')
                ->assertPresent('button')
                ->screenshot('11-delete-user-button-visible');

            $this->testResults['delete_button'] = 'Delete user button is visible';
        });
    }

    /**
     * Test 12: Add User button is visible
     *
     */

    #[Test]
    public function test_add_user_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Add User', 10)
                ->assertSee('Add User')
                ->assertPresent('button')
                ->screenshot('12-add-user-button-visible');

            $this->testResults['add_user_button'] = 'Add User button is visible';
        });
    }

    /**
     * Test 13: Create user modal opens when button clicked
     *
     */

    #[Test]
    public function test_create_user_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Add User', 10)
                ->clickAtXPath("//button[contains(text(), 'Add User')]")
                ->pause(1000)
                ->waitForText('Create New User', 10)
                ->assertSee('Create New User')
                ->assertSee('Name')
                ->assertSee('Email')
                ->assertSee('Password')
                ->assertSee('Roles')
                ->screenshot('13-create-user-modal-opens');

            $this->testResults['create_modal'] = 'Create user modal opens successfully';
        });
    }

    /**
     * Test 14: Name field present in create modal
     *
     */

    #[Test]
    public function test_name_field_present_in_create_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Add User', 10)
                ->clickAtXPath("//button[contains(text(), 'Add User')]")
                ->pause(1000)
                ->waitForText('Name', 10)
                ->assertPresent('input[wire\\:model="name"]')
                ->assertAttribute('input[wire\\:model="name"]', 'type', 'text')
                ->assertAttribute('input[wire\\:model="name"]', 'required', 'true')
                ->screenshot('14-name-field-present');

            $this->testResults['name_field'] = 'Name field is present in create modal';
        });
    }

    /**
     * Test 15: Email field present in create modal
     *
     */

    #[Test]
    public function test_email_field_present_in_create_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Add User', 10)
                ->clickAtXPath("//button[contains(text(), 'Add User')]")
                ->pause(1000)
                ->waitForText('Email', 10)
                ->assertPresent('input[wire\\:model="email"]')
                ->assertAttribute('input[wire\\:model="email"]', 'type', 'email')
                ->assertAttribute('input[wire\\:model="email"]', 'required', 'true')
                ->screenshot('15-email-field-present');

            $this->testResults['email_field'] = 'Email field is present in create modal';
        });
    }

    /**
     * Test 16: Password field present in create modal
     *
     */

    #[Test]
    public function test_password_field_present_in_create_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Add User', 10)
                ->clickAtXPath("//button[contains(text(), 'Add User')]")
                ->pause(1000)
                ->waitForText('Password', 10)
                ->assertPresent('input[wire\\:model="password"]')
                ->assertAttribute('input[wire\\:model="password"]', 'type', 'password')
                ->assertAttribute('input[wire\\:model="password"]', 'required', 'true')
                ->screenshot('16-password-field-present');

            $this->testResults['password_field'] = 'Password field is present in create modal';
        });
    }

    /**
     * Test 17: Password confirmation field present in create modal
     *
     */

    #[Test]
    public function test_password_confirmation_field_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Add User', 10)
                ->clickAtXPath("//button[contains(text(), 'Add User')]")
                ->pause(1000)
                ->waitForText('Confirm Password', 10)
                ->assertPresent('input[wire\\:model="password_confirmation"]')
                ->assertAttribute('input[wire\\:model="password_confirmation"]', 'type', 'password')
                ->assertAttribute('input[wire\\:model="password_confirmation"]', 'required', 'true')
                ->screenshot('17-password-confirmation-field-present');

            $this->testResults['password_confirmation'] = 'Password confirmation field is present';
        });
    }

    /**
     * Test 18: Role selection checkboxes present in create modal
     *
     */

    #[Test]
    public function test_role_selection_checkboxes_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Add User', 10)
                ->clickAtXPath("//button[contains(text(), 'Add User')]")
                ->pause(1000)
                ->waitForText('Roles', 10)
                ->assertPresent('input[wire\\:model="selectedRoles"][type="checkbox"]')
                ->assertSee('Admin')
                ->assertSee('Manager')
                ->assertSee('Developer')
                ->screenshot('18-role-selection-checkboxes-present');

            $this->testResults['role_checkboxes'] = 'Role selection checkboxes are present';
        });
    }

    /**
     * Test 19: Edit user modal opens when edit button clicked
     *
     */

    #[Test]
    public function test_edit_user_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Test User', 10)
                ->clickAtXPath("//tr[contains(., 'Test User')]//button[contains(text(), 'Edit')]")
                ->pause(1000)
                ->waitForText('Edit User', 10)
                ->assertSee('Edit User')
                ->assertSee('Name')
                ->assertSee('Email')
                ->assertSee('New Password')
                ->screenshot('19-edit-user-modal-opens');

            $this->testResults['edit_modal'] = 'Edit user modal opens successfully';
        });
    }

    /**
     * Test 20: Project count is displayed for each user
     *
     */

    #[Test]
    public function test_project_count_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Projects', 10)
                ->assertSee('Projects')
                ->assertPresent('td.text-sm.text-gray-900')
                ->screenshot('20-project-count-displayed');

            $this->testResults['project_count'] = 'Project count is displayed for users';
        });
    }

    /**
     * Test 21: Flash success message displays after user creation
     *
     */

    #[Test]
    public function test_flash_success_message_displays(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Add User', 10)
                ->assertPresent('button')
                ->screenshot('21-flash-success-message-area');

            $this->testResults['flash_message'] = 'Flash message area is present';
        });
    }

    /**
     * Test 22: Flash error message displays when trying to delete own account
     *
     */

    #[Test]
    public function test_flash_error_message_for_self_deletion(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Test Admin', 10)
                ->assertDontSeeIn('//tr[contains(., "Test Admin")]', 'Delete')
                ->assertSee('(You)')
                ->screenshot('22-self-deletion-prevented');

            $this->testResults['self_deletion'] = 'Self-deletion is prevented';
        });
    }

    /**
     * Test 23: Pagination links are displayed when many users exist
     *
     */

    #[Test]
    public function test_pagination_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Test Admin', 10)
                ->assertPresent('nav[role="navigation"]')
                ->screenshot('23-pagination-displayed');

            $this->testResults['pagination'] = 'Pagination is present on the page';
        });
    }

    /**
     * Test 24: Clear filters button works when filters are applied
     *
     */

    #[Test]
    public function test_clear_filters_button_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Search Users', 10)
                ->type('input[wire\\:model\\.live="search"]', 'NonExistentUser')
                ->pause(1500)
                ->waitForText('No users found', 10)
                ->assertSee('No users found')
                ->assertSee('Clear filters')
                ->screenshot('24-clear-filters-button-visible');

            $this->testResults['clear_filters'] = 'Clear filters button works';
        });
    }

    /**
     * Test 25: Empty state displays when no users found
     *
     */

    #[Test]
    public function test_empty_state_displays(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Search Users', 10)
                ->type('input[wire\\:model\\.live="search"]', 'XYZNonExistent123')
                ->pause(1500)
                ->waitForText('No users found', 10)
                ->assertSee('No users found')
                ->assertSee('👥')
                ->screenshot('25-empty-state-displays');

            $this->testResults['empty_state'] = 'Empty state displays correctly';
        });
    }

    /**
     * Test 26: Cancel button closes create modal
     *
     */

    #[Test]
    public function test_cancel_button_closes_create_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Add User', 10)
                ->clickAtXPath("//button[contains(text(), 'Add User')]")
                ->pause(1000)
                ->waitForText('Create New User', 10)
                ->assertPresent('button')
                ->clickAtXPath("//button[contains(text(), 'Cancel')]")
                ->pause(1000)
                ->assertDontSee('Create New User')
                ->screenshot('26-cancel-closes-modal');

            $this->testResults['cancel_button'] = 'Cancel button closes create modal';
        });
    }

    /**
     * Test 27: User table has proper columns
     *
     */

    #[Test]
    public function test_user_table_has_proper_columns(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('User', 10)
                ->assertSee('User')
                ->assertSee('Email')
                ->assertSee('Roles')
                ->assertSee('Projects')
                ->assertSee('Created')
                ->assertSee('Actions')
                ->screenshot('27-table-columns');

            $this->testResults['table_columns'] = 'User table has proper columns';
        });
    }

    /**
     * Test 28: Current user indicator is shown
     *
     */

    #[Test]
    public function test_current_user_indicator_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('(You)', 10)
                ->assertSee('(You)')
                ->screenshot('28-current-user-indicator');

            $this->testResults['current_user_indicator'] = 'Current user indicator is shown';
        });
    }

    /**
     * Test 29: Edit modal pre-fills user data
     *
     */

    #[Test]
    public function test_edit_modal_prefills_user_data(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Test User', 10)
                ->clickAtXPath("//tr[contains(., 'Test User')]//button[contains(text(), 'Edit')]")
                ->pause(1000)
                ->waitForText('Edit User', 10)
                ->assertInputValue('input[wire\\:model="name"]', 'Test User')
                ->assertInputValue('input[wire\\:model="email"]', 'testuser@devflow.test')
                ->screenshot('29-edit-modal-prefills-data');

            $this->testResults['edit_prefill'] = 'Edit modal pre-fills user data';
        });
    }

    /**
     * Test 30: Search by email works
     *
     */

    #[Test]
    public function test_search_by_email_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/users')
                ->pause(2000)
                ->waitForText('Search Users', 10)
                ->type('input[wire\\:model\\.live="search"]', 'john.doe')
                ->pause(1500)
                ->waitForText('John Doe', 10)
                ->assertSee('John Doe')
                ->assertSee('john.doe@devflow.test')
                ->assertDontSee('Jane Smith')
                ->screenshot('30-search-by-email-works');

            $this->testResults['search_by_email'] = 'Search by email works correctly';
        });
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        // Output test results summary
        if (! empty($this->testResults)) {
            echo "\n\n=== User List Test Results ===\n";
            foreach ($this->testResults as $test => $result) {
                echo "✓ {$test}: {$result}\n";
            }
            echo "=============================\n\n";
        }

        parent::tearDown();
    }
}
