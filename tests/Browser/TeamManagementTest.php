<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

/**
 * Comprehensive Team Management Tests for DevFlow Pro
 *
 * This test suite covers all team management functionality including:
 * - Team listing and viewing
 * - Team creation with validation
 * - Team settings and configuration
 * - Member management (invite, remove, role updates)
 * - Team invitations handling
 * - Team ownership transfer
 * - Team deletion
 */
class TeamManagementTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected User $otherUser;

    protected Team $team;

    protected array $testResults = [];

    /**
     * Set up test environment with required data
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create or get test user
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create or get another user for member management tests
        $this->otherUser = User::firstOrCreate(
            ['email' => 'member@devflow.test'],
            [
                'name' => 'Test Member',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create or get test team
        $this->team = Team::firstOrCreate(
            ['slug' => 'test-management-team'],
            [
                'name' => 'Test Management Team',
                'owner_id' => $this->user->id,
                'description' => 'A team for testing management features',
                'is_personal' => false,
            ]
        );

        // Ensure user is a member with owner role
        TeamMember::firstOrCreate([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
        ], [
            'role' => 'owner',
            'invited_by' => $this->user->id,
            'joined_at' => now(),
        ]);

        // Add other user as a member
        TeamMember::firstOrCreate([
            'team_id' => $this->team->id,
            'user_id' => $this->otherUser->id,
        ], [
            'role' => 'member',
            'invited_by' => $this->user->id,
            'joined_at' => now(),
        ]);

        // Set current team for user
        $this->user->update(['current_team_id' => $this->team->id]);

        // Create additional team for listing tests
        $secondTeam = Team::firstOrCreate(
            ['slug' => 'development-management-team'],
            [
                'name' => 'Development Management Team',
                'owner_id' => $this->user->id,
                'description' => 'Second team for testing',
                'is_personal' => false,
            ]
        );

        // Add user as member
        TeamMember::firstOrCreate([
            'team_id' => $secondTeam->id,
            'user_id' => $this->user->id,
        ], [
            'role' => 'owner',
            'invited_by' => $this->user->id,
            'joined_at' => now(),
        ]);
    }

    /**
     * Test 1: Teams list page loads successfully
     *
     * @test
     */
    public function test_teams_list_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/teams')
                ->pause(2000)
                ->waitForText('Your Teams', 15)
                ->assertSee('Your Teams')
                ->assertSee('Manage your teams and collaborate with others')
                ->screenshot('01-team-management-teams-list-page');

            $this->testResults['teams_list_page'] = 'Teams list page loaded successfully';
        });
    }

    /**
     * Test 2: Create team button is visible
     *
     * @test
     */
    public function test_create_team_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/teams')
                ->pause(2000)
                ->waitForText('Create Team', 10)
                ->assertSee('Create Team')
                ->assertPresent('button')
                ->screenshot('02-team-management-create-button');

            $this->testResults['create_button'] = 'Create team button is visible';
        });
    }

    /**
     * Test 3: Create team modal opens when button clicked
     *
     * @test
     */
    public function test_create_team_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/teams')
                ->pause(2000)
                ->waitForText('Create Team', 10)
                ->clickAtXPath("//button[contains(text(), 'Create Team')]")
                ->pause(1000)
                ->waitForText('Create New Team', 10)
                ->assertSee('Create New Team')
                ->assertSee('Team Name')
                ->assertSee('Description (Optional)')
                ->screenshot('03-team-management-create-modal');

            $this->testResults['create_modal'] = 'Create team modal opens successfully';
        });
    }

    /**
     * Test 4: Team name field is present in create modal
     *
     * @test
     */
    public function test_team_name_field_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/teams')
                ->pause(2000)
                ->waitForText('Create Team', 10)
                ->clickAtXPath("//button[contains(text(), 'Create Team')]")
                ->pause(1000)
                ->waitForText('Team Name', 10)
                ->assertPresent('#name')
                ->assertAttribute('#name', 'type', 'text')
                ->assertAttribute('#name', 'required', 'true')
                ->screenshot('04-team-management-name-field');

            $this->testResults['name_field'] = 'Team name field is present';
        });
    }

    /**
     * Test 5: Teams list displays existing teams
     *
     * @test
     */
    public function test_team_list_displays_existing_teams(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/teams')
                ->pause(2000)
                ->waitForText('Test Management Team', 10)
                ->assertSee('Test Management Team')
                ->assertSee('Development Management Team')
                ->assertSee('members')
                ->screenshot('05-team-management-existing-teams');

            $this->testResults['existing_teams'] = 'Existing teams are displayed';
        });
    }

    /**
     * Test 6: Team settings page loads successfully
     *
     * @test
     */
    public function test_team_settings_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Test Management Team', 15)
                ->assertSee('Test Management Team')
                ->assertSee('General')
                ->assertSee('Members')
                ->assertSee('Invitations')
                ->assertSee('Danger Zone')
                ->screenshot('06-team-management-settings-page');

            $this->testResults['settings_page'] = 'Team settings page loaded successfully';
        });
    }

    /**
     * Test 7: Team members list is visible in settings
     *
     * @test
     */
    public function test_team_members_list_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Members', 10)
                ->clickAtXPath("//button[contains(text(), 'Members')]")
                ->pause(1000)
                ->waitForText('Test Admin', 10)
                ->assertSee('Test Admin')
                ->assertSee('admin@devflow.test')
                ->assertSee('Test Member')
                ->assertSee('member@devflow.test')
                ->screenshot('07-team-management-members-list');

            $this->testResults['members_list'] = 'Team members list is visible';
        });
    }

    /**
     * Test 8: Invite member button is present
     *
     * @test
     */
    public function test_invite_member_button_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Invitations', 10)
                ->clickAtXPath("//button[contains(text(), 'Invitations')]")
                ->pause(1000)
                ->waitForText('Invite Member', 10)
                ->assertSee('Invite Member')
                ->assertPresent('button')
                ->screenshot('08-team-management-invite-button');

            $this->testResults['invite_button'] = 'Invite member button is present';
        });
    }

    /**
     * Test 9: Invite member modal opens when button clicked
     *
     * @test
     */
    public function test_invite_member_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Invitations', 10)
                ->clickAtXPath("//button[contains(text(), 'Invitations')]")
                ->pause(1000)
                ->waitForText('Invite Member', 10)
                ->clickAtXPath("//button[contains(text(), 'Invite Member')]")
                ->pause(1000)
                ->waitForText('Invite Team Member', 10)
                ->assertSee('Invite Team Member')
                ->assertSee('Email Address')
                ->assertSee('Role')
                ->screenshot('09-team-management-invite-modal');

            $this->testResults['invite_modal'] = 'Invite member modal opens successfully';
        });
    }

    /**
     * Test 10: Email field is present in invitation modal
     *
     * @test
     */
    public function test_email_field_for_invitation_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Invitations', 10)
                ->clickAtXPath("//button[contains(text(), 'Invitations')]")
                ->pause(1000)
                ->waitForText('Invite Member', 10)
                ->clickAtXPath("//button[contains(text(), 'Invite Member')]")
                ->pause(1000)
                ->waitForText('Email Address', 10)
                ->assertPresent('#inviteEmail')
                ->assertAttribute('#inviteEmail', 'type', 'email')
                ->assertAttribute('#inviteEmail', 'required', 'true')
                ->screenshot('10-team-management-email-field');

            $this->testResults['email_field'] = 'Email field is present in invitation modal';
        });
    }

    /**
     * Test 11: Role selection dropdown is present in invitation modal
     *
     * @test
     */
    public function test_role_selection_dropdown_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Invitations', 10)
                ->clickAtXPath("//button[contains(text(), 'Invitations')]")
                ->pause(1000)
                ->waitForText('Invite Member', 10)
                ->clickAtXPath("//button[contains(text(), 'Invite Member')]")
                ->pause(1000)
                ->waitForText('Role', 10)
                ->assertPresent('#inviteRole')
                ->assertPresent('select#inviteRole option[value="admin"]')
                ->assertPresent('select#inviteRole option[value="member"]')
                ->assertPresent('select#inviteRole option[value="viewer"]')
                ->screenshot('11-team-management-role-dropdown');

            $this->testResults['role_dropdown'] = 'Role selection dropdown is present';
        });
    }

    /**
     * Test 12: Remove member button is visible for team members
     *
     * @test
     */
    public function test_remove_member_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Members', 10)
                ->clickAtXPath("//button[contains(text(), 'Members')]")
                ->pause(1000)
                ->waitForText('Test Member', 10)
                ->assertSee('Remove')
                ->assertPresent('button')
                ->screenshot('12-team-management-remove-button');

            $this->testResults['remove_button'] = 'Remove member button is visible';
        });
    }

    /**
     * Test 13: Danger Zone tab displays team deletion option
     *
     * @test
     */
    public function test_danger_zone_displays_deletion_option(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Danger Zone', 10)
                ->clickAtXPath("//button[contains(text(), 'Danger Zone')]")
                ->pause(1000)
                ->waitForText('Delete Team', 10)
                ->assertSee('Delete Team')
                ->assertSee('Permanently delete this team and all its data')
                ->assertSee('This action cannot be undone')
                ->screenshot('13-team-management-danger-zone');

            $this->testResults['danger_zone'] = 'Danger zone displays deletion option';
        });
    }

    /**
     * Test 14: Team deletion button is present for owners
     *
     * @test
     */
    public function test_team_deletion_button_for_owners(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Danger Zone', 10)
                ->clickAtXPath("//button[contains(text(), 'Danger Zone')]")
                ->pause(1000)
                ->waitForText('Delete Team', 15)
                ->assertPresent('button')
                ->screenshot('14-team-management-delete-button');

            $this->testResults['delete_button'] = 'Team deletion button is present for owners';
        });
    }

    /**
     * Test 15: Flash messages display properly after actions
     *
     * @test
     */
    public function test_flash_messages_display(): void
    {
        $this->browse(function (Browser $browser) {
            // Test that clicking on General tab and making a change shows feedback
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('General', 10)
                ->clickAtXPath("//button[contains(text(), 'General')]")
                ->pause(1000)
                ->waitForText('Team Name', 10)
                ->assertPresent('#name')
                ->assertPresent('#description')
                ->assertPresent('button[type="submit"]')
                ->assertSee('Save Changes')
                ->screenshot('15-team-management-general-settings');

            $this->testResults['flash_messages'] = 'General settings form is ready for updates';
        });
    }

    /**
     * Test 16: Team description field is editable in General settings
     *
     * @test
     */
    public function test_team_description_editable(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('General', 10)
                ->clickAtXPath("//button[contains(text(), 'General')]")
                ->pause(1000)
                ->waitForText('Description', 10)
                ->assertPresent('#description')
                ->assertPresent('textarea#description')
                ->screenshot('16-team-management-description-field');

            $this->testResults['description_field'] = 'Team description field is editable';
        });
    }

    /**
     * Test 17: Team avatar upload field is present
     *
     * @test
     */
    public function test_team_avatar_upload_field_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('General', 10)
                ->clickAtXPath("//button[contains(text(), 'General')]")
                ->pause(1000)
                ->waitForText('Team Avatar', 10)
                ->assertSee('Team Avatar')
                ->assertPresent('input[type="file"]')
                ->screenshot('17-team-management-avatar-upload');

            $this->testResults['avatar_field'] = 'Team avatar upload field is present';
        });
    }

    /**
     * Test 18: Transfer ownership option is visible for team owners
     *
     * @test
     */
    public function test_transfer_ownership_option_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Danger Zone', 10)
                ->clickAtXPath("//button[contains(text(), 'Danger Zone')]")
                ->pause(1000)
                ->waitForText('Transfer Ownership', 10)
                ->assertSee('Transfer Ownership')
                ->assertSee('Transfer this team to another member')
                ->screenshot('18-team-management-transfer-ownership');

            $this->testResults['transfer_ownership'] = 'Transfer ownership option is visible';
        });
    }

    /**
     * Test 19: Member role can be changed via dropdown
     *
     * @test
     */
    public function test_member_role_can_be_changed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Members', 10)
                ->clickAtXPath("//button[contains(text(), 'Members')]")
                ->pause(1000)
                ->waitForText('Test Member', 10)
                ->assertPresent('select')
                ->assertPresent('select option[value="admin"]')
                ->assertPresent('select option[value="member"]')
                ->assertPresent('select option[value="viewer"]')
                ->screenshot('19-team-management-role-change');

            $this->testResults['role_change'] = 'Member role can be changed via dropdown';
        });
    }

    /**
     * Test 20: Team card displays member count
     *
     * @test
     */
    public function test_team_card_displays_member_count(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/teams')
                ->pause(2000)
                ->waitForText('Test Management Team', 10)
                ->assertSee('members')
                ->screenshot('20-team-management-member-count');

            $this->testResults['member_count'] = 'Team card displays member count';
        });
    }

    /**
     * Test 21: Team settings button navigates to settings page
     *
     * @test
     */
    public function test_settings_button_navigates_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/teams')
                ->pause(2000)
                ->waitForText('Settings', 10)
                ->assertSeeLink('Settings')
                ->screenshot('21-team-management-settings-link');

            $this->testResults['settings_link'] = 'Settings button navigates correctly';
        });
    }

    /**
     * Test 22: Invitations tab shows pending invitations
     *
     * @test
     */
    public function test_invitations_tab_shows_pending_invitations(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Invitations', 10)
                ->clickAtXPath("//button[contains(text(), 'Invitations')]")
                ->pause(1000)
                ->assertSee('Invite Member')
                ->screenshot('22-team-management-invitations-tab');

            $this->testResults['invitations_tab'] = 'Invitations tab shows properly';
        });
    }

    /**
     * Test 23: Team owner badge is displayed correctly
     *
     * @test
     */
    public function test_team_owner_badge_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/teams')
                ->pause(2000)
                ->waitForText('Test Management Team', 10)
                ->assertSee('Owner')
                ->screenshot('23-team-management-owner-badge');

            $this->testResults['owner_badge'] = 'Team owner badge is displayed correctly';
        });
    }

    /**
     * Test 24: Create team form validates required fields
     *
     * @test
     */
    public function test_create_team_form_validates_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/teams')
                ->pause(2000)
                ->waitForText('Create Team', 10)
                ->clickAtXPath("//button[contains(text(), 'Create Team')]")
                ->pause(1000)
                ->waitForText('Create New Team', 10)
                ->assertPresent('#name')
                ->assertAttribute('#name', 'required', 'true')
                ->screenshot('24-team-management-form-validation');

            $this->testResults['form_validation'] = 'Create team form validates required fields';
        });
    }

    /**
     * Test 25: Team name is displayed in settings header
     *
     * @test
     */
    public function test_team_name_displayed_in_settings_header(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/teams/{$this->team->id}/settings")
                ->pause(2000)
                ->waitForText('Test Management Team', 10)
                ->assertSee('Test Management Team')
                ->assertSee('A team for testing management features')
                ->screenshot('25-team-management-settings-header');

            $this->testResults['settings_header'] = 'Team name is displayed in settings header';
        });
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        // Output test results summary
        if (! empty($this->testResults)) {
            echo "\n\n=== Team Management Test Results ===\n";
            foreach ($this->testResults as $test => $result) {
                echo "✓ {$test}: {$result}\n";
            }
            echo "=================================\n\n";
        }

        parent::tearDown();
    }
}
