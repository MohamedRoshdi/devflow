<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for port number fields
 * Validates: required, integer, between 1-65535
 *
 * Usage:
 * #[Validate(new PortRule())]
 * public int $port = 22;
 */
class PortRule implements ValidationRule
{
    public function __construct(
        private readonly bool $required = true,
        private readonly int $min = 1,
        private readonly int $max = 65535
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
        if ($this->required && ($value === null || $value === '')) {
            $fail("The {$attribute} field is required.");

            return;
        }

        // Allow null/empty if not required
        if (! $this->required && ($value === null || $value === '')) {
            return;
        }

        if (! is_numeric($value)) {
            $fail("The {$attribute} must be a number.");

            return;
        }

        $intValue = (int) $value;

        if ($intValue < $this->min || $intValue > $this->max) {
            $fail("The {$attribute} must be between {$this->min} and {$this->max}.");

            return;
        }
    }

    /**
     * Get validation rules as string (for backward compatibility)
     */
    public static function rules(bool $required = true, int $min = 1, int $max = 65535): string
    {
        $rules = [];

        if ($required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        $rules[] = 'integer';
        $rules[] = "min:{$min}";
        $rules[] = "max:{$max}";

        return implode('|', $rules);
    }
}
