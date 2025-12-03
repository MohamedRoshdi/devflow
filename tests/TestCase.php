<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Default setup for all tests
        $this->withoutVite();
    }

    /**
     * Create and authenticate a user.
     */
    protected function actingAsUser($user = null): self
    {
        $user = $user ?? \App\Models\User::factory()->create();

        return $this->actingAs($user);
    }

    /**
     * Assert that a JSON response has validation errors for specific fields.
     */
    protected function assertHasValidationErrors(array $fields): void
    {
        $this->assertJsonValidationErrors($fields);
    }

    /**
     * Mock an SSH connection for testing.
     */
    protected function mockSshConnection(): void
    {
        // Mock Process facade to prevent actual SSH calls
        \Illuminate\Support\Facades\Process::fake([
            '*ssh*' => \Illuminate\Support\Facades\Process::result(
                output: 'Mocked SSH output',
                errorOutput: ''
            ),
        ]);
    }

    /**
     * Mock successful command execution.
     */
    protected function mockSuccessfulCommand(string $output = 'Success'): void
    {
        \Illuminate\Support\Facades\Process::fake([
            '*' => \Illuminate\Support\Facades\Process::result(
                output: $output,
                errorOutput: ''
            ),
        ]);
    }

    /**
     * Mock failed command execution.
     */
    protected function mockFailedCommand(string $error = 'Command failed'): void
    {
        \Illuminate\Support\Facades\Process::fake([
            '*' => \Illuminate\Support\Facades\Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
        ]);
    }
}
