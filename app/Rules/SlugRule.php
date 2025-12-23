<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for slug fields
 * Validates: required, string, max 255, lowercase alphanumeric with hyphens
 *
 * Usage:
 * #[Validate(new SlugRule())]
 * public string $slug = '';
 */
class SlugRule implements ValidationRule
{
    public function __construct(
        private readonly bool $required = true,
        private readonly int $maxLength = 255
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

        if (! is_string($value)) {
            $fail("The {$attribute} must be a string.");

            return;
        }

        if (strlen($value) > $this->maxLength) {
            $fail("The {$attribute} must not be greater than {$this->maxLength} characters.");

            return;
        }

        if (! preg_match('/^[a-z0-9-]+$/', $value)) {
            $fail("The {$attribute} must only contain lowercase letters, numbers, and hyphens.");

            return;
        }
    }

    /**
     * Get validation rules as string (for backward compatibility)
     */
    public static function rules(bool $required = true, int $maxLength = 255): string
    {
        $rules = [];

        if ($required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        $rules[] = 'string';
        $rules[] = "max:{$maxLength}";
        $rules[] = 'regex:/^[a-z0-9-]+$/';

        return implode('|', $rules);
    }
}
