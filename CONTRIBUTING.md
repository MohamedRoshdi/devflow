# Contributing to DevFlow Pro

Thank you for considering contributing to DevFlow Pro! We appreciate your interest in making this project better.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
  - [Reporting Bugs](#reporting-bugs)
  - [Suggesting Features](#suggesting-features)
  - [Improving Documentation](#improving-documentation)
  - [Contributing Code](#contributing-code)
- [Development Setup](#development-setup)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Commit Message Guidelines](#commit-message-guidelines)

---

## Code of Conduct

This project adheres to a Code of Conduct that all contributors are expected to follow. By participating, you are expected to uphold this code. Please report unacceptable behavior to conduct@devflow.pro.

### Our Standards

**Examples of behavior that contributes to a positive environment:**
- Using welcoming and inclusive language
- Being respectful of differing viewpoints and experiences
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

**Examples of unacceptable behavior:**
- Trolling, insulting/derogatory comments, and personal attacks
- Public or private harassment
- Publishing others' private information without permission
- Other conduct which could reasonably be considered inappropriate

---

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When creating a bug report, include as many details as possible:

**Bug Report Template:**
```markdown
**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Environment:**
 - OS: [e.g. Ubuntu 22.04]
 - PHP Version: [e.g. 8.3]
 - Laravel Version: [e.g. 12.0]
 - Browser: [e.g. Chrome, Safari]
 - Version: [e.g. 22]

**Additional context**
Add any other context about the problem here.
```

### Suggesting Features

Feature suggestions are welcome! Before suggesting a feature:

1. Check if the feature has already been suggested
2. Make sure it aligns with the project's goals
3. Provide as much detail as possible

**Feature Request Template:**
```markdown
**Is your feature request related to a problem?**
A clear and concise description of what the problem is.

**Describe the solution you'd like**
A clear and concise description of what you want to happen.

**Describe alternatives you've considered**
A clear and concise description of any alternative solutions or features you've considered.

**Additional context**
Add any other context or screenshots about the feature request here.
```

### Improving Documentation

Documentation improvements are always appreciated! This includes:
- Fixing typos or grammatical errors
- Adding missing documentation
- Improving existing explanations
- Adding code examples
- Translating documentation

### Contributing Code

We actively welcome your pull requests! Here's how to get started:

1. Fork the repository
2. Create a new branch from `master`
3. Make your changes
4. Write or update tests as needed
5. Ensure all tests pass
6. Submit a pull request

---

## Development Setup

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- PostgreSQL 14+ or MySQL 8.0+
- Redis 6.0+
- Docker (optional)

### Setup Steps

```bash
# 1. Fork and clone the repository
git clone https://github.com/YOUR_USERNAME/devflow-pro.git
cd devflow-pro

# 2. Install PHP dependencies
composer install

# 3. Install Node.js dependencies
npm install

# 4. Create environment file
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Configure your database in .env
# Then run migrations
php artisan migrate

# 7. Seed test data (optional)
php artisan db:seed

# 8. Build frontend assets
npm run dev

# 9. Start development server
php artisan serve
```

### Running with Docker

```bash
# Build and start containers
docker-compose up -d

# Access the container
docker-compose exec app bash

# Run migrations
docker-compose exec app php artisan migrate
```

---

## Pull Request Process

### Before Submitting

1. **Update documentation** - If you're adding a new feature, update the relevant documentation
2. **Write tests** - Add tests for new functionality
3. **Run tests** - Ensure all tests pass: `php artisan test`
4. **Run static analysis** - Ensure code quality: `composer analyse`
5. **Run code style fixer** - Format code: `composer lint:fix`
6. **Update CHANGELOG.md** - Add your changes under "Unreleased"

### Submitting the Pull Request

1. Push your changes to your fork
2. Submit a pull request to the `master` branch
3. Fill out the pull request template completely
4. Link any related issues
5. Wait for review

**Pull Request Template:**
```markdown
## Description
Briefly describe the changes in this PR.

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## How Has This Been Tested?
Describe the tests you ran to verify your changes.

## Checklist
- [ ] My code follows the project's coding standards
- [ ] I have performed a self-review of my code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
- [ ] I have run `composer lint:fix` and `composer analyse`

## Related Issues
Closes #(issue number)
```

### Review Process

1. At least one maintainer must approve the PR
2. All CI checks must pass
3. Code coverage must not decrease
4. Documentation must be updated if applicable

---

## Coding Standards

### PHP Standards

We follow **PSR-12** coding standards with Laravel-specific conventions.

```bash
# Check code style
composer lint

# Fix code style automatically
composer lint:fix
```

### Key Guidelines

**General:**
- Use strict types: `declare(strict_types=1);`
- Use type hints for parameters and return types
- Keep methods small and focused (single responsibility)
- Avoid deep nesting (max 3 levels)

**Naming:**
- Classes: `PascalCase`
- Methods: `camelCase`
- Variables: `camelCase`
- Constants: `UPPER_SNAKE_CASE`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;

class DeploymentService
{
    public function __construct(
        private readonly DockerService $dockerService
    ) {}

    public function deploy(Project $project): bool
    {
        // Implementation
        return true;
    }
}
```

### JavaScript Standards

We use **ESLint** with Vue/Alpine.js specific rules.

**Key Guidelines:**
- Use `const` and `let`, not `var`
- Use arrow functions for callbacks
- Use template literals for string interpolation
- Keep functions small and focused

### Blade Templates

- Use 4 spaces for indentation
- Use semantic HTML5 elements
- Keep logic minimal (use Livewire components for complex logic)
- Use Tailwind CSS utility classes

---

## Testing Guidelines

### Writing Tests

DevFlow Pro uses **PHPUnit** for testing. All new features should include tests.

**Test Structure:**
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_a_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/projects', [
                'name' => 'Test Project',
                'repository_url' => 'https://github.com/user/repo.git',
                'framework' => 'laravel',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
        ]);
    }
}
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ProjectManagementTest.php

# Run with coverage
php artisan test --coverage

# Run with coverage HTML report
php artisan test --coverage-html coverage

# Run static analysis
composer analyse
```

### Test Coverage Requirements

- New features must have at least 80% code coverage
- Bug fixes should include regression tests
- Critical paths should have 100% coverage

---

## Commit Message Guidelines

We follow **Conventional Commits** specification.

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, no logic change)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

### Examples

```
feat(deployment): add rollback functionality

Implement one-click rollback to previous deployments
with automatic container restoration and state recovery.

Closes #123
```

```
fix(docker): resolve container name conflict

Fixed issue where multiple deployments would fail due to
existing container names by implementing smart cleanup.

Fixes #456
```

```
docs(readme): update installation instructions

Added Docker installation steps and improved
prerequisites section with version requirements.
```

### Scope

Common scopes:
- `deployment`
- `docker`
- `kubernetes`
- `server`
- `project`
- `security`
- `api`
- `ui`

---

## Static Analysis

We use **PHPStan** at level 8 for static analysis.

```bash
# Run PHPStan
composer analyse

# Generate baseline (if needed)
composer analyse:baseline
```

Fix all PHPStan errors before submitting a PR.

---

## Style Guide

### Livewire Components

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\{Validate, Computed, On};

class ProjectManager extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Computed]
    public function projects()
    {
        return Project::all();
    }

    #[On('project-created')]
    public function onProjectCreated(): void
    {
        unset($this->projects);
    }

    public function render()
    {
        return view('livewire.project-manager');
    }
}
```

### Service Classes

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Collection;

class ProjectService
{
    public function __construct(
        private readonly DeploymentService $deploymentService
    ) {}

    public function getAllProjects(): Collection
    {
        return Project::with('server')->get();
    }
}
```

---

## Need Help?

- **Documentation:** Check [docs/](docs/) folder
- **Discussions:** Use [GitHub Discussions](https://github.com/yourusername/devflow-pro/discussions)
- **Chat:** Join our Discord server (coming soon)
- **Email:** dev@devflow.pro

---

## Recognition

Contributors will be:
- Listed in CHANGELOG.md
- Mentioned in release notes
- Added to the contributors section

Thank you for contributing to DevFlow Pro!
