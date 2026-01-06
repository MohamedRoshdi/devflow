<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\Livewire\ServerEnvVariableRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServerEnvVariableRequestTest extends TestCase
{
    // ==================== SENSITIVE_KEYS TESTS ====================

    public function test_sensitive_keys_contains_app_key(): void
    {
        $this->assertContains('APP_KEY', ServerEnvVariableRequest::SENSITIVE_KEYS);
    }

    public function test_sensitive_keys_contains_db_password(): void
    {
        $this->assertContains('DB_PASSWORD', ServerEnvVariableRequest::SENSITIVE_KEYS);
    }

    public function test_sensitive_keys_contains_db_username(): void
    {
        $this->assertContains('DB_USERNAME', ServerEnvVariableRequest::SENSITIVE_KEYS);
    }

    public function test_sensitive_keys_contains_redis_password(): void
    {
        $this->assertContains('REDIS_PASSWORD', ServerEnvVariableRequest::SENSITIVE_KEYS);
    }

    public function test_sensitive_keys_contains_mail_password(): void
    {
        $this->assertContains('MAIL_PASSWORD', ServerEnvVariableRequest::SENSITIVE_KEYS);
    }

    public function test_sensitive_keys_contains_aws_secret(): void
    {
        $this->assertContains('AWS_SECRET_ACCESS_KEY', ServerEnvVariableRequest::SENSITIVE_KEYS);
    }

    public function test_sensitive_keys_contains_pusher_secret(): void
    {
        $this->assertContains('PUSHER_APP_SECRET', ServerEnvVariableRequest::SENSITIVE_KEYS);
    }

    public function test_sensitive_keys_contains_stripe_secret(): void
    {
        $this->assertContains('STRIPE_SECRET', ServerEnvVariableRequest::SENSITIVE_KEYS);
    }

    public function test_sensitive_keys_contains_paypal_secret(): void
    {
        $this->assertContains('PAYPAL_SECRET', ServerEnvVariableRequest::SENSITIVE_KEYS);
    }

    // ==================== PROTECTED_KEYS TESTS ====================

    public function test_protected_keys_contains_app_key(): void
    {
        $this->assertContains('APP_KEY', ServerEnvVariableRequest::PROTECTED_KEYS);
    }

    // ==================== isSensitiveKey TESTS ====================

    #[DataProvider('sensitiveKeyProvider')]
    public function test_is_sensitive_key_returns_true_for_sensitive_keys(string $key): void
    {
        $this->assertTrue(ServerEnvVariableRequest::isSensitiveKey($key));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function sensitiveKeyProvider(): array
    {
        return [
            'APP_KEY' => ['APP_KEY'],
            'DB_PASSWORD' => ['DB_PASSWORD'],
            'DB_USERNAME' => ['DB_USERNAME'],
            'REDIS_PASSWORD' => ['REDIS_PASSWORD'],
            'lowercase app_key' => ['app_key'],
            'mixed case App_Key' => ['App_Key'],
        ];
    }

    #[DataProvider('nonSensitiveKeyProvider')]
    public function test_is_sensitive_key_returns_false_for_non_sensitive_keys(string $key): void
    {
        $this->assertFalse(ServerEnvVariableRequest::isSensitiveKey($key));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function nonSensitiveKeyProvider(): array
    {
        return [
            'APP_NAME' => ['APP_NAME'],
            'APP_ENV' => ['APP_ENV'],
            'APP_DEBUG' => ['APP_DEBUG'],
            'DB_HOST' => ['DB_HOST'],
            'CACHE_DRIVER' => ['CACHE_DRIVER'],
            'QUEUE_CONNECTION' => ['QUEUE_CONNECTION'],
        ];
    }

    // ==================== isProtectedKey TESTS ====================

    public function test_is_protected_key_returns_true_for_app_key(): void
    {
        $this->assertTrue(ServerEnvVariableRequest::isProtectedKey('APP_KEY'));
    }

    public function test_is_protected_key_returns_true_for_lowercase_app_key(): void
    {
        $this->assertTrue(ServerEnvVariableRequest::isProtectedKey('app_key'));
    }

    public function test_is_protected_key_returns_false_for_db_password(): void
    {
        // DB_PASSWORD is sensitive but not protected
        $this->assertFalse(ServerEnvVariableRequest::isProtectedKey('DB_PASSWORD'));
    }

    public function test_is_protected_key_returns_false_for_app_name(): void
    {
        $this->assertFalse(ServerEnvVariableRequest::isProtectedKey('APP_NAME'));
    }

    // ==================== rules TESTS ====================

    public function test_rules_returns_server_env_key_rules(): void
    {
        $request = new ServerEnvVariableRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('serverEnvKey', $rules);
        $this->assertArrayHasKey('serverEnvValue', $rules);
    }

    public function test_server_env_key_is_required(): void
    {
        $request = new ServerEnvVariableRequest();
        $rules = $request->rules();

        $this->assertContains('required', $rules['serverEnvKey']);
    }

    public function test_server_env_key_max_length_is_255(): void
    {
        $request = new ServerEnvVariableRequest();
        $rules = $request->rules();

        $this->assertContains('max:255', $rules['serverEnvKey']);
    }

    public function test_server_env_value_is_nullable(): void
    {
        $request = new ServerEnvVariableRequest();
        $rules = $request->rules();

        $this->assertContains('nullable', $rules['serverEnvValue']);
    }

    public function test_server_env_value_max_length_is_2000(): void
    {
        $request = new ServerEnvVariableRequest();
        $rules = $request->rules();

        $this->assertContains('max:2000', $rules['serverEnvValue']);
    }

    // ==================== attributes TESTS ====================

    public function test_attributes_returns_friendly_names(): void
    {
        $request = new ServerEnvVariableRequest();
        $attributes = $request->attributes();

        $this->assertEquals('variable name', $attributes['serverEnvKey']);
        $this->assertEquals('variable value', $attributes['serverEnvValue']);
    }

    // ==================== messages TESTS ====================

    public function test_messages_contains_custom_regex_message(): void
    {
        $request = new ServerEnvVariableRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('serverEnvKey.regex', $messages);
        $this->assertStringContainsString('start with a letter', $messages['serverEnvKey.regex']);
    }

    public function test_messages_contains_custom_required_message(): void
    {
        $request = new ServerEnvVariableRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('serverEnvKey.required', $messages);
    }

    public function test_messages_contains_custom_max_message(): void
    {
        $request = new ServerEnvVariableRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('serverEnvValue.max', $messages);
    }
}
