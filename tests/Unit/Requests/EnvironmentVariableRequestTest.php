<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\Livewire\EnvironmentVariableRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EnvironmentVariableRequestTest extends TestCase
{
    // ==================== keyRules TESTS ====================

    public function test_key_rules_returns_required(): void
    {
        $rules = EnvironmentVariableRequest::keyRules();
        $this->assertContains('required', $rules);
    }

    public function test_key_rules_returns_string(): void
    {
        $rules = EnvironmentVariableRequest::keyRules();
        $this->assertContains('string', $rules);
    }

    public function test_key_rules_returns_max_255(): void
    {
        $rules = EnvironmentVariableRequest::keyRules();
        $this->assertContains('max:255', $rules);
    }

    public function test_key_rules_returns_regex_pattern(): void
    {
        $rules = EnvironmentVariableRequest::keyRules();
        $this->assertContains('regex:/^[A-Z][A-Z0-9_]*$/i', $rules);
    }

    // ==================== valueRules TESTS ====================

    public function test_value_rules_returns_nullable(): void
    {
        $rules = EnvironmentVariableRequest::valueRules();
        $this->assertContains('nullable', $rules);
    }

    public function test_value_rules_returns_string(): void
    {
        $rules = EnvironmentVariableRequest::valueRules();
        $this->assertContains('string', $rules);
    }

    public function test_value_rules_returns_max_2000(): void
    {
        $rules = EnvironmentVariableRequest::valueRules();
        $this->assertContains('max:2000', $rules);
    }

    // ==================== keyValueRules TESTS ====================

    public function test_key_value_rules_returns_array_with_key_and_value(): void
    {
        $rules = EnvironmentVariableRequest::keyValueRules();

        $this->assertArrayHasKey('key', $rules);
        $this->assertArrayHasKey('value', $rules);
    }

    // ==================== environmentRules TESTS ====================

    public function test_environment_rules_requires_environment(): void
    {
        $rules = EnvironmentVariableRequest::environmentRules();

        $this->assertArrayHasKey('environment', $rules);
        $this->assertStringContainsString('required', $rules['environment']);
    }

    public function test_environment_rules_only_allows_valid_environments(): void
    {
        $rules = EnvironmentVariableRequest::environmentRules();

        $this->assertStringContainsString('local', $rules['environment']);
        $this->assertStringContainsString('development', $rules['environment']);
        $this->assertStringContainsString('staging', $rules['environment']);
        $this->assertStringContainsString('production', $rules['environment']);
    }

    // ==================== REGEX VALIDATION TESTS ====================

    #[DataProvider('validKeyProvider')]
    public function test_key_regex_accepts_valid_keys(string $key): void
    {
        $pattern = '/^[A-Z][A-Z0-9_]*$/i';
        $this->assertEquals(1, preg_match($pattern, $key));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function validKeyProvider(): array
    {
        return [
            'uppercase' => ['APP_NAME'],
            'lowercase' => ['app_name'],
            'mixed case' => ['App_Name'],
            'with numbers' => ['APP_NAME_123'],
            'single letter' => ['A'],
            'no underscores' => ['APPNAME'],
        ];
    }

    #[DataProvider('invalidKeyProvider')]
    public function test_key_regex_rejects_invalid_keys(string $key): void
    {
        $pattern = '/^[A-Z][A-Z0-9_]*$/i';
        $this->assertEquals(0, preg_match($pattern, $key));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function invalidKeyProvider(): array
    {
        return [
            'starts with number' => ['1APP_NAME'],
            'contains hyphen' => ['APP-NAME'],
            'contains space' => ['APP NAME'],
            'contains dot' => ['APP.NAME'],
            'starts with underscore' => ['_APP_NAME'],
            'empty string' => [''],
        ];
    }
}
