<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Project;
use App\Models\Server;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class TenantManagerTest extends DuskTestCase
{
    use LoginViaUI;

    // use RefreshDatabase; // Disabled - testing against existing app

    protected User $user;

    protected Server $server;

    protected Project $multiTenantProject;

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing test user (shared database approach)
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Get or create test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'tenant-manager-test.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Tenant Manager Test Server',
                'ip_address' => '192.168.1.250',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Get or create multi-tenant test project
        $this->multiTenantProject = Project::firstOrCreate(
            ['slug' => 'multi-tenant-manager-test'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Multi-Tenant Manager Test',
                'framework' => 'laravel',
                'project_type' => 'multi_tenant',
                'environment' => 'production',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/tenant-manager-test.git',
                'branch' => 'main',
                'root_directory' => '/var/www/tenant-manager-test',
            ]
        );
    }

    /**
     * Test 1: Page loads successfully
     */
    public function test_tenant_manager_page_loads_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->assertSee('Tenant Manager')
                ->assertSee('Manage multi-tenant configurations and tenant databases')
                ->assertPresent('div, section, main')
                ->screenshot('tenant-manager-page-loads');
        });
    }

    /**
     * Test 2: Tenant list is displayed when project is selected
     */
    public function test_tenant_list_is_displayed(): void
    {
        // Create a test tenant
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-manager-list'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Tenant Manager List',
                'database' => 'tenant_manager_list',
                'admin_email' => 'admin@test-tenant-manager-list.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText($tenant->name, 10)
                ->assertSee($tenant->name)
                ->assertSee($tenant->subdomain)
                ->screenshot('tenant-manager-list-displayed');
        });
    }

    /**
     * Test 3: Create tenant button visible
     */
    public function test_create_tenant_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitFor('button:contains("Create Tenant")', 5)
                ->assertSee('Create Tenant')
                ->assertPresent('button:contains("Create Tenant")')
                ->screenshot('tenant-manager-create-button-visible');
        });
    }

    /**
     * Test 4: Create tenant modal opens
     */
    public function test_create_tenant_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitFor('button:contains("Create Tenant")', 5)
                ->click('button:contains("Create Tenant")')
                ->waitForText('Create New Tenant', 5)
                ->assertSee('Create New Tenant')
                ->assertPresent('form')
                ->screenshot('tenant-manager-create-modal-opens');
        });
    }

    /**
     * Test 5: Tenant name field present
     */
    public function test_tenant_name_field_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->click('button:contains("Create Tenant")')
                ->waitForText('Create New Tenant', 5)
                ->assertSee('Tenant Name')
                ->assertPresent('input[wire\\:model="tenantName"]')
                ->screenshot('tenant-manager-name-field-present');
        });
    }

    /**
     * Test 6: Tenant domain field present
     */
    public function test_tenant_domain_field_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->click('button:contains("Create Tenant")')
                ->waitForText('Create New Tenant', 5)
                ->assertSee('Subdomain')
                ->assertPresent('input[wire\\:model="subdomain"]')
                ->screenshot('tenant-manager-domain-field-present');
        });
    }

    /**
     * Test 7: Create tenant form submits
     */
    public function test_create_tenant_form_submits(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->click('button:contains("Create Tenant")')
                ->waitForText('Create New Tenant', 5)
                ->type('input[wire\\:model="tenantName"]', 'Test Form Submit Tenant')
                ->type('input[wire\\:model="subdomain"]', 'test-form-submit')
                ->type('input[wire\\:model="adminEmail"]', 'admin@test-form-submit.com')
                ->type('input[wire\\:model="adminPassword"]', 'password123')
                ->click('button[type="submit"]:contains("Create Tenant")')
                ->pause(2000)
                ->assertDontSee('Create New Tenant')
                ->screenshot('tenant-manager-form-submits');
        });
    }

    /**
     * Test 8: Tenant settings accessible
     */
    public function test_tenant_settings_accessible(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-settings-access'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Settings Access',
                'database' => 'tenant_settings_access',
                'admin_email' => 'admin@test-settings-access.com',
                'plan' => 'pro',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText($tenant->name, 10)
                ->assertPresent('button:contains("Edit")')
                ->click('button:contains("Edit")')
                ->pause(1000)
                ->waitForText('Edit Tenant', 5)
                ->assertSee('Edit Tenant')
                ->screenshot('tenant-manager-settings-accessible');
        });
    }

    /**
     * Test 9: Enable/disable toggle works
     */
    public function test_enable_disable_toggle_works(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-toggle-status'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Toggle Status',
                'database' => 'tenant_toggle_status',
                'admin_email' => 'admin@test-toggle-status.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText($tenant->name, 10)
                ->assertSee('Active')
                ->assertPresent('button:contains("Suspend")')
                ->screenshot('tenant-manager-toggle-works');
        });
    }

    /**
     * Test 10: Delete tenant button visible
     */
    public function test_delete_tenant_button_visible(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-delete-button'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Delete Button',
                'database' => 'tenant_delete_button',
                'admin_email' => 'admin@test-delete-button.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText($tenant->name, 10)
                ->assertPresent('button:contains("Delete")')
                ->assertSee('Delete')
                ->screenshot('tenant-manager-delete-button-visible');
        });
    }

    /**
     * Test 11: Tenant status indicators shown
     */
    public function test_tenant_status_indicators_shown(): void
    {
        $activeTenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-status-active'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Status Active',
                'database' => 'tenant_status_active',
                'admin_email' => 'admin@test-status-active.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $suspendedTenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-status-suspended'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Status Suspended',
                'database' => 'tenant_status_suspended',
                'admin_email' => 'admin@test-status-suspended.com',
                'plan' => 'basic',
                'status' => 'suspended',
            ]
        );

        $this->browse(function (Browser $browser) use ($activeTenant, $suspendedTenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText($activeTenant->name, 10)
                ->assertSee('Active')
                ->assertSee('Suspended')
                ->screenshot('tenant-manager-status-indicators-shown');
        });
    }

    /**
     * Test 12: Project filter works
     */
    public function test_project_filter_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->assertSee('Select Multi-Tenant Project')
                ->assertPresent('button:contains("'.$this->multiTenantProject->name.'")')
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->assertPresent('table, tbody, tr')
                ->screenshot('tenant-manager-project-filter-works');
        });
    }

    /**
     * Test 13: Search tenants works
     */
    public function test_search_tenants_works(): void
    {
        // Create multiple tenants for search testing
        Tenant::firstOrCreate(
            ['subdomain' => 'search-test-alpha'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Alpha Search Test',
                'database' => 'tenant_search_alpha',
                'admin_email' => 'admin@search-alpha.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        Tenant::firstOrCreate(
            ['subdomain' => 'search-test-beta'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Beta Search Test',
                'database' => 'tenant_search_beta',
                'admin_email' => 'admin@search-beta.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText('Alpha Search Test', 10)
                ->assertSee('Alpha Search Test')
                ->assertSee('Beta Search Test')
                ->screenshot('tenant-manager-search-works');
        });
    }

    /**
     * Test 14: Tenant details page loads
     */
    public function test_tenant_details_page_loads(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-details-load'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Details Load',
                'database' => 'tenant_details_load',
                'admin_email' => 'admin@test-details-load.com',
                'plan' => 'enterprise',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText($tenant->name, 10)
                ->click('button:contains("Details")')
                ->waitForText('Tenant Details', 5)
                ->assertSee('Tenant Details: '.$tenant->name)
                ->assertSee('Tenant ID')
                ->assertSee('Created')
                ->assertSee('Database')
                ->screenshot('tenant-manager-details-page-loads');
        });
    }

    /**
     * Test 15: Flash messages display
     */
    public function test_flash_messages_display(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->click('button:contains("Create Tenant")')
                ->waitForText('Create New Tenant', 5)
                ->click('button[type="submit"]:contains("Create Tenant")')
                ->pause(1000)
                ->assertPresent('.text-red-500, .text-red-600, [class*="error"]')
                ->screenshot('tenant-manager-flash-messages-display');
        });
    }

    /**
     * Test 16: Tenant plan badges are displayed correctly
     */
    public function test_tenant_plan_badges_displayed(): void
    {
        Tenant::firstOrCreate(
            ['subdomain' => 'test-plan-basic'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Plan Basic',
                'database' => 'tenant_plan_basic',
                'admin_email' => 'admin@test-plan-basic.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        Tenant::firstOrCreate(
            ['subdomain' => 'test-plan-pro'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Plan Pro',
                'database' => 'tenant_plan_pro',
                'admin_email' => 'admin@test-plan-pro.com',
                'plan' => 'pro',
                'status' => 'active',
            ]
        );

        Tenant::firstOrCreate(
            ['subdomain' => 'test-plan-enterprise'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Plan Enterprise',
                'database' => 'tenant_plan_enterprise',
                'admin_email' => 'admin@test-plan-enterprise.com',
                'plan' => 'enterprise',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText('Test Plan Basic', 10)
                ->assertSee('Basic')
                ->assertSee('Pro')
                ->assertSee('Enterprise')
                ->screenshot('tenant-manager-plan-badges-displayed');
        });
    }

    /**
     * Test 17: Tenant modal form has all required fields
     */
    public function test_tenant_modal_has_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->click('button:contains("Create Tenant")')
                ->waitForText('Create New Tenant', 5)
                ->assertPresent('input[wire\\:model="tenantName"]')
                ->assertPresent('input[wire\\:model="subdomain"]')
                ->assertPresent('input[wire\\:model="adminEmail"]')
                ->assertPresent('input[wire\\:model="adminPassword"]')
                ->assertPresent('select[wire\\:model="plan"]')
                ->assertPresent('select[wire\\:model="status"]')
                ->screenshot('tenant-manager-modal-required-fields');
        });
    }

    /**
     * Test 18: Deploy to tenants button is visible
     */
    public function test_deploy_to_tenants_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->assertPresent('button:contains("Deploy to Tenants")')
                ->assertSee('Deploy to Tenants')
                ->screenshot('tenant-manager-deploy-button-visible');
        });
    }

    /**
     * Test 19: Deploy modal opens and shows deployment options
     */
    public function test_deploy_modal_opens_with_options(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->click('button:contains("Deploy to Tenants")')
                ->waitForText('Deploy to Tenants', 5)
                ->assertSee('Deployment Type')
                ->assertPresent('select[wire\\:model="deploymentType"]')
                ->assertPresent('input[type="checkbox"][wire\\:model="clearCache"]')
                ->assertPresent('input[type="checkbox"][wire\\:model="maintenanceMode"]')
                ->screenshot('tenant-manager-deploy-modal-options');
        });
    }

    /**
     * Test 20: Tenant table shows all necessary columns
     */
    public function test_tenant_table_shows_all_columns(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->assertSee('Tenant')
                ->assertSee('Domain')
                ->assertSee('Plan')
                ->assertSee('Users')
                ->assertSee('Storage')
                ->assertSee('Status')
                ->assertSee('Actions')
                ->screenshot('tenant-manager-table-columns');
        });
    }

    /**
     * Test 21: Tenant select all checkbox works
     */
    public function test_tenant_select_all_checkbox_works(): void
    {
        // Create some tenants
        for ($i = 1; $i <= 3; $i++) {
            Tenant::firstOrCreate(
                ['subdomain' => 'test-select-all-'.$i],
                [
                    'project_id' => $this->multiTenantProject->id,
                    'name' => 'Test Select All '.$i,
                    'database' => 'tenant_select_all_'.$i,
                    'admin_email' => 'admin@test-select-all-'.$i.'.com',
                    'plan' => 'basic',
                    'status' => 'active',
                ]
            );
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText('Test Select All', 10)
                ->assertPresent('input[type="checkbox"]')
                ->screenshot('tenant-manager-select-all-works');
        });
    }

    /**
     * Test 22: Tenant details modal shows database information
     */
    public function test_tenant_details_shows_database_info(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-db-info'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test DB Info',
                'database' => 'tenant_db_info',
                'admin_email' => 'admin@test-db-info.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText($tenant->name, 10)
                ->click('button:contains("Details")')
                ->waitForText('Tenant Details', 5)
                ->assertSee('Database')
                ->assertSee($tenant->database)
                ->screenshot('tenant-manager-details-database-info');
        });
    }

    /**
     * Test 23: Tenant backup button is visible in details
     */
    public function test_tenant_backup_button_visible(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-backup-button'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Backup Button',
                'database' => 'tenant_backup_button',
                'admin_email' => 'admin@test-backup-button.com',
                'plan' => 'pro',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText($tenant->name, 10)
                ->click('button:contains("Details")')
                ->waitForText('Tenant Details', 5)
                ->assertPresent('button:contains("Backup")')
                ->assertSee('Backup')
                ->screenshot('tenant-manager-backup-button-visible');
        });
    }

    /**
     * Test 24: Tenant reset data button is visible
     */
    public function test_tenant_reset_data_button_visible(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-reset-button'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Reset Button',
                'database' => 'tenant_reset_button',
                'admin_email' => 'admin@test-reset-button.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText($tenant->name, 10)
                ->click('button:contains("Details")')
                ->waitForText('Tenant Details', 5)
                ->assertPresent('button:contains("Reset Data")')
                ->assertSee('Reset Data')
                ->screenshot('tenant-manager-reset-button-visible');
        });
    }

    /**
     * Test 25: Tenant count is displayed correctly
     */
    public function test_tenant_count_displayed_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->assertSee('total tenants')
                ->assertPresent('span:contains("total tenants")')
                ->screenshot('tenant-manager-count-displayed');
        });
    }

    /**
     * Test 26: Empty state is shown when no tenants exist
     */
    public function test_empty_state_shown_when_no_tenants(): void
    {
        // Create a new project without tenants
        $emptyProject = Project::firstOrCreate(
            ['slug' => 'empty-tenant-project-manager'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Empty Tenant Project Manager',
                'framework' => 'laravel',
                'project_type' => 'multi_tenant',
                'environment' => 'production',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/empty-tenant-manager.git',
                'branch' => 'main',
                'root_directory' => '/var/www/empty-tenant-manager',
            ]
        );

        $this->browse(function (Browser $browser) use ($emptyProject) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$emptyProject->name.'")')
                ->pause(1000)
                ->assertSee('No tenants created yet')
                ->screenshot('tenant-manager-empty-state');
        });
    }

    /**
     * Test 27: Pagination is shown when many tenants exist
     */
    public function test_pagination_shown_with_many_tenants(): void
    {
        // Create multiple tenants for pagination
        for ($i = 1; $i <= 15; $i++) {
            Tenant::firstOrCreate(
                ['subdomain' => 'pagination-test-'.$i],
                [
                    'project_id' => $this->multiTenantProject->id,
                    'name' => 'Pagination Test '.$i,
                    'database' => 'tenant_pagination_'.$i,
                    'admin_email' => 'admin@pagination-test-'.$i.'.com',
                    'plan' => 'basic',
                    'status' => 'active',
                ]
            );
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText('Pagination Test', 10)
                ->assertPresent('nav, .pagination, a[rel="next"]')
                ->screenshot('tenant-manager-pagination-shown');
        });
    }

    /**
     * Test 28: Tenant subdomain link is clickable
     */
    public function test_tenant_subdomain_link_clickable(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-subdomain-link'],
            [
                'project_id' => $this->multiTenantProject->id,
                'name' => 'Test Subdomain Link',
                'database' => 'tenant_subdomain_link',
                'admin_email' => 'admin@test-subdomain-link.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->waitForText($tenant->name, 10)
                ->assertPresent('a:contains("'.$tenant->subdomain.'")')
                ->screenshot('tenant-manager-subdomain-link-clickable');
        });
    }

    /**
     * Test 29: Deployment type dropdown has all options
     */
    public function test_deployment_type_dropdown_has_options(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->click('button:contains("Deploy to Tenants")')
                ->waitForText('Deploy to Tenants', 5)
                ->assertSee('Code Only')
                ->assertSee('Code + Migrations')
                ->assertSee('Full Deployment')
                ->assertSee('Migrations Only')
                ->screenshot('tenant-manager-deployment-type-options');
        });
    }

    /**
     * Test 30: Modal cancel buttons work correctly
     */
    public function test_modal_cancel_buttons_work(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenant Manager', 10)
                ->click('button:contains("'.$this->multiTenantProject->name.'")')
                ->pause(1000)
                ->click('button:contains("Create Tenant")')
                ->waitForText('Create New Tenant', 5)
                ->assertSee('Create New Tenant')
                ->click('button:contains("Cancel")')
                ->pause(1000)
                ->assertDontSee('Create New Tenant')
                ->screenshot('tenant-manager-cancel-button-works');
        });
    }
}
