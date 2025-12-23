<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Support\Str;

/**
 * Shared form fields and validation for Project Create/Edit components.
 *
 * Provides common properties, computed properties, and validation rules.
 * Components should define their own rules() method, using baseProjectRules()
 * and uniqueSlugRule() helpers.
 */
trait HasProjectFormFields
{
    public string $name = '';

    public string $slug = '';

    public string|int|null $server_id = '';

    public ?string $repository_url = '';

    public string $branch = 'main';

    public ?string $framework = '';

    public ?string $php_version = '8.3';

    public ?string $node_version = '20';

    public string $root_directory = '/';

    public ?string $build_command = '';

    public ?string $start_command = '';

    public bool $auto_deploy = false;

    public float|string|null $latitude = null;

    public float|string|null $longitude = null;

    public ?string $notes = null;

    /**
     * Auto-generate slug from name
     */
    public function updatedName(): void
    {
        $this->slug = Str::slug($this->name);
    }

    /**
     * Get available frameworks
     *
     * @return array<string, string>
     */
    public function getFrameworksProperty(): array
    {
        return [
            '' => '-- Select Framework --',
            'static' => 'Static Site (HTML/CSS/JS)',
            'laravel' => 'Laravel',
            'nodejs' => 'Node.js / Express',
            'react' => 'React',
            'vue' => 'Vue.js',
            'nextjs' => 'Next.js',
            'nuxt' => 'Nuxt.js',
        ];
    }

    /**
     * Get available PHP versions
     *
     * @return array<string, string>
     */
    public function getPhpVersionsProperty(): array
    {
        return [
            '8.4' => 'PHP 8.4 (Latest)',
            '8.3' => 'PHP 8.3',
            '8.2' => 'PHP 8.2',
            '8.1' => 'PHP 8.1',
            '8.0' => 'PHP 8.0',
            '7.4' => 'PHP 7.4 (Legacy)',
        ];
    }

    /**
     * Get available Node.js versions
     *
     * @return array<int|string, string>
     */
    public function getNodeVersionsProperty(): array
    {
        return [
            '22' => 'Node.js 22 (Latest LTS)',
            '20' => 'Node.js 20 (LTS)',
            '18' => 'Node.js 18 (LTS)',
            '16' => 'Node.js 16 (Legacy)',
        ];
    }

    /**
     * Base validation rules shared between Create and Edit
     *
     * @return array<string, mixed>
     */
    protected function baseProjectRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'server_id' => 'required|exists:servers,id',
            'repository_url' => ['required', 'regex:/^(https?:\/\/|git@)[\w\-\.]+[\/:][\w\-\.]+\/[\w\-\.]+\.git$/'],
            'branch' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\.\/]+$/'],
            'framework' => 'nullable|string|max:255',
            'php_version' => 'nullable|string|max:255',
            'node_version' => 'nullable|string|max:255',
            'root_directory' => 'required|string|max:255',
            'build_command' => 'nullable|string',
            'start_command' => 'nullable|string',
            'auto_deploy' => 'boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get the unique slug validation rule
     *
     * @param int|null $ignoreId Project ID to ignore for edit (null for create)
     */
    protected function uniqueSlugRule(?int $ignoreId = null): string
    {
        if ($ignoreId !== null) {
            return 'required|string|max:255|unique:projects,slug,'.$ignoreId.',id,deleted_at,NULL';
        }

        return 'required|string|max:255|unique:projects,slug,NULL,id,deleted_at,NULL';
    }
}
