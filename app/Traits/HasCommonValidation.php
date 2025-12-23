<?php

declare(strict_types=1);

namespace App\Traits;

use App\Rules\DescriptionRule;
use App\Rules\EmailRule;
use App\Rules\IpAddressRule;
use App\Rules\NameRule;
use App\Rules\PathRule;
use App\Rules\PortRule;
use App\Rules\SlugRule;
use App\Rules\UrlRule;

/**
 * Trait for common validation rules used across Livewire components
 *
 * This trait provides reusable validation methods to avoid duplication
 * and ensure consistency across the application.
 *
 * Usage:
 * ```php
 * class MyComponent extends Component
 * {
 *     use HasCommonValidation;
 *
 *     protected function rules(): array
 *     {
 *         return [
 *             'name' => $this->nameValidation(),
 *             'description' => $this->descriptionValidation(),
 *             'email' => $this->emailValidation(),
 *         ];
 *     }
 * }
 * ```
 */
trait HasCommonValidation
{
    /**
     * Standard validation for name fields
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $maxLength  Maximum length (default: 255)
     * @return string Validation rules string
     */
    protected function nameValidation(bool $required = true, int $maxLength = 255): string
    {
        return NameRule::rules(required: $required, maxLength: $maxLength);
    }

    /**
     * Standard validation for description fields
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $maxLength  Maximum length (default: 500)
     * @return string Validation rules string
     */
    protected function descriptionValidation(bool $required = false, int $maxLength = 500): string
    {
        return DescriptionRule::rules(required: $required, maxLength: $maxLength);
    }

    /**
     * Standard validation for slug fields
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $maxLength  Maximum length (default: 255)
     * @return string Validation rules string
     */
    protected function slugValidation(bool $required = true, int $maxLength = 255): string
    {
        return SlugRule::rules(required: $required, maxLength: $maxLength);
    }

    /**
     * Standard validation for URL fields
     *
     * @param  bool  $required  Whether the field is required
     * @return string Validation rules string
     */
    protected function urlValidation(bool $required = true): string
    {
        return UrlRule::rules(required: $required);
    }

    /**
     * Standard validation for email fields
     *
     * @param  bool  $required  Whether the field is required
     * @return string Validation rules string
     */
    protected function emailValidation(bool $required = true): string
    {
        return EmailRule::rules(required: $required);
    }

    /**
     * Standard validation for path fields
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $maxLength  Maximum length (default: 500)
     * @return string Validation rules string
     */
    protected function pathValidation(bool $required = false, int $maxLength = 500): string
    {
        return PathRule::rules(required: $required, maxLength: $maxLength);
    }

    /**
     * Standard validation for IP address fields
     *
     * @param  bool  $required  Whether the field is required
     * @param  bool  $ipv4Only  Restrict to IPv4 only
     * @param  bool  $ipv6Only  Restrict to IPv6 only
     * @return string Validation rules string
     */
    protected function ipAddressValidation(bool $required = true, bool $ipv4Only = false, bool $ipv6Only = false): string
    {
        return IpAddressRule::rules(required: $required, ipv4Only: $ipv4Only, ipv6Only: $ipv6Only);
    }

    /**
     * Standard validation for port number fields
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $min  Minimum port number (default: 1)
     * @param  int  $max  Maximum port number (default: 65535)
     * @return string Validation rules string
     */
    protected function portValidation(bool $required = true, int $min = 1, int $max = 65535): string
    {
        return PortRule::rules(required: $required, min: $min, max: $max);
    }

    /**
     * Validation for project slug with uniqueness check
     *
     * @param  int|null  $excludeId  Project ID to exclude from uniqueness check
     * @return string Validation rules string
     */
    protected function projectSlugValidation(?int $excludeId = null): string
    {
        $rules = SlugRule::rules(required: true, maxLength: 255);

        if ($excludeId !== null) {
            $rules .= "|unique:projects,slug,{$excludeId},id,deleted_at,NULL";
        } else {
            $rules .= '|unique:projects,slug,NULL,id,deleted_at,NULL';
        }

        return $rules;
    }

    /**
     * Validation for team name
     *
     * @return string Validation rules string
     */
    protected function teamNameValidation(): string
    {
        return NameRule::rules(required: true, maxLength: 255);
    }

    /**
     * Validation for team description
     *
     * @return string Validation rules string
     */
    protected function teamDescriptionValidation(): string
    {
        return DescriptionRule::rules(required: false, maxLength: 500);
    }

    /**
     * Validation for server name
     *
     * @return string Validation rules string
     */
    protected function serverNameValidation(): string
    {
        return NameRule::rules(required: true, maxLength: 255);
    }

    /**
     * Validation for Git repository URL
     *
     * @return array<int, string> Validation rules array
     */
    protected function repositoryUrlValidation(): array
    {
        return ['required', 'regex:/^(https?:\/\/|git@)[\w\-\.]+[\/:][\w\-\.]+\/[\w\-\.]+\.git$/'];
    }

    /**
     * Validation for Git branch name
     *
     * @return array<int, string> Validation rules array
     */
    protected function branchNameValidation(): array
    {
        return ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\.\/]+$/'];
    }

    /**
     * Validation for image upload (avatar, logo, etc.)
     *
     * @param  int  $maxSizeKb  Maximum file size in KB (default: 2048 = 2MB)
     * @return string Validation rules string
     */
    protected function imageValidation(int $maxSizeKb = 2048): string
    {
        return "nullable|image|max:{$maxSizeKb}";
    }

    /**
     * Validation for coordinates (latitude/longitude)
     *
     * @param  string  $type  'latitude' or 'longitude'
     * @return string Validation rules string
     */
    protected function coordinateValidation(string $type = 'latitude'): string
    {
        if ($type === 'latitude') {
            return 'nullable|numeric|between:-90,90';
        }

        return 'nullable|numeric|between:-180,180';
    }
}
