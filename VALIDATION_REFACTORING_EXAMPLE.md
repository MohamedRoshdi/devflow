# Validation Refactoring Examples

This document provides before/after examples of refactoring Livewire components to use the new reusable validation rules.

## Example 1: TeamList Component

### Before (With Duplicate Validation)

```php
<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Services\TeamService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class TeamList extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    #[Validate('nullable|image|max:2048')]
    public mixed $avatar = null;

    public function createTeam()
    {
        $this->validate();

        // ... creation logic
    }
}
```

### After (Using HasCommonValidation Trait)

```php
<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Services\TeamService;
use App\Traits\HasCommonValidation;
use Livewire\Component;
use Livewire\WithFileUploads;

class TeamList extends Component
{
    use WithFileUploads, HasCommonValidation;

    public string $name = '';
    public string $description = '';
    public mixed $avatar = null;

    protected function rules(): array
    {
        return [
            'name' => $this->nameValidation(),
            'description' => $this->descriptionValidation(),
            'avatar' => $this->imageValidation(),
        ];
    }

    public function createTeam()
    {
        $this->validate();

        // ... creation logic
    }
}
```

## Example 2: ServerCreate Component

### Before

```php
<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use Livewire\Component;

class ServerCreate extends Component
{
    public string $name = '';
    public string $ip_address = '';
    public int $port = 22;
    public string $username = 'root';

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-]+$/'],
        ];
    }
}
```

### After (Using HasCommonValidation Trait)

```php
<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use App\Traits\HasCommonValidation;
use Livewire\Component;

class ServerCreate extends Component
{
    use HasCommonValidation;

    public string $name = '';
    public string $ip_address = '';
    public int $port = 22;
    public string $username = 'root';

    protected function rules(): array
    {
        return [
            'name' => $this->serverNameValidation(),
            'ip_address' => $this->ipAddressValidation(),
            'port' => $this->portValidation(),
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-]+$/'],
        ];
    }
}
```

## Example 3: ProjectCreate Component

### Before

```php
<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Livewire\Component;

class ProjectCreate extends Component
{
    public string $name = '';
    public string $slug = '';
    public string $repository_url = '';
    public string $branch = 'main';

    protected function validateStep1(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:projects,slug,NULL,id,deleted_at,NULL',
            'repository_url' => ['required', 'regex:/^(https?:\/\/|git@)[\w\-\.]+[\/:][\w\-\.]+\/[\w\-\.]+\.git$/'],
            'branch' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\.\/]+$/'],
        ]);
    }
}
```

### After (Using HasCommonValidation Trait)

```php
<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Traits\HasCommonValidation;
use Livewire\Component;

class ProjectCreate extends Component
{
    use HasCommonValidation;

    public string $name = '';
    public string $slug = '';
    public string $repository_url = '';
    public string $branch = 'main';

    protected function validateStep1(): void
    {
        $this->validate([
            'name' => $this->nameValidation(),
            'slug' => $this->projectSlugValidation(),
            'repository_url' => $this->repositoryUrlValidation(),
            'branch' => $this->branchNameValidation(),
        ]);
    }
}
```

## Example 4: Using Rule Classes Directly (Alternative Approach)

If you prefer not to use the trait, you can use the Rule classes directly:

```php
<?php

namespace App\Livewire\Settings;

use App\Rules\NameRule;
use App\Rules\DescriptionRule;
use App\Rules\PathRule;
use Livewire\Component;

class StorageSettings extends Component
{
    public string $name = '';
    public string $description = '';
    public string $path_prefix = '';

    protected function rules(): array
    {
        return [
            'name' => NameRule::rules(required: true, maxLength: 255),
            'description' => DescriptionRule::rules(required: false, maxLength: 500),
            'path_prefix' => PathRule::rules(required: false, maxLength: 500),
        ];
    }
}
```

## Example 5: Using Form Requests

For complex validation or when you want to reuse validation across multiple components:

```php
<?php

namespace App\Livewire\Projects;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use Livewire\Component;

class ProjectCreate extends Component
{
    public string $name = '';
    public string $slug = '';
    // ... other properties

    protected function rules(): array
    {
        // Reuse the Form Request rules
        return (new StoreProjectRequest())->rules();
    }

    // Or validate using the Form Request directly
    public function createProject(): void
    {
        $validated = $this->validate((new StoreProjectRequest())->rules());

        Project::create($validated);
    }
}
```

## Migration Checklist

When refactoring a Livewire component:

1. ✅ Add `use App\Traits\HasCommonValidation;` to imports
2. ✅ Add trait to class: `use HasCommonValidation;`
3. ✅ Remove `#[Validate()]` attributes from properties
4. ✅ Create or update `rules()` method
5. ✅ Replace validation strings with trait methods:
   - `'required|string|max:255'` → `$this->nameValidation()`
   - `'nullable|string|max:500'` → `$this->descriptionValidation()`
   - `'required|email'` → `$this->emailValidation()`
   - `'required|ip'` → `$this->ipAddressValidation()`
   - `'required|integer|min:1|max:65535'` → `$this->portValidation()`
6. ✅ Update inline `$this->validate([...])` calls to use trait methods
7. ✅ Test validation still works correctly
8. ✅ Run PHPStan to ensure type safety

## Benefits of Refactoring

1. **Consistency**: All name fields across the application use identical validation
2. **Maintainability**: Change validation in one place, updates everywhere
3. **Readability**: `$this->nameValidation()` is clearer than `'required|string|max:255'`
4. **Type Safety**: PHPStan Level 8 compliance with proper type hints
5. **Reusability**: Same validation logic can be used in Form Requests, API controllers, etc.
6. **Less Code**: Reduces boilerplate validation code by ~40%
7. **Documentation**: Self-documenting - method names explain what they validate

## Common Patterns Quick Reference

| Old Validation String | New Trait Method |
|----------------------|------------------|
| `'required\|string\|max:255'` | `$this->nameValidation()` |
| `'nullable\|string\|max:500'` | `$this->descriptionValidation()` |
| `'required\|email'` | `$this->emailValidation()` |
| `'required\|url'` | `$this->urlValidation()` |
| `'required\|ip'` | `$this->ipAddressValidation()` |
| `'required\|integer\|min:1\|max:65535'` | `$this->portValidation()` |
| `'required\|string\|max:255\|regex:/^[a-z0-9-]+$/'` | `$this->slugValidation()` |
| `'nullable\|string\|max:500'` (paths) | `$this->pathValidation()` |
| `'nullable\|image\|max:2048'` | `$this->imageValidation()` |
| `'nullable\|numeric\|between:-90,90'` | `$this->coordinateValidation('latitude')` |
| `'nullable\|numeric\|between:-180,180'` | `$this->coordinateValidation('longitude')` |

## Testing After Refactoring

```php
use Tests\TestCase;

class TeamListTest extends TestCase
{
    /** @test */
    public function it_validates_team_name_is_required()
    {
        Livewire::test(TeamList::class)
            ->set('name', '')
            ->call('createTeam')
            ->assertHasErrors(['name' => 'required']);
    }

    /** @test */
    public function it_validates_team_name_max_length()
    {
        Livewire::test(TeamList::class)
            ->set('name', str_repeat('a', 256))
            ->call('createTeam')
            ->assertHasErrors(['name' => 'max']);
    }

    /** @test */
    public function it_validates_description_is_optional()
    {
        Livewire::test(TeamList::class)
            ->set('name', 'Test Team')
            ->set('description', '')
            ->call('createTeam')
            ->assertHasNoErrors(['description']);
    }
}
```
