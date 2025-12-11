<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for path/URL path fields
 * Validates: string, max 500 characters
 *
 * Usage:
 * #[Validate(new PathRule())]
 * public string $path = '';
 */
class PathRule implements ValidationRule
{
    public function __construct(
        private readonly bool $required = false,
        private readonly int $maxLength = 500
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

        if (strlen($value) > $this->maxLength) {
            $fail("The {$attribute} must not be greater than {$this->maxLength} characters.");

            return;
        }
    }

    /**
     * Get validation rules as string (for backward compatibility)
     */
    public static function rules(bool $required = false, int $maxLength = 500): string
    {
        $rules = [];

        if ($required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        $rules[] = 'string';
        $rules[] = "max:{$maxLength}";

        return implode('|', $rules);
    }
}
