<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for URL fields
 * Validates: required, valid URL format
 *
 * Usage:
 * #[Validate(new UrlRule())]
 * public string $url = '';
 */
class UrlRule implements ValidationRule
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

        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            $fail("The {$attribute} must be a valid URL.");

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

        $rules[] = 'url';

        return implode('|', $rules);
    }
}
