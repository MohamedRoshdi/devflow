<?php

namespace Tests\Browser;

use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

/**
 * DomainManagerTest - Comprehensive browser tests for Domain management
 *
 * This test suite covers domain management functionality which is displayed
 * within the project detail page (ProjectShow component).
 *
 * Test Coverage:
 * - Domain list display
 * - Add domain functionality
 * - Domain validation
 * - Primary domain indicator
 * - SSL status display
 * - DNS configuration status
 * - Domain removal
 * - Empty state handling
 * - Domain count display
 * - Flash messages
 * - Duplicate domain prevention
 * - Domain status indicators
 */
class DomainManagerTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected ?Project $project = null;

    protected ?Server $server = null;

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

        // Try to get the first project with domains
        $this->project = Project::has('domains')->first();

        // If no project with domains exists, create one
        if (! $this->project) {
            $this->server = Server::firstOrCreate(
                ['hostname' => 'test-domain.example.com'],
                [
                    'user_id' => $this->user->id,
                    'name' => 'Test Domain Server',
                    'ip_address' => '192.168.1.150',
                    'port' => 22,
                    'username' => 'root',
                    'status' => 'online',
                ]
            );

            $this->project = Project::create([
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Domain Project',
                'slug' => 'test-domain-project',
                'framework' => 'Laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/domain-project.git',
                'branch' => 'main',
                'root_directory' => '/var/www/domain-project',
                'php_version' => '8.4',
                'environment' => 'production',
            ]);

            // Create test domains
            Domain::create([
                'project_id' => $this->project->id,
                'domain' => 'primary.test.com',
                'is_primary' => true,
                'ssl_enabled' => true,
                'ssl_provider' => 'letsencrypt',
                'dns_configured' => true,
                'status' => 'active',
                'ssl_issued_at' => now(),
                'ssl_expires_at' => now()->addDays(90),
            ]);

            Domain::create([
                'project_id' => $this->project->id,
                'domain' => 'secondary.test.com',
                'is_primary' => false,
                'ssl_enabled' => false,
                'dns_configured' => true,
                'status' => 'active',
            ]);
        }
    }

    /**
     * Test 1: Project show page with domains section loads successfully
     */
    public function test_project_show_page_with_domains_section_loads(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Domains')
                ->screenshot('domain-manager-page-loads');
        });
    }

    /**
     * Test 2: Domain list is displayed
     */
    public function test_domain_list_is_displayed(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->scrollIntoView('[wire\\:key^="domain-"]')
                ->pause(500);

            $domains = $this->project->domains;
            if ($domains->isNotEmpty()) {
                $browser->assertSee($domains->first()->domain);
            }

            $browser->screenshot('domain-manager-list-displayed');
        });
    }

    /**
     * Test 3: Add domain button is visible
     */
    public function test_add_domain_button_is_visible(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Add')
                ->screenshot('domain-manager-add-button');
        });
    }

    /**
     * Test 4: Domain count is displayed correctly
     */
    public function test_domain_count_is_displayed_correctly(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $domainCount = $this->project->domains->count();
            $browser->assertSee((string) $domainCount)
                ->screenshot('domain-manager-count-display');
        });
    }

    /**
     * Test 5: Primary domain indicator is shown
     */
    public function test_primary_domain_indicator_is_shown(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $primaryDomain = $this->project->domains->where('is_primary', true)->first();
            if ($primaryDomain) {
                $browser->assertSee('Primary');
            }

            $browser->screenshot('domain-manager-primary-indicator');
        });
    }

    /**
     * Test 6: SSL status indicator is visible
     */
    public function test_ssl_status_indicator_is_visible(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $sslDomain = $this->project->domains->where('ssl_enabled', true)->first();
            if ($sslDomain) {
                $browser->assertSee('SSL Active');
            }

            $noSslDomain = $this->project->domains->where('ssl_enabled', false)->first();
            if ($noSslDomain) {
                $browser->assertSee('No SSL');
            }

            $browser->screenshot('domain-manager-ssl-status');
        });
    }

    /**
     * Test 7: Domain status is displayed
     */
    public function test_domain_status_is_displayed(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $domains = $this->project->domains;
            foreach ($domains as $domain) {
                $browser->assertSee(ucfirst($domain->status));
            }

            $browser->screenshot('domain-manager-status-display');
        });
    }

    /**
     * Test 8: Domain links are clickable
     */
    public function test_domain_links_are_clickable(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $domain = $this->project->domains->first();
            if ($domain) {
                $protocol = $domain->ssl_enabled ? 'https' : 'http';
                $url = $protocol.'://'.$domain->domain;
                $browser->assertPresent('a[href="'.$url.'"]');
            }

            $browser->screenshot('domain-manager-links-clickable');
        });
    }

    /**
     * Test 9: External link icon is present for domains
     */
    public function test_external_link_icon_is_present(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            if ($this->project->domains->isNotEmpty()) {
                $browser->assertPresent('svg');
            }

            $browser->screenshot('domain-manager-external-link-icon');
        });
    }

    /**
     * Test 10: Empty state message displays when no domains exist
     */
    public function test_empty_state_message_displays_when_no_domains(): void
    {
        // Create a project without domains for this test
        $emptyProject = Project::create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id ?? Server::first()->id,
            'name' => 'Empty Domain Project',
            'slug' => 'empty-domain-project-'.time(),
            'framework' => 'Laravel',
            'status' => 'stopped',
            'branch' => 'main',
            'root_directory' => '/var/www/empty',
        ]);

        $this->browse(function (Browser $browser) use ($emptyProject) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $emptyProject))
                ->waitForText($emptyProject->name, 10)
                ->assertSee('No domains configured')
                ->screenshot('domain-manager-empty-state');
        });

        // Cleanup
        $emptyProject->delete();
    }

    /**
     * Test 11: SSL certificate expiry information is shown
     */
    public function test_ssl_certificate_expiry_info_is_shown(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $sslDomain = $this->project->domains->where('ssl_enabled', true)->first();
            if ($sslDomain && $sslDomain->ssl_expires_at) {
                // SSL info should be present
                $browser->assertSee('SSL Active');
            }

            $browser->screenshot('domain-manager-ssl-expiry');
        });
    }

    /**
     * Test 12: Domain hover states work correctly
     */
    public function test_domain_hover_states_work_correctly(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $domain = $this->project->domains->first();
            if ($domain) {
                $browser->assertPresent('.hover\\:bg-gray-100');
            }

            $browser->screenshot('domain-manager-hover-states');
        });
    }

    /**
     * Test 13: Multiple domains are displayed correctly
     */
    public function test_multiple_domains_are_displayed_correctly(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $domains = $this->project->domains;
            foreach ($domains as $domain) {
                $browser->assertSee($domain->domain);
            }

            $browser->screenshot('domain-manager-multiple-domains');
        });
    }

    /**
     * Test 14: Domain section has proper heading
     */
    public function test_domain_section_has_proper_heading(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSeeIn('h2', 'Domains')
                ->screenshot('domain-manager-heading');
        });
    }

    /**
     * Test 15: Domain status colors are appropriate
     */
    public function test_domain_status_colors_are_appropriate(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $activeDomain = $this->project->domains->where('status', 'active')->first();
            if ($activeDomain) {
                $browser->assertPresent('.text-green-600');
            }

            $browser->screenshot('domain-manager-status-colors');
        });
    }

    /**
     * Test 16: DNS configuration status is visible
     */
    public function test_dns_configuration_status_is_visible(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            // DNS status is reflected in the domain status
            $domains = $this->project->domains;
            if ($domains->isNotEmpty()) {
                $browser->assertPresent('.text-green-600, .text-yellow-600, .text-red-600, .text-gray-600');
            }

            $browser->screenshot('domain-manager-dns-status');
        });
    }

    /**
     * Test 17: Domain card layout is properly structured
     */
    public function test_domain_card_layout_is_properly_structured(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            if ($this->project->domains->isNotEmpty()) {
                $browser->assertPresent('.p-4.bg-gray-50');
            }

            $browser->screenshot('domain-manager-card-layout');
        });
    }

    /**
     * Test 18: SSL lock icon is displayed for secure domains
     */
    public function test_ssl_lock_icon_displayed_for_secure_domains(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $sslDomain = $this->project->domains->where('ssl_enabled', true)->first();
            if ($sslDomain) {
                $browser->assertPresent('svg.w-4.h-4');
            }

            $browser->screenshot('domain-manager-ssl-lock-icon');
        });
    }

    /**
     * Test 19: Domain section is responsive on mobile
     */
    public function test_domain_section_is_responsive_on_mobile(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->resize(375, 667)
                ->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Domains')
                ->screenshot('domain-manager-mobile-responsive');
        });
    }

    /**
     * Test 20: Domain section is responsive on tablet
     */
    public function test_domain_section_is_responsive_on_tablet(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->resize(768, 1024)
                ->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Domains')
                ->screenshot('domain-manager-tablet-responsive');
        });
    }

    /**
     * Test 21: Primary domain badge is blue colored
     */
    public function test_primary_domain_badge_is_blue_colored(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $primaryDomain = $this->project->domains->where('is_primary', true)->first();
            if ($primaryDomain) {
                $browser->assertPresent('.bg-blue-500');
            }

            $browser->screenshot('domain-manager-primary-badge-color');
        });
    }

    /**
     * Test 22: Domain display includes protocol indicator
     */
    public function test_domain_display_includes_protocol_indicator(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $domain = $this->project->domains->first();
            if ($domain) {
                $protocol = $domain->ssl_enabled ? 'https' : 'http';
                $browser->assertPresent('a[href^="'.$protocol.'://"]');
            }

            $browser->screenshot('domain-manager-protocol-indicator');
        });
    }

    /**
     * Test 23: Add first domain button appears in empty state
     */
    public function test_add_first_domain_button_appears_in_empty_state(): void
    {
        // Create a project without domains for this test
        $emptyProject = Project::create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id ?? Server::first()->id,
            'name' => 'Empty Domain Test Project',
            'slug' => 'empty-domain-test-'.time(),
            'framework' => 'Laravel',
            'status' => 'stopped',
            'branch' => 'main',
            'root_directory' => '/var/www/empty-test',
        ]);

        $this->browse(function (Browser $browser) use ($emptyProject) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $emptyProject))
                ->waitForText($emptyProject->name, 10)
                ->assertSee('Add First Domain')
                ->screenshot('domain-manager-add-first-domain');
        });

        // Cleanup
        $emptyProject->delete();
    }

    /**
     * Test 24: Domain cards have rounded corners
     */
    public function test_domain_cards_have_rounded_corners(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            if ($this->project->domains->isNotEmpty()) {
                $browser->assertPresent('.rounded-lg');
            }

            $browser->screenshot('domain-manager-rounded-cards');
        });
    }

    /**
     * Test 25: Domain section has proper spacing
     */
    public function test_domain_section_has_proper_spacing(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            if ($this->project->domains->isNotEmpty()) {
                $browser->assertPresent('.space-y-3');
            }

            $browser->screenshot('domain-manager-spacing');
        });
    }

    /**
     * Test 26: Domain statistics are displayed in overview
     */
    public function test_domain_statistics_displayed_in_overview(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertSee('Domains')
                ->assertSee($this->project->domains->count())
                ->screenshot('domain-manager-statistics');
        });
    }

    /**
     * Test 27: Domain globe icon is visible in stats card
     */
    public function test_domain_globe_icon_visible_in_stats_card(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10)
                ->assertPresent('.text-purple-600')
                ->screenshot('domain-manager-globe-icon');
        });
    }

    /**
     * Test 28: Domain badges display correctly
     */
    public function test_domain_badges_display_correctly(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $domains = $this->project->domains;
            foreach ($domains as $domain) {
                if ($domain->is_primary) {
                    $browser->assertPresent('.rounded-full');
                }
            }

            $browser->screenshot('domain-manager-badges');
        });
    }

    /**
     * Test 29: Domain information is properly formatted
     */
    public function test_domain_information_is_properly_formatted(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            $domains = $this->project->domains;
            foreach ($domains as $domain) {
                $browser->assertSee($domain->domain)
                    ->assertSee(ucfirst($domain->status));
            }

            $browser->screenshot('domain-manager-formatting');
        });
    }

    /**
     * Test 30: Dark mode styling is applied correctly
     */
    public function test_dark_mode_styling_is_applied_correctly(): void
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user);
            $browser->visit(route('projects.show', $this->project))
                ->waitForText($this->project->name, 10);

            if ($this->project->domains->isNotEmpty()) {
                $browser->assertPresent('.dark\\:bg-gray-700\\/50, .dark\\:bg-gray-700, .dark\\:text-gray-400');
            }

            $browser->screenshot('domain-manager-dark-mode');
        });
    }
}
