<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for IP address fields
 * Validates: required, valid IP address (IPv4 or IPv6)
 *
 * Usage:
 * #[Validate(new IpAddressRule())]
 * public string $ip_address = '';
 */
class IpAddressRule implements ValidationRule
{
    public function __construct(
        private readonly bool $required = true,
        private readonly bool $ipv4Only = false,
        private readonly bool $ipv6Only = false
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

        $flags = 0;
        if ($this->ipv4Only) {
            $flags = FILTER_FLAG_IPV4;
        } elseif ($this->ipv6Only) {
            $flags = FILTER_FLAG_IPV6;
        }

        if (! filter_var($value, FILTER_VALIDATE_IP, $flags)) {
            $fail("The {$attribute} must be a valid IP address.");

            return;
        }
    }

    /**
     * Get validation rules as string (for backward compatibility)
     */
    public static function rules(bool $required = true, bool $ipv4Only = false, bool $ipv6Only = false): string
    {
        $rules = [];

        if ($required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($ipv4Only) {
            $rules[] = 'ipv4';
        } elseif ($ipv6Only) {
            $rules[] = 'ipv6';
        } else {
            $rules[] = 'ip';
        }

        return implode('|', $rules);
    }
}
