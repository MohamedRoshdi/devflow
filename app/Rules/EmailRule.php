<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for email fields
 * Validates: required, valid email format
 *
 * Usage:
 * #[Validate(new EmailRule())]
 * public string $email = '';
 */
class EmailRule implements ValidationRule
{
    public function __construct(
        private readonly bool $required = true
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->required && empty($value)) {
            $fail("The {$attribute} field is required.");

            return;
        }

        // Allow null/empty if not required
        if (! $this->required && empty($value)) {
            return;
        }

        if (! is_string($value)) {
            $fail("The {$attribute} must be a string.");

            return;
        }

        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail("The {$attribute} must be a valid email address.");

            return;
        }
    }

    /**
     * Get validation rules as string (for backward compatibility)
     */
    public static function rules(bool $required = true): string
    {
        $rules = [];

        if ($required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        $rules[] = 'email';

        return implode('|', $rules);
    }
}
