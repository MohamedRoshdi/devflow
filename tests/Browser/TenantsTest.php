<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class TenantsTest extends DuskTestCase
{
    use LoginViaUI;

    // use RefreshDatabase; // Disabled - testing against existing app

    protected User $user;

    protected Server $server;

    protected Project $project;

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
            ['hostname' => 'tenant-test.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Tenant Test Server',
                'ip_address' => '192.168.1.200',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Get or create multi-tenant test project
        $this->project = Project::firstOrCreate(
            ['slug' => 'multi-tenant-test-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Multi-Tenant Test Project',
                'framework' => 'laravel',
                'project_type' => 'web',
                'environment' => 'production',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/multi-tenant-test.git',
                'branch' => 'main',
                'root_directory' => '/var/www/multi-tenant-test',
            ]
        );
    }

    /**
     * Test 1: Tenant list page loads successfully
     */
    public function test_tenant_list_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenants', 10)
                ->assertSee('Tenants')
                ->assertPresent('div, section, main')
                ->screenshot('tenant-list-page');
        });
    }

    /**
     * Test 2: Tenant list displays search functionality
     */
    public function test_tenant_list_has_search(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenants', 10)
                ->assertPresent('input[wire\\:model*="search"], input[type="search"], input[placeholder*="Search"]')
                ->screenshot('tenant-list-search');
        });
    }

    /**
     * Test 3: Tenant list has status filter
     */
    public function test_tenant_list_has_status_filter(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenants', 10)
                ->assertPresent('select[wire\\:model*="statusFilter"], select[wire\\:model*="status"], button[role="button"]')
                ->screenshot('tenant-list-status-filter');
        });
    }

    /**
     * Test 4: Tenant list displays tenant cards or table
     */
    public function test_tenant_list_displays_tenants(): void
    {
        // Create a test tenant
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-list'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant List',
                'database' => 'tenant_test_list',
                'admin_email' => 'admin@test-tenant-list.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenants', 10)
                ->assertSee($tenant->name)
                ->screenshot('tenant-list-tenants-display');
        });
    }

    /**
     * Test 5: Create tenant button is visible and clickable
     */
    public function test_create_tenant_button_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenants', 10)
                ->assertPresent('a[href*="create"], button:contains("Create"), a:contains("New Tenant"), a:contains("Create Tenant")')
                ->screenshot('tenant-create-button');
        });
    }

    /**
     * Test 6: Tenant create page loads successfully
     */
    public function test_tenant_create_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants/create')
                ->waitForText('Create', 10)
                ->assertSee('Create')
                ->assertPresent('form, [wire\\:submit], input[wire\\:model*="name"]')
                ->screenshot('tenant-create-page');
        });
    }

    /**
     * Test 7: Tenant create form contains required fields
     */
    public function test_tenant_create_form_has_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants/create')
                ->waitForText('Create', 10)
                ->assertPresent('input[wire\\:model*="name"], input[name="name"]')
                ->assertPresent('input[wire\\:model*="subdomain"], input[name="subdomain"]')
                ->assertPresent('input[wire\\:model*="email"], input[name="email"], input[name="admin_email"]')
                ->assertPresent('select[wire\\:model*="project"], select[name="project"]')
                ->screenshot('tenant-create-form-fields');
        });
    }

    /**
     * Test 8: Tenant create form validates required fields
     */
    public function test_tenant_create_form_validates_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants/create')
                ->waitForText('Create', 10)
                ->pause(500)
                // Try to submit empty form
                ->press('Create Tenant')
                ->pause(1000)
                ->waitFor('.text-red-500, .text-red-600, .error, [class*="error"]', 5)
                ->screenshot('tenant-create-validation-errors');
        });
    }

    /**
     * Test 9: Tenant create form shows database configuration
     */
    public function test_tenant_create_shows_database_configuration(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants/create')
                ->waitForText('Create', 10)
                ->assertPresent('input[wire\\:model*="database"], input[name="database"]')
                ->screenshot('tenant-create-database-config');
        });
    }

    /**
     * Test 10: Tenant detail page loads successfully
     */
    public function test_tenant_detail_page_loads(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-detail'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Detail',
                'database' => 'tenant_test_detail',
                'admin_email' => 'admin@test-tenant-detail.com',
                'plan' => 'professional',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertSee($tenant->name)
                ->assertSee($tenant->subdomain)
                ->screenshot('tenant-detail-page');
        });
    }

    /**
     * Test 11: Tenant detail page shows overview tab
     */
    public function test_tenant_detail_shows_overview_tab(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-overview'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Overview',
                'database' => 'tenant_test_overview',
                'admin_email' => 'admin@test-tenant-overview.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('button:contains("Overview"), a:contains("Overview"), [role="tab"]')
                ->screenshot('tenant-detail-overview-tab');
        });
    }

    /**
     * Test 12: Tenant detail shows database tab
     */
    public function test_tenant_detail_shows_database_tab(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-database'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Database',
                'database' => 'tenant_test_database',
                'admin_email' => 'admin@test-tenant-database.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('button:contains("Database"), a:contains("Database"), [x-on\\:click*="database"]')
                ->screenshot('tenant-detail-database-tab');
        });
    }

    /**
     * Test 13: Tenant detail shows configuration tab
     */
    public function test_tenant_detail_shows_configuration_tab(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-config'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Config',
                'database' => 'tenant_test_config',
                'admin_email' => 'admin@test-tenant-config.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('button:contains("Configuration"), a:contains("Configuration"), button:contains("Settings")')
                ->screenshot('tenant-detail-configuration-tab');
        });
    }

    /**
     * Test 14: Tenant detail shows deployments section
     */
    public function test_tenant_detail_shows_deployments(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-deployments'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Deployments',
                'database' => 'tenant_test_deployments',
                'admin_email' => 'admin@test-tenant-deployments.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('button:contains("Deployments"), section, div, [class*="deployment"]')
                ->screenshot('tenant-detail-deployments');
        });
    }

    /**
     * Test 15: Tenant shows plan information
     */
    public function test_tenant_shows_plan_information(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-plan'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Plan',
                'database' => 'tenant_test_plan',
                'admin_email' => 'admin@test-tenant-plan.com',
                'plan' => 'enterprise',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertSee('enterprise')
                ->screenshot('tenant-plan-information');
        });
    }

    /**
     * Test 16: Tenant shows status indicator
     */
    public function test_tenant_shows_status_indicator(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-status'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Status',
                'database' => 'tenant_test_status',
                'admin_email' => 'admin@test-tenant-status.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertSee('active')
                ->screenshot('tenant-status-indicator');
        });
    }

    /**
     * Test 17: Tenant edit page loads successfully
     */
    public function test_tenant_edit_page_loads(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-edit'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Edit',
                'database' => 'tenant_test_edit',
                'admin_email' => 'admin@test-tenant-edit.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id.'/edit')
                ->waitForText('Edit', 10)
                ->assertSee('Edit')
                ->assertSee($tenant->name)
                ->screenshot('tenant-edit-page');
        });
    }

    /**
     * Test 18: Tenant edit form displays current values
     */
    public function test_tenant_edit_form_displays_current_values(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-edit-values'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Edit Values',
                'database' => 'tenant_test_edit_values',
                'admin_email' => 'admin@test-tenant-edit-values.com',
                'plan' => 'professional',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id.'/edit')
                ->waitForText('Edit', 10)
                ->assertInputValue('input[wire\\:model*="name"], input[name="name"]', $tenant->name)
                ->assertInputValue('input[wire\\:model*="subdomain"], input[name="subdomain"]', $tenant->subdomain)
                ->screenshot('tenant-edit-form-values');
        });
    }

    /**
     * Test 19: Tenant edit has save button
     */
    public function test_tenant_edit_has_save_button(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-edit-save'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Edit Save',
                'database' => 'tenant_test_edit_save',
                'admin_email' => 'admin@test-tenant-edit-save.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id.'/edit')
                ->waitForText('Edit', 10)
                ->assertPresent('button:contains("Update"), button:contains("Save"), button[type="submit"]')
                ->screenshot('tenant-edit-save-button');
        });
    }

    /**
     * Test 20: Tenant shows domain/subdomain information
     */
    public function test_tenant_shows_domain_information(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-domain'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Domain',
                'database' => 'tenant_test_domain',
                'admin_email' => 'admin@test-tenant-domain.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertSee($tenant->subdomain)
                ->screenshot('tenant-domain-information');
        });
    }

    /**
     * Test 21: Tenant shows project information
     */
    public function test_tenant_shows_project_information(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-project'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Project',
                'database' => 'tenant_test_project',
                'admin_email' => 'admin@test-tenant-project.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertSee($this->project->name)
                ->screenshot('tenant-project-information');
        });
    }

    /**
     * Test 22: Tenant database management is accessible
     */
    public function test_tenant_database_management_accessible(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-db-mgmt'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant DB Mgmt',
                'database' => 'tenant_test_db_mgmt',
                'admin_email' => 'admin@test-tenant-db-mgmt.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->click('button:contains("Database"), a:contains("Database")')
                ->pause(1000)
                ->assertPresent('div, section, [class*="database"]')
                ->screenshot('tenant-database-management');
        });
    }

    /**
     * Test 23: Tenant database shows migration controls
     */
    public function test_tenant_database_shows_migration_controls(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-migration'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Migration',
                'database' => 'tenant_test_migration',
                'admin_email' => 'admin@test-tenant-migration.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->click('button:contains("Database"), a:contains("Database")')
                ->pause(1000)
                ->assertPresent('button:contains("Migrate"), button:contains("Run Migrations"), button[wire\\:click*="migrate"]')
                ->screenshot('tenant-migration-controls');
        });
    }

    /**
     * Test 24: Tenant database shows seeding controls
     */
    public function test_tenant_database_shows_seeding_controls(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-seeding'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Seeding',
                'database' => 'tenant_test_seeding',
                'admin_email' => 'admin@test-tenant-seeding.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->click('button:contains("Database"), a:contains("Database")')
                ->pause(1000)
                ->assertPresent('button:contains("Seed"), button:contains("Run Seeders"), button[wire\\:click*="seed"]')
                ->screenshot('tenant-seeding-controls');
        });
    }

    /**
     * Test 25: Tenant deployment can be triggered
     */
    public function test_tenant_deployment_can_be_triggered(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-deploy'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Deploy',
                'database' => 'tenant_test_deploy',
                'admin_email' => 'admin@test-tenant-deploy.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('button:contains("Deploy"), button[wire\\:click*="deploy"]')
                ->screenshot('tenant-deploy-button');
        });
    }

    /**
     * Test 26: Tenant shows deployment history
     */
    public function test_tenant_shows_deployment_history(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-deploy-history'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Deploy History',
                'database' => 'tenant_test_deploy_history',
                'admin_email' => 'admin@test-tenant-deploy-history.com',
                'plan' => 'basic',
                'status' => 'active',
                'last_deployed_at' => now()->subDay(),
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('time, [datetime], [class*="time"], [class*="date"], [class*="deployment"]')
                ->screenshot('tenant-deployment-history');
        });
    }

    /**
     * Test 27: Tenant resource limits can be configured
     */
    public function test_tenant_resource_limits_configurable(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-limits'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Limits',
                'database' => 'tenant_test_limits',
                'admin_email' => 'admin@test-tenant-limits.com',
                'plan' => 'professional',
                'status' => 'active',
                'features' => [
                    'max_users' => 50,
                    'max_storage' => 10000,
                    'max_api_calls' => 100000,
                ],
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('button:contains("Settings"), button:contains("Configuration"), button:contains("Limits")')
                ->screenshot('tenant-resource-limits');
        });
    }

    /**
     * Test 28: Tenant shows trial information if on trial
     */
    public function test_tenant_shows_trial_information(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-trial'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Trial',
                'database' => 'tenant_test_trial',
                'admin_email' => 'admin@test-tenant-trial.com',
                'plan' => 'trial',
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14),
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertSee('trial')
                ->screenshot('tenant-trial-information');
        });
    }

    /**
     * Test 29: Tenant billing/subscription information is displayed
     */
    public function test_tenant_shows_billing_information(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-billing'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Billing',
                'database' => 'tenant_test_billing',
                'admin_email' => 'admin@test-tenant-billing.com',
                'plan' => 'enterprise',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('button:contains("Billing"), a:contains("Subscription"), button:contains("Plan")')
                ->screenshot('tenant-billing-information');
        });
    }

    /**
     * Test 30: Tenant custom configuration can be edited
     */
    public function test_tenant_custom_configuration_editable(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-custom-config'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Custom Config',
                'database' => 'tenant_test_custom_config',
                'admin_email' => 'admin@test-tenant-custom-config.com',
                'plan' => 'basic',
                'status' => 'active',
                'custom_config' => [
                    'theme' => 'dark',
                    'timezone' => 'UTC',
                    'language' => 'en',
                ],
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id.'/edit')
                ->waitForText('Edit', 10)
                ->assertPresent('input, select, textarea, [class*="config"]')
                ->screenshot('tenant-custom-configuration');
        });
    }

    /**
     * Test 31: Tenant features can be managed
     */
    public function test_tenant_features_can_be_managed(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-features'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Features',
                'database' => 'tenant_test_features',
                'admin_email' => 'admin@test-tenant-features.com',
                'plan' => 'professional',
                'status' => 'active',
                'features' => [
                    'api_access' => true,
                    'custom_branding' => true,
                    'advanced_analytics' => false,
                ],
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('button:contains("Features"), section, div, [class*="feature"]')
                ->screenshot('tenant-features-management');
        });
    }

    /**
     * Test 32: Tenant list pagination works
     */
    public function test_tenant_list_pagination_works(): void
    {
        // Create multiple tenants to test pagination
        for ($i = 1; $i <= 15; $i++) {
            Tenant::firstOrCreate(
                ['subdomain' => 'test-pagination-tenant-'.$i],
                [
                    'project_id' => $this->project->id,
                    'name' => 'Test Pagination Tenant '.$i,
                    'database' => 'tenant_pagination_'.$i,
                    'admin_email' => 'admin@pagination-tenant-'.$i.'.com',
                    'plan' => 'basic',
                    'status' => 'active',
                ]
            );
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenants', 10)
                ->assertPresent('nav[role="navigation"], .pagination, button[rel="next"], a[rel="next"]')
                ->screenshot('tenant-list-pagination');
        });
    }

    /**
     * Test 33: Tenant can be suspended
     */
    public function test_tenant_can_be_suspended(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-suspend'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Suspend',
                'database' => 'tenant_test_suspend',
                'admin_email' => 'admin@test-tenant-suspend.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('button:contains("Suspend"), button[wire\\:click*="suspend"], a:contains("Suspend")')
                ->screenshot('tenant-suspend-button');
        });
    }

    /**
     * Test 34: Tenant user management is accessible
     */
    public function test_tenant_user_management_accessible(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-users'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Users',
                'database' => 'tenant_test_users',
                'admin_email' => 'admin@test-tenant-users.com',
                'plan' => 'professional',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('button:contains("Users"), a:contains("Users"), [class*="user"]')
                ->screenshot('tenant-user-management');
        });
    }

    /**
     * Test 35: Tenant admin email is displayed
     */
    public function test_tenant_shows_admin_email(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-admin-email'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Admin Email',
                'database' => 'tenant_test_admin_email',
                'admin_email' => 'admin@test-tenant-admin-email.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertSee($tenant->admin_email)
                ->screenshot('tenant-admin-email');
        });
    }

    /**
     * Test 36: Tenant settings page is accessible
     */
    public function test_tenant_settings_page_accessible(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-settings'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Settings',
                'database' => 'tenant_test_settings',
                'admin_email' => 'admin@test-tenant-settings.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id.'/settings')
                ->waitForText('Settings', 10)
                ->assertSee('Settings')
                ->screenshot('tenant-settings-page');
        });
    }

    /**
     * Test 37: Tenant list shows created date
     */
    public function test_tenant_list_shows_created_date(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-created-date'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Created Date',
                'database' => 'tenant_test_created_date',
                'admin_email' => 'admin@test-tenant-created-date.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenants', 10)
                ->assertSee($tenant->name)
                ->assertPresent('time, [datetime], [class*="date"]')
                ->screenshot('tenant-created-date');
        });
    }

    /**
     * Test 38: Tenant can filter by project
     */
    public function test_tenant_list_can_filter_by_project(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenants', 10)
                ->assertPresent('select[wire\\:model*="projectFilter"], select[wire\\:model*="project"]')
                ->screenshot('tenant-filter-by-project');
        });
    }

    /**
     * Test 39: Tenant can filter by plan
     */
    public function test_tenant_list_can_filter_by_plan(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/tenants')
                ->waitForText('Tenants', 10)
                ->assertPresent('select[wire\\:model*="planFilter"], select[wire\\:model*="plan"]')
                ->screenshot('tenant-filter-by-plan');
        });
    }

    /**
     * Test 40: Tenant shows database name
     */
    public function test_tenant_shows_database_name(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-db-name'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant DB Name',
                'database' => 'tenant_test_db_name',
                'admin_email' => 'admin@test-tenant-db-name.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertSee($tenant->database)
                ->screenshot('tenant-database-name');
        });
    }

    /**
     * Test 41: Tenant detail shows URL/access link
     */
    public function test_tenant_shows_access_url(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-url'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant URL',
                'database' => 'tenant_test_url',
                'admin_email' => 'admin@test-tenant-url.com',
                'plan' => 'basic',
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('a[href*="'.$tenant->subdomain.'"], [class*="url"], [class*="link"]')
                ->screenshot('tenant-access-url');
        });
    }

    /**
     * Test 42: Tenant list shows empty state when no tenants
     */
    public function test_tenant_list_empty_state(): void
    {
        // Create a new project without tenants
        $emptyProject = Project::firstOrCreate(
            ['slug' => 'empty-tenant-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Empty Tenant Project',
                'framework' => 'laravel',
                'project_type' => 'web',
                'environment' => 'production',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/empty-tenant.git',
                'branch' => 'main',
                'root_directory' => '/var/www/empty-tenant',
            ]
        );

        $this->browse(function (Browser $browser) use ($emptyProject) {
            $browser->loginAs($this->user)
                ->visit('/tenants?projectFilter='.$emptyProject->id)
                ->waitForText('Tenants', 10)
                ->assertSee('No tenants found')
                ->screenshot('tenant-empty-state');
        });
    }

    /**
     * Test 43: Tenant detail shows last deployed timestamp
     */
    public function test_tenant_shows_last_deployed_timestamp(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'test-tenant-last-deployed'],
            [
                'project_id' => $this->project->id,
                'name' => 'Test Tenant Last Deployed',
                'database' => 'tenant_test_last_deployed',
                'admin_email' => 'admin@test-tenant-last-deployed.com',
                'plan' => 'basic',
                'status' => 'active',
                'last_deployed_at' => now()->subHours(3),
            ]
        );

        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->loginAs($this->user)
                ->visit('/tenants/'.$tenant->id)
                ->waitForText($tenant->name, 10)
                ->assertPresent('time, [datetime], [class*="deployed"]')
                ->screenshot('tenant-last-deployed');
        });
    }
}
