<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use App\Models\Project;

class MobileResponsivenessTest extends DuskTestCase
{
    protected User $user;
    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test responsive design across different devices
     * @test
     */
    public function testResponsiveDesignAcrossDevices()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user);

            $devices = [
                'mobile' => ['width' => 375, 'height' => 667],   // iPhone SE
                'tablet' => ['width' => 768, 'height' => 1024],  // iPad
                'desktop' => ['width' => 1920, 'height' => 1080], // Full HD
                'mobile_landscape' => ['width' => 667, 'height' => 375],
                'tablet_landscape' => ['width' => 1024, 'height' => 768],
            ];

            foreach ($devices as $device => $dimensions) {
                $this->testDeviceResponsiveness($browser, $device, $dimensions);
            }
        });
    }

    /**
     * Test specific device responsiveness
     */
    protected function testDeviceResponsiveness(Browser $browser, string $device, array $dimensions)
    {
        $browser->resize($dimensions['width'], $dimensions['height']);

        // Test navigation menu
        $this->testNavigationMenu($browser, $device, $dimensions);

        // Test dashboard layout
        $this->testDashboardLayout($browser, $device, $dimensions);

        // Test project cards
        $this->testProjectCards($browser, $device, $dimensions);

        // Test forms
        $this->testFormLayouts($browser, $device, $dimensions);

        // Test tables
        $this->testTableResponsiveness($browser, $device, $dimensions);

        // Test modals
        $this->testModalResponsiveness($browser, $device, $dimensions);
    }

    /**
     * Test navigation menu responsiveness
     */
    protected function testNavigationMenu(Browser $browser, string $device, array $dimensions)
    {
        $browser->visit('/dashboard');

        if ($dimensions['width'] < 768) {
            // Mobile: Check hamburger menu
            $browser->assertPresent('[data-test="mobile-menu-button"]')
                    ->click('[data-test="mobile-menu-button"]')
                    ->assertVisible('[data-test="mobile-menu"]')
                    ->screenshot("navigation-{$device}");

            $this->testResults['navigation'][$device] = 'Mobile menu working';
        } else {
            // Desktop/Tablet: Check full menu
            $browser->assertVisible('nav')
                    ->assertPresent('[data-test="desktop-menu"]')
                    ->screenshot("navigation-{$device}");

            $this->testResults['navigation'][$device] = 'Desktop menu visible';
        }
    }

    /**
     * Test dashboard layout
     */
    protected function testDashboardLayout(Browser $browser, string $device, array $dimensions)
    {
        $browser->visit('/dashboard');

        if ($dimensions['width'] < 768) {
            // Mobile: Single column layout
            $browser->assertPresent('.grid-cols-1')
                    ->screenshot("dashboard-{$device}");

            // Check if cards stack vertically
            $cards = $browser->elements('[data-test="stat-card"]');
            if (count($cards) > 1) {
                $firstCard = $browser->element('[data-test="stat-card"]:first-child');
                $secondCard = $browser->element('[data-test="stat-card"]:nth-child(2)');

                $this->assertTrue(
                    $firstCard->getLocation()->getY() < $secondCard->getLocation()->getY(),
                    "Cards should stack vertically on mobile"
                );
            }
        } elseif ($dimensions['width'] < 1024) {
            // Tablet: Two column layout
            $browser->assertPresent('.md\\:grid-cols-2')
                    ->screenshot("dashboard-{$device}");
        } else {
            // Desktop: Multi-column layout
            $browser->assertPresent('.lg\\:grid-cols-4')
                    ->screenshot("dashboard-{$device}");
        }

        $this->testResults['dashboard'][$device] = 'Layout responsive';
    }

    /**
     * Test project cards responsiveness
     */
    protected function testProjectCards(Browser $browser, string $device, array $dimensions)
    {
        Project::factory()->count(3)->create(['user_id' => $this->user->id]);

        $browser->visit('/projects');

        if ($dimensions['width'] < 640) {
            // Mobile: Full width cards
            $browser->assertPresent('.w-full')
                    ->screenshot("projects-{$device}");

            // Check card content is readable
            $fontSize = $browser->script("return window.getComputedStyle(document.querySelector('.project-title')).fontSize")[0];
            $this->assertGreaterThanOrEqual(14, intval($fontSize), "Font size should be readable on mobile");
        } else {
            // Tablet/Desktop: Grid layout
            $browser->assertPresent('.grid')
                    ->screenshot("projects-{$device}");
        }

        $this->testResults['project_cards'][$device] = 'Cards responsive';
    }

    /**
     * Test form layouts
     */
    protected function testFormLayouts(Browser $browser, string $device, array $dimensions)
    {
        $browser->visit('/projects/create');

        if ($dimensions['width'] < 768) {
            // Mobile: Single column forms
            $inputs = $browser->elements('input[type="text"], input[type="email"], select');

            foreach ($inputs as $input) {
                $width = $browser->script("return document.querySelector('input').offsetWidth")[0];
                $containerWidth = $browser->script("return document.querySelector('form').offsetWidth")[0];

                // Input should be at least 90% of container width on mobile
                $this->assertGreaterThan($containerWidth * 0.9, $width, "Inputs should be full width on mobile");
            }

            $browser->screenshot("form-{$device}");
        } else {
            // Desktop: Multi-column forms possible
            $browser->assertPresent('form')
                    ->screenshot("form-{$device}");
        }

        // Test button visibility
        $browser->assertVisible('button[type="submit"]');

        $this->testResults['forms'][$device] = 'Forms responsive';
    }

    /**
     * Test table responsiveness
     */
    protected function testTableResponsiveness(Browser $browser, string $device, array $dimensions)
    {
        $browser->visit('/deployments');

        if ($dimensions['width'] < 768) {
            // Mobile: Check for horizontal scroll or responsive table
            $tableWidth = $browser->script("return document.querySelector('table')?.offsetWidth")[0];
            $containerWidth = $browser->script("return document.querySelector('.table-container')?.offsetWidth")[0];

            if ($tableWidth > $containerWidth) {
                // Table should be scrollable
                $browser->assertPresent('.overflow-x-auto')
                        ->screenshot("table-scroll-{$device}");
            } else {
                // Or use responsive cards instead of table
                $browser->assertPresent('[data-test="mobile-list-item"]')
                        ->screenshot("table-cards-{$device}");
            }
        } else {
            // Desktop: Full table
            $browser->assertPresent('table')
                    ->assertVisible('thead')
                    ->screenshot("table-{$device}");
        }

        $this->testResults['tables'][$device] = 'Tables responsive';
    }

    /**
     * Test modal responsiveness
     */
    protected function testModalResponsiveness(Browser $browser, string $device, array $dimensions)
    {
        $browser->visit('/projects')
                ->click('[data-test="create-project-button"]');

        if ($dimensions['width'] < 768) {
            // Mobile: Full screen modals
            $modalWidth = $browser->script("return document.querySelector('.modal')?.offsetWidth")[0];
            $viewportWidth = $dimensions['width'];

            // Modal should take most of viewport width on mobile
            $this->assertGreaterThan($viewportWidth * 0.9, $modalWidth, "Modal should be nearly full width on mobile");

            $browser->screenshot("modal-{$device}");
        } else {
            // Desktop: Centered modals
            $browser->assertVisible('.modal')
                    ->screenshot("modal-{$device}");
        }

        // Close modal
        $browser->press('Cancel')->pause(500);

        $this->testResults['modals'][$device] = 'Modals responsive';
    }

    /**
     * Test touch interactions on mobile
     * @test
     */
    public function testTouchInteractions()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->resize(375, 667); // Mobile size

            // Test swipe gestures if implemented
            $browser->visit('/projects')
                    ->swipeLeft('[data-test="swipeable-card"]')
                    ->assertVisible('[data-test="card-actions"]')
                    ->screenshot('swipe-actions');

            // Test tap targets size
            $buttons = $browser->elements('button, a');
            foreach ($buttons as $button) {
                $height = $browser->script("return arguments[0].offsetHeight", [$button])[0];
                $width = $browser->script("return arguments[0].offsetWidth", [$button])[0];

                // Touch targets should be at least 44x44 pixels (iOS guideline)
                $this->assertGreaterThanOrEqual(44, $height, "Button height should be at least 44px for touch");
                $this->assertGreaterThanOrEqual(44, $width, "Button width should be at least 44px for touch");
            }
        });
    }

    /**
     * Test viewport meta tag
     * @test
     */
    public function testViewportMetaTag()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');

            $viewport = $browser->script("
                return document.querySelector('meta[name=viewport]')?.content
            ")[0];

            $this->assertNotNull($viewport, "Viewport meta tag should be present");
            $this->assertStringContainsString('width=device-width', $viewport);
            $this->assertStringContainsString('initial-scale=1', $viewport);
        });
    }

    /**
     * Test font sizes on mobile
     * @test
     */
    public function testReadabilityOnMobile()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->resize(375, 667)
                    ->visit('/dashboard');

            // Test minimum font sizes
            $elements = [
                'h1' => 24,
                'h2' => 20,
                'h3' => 18,
                'p' => 14,
                'button' => 14,
            ];

            foreach ($elements as $selector => $minSize) {
                $fontSize = $browser->script("
                    const el = document.querySelector('{$selector}');
                    return el ? parseInt(window.getComputedStyle(el).fontSize) : 0;
                ")[0];

                if ($fontSize > 0) {
                    $this->assertGreaterThanOrEqual(
                        $minSize,
                        $fontSize,
                        "{$selector} font size should be at least {$minSize}px on mobile"
                    );
                }
            }
        });
    }

    /**
     * Test image responsiveness
     * @test
     */
    public function testImageResponsiveness()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/');

            $devices = [
                ['width' => 375, 'height' => 667],
                ['width' => 768, 'height' => 1024],
                ['width' => 1920, 'height' => 1080],
            ];

            foreach ($devices as $device) {
                $browser->resize($device['width'], $device['height']);

                $images = $browser->script("
                    return Array.from(document.querySelectorAll('img')).map(img => ({
                        src: img.src,
                        width: img.offsetWidth,
                        naturalWidth: img.naturalWidth,
                        hasAlt: !!img.alt,
                        isResponsive: img.classList.contains('responsive') ||
                                     img.style.maxWidth === '100%' ||
                                     img.parentElement.classList.contains('responsive')
                    }));
                ")[0];

                foreach ($images as $img) {
                    // Images shouldn't exceed viewport width
                    $this->assertLessThanOrEqual(
                        $device['width'],
                        $img['width'],
                        "Image width should not exceed viewport"
                    );

                    // Images should have alt text for accessibility
                    $this->assertTrue($img['hasAlt'], "Images should have alt text");

                    // Check for responsive images
                    $this->assertTrue(
                        $img['isResponsive'],
                        "Images should be responsive"
                    );
                }
            }
        });
    }

    /**
     * Generate responsiveness report
     */
    protected function tearDown(): void
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'test_results' => $this->testResults,
            'summary' => [
                'total_tests' => count($this->testResults, COUNT_RECURSIVE),
                'devices_tested' => ['mobile', 'tablet', 'desktop'],
                'passed' => array_filter($this->testResults, fn($result) => !str_contains(json_encode($result), 'failed')),
            ],
            'recommendations' => $this->generateRecommendations(),
        ];

        $reportPath = storage_path('app/responsiveness-reports/report-' . now()->format('Y-m-d-H-i-s') . '.json');
        @mkdir(dirname($reportPath), 0755, true);
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));

        parent::tearDown();
    }

    /**
     * Generate recommendations based on test results
     */
    protected function generateRecommendations(): array
    {
        return [
            'Ensure all touch targets are at least 44x44 pixels',
            'Use relative units (rem, em, %) instead of fixed pixels',
            'Implement responsive images with srcset',
            'Test on actual devices, not just browser resize',
            'Consider using CSS Grid and Flexbox for layouts',
            'Implement mobile-first design approach',
            'Optimize images for different screen sizes',
            'Ensure text is readable without zooming',
        ];
    }
}