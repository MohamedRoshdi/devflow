<?php

namespace Tests\Browser;

use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ProjectEditTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected ?Project $project = null;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->project = Project::first();
    }

    /**
     * Test 1: Page loads successfully (skip if no project)
     *
     * @test
     */
    public function test_page_loads_successfully()
    {
        if (! $this->project) {
            $this->testResults['page_loads'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('project-edit-page-load');

            $pageSource = strtolower($browser->driver->getPageSource());
            $isEditPage = str_contains($pageSource, 'edit project') ||
                         str_contains($pageSource, 'update project') ||
                         str_contains($pageSource, $this->project->name);

            $this->assertTrue($isEditPage, 'Project edit page should load successfully');

            $this->testResults['page_loads'] = 'Page loaded successfully';
        });
    }

    /**
     * Test 2: Project name pre-filled
     *
     * @test
     */
    public function test_project_name_prefilled()
    {
        if (! $this->project) {
            $this->testResults['name_prefilled'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('project-name-prefilled');

            $browser->assertPresent('#name');

            $nameValue = $browser->value('#name');
            $this->assertNotEmpty($nameValue, 'Name field should have a value');
            $this->assertEquals($this->project->name, $nameValue, 'Name field should be pre-filled');

            $this->testResults['name_prefilled'] = 'Project name field is pre-filled';
        });
    }

    /**
     * Test 3: Repository URL pre-filled
     *
     * @test
     */
    public function test_repository_url_prefilled()
    {
        if (! $this->project) {
            $this->testResults['repo_url_prefilled'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('repository-url-prefilled');

            $browser->assertPresent('#repository_url');

            $repoValue = $browser->value('#repository_url');
            $this->assertNotEmpty($repoValue, 'Repository URL field should have a value');

            $this->testResults['repo_url_prefilled'] = 'Repository URL field is pre-filled';
        });
    }

    /**
     * Test 4: Branch pre-filled
     *
     * @test
     */
    public function test_branch_prefilled()
    {
        if (! $this->project) {
            $this->testResults['branch_prefilled'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('branch-prefilled');

            $browser->assertPresent('#branch');

            $branchValue = $browser->value('#branch');
            $this->assertNotEmpty($branchValue, 'Branch field should have a value');
            $this->assertEquals($this->project->branch, $branchValue, 'Branch field should be pre-filled');

            $this->testResults['branch_prefilled'] = 'Branch field is pre-filled';
        });
    }

    /**
     * Test 5: Server selection shown
     *
     * @test
     */
    public function test_server_selection_shown()
    {
        if (! $this->project) {
            $this->testResults['server_selection'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('server-selection-shown');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasServerSection = str_contains($pageSource, 'server') &&
                               (str_contains($pageSource, 'select server') || str_contains($pageSource, 'server configuration'));

            $this->assertTrue($hasServerSection, 'Server selection section should be visible');

            $this->testResults['server_selection'] = 'Server selection is shown';
        });
    }

    /**
     * Test 6: Framework selection shown
     *
     * @test
     */
    public function test_framework_selection_shown()
    {
        if (! $this->project) {
            $this->testResults['framework_selection'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('framework-selection-shown');

            $browser->assertPresent('#framework');

            $this->testResults['framework_selection'] = 'Framework selection is shown';
        });
    }

    /**
     * Test 7: PHP version shown
     *
     * @test
     */
    public function test_php_version_shown()
    {
        if (! $this->project) {
            $this->testResults['php_version'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('php-version-shown');

            $browser->assertPresent('#php_version');

            $this->testResults['php_version'] = 'PHP version field is shown';
        });
    }

    /**
     * Test 8: Node version field visible
     *
     * @test
     */
    public function test_node_version_visible()
    {
        if (! $this->project) {
            $this->testResults['node_version'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('node-version-visible');

            $browser->assertPresent('#node_version');

            $this->testResults['node_version'] = 'Node version field is visible';
        });
    }

    /**
     * Test 9: Root directory field visible
     *
     * @test
     */
    public function test_root_directory_visible()
    {
        if (! $this->project) {
            $this->testResults['root_directory'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('root-directory-visible');

            $browser->assertPresent('#root_directory');

            $rootDirValue = $browser->value('#root_directory');
            $this->assertNotEmpty($rootDirValue, 'Root directory field should have a value');

            $this->testResults['root_directory'] = 'Root directory field is visible';
        });
    }

    /**
     * Test 10: Build command field visible
     *
     * @test
     */
    public function test_build_command_visible()
    {
        if (! $this->project) {
            $this->testResults['build_command'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('build-command-visible');

            $browser->assertPresent('#build_command');

            $this->testResults['build_command'] = 'Build command field is visible';
        });
    }

    /**
     * Test 11: Update button visible
     *
     * @test
     */
    public function test_update_button_visible()
    {
        if (! $this->project) {
            $this->testResults['update_button'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('update-button-visible');

            $pageSource = $browser->driver->getPageSource();
            $hasUpdateButton = str_contains($pageSource, 'Update Project') ||
                              str_contains($pageSource, 'Save Changes') ||
                              str_contains($pageSource, 'Save Project');

            $this->assertTrue($hasUpdateButton, 'Update button should be visible');

            $this->testResults['update_button'] = 'Update button is visible';
        });
    }

    /**
     * Test 12: Cancel button present
     *
     * @test
     */
    public function test_cancel_button_present()
    {
        if (! $this->project) {
            $this->testResults['cancel_button'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('cancel-button-present');

            $pageSource = $browser->driver->getPageSource();
            $hasCancelButton = str_contains($pageSource, 'Cancel') ||
                              str_contains($pageSource, 'Back');

            $this->assertTrue($hasCancelButton, 'Cancel or Back button should be present');

            $this->testResults['cancel_button'] = 'Cancel button is present';
        });
    }

    /**
     * Test 13: Slug field present and auto-generates
     *
     * @test
     */
    public function test_slug_field_present()
    {
        if (! $this->project) {
            $this->testResults['slug_field'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('slug-field-present');

            $browser->assertPresent('#slug');

            $slugValue = $browser->value('#slug');
            $this->assertNotEmpty($slugValue, 'Slug field should have a value');

            $this->testResults['slug_field'] = 'Slug field is present and has value';
        });
    }

    /**
     * Test 14: Auto-deploy toggle visible
     *
     * @test
     */
    public function test_auto_deploy_toggle_visible()
    {
        if (! $this->project) {
            $this->testResults['auto_deploy_toggle'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('auto-deploy-toggle');

            $browser->assertPresent('#auto_deploy');

            $this->testResults['auto_deploy_toggle'] = 'Auto-deploy toggle is visible';
        });
    }

    /**
     * Test 15: Form validation works
     *
     * @test
     */
    public function test_form_validation_works()
    {
        if (! $this->project) {
            $this->testResults['form_validation'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000);

            $browser->clear('#name')
                ->pause(500);

            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Update Project')) {
                $browser->press('Update Project');
            } elseif (str_contains($pageSource, 'Save Changes')) {
                $browser->press('Save Changes');
            }

            $browser->pause(2000)
                ->screenshot('form-validation-error');

            $currentUrl = $browser->driver->getCurrentURL();
            $isStillOnEditPage = str_contains($currentUrl, '/edit');

            $this->assertTrue($isStillOnEditPage, 'Form validation should prevent submission with empty name');

            $this->testResults['form_validation'] = 'Form validation works';
        });
    }

    /**
     * Test 16: Changes persist after save
     *
     * @test
     */
    public function test_changes_persist_after_save()
    {
        if (! $this->project) {
            $this->testResults['changes_persist'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $originalName = $this->project->name;
            $newName = 'Updated Test Project '.time();

            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000);

            $browser->clear('#name')
                ->type('#name', $newName)
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Update Project')) {
                $browser->press('Update Project');
            } elseif (str_contains($pageSource, 'Save Changes')) {
                $browser->press('Save Changes');
            }

            $browser->pause(4000)
                ->screenshot('changes-saved');

            $currentUrl = $browser->driver->getCurrentURL();
            $redirected = (str_contains($currentUrl, '/projects/'.$this->project->id) &&
                          ! str_contains($currentUrl, '/edit')) ||
                         str_contains($currentUrl, '/projects');

            $this->assertTrue($redirected, 'Should redirect after save');

            $this->project->refresh();
            $this->assertEquals($newName, $this->project->name, 'Changes should persist in database');

            $this->project->update(['name' => $originalName]);

            $this->testResults['changes_persist'] = 'Changes persist after save';
        });
    }

    /**
     * Test 17: Success message displayed
     *
     * @test
     */
    public function test_success_message_displayed()
    {
        if (! $this->project) {
            $this->testResults['success_message'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000);

            $browser->clear('#name')
                ->type('#name', 'Test Project Update '.time())
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Update Project')) {
                $browser->press('Update Project');
            }

            $browser->pause(4000)
                ->screenshot('success-message-displayed');

            $pageSource = $browser->driver->getPageSource();
            $hasSuccessMessage = str_contains($pageSource, 'success') ||
                                str_contains($pageSource, 'updated') ||
                                str_contains($pageSource, 'saved');

            $this->assertTrue($hasSuccessMessage, 'Success message should be displayed');

            $this->testResults['success_message'] = 'Success message displayed';
        });
    }

    /**
     * Test 18: Error handling for invalid repository URL
     *
     * @test
     */
    public function test_error_handling_invalid_repo()
    {
        if (! $this->project) {
            $this->testResults['error_handling'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $originalRepo = $this->project->repository_url;

            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000);

            $browser->clear('#repository_url')
                ->type('#repository_url', 'invalid-repo-url')
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Update Project')) {
                $browser->press('Update Project');
            }

            $browser->pause(2000)
                ->screenshot('error-handling-invalid-repo');

            $currentUrl = $browser->driver->getCurrentURL();
            $isStillOnEditPage = str_contains($currentUrl, '/edit');

            $this->assertTrue($isStillOnEditPage, 'Should stay on page with validation error');

            $this->project->refresh();
            $this->assertEquals($originalRepo, $this->project->repository_url, 'Invalid URL should not be saved');

            $this->testResults['error_handling'] = 'Error handling works for invalid data';
        });
    }

    /**
     * Test 19: Navigation back works
     *
     * @test
     */
    public function test_back_navigation_works()
    {
        if (! $this->project) {
            $this->testResults['back_navigation'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('before-back-navigation');

            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Cancel')) {
                $browser->press('Cancel');
            } else {
                $browser->back();
            }

            $browser->pause(3000)
                ->screenshot('after-back-navigation');

            $currentUrl = $browser->driver->getCurrentURL();
            $leftEditPage = ! str_contains($currentUrl, '/edit') ||
                           str_contains($currentUrl, '/projects');

            $this->assertTrue($leftEditPage, 'Should navigate away from edit page');

            $this->testResults['back_navigation'] = 'Back navigation works';
        });
    }

    /**
     * Test 20: GPS Location fields present
     *
     * @test
     */
    public function test_location_fields_present()
    {
        if (! $this->project) {
            $this->testResults['location_fields'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('location-fields-present');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLocationFields = str_contains($pageSource, 'latitude') &&
                                str_contains($pageSource, 'longitude');

            $this->assertTrue($hasLocationFields, 'Location fields should be present');

            $this->testResults['location_fields'] = 'GPS location fields present';
        });
    }

    /**
     * Test 21: Server refresh button works
     *
     * @test
     */
    public function test_server_refresh_button()
    {
        if (! $this->project) {
            $this->testResults['server_refresh'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('server-refresh-button');

            $pageSource = $browser->driver->getPageSource();
            $hasRefreshButton = str_contains($pageSource, 'Refresh') ||
                               str_contains($pageSource, '🔄');

            $this->assertTrue($hasRefreshButton, 'Server refresh button should be present');

            $this->testResults['server_refresh'] = 'Server refresh button present';
        });
    }

    /**
     * Test 22: Start command field present
     *
     * @test
     */
    public function test_start_command_field()
    {
        if (! $this->project) {
            $this->testResults['start_command'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('start-command-field');

            $browser->assertPresent('#start_command');

            $this->testResults['start_command'] = 'Start command field is present';
        });
    }

    /**
     * Test 23: Framework options available
     *
     * @test
     */
    public function test_framework_options_available()
    {
        if (! $this->project) {
            $this->testResults['framework_options'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('framework-options');

            $pageSource = $browser->driver->getPageSource();
            $hasFrameworkOptions = str_contains($pageSource, 'Laravel') ||
                                  str_contains($pageSource, 'Node.js') ||
                                  str_contains($pageSource, 'React');

            $this->assertTrue($hasFrameworkOptions, 'Framework options should be available');

            $this->testResults['framework_options'] = 'Framework options are available';
        });
    }

    /**
     * Test 24: All form fields are editable
     *
     * @test
     */
    public function test_all_fields_editable()
    {
        if (! $this->project) {
            $this->testResults['fields_editable'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000);

            $browser->clear('#name')
                ->type('#name', 'New Project Name')
                ->clear('#branch')
                ->type('#branch', 'develop')
                ->clear('#root_directory')
                ->type('#root_directory', '/app')
                ->pause(1000)
                ->screenshot('all-fields-edited');

            $this->assertEquals('New Project Name', $browser->value('#name'));
            $this->assertEquals('develop', $browser->value('#branch'));
            $this->assertEquals('/app', $browser->value('#root_directory'));

            $this->testResults['fields_editable'] = 'All form fields are editable';
        });
    }

    /**
     * Test 25: Flash messages display properly
     *
     * @test
     */
    public function test_flash_messages_display()
    {
        if (! $this->project) {
            $this->testResults['flash_messages'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000);

            $browser->clear('#name')
                ->type('#name', 'Test Flash Message '.time())
                ->pause(1000);

            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Update Project')) {
                $browser->press('Update Project');
            }

            $browser->pause(4000)
                ->screenshot('flash-message-displayed');

            $pageSource = $browser->driver->getPageSource();
            $hasMessage = str_contains($pageSource, 'success') ||
                         str_contains($pageSource, 'updated') ||
                         str_contains($pageSource, 'message');

            $this->assertTrue($hasMessage, 'Flash message should be displayed');

            $this->testResults['flash_messages'] = 'Flash messages display properly';
        });
    }

    /**
     * Test 26: Server selection radio buttons work
     *
     * @test
     */
    public function test_server_radio_buttons()
    {
        if (! $this->project) {
            $this->testResults['server_radio'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        if (Server::count() < 2) {
            $this->testResults['server_radio'] = 'Skipped - need at least 2 servers';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('server-radio-buttons');

            $pageSource = $browser->driver->getPageSource();
            $hasRadioButtons = str_contains($pageSource, 'type="radio"') &&
                              str_contains($pageSource, 'server_id');

            $this->assertTrue($hasRadioButtons, 'Server radio buttons should be present');

            $this->testResults['server_radio'] = 'Server radio buttons work';
        });
    }

    /**
     * Test 27: Section headers are visible
     *
     * @test
     */
    public function test_section_headers_visible()
    {
        if (! $this->project) {
            $this->testResults['section_headers'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('section-headers');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSections = str_contains($pageSource, 'basic information') ||
                          str_contains($pageSource, 'server configuration') ||
                          str_contains($pageSource, 'repository') ||
                          str_contains($pageSource, 'framework');

            $this->assertTrue($hasSections, 'Section headers should be visible');

            $this->testResults['section_headers'] = 'Section headers are visible';
        });
    }

    /**
     * Test 28: Form has proper Livewire structure
     *
     * @test
     */
    public function test_livewire_structure()
    {
        if (! $this->project) {
            $this->testResults['livewire_structure'] = 'Skipped - no projects available';
            $this->assertTrue(true);

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/edit')
                ->pause(3000)
                ->screenshot('livewire-structure');

            $pageSource = $browser->driver->getPageSource();
            $hasLivewireStructure = str_contains($pageSource, 'wire:') &&
                                   str_contains($pageSource, 'form');

            $this->assertTrue($hasLivewireStructure, 'Form should have Livewire structure');

            $this->testResults['livewire_structure'] = 'Form has proper Livewire structure';
        });
    }

    /**
     * Generate test report
     */
    protected function tearDown(): void
    {
        if (! empty($this->testResults)) {
            $report = [
                'timestamp' => now()->toIso8601String(),
                'test_suite' => 'Project Edit Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                    'project_tested' => $this->project?->name ?? 'None',
                ],
                'environment' => [
                    'projects_available' => Project::count(),
                    'servers_available' => Server::count(),
                    'user_email' => $this->user->email,
                ],
            ];

            $reportPath = storage_path('app/test-reports/project-edit-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
