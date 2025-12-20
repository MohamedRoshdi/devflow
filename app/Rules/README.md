# Validation Rules Guide

This directory contains reusable validation rule classes that help eliminate duplicate validation logic across the application.

## Available Rule Classes

### NameRule

Validates name fields with standard requirements: required, string, max 255 characters.

**Usage in Livewire #[Validate] attribute:**
```php
use App\Rules\NameRule;

#[Validate]
public string $name = '';

protected function rules(): array
{
    return [
        'name' => NameRule::rules(required: true, maxLength: 255),
    ];
}
```

**Usage in Form Requests:**
```php
use App\Rules\NameRule;

public function rules(): array
{
    return [
        'name' => NameRule::rules(required: true, maxLength: 255),
    ];
}
```

**Usage as object (PHPStan compatible):**
```php
use App\Rules\NameRule;

#[Validate(new NameRule(required: true, maxLength: 255))]
public string $name = '';
```

### DescriptionRule

Validates description fields: nullable, string, max 500 characters (configurable).

**Usage:**
```php
use App\Rules\DescriptionRule;

// String format (recommended for Livewire)
protected function rules(): array
{
    return [
        'description' => DescriptionRule::rules(required: false, maxLength: 500),
    ];
}

// Object format
#[Validate(new DescriptionRule(required: false, maxLength: 500))]
public string $description = '';
```

### SlugRule

Validates slug fields: required, string, max 255, lowercase alphanumeric with hyphens.

**Usage:**
```php
use App\Rules\SlugRule;

protected function rules(): array
{
    return [
        'slug' => SlugRule::rules(required: true, maxLength: 255) . '|unique:projects,slug',
    ];
}
```

### UrlRule

Validates URL fields: required, valid URL format.

**Usage:**
```php
use App\Rules\UrlRule;

protected function rules(): array
{
    return [
        'repository_url' => UrlRule::rules(required: true),
    ];
}
```

### PathRule

Validates path/file path fields: nullable, string, max 500 characters.

**Usage:**
```php
use App\Rules\PathRule;

protected function rules(): array
{
    return [
        'health_check_path' => PathRule::rules(required: false, maxLength: 500),
    ];
}
```

## Form Request Classes

Pre-built Form Request classes are available for common operations:

### Project Operations

**StoreProjectRequest** - For creating new projects
**UpdateProjectRequest** - For updating existing projects

```php
use App\Http\Requests\StoreProjectRequest;

// In Livewire component
protected function rules(): array
{
    return (new StoreProjectRequest())->rules();
}

// Or use specific rules
use App\Rules\NameRule;
use App\Rules\SlugRule;

protected function rules(): array
{
    return [
        'name' => NameRule::rules(),
        'slug' => SlugRule::rules() . '|unique:projects,slug',
        'server_id' => 'required|exists:servers,id',
        // ... other fields
    ];
}
```

### Server Operations

**StoreServerRequest** - For creating new servers
**UpdateServerRequest** - For updating existing servers

### Team Operations

**StoreTeamRequest** - For creating new teams
**UpdateTeamRequest** - For updating existing teams

### Template Operations

**StoreProjectTemplateRequest** - For creating project templates

## Migration Guide

### Before (Duplicate Validation)

```php
// Multiple components with the same validation
#[Validate('required|string|max:255')]
public string $name = '';

#[Validate('nullable|string|max:500')]
public string $description = '';
```

### After (Using Rule Classes)

```php
use App\Rules\NameRule;
use App\Rules\DescriptionRule;

// Remove #[Validate] attributes, use rules() method instead
public string $name = '';
public string $description = '';

protected function rules(): array
{
    return [
        'name' => NameRule::rules(required: true, maxLength: 255),
        'description' => DescriptionRule::rules(required: false, maxLength: 500),
    ];
}
```

## Common Validation Patterns

### Name Field (used 11+ times in codebase)
**Old:** `'required|string|max:255'`
**New:** `NameRule::rules(required: true, maxLength: 255)`

### Description Field (used 8+ times)
**Old:** `'nullable|string|max:500'`
**New:** `DescriptionRule::rules(required: false, maxLength: 500)`

### URL Field (used 6+ times)
**Old:** `'required|url'`
**New:** `UrlRule::rules(required: true)`

### Slug Field (used 5+ times)
**Old:** `'required|string|max:255|regex:/^[a-z0-9-]+$/'`
**New:** `SlugRule::rules(required: true, maxLength: 255)`

### Path Field (used 4+ times)
**Old:** `'nullable|string|max:500'`
**New:** `PathRule::rules(required: false, maxLength: 500)`

## Benefits

1. **DRY Principle**: Define validation logic once, use everywhere
2. **Consistency**: Ensures all name fields have the same validation rules
3. **Easy Updates**: Change validation in one place, applies everywhere
4. **Type Safety**: PHPStan Level 8 compliant with proper type hints
5. **Reusability**: Both string format and object format supported
6. **Documentation**: Self-documenting code with clear intent

## PHPStan Compliance

All Rule classes are fully compliant with PHPStan Level 8:

- Proper type hints on all methods
- Correct return types
- Proper PHPDoc annotations
- No mixed types except where necessary (e.g., validation value parameter)

## Testing

```php
use App\Rules\NameRule;
use Illuminate\Support\Facades\Validator;

// Test validation
$validator = Validator::make(
    ['name' => 'Test Name'],
    ['name' => NameRule::rules()]
);

$this->assertTrue($validator->passes());
```

## Using the HasCommonValidation Trait

For even simpler usage in Livewire components, use the `HasCommonValidation` trait:

```php
use App\Traits\HasCommonValidation;
use Livewire\Component;

class MyComponent extends Component
{
    use HasCommonValidation;

    public string $name = '';
    public string $description = '';
    public string $email = '';
    public string $ip_address = '';
    public int $port = 22;

    protected function rules(): array
    {
        return [
            'name' => $this->nameValidation(),
            'description' => $this->descriptionValidation(),
            'email' => $this->emailValidation(),
            'ip_address' => $this->ipAddressValidation(),
            'port' => $this->portValidation(),
        ];
    }
}
```

### Available Trait Methods

- `nameValidation(bool $required = true, int $maxLength = 255)`
- `descriptionValidation(bool $required = false, int $maxLength = 500)`
- `slugValidation(bool $required = true, int $maxLength = 255)`
- `urlValidation(bool $required = true)`
- `emailValidation(bool $required = true)`
- `pathValidation(bool $required = false, int $maxLength = 500)`
- `ipAddressValidation(bool $required = true, bool $ipv4Only = false, bool $ipv6Only = false)`
- `portValidation(bool $required = true, int $min = 1, int $max = 65535)`
- `projectSlugValidation(?int $excludeId = null)` - includes uniqueness check
- `teamNameValidation()`
- `teamDescriptionValidation()`
- `serverNameValidation()`
- `repositoryUrlValidation()`
- `branchNameValidation()`
- `imageValidation(int $maxSizeKb = 2048)`
- `coordinateValidation(string $type = 'latitude')`

## Additional Notes

- All Rule classes implement `ValidationRule` interface for object usage
- All Rule classes provide static `rules()` method for string usage
- String format is recommended for Livewire components (better compatibility)
- Object format is recommended for standalone validation or Form Requests
- The `HasCommonValidation` trait provides the easiest way to use these rules in Livewire components
- Can be easily extended for custom validation needs
