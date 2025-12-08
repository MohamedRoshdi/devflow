<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        // When using Docker/Selenium, we don't need to start ChromeDriver locally
        // Only start ChromeDriver if running locally (not in Docker)
        if (! static::runningInSail() && ! static::runningInDocker()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
                '--no-sandbox',
                '--disable-dev-shm-usage',
            ]);
        })->all());

        $driverUrl = $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515';

        return RemoteWebDriver::create(
            $driverUrl,
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            ),
            60000, // Connection timeout (60 seconds)
            60000  // Request timeout (60 seconds)
        );
    }

    /**
     * Determine if tests are running in Docker.
     */
    protected static function runningInDocker(): bool
    {
        return env('DUSK_DRIVER_URL') !== null || file_exists('/.dockerenv');
    }
}
