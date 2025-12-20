# Validation Rules Index

Quick reference for all available validation rules, helpers, and form requests in DevFlow Pro.

## Quick Start

```php
use App\Traits\HasCommonValidation;
use Livewire\Component;

class MyComponent extends Component
{
    use HasCommonValidation;

    public string $name = '';
    public string $email = '';

    protected function rules(): array
    {
        return [
            'name' => $this->nameValidation(),
            'email' => $this->emailValidation(),
        ];
    }
}
```

## Available Rule Classes

| Rule Class | Usage | Validates |
|-----------|-------|-----------|
| `NameRule` | `NameRule::rules()` | required\|string\|max:255 |
| `DescriptionRule` | `DescriptionRule::rules()` | nullable\|string\|max:500 |
| `SlugRule` | `SlugRule::rules()` | required\|string\|max:255\|regex:/^[a-z0-9-]+$/ |
| `UrlRule` | `UrlRule::rules()` | required\|url |
| `PathRule` | `PathRule::rules()` | nullable\|string\|max:500 |
| `EmailRule` | `EmailRule::rules()` | required\|email |
| `IpAddressRule` | `IpAddressRule::rules()` | required\|ip |
| `PortRule` | `PortRule::rules()` | required\|integer\|min:1\|max:65535 |

## Available Trait Methods

| Trait Method | Parameters | Usage |
|-------------|------------|-------|
| `nameValidation()` | `$required=true, $maxLength=255` | Standard name fields |
| `descriptionValidation()` | `$required=false, $maxLength=500` | Description fields |
| `slugValidation()` | `$required=true, $maxLength=255` | URL-safe slugs |
| `urlValidation()` | `$required=true` | URL fields |
| `emailValidation()` | `$required=true` | Email addresses |
| `pathValidation()` | `$required=false, $maxLength=500` | File/URL paths |
| `ipAddressValidation()` | `$required=true, $ipv4Only=false, $ipv6Only=false` | IP addresses |
| `portValidation()` | `$required=true, $min=1, $max=65535` | Port numbers |
| `projectSlugValidation()` | `$excludeId=null` | Project slug with uniqueness |
| `teamNameValidation()` | - | Team names |
| `teamDescriptionValidation()` | - | Team descriptions |
| `serverNameValidation()` | - | Server names |
| `repositoryUrlValidation()` | - | Git repository URLs |
| `branchNameValidation()` | - | Git branch names |
| `imageValidation()` | `$maxSizeKb=2048` | Image uploads |
| `coordinateValidation()` | `$type='latitude'` | GPS coordinates |

## Available Form Requests

| Form Request | Use Case | Key Validations |
|-------------|----------|-----------------|
| `StoreProjectRequest` | Creating projects | name, slug (unique), server_id, repository_url |
| `UpdateProjectRequest` | Updating projects | name, slug (unique except self) |
| `StoreServerRequest` | Creating servers | name, ip_address, port, username |
| `UpdateServerRequest` | Updating servers | Optional fields for partial updates |
| `StoreTeamRequest` | Creating teams | name, description, avatar |
| `UpdateTeamRequest` | Updating teams | name, description, avatar |
| `StoreProjectTemplateRequest` | Creating templates | name, slug, framework, php_version |

## Common Use Cases

### Basic Component Validation

```php
use App\Traits\HasCommonValidation;

class TeamSettings extends Component
{
    use HasCommonValidation;

    protected function rules(): array
    {
        return [
            'name' => $this->teamNameValidation(),
            'description' => $this->teamDescriptionValidation(),
            'avatar' => $this->imageValidation(),
        ];
    }
}
```

### Server Configuration

```php
use App\Traits\HasCommonValidation;

class ServerCreate extends Component
{
    use HasCommonValidation;

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

### Project Creation

```php
use App\Traits\HasCommonValidation;

class ProjectCreate extends Component
{
    use HasCommonValidation;

    protected function rules(): array
    {
        return [
            'name' => $this->nameValidation(),
            'slug' => $this->projectSlugValidation(),
            'repository_url' => $this->repositoryUrlValidation(),
            'branch' => $this->branchNameValidation(),
        ];
    }
}
```

### Using Form Requests

```php
use App\Http\Requests\StoreProjectRequest;

class ProjectController extends Controller
{
    public function store(StoreProjectRequest $request)
    {
        // $request->validated() contains validated data
        Project::create($request->validated());
    }
}
```

### Custom Validation with Rule Classes

```php
use App\Rules\NameRule;
use App\Rules\EmailRule;

class UserSettings extends Component
{
    protected function rules(): array
    {
        return [
            'first_name' => NameRule::rules(required: true, maxLength: 100),
            'last_name' => NameRule::rules(required: true, maxLength: 100),
            'email' => EmailRule::rules() . '|unique:users,email,' . auth()->id(),
        ];
    }
}
```

## File Locations

```
devflow/
├── app/
│   ├── Rules/                              # Validation Rule classes
│   │   ├── NameRule.php                   # Name validation
│   │   ├── DescriptionRule.php            # Description validation
│   │   ├── SlugRule.php                   # Slug validation
│   │   ├── UrlRule.php                    # URL validation
│   │   ├── PathRule.php                   # Path validation
│   │   ├── EmailRule.php                  # Email validation
│   │   ├── IpAddressRule.php              # IP address validation
│   │   ├── PortRule.php                   # Port validation
│   │   ├── README.md                      # Detailed usage guide
│   │   └── INDEX.md                       # This file
│   │
│   ├── Traits/
│   │   └── HasCommonValidation.php        # Validation helper trait
│   │
│   └── Http/
│       └── Requests/                      # Form Request classes
│           ├── StoreProjectRequest.php
│           ├── UpdateProjectRequest.php
│           ├── StoreServerRequest.php
│           ├── UpdateServerRequest.php
│           ├── StoreTeamRequest.php
│           ├── UpdateTeamRequest.php
│           └── StoreProjectTemplateRequest.php
│
├── VALIDATION_REFACTORING_EXAMPLE.md      # Before/after examples
└── VALIDATION_CONSOLIDATION_SUMMARY.md    # Complete summary
```

## Documentation

- **README.md** - Comprehensive usage guide with all Rule classes and Form Requests
- **INDEX.md** - This quick reference file
- **VALIDATION_REFACTORING_EXAMPLE.md** - Before/after refactoring examples
- **VALIDATION_CONSOLIDATION_SUMMARY.md** - Project summary and statistics

## Support

For questions or issues:
1. Check the detailed README: `app/Rules/README.md`
2. Review examples: `VALIDATION_REFACTORING_EXAMPLE.md`
3. See project summary: `VALIDATION_CONSOLIDATION_SUMMARY.md`

## Version

**Current Version:** 1.0.0
**PHPStan Level:** 8 (Strict)
**Laravel Version:** 12
**PHP Version:** 8.4
