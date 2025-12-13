# Changelog

All notable changes to DevFlow Pro will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [5.52.0] - 2025-12-13

### Added
- **5 New Unit Test Files (~292 test methods)**
  - `tests/Unit/Livewire/ProjectEnvironmentTest.php` - Env vars, .env parsing (54 tests)
  - `tests/Unit/Livewire/StorageSettingsTest.php` - S3, GCS, FTP, SFTP (80+ tests)
  - `tests/Unit/Livewire/ProjectTemplateManagerTest.php` - Template CRUD (71 tests)
  - `tests/Unit/Livewire/ClusterManagerTest.php` - K8s clusters (39 tests)
  - `tests/Unit/Livewire/PipelineBuilderTest.php` - CI/CD pipelines (48 tests)

- **Docker Registry Credentials Management**
  - `app/Models/DockerRegistry.php` - Multi-registry support with encryption
  - `database/migrations/2025_12_13_000002_create_docker_registries_table.php`
  - Supports: Docker Hub, GitHub, GitLab, AWS ECR, Google GCR, Azure ACR, Custom
  - Automatic Kubernetes secret creation for private registries

- **2 New Factories**
  - `database/factories/DockerRegistryFactory.php`
  - `database/factories/KubernetesClusterFactory.php`

### Improved
- **UI Loading States**
  - `project-edit.blade.php` - All inputs, selects, update button
  - `server-edit.blade.php` - Test connection, update, all inputs

- **Accessibility (WCAG AA)**
  - `deployment-list.blade.php` - Status badges now use solid colors (4.5:1+ contrast)

- **Server Provisioning UX**
  - Added progress bar with percentage, step counter, ETA, current task display
  - Auto-polling every 5 seconds during provisioning

### Documentation
- Updated `TASKS.md` - 63/127+ tasks completed (~50%)
- Test coverage improved to ~80% (5,343+ tests)

---

## [5.51.0] - 2025-12-13

### Added
- **6 New Test Files (~335 test methods)**
  - `tests/Feature/Livewire/DeploymentRollbackTest.php` - Rollback workflow (27 tests)
  - `tests/Feature/Livewire/ProjectConfigurationTest.php` - Settings & validation (66 tests)
  - `tests/Feature/Integration/ServerProvisioningTest.php` - Server setup workflow (38 tests)
  - `tests/Feature/Api/DeploymentControllerTest.php` - API endpoints & rate limiting (46 tests)
  - `tests/Unit/Livewire/ServerBackupManagerTest.php` - Backup operations (72 tests)
  - `tests/Unit/Livewire/ResourceAlertManagerTest.php` - Alert management (86 tests)

- **FileUploadRule Class** - Centralized file upload validation
  - `app/Rules/FileUploadRule.php` - MIME validation, size limits, filename sanitization
  - Blacklist for dangerous extensions (php, exe, sh, etc.)

- **Security Documentation** - `docs/security/` directory with audit reports

### Security
- **SQL Injection Audit** - Audited 47 raw SQL queries, fixed 1 critical vulnerability
  - `MultiTenantService.php` - Added `sanitizeDatabaseName()` with 3-layer defense
  - `Tenant.php` - Added database name mutator validation

- **File Upload Hardening** - Updated TeamList, TeamSettings, SSHKeyManager, request classes

### Improved
- **Loading States UI**
  - `project-create.blade.php` - Step navigation, server selection, create button
  - `server-create.blade.php` - Test connection, create server, GPS location

### Documentation
- Updated `TASKS.md` - 53/127+ tasks completed (~42%), test coverage ~78%

---

## [5.50.0] - 2025-12-13

### Added
- **6 New Feature Tests**
  - `tests/Feature/Integration/DeploymentWorkflowTest.php` - Deployment workflow integration (~25 test methods)
  - `tests/Feature/Livewire/DeploymentShowTest.php` - Deployment details, logs, authorization
  - `tests/Feature/Livewire/ProjectShowTest.php` - Tab navigation, deployments, Git, Docker (30 tests, 611 lines)
  - `tests/Feature/Livewire/ServerShowTest.php` - Server status, metrics, SSH operations
  - `tests/Feature/Livewire/ServerMetricsDashboardTest.php` - Metrics display, alerts, charts (27 tests)

### Security
- **API Rate Limiting** - Added throttle middleware to all API routes
  - `throttle:60,1` for read operations (60 requests/minute)
  - `throttle:10,1` for write operations (10 requests/minute)

### Improved
- **Service Error Logging** - Enhanced logging in 4 services with consistent patterns:
  - `FileBackupService.php` - Added `Log::error()` with context to empty catch blocks
  - `KubernetesService.php` - Added Log facade, enhanced 4 error locations with project context
  - `ServerConnectivityService.php` - Standardized logging with server ID, IP, port context
  - `DomainService.php` - Enhanced DNS lookup logging with operation and method info

### Documentation
- Updated `TASKS.md` with completed tasks, test coverage now at ~75%
- 41 tasks completed out of 127+ total

---

## [5.49.0] - 2025-12-13

### Added
- **7 New Livewire Component Tests**
  - `tests/Feature/Livewire/DeploymentListTest.php` - Deployment list display, filtering, pagination
  - `tests/Feature/Livewire/ProjectCreateTest.php` - Multi-step wizard, validation, server selection
  - `tests/Feature/Livewire/ServerCreateTest.php` - Form validation, SSH testing, Docker detection
  - `tests/Unit/Livewire/DashboardTest.php` - Stats loading, project listing, quick actions
  - `tests/Unit/Livewire/TeamSettingsTest.php` - Team update, member management, invitations
  - `tests/Unit/Livewire/DeploymentApprovalsTest.php` - Approve/reject flow, modal interactions
  - `tests/Unit/Livewire/Deployments/ScheduledDeploymentsTest.php` - Schedule CRUD operations

### Improved
- **Accessibility (ARIA Labels)**
  - `deployment-list.blade.php` - Added `role="status"`, `aria-label` to status badges and timeline
  - `project-list.blade.php` - Added `aria-label` to project cards, icons, and action buttons
  - `server-list.blade.php` - Added `role="status"`, `aria-label` to server status indicators

### Fixed
- **Mobile Responsiveness** - Fixed multi-step indicator breaking on mobile in `project-create.blade.php`

### Documentation
- Updated `TASKS.md` with completed test coverage and UI improvements
- Test coverage improved from ~7% to ~14% for Livewire components

---

## [5.48.1] - 2025-12-13

### Fixed
- **Domain Model** - Added `full_domain` accessor (computes `subdomain.domain`)
- **Dashboard Queries** - Fixed column mismatches:
  - Replaced `issuer` with `provider` in SSL certificates query
  - Replaced `url` with `target_url` in health checks query
  - Removed `full_domain` from eager loading selects (now an accessor)
- **HealthCheckService** - Added null URL validation to prevent TypeError
- **Dashboard Metrics** - Cast CPU/memory/disk values to float for `getServerHealthStatus()`
- **Migration** - Removed Doctrine DBAL dependency, using native `SHOW INDEX` query

---

## [5.48.0] - 2025-12-13

### Security Fixes (CRITICAL)
- **HMAC Signature Verification** - Webhook endpoint now verifies GitHub signatures
- **SSH Command Injection Prevention** - Added IP validation and `escapeshellarg()` to all SSH commands
- **Authorization Checks** - DeploymentShow now verifies user owns the project
- **User Data Isolation** - DeploymentList filters deployments by authenticated user
- **Hardcoded Credentials Removed** - Moved SSH credentials from SystemAdmin to environment variables

### Added
- **7 New Service Unit Tests**
  - `CacheManagementServiceTest.php`
  - `DeploymentServiceTest.php`
  - `DomainServiceTest.php`
  - `LogManagerServiceTest.php`
  - `MetricsCollectionServiceTest.php`
  - `ProjectHealthServiceTest.php`
  - `ProjectManagerServiceTest.php`
- **Scheduled Tasks Configuration** - Added automated tasks in Kernel.php
  - Health checks every 5 minutes
  - Metrics collection every 15 minutes
  - SSL certificate checks daily
  - Database backups daily at 2 AM
  - Log cleanup weekly
  - Storage cleanup monthly
- **Performance Indexes Migration** - New indexes for frequently queried columns
- **DevFlow Configuration File** - `config/devflow.php` for centralized settings
- **UI Loading & Empty States** - Added to deployment-list, deployment-show, project-list, server-list

### Fixed
- **SQL Injection Prevention** - Input validation in AnalyticsDashboard, ClusterManager, ScriptManager
- **Rate Limiting** - Added to sensitive API endpoints
- **Cache Key Isolation** - User-specific cache keys prevent data leakage

### Documentation
- `SECURITY_FIXES_SUMMARY.md` - Complete security audit documentation

---

## [5.47.0] - 2025-12-13

### Added
- **5 New Database Migrations** - Enhanced schema for better performance
  - `2025_12_12_000001_add_performance_indexes.php` - Performance indexes on frequently queried columns
  - `2025_12_12_000002_add_missing_project_columns.php` - Missing project columns from CLAUDE.md spec
  - `2025_12_12_000003_add_missing_domain_columns.php` - Missing domain columns
  - `2025_12_12_000004_add_missing_server_columns.php` - Missing server columns
  - `2025_12_12_000005_create_storage_usage_table.php` - New storage_usage analytics table

- **7 New Service Classes** - Clean architecture implementation
  - `ProjectManagerService.php` - Central orchestrator for project operations
  - `DeploymentService.php` - Deployment lifecycle management
  - `DomainService.php` - Domain health checks, DNS/SSL verification
  - `LogManagerService.php` - Centralized log aggregation and analysis
  - `CacheManagementService.php` - Unified cache operations
  - `MetricsCollectionService.php` - Server metrics collection
  - `ProjectHealthService.php` - Project health monitoring

- **Service Contracts/Interfaces** - `app/Contracts/` directory with interfaces
- **Helper Functions** - `app/Helpers/` directory with reusable utilities

### Fixed
- **Security Vulnerabilities** - Fixed command injection in DockerService, GitService, ServerConnectivityService
  - Input sanitization using `escapeshellarg()` for all user-provided values
  - Command argument validation before execution
- **N+1 Query Issues** - Added eager loading in Dashboard, ProjectList, DeploymentList, ServerList components
- **PHPStan Level 8 Compliance** - Added return types across 50+ files
  - Fixed nullable property access issues
  - Added proper type annotations to Models, Services, and Livewire components
- **Dependency Injection** - Fixed DI patterns in Livewire components using `mount()` method

### Changed
- **Dead Code Removal** - Removed duplicate `getLatestDeployment()` from Project model
- **Strict Types** - Added `declare(strict_types=1)` to all PHP files
- **Service Extraction** - Extracted business logic from Livewire components into dedicated services

### Documentation
- `SERVICE_EXTRACTION_SUMMARY.md` - Summary of service extraction work
- `SERVICES_QUICK_REFERENCE.md` - Quick reference for new services
- `SERVICE_REFACTORING_INDEX.md` - Index of refactoring changes
- `HEALTHDASHBOARD_REFACTOR.md` - HealthDashboard component refactoring details

---

## [5.46.3] - 2025-12-12

### Changed
- **File Permissions Normalized** - Standardized file permissions in storage directory
  - Changed from 755 to 644 for testing, documentation, and translation files
  - Improved security posture by removing unnecessary execute permissions

---

## [5.46.2] - 2025-12-12

### Fixed
- **Deploy Tab Empty State** - Added complete default UI for deployment tab
  - Deploy Now button with gradient styling
  - Deployment steps preview grid (Git Pull, Composer, NPM Build, etc.)
  - Git repository status, deploy script info, and server info cards
  - Pre-deployment warning notes
- **Git Tab Commits Empty** - Fixed Recent Commits not loading on mount
  - Added `loadGitTab()` call when component mounts with valid git repo

---

## [5.46.1] - 2025-12-12

### Fixed
- **Production Deployment Fix** - Resolved 500 error caused by cached dev dependencies
  - Cleared stale bootstrap cache containing `laravel/dusk` service provider
  - Regenerated autoload files with `--no-dev` flag
  - Rebuilt config, route, and view caches

### Technical Details
- Issue: `Class "Laravel\Dusk\DuskServiceProvider" not found` in production
- Root cause: `bootstrap/cache/packages.php` included dev-only packages
- Solution: Clear cache and run `composer dump-autoload -o --no-dev`

---

## [5.45.0] - 2025-12-11

### Added
- **5 New Authorization Policies** - Team, NotificationChannel, LogSource, ScheduledDeployment, PipelineConfig
- **6 Reusable Livewire Traits** - WithServerFiltering, WithServerActions, WithBulkServerActions, WithBackupCreation, WithBackupRestoration, WithBackupScheduleManagement
- **DeploymentStatusUpdatedListener** - New listener for deployment status audit logging
- **Translation Infrastructure** - 112 translation keys in English and Arabic
  - lang/en/ and lang/ar/ with status, labels, buttons, messages
  - Full RTL support for Arabic

### Changed
- **ServerList.php Refactored** - 652 → 99 lines (85% reduction)
- **DatabaseBackupManager.php Refactored** - 516 → 164 lines (68% reduction)
- **Events Enhanced** - Added documentation and ShouldBroadcast to broadcast-only events
- **deployment-approvals.blade.php** - 39 translation function calls implemented

### Documentation
- lang/README.md, USAGE_EXAMPLES.md, QUICK_REFERENCE.md for i18n

---

## [5.44.0] - 2025-12-11

### Added
- **DomainController Tests** - 45 comprehensive tests for domain CRUD operations
- **GitManager Component** - Extracted git operations (441 lines)
- **SystemInfo Component** - Extracted system configuration (285 lines)
- **ServiceManager Component** - Extracted service management (238 lines)

### Changed
- **DevFlowSelfManagement Refactored** - Reduced from 1,183 to 83 lines (93% reduction)
- **API Rate Limiting** - Added throttle middleware to 5 POST endpoints
  - /api/projects/{project}/deploy - 6 req/min
  - /api/projects/{project}/deployments - 10 req/min
  - /api/deployments/{deployment}/rollback - 6 req/min
  - /api/servers/{server}/metrics - 60 req/min
  - /api/webhooks/deploy/{token} - 30 req/min

### Fixed
- **Console.log Conditional** - JavaScript logging now only in development mode

---

## [5.43.0] - 2025-12-11

### Added
- **Comprehensive Model Tests** - 192 new unit tests for models
  - AlertModelsTest.php (61 tests) - AlertHistory, ResourceAlert
  - DeploymentModelsTest.php (21 tests) - DeploymentApproval, DeploymentComment
  - SecurityModelsTest.php (23 tests) - FirewallRule, SecurityEvent
  - HelpSystemModelsTest.php (32 tests) - HelpContent, translations, interactions
  - PipelineModelsTest.php (46 tests) - PipelineStage, PipelineStageRun
  - TenantModelsTest.php (9 tests) - TenantDeployment

- **Reusable Validation Rules** - DRY validation infrastructure
  - 8 Rule classes: NameRule, DescriptionRule, SlugRule, UrlRule, PathRule, EmailRule, IpAddressRule, PortRule
  - 7 Form Requests: StoreProject, UpdateProject, StoreServer, UpdateServer, StoreTeam, UpdateTeam, StoreProjectTemplate
  - HasCommonValidation trait with 17 helper methods
  - ~200 lines of duplicate validation code eliminated

- **New Model Factories**
  - HelpContentTranslationFactory.php
  - HelpInteractionFactory.php
  - FirewallRuleFactory.php

### Changed
- **Eager Loading Optimization** - 21 queries optimized across 8 Livewire components
  - ProjectList, FileBackupManager, DatabaseBackupManager
  - ProjectEnvironment, GitHubRepoPicker, ProjectLogs
  - ProjectDockerManagement, ProjectWebhookSettings

- **N+1 Query Fixes** - Team and backup components optimized
  - TeamList.php - Added members eager loading
  - TeamSwitcher.php - Fixed role retrieval from loaded collection
  - FileBackupManager.php - Recursive parent chain loading

### Security
- **Authorization Checks** - Added to 6 Livewire components
  - AuditLogViewer - view-audit-logs permission / super-admin role
  - HelpContentManager - admin role required
  - ProjectTemplateManager - admin role required
  - AnalyticsDashboard - view-analytics permission
  - PipelineBuilder - create/edit-pipelines permission
  - HealthDashboard - view-health-checks permission

### Fixed
- **KubernetesService Logging** - Added proper error logging to getPodStatus() and getServiceEndpoints()

---

## [5.42.0] - 2025-12-11

### Added
- **Deployment Method Selection** - New feature for choosing deployment strategy
  - Added `deployment_method` enum field to projects table (docker/standard)
  - Beautiful UI with radio button cards in project creation wizard
  - Docker option (🐳) - Uses docker-compose.yml from repository
  - Standard Laravel option (🔧) - Traditional Nginx + PHP-FPM deployment
  - Visual selection indicators with checkmarks and color themes
  - Defaults to 'docker' as most common use case

- **DevFlow Deployment Progress UI** - Real-time deployment step tracking
  - Added `wire:poll.1s="pollDeploymentStep"` for automatic step execution
  - Comprehensive deployment progress section with:
    - Real-time step status display (pending/running/success/failed)
    - Visual indicators (spinner, checkmark, error icons)
    - Status badges with color coding
    - Live console output display
    - Step counter showing progress (e.g., "Step 3 of 9")
  - Different visual states based on deployment status:
    - ✅ Success: Green gradient with checkmark icon
    - ❌ Failed: Red gradient with X icon
    - 🔄 Running: Emerald gradient with spinner
  - Close button to dismiss completed deployments
  - Deployment remains visible after completion until manually closed

- **Storage Permissions Fix Script** - Permanent solution for recurring permission issues
  - Created `fix-storage.sh` executable script
  - Automatically creates all required storage directories:
    - storage/framework/{cache,sessions,views,testing}
    - storage/logs
    - storage/app/{public,backups}
    - bootstrap/cache
  - Sets proper permissions (775 for directories)
  - Clears Laravel caches (view, config, route)
  - Added proper `.gitignore` files in all storage directories
  - Can be run anytime with `./fix-storage.sh`
  - Fixes the recurring "file_put_contents failed" error permanently

### Changed
- **PHP & Node Version Fields** - Now context-aware based on deployment method
  - Fields only show when "Standard Laravel" deployment is selected
  - Hidden for Docker deployment (versions defined in docker-compose.yml)
  - Replaced with informative blue info box for Docker explaining version source
  - Updated Step 4 summary to conditionally show PHP/Node only for Standard deployment
  - Improved framework dropdown layout with helper text

- **DevFlow Deployment Steps** - Made npm operations non-critical
  - `stepNpmInstall()` - Checks if package.json exists before running
    - Skips with message if package.json not found
    - Logs warning but continues on npm install errors
    - Prevents deployment failure due to missing Node.js setup
  - `stepNpmBuild()` - Checks if node_modules exists before running
    - Skips with message if node_modules not found
    - Attempts fallback command if first build fails
    - Logs warning but continues on build errors
    - Allows deployment to complete even if frontend assets fail

- **Deployment Step Visibility** - Improved UX for deployment monitoring
  - Changed UI condition from `@if($isDeploying && count($deploymentSteps) > 0)`
  - To: `@if(count($deploymentSteps) > 0 && ($isDeploying || $deploymentStatus === 'success' || $deploymentStatus === 'failed'))`
  - Steps now remain visible after deployment completes
  - User can review all steps and close when ready
  - Prevents premature hiding of deployment progress

### Fixed
- **DevFlow Deploy Now Button** - Button now properly executes all deployment steps
  - Added polling mechanism to automatically advance through steps
  - All 9 deployment steps now execute without stopping
  - Fixed issue where steps would disappear after 2-3 steps
  - Steps remain visible throughout entire deployment process

- **Storage Permission Issues** - Permanent fix for recurring file write errors
  - Fixed "file_put_contents(...): Failed to open stream: No such file or directory"
  - Created missing storage directories with proper structure
  - Added .gitignore files to ensure directories are tracked in git
  - Set correct permissions (775) on all storage directories
  - Includes automated fix script for future occurrences

- **Production Cache Issues** - Resolved Dusk service provider conflicts
  - Removed stale Laravel Dusk cache from production
  - Added automatic cache clearing in deployment process
  - Fixed "Class 'Laravel\Dusk\DuskServiceProvider' not found" errors
  - Improved cache clearing in fix-storage.sh script

### Technical Details

**Database Migration**
```php
// 2025_12_11_171512_add_deployment_method_to_projects_table.php
$table->enum('deployment_method', ['docker', 'standard'])
    ->default('docker')
    ->after('framework')
    ->comment('Deployment method: docker or standard');
```

**Files Modified**
- `app/Livewire/Projects/ProjectCreate.php` - Added deployment_method property and validation
- `app/Livewire/Projects/DevFlowSelfManagement.php` - Made npm steps non-critical
- `app/Models/Project.php` - Added deployment_method to fillable
- `resources/views/livewire/projects/project-create.blade.php` - Added deployment method UI
- `resources/views/livewire/projects/devflow-self-management.blade.php` - Added deployment progress UI

**New Files**
- `fix-storage.sh` - Automated storage permissions fix script
- `storage/framework/cache/.gitignore` - Ensures cache directory is tracked
- `storage/framework/sessions/.gitignore` - Ensures sessions directory is tracked
- `storage/framework/views/.gitignore` - Ensures views directory is tracked
- `storage/logs/.gitignore` - Ensures logs directory is tracked

**Commits**
- `e4e7253` - feat: add deployment method selection to project creation
- `3905a0f` - fix: add polling and display for DevFlow deployment steps
- `12a0cec` - feat: make npm steps non-critical in DevFlow deployment
- `f6da707` - fix: conditionally show PHP/Node versions only for Standard deployment
- `c27bec9` - fix: add permanent storage permission fix script

---

## [5.41.0] - 2025-12-11

### Fixed - PHPStan Level 8 Compliance (63 warnings resolved)

#### Type Hinting Improvements (23 fixes)
- **CacheManager, DeploymentActions, LogViewer** - Added comprehensive array type hints
  - Added `@var array<string, mixed>` for cache statistics arrays
  - Added `@var array<int, string>` for deployment step arrays
  - Added `@var array<string, int|float|string>` for storage information
  - Improves IDE autocomplete and static analysis accuracy

- **DevFlowSelfManagement** - Documented complex array structures
  - Added `@var array<int, array{name: string, status: string, output: string}>` for deployment steps
  - Added `@var array<string, string>` for system info and environment variables
  - Added `@var array<string, mixed>` for database info
  - Added `@var array<int, string>` for pending migrations

- **ProjectGit** - Git operation array types
  - Added `@var array<int, array<string, mixed>>` for commit arrays
  - Added `@var array<int, string>` for branch lists
  - Added `@var array<string, mixed>` for update status

#### Generic Type Parameters (16 fixes)
- **Dashboard** - Added collection generic types
  - `@property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Deployment> $recentDeployments`
  - `@property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects`
  - Provides type safety for collection operations

- **ProjectList** - Added servers() return type
  - `@return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Server>`
  - Ensures proper type inference for cached server list

- **HelpContent Models** - Eloquent relationship generics
  - `@return HasMany<HelpContentTranslation, $this>` for translations()
  - `@return HasMany<HelpInteraction, $this>` for interactions()
  - `@return BelongsTo<User, $this>` for user relationships
  - `@param Builder<HelpContent> $query` for scope methods

- **HelpContentService** - Service method return types
  - `@return Collection<int, HelpContent>` for all query methods
  - Includes getByCategory(), search(), and getPopular()

#### Parameter Type Fixes (7 fixes)
- **LogViewer, DevFlowSelfManagement** - formatBytes() method
  - Changed parameter type from implicit to `int|float $bytes, int $precision = 2`
  - Accepts both integer and float byte values

- **HelpContent** - Scope method parameters
  - Added `Builder $query` type hint to all scope methods
  - Added `string` type hints for scopeByCategory() and scopeSearch()

#### Argument Type Safety (20+ fixes)
- **InlineHelp** - Null safety for auth()->user()
  - Store user in variable before accessing properties
  - Added null check: `$user = auth()->user(); if ($user && !$user->show_inline_help)`
  - Prevents null pointer exceptions

- **CacheManager** - Handle disk_free_space() false returns
  - Added null coalescing: `$diskFree = disk_free_space($path) ?: 0`
  - Prevents false values in array assignments
  - Same fix for disk_total_space()

- **DeploymentActions** - file_get_contents() error handling
  - Check for false: `$content = file_get_contents($path); $this->deployScript = $content !== false ? $content : $default`
  - Fixed array offset access by using temporary variables
  - Pattern: `$currentStep = $this->deploymentSteps[$index]; $currentStep['status'] = 'running'; $this->deploymentSteps[$index] = $currentStep`

- **LogViewer** - Float to int conversion for array keys
  - Cast float to int: `$powInt = (int) $pow; return $units[$powInt]`
  - Prevents invalid array key type errors

- **DevFlowSelfManagement** - Multiple type safety improvements
  - parse_url() null handling: `$parsedHost = parse_url($url, PHP_URL_HOST); $domain = ($parsedHost !== false && $parsedHost !== null) ? $parsedHost : 'localhost'`
  - ini_get() type casting: `'memory_limit' => (string) ini_get('memory_limit')`
  - Same array offset fix pattern as DeploymentActions

- **DefaultSetupPreferences** - User update null safety
  - Store user before update: `$user = auth()->user(); if ($user) { $user->update([...]); }`
  - Prevents calling update() on null

- **RolesPermissions** - Null coalescing for Role properties
  - `$this->roleGuardName = $role->guard_name ?? 'web'`
  - Handles null guard_name gracefully

- **GitService** - Remove redundant empty() checks
  - Removed `empty($line)` check after array_filter()
  - array_filter() already removes empty values

### Fixed - Production Bug Fixes
- **Dashboard 500 Error** - Deployed null safety fixes for server relationships
  - Fixed "Attempt to read property 'name' on null" error on production
  - Updated production code and cleared all caches
  - Dashboard now properly handles missing server relationships

- **ProjectList 500 Error** - Livewire 3 computed property access
  - Fixed "Undefined variable $servers" in project-list.blade.php
  - Changed `@foreach($servers as $server)` to `@foreach($this->servers as $server)`
  - Livewire 3 requires `$this->` prefix for computed properties

- **Database Cleanup** - Removed orphaned projects after server recreation
  - Deleted 3 projects with invalid server_id references
  - Provides clean slate for fresh project setup

### Technical Details
- All fixes maintain backward compatibility
- Follows Laravel 12 and PHP 8.4 best practices
- PHPStan Level 8 now reports 0 errors (down from 63)
- Improved IDE autocomplete and type inference
- Better code maintainability and debugging experience

### Developer Notes
- Type hints improve static analysis accuracy
- Generic types provide better collection type safety
- Union types (int|float, string|false) properly handled
- All changes verified with PHPStan Level 8 analysis

---

## [5.40.0] - 2025-12-11

### Security - Critical Vulnerability Fixes
- **DomainController Authorization** - Added proper authorization checks
  - `store()` method now validates project update permissions
  - `update()` method validates domain update permissions via DomainPolicy
  - `destroy()` method validates domain delete permissions
  - Prevents unauthorized domain management operations

- **SQL Injection Prevention** - DeploymentController input validation
  - Added comprehensive validation for all filter parameters
  - Status values restricted to valid enums: `pending`, `running`, `success`, `failed`, `rolled_back`
  - Branch names validated with string type and max length
  - Triggered_by restricted to: `manual`, `webhook`, `scheduled`, `rollback`
  - Sort parameters whitelisted to prevent arbitrary column access
  - Per-page parameter validated with min/max constraints

- **API Token Security** - ServerMetricsController enhanced validation
  - Added token ability check for `server:report-metrics` permission
  - Prevents unauthorized metric submission
  - Comprehensive error messages for insufficient permissions
  - Documentation for automated metric collection best practices

- **Shell Injection Prevention** - DockerService hardening
  - Created `getValidatedSlug()` helper with defense-in-depth validation
  - Runtime validation using `/^[a-z0-9-]+$/` regex pattern
  - Directory traversal prevention (blocks `..` and `/` characters)
  - Updated `buildContainer()` to use validated slugs
  - Helper available for all 50+ project slug usages

- **Docker Registry Login Security** - Credential protection
  - Removed password from command line echo
  - Now uses `Process::setInput()` for secure stdin password passing
  - Added `escapeshellarg()` for registry and username parameters
  - Prevents password exposure in process lists and shell history

### Security - Authorization Improvements
- **ProjectList Authorization** - Policy-based approach
  - Replaced manual authorization checks with ProjectPolicy
  - Added `AuthorizesRequests` trait to component
  - Simplified `deleteProject()` method using `$this->authorize()`
  - More consistent with Laravel authorization patterns
  - Better maintainability and security auditing

### Performance - Query Optimization
- **ProjectList N+1 Query Fix** - Eliminated redundant database queries
  - Converted servers list to cached computed property
  - Added 5-minute cache lifetime (300 seconds)
  - Prevents repeated server queries on every component render
  - Reduced database load and improved page response time

- **Dashboard Query Optimization** - Verified existing optimizations
  - Confirmed all Project queries use eager loading
  - All queries include `with(['server', 'user'])` relationships
  - No N+1 query issues detected

### Fixed - Code Quality Issues
- **Dashboard Null Safety** - Prevented potential null pointer errors
  - Added null-safe operator for `$project->server?->name` access
  - Fallback to "Unknown Server" when server relationship missing
  - Fixed in `loadRecentActivity()` method (2 locations)
  - Prevents crashes when projects have no associated server

### Changed - Code Structure
- **DockerService Validation** - Added helper method for slug validation
  - New `getValidatedSlug()` protected method with comprehensive checks
  - Validates slug format matches `/^[a-z0-9-]+$/` pattern
  - Prevents directory traversal attempts
  - Throws `InvalidArgumentException` for invalid slugs
  - Comprehensive PHPDoc documentation

### Technical Debt
- **Test Coverage Analysis** - 203 total test files verified
  - Comprehensive coverage for all fixed components
  - Browser, Feature, and Unit tests all present
  - Identified gap: DomainPolicy lacks dedicated unit tests (has integration tests)
  - All other policies (Server, Project, Deployment) have full test coverage

### Developer Notes
- All changes maintain backward compatibility
- Follows Laravel 12 and PHP 8.4 best practices
- PHPStan Level 8 compliance maintained
- Security fixes follow OWASP top 10 guidelines

---

## [5.39.0] - 2025-12-11

### Added - DevFlow Pro Self-Management Environment Editor
- **Environment Editor Modal** - Full environment variable editing for DevFlow Pro itself
  - Added "Edit" button to Environment section in Config tab
  - Full-screen modal with individual edit controls for each variable
  - Real-time saving with loading indicators
  - Automatic config cache clearing after updates
  - **Design Features:**
    - Emerald gradient header with close button
    - Individual Save button for each environment variable
    - Reload Values button to refresh from .env file
    - Warning notice about .env file changes and cache clearing
    - Responsive modal with overflow scrolling
    - Loading spinners on save actions

- **Editable Environment Variables** - DevFlow Pro configuration management
  - **Application Settings:**
    - `APP_NAME` - Application name
    - `APP_ENV` - Environment (local/development/staging/production)
    - `APP_DEBUG` - Debug mode toggle
    - `APP_URL` - Application URL
  - **Database Configuration:**
    - `DB_HOST` - Database host
    - `DB_PORT` - Database port
    - `DB_DATABASE` - Database name
  - **System Services:**
    - `CACHE_DRIVER` - Cache driver selection
    - `QUEUE_CONNECTION` - Queue connection type
    - `SESSION_DRIVER` - Session driver
  - **Mail Configuration:**
    - `MAIL_MAILER` - Mail service provider
    - `MAIL_HOST` - Mail server host
    - `MAIL_PORT` - Mail server port
    - `MAIL_FROM_ADDRESS` - Default sender email
  - **Additional Services:**
    - `BROADCAST_DRIVER` - Broadcasting driver
    - `FILESYSTEM_DISK` - Default filesystem disk

### Improved - DevFlow Pro Self-Management UX
- **Config Tab Enhancement**
  - Changed Environment section from read-only to interactive
  - Added edit button with emerald styling matching the theme
  - Improved section header layout with space for action button
  - Better visual hierarchy between read-only display and edit mode

### Technical Details
- **Component**: `app/Livewire/Projects/DevFlowSelfManagement.php`
  - Already had backend logic for environment editing (lines 264-303)
  - `toggleEnvEditor()` method to show/hide modal
  - `updateEnvVariable()` method writes directly to .env file
  - `loadEnvVariables()` method refreshes values from file
  - Automatic `config:clear` after each variable update
- **View**: `resources/views/livewire/projects/devflow-self-management.blade.php`
  - Added Edit button to Environment section (line 632-638)
  - Added full environment editor modal (lines 687-767)
  - Modal uses Livewire wire:click and wire:model.defer
  - Individual save buttons for granular control
- **Security**: Only non-sensitive variables are shown (passwords, secrets, keys excluded)
- **User Feedback**: Success/error messages via session flash
- **Performance**: Uses wire:loading states for better UX

### Fixed - DevFlow Pro Environment Management
- **Missing UI for Environment Editing** - Previously unusable feature
  - Backend logic existed but no UI to access it
  - Users couldn't edit DevFlow Pro's own environment variables
  - Config tab showed read-only display only
  - Solution: Added Edit button and modal interface

---

## [5.38.0] - 2025-12-11

### Added - Deployment Locking & Active Deployment Banner
- **Deployment Locking System** - Prevents concurrent deployments
  - Check for active deployments before creating new ones
  - Queries for deployments with 'pending' or 'running' status
  - Automatic redirect to active deployment if one exists
  - User-friendly error messages when deployment already in progress
  - **ProjectShow Component** - Added active deployment check in `deploy()` method
  - **ProjectGit Component** - Added active deployment check in `deployProject()` method
  - Prevents "job attempted too many times" queue errors
  - Ensures only one deployment runs per project at a time

- **Active Deployment Banner** - Visual indicator on project page
  - Prominent banner displayed at top of project page
  - Only shows when deployment is pending or running
  - **Design Features:**
    - Gradient background with glassmorphism effects
    - Animated pulsing deployment icon
    - Real-time status badge (Running/Pending)
    - Shows time since deployment started
    - Displays branch being deployed
    - Clickable to view live deployment progress
  - **User Experience:**
    - Immediate visibility when entering project page
    - No need to click deploy button to discover active deployment
    - Clear call-to-action to view progress
    - Responsive design for mobile and desktop

- **Project Model Enhancement**
  - Added `activeDeployment()` relationship to Project model
  - Returns first deployment with 'pending' or 'running' status
  - Eager loaded in ProjectShow component for performance
  - Enables real-time active deployment detection

### Fixed - Deployment Flow Issues
- **Multiple Concurrent Deployments** - Resolved queue job failures
  - Previous issue: Multiple deployments queuing simultaneously
  - Error: "App\Jobs\DeployProjectJob has been attempted too many times"
  - Solution: Deployment locking checks before job dispatch
  - Impact: Smoother deployment experience, fewer failed jobs

- **Deploy Button Behavior** - Improved user feedback
  - "Deploy Now" button now properly redirects to deployment page
  - Uses Livewire's `$this->redirect()` with `navigate: true`
  - "Deploy Update" button in Git viewer includes deployment check
  - Both buttons disabled when active deployment exists

### Technical Details
- **Database Query Optimization**
  ```php
  $activeDeployment = $this->project->deployments()
      ->whereIn('status', ['pending', 'running'])
      ->first();
  ```
- **Relationship Definition**
  ```php
  public function activeDeployment()
  {
      return $this->hasOne(Deployment::class)
          ->whereIn('status', ['pending', 'running'])
          ->latest();
  }
  ```
- **Banner Location**: `resources/views/livewire/projects/project-show.blade.php:18-87`
- **Modified Components**:
  - `app/Models/Project.php` - Added activeDeployment relationship
  - `app/Livewire/Projects/ProjectShow.php` - Added deployment locking
  - `app/Livewire/Projects/ProjectGit.php` - Added deployment locking

---

## [5.37.0] - 2025-12-11

### Added - Git Viewer Component
- **ProjectGit Livewire Component** - Comprehensive Git management interface
  - `ProjectGit.php` - Livewire component for displaying Git data
  - `project-git.blade.php` - Modern UI with animated loading states
  - **Update Status Banner** - Real-time sync status with remote repository
    - Shows current vs. latest commit comparison
    - Displays number of commits behind
    - One-click "Deploy Update" button when updates available
  - **Branches Section** - Visual branch management
    - Grid layout of all available branches
    - Current branch highlighted with blue badge
    - Main branches marked with purple badge
    - Click to switch branches with confirmation
    - Shows last commit date and committer for each branch
  - **Commits History** - Paginated commit log
    - Commit hash, message, author, and timestamp
    - 10 commits per page with navigation
    - Refresh button to fetch latest commits
    - Human-readable timestamps (e.g., "2 hours ago")
  - **Integration** - Replaced empty placeholder in Git tab
    - Uses existing GitService for SSH-based Git operations
    - Real-time data fetching from remote servers
    - Error handling with retry functionality
- **Git Tab Enhancement** - No longer shows empty placeholder
  - DevFlow Pro projects: Shows self-management console
  - All other projects: Shows comprehensive Git viewer
  - Seamless integration with existing project tabs

### Fixed - ProjectEdit Return Type Error
- Fixed `500 Internal Server Error` when editing projects
  - Removed incorrect `: RedirectResponse` return type from `updateProject()` method
  - Issue: Livewire's `redirect()` returns `Redirector`, not `RedirectResponse`
  - Location: `app/Livewire/Projects/ProjectEdit.php:161`
  - Projects can now be edited without errors

### Technical Details
- **GitService Integration** - Leverages existing SSH-based Git operations
  - `getLatestCommits()` - Fetch paginated commit history
  - `getBranches()` - List all available branches
  - `checkForUpdates()` - Compare local vs remote commits
  - `switchBranch()` - Change project branch with automatic deployment
- **Error Handling** - Graceful degradation for Git failures
  - Shows loading spinner during data fetch
  - Displays error message with retry button on failure
  - Falls back to empty state if repository not cloned yet

---

## [5.36.0] - 2025-12-11

### Added - Inline Help System UI
- **Help UI Components** - Complete context-sensitive help system
  - `InlineHelpButton.php` - Livewire component for triggering help tooltips
  - `InlineHelpPanel.php` - Livewire component for displaying help content
  - `inline-help-button.blade.php` - Animated help icon button with gradient styling
  - `inline-help-panel.blade.php` - Glass morphism panel with dark mode support
- **Help Integration on 6 Pages** - Inline help added to key pages
  - Dashboard - Overview of system health and quick actions
  - Server List - Server management and monitoring guidance
  - Project List - Project creation and management help
  - Project Show - Deployment and configuration assistance
  - Deployment List - Deployment history and rollback guidance
  - Settings - Configuration and preference help

### Added - Documentation Center
- **DocsController** - Full documentation controller with markdown parsing
  - `show()` - Display documentation pages by category with syntax highlighting
  - `search()` - Full-text search across all documentation files
  - Uses League CommonMark for rich markdown rendering
- **Documentation Views**
  - `docs/layout.blade.php` - Base layout with responsive sidebar navigation
  - `docs/index.blade.php` - Documentation home page with category cards
  - `docs/show.blade.php` - Individual documentation page with TOC
  - `docs/search.blade.php` - Search results with highlighting
- **13 Documentation Categories** - Comprehensive guides with briefs:
  - **Deployments** - Git-based deployments, rollback procedures, zero-downtime strategies
  - **Domains** - Domain configuration, DNS setup, subdomain management
  - **SSL** - Certificate management, auto-renewal, Let's Encrypt integration
  - **Servers** - Server provisioning, SSH configuration, resource monitoring
  - **Monitoring** - Health checks, alerting, uptime tracking
  - **Security** - Firewall rules, Fail2ban, SSH hardening, security scans
  - **Docker** - Container management, Docker Compose, image builds
  - **Kubernetes** - Cluster management, deployments, scaling, ingress
  - **Pipelines** - CI/CD configuration, build steps, deployment automation
  - **Teams** - Team management, role-based access, invitations
  - **Database** - Backup strategies, migration management, query optimization
  - **Backups** - Automated backups, retention policies, restoration
  - **Multi-tenancy** - Tenant isolation, database separation, tenant management

### Added - DevFlow Self Management Console
- **DevFlowSelfManagement.php** - Livewire component for managing DevFlow itself
  - **System Status Cards** - Real-time monitoring of app, database, redis, queue health
  - **Storage Analytics** - Visual breakdown of storage usage by category
  - **Cache Management Actions**:
    - Clear Config Cache - Remove cached configuration for fresh reload
    - Clear Route Cache - Rebuild route cache after route changes
    - Clear View Cache - Recompile Blade templates
    - Clear App Cache - Reset application cache data
    - Rebuild All Caches - One-click full cache rebuild
  - **Deployment Actions**:
    - Deploy Now - Pull latest changes and run deployment steps
    - Enable/Disable Maintenance - Toggle maintenance mode
    - View Deployment Logs - Real-time deployment progress
  - **Log Management**:
    - View recent Laravel logs
    - Download log files for analysis
    - Clear old log entries
- **devflow-self-management.blade.php** - Modern animated UI
  - Animated gradient backgrounds with floating orbs
  - Hero header with live status badges and version info
  - Quick action buttons with hover effects
  - System stats cards with glassmorphism design
  - Responsive grid layout for all screen sizes

### Added - Navigation Improvements
- **DevFlow Pro (Self)** link added to Projects dropdown
  - Located under new "System" section separator
  - Indigo-colored gear icon for visual distinction
  - Quick access to self-management console
- **All Documentation** link added to Settings dropdown
  - Direct access to `/docs` documentation hub
  - Browse all 13 documentation categories

### Fixed - Light Mode Colors
- Improved visibility for navigation elements in light mode
  - System label: `gray-400` → `gray-600` (darker text)
  - Border: `gray-100` → `gray-200` (more visible)
  - Link text: `gray-700` → `gray-800` (bolder)
  - Icon: `indigo-500` → `indigo-600` (richer color)

### Fixed - HelpContentManager Constructor Error
- Fixed `ArgumentCountError` in Livewire component
  - Replaced constructor dependency injection with `app()` helper
  - Livewire full-page components don't support constructor injection

### Fixed - DocsController Laravel 12 Compatibility
- Removed deprecated `$this->middleware()` call from controller constructor
  - Laravel 12 no longer supports middleware in controller constructors
  - Middleware is now defined in routes/web.php

### Fixed - Markdown Frontmatter Rendering
- Strip YAML frontmatter before markdown conversion
  - Prevents raw `---title: ...---` text from appearing in content
  - Frontmatter now properly parsed for page title/description only

### Added - Dashboard Docs Quick Link
- Added "Docs" button to dashboard header
  - Book icon with gray styling
  - Quick access to documentation center from main dashboard

### Added - Documentation Content Styling
- Comprehensive CSS for markdown content rendering
  - **Headers**: Proper sizing, colors, and spacing (H1, H2, H3)
  - **Paragraphs**: Readable line height and gray text colors
  - **Lists**: Bullet/numbered with proper indentation
  - **Code**: Inline code with background, code blocks with dark theme
  - **Links**: Blue with hover underline
  - **Blockquotes**: Blue left border with light background
  - **Dark mode**: All styles adapt automatically
  - **Permalink anchors**: Hidden by default, visible on hover

---

## [5.35.0] - 2025-12-10

### Added - Modern Error Pages
- **Custom Error Page Suite** - Beautiful glass morphism design for all HTTP errors
  - `404.blade.php` - Page Not Found with emerald/teal gradient
  - `500.blade.php` - Server Error with red/rose gradient
  - `503.blade.php` - Maintenance Mode with animated spinning gears
  - `403.blade.php` - Forbidden with purple/violet gradient and lock icon
  - `401.blade.php` - Unauthorized with blue/indigo gradient
  - `419.blade.php` - Session Expired with cyan/teal gradient
  - `429.blade.php` - Too Many Requests with countdown timer
  - `layout.blade.php` - Base layout with floating backgrounds and grid pattern
- All error pages feature:
  - Glass morphism design with backdrop blur
  - Animated floating gradient blobs
  - Responsive design for all screen sizes
  - Contextual action buttons (Go Home, Sign In, Refresh)
  - Dark theme optimized

### Added - Real-Time Deployment Progress
- **Polling-Based Step Execution** - Deployment steps now show one-by-one in real-time
  - `wire:poll.500ms="pollDeploymentStep"` for live UI updates
  - Step-by-step execution instead of monolithic batch
  - Status badges showing "Running Step X/10", "Complete", or "Failed"
  - Visual progress indicator during deployment

### Fixed - Bug Fixes
- **TypeError in formatBytes** - Fixed `log(): Argument #1 ($num) must be of type float, string given`
  - Cast `$bytes` to float and added early return for 0 value
- **Route Not Defined** - Fixed `Route [devflow.logs.download] not defined`
  - Updated blade template to use correct route name `projects.devflow.logs.download`
- **503 During Deployment** - Fixed maintenance mode issue
  - Proper error handling to bring app back up after failed deployments

### Added - Inline Help System Database
- **Database Migration** - `create_help_contents_tables.php` with 4 tables
  - help_contents, help_content_translations, help_interactions, help_content_related
- **Models** - HelpContent, HelpContentTranslation, HelpInteraction, HelpContentRelated
  - Multi-language support with automatic English fallback
  - Analytics tracking (views, helpful votes)
- **HelpContentService** - Complete service layer with caching
  - getByKey(), recordView(), recordHelpful(), search(), getPopularHelp()

---

## [5.34.0] - 2025-12-09

### Added - Comprehensive Test Suite Expansion
- **19 new test files** added across all test categories
- **Unit Tests**: Middleware tests, Event dispatching tests
- **Feature Tests**: API tests (Project, Server), Authentication, Webhooks, Team management
- **Security Tests**: Input validation, Session security, Authorization, File upload security
- **Browser Tests**: Notification channel management tests
- Total test files: 188 (up from 169)

### Fixed - Critical Security Vulnerabilities (IMPORTANT!)
- **IDOR Vulnerability Fixed**: `ProjectShow.php` - Added authorization check
- **Privilege Escalation Fixed**: `ProjectEdit.php` - Added owner/team validation
- **Unauthorized Delete Fixed**: `ProjectList.php` - Added ownership check for delete
- **Server Access Fixed**: `ServerPolicy.php` - Changed from "allow all" to proper ownership-based auth

### Fixed - Unit Test Issues
- **DeploymentFailed Event**: Added `$error` property and constructor parameter
- **Team Model**: `hasMember()` now includes owner check for proper authorization
- **MiddlewareTest**: Fixed user resolver for proper authentication testing

### Fixed - Feature Test Infrastructure
- **ServerFactory**: Removed `ssh_password` field causing schema mismatch
- **TestCase**: Changed `RefreshDatabase` to `DatabaseTransactions` (PostgreSQL deadlock fix)
- Resolved database migration conflicts and race conditions

### Security Test Coverage
- XSS prevention tests
- SQL injection prevention tests
- Command injection prevention tests
- CSRF protection tests
- Session fixation/hijacking tests
- File upload security tests (MIME spoofing, path traversal)
- Authorization/IDOR tests
- Mass assignment protection tests

---

## [5.33.0] - 2025-12-09

### Added - Deploy Script Enhancements
- **Automatic Backup** before each deployment (app files + database)
- **Rollback Command** (`./deploy.sh --rollback`) to restore previous deployment
- **Health Check** after deployment (HTTP, database, queue workers)
- **Backup Rotation** - keeps only last 5 backups to save disk space
- **Colored Output** for better visibility of deployment stages
- **Timestamp Tracking** for each deployment

### Added - Comprehensive Lazy Loading
- **Health Dashboard**: Page loads instantly with skeleton placeholders
- **Server Show**: Metrics load asynchronously after initial render
- **Docker Dashboard**: All SSH operations deferred via `wire:init`
- Animated pulse skeleton placeholders across all heavy components
- Filter buttons disabled during loading states

### Fixed - Livewire Constructor Injection
- Fixed `TeamSettings.php` constructor injection (Livewire 3 incompatibility)
- Changed to `boot()` method for service injection

### Fixed - Deployment Live Streaming
- Show actual SSH and Docker commands being executed (`$ ssh ...`, `$ docker exec ...`)
- Save logs more frequently for real-time updates
- Added `failed()` handler to properly mark failed deployments
- Increased timeout to 30 minutes for large builds
- Set tries=1 to prevent auto-retry on deployments
- Show git clone/pull progress output

### Fixed - Docker Container Cleanup
- Aggressive cleanup of orphaned containers before starting compose services
- Parse docker-compose.yml for explicit container_names
- Remove containers matching project slug pattern
- Prevents "container name already in use" errors

### Fixed - Analytics Dashboard
- Cast avg() result to float before round() to fix PHP 8 type error

---

## [5.32.1] - 2025-12-09

### Fixed - Test Suite Improvements

#### Dashboard Template Fixes
- Fixed undefined array key errors in `dashboard.blade.php`
- Added null coalescing operators for all stats array accesses
- Fixed `$stats`, `$sslStats`, `$healthCheckStats`, `$queueStats` arrays
- Fixed `$server['health_status']` access in server health section

#### Security Test Fixes
- Fixed LDAP injection test assertions (was failing on CSS `*` character)
- Fixed XSS test assertions (now properly check for escaped payloads)
- Fixed XXE injection test to handle non-existent route gracefully

#### Feature Test Fixes
- Updated `ProjectManagementTest` to use Livewire component for creation
- Fixed authorization test to reflect current behavior
- Updated `DashboardTest` assertions to use `assertGreaterThanOrEqual`

#### Added - Docker Testing Environment
- Added `Dockerfile.test` for containerized testing
- Added `docker-compose.yml` for test orchestration
- Added `phpunit.dusk.xml` for browser test configuration
- Updated `TESTING.md` with Docker testing instructions

---

## [5.32.0] - 2025-12-08

### Added - Comprehensive Test Suite (4,300+ Tests)

#### Browser Tests (97 files, ~2,500 tests)
- **35 new browser test files** for complete Livewire component coverage
- SystemSettingsTest, FirewallManagerTest, Fail2banManagerTest, SSHSecurityManagerTest
- GitHubRepoPickerTest, ProjectEnvironmentTest, ServerSecurityDashboardTest
- ApiTokenManagerTest, HealthCheckManagerTest, StorageSettingsTest
- TeamManagementTest, NotificationChannelTest, TenantManagerTest
- ClusterManagerTest, ScriptManagerTest, LogViewerTest, WebhookLogsTest
- UserListTest, ForgotPasswordTest, ServerShowTest, ServerCreateTest, ServerEditTest
- HealthDashboardTest, SystemAdminTest, NotificationLogsTest
- ProjectShowTest, ProjectListTest, ProjectCreateTest, ProjectEditTest
- DeploymentListTest, DeploymentShowTest, QueueMonitorTest, DomainManagerTest
- GitHubSettingsTest, SSHKeyManagerTest

#### Unit Tests - Services (11 new files, ~400 tests)
- **KubernetesServiceTest** - 38 tests for cluster, pod, deployment operations
- **SecurityScoreServiceTest** - 37 tests for security scoring algorithms
- **SlackDiscordNotificationServiceTest** - 37 tests for webhook notifications
- **RemoteStorageServiceTest** - 31 tests for S3/cloud storage operations
- **PipelineServiceTest** - 38 tests for CI/CD pipeline management
- **DeploymentScriptServiceTest** - 41 tests for script execution
- **ServerSecurityServiceTest** - 27 tests for security auditing
- **SSHSecurityServiceTest** - 41 tests for SSH hardening
- **Fail2banServiceTest** - 53 tests for intrusion prevention
- **SystemSettingsServiceTest** - 30 tests for application settings

#### Unit Tests - Models & Requests
- **AdditionalModelsTest** - 122 tests for 15 previously untested models
  - KubernetesCluster, Pipeline, PipelineRun, PipelineStage, PipelineStageRun
  - ResourceAlert, SecurityEvent, SecurityScan, NotificationLog
  - ProjectAnalytic, ProjectSetupTask, DeploymentApproval
  - DeploymentComment, DeploymentScript, TenantDeployment
- **FormRequestValidationTest** - 172 tests for API request validation
  - StoreProjectRequest, StoreServerRequest
  - UpdateProjectRequest, UpdateServerRequest

#### Feature Tests
- **ApiEndpointTest** - 44 tests for full API CRUD coverage
  - PUT/DELETE operations for projects and servers
  - Webhook signature validation
  - Token authentication and authorization
- **WorkflowIntegrationTest** - 56 tests for end-to-end workflows
  - Pipeline execution workflow
  - Multi-tenant deployment workflow
  - Webhook delivery workflow
  - Bulk server operations
  - Security scanning workflow

#### Security Tests
- **PenetrationTest** - 39 comprehensive security tests
  - XSS payload prevention (script, event handler, URL-based)
  - SQL injection prevention (union, boolean, time-based)
  - Race condition protection
  - Mass assignment protection
  - API token abuse prevention
  - Authentication security (session fixation, brute force)
  - CSRF protection

#### Supporting Files
- 16 new model factories for test data generation
- Updated phpunit.xml for SQLite test database
- Updated phpstan.neon with test-specific rules

### Summary
- **Total Tests**: 4,300+ across all categories
- **Test Coverage**: ~90% of application code
- **New Test Files**: 50+ files
- **Lines of Test Code**: 51,000+

---

## [5.31.0] - 2025-12-06

### Added

- **MiddlewareRequestsTest.php** - 57 Middleware & Request unit tests
  - AuthenticateApiToken: token validation, authentication flow
  - EnsureTeamAccess: team membership verification
  - StoreServerRequest: server creation validation rules
  - UpdateServerRequest: server update validation rules
  - StoreProjectRequest: project creation validation rules
  - UpdateProjectRequest: project update validation rules

- **PoliciesTest.php** - 37 Policy unit tests
  - ServerPolicy: view, create, update, delete authorization
  - ProjectPolicy: CRUD and deployment authorization
  - DeploymentPolicy: view, create, rollback authorization
  - Ownership-based and team-based access control

- **EventsNotificationsTest.php** - 71 Event, Notification & Mail tests
  - DashboardUpdated, DeploymentCompleted/Failed/Started events
  - DeploymentLogUpdated, DeploymentStatusUpdated events
  - PipelineStageUpdated, ProjectSetupUpdated events
  - ServerMetricsUpdated event broadcasting
  - DeploymentApprovalRequested notification
  - ServerProvisioningCompleted notification
  - SSLCertificateExpiring/Renewed notifications
  - UserMentionedInComment notification
  - TeamInvitation mailable

### Improved

- Complete application test coverage: 165 new tests across 3 test files
- Total unit tests now at 2,308 (complete application coverage)
- PHPStan Level 6 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)
- 100% coverage achieved for ALL application layers

---

## [5.30.0] - 2025-12-06

### Added

- **CommandsTest.php** - 76 Console Command unit tests
  - BackupDatabase/BackupFiles: database and file backup commands
  - CheckResourceAlertsCommand: resource monitoring alerts
  - CheckSSLCommand/CheckSSLExpiry: SSL certificate checks
  - CleanupBackups/CleanupMetricsCommand: cleanup operations
  - CollectServerMetrics: server metrics collection
  - FixPermissionsCommand: permission fixing utility
  - MonitorServersCommand: server monitoring
  - ProcessScheduledDeployments: scheduled deployment processing
  - ProvisionServer: server provisioning workflow
  - RenewSSL/SSLRenewCommand: SSL certificate renewal
  - RunBackupsCommand/RunServerBackupsCommand: backup execution
  - RunHealthChecksCommand: health check execution
  - RunQualityTests: quality test runner
  - SyncLogsCommand: log synchronization
  - VerifyBackup: backup verification

- **ControllersTest.php** - 68 Controller unit tests
  - DeploymentWebhookController: webhook handling for deployments
  - ServerMetricsController: server metrics API
  - V1/DeploymentController: deployment CRUD API
  - V1/ProjectController: project CRUD API
  - V1/ServerController: server CRUD API
  - GitHubAuthController: GitHub OAuth flow
  - TeamInvitationController: team invitation handling
  - WebhookController: general webhook processing

- **JobsTest.php** - 41 Job unit tests
  - DeployProjectJob: project deployment queue job
  - InstallDockerJob: Docker installation job
  - ProcessProjectSetupJob: project setup processing

### Improved

- Console/Controller/Job test coverage: 185 new tests across 3 test files
- Total unit tests now at 2,143 (complete application coverage)
- PHPStan Level 6 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)
- 100% coverage achieved for Commands, Controllers, and Jobs

---

## [5.29.0] - 2025-12-06

### Added

- **DashboardAdminComponentsTest.php** - 92 Livewire component tests
  - Dashboard: stats loading, server/project counts, quick actions
  - DashboardOptimized: cached stats, performance optimization
  - HealthDashboard: health checks display, status indicators
  - SystemAdmin: admin functions, system settings
  - AuditLogViewer: log filtering, pagination, search
  - Login/Register/ForgotPassword: auth flows, validation
  - HomePublic: public portfolio, project showcase
  - AnalyticsDashboard: metrics display, chart data

- **ServerComponentsTest.php** - 135 Livewire component tests
  - ServerList: server listing, filtering, status display
  - ServerShow/Create/Edit: CRUD operations, validation
  - ServerProvisioning: provisioning workflow, progress tracking
  - ServerMetricsDashboard: metrics visualization, polling
  - ServerBackupManager: backup operations, scheduling
  - ServerTagManager/Assignment: tag CRUD, server assignment
  - SSHTerminal: terminal interaction, command execution
  - SSLManager: certificate management, renewal
  - ResourceAlertManager: alert configuration, thresholds
  - Security components: firewall, fail2ban, SSH security, scans

- **ProjectDeploymentComponentsTest.php** - 76 Livewire component tests
  - ProjectList/Show/Create/Edit: project CRUD operations
  - ProjectConfiguration: settings management
  - ProjectEnvironment: env variable management
  - ProjectLogs/DockerManagement: log viewing, Docker ops
  - DatabaseBackupManager/FileBackupManager: backup operations
  - GitHubRepoPicker: GitHub integration
  - DeploymentList/Show: deployment listing, details
  - DeploymentApprovals/Comments: approval workflow
  - DeploymentRollback: rollback functionality
  - ScheduledDeployments: scheduling management

- **SettingsUtilityComponentsTest.php** - 87 Livewire component tests
  - ApiTokenManager/SSHKeyManager: token/key CRUD
  - GitHubSettings/StorageSettings: integration settings
  - QueueMonitor: queue status, job management
  - HealthCheckManager: health check configuration
  - SystemStatus: system health display
  - TeamList/Settings/Switcher: team management
  - UserList: user administration
  - LogViewer/LogSourceManager: log management
  - NotificationChannelManager: notification config
  - ScriptManager/DockerDashboard: utilities
  - TenantManager/ClusterManager: advanced features

### Improved

- Livewire component test coverage: 390 new tests across 4 test files
- Total unit tests now at 1,958 (31 service + 4 model + 4 Livewire test files)
- 75 Livewire components now have test coverage
- PHPStan Level 6 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)

---

## [5.28.0] - 2025-12-06

### Added

- **CoreModelsTest.php** - 112 unit tests for core models
  - User model: relationships, accessors, scopes, role management
  - Server model: status checks, online/offline scopes, SSH configuration
  - Project model: deployment relations, framework detection, active scopes
  - Deployment model: status management, duration calculation, rollback relations

- **InfrastructureModelsTest.php** - 86 unit tests for infrastructure models
  - Domain model: SSL status, primary domain handling, project relations
  - SSLCertificate model: expiry checks, renewal status, validity scopes
  - HealthCheck model: result relations, active/inactive scopes
  - LogEntry model: severity levels, source relations, filtering

- **BackupModelsTest.php** - 69 unit tests for backup models
  - DatabaseBackup model: project/server relations, status scopes
  - FileBackup model: storage configuration, backup types
  - ServerBackup model: scheduling, status management
  - BackupSchedule model: frequency settings, next run calculation

- **TeamAuthModelsTest.php** - 92 unit tests for team/auth models
  - Team model: member relations, owner validation, settings
  - TeamMember model: role management, permission scopes
  - ApiToken model: expiry handling, scope validation
  - AuditLog model: action tracking, user relations

### Improved

- Model unit test coverage: 359 new tests across 4 test files
- Total unit tests now at 1,568 (31 service + 4 model test files)
- PHPStan Level 6 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)
- Fixed FailedJob model PHPDoc for generic trait

---

## [5.27.0] - 2025-12-06

### Added

- **DockerInstallationServiceTest.php** - 45 unit tests for Docker installation
  - Docker and Docker Compose installation
  - Installation verification and version checking
  - Docker service management (start, stop, restart)
  - Container and image management
  - Error handling for installation failures

- **GPSServiceTest.php** - 35 unit tests for GPS and location services
  - Project and server discovery within radius
  - Haversine distance calculation
  - Reverse geocoding via OpenStreetMap Nominatim
  - Geofencing with custom radius support
  - Coordinate validation and edge cases

- **ServerBackupServiceTest.php** - 38 unit tests for server backup operations
  - Full and incremental server backups
  - Backup restoration and verification
  - Retention policy enforcement
  - Remote storage integration
  - Backup scheduling and automation

- **SSLServiceTest.php** - 45 unit tests for SSL certificate operations
  - Let's Encrypt certificate issuance
  - Certificate renewal and revocation
  - SSL verification and expiry checking
  - Nginx configuration updates
  - Error handling and logging

### Improved

- Unit test coverage expanded with 163 new service tests
- Total unit tests now at 1,209 (31 service test files)
- PHPStan Level 6 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)
- Complete 100% service test coverage achieved

---

## [5.26.0] - 2025-12-06

### Added

- **StorageServiceTest.php** - 41 unit tests for storage management
  - Project storage calculation via SSH
  - Total storage statistics with percentages
  - Storage cleanup (logs, cache, sessions, views)
  - SSH command building with custom ports and keys
  - Error handling and logging

- **ServerProvisioningServiceTest.php** - 43 unit tests for server provisioning
  - Complete LEMP stack installation (Nginx, MySQL, PHP)
  - Composer and Node.js installation
  - Firewall configuration with custom ports
  - Swap file setup and SSH hardening
  - Provisioning script generation
  - Notification sending on success/failure

- **MultiTenantServiceTest.php** - 44 unit tests for multi-tenant management
  - Tenant deployment with custom options
  - Tenant creation with seed data and domains
  - Tenant status updates (active, suspended)
  - Cache clearing including Redis
  - Service restart and migration handling
  - Tenant statistics calculation

- **BulkServerActionServiceTest.php** - 40 unit tests for bulk operations
  - Bulk server ping with status updates
  - Bulk server reboot operations
  - Docker installation on multiple servers
  - Service restart across server fleet
  - Summary statistics calculation
  - Error handling and continuation

### Improved

- Unit test coverage expanded with 168 new service tests
- Total unit tests now at 1,046 (878 + 168)
- PHPStan Level 6 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)
- All 29 service classes now have comprehensive unit tests

---

## [5.25.0] - 2025-12-06

### Added

- **AlertNotificationServiceTest.php** - 39 unit tests for alert notifications
  - Multi-channel notification dispatch (email, Slack, Discord)
  - Email notification formatting and sending
  - Slack webhook with Block Kit message structure
  - Discord webhook with embed formatting
  - Value formatting (CPU/memory/disk with %, load without)
  - Error handling and channel failure isolation

- **SSHKeyServiceTest.php** - 46 unit tests for SSH key management
  - SSH key generation (ED25519, RSA, ECDSA)
  - Key pair import and validation
  - Fingerprint calculation (MD5, SHA256)
  - Key deployment to local and remote servers
  - Key removal from authorized_keys
  - Localhost detection and SSH command building

- **QueueMonitorServiceTest.php** - 51 unit tests for queue monitoring
  - Queue statistics (pending, processing, failed counts)
  - Recent jobs retrieval with pagination
  - Failed jobs management (retry, delete, clear)
  - Jobs per hour calculation with caching
  - Worker status detection (standard, Horizon)
  - Queue breakdown by name
  - Processing rate and success rate calculation

- **LogAggregationServiceTest.php** - 46 unit tests for log aggregation
  - Log syncing from multiple sources (file, Docker, journald)
  - Log parsing (Nginx, Laravel, PHP, MySQL, system, Docker)
  - Log searching with filters (server, project, level, date range)
  - Log cleanup with retention policies
  - Level normalization (warn→warning, crit→critical)
  - Edge cases (malformed lines, multiline messages)

### Improved

- Unit test coverage expanded with 182 new service tests
- Total unit tests now at 878 (696 + 182)
- PHPStan Level 6 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)
- New factories: SSHKeyFactory, FailedJobFactory

---

## [5.24.0] - 2025-12-06

### Added

- **AuditServiceTest.php** - 40 unit tests for audit logging
  - Audit logging with authenticated/unauthenticated users
  - Old/new value tracking with change detection
  - Sensitive data sanitization (passwords, SSH keys, API keys, secrets, tokens)
  - Polymorphic model logging support
  - Log filtering by user, action, model type, date range, IP
  - Activity statistics generation
  - CSV export with proper formatting

- **GitHubServiceTest.php** - 50 unit tests for GitHub API integration
  - OAuth authorization flow and callback handling
  - Token refresh mechanism with expiration handling
  - Repository listing with pagination
  - Branch and commit history retrieval
  - Repository synchronization to database
  - Webhook creation and deletion
  - Error handling (401, 404, 429, 500 errors)
  - All HTTP methods (GET, POST, PUT, PATCH, DELETE)

- **ProjectSetupServiceTest.php** - 45 unit tests for project initialization
  - Setup initialization with various configurations
  - SSL certificate setup scenarios
  - Webhook configuration testing
  - Health check monitoring setup
  - Database backup scheduling
  - Notification configuration
  - Initial deployment triggering
  - Progress tracking and reporting
  - Failed task retry functionality
  - Task skipping functionality

- **ResourceAlertServiceTest.php** - 41 unit tests for resource monitoring
  - Server resource checking with latest metrics
  - CPU/memory/disk/load threshold evaluation
  - Alert triggering when thresholds exceeded
  - Alert resolution when thresholds normalize
  - Cooldown period validation
  - Notification dispatch on alerts
  - Alert history tracking
  - Test alert functionality
  - Message formatting for different resource types

### Improved

- Unit test coverage expanded with 176 new service tests
- Total unit tests now at 696 (520 + 176)
- PHPStan Level 6 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)
- New factories: GitHubConnectionFactory, GitHubRepositoryFactory, ProjectSetupTaskFactory, ResourceAlertFactory, AlertHistoryFactory

---

## [5.23.0] - 2025-12-06

### Added

- **FirewallServiceTest.php** - 52 unit tests for UFW firewall management
  - UFW status checking (active/inactive)
  - Enable/disable firewall operations
  - Port and IP-based allow/deny rules
  - Rule deletion and listing
  - SSH command execution with different auth methods
  - Security event logging

- **FileBackupServiceTest.php** - 42 unit tests for file backup operations
  - Full and incremental backup creation
  - Backup scheduling and configuration
  - Retention policy enforcement
  - Multi-storage backend support (local, S3)
  - Backup restoration and verification
  - Exclude patterns management

- **WebhookServiceTest.php** - 42 unit tests for webhook processing
  - GitHub HMAC-SHA256 signature verification
  - GitLab token verification
  - GitHub/GitLab payload parsing
  - Event type detection
  - Deployment trigger decision logic
  - Webhook delivery recording

- **TeamServiceTest.php** - 39 unit tests for team management
  - Team creation with owner assignment
  - Team invitation and acceptance workflow
  - Member management (add/remove/roles)
  - Role-based permissions
  - Ownership transfer
  - Team deletion and cleanup

### Improved

- Unit test coverage expanded with 175 new service tests
- Total unit tests now at 520 (345 + 175)
- PHPStan Level 6 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)
- New factories: TeamFactory, TeamMemberFactory, TeamInvitationFactory, FileBackupFactory

---

## [5.22.0] - 2025-12-06

### Added

- **NotificationServiceTest.php** - 41 unit tests for notification system
  - Email notification sending (success/failure)
  - Slack notifications with Block Kit formatting
  - Discord notifications with embeds
  - Webhook notification delivery with signatures
  - Health check failure/recovery notifications
  - Deployment event notifications
  - Channel configuration and selection

- **SSLManagementServiceTest.php** - 44 unit tests for SSL certificates
  - Certificate issuance via Let's Encrypt
  - Certificate renewal workflows
  - Auto-renewal scheduling and cron setup
  - Expiry date parsing and checking
  - Certificate revocation operations
  - Domain validation (HTTP-01, DNS-01)
  - SSH command execution mocking

- **HealthCheckServiceTest.php** - 37 unit tests for health monitoring
  - HTTP health checks (200/non-200 responses)
  - TCP connectivity checks
  - Ping operations and timeouts
  - SSL certificate expiry checks
  - Health result recording
  - Status transitions (healthy/degraded/down)
  - Consecutive failure tracking
  - Notification triggering

- **ServerConnectivityServiceTest.php** - 43 unit tests for server connectivity
  - SSH connection testing (success/failure)
  - Password and SSH key authentication
  - Latency measurement
  - Localhost detection
  - Server status updates
  - Service restart operations
  - System cache clearing
  - Disk and memory usage monitoring

### Improved

- Unit test coverage expanded with 165 new service tests
- Total unit tests now at 345 (180 + 165)
- PHPStan Level 6 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)

---

## [5.21.0] - 2025-12-06

### Added

- **GitServiceTest.php** - 39 unit tests for Git operations
  - Repository cloning and initialization
  - Git pull and fetch operations
  - Branch checkout and switching
  - Commit history retrieval
  - SSH key authentication
  - Safe directory configuration
  - Error handling and rollback

- **DockerServiceTest.php** - 69 unit tests for Docker operations
  - Docker compose up/down/restart
  - Container management (start, stop, remove)
  - Container logs and inspection
  - Volume and network operations
  - Image pull and build
  - Health check detection
  - Docker daemon connectivity

- **DeploymentApprovalServiceTest.php** - 39 unit tests for approval workflow
  - Approval requirement detection
  - Environment and branch-based rules
  - Approval request creation
  - Approval and rejection processing
  - Permission validation
  - Notification triggering
  - Audit logging integration

- **RollbackServiceTest.php** - 33 unit tests for rollback operations
  - Rollback to previous deployment
  - State backup before rollback
  - Git checkout and reset
  - Environment restoration
  - Docker container rebuild
  - Health check verification
  - Rollback point management

### Improved

- Unit test coverage expanded with 180 new service tests
- PHPStan Level 6 compliance maintained (0 errors in app/)
- Laravel Pint code style compliance maintained (0 issues)
- Comprehensive testing for core deployment and infrastructure services

---

## [5.20.0] - 2025-12-06

### Added

- **ProjectDetailTest.php** - 50 browser tests for public project detail page
  - Public page access without authentication
  - Project info display (name, description, status)
  - Technology stack and framework badges
  - Live demo links and domain display
  - 404 handling and private project protection
  - Responsive design (mobile, tablet, desktop)

- **PipelineSettingsTest.php** - 48 browser tests for pipeline settings
  - Pipeline enable/disable toggle
  - Auto-deploy branch configuration
  - Skip and deploy patterns management
  - Webhook secret generation and display
  - Settings persistence and validation

- **DefaultSetupPreferencesTest.php** - 48 browser tests for default preferences
  - Default SSL, webhooks, health checks settings
  - Default backup and notification settings
  - Theme and UI preferences
  - Settings save and persistence
  - Mobile and tablet responsiveness

- **SecurityAuditLogTest.php** - 50 browser tests for security audit logs
  - Audit log entries display and filtering
  - Event type filtering (firewall, SSH, IP bans)
  - Date range and search functionality
  - Export to CSV, JSON, PDF
  - Severity indicators and statistics

### Improved

- Browser test coverage expanded from 2461 to 2657 tests (63 test files)
- PHPStan Level 8 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)
- Complete coverage for all remaining Livewire components

---

## [5.19.0] - 2025-12-06

### Added

- **HomePublicTest.php** - 55 browser tests for public home page
  - Public page accessibility (no auth required)
  - Hero section and platform features display
  - NileStack branding and DevFlow Pro attribution
  - SEO meta tags and accessibility
  - Responsive design (mobile, tablet, desktop)
  - Dark mode and theme toggle

- **ProjectLogsTest.php** - 48 browser tests for project logs viewer
  - Laravel and Docker log viewing
  - Log level filtering and search
  - Real-time log tailing
  - Log download and clear functionality
  - Line count selector and refresh

- **ProjectDockerManagementTest.php** - 50 browser tests for Docker management
  - Container list and status indicators
  - Container operations (start, stop, restart, rebuild)
  - Container logs and resource monitoring
  - Docker compose operations
  - Networks, volumes, and port mappings

- **PipelineRunShowTest.php** - 48 browser tests for pipeline run details
  - Run status and progress display
  - Stage breakdown and logs viewing
  - Trigger and commit information
  - Re-run, cancel, and retry operations
  - Artifacts and test results display

### Improved

- Browser test coverage expanded from 2260 to 2461 tests (59 test files)
- PHPStan Level 8 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)

---

## [5.18.0] - 2025-12-06

### Added

- **PipelineBuilderTest.php** - 51 browser tests for CI/CD pipeline builder
  - Pipeline creation and configuration
  - Stage management (add, edit, delete, reorder)
  - Stage types (Pre-Deploy, Deploy, Post-Deploy)
  - Environment variables per stage
  - Pipeline templates (Laravel, Node.js, Static Site)
  - Timeout and failure handling configuration

- **SecurityScanDashboardTest.php** - 48 browser tests for security scanning
  - Security scan initiation and progress tracking
  - Vulnerability severity levels (critical, high, medium, low)
  - Remediation suggestions and false positive management
  - Scan scheduling and history
  - Compliance checks (PCI, HIPAA)
  - Export reports (PDF, CSV)

- **ProjectConfigurationTest.php** - 50 browser tests for project configuration
  - Basic project settings (name, repository, branch)
  - Framework and version selection (PHP, Node.js)
  - Deployment and build settings
  - Environment and storage configuration
  - Auto-deploy and health check settings

- **SystemStatusTest.php** - 48 browser tests for system status monitoring
  - Service status indicators (Database, Redis, Queue, Cache)
  - System metrics (CPU, Memory, Disk, Uptime)
  - Version information (PHP, Laravel, Application)
  - Real-time status updates
  - Responsive design testing

### Improved

- Browser test coverage expanded from 2063 to 2260 tests (55 test files)
- PHPStan Level 8 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)

---

## [5.17.0] - 2025-12-06

### Added

- **MobileDesignTest.php** - 55 browser tests for mobile responsive design
  - Mobile navigation (hamburger menu, sidebar toggle)
  - Mobile layouts (stacked inputs, full-width containers)
  - Touch-friendly controls (44x44px minimum targets)
  - Mobile forms, tables, and modals
  - Portrait and landscape orientation tests
  - iPhone SE viewport (375x812)

- **TabletDesignTest.php** - 55 browser tests for tablet responsive design
  - Multi-device viewport testing (iPad, Surface Pro, Galaxy Tab)
  - Two-column and three-column layouts
  - Tablet navigation patterns
  - Split-view compatibility
  - Portrait and landscape orientations
  - Touch-friendly tablet interactions

- **LogSourceManagerTest.php** - 50 browser tests for log source management
  - Log source creation and configuration
  - Source types (file, Docker, journald)
  - Predefined templates (Laravel, Nginx, MySQL)
  - Source status management
  - Connection testing and validation

- **PipelineRunHistoryTest.php** - 48 browser tests for pipeline run history
  - Run status indicators and tracking
  - Stage breakdown and logs viewing
  - Filtering by status and date range
  - Re-run and cancel operations
  - Artifacts, metrics, and analytics

### Fixed

- Code style issues in 4 new test files (Pint compliance)

### Improved

- Browser test coverage expanded from 1905 to 2063 tests (51 test files)
- Added comprehensive mobile responsive design testing
- Added comprehensive tablet responsive design testing
- PHPStan Level 8 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)

---

## [5.16.0] - 2025-12-06

### Added

- **ScheduledDeploymentsTest.php** - 50 browser tests for scheduled deployments
  - Schedule modal and form fields
  - Branch, date, time, and timezone selection
  - Pre-deployment notifications configuration
  - Status management (pending, running, completed, cancelled)
  - Cancel scheduled deployments

- **DeploymentCommentsTest.php** - 48 browser tests for deployment comments
  - Adding, editing, and deleting comments
  - User mentions (@username) and highlighting
  - Markdown formatting support
  - Comment ordering and timestamps
  - Permission checks (edit own comments only)

- **FileBackupManagerTest.php** - 50 browser tests for file backup manager
  - Full and incremental file backups
  - Storage destination selection (local, S3, GCS, Azure)
  - Exclude patterns management
  - Backup restoration and download
  - Verification and logs viewing

- **DatabaseBackupManagerTest.php** - 50 browser tests for database backup manager
  - Manual and scheduled database backups
  - MySQL, PostgreSQL, SQLite support
  - Backup frequency and retention configuration
  - Restore and verify functionality
  - Backup statistics and monitoring

### Fixed

- Code style issues in DeploymentCommentsTest.php and ScheduledDeploymentsTest.php
- Code style issues in DeploymentApprovalsTest.php and DeploymentRollbackTest.php

### Improved

- Browser test coverage expanded from 1707 to 1905 tests (47 test files)
- PHPStan Level 8 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)

---

## [5.15.0] - 2025-12-06

### Added

- **ServerBackupsTest.php** - 50 browser tests for server backups
  - Backup creation (full, incremental)
  - Backup scheduling and storage locations
  - Backup restoration and download
  - Encryption, retention, and verification
  - Remote destinations (S3, GCS, Azure)

- **DeploymentRollbackTest.php** - 48 browser tests for deployment rollback
  - Rollback target selection and confirmation
  - Progress tracking and history
  - Automatic rollback on failure
  - Database and file rollback options
  - Dry-run preview and emergency rollback

- **DeploymentApprovalsTest.php** - 48 browser tests for deployment approvals
  - Approval request creation and workflow
  - Approve/reject actions with comments
  - Multi-level approval chains
  - Approval expiration and bypass
  - Email approval links and audit trail

- **ServerTagsTest.php** - 50 browser tests for server tags
  - Tag creation with color selection
  - Tag assignment and removal
  - Bulk tag operations
  - Tag filtering and search
  - Tag statistics and permissions

### Fixed

- Code style issues in AuditLogsTest.php and ServerMetricsTest.php

### Improved

- Browser test coverage expanded from 1505 to 1707 tests (43 test files)
- PHPStan Level 8 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)

---

## [5.14.0] - 2025-12-06

### Added

- **ServerMetricsTest.php** - 50 browser tests for server metrics
  - CPU, memory, and disk usage charts
  - Network traffic monitoring
  - Real-time and historical metrics viewing
  - Metrics time range selection and export
  - Server load averages and uptime statistics

- **ResourceAlertsTest.php** - 46 browser tests for resource alerts
  - Alert rule creation and threshold configuration
  - CPU, memory, disk, and load alerts
  - Notification channel configuration (Email, Slack, Discord)
  - Alert history, acknowledgement, and resolution
  - Cooldown periods and escalation rules

- **AuditLogsTest.php** - 45 browser tests for audit logs
  - Audit log listing and pagination
  - Filtering by user, action, date range
  - Search and export functionality (CSV, JSON)
  - User, server, project, deployment activity logging
  - Security event and API access logging

- **ProjectWebhooksTest.php** - 45 browser tests for project webhooks
  - Webhook creation and configuration
  - Secret management and event selection
  - Delivery history and retry functionality
  - GitHub, GitLab, Bitbucket integration
  - Payload inspection and testing

### Fixed

- Code style issue in SSHTerminalTest.php (unary_operator_spaces, unused imports)

### Improved

- Browser test coverage expanded from 1319 to 1505 tests (39 test files)
- PHPStan Level 8 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)

---

## [5.13.0] - 2025-12-06

### Added

- **ServerProvisioningTest.php** - 48 browser tests for server provisioning
  - Provisioning wizard and page access
  - Software installation (PHP, MySQL, Nginx, Redis, Node.js)
  - Provisioning progress tracking and logs
  - Server configuration templates
  - SSL and DNS configuration during provisioning

- **SSHTerminalTest.php** - 43 browser tests for SSH terminal
  - Terminal access and connection status
  - Command input and output display
  - Terminal history and quick commands
  - Terminal customization and themes
  - Session management

- **KubernetesTest.php** - 51 browser tests for Kubernetes management
  - Cluster creation and configuration
  - Namespace, pod, and deployment management
  - Service, ConfigMap, and Secret management
  - Cluster monitoring and scaling
  - Helm chart and kubectl integration

- **UserSettingsTest.php** - 50 browser tests for user settings
  - Profile information editing
  - Password and two-factor authentication
  - API token and SSH key management
  - Notification and theme preferences
  - Session and activity management

### Fixed

- Code style issue in TeamsTest.php (unary_operator_spaces)

### Improved

- Browser test coverage expanded from 1157 to 1319 tests (35 test files)
- PHPStan Level 8 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)

---

## [5.12.0] - 2025-12-06

### Added

- **DockerTest.php** - 50 browser tests for Docker management
  - Docker dashboard and overview
  - Container operations (start, stop, restart, logs)
  - Container resource monitoring (CPU, memory)
  - Docker compose operations
  - Volume and network management
  - Image management and registry configuration

- **NotificationsTest.php** - 50 browser tests for notifications
  - Notification channel management (Slack, Discord, Email, Webhook)
  - Notification events configuration
  - Notification logs and history
  - Real-time notification indicators
  - User notification preferences

- **TeamsTest.php** - 50 browser tests for teams management
  - Team listing, creation, and settings
  - Team member management and invitations
  - Team role and permissions configuration
  - Team switching functionality
  - Team resource access control

- **SecurityTest.php** - 50 browser tests for server security
  - Security dashboard and metrics
  - Firewall rule management
  - Fail2ban configuration
  - SSH security settings
  - Security scanning and vulnerability management
  - IP whitelist/blacklist management

### Improved

- Browser test coverage expanded from 1057 to 1157 tests (32 test files)
- PHPStan Level 8 compliance maintained (0 errors)
- Laravel Pint code style compliance maintained (0 issues)

---

## [5.11.0] - 2025-12-06

### Added

- **HealthChecksTest.php** - 50 browser tests for health checks
  - Health check dashboard and manager pages
  - Health check types (HTTP, TCP, DNS, SSL)
  - Configuration (intervals, timeouts, response validation)
  - Notifications and alerting rules
  - History, results, and uptime statistics

- **SSLTest.php** - 50 browser tests for SSL certificate management
  - SSL certificate listing and details
  - Certificate creation (Let's Encrypt, custom)
  - Certificate renewal and auto-renewal
  - Expiry monitoring and notifications
  - Certificate chain validation and history

- **AdminTest.php** - 50 browser tests for admin/system features
  - User management (CRUD, roles, permissions)
  - System status and health indicators
  - Audit log viewing and filtering
  - Cache and queue management
  - Backup and optimization operations

- **DomainsTest.php** - 50 browser tests for domain management
  - Domain listing and CRUD operations
  - DNS configuration and verification
  - SSL association and management
  - Subdomain and redirect support
  - Bulk operations and filtering

### Fixed

- Code style issues fixed with Laravel Pint (18 files)
  - unary_operator_spaces, single_quote, concat_space
  - not_operator_with_successor_space, no_unused_imports

### Improved

- Browser test coverage expanded from 982 to 1057 tests (32 test files)
- PHPStan Level 8 compliance maintained (0 errors)
- Comprehensive test coverage for all major features

---

## [5.10.0] - 2025-12-06

### Added

- **LogsAndMonitoringTest.php** - 50 browser tests for logs & monitoring
  - Log viewer page access and navigation
  - Log filtering by level (error, warning, info, debug)
  - Log search and export functionality
  - Log source management
  - Real-time log streaming
  - Notification, webhook, and security audit logs

- **AnalyticsTest.php** - 55 browser tests for analytics features
  - Analytics dashboard access
  - Deployment statistics and charts
  - Server performance metrics
  - Project activity graphs
  - Resource usage analytics
  - Trend analysis and date range filtering

- **QueueMonitorTest.php** - 40 browser tests for queue monitoring
  - Queue monitor dashboard access
  - Failed jobs management and retry
  - Queue statistics display
  - Worker status monitoring
  - Job payload viewing
  - Batch job management

### Fixed

- **PHPStan Level 8 Compliance** - 109 errors fixed across 80+ files
  - Null parameter type handling (21 errors)
  - Null method calls on optional objects (45 errors)
  - Null property access (32 errors)
  - Return type mismatches (11 errors)
  - Console commands, controllers, Livewire components, models, services, notifications

- **PHPUnit Test Fixes** - Critical parse errors and factory issues resolved
  - Fixed Dashboard.php syntax error (null-safe operator in string interpolation)
  - Fixed ResourceAlertManager.php syntax error (if statement in array)
  - Fixed ProjectFactory enum mismatches (project_type, status)
  - Fixed BackupScheduleFactory column names
  - Created missing DatabaseBackupFactory

### Improved

- Browser test coverage expanded from 897 to 982 tests (32 test files)
- PHPStan compliance upgraded from Level 7 to Level 8
- Stricter null handling throughout entire codebase

---

## [5.9.0] - 2025-12-06

### Added

- **ScriptsTest.php** - 50 browser tests for deployment scripts
  - Script creation, editing, and cloning
  - Script templates (deployment, backup, maintenance)
  - Script execution on servers
  - Script scheduling and automation
  - Script logs and output viewing

- **BackupsTest.php** - 52 browser tests for backup management
  - Database backup creation and restoration
  - File backup (full and incremental)
  - Backup scheduling (hourly, daily, weekly, monthly)
  - Multiple storage locations (local, S3, GCS, Azure)
  - Backup encryption and retention policies

- **ApiDocumentationTest.php** - 35 browser tests for API documentation
  - API endpoint documentation display
  - API token management
  - Request/response examples
  - Webhook documentation
  - API versioning information

### Fixed

- **Database Schema Consistency** - Fixed `env_variables` vs `environment_variables` inconsistency
  - ProjectFactory.php - Removed duplicate field
  - KubernetesService.php - Fixed 2 references
  - DeploymentScriptService.php - Fixed 1 reference
  - ProjectManagementTest.php - Fixed test assertions
  - Created migration for `pipeline_stages.environment_variables` column

- **PHPStan Level 7 Compliance** - 42 errors fixed across 18 files
  - Console commands: ProvisionServer, RenewSSL, RunQualityTests
  - Controllers: GitHubAuthController
  - Livewire: PipelineSettings, ServerMetricsDashboard, SSHKeyManager
  - Models: BackupSchedule, PipelineConfig, Server, ServerBackupSchedule, UserSettings
  - Services: DatabaseBackupService, SSHKeyService, FirewallService, SecurityScoreService, ServerProvisioningService
  - Notifications: ServerProvisioningCompleted

### Improved

- Browser test coverage expanded from 769 to 897 tests (31 test files)
- PHPStan compliance upgraded from Level 6 to Level 7
- Union type handling and stricter type checks throughout codebase

---

## [5.8.0] - 2025-12-05

### Added

- **EnvironmentsTest.php** - 50 browser tests for environment management
  - Environment variables CRUD operations
  - Environment cloning and switching
  - Secrets management
  - Deployment settings per environment

- **PipelinesTest.php** - 45 browser tests for CI/CD pipelines
  - Pipeline creation and configuration
  - Stage management and execution
  - Approval workflows
  - Notifications and rollback

- **TenantsTest.php** - 43 browser tests for multi-tenant management
  - Tenant CRUD operations
  - Database management and migrations
  - Deployment management
  - Billing and subscription

- **GitHubIntegrationTest.php** - 35 browser tests for GitHub integration
  - OAuth flow and connection management
  - Repository listing and import
  - Webhook configuration
  - Branch and commit history

### Fixed

- Code style compliance with Laravel Pint (308 files fixed)

### Improved

- Browser test coverage expanded from 596 to 769 tests (24 test files)
- Test coverage now includes: Environments, Pipelines, Tenants, GitHub Integration

---

## [5.7.0] - 2025-12-05

### Added

- **DomainsTest.php** - 30 comprehensive browser tests for domain management
  - Domain creation, editing, and deletion
  - Domain verification and DNS configuration
  - Domain SSL integration testing
  - Primary domain management
  - Domain bulk operations

- **WebhooksTest.php** - 35 browser tests for webhook functionality
  - Webhook creation and configuration
  - Webhook event types and filtering
  - Webhook secret management and security
  - Webhook delivery monitoring
  - Webhook retry functionality

- **StorageTest.php** - 35 browser tests for storage configuration
  - Storage driver configuration (local, S3, etc.)
  - Storage connection testing
  - Storage analytics and monitoring
  - Storage cleanup operations
  - Multi-storage management

### Fixed

- **PHPStan Level 6 Compliance** - Complete PHPStan Level 6 Compliance Achieved (0 errors!)
  - Fixed 146+ type errors across the entire codebase
  - All Eloquent models now have proper HasFactory generics
  - All relationship methods have correct BelongsTo/HasMany/HasOne generics
  - All Livewire components have proper property types and PHPDoc annotations
  - All Service classes have proper return types and array annotations

- **PHPStan Level 6 Compliance** - Eloquent Models Fixed (40+ models)
  - `Team`, `ApiToken`, `BackupSchedule`, `Deployment`, `DeploymentScript`
  - `Domain`, `PipelineConfig`, `FileBackup`, `FirewallRule`, `HealthCheckResult`
  - `NotificationLog`, `NotificationChannel`, `Project`, `SSLCertificate`, `Server`
  - `ServerMetric`, `User`, `WebhookDelivery`, `ProjectAnalytic`, `ProjectSetupTask`
  - `ProvisioningLog`, `ResourceAlert`, `SSHKey`, `ServerBackupSchedule`
  - `ServerTag`, `SshConfiguration`, `StorageConfiguration`, `Tenant`, `UserSettings`
  - `AuditLog`, `DeploymentComment`, `GitHubConnection`, `PipelineStage`

- **PHPStan Level 6 Compliance** - Livewire Components Fixed (50+ components)
  - Dashboard and Health Dashboard components
  - All Deployments components (DeploymentList, ScheduledDeployments)
  - All Projects components (ProjectList, ProjectShow, ProjectConfiguration, etc.)
  - All Servers components (ServerShow, SSHTerminal, ServerBackupManager, etc.)
  - All Settings components (QueueMonitor, StorageSettings, SystemStatus, etc.)
  - All Teams/Users components
  - All Logs components (LogViewer, NotificationLogs, SecurityAuditLog, etc.)
  - SSL Manager with confirmation modals

- **PHPStan Level 6 Compliance** - Services Fixed (15+ services)
  - `LogAggregationService`, `SSLManagementService`, `ServerSecurityService`
  - `ServerMetricsService`, `MultiTenantService`, `AlertNotificationService`
  - `PipelineExecutionService`, `PipelineService`, `DeploymentScriptService`
  - `DeploymentApprovalService`, `FileBackupService`, `GPSService`
  - `DatabaseBackupService`, `AuditService`, `BulkServerActionService`

- **PHPStan Level 6 Compliance** - Console Commands Fixed
  - `CleanupBackups` - Fixed Collection parameter type
  - `RunQualityTests` - Fixed parameter types

- **PHPStan Level 6 Compliance** - Notifications Fixed
  - `UserMentionedInComment`, `DeploymentApprovalRequested`
  - `SSLCertificateExpiring`, `SSLCertificateRenewed`, `ServerProvisioningCompleted`

### Improved

- **Code Quality** - PHPStan Level 6 Complete Compliance
  - Total errors reduced from 146 to 0 (100% reduction!)
  - All models have proper generic type annotations
  - All relationships properly typed with `$this` parameter
  - All array properties have value type specifications
  - All Collection return types have proper generics

- **Test Coverage** - Expanded browser test suite
  - Total browser test coverage now at 596 tests (20 test files)
  - Comprehensive domain management testing
  - Full webhook functionality test coverage
  - Storage configuration thoroughly tested

---

## [5.6.0] - 2025-12-05

### Added

- **SSLTest.php** - 30 comprehensive browser tests for SSL certificate management
  - SSL certificate creation and renewal
  - SSL certificate validation and verification
  - SSL certificate monitoring and alerts
  - SSL certificate auto-renewal configuration
  - SSL certificate domain management

- **HealthChecksTest.php** - 35 browser tests for health check functionality
  - Health check dashboard and monitoring
  - Health check configuration management
  - Health check execution and reporting
  - Health check alerting and notifications
  - Health check history and analytics

- **ScriptsTest.php** - 30 browser tests for scripts/automation
  - Script creation and management
  - Script execution and scheduling
  - Script output and logging
  - Script configuration and parameters
  - Script error handling and recovery

### Fixed

- **PHPStan Level 6 Compliance** - Eloquent models now pass Level 6
  - `TenantDeployment` - Enhanced multi-tenant deployment type safety
  - `TeamInvitation` - Fixed team invitation type declarations
  - `SecurityScan` - Added security scanning type hints
  - `SecurityEvent` - Enhanced security event type safety
  - `PipelineStageRun` - Fixed pipeline execution types
  - `Pipeline` - Added CI/CD pipeline type declarations
  - `KubernetesCluster` - Enhanced K8s cluster type safety
  - `GitHubRepository` - Fixed repository integration types

- **PHPStan Level 6 Compliance** - Services now pass Level 6
  - `SlackDiscordNotificationService` - Fixed notification service type declarations

### Improved

- **Code Quality** - Further reduced PHPStan errors
  - Total errors reduced from ~172 to ~140 (19% reduction)
  - Level 6 compliance for security and deployment models
  - Enhanced type safety across critical infrastructure components
  - Better error detection for multi-tenant operations

- **Test Coverage** - Expanded browser test suite significantly
  - Total browser test coverage now at 496 tests (17 test files)
  - Comprehensive SSL certificate management testing
  - Full health check functionality test coverage
  - Scripts and automation thoroughly tested

---

## [5.5.0] - 2025-12-05

### Added

- **ProjectsTest.php** - 35 comprehensive browser tests for project management
  - Project CRUD operations and validation
  - Project configuration management
  - Project deployment workflows
  - Project status monitoring
  - Project environment management

- **BackupsTest.php** - 29 browser tests for backup functionality
  - Backup creation and scheduling
  - Backup restoration operations
  - Backup history and management
  - Backup configuration testing
  - Backup validation and verification

- **NotificationsTest.php** - 35 browser tests for notification management
  - Notification dashboard functionality
  - Notification preferences configuration
  - Notification delivery testing
  - Notification filtering and search
  - Notification channels management

### Fixed

- **PHPStan Level 6 Compliance** - Livewire components now pass Level 6
  - `DeploymentShow` - Enhanced deployment display types and properties

- **PHPStan Level 6 Compliance** - Eloquent models now pass Level 6
  - `DeploymentApproval` - Added approval workflow type declarations
  - `DeploymentScriptRun` - Fixed script execution type safety
  - `ResourceAlert` - Enhanced resource monitoring types
  - `ServerBackup` - Added backup metadata type hints
  - `TeamMember` - Fixed team relationship types

- **PHPStan Level 6 Compliance** - Services now pass Level 6
  - `AuditService` - Added comprehensive audit logging types
  - `BulkServerActionService` - Fixed bulk operation type safety
  - `DatabaseBackupService` - Enhanced backup service type declarations

### Improved

- **Code Quality** - Reduced PHPStan errors significantly
  - Total errors reduced from ~217 to ~180 (17% reduction)
  - Level 6 compliance for deployment components
  - Enhanced type safety across service layer
  - Better error detection during development

- **Test Coverage** - Expanded browser test suite
  - Total browser test coverage now at 301 tests (14 test files)
  - Comprehensive project management testing
  - Full backup functionality test coverage
  - Notification system thoroughly tested

---

## [5.4.0] - 2025-12-05

### Added

- **DeploymentsTest.php** - 30 comprehensive browser tests for deployment management
  - Deployment dashboard navigation and functionality
  - Deployment creation and execution
  - Deployment rollback operations
  - Deployment history and logs viewing
  - Deployment status monitoring
  - Deployment configuration management

- **ServersTest.php** - 40 browser tests for server management
  - Server CRUD operations
  - Server connection testing
  - Server metrics and monitoring
  - Server configuration management
  - SSH key management
  - Server health checks

- **AnalyticsTest.php** - 35 browser tests for analytics dashboard
  - Analytics dashboard navigation
  - Metrics visualization and charts
  - Data filtering and date ranges
  - Export functionality
  - Performance analytics
  - Resource usage tracking

### Fixed

- **PHPStan Level 6 Compliance** - Auth components now pass Level 6
  - `Login` - Added strict types and proper form validation
  - `Register` - Fixed property types and registration flow
  - `ForgotPassword` - Enhanced type safety for password reset

- **PHPStan Level 6 Compliance** - Livewire components now pass Level 6
  - `ServerList` - Fixed collection type hints and return types
  - `SSHKeyManager` - Added proper SSH key type declarations
  - `ApiTokenManager` - Enhanced token management types
  - `ServerMetricsDashboard` - Fixed metrics array shapes
  - `AnalyticsDashboard` - Added proper analytics data types

- **PHPStan Level 6 Compliance** - Eloquent models now pass Level 6
  - `Tenant` - Added relationship return types and tenant data
  - `ProjectTemplate` - Fixed template configuration types
  - `PipelineRun` - Enhanced pipeline execution types
  - `LogSource` - Added log data type declarations
  - `AlertHistory` - Fixed alert tracking types

### Improved

- **Code Quality** - Reduced PHPStan errors significantly
  - Total errors reduced from ~270 to ~220 (18% reduction)
  - Level 6 compliance for authentication components
  - Enhanced type safety across core Livewire components
  - Better error detection during development

- **Test Coverage** - Expanded browser test suite
  - Total browser test coverage now at 197 tests (8 test files)
  - Comprehensive deployment workflow testing
  - Full server management test coverage
  - Analytics dashboard thoroughly tested

---

## [5.3.0] - 2025-12-05

### Added

- **DockerTest.php** - 25 comprehensive browser tests for Docker management
  - Docker dashboard navigation and functionality
  - Container lifecycle operations (start, stop, restart)
  - Container log viewing and refresh
  - Image management (build, pull, delete)
  - Volume and network operations
  - Container stats and metrics
  - Resource cleanup and pruning

- **KubernetesTest.php** - 30 browser tests for Kubernetes cluster management
  - Cluster management dashboard
  - Pod lifecycle operations
  - Service and deployment management
  - ConfigMap and Secret operations
  - Namespace management
  - Cluster health monitoring
  - Resource scaling operations

- **AdminTest.php** - 30 browser tests for user/admin management
  - User CRUD operations
  - Role and permission management
  - Team management functionality
  - API token management
  - System settings configuration
  - Audit log viewing
  - User activity monitoring

### Fixed

- **PHPStan Level 6 Compliance** - 15+ Livewire components now pass Level 6
  - `ProjectDockerManagement` - Added strict types and PHPDoc annotations
  - `UserList` - Fixed collection type hints and return types
  - `SystemAdmin` - Added proper property declarations
  - `DockerDashboard` - Fixed array shape annotations
  - `ServerCreate` - Enhanced validation type hints
  - `ServerEdit` - Fixed property types and nullability
  - `DeploymentRollback` - Added proper type declarations
  - `DeploymentNotifications` - Fixed notification types
  - `ServerTagManager` - Added tag collection types
  - `SystemStatus` - Fixed status check return types

- **PHPStan Level 6 Compliance** - 8+ Eloquent models now pass Level 6
  - `FileBackup` - Added relationship return types
  - `PipelineStage` - Fixed property types and relationships
  - `DatabaseBackup` - Enhanced type safety for backup methods
  - `BackupSchedule` - Added proper datetime type hints
  - `NotificationChannel` - Fixed channel type declarations
  - `Team` - Added team member relationship types
  - `StorageConfiguration` - Fixed config array types
  - `HealthCheck` - Added check result types
  - `ScheduledDeployment` - Fixed scheduling type hints

### Improved

- **Type Safety** - Comprehensive type coverage across the codebase
  - Strict type declarations (`declare(strict_types=1);`) added throughout
  - Generic type hints for collections (e.g., `Collection<int, Project>`)
  - Array shape annotations for complex data structures
  - Proper nullability declarations for optional values
  - Better IDE autocomplete support

- **Code Quality** - Reduced PHPStan errors significantly
  - Total errors reduced from 416 to ~300 (28% reduction)
  - Level 6 compliance for core components
  - Improved maintainability and debugging
  - Better error detection during development
  - Enhanced code documentation

---

## [5.2.0] - 2025-12-05

### Added

- **Browser Tests** - New comprehensive test files for enhanced test coverage
  - `TeamsTest.php` with 20 tests covering team management functionality
    - Team creation, listing, and deletion
    - Team member management
    - Team invitations and acceptance
    - Team switching and role management
  - `SecurityTest.php` with 20 tests for security features
    - SSH key management
    - Security dashboard and scores
    - Firewall configuration
    - Security audit logs
  - `SettingsTest.php` for testing settings pages functionality

### Changed

- **PHPStan Type Declarations** - Enhanced type safety across 8 Livewire components
  - `ProjectCreate.php` - Added proper return types and property declarations
  - `ProjectEdit.php` - Improved type hints for project editing operations
  - `ProjectEnvironment.php` - Type-safe environment variable management
  - `Dashboard.php` - Proper return types for dashboard methods
  - `TenantManager.php` - Type hints for tenant operations
  - `ClusterManager.php` - Enhanced cluster management type safety
  - `ScriptManager.php` - Type-safe script execution
  - `SSLManager.php` - Improved SSL certificate type declarations

### Fixed

- **N+1 Query Optimizations** - Significant performance improvements in 5 components
  - `HealthDashboard.php` - 87% query reduction with eager loading
    - Added `with('server', 'domains')` to project queries
    - Implemented computed property caching for health data
  - `ProjectShow.php` - Added domains eager loading to prevent N+1 queries
  - `SecurityAuditLog.php` - Added computed property caching for log filtering
  - `SSLManager.php` - Optimized certificate queries with proper eager loading
  - `Dashboard.php` - Fixed activity feed N+1 with relationship preloading

### Documentation

- Updated `ROADMAP.md` with complete v5.x release history and progress tracking

---

## [5.1.0] - 2025-12-05

### Added

- **Browser Tests with Dusk** - Comprehensive Dusk browser tests for server management
  - 20 tests covering server list, CRUD operations, and quick actions
  - UI-based login trait for testing against production server
  - Session driver configuration for Dusk environment

### Security

- **Public Page Security Hardening** - Removed infrastructure exposure from public pages
  - Removed IP address fallback from project URLs
  - HTTPS-only URL construction
  - No server names or ports displayed publicly
  - Projects without domains are filtered from public views

### Changed

- **NileStack Branding** - Updated branding across the platform
  - NileStack logo in navigation header
  - NileStack attribution in footer
  - Updated meta tags (og:site_name, author)
  - favicon.svg and apple-touch-icon.svg assets

- **Dashboard Enhancements** - Extended dashboard with more metrics
  - SSL Certificates card with expiring_soon warnings
  - Health Checks card with healthy/down counts
  - Queue Jobs card with pending/failed counts
  - Security Score card aggregated from servers
  - Deploy All quick action button
  - Clear All Caches quick action button
  - Activity feed with timeline layout
  - Server health summary with progress bars
  - Collapsible sections with user preferences

- **Design Consistency** - Unified design language
  - Team list page gradient hero header (indigo-purple-pink)
  - Health dashboard gradient header (emerald-teal-cyan)
  - Health check manager gradient header (blue-indigo)
  - Consistent rounded-2xl and shadow-xl styling

### Files Modified

- `app/Livewire/Home/HomePublic.php` - Security: removed IP fallback
- `resources/views/livewire/home/home-public.blade.php` - NileStack branding
- `resources/views/layouts/marketing.blade.php` - Meta tags update
- `app/Livewire/Dashboard.php` - Extended stats and quick actions
- `resources/views/livewire/dashboard.blade.php` - Enhanced UI
- `resources/views/livewire/teams/team-list.blade.php` - Gradient hero
- `resources/views/livewire/dashboard/health-dashboard.blade.php` - Gradient header
- `resources/views/livewire/settings/health-check-manager.blade.php` - Gradient header
- `tests/Browser/ServerManagementTest.php` - 20 browser tests
- `tests/Browser/Traits/LoginViaUI.php` - UI-based login trait

---

## [5.0.2] - 2025-12-04

### Fixed

- **Deploy All Button Not Working** - Dashboard quick action now triggers deployments
  - Added missing `wire:click="deployAll"` handler to the Deploy All button
  - Added `wire:confirm` for user confirmation before mass deployment
  - Fixed project status filter: was checking for 'active' but projects use 'running'
  - Now correctly finds all active/running projects with assigned servers
  - Creates deployment records and dispatches DeployProjectJob for each project

- **Mixed Content Errors (HTTPS)** - All websites now load assets over HTTPS
  - **ATS Pro**: Added `URL::forceScheme('https')` to AppServiceProvider
  - **Portfolio**: Added `URL::forceScheme('https')` and `trustProxies(at: '*')` to bootstrap/app.php
  - **Workspace Pro**: Added `URL::forceScheme('https')` to AppServiceProvider
  - Added `ASSET_URL` environment variable to all apps
  - Fixed CSS/JS assets being blocked by browsers due to Mixed Content policy
  - All three sites now serve assets over HTTPS: ats.nilestack.duckdns.org, nilestack.duckdns.org, workspace.nilestack.duckdns.org

### Files Modified

- `app/Livewire/Dashboard.php` - Added `deployAll()` method with DeployProjectJob dispatch
- `resources/views/livewire/dashboard.blade.php` - Added wire:click and wire:confirm to Deploy All button
- Production: Updated AppServiceProvider.php and bootstrap/app.php for all 3 deployed sites

---

## [5.0.1] - 2025-12-03

### Fixed

- **Cache::tags() Compatibility** - Fixed 500 errors on production
  - Replaced `Cache::tags()` calls with `Cache::remember()` in DeploymentList and DashboardOptimized
  - `Cache::tags()` only works with Redis/Memcached, not database cache driver
  - Production uses database cache driver which doesn't support tagging
  - Simplified caching code by removing unnecessary try/catch fallback patterns

- **Domain Subdomain Column Error** - Fixed missing column reference
  - Removed `subdomain` from domains eager loading in ProjectList and DashboardOptimized
  - Production domains table doesn't have a `subdomain` column
  - Eager loading now only selects existing columns: `id`, `project_id`, `domain`

### Files Modified

- `app/Livewire/Deployments/DeploymentList.php` - Removed Cache::tags()
- `app/Livewire/DashboardOptimized.php` - Removed Cache::tags() and subdomain references
- `app/Livewire/Projects/ProjectList.php` - Removed subdomain from eager loading

---

## [5.0.0] - 2025-12-03

### Added

- **Phase 8: UI/UX Improvements Complete**

  **Theme Management:**
  - Theme toggle component with Light/Dark/System modes
  - Persistent theme preference via localStorage
  - Smooth transitions between themes
  - System preference detection
  - PWA theme-color meta tag updates

  **Keyboard Shortcuts:**
  - Global keyboard shortcuts manager (keyboard-shortcuts.js)
  - Command palette (Cmd/Ctrl+K)
  - Navigation shortcuts (Cmd+D, Cmd+S, Cmd+P, Cmd+E, Cmd+H)
  - Action shortcuts (Cmd+N for new project, Cmd+F for search)
  - Help modal (Cmd+/) showing all available shortcuts
  - ESC key to close modals
  - Cross-platform support (Mac ⌘ / Windows Ctrl)

  **Loading States:**
  - Skeleton loader component with multiple types (stats, card, list, table, text)
  - Shimmer animation effect for realistic loading
  - Dark mode compatible skeleton loaders
  - Configurable count for repeated elements
  - Already integrated in project detail views

  **Empty States:**
  - Empty state component with customizable icons
  - 9 built-in icon variants (inbox, server, folder, document, code, clock, chart, database, search)
  - Primary and secondary action buttons
  - Support for routes and Livewire actions
  - Dark mode styling

  **Toast Notifications:**
  - Enhanced toast system with icons (success, error, warning, info)
  - Progress bar with auto-dismiss
  - Manual close button
  - Slide-in and slide-out animations
  - Livewire event integration
  - Configurable duration
  - Stacking support for multiple toasts

  **CSS Improvements:**
  - New animation classes (fadeIn, slideUp, scaleIn)
  - Hover lift effect for cards
  - Enhanced focus ring styles
  - Custom scrollbar styling for dark mode
  - Smooth transition utilities
  - Toast notification styles with progress indicators

  **Developer Experience:**
  - UI examples component showing all features
  - Comprehensive documentation in component files
  - Reusable blade components
  - Easy integration with Livewire

## [4.0.0] - 2025-12-03

### Added

- **Phase 4: Testing & Quality Complete**

  **Comprehensive Test Suite:**
  - Unit tests for all core services (ServerMetrics, Pipeline, Backup)
  - Livewire component tests (Dashboard, HomePublic)
  - 86+ test cases covering critical paths
  - Test traits: CreatesProjects, CreatesServers, MocksSSH
  - Factory files for rapid test data generation

  **CI/CD for DevFlow Pro:**
  - GitHub Actions workflow (ci.yml) - Multi-PHP version testing
  - Code quality workflow (code-quality.yml) - PHPStan + Laravel Pint
  - Deployment workflow (deploy.yml) - SSH-based automated deployment
  - Scheduled workflow (scheduled.yml) - Security audits + dependency checks
  - Release workflow (release.yml) - Automated releases with notes

- **Phase 5: Advanced Features Complete**

  **Server Provisioning:**
  - ServerProvisioningService with SSH-based automation
  - LEMP stack auto-installation (Nginx, MySQL, PHP)
  - UFW firewall configuration with common ports
  - Swap file setup for memory optimization
  - Provisioning script download feature

  **SSL Auto-Management:**
  - SSLManagementService with Let's Encrypt integration
  - Auto-issue certificates via Certbot
  - Expiry monitoring with alerts (30/14/7 days)
  - Auto-renewal with nginx reload
  - SSL status tracking per domain

  **Team Collaboration:**
  - DeploymentApproval model with required approvers
  - DeploymentComment model for team discussions
  - AuditLog model for comprehensive action tracking
  - Slack notifications with Block Kit formatting
  - Discord notifications with rich embeds

### Technical

- **New Models:** DeploymentApproval, DeploymentComment, AuditLog, ProvisioningLog
- **New Services:** ServerProvisioningService, SSLManagementService, DeploymentApprovalService, AuditService
- **New Components:** ServerProvisioning, DeploymentApprovals, AuditLogViewer
- **GitHub Actions:** 5 workflow files for complete CI/CD
- **Tests:** 86+ tests in Unit/Services and Feature/Livewire

### v4.0 Roadmap Complete

All phases of the v4.0 roadmap are now complete:
- ✅ Phase 1: Real-time Server Metrics
- ✅ Phase 2: CI/CD Pipeline Implementation
- ✅ Phase 3: Automated Backup System
- ✅ Phase 4: Testing & Quality
- ✅ Phase 5: Advanced Features

---

## [3.17.0] - 2025-12-03

### Added

- **Automated Backup System** - Complete backup infrastructure with remote storage

  **Database Backup Management:**
  - Scheduled backups via mysqldump over SSH
  - Configurable retention policies (daily: 7, weekly: 4, monthly: 3)
  - One-click restore with progress tracking
  - SHA-256 checksum verification for integrity
  - Backup metadata tracking (tables, size, duration)

  **File Backup System:**
  - Full backups with tar.gz compression
  - Incremental backups (only changed files)
  - Configurable exclude patterns per project
  - Manifest generation for backup contents
  - Parent-child backup chain tracking

  **Remote Storage Integration:**
  - Amazon S3 support (including DigitalOcean Spaces, MinIO)
  - Google Cloud Storage support
  - FTP/SFTP support with key or password auth
  - AES-256-GCM encryption at rest
  - Connection testing before save
  - Streaming uploads for large files

### Technical

- **New Models:** DatabaseBackup, FileBackup, StorageConfiguration, BackupSchedule
- **New Services:** DatabaseBackupService, FileBackupService, RemoteStorageService
- **New Components:** DatabaseBackupManager, FileBackupManager, StorageSettings
- **Artisan Commands:** backup:database, backup:files, backup:cleanup, backup:verify

### Routes

- `GET /projects/{project}/backups/database` - Database backup manager
- `GET /projects/{project}/backups/files` - File backup manager
- `GET /settings/storage` - Remote storage configuration

### Phase 3 Complete

This release completes Phase 3 (Automated Backup System) of the v4.0 roadmap:
- ✅ Database backup management with scheduling
- ✅ File backup system with incremental support
- ✅ Remote storage integration (S3, GCS, FTP, SFTP)
- ✅ Encryption at rest

---

## [3.16.0] - 2025-12-03

### Added

- **CI/CD Pipeline System** - Complete pipeline management for automated deployments

  **Webhook Integration:**
  - GitHub webhook handler with HMAC-SHA256 signature validation
  - GitLab webhook handler with token validation
  - Branch-based deployment rules (auto_deploy_branches)
  - Commit message patterns: `[skip ci]`, `[deploy]`, `WIP`, `HOTFIX`
  - PipelineConfig model for per-project configuration

  **Pipeline Builder UI:**
  - Visual drag-and-drop stage editor using SortableJS
  - Three-column layout: Pre-Deploy | Deploy | Post-Deploy
  - Stage cards with enable/disable toggles
  - Template system (Laravel, Node.js, Static Site)
  - Command editor with multi-line support
  - Environment variables per stage
  - Timeout configuration (10-3600 seconds)

  **Pipeline Execution Engine:**
  - PipelineExecutionService for orchestrating deployments
  - Sequential stage execution (pre_deploy → deploy → post_deploy)
  - Real-time output streaming via WebSocket
  - Stage status tracking: pending, running, success, failed, skipped
  - Continue on failure option per stage
  - Automatic rollback for failed deployments

  **Pipeline Run Views:**
  - Run history with status filtering
  - Detailed run view with expandable stages
  - Terminal-style output display
  - Cancel running pipelines
  - Retry failed pipelines
  - Download stage outputs

### Technical

- **New Models:** PipelineConfig, PipelineStage, PipelineStageRun
- **New Service:** PipelineExecutionService
- **New Event:** PipelineStageUpdated (broadcasts on `pipeline.{runId}`)
- **New Components:** PipelineSettings, PipelineBuilder, PipelineRunHistory, PipelineRunShow
- **Database Migrations:** pipeline_configs, pipeline_stages, pipeline_stage_runs tables

### Routes

- `GET /projects/{project}/pipeline` - Pipeline settings & builder
- `GET /projects/{project}/pipeline/runs` - Pipeline run history
- `GET /pipelines/runs/{run}` - Pipeline run details
- `POST /webhooks/github/{project}` - GitHub webhook endpoint
- `POST /webhooks/gitlab/{project}` - GitLab webhook endpoint

### Phase 2 Complete

This release completes Phase 2 (CI/CD Pipeline Implementation) of the v4.0 roadmap:
- ✅ GitHub/GitLab webhook integration
- ✅ Branch-based rules and commit message parsing
- ✅ Visual pipeline builder with drag-and-drop
- ✅ Pipeline execution engine with stage tracking
- ✅ Rollback on failure

---

## [3.15.0] - 2025-12-03

### Added

- **Process List Viewer** - Real-time top processes on server metrics dashboard
  - **CPU Tab** - Top 10 processes sorted by CPU usage
  - **Memory Tab** - Top 10 processes sorted by memory usage
  - **Auto-refresh** - Updates every 30 seconds when live mode enabled
  - **Color-coded metrics** - Green (<20%), yellow (<50%), red (≥50%) for CPU
  - **Full command tooltips** - Hover to see truncated commands in full

- **Live Deployment Logs** - Real-time log streaming via WebSocket
  - **Terminal-style viewer** - Dark theme with monospace font
  - **Color-coded log levels**
    - Error: red (`text-red-400`)
    - Warning: yellow (`text-yellow-400`)
    - Info: gray (`text-gray-300`)
  - **Line numbers** - Left column with sequential numbering
  - **Auto-scroll** - Automatically follows new log entries
  - **Pause/Resume** - Control buttons for auto-scroll behavior
  - **Live Streaming indicator** - Shows when deployment is in progress

### Technical

- **DeploymentLogUpdated Broadcast Event** - Real-time logs via WebSocket
  - Broadcasts on `deployment-logs.{deploymentId}` channel
  - Includes: deployment_id, line, level (info/warning/error), timestamp
  - Pattern detection for error/warning keywords
- **ServerMetricsService** - New methods for process listing
  - `getTopProcessesByCPU()` - Fetches via `ps aux --sort=-%cpu`
  - `getTopProcessesByMemory()` - Fetches via `ps aux --sort=-%mem`
  - `parseProcessOutput()` - Parses ps output into structured data
- **DeployProjectJob** - Updated to broadcast log lines during deployment

### Phase 1 Complete

This release completes Phase 1 (Real-time Server Metrics) of the v4.0 roadmap:
- ✅ Live monitoring dashboard with Chart.js
- ✅ Process list viewer
- ✅ Deployment logs streaming
- ✅ Color-coded log levels
- ✅ Auto-scroll with pause

---

## [3.14.0] - 2025-12-03

### Added

- **Real-time Server Metrics Dashboard** - Live monitoring with Chart.js
  - **CPU & Memory Trend Chart** - Line chart showing historical data
  - **Disk & Load Average Chart** - Dual-axis chart for storage and system load
  - **Live Progress Bars** - Animated bars for current CPU/Memory/Disk usage
  - **Alert System** - Critical (red) and warning (yellow) alerts for thresholds
    - CPU > 90% = critical, > 80% = warning
    - Memory > 85% = critical, > 75% = warning
    - Disk > 90% = critical, > 80% = warning
  - **Time Range Selector** - View metrics for 1h, 6h, 24h, or 7 days
  - **Live Updates Indicator** - Pulsing green dot showing WebSocket connection

### Technical

- **ServerMetricsUpdated Broadcast Event** - Real-time metrics via WebSocket
  - Broadcasts on `server-metrics.{serverId}` channel
  - Includes CPU, Memory, Disk, Load, Network data
  - Alert threshold detection in event payload
- **Chart.js Integration** - Added to frontend bundle (~208KB gzipped)
- **Updated Scheduler** - Metrics collection now runs every minute with `--broadcast` flag
- **Database Migration** - Added missing columns to server_metrics table

### Routes

- `GET /servers/{server}/metrics` - Real-time metrics dashboard

---

## [3.13.0] - 2025-12-03

### Changed

- **Navigation Redesign** - Switched from border-bottom to pill-style buttons
  - Consistent styling across all nav items including dropdowns
  - Improved hover states and transitions
  - Renamed "Advanced" dropdown to "More"
  - Added section headers in dropdown menus

### Removed

- **Project Portfolio** - Removed from public home page for cleaner landing

### Added

- **Platform Status Display** - Home page now shows operational status
- **Server Helper Commands** - SSH aliases for common operations
  - `status`, `logs`, `deploy`, `restart`, `clear-cache`, `disk`, `mem`, `ports`
- **tmux Configuration** - Custom setup with Ctrl+a prefix and NileStack branding

---

## [3.12.0] - 2025-12-03

### Added

- **Management UI Pages** - New admin interfaces for backend features
  - **System Status Dashboard** (`/settings/system-status`)
    - WebSocket (Reverb) server status and connectivity
    - Queue worker statistics (pending, failed, processed)
    - Cache health check
    - Database connectivity test
    - "Test Broadcast" button for WebSocket verification
  - **Notification Logs Viewer** (`/logs/notifications`)
    - Browse all notification deliveries
    - Filter by status (success/failed/pending), channel, event type
    - View notification details in modal
    - Statistics: total, success, failed, pending counts
  - **Webhook Logs Viewer** (`/logs/webhooks`)
    - View GitHub/GitLab webhook deliveries
    - Filter by provider, project, status, event type
    - View payload and response data
    - Statistics: total, success, failed, ignored counts
  - **Security Audit Log** (`/logs/security`)
    - View firewall changes, IP bans, SSH config changes
    - Filter by server, event type
    - View detailed event metadata
    - Statistics: total, today, firewall events, IP bans

### Technical

- **New Livewire Components:**
  - `App\Livewire\Settings\SystemStatus`
  - `App\Livewire\Logs\NotificationLogs`
  - `App\Livewire\Logs\WebhookLogs`
  - `App\Livewire\Logs\SecurityAuditLog`

- **New Routes:**
  - `GET /settings/system-status` - System status dashboard
  - `GET /logs/notifications` - Notification delivery logs
  - `GET /logs/webhooks` - Webhook delivery logs
  - `GET /logs/security` - Security audit log

### Improved

- Enhanced observability into system operations
- Better debugging capabilities for webhook and notification failures
- Security event tracking for audit compliance

---

## [3.11.0] - 2025-12-03

### Added

- **Real-Time WebSocket Updates** - Live dashboard updates without polling
  - Installed Laravel Reverb v1.6.3 as self-hosted WebSocket server
  - Created broadcast events for deployment lifecycle:
    - `DeploymentStarted` - Fired when a deployment begins
    - `DeploymentCompleted` - Fired when a deployment succeeds
    - `DeploymentFailed` - Fired when a deployment fails
    - `DashboardUpdated` - General dashboard update event
  - Frontend listens via Laravel Echo on public 'dashboard' channel
  - Toast notifications for real-time deployment status updates

### Infrastructure

- **Laravel Reverb WebSocket Server**
  - Runs on port 8080, proxied via nginx at `/app` endpoint
  - Supervisor managed process for automatic restart
  - Uses Pusher protocol for client compatibility
  - Configured for wss:// (secure WebSocket) via nginx proxy

### Technical

- **New Files:**
  - `config/reverb.php` - Reverb server configuration
  - `app/Events/DeploymentStarted.php` - Broadcast event
  - `app/Events/DeploymentCompleted.php` - Broadcast event
  - `app/Events/DeploymentFailed.php` - Broadcast event
  - `app/Events/DashboardUpdated.php` - Broadcast event

- **Modified Files:**
  - `resources/js/bootstrap.js` - Laravel Echo with Reverb configuration
  - `resources/js/app.js` - WebSocket event listeners and toast notifications
  - `composer.json` - Added laravel/reverb dependency

- **Production Configuration:**
  - Nginx location block for `/app` WebSocket proxy
  - Supervisor config at `/etc/supervisor/conf.d/reverb.conf`
  - Environment variables: REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET

### Commands

```bash
# Start Reverb WebSocket server
php artisan reverb:start --host=0.0.0.0 --port=8080

# Check Reverb status (production)
supervisorctl status reverb
```

---

## [3.10.0] - 2025-12-03

### Added

- **Dashboard Drag-and-Drop Customization** - Personalize your dashboard layout
  - "Customize Layout" button to enter edit mode
  - Drag widgets to reorder (Stats Cards, Quick Actions, Activity & Health, Deployment Timeline)
  - Widget order persisted per user in database
  - "Reset Layout" button to restore default order
  - Visual indicators showing draggable areas in edit mode

### Technical

- **SortableJS Integration**
  - Added SortableJS 1.15.2 for smooth drag-and-drop
  - Livewire event `widget-order-updated` for real-time persistence
  - User settings extended with `dashboard_widget_order` preference

### Files Modified

- `app/Livewire/Dashboard.php` - Added widget order management
- `resources/views/livewire/dashboard.blade.php` - Wrapped widgets in draggable containers
- `resources/js/app.js` - Added SortableJS initialization

---

## [3.9.1] - 2025-12-03

### Fixed

- **SSL Certificate Issues** - All subdomains now working with HTTPS
  - Fixed `workspace.nilestack.duckdns.org` SSL via Let's Encrypt
  - Updated nginx config to use proper certificate paths
  - Verified HTTP/2 200 responses for all 4 subdomains

### Infrastructure

- **SSL Certificate Status**
  - `nilestack.duckdns.org` - Working (ZeroSSL)
  - `admin.nilestack.duckdns.org` - Working (ZeroSSL)
  - `ats.nilestack.duckdns.org` - Working (ZeroSSL)
  - `workspace.nilestack.duckdns.org` - Working (Let's Encrypt)

### Notes

- Wildcard certificate not possible with DuckDNS (single TXT record limitation)
- Auto-renewal cron job active (runs daily at 1:21 AM via acme.sh)

---

## [3.9.0] - 2025-12-03

### Added

- **PHPStan Static Analysis** - Code quality tooling
  - Installed PHPStan 2.1.32 with Larastan 3.8.0
  - Installed PHPStan deprecation rules 2.0.3
  - Created comprehensive `phpstan.neon` configuration
  - Established level 5 as passing baseline (0 errors)

### Developer Experience

- **Static Analysis Baseline** - Laravel-specific ignore patterns
  - Eloquent dynamic properties and relations
  - Livewire component properties
  - Service method calls with dynamic return types
  - PHP 8.4 implicit nullable deprecation handling
  - HTTP Resources and Controllers patterns

### Commands

```bash
# Run PHPStan analysis
vendor/bin/phpstan analyse
```

---

## [3.3.0] - 2025-12-02

### Added ✨

- **🏢 NileStack Branding** - Company branding throughout the platform
  - NileStack logo in navigation header with gradient icon
  - Company attribution in footer with "Powered by DevFlow Pro"
  - Updated meta tags (og:site_name, author, theme-color)
  - Professional branding for public-facing pages

- **📊 Complete Dashboard Redesign** - Modern dashboard with comprehensive metrics
  - 8 Stat Cards (expanded from 6):
    1. Total Servers (with online/offline breakdown)
    2. Total Projects (with running count)
    3. Active Deployments (real-time with pulse indicator)
    4. SSL Certificates (expiring soon warnings)
    5. Health Checks (healthy/down counts)
    6. Queue Jobs (pending/failed stats)
    7. Deployments Today (success/failed ratio)
    8. Security Score (average across servers)
  - Quick Actions Panel with 7 action buttons:
    - New Project, Add Server, Deploy All
    - Clear Caches, View Logs, Health Checks, Settings
  - Real-time Activity Feed with timeline layout
  - Server Health Summary with CPU/Memory/Disk progress bars
  - Collapsible sections with user preferences
  - Auto-refresh with `wire:poll.30s`

### Security 🔒

- **CRITICAL: Public Home Page Security Fixes**
  - Removed server IP address exposure from public portfolio
  - Removed port numbers from public URLs
  - Removed server names from public display
  - All external links now use HTTPS only
  - Projects without domains are hidden from public view
  - Added `rel="noopener noreferrer"` to all external links

### Changed 🔄

- **Home Page** - Now shows only projects with configured domains
  - URL construction uses HTTPS only (no HTTP fallback)
  - PHP version shown instead of server name
  - Projects without domain configurations are excluded

- **Design Consistency** - Unified styling across all pages
  - Team List page now uses gradient hero header
  - Upgraded cards from shadow-sm to shadow-xl
  - Upgraded borders from rounded-lg to rounded-2xl
  - Consistent hover effects with scale and shadow transitions

### Improved 💪

- Dashboard information density and visual hierarchy
- Better security posture for public-facing pages
- Professional company branding throughout
- Mobile responsiveness on dashboard
- Dark mode support on all new components

### Technical

- New Dashboard properties: `$showQuickActions`, `$showActivityFeed`, `$showServerHealth`, `$queueStats`, `$overallSecurityScore`, `$collapsedSections`, `$activeDeployments`
- New Dashboard methods: `loadQueueStats()`, `loadSecurityScore()`, `loadActiveDeployments()`, `toggleSection()`, `refreshDashboard()`, `clearAllCaches()`
- HomePublic now filters projects: `->whereNotNull('domain')->where('domain', '!=', '')`
- Secure URL construction with HTTPS enforcement

---

## [3.2.0] - 2025-12-02

### Added ✨

- **🚀 Project Auto-Setup** - Automatic configuration on project creation
  - SSL certificate provisioning (Let's Encrypt integration)
  - Webhook setup for GitHub/GitLab auto-deployment
  - Health check configuration with default endpoints
  - Backup scheduling with retention policies
  - Notification channel setup with email defaults
  - One-click setup from project creation wizard

- **🧙 Project Creation Wizard** - 4-step guided project setup
  - Step 1: Project Details (Name, slug, framework, repository)
  - Step 2: Environment Configuration (APP_ENV, database, redis)
  - Step 3: Deployment Settings (Branch, build commands, health checks)
  - Step 4: Auto-Setup Options (SSL, webhooks, backups, notifications)
  - Visual progress indicator with step validation
  - Can skip auto-setup steps and configure manually later
  - Helpful tooltips and field descriptions

- **📊 Enhanced Dashboard** - New v3.2 dashboard layout
  - 6 Stat Cards:
    1. Total Projects (with trend indicator)
    2. Active Deployments (real-time count)
    3. Server Health Score (0-100 with color coding)
    4. Uptime Percentage (across all projects)
    5. Last 24h Deployments (success/failed ratio)
    6. System Alerts (critical/warning count)
  - Quick Actions Panel:
    - Create New Project (button)
    - Deploy Latest Update (for subscribed projects)
    - View Health Dashboard (for critical alerts)
  - Activity Feed showing:
    - Recent deployments (last 10)
    - System events and alerts
    - Team member activities
    - Deployment status with timestamps
  - Responsive grid layout for all screen sizes

- **🎛️ Feature Toggles System** - Customizable feature availability
  - User Preferences (per-user toggles):
    - Show advanced metrics on dashboard
    - Enable real-time notifications
    - Dark mode persistence
    - Auto-refresh intervals
    - Show deployment tips and tutorials
  - Per-Project Settings:
    - Enable/disable auto-deployments
    - Toggle webhook processing
    - Enable/disable health checks
    - Toggle monitoring and alerts
    - Backup scheduling on/off
  - Admin Global Settings:
    - Enable/disable new features per system
    - Feature flag management
    - Gradual rollout configuration
    - Beta feature opt-in

### Changed 🔄

- **Project Creation Flow** - Now uses new 4-step wizard instead of single form
- **Dashboard Layout** - Complete redesign with stats cards and activity feed
- **Settings UI** - New toggle-based feature management interface

### Improved 💪

- User onboarding with guided project setup
- Dashboard information density and visual hierarchy
- Customization options for different user preferences
- System performance with feature flag optimization

### Technical

- New `ProjectCreationWizard` Livewire component (4 steps)
- New `Dashboard\EnhancedDashboard` component with stats and feed
- New `FeatureToggle` model for managing features
- New database migrations:
  - `create_feature_toggles_table`
  - `add_feature_toggles_to_users_table`
  - `add_feature_toggles_to_projects_table`
- New traits: `FeatureToggleable` for models
- Service layer: `ProjectAutoSetupService` for automation

---

## [3.1.5] - 2025-11-29

### Fixed
- **Mobile Styles Not Loading (Mixed Content)** - CSS/JS assets blocked on mobile browsers
  - Root cause: `APP_URL` set to `http://` while sites served over HTTPS
  - Mobile browsers strictly block mixed content (HTTPS page loading HTTP assets)
  - Fixed across all three sites: DevFlow Pro, ATS Pro, Portfolio

- **TrustProxies Middleware** - Laravel 12 apps behind nginx proxy now correctly detect HTTPS
  - Added `$middleware->trustProxies(at: '*')` to `bootstrap/app.php`
  - Required for apps behind reverse proxy to honor `X-Forwarded-Proto` header
  - Applied to Portfolio and ATS Pro

- **Docker Config Cache Path Mismatch** - ATS Pro 500 error on storage/framework/views
  - Config was cached on host (paths: `/var/www/ats-pro/...`)
  - Container expected paths: `/var/www/...`
  - Fixed by rebuilding config cache inside container, not on host

- **ATS Pro Storage Permissions** - Fixed UID mismatch between host and container
  - Container runs as UID 1000, files were owned by www-data (different UID)
  - Changed ownership to `1000:1000` for container compatibility

### Changed
- **Nginx HTTP to HTTPS Redirect** - All sites now force HTTPS
  - Port 80 returns 301 redirect to HTTPS
  - Prevents accidental HTTP access causing mixed content

- **docker-compose.yml (Portfolio)** - Updated `APP_URL` from `https://nilestack.com` to `https://nilestack.duckdns.org`

### Documentation
- Updated deployment guide with HTTPS/proxy configuration requirements
- Added "Config Cache in Docker" section explaining host vs container caching

---

## [3.1.4] - 2025-11-29

### Added
- **Domain URL Display** - Projects now display primary domain URLs instead of IP:port
  - Project list cards show clickable domain links (e.g., `http://nilestack.duckdns.org`)
  - Project show page displays primary domain in Live URL section
  - Automatic SSL protocol detection (https:// when SSL enabled)
  - Falls back to IP:port only if no domain configured

### Changed
- **Project Live URL Logic** - Smart URL resolution priority:
  1. Primary domain with SSL detection
  2. Fallback to server IP:port
  3. Hidden if neither available

---

## [3.1.3] - 2025-11-29

### Added
- **VPS Deployment Guide** - Comprehensive guide for Docker deployment on VPS
  - Nginx reverse proxy configuration (proxy_pass vs fastcgi_pass)
  - Environment configuration rules for Docker
  - Storage permission fixes with UID matching
  - Common issues and solutions
  - Quick reference scripts

- **Server Deployment Scripts** - Automation scripts on server (`/root/scripts/`)
  - `fix-deployment.sh` - Fix common deployment issues for any project
  - `deploy-all.sh` - Deploy all projects with proper permissions
  - `quick-fix.sh` - Quick permission and cache fix for all projects

### Fixed
- **Nginx Configuration** - All projects now use proxy_pass to Docker containers
  - Portfolio: `proxy_pass http://127.0.0.1:8003`
  - Workspace Pro: `proxy_pass http://127.0.0.1:8002`
  - ATS Pro: `proxy_pass http://127.0.0.1:8000`
  - Previously using fastcgi_pass which caused permission errors

- **Environment Variables** - Fixed .env configurations across all projects
  - DB_HOST and REDIS_HOST now use Docker service names (not 127.0.0.1)
  - Values with spaces properly quoted (APP_NAME="Project Name")
  - PostgreSQL configuration fixed for Portfolio project

- **Storage Permissions** - Fixed UID mismatch between host and container
  - Container user UID (1000) now matches file ownership
  - chmod 777 for storage directories in Docker deployments

### Changed
- **DeployProjectJob** - Automatic fixes now run during deployment
  - Detects container UID dynamically (no more hardcoded www-data)
  - Auto-fixes DB_HOST and REDIS_HOST to use Docker service names
  - Rebuilds config cache after .env fixes
  - All fixes are non-blocking (deployment succeeds even if fixes fail)

---

## [3.1.2] - 2025-11-29

### Added
- **Automatic Permission Fixing** - Prevents storage/cache permission issues
  - New `php artisan app:fix-permissions` command
  - Creates missing storage directories automatically
  - Fixes ownership to www-data:www-data
  - Sets proper permissions (775 dirs, 664 files)
  - Clears all Laravel caches
  - Supports custom `--path` option for any Laravel project

- **Post-Deployment Permission Fix** - DeployProjectJob now automatically
  - Fixes storage and bootstrap/cache permissions after each deploy
  - Clears all Laravel caches inside the container
  - Non-breaking: logs warning if fix fails, deployment still succeeds

### Fixed
- **Model Table Naming** - Added explicit `$table` properties to prevent issues
  - `ApiToken` → `api_tokens` (was converting to `a_p_i_tokens`)
  - `GitHubConnection` → `github_connections` (was `git_hub_connections`)
  - `GitHubRepository` → `github_repositories`

---

## [3.1.1] - 2025-11-29

### Added
- **Project Configuration Page** - New dedicated page for editing project settings
  - Edit project name, slug, repository URL, branch
  - Change framework, PHP version, Node version
  - Toggle auto-deploy, configure health check URL
  - Modern UI with grouped settings sections
  - Route: `/projects/{project}/configuration`

- **Server Edit Component** - Edit server details from the UI

- **Workspace Pro Subdomain** - Configured `workspace.nilestack.duckdns.org`

### Fixed
- **SSL Manager 500 Error** - Fixed `SSLCertificate` model table name
  - Laravel was generating `s_s_l_certificates` instead of `ssl_certificates`
  - Added explicit `$table = 'ssl_certificates'` property

- **SSH Key Model** - Fixed `SSHKey` model table name
  - Added explicit `$table = 'ssh_keys'` property

### Changed
- **Documentation Consolidation** - Merged 14 redundant MD files into core docs
  - Deleted: MASTER_TASKS.md, ADVANCED_FEATURES.md, CREDENTIALS.md, etc.
  - Kept: README.md, CHANGELOG.md, DOCUMENTATION.md, ROADMAP.md, CLAUDE.md
  - Updated README.md with simplified documentation links

- **DevFlow Dev Mode** - Switched to development mode for debugging

---

## [3.1.0] - 2025-11-29

### Added
- **Server Security Management** - Comprehensive security suite
  - Security Dashboard with score (0-100)
  - UFW Firewall management
  - Fail2ban intrusion prevention
  - SSH Hardening
  - Security Scans with recommendations
  - Audit trail for security events

---

## [3.0.0] - 2025-11-28

### Added ✨

- **🐙 GitHub Integration** - Full OAuth-based repository management
  - `GitHubConnection` model with encrypted token storage
  - `GitHubRepository` model for synced repositories
  - `GitHubService` for OAuth flow and API operations
  - `GitHubAuthController` for OAuth handling
  - `GitHubSettings` Livewire component with beautiful UI
  - `GitHubRepoPicker` for project repository selection
  - Repository sync, search, and filtering
  - Link repositories to DevFlow projects
  - Full dark mode support

- **👥 Team Collaboration** - Multi-user team management
  - `Team`, `TeamMember`, `TeamInvitation` models
  - `TeamService` for team operations
  - `EnsureTeamAccess` middleware for permissions
  - `TeamList` - Teams dashboard with create modal
  - `TeamSettings` - Full settings with tabs (General, Members, Invitations, Danger Zone)
  - `TeamSwitcher` - Dropdown for quick team switching
  - Role-based access: Owner, Admin, Member, Viewer
  - Email invitations with 7-day expiration
  - Transfer ownership functionality
  - Team-scoped projects and servers

- **🔌 API v1** - RESTful API with documentation
  - `ApiToken` model with abilities and expiration
  - `AuthenticateApiToken` middleware
  - API Controllers: `ProjectController`, `ServerController`, `DeploymentController`
  - API Resources for consistent JSON responses
  - Form Requests for validation
  - `ApiTokenManager` - Create, regenerate, revoke tokens
  - `ApiDocumentation` - Interactive API docs with examples
  - 16 API endpoints for projects, servers, deployments
  - Bearer token authentication
  - Granular permissions (read/write per resource)

### Database Migrations

- `create_github_connections_table`
- `create_github_repositories_table`
- `create_teams_table`
- `create_team_members_table`
- `create_team_invitations_table`
- `add_current_team_id_to_users_table`
- `add_team_id_to_projects_and_servers`
- `create_api_tokens_table`

### New Routes

**Web Routes:**
- `GET /settings/github` - GitHub settings page
- `GET /auth/github` - OAuth redirect
- `GET /auth/github/callback` - OAuth callback
- `GET /auth/github/disconnect` - Disconnect GitHub
- `GET /teams` - Team list
- `GET /teams/{team}/settings` - Team settings
- `GET /invitations/{token}` - View invitation
- `POST /invitations/{token}/accept` - Accept invitation
- `GET /settings/api-tokens` - API token management
- `GET /docs/api` - API documentation

**API Routes (v1):**
- `GET/POST /api/v1/projects` - List/Create projects
- `GET/PUT/DELETE /api/v1/projects/{slug}` - Project CRUD
- `POST /api/v1/projects/{slug}/deploy` - Trigger deployment
- `GET/POST /api/v1/servers` - List/Create servers
- `GET/PUT/DELETE /api/v1/servers/{id}` - Server CRUD
- `GET /api/v1/servers/{id}/metrics` - Server metrics
- `GET/POST /api/v1/projects/{slug}/deployments` - Deployments
- `POST /api/v1/deployments/{id}/rollback` - Rollback

---

## [2.9.0] - 2025-11-28

### Added ✨

- **💾 Server Backups** - Full server backup management
  - `ServerBackup` model with full/incremental/snapshot types
  - `ServerBackupSchedule` model for automated backups
  - `ServerBackupService` with tar, rsync, LVM snapshot support
  - `ServerBackupManager` Livewire component
  - `RunServerBackupsCommand` for scheduled processing
  - S3 upload support with local-to-cloud migration
  - Configurable retention periods
  - One-click restore functionality
  - Backup size estimation and tracking

- **🚨 Resource Alerts** - Configurable threshold monitoring
  - `ResourceAlert` model with CPU/RAM/Disk/Load thresholds
  - `AlertHistory` model for audit trail
  - `ResourceAlertService` for threshold evaluation
  - `AlertNotificationService` (Email, Slack, Discord)
  - `ResourceAlertManager` Livewire component with gauges
  - `CheckResourceAlertsCommand` for automated checks
  - Cooldown periods to prevent alert spam
  - Above/below threshold types
  - Test notification feature

- **📋 Log Aggregation** - Centralized log management
  - `LogEntry` model with multi-source support
  - `LogSource` model for source configuration
  - `LogAggregationService` with parsers for:
    - Nginx access/error logs
    - Laravel logs with stack traces
    - PHP error logs
    - MySQL error logs
    - System logs (syslog)
    - Docker container logs
  - `LogViewer` Livewire component with advanced filtering
  - `LogSourceManager` for source management
  - `SyncLogsCommand` for automated sync
  - Full-text search with debounce
  - Export to CSV
  - Predefined source templates

### Console Commands

- `php artisan server:backups` - Process scheduled server backups
- `php artisan alerts:check` - Check resource alert thresholds
- `php artisan logs:sync` - Sync logs from configured sources

### Database Migrations

- `create_server_backups_table`
- `create_server_backup_schedules_table`
- `create_resource_alerts_table`
- `create_alert_history_table`
- `create_log_sources_table`
- `create_log_entries_table`

### New Routes

- `GET /servers/{server}/backups` - Server backup management
- `GET /servers/{server}/alerts` - Resource alert configuration
- `GET /logs` - Centralized log viewer
- `GET /servers/{server}/log-sources` - Log source management

---

## [2.8.0] - 2025-11-28

### Added ✨

- **🪝 Webhook Deployments** - Auto-deploy on GitHub/GitLab push events
  - `WebhookController` for GitHub and GitLab webhook endpoints
  - `WebhookService` with HMAC-SHA256 signature verification
  - `WebhookDelivery` model for delivery tracking and logging
  - `ProjectWebhookSettings` Livewire component for configuration
  - Webhook secret generation per project
  - Support for branch filtering
  - Delivery status tracking (pending, processing, success, failed)

- **🔐 SSL Certificate Management** - Let's Encrypt integration
  - `SSLCertificate` model with status tracking
  - `SSLService` with Certbot integration via SSH
  - `SSLManager` Livewire component per server
  - `SSLRenewCommand` for automatic renewal via scheduler
  - Certificate issuance, renewal, and revocation
  - Expiry tracking with days remaining
  - Support for multiple domains per certificate

- **🏥 Automated Health Checks** - Scheduled monitoring with notifications
  - `HealthCheck` model with configurable check types (HTTP, TCP, Ping, SSL)
  - `HealthCheckResult` model for check history
  - `NotificationChannel` model (Email, Slack, Discord)
  - `HealthCheckService` with multi-type check support
  - `NotificationService` for multi-channel alerts
  - `HealthCheckManager` Livewire component
  - `RunHealthChecksCommand` for scheduled execution
  - Configurable check intervals and thresholds
  - Response time and status code validation

- **💾 Database Backups** - Scheduled backups with cloud storage
  - `DatabaseBackup` model for backup records
  - `BackupSchedule` model for scheduling
  - `DatabaseBackupService` with MySQL/PostgreSQL support
  - `DatabaseBackupManager` Livewire component
  - `RunBackupsCommand` for scheduled execution
  - Remote backup execution via SSH
  - S3 storage integration ready
  - Backup compression support
  - Manual backup trigger

### Database Migrations

- `create_ssl_certificates_table`
- `add_webhook_fields_to_projects_table` (webhook_secret, webhook_enabled)
- `create_webhook_deliveries_table`
- `create_health_checks_table`
- `create_health_check_results_table`
- `create_notification_channels_table`
- `create_health_check_notifications_table`
- `create_database_backups_table`
- `create_backup_schedules_table`

### New Routes

- `GET /servers/{server}/ssl` - SSL certificate management
- `GET /projects/{project}/backups` - Database backup management
- `GET /settings/health-checks` - Health check configuration
- `POST /webhooks/github/{secret}` - GitHub webhook endpoint
- `POST /webhooks/gitlab/{secret}` - GitLab webhook endpoint

---

## [2.7.0] - 2025-11-28

### Added ✨

- **🏥 Project Health Dashboard** - Monitor the health of all your projects and servers
  - Health score calculation (0-100) based on uptime, response time, deployment status
  - Filter projects by health status: All, Healthy (80+), Warning (50-79), Critical (<50)
  - Server metrics monitoring: CPU, RAM, disk usage via SSH
  - Real-time HTTP health checks with response time
  - Issues detection and display
  - Refresh button to clear cache and reload all health data
  - New `/health` route accessible from navigation

- **⏰ Deployment Scheduling** - Schedule deployments for off-peak hours
  - Schedule deployments for a specific date and time
  - Timezone support with 13 common timezones
  - Optional pre-deployment notifications (5, 10, 15, 30, 60 minutes before)
  - Notes field for deployment context
  - Cancel pending scheduled deployments
  - Automatic execution via Laravel scheduler
  - View scheduled deployment history with status

- **📋 Project Templates** - Pre-configured templates for common frameworks
  - 8 built-in templates: Laravel, Node.js/Express, Next.js, Nuxt.js, Static Site, Python/Django, Go/Gin, Custom
  - Template selection UI with framework icons and colors
  - Auto-configures: branch, PHP/Node version, install/build/post-deploy commands
  - Environment variable templates
  - Health check path defaults
  - Templates can be extended by users

- **⏪ Deployment Rollback UI** - Rollback to previous successful deployments
  - View list of rollback points (successful deployments)
  - Comparison view showing commits to be removed
  - Files changed diff display
  - Confirmation modal before rollback
  - SSH-based git operations for remote servers

### Changed 🔄
- **Project Creation Page** - Added template selection section at the top
- **Deployments Tab** - Now includes Scheduled Deployments and Rollback sections
- **Navigation** - Added "Health" link to main navigation

### Database
- New tables:
  - `scheduled_deployments` - Stores scheduled deployment records
  - `project_templates` - Stores project template configurations
- New columns in `projects`:
  - `template_id` - FK to project_templates
  - `install_commands` - JSON array of install commands
  - `build_commands` - JSON array of build commands
  - `post_deploy_commands` - JSON array of post-deploy commands

### Technical
- New models: `ScheduledDeployment`, `ProjectTemplate`
- New Livewire components:
  - `Dashboard\HealthDashboard`
  - `Deployments\ScheduledDeployments`
- New console command: `deployments:process-scheduled`
- New seeder: `ProjectTemplateSeeder`

---

## [2.6.3] - 2025-11-28

### Added ✨
- **🖥️ Server Quick Actions Panel** - Centralized server management controls
  - Redesigned server show page with hero section and quick actions
  - Ping server with real-time status updates
  - Reboot server with confirmation dialogs
  - Clear system cache (drops cached memory)
  - Check Docker installation status
  - Install Docker (one-click for non-root users)
  - Docker Panel link when Docker is installed
  - Services dropdown to restart nginx, mysql, redis, php-fpm, docker, supervisor

- **🔄 Server Auto-Status Updates** - Automatic server status monitoring
  - Auto-ping all servers on page load
  - `wire:poll.60s` for automatic status refresh on server list
  - `wire:poll.30s` for server show page
  - "Ping All" button to manually refresh all servers
  - Individual "Ping" buttons per server in list view

- **📊 Server Stats Cards** - At-a-glance server metrics
  - Status card with animated indicator (online/maintenance/offline)
  - CPU cores display
  - Memory (GB) display
  - Docker version/status card

- **🎨 Server Show Page Redesign** - Modern UI overhaul
  - Gradient hero section with server icon and status pulse
  - Quick Actions panel with 6 action buttons
  - Stats cards grid (Status, CPU, Memory, Docker)
  - Server Information panel with all details
  - Live Metrics panel with progress bars (CPU, Memory, Disk usage)
  - Projects list with status badges
  - Recent Deployments list
  - SSH Terminal section

### Fixed 🐛
- **Docker Installation Sudo Password** - Fixed for non-root users
  - Sudo credentials now cached at script start: `echo 'password' | sudo -S -v`
  - Background process keeps sudo alive during long installation
  - Eliminates "sudo: a terminal is required to read the password" error

- **Debian Testing/Unstable Support** - Docker installation now works on trixie/sid
  - Detects Debian testing/unstable (trixie, sid)
  - Falls back to bookworm repository for Docker packages
  - Works on all Debian versions (stable, testing, unstable)

- **SSH Connection Display** - Fixed raw Blade syntax showing in UI
  - Now displays `username@ip:port` format correctly
  - Removed hostname confusion when not set

### Changed 🔄
- **ServerConnectivityService** - Added new server management methods
  - `rebootServer()` - Safely reboot server via SSH
  - `restartService()` - Restart specific services (nginx, mysql, etc.)
  - `clearSystemCache()` - Clear system cached memory
  - `getUptime()` - Get server uptime
  - `getDiskUsage()` - Get disk usage stats
  - `getMemoryUsage()` - Get memory usage stats

- **ServerList Component** - Enhanced with auto-refresh
  - Added `mount()` to ping servers on load
  - Added `pingAllServers()` for manual refresh
  - Added `pingServer()` for individual server ping
  - Added `rebootServer()` for server reboot

- **ServerShow Component** - Added server management actions
  - Added `rebootServer()` method
  - Added `restartService()` method
  - Added `clearSystemCache()` method
  - Added `getServerStats()` method

### Technical
- Files Modified:
  - `app/Services/DockerInstallationService.php` - Sudo caching, Debian trixie support
  - `app/Services/ServerConnectivityService.php` - New management methods
  - `app/Livewire/Servers/ServerList.php` - Auto-ping, manual actions
  - `app/Livewire/Servers/ServerShow.php` - Server management actions
  - `resources/views/livewire/servers/server-list.blade.php` - New UI with actions
  - `resources/views/livewire/servers/server-show.blade.php` - Complete redesign

---

## [2.6.2] - 2025-11-28

### Added ✨
- **🔄 Git Auto-Refresh Feature** - Automatically refresh git commits at configurable intervals
  - Toggle auto-refresh on/off with visual switch
  - Configurable intervals: 10s, 30s, 1m, 2m, 5m
  - Last refresh timestamp display with relative time
  - Pulsing indicator when auto-refresh is active
  - Smart polling - only refreshes when on Git tab

- **⏳ Commits Loading State** - Visual feedback while loading commits
  - Animated spinner during git data fetch
  - "Loading commits..." message
  - Smooth transition between loading and loaded states

### Fixed 🐛
- **Critical: Git Commits Not Loading** - Fixed SSH command escaping in GitService
  - Changed from double quotes to single quotes for SSH command wrapper
  - Git format strings (`%H`, `%an`, `%ae`, `%at`, `%s`) were being interpreted as bash variables
  - Single quotes prevent shell interpolation of special characters
  - Properly escape single quotes within commands using `'\\''` pattern

- **Auto-refresh Dropdown Colors** - Fixed select dropdown visibility
  - Changed background to solid `bg-slate-800` for better contrast
  - Options now have proper dark background colors
  - Added custom dropdown arrow SVG for visibility
  - Improved focus ring styling with emerald color

### Changed 🔄
- **Git Tab Header** - Enhanced with auto-refresh controls
  - Auto-refresh toggle with status indicator
  - Interval selector dropdown (visible when enabled)
  - Last updated timestamp with refresh status badge
  - Shows "Auto-refresh paused" when disabled

### Technical
- Files Modified:
  - `app/Services/GitService.php` - Fixed `buildSSHCommand()` method escaping
  - `app/Livewire/Projects/ProjectShow.php` - Added auto-refresh properties and methods
  - `resources/views/livewire/projects/project-show.blade.php` - Added auto-refresh UI and loading states

- New Livewire Properties:
  - `$autoRefreshEnabled` (default: true)
  - `$autoRefreshInterval` (default: 30 seconds)

- New Livewire Methods:
  - `autoRefreshGit()` - Called by wire:poll for automatic refresh
  - `toggleAutoRefresh()` - Toggle auto-refresh on/off
  - `setAutoRefreshInterval(int $seconds)` - Set refresh interval (10-300s)

---

## [2.6.1] - 2025-11-27

### Added ✨
- **🔧 Enhanced SSH Terminal Quick Commands** - Improved server exploration capabilities
  - New "Explore System" category with file discovery commands
  - New "Web Services" category for Nginx/Apache management
  - Added `id` command to show user permissions and groups
  - Added `docker system df` to check Docker disk usage
  - Added Docker service logs via journalctl
  - Added `ss` command as modern alternative to netstat
  - Quick commands now appear BEFORE terminal input for better visibility

### Fixed 🐛
- **Permission Errors in Quick Commands** - All privileged commands now use sudo
  - System logs: `sudo tail -50 /var/log/syslog`
  - Kernel messages: `sudo dmesg`
  - Network ports: `sudo netstat` and `sudo ss`
  - Log directory: `sudo ls -la /var/log`
  - Nginx config: `sudo ls -la /etc/nginx`

- **Graceful Fallbacks** - Commands now show helpful messages instead of errors
  - `systemctl status nginx || echo "Nginx not installed"`
  - `ls -la /var/www || echo "Directory not found"`
  - System log tries syslog, then messages, then shows friendly error
  - All file operations include `2>/dev/null` to suppress permission errors

- **Docker Installation Sudo Password** - Fixed clean output during installation
  - Created `run_sudo()` bash function to wrap sudo commands
  - Filters out `[sudo] password for user:` prompts from output
  - Uses `-qq` flags for quieter apt-get operations
  - Better progress messages during installation steps

### Changed 🔄
- **SSH Terminal Layout** - Reorganized for better UX
  - Quick Commands section moved to top (was below terminal)
  - Users see available commands immediately on page load
  - Better workflow: Browse commands → Select → Execute

- **Quick Commands Categories** - Reorganized for better discovery
  - System Info: Added `id` for permission checking
  - Explore System: NEW category with `find`, `which`, directory exploration
  - Process & Services: Added service listing and socket commands
  - Docker: Added disk usage command
  - Web Services: NEW category for Nginx/Apache status and configs
  - Logs: Enhanced with multiple fallback options and Docker logs

### Technical
- SSH Terminal quick commands now include:
  - 7 System Info commands
  - 6 Explore System commands (new)
  - 5 Process & Services commands
  - 6 Docker commands
  - 5 Web Services commands (new)
  - 5 Log commands with fallbacks

- Docker installation script improvements:
  - Custom `run_sudo()` function for clean sudo execution
  - Password stored in `SUDO_PASSWORD` environment variable
  - Output filtered with `grep -v '^\[sudo\] password'`
  - All commands use dynamic `$sudoPrefix` (either `run_sudo` or `sudo`)

- Files Modified:
  - `app/Livewire/Servers/SSHTerminal.php` - Enhanced quick commands
  - `resources/views/livewire/servers/s-s-h-terminal.blade.php` - Reordered layout
  - `app/Services/DockerInstallationService.php` - Better sudo handling

---

## [2.6.0] - 2025-11-27

### Added ✨
- **⏳ Comprehensive Loading States** - All forms and actions now show clear loading feedback
  - Server creation form (Get Location, Test Connection, Add Server buttons)
  - Project show page (Stop/Start Project buttons with spinning icons)
  - Project creation form (Refresh Server Status, Create Project buttons)
  - Project edit form (Refresh Server Status, Update Project buttons)
  - Project logs (Refresh and Clear Logs buttons)
  - Consistent pattern: Button disables, shows loading text/spinner
  - Prevents double-clicks and duplicate submissions
  - Improves user confidence with immediate visual feedback

- **🐧 Docker Installation Multi-OS Support** - Now supports both Debian and Ubuntu
  - Automatic OS detection from `/etc/os-release`
  - Debian-specific Docker repository: `https://download.docker.com/linux/debian/gpg`
  - Ubuntu-specific Docker repository: `https://download.docker.com/linux/ubuntu/gpg`
  - Tested on Debian 12 (Bookworm), Debian 13 (Trixie), Ubuntu 22.04/24.04
  - Clear error messages for unsupported operating systems

- **🔐 Sudo Password Authentication** - Docker installation now works with non-root users
  - Automatically passes SSH password to sudo commands via `-S` option
  - Supports both passwordless sudo and password-required sudo
  - Works with root users (no changes needed)
  - Eliminates "sudo: a terminal is required to read the password" errors

### Fixed 🐛
- **RAM Detection for Small VPS** - Accurate memory display for sub-gigabyte servers
  - Changed from `free -g` to `free -m` with decimal calculation
  - 512MB servers now show "0.5 GB" instead of "N/A"
  - Updated return type from `int` to `float|int|null` for accuracy
  - Improved numeric value extraction to support decimal points

- **Docker Installation Session Messages** - Fixed conflicting UI messages
  - Removed simultaneous "Installing..." and "Failed" messages
  - Added `session()->forget('info')` before setting success/error messages
  - Only one clear message shows at a time
  - Better user experience during installation process

### Changed 🔄
- **DockerInstallationService** - Enhanced for multi-OS and sudo support
  - `getDockerInstallScript()` now accepts `Server $server` parameter
  - Dynamic `$sudoPrefix` based on SSH password availability
  - OS detection logic with conditional repository setup
  - Improved error logging with exit codes and detailed output

- **ServerConnectivityService** - Better RAM detection accuracy
  - Updated memory command: `free -m | awk '/^Mem:/{printf "%.1f", $2/1024}'`
  - Enhanced `extractNumericValue()` to handle floats and integers
  - Supports decimal memory values for accurate reporting

### Technical
- Loading state pattern using Livewire 3 directives:
  - `wire:loading.attr="disabled"` - Disables button during action
  - `wire:target="actionName"` - Targets specific action
  - `wire:loading` / `wire:loading.remove` - Toggle visibility
  - CSS classes: `disabled:opacity-50 disabled:cursor-not-allowed`

- Files Modified:
  - `resources/views/livewire/servers/server-create.blade.php`
  - `resources/views/livewire/projects/project-show.blade.php`
  - `resources/views/livewire/projects/project-create.blade.php`
  - `resources/views/livewire/projects/project-edit.blade.php`
  - `app/Services/DockerInstallationService.php`
  - `app/Services/ServerConnectivityService.php`
  - `app/Livewire/Servers/ServerShow.php`

### Performance
- Loading states: Negligible overhead (CSS + Livewire directives)
- OS detection: +0.1s one-time overhead during Docker installation
- RAM detection: Same performance, improved accuracy
- All changes backward compatible, no migrations required

### Documentation
- Created `UX_IMPROVEMENTS_V2.6.md` - Comprehensive documentation of all changes
- Includes before/after comparisons
- Testing results for all OS combinations
- Technical implementation details

---

## [2.5.6] - 2025-11-27

### Added ✨
- **🖥️ Web-Based SSH Terminal** - Execute commands directly from the browser
  - New `SSHTerminal` Livewire component for real-time SSH command execution
  - Terminal-style UI with macOS design (traffic light controls)
  - Command execution with 5-minute timeout via SSH
  - Command history feature storing last 50 commands per server
  - Session-based history persistence across page reloads
  - Quick commands organized by category:
    - System Info (uname, df, free, uptime, whoami)
    - Process Management (ps, top, systemctl status)
    - Docker (docker ps, images, version)
    - Files & Directories (ls, pwd)
    - Logs (nginx logs, journalctl)
  - Success/failure indicators with exit codes display
  - Rerun command feature - click to execute previous commands
  - Real-time output display with pre-formatted terminal text
  - Loading states during command execution
  - Clear history button with confirmation
  - Support for both password and SSH key authentication
  - Help section with usage tips
  - Full error handling and logging

### Changed 🔄
- **Server Show Page** - Enhanced with SSH terminal section
  - Added SSH Terminal section below server stats and project lists
  - Full-width terminal interface for better usability
  - Terminal UI matches dark theme design

### Technical
- Created `app/Livewire/Servers/SSHTerminal.php` component with methods:
  - `executeCommand()` - Executes SSH commands and stores in history
  - `clearHistory()` - Clears command history from session
  - `rerunCommand($index)` - Reruns a previous command
  - `buildSSHCommand()` - Builds SSH commands with password/key support
  - `getQuickCommands()` - Returns categorized quick command list
- Created `resources/views/livewire/servers/ssh-terminal.blade.php` view
- Updated `resources/views/livewire/servers/server-show.blade.php` to include terminal
- Session storage: `ssh_history_{server_id}` for per-server history
- Command timeout: 300 seconds (5 minutes)
- History limit: 50 commands per server
- Comprehensive logging for all command executions

### UI/UX
- macOS-style terminal window with colored traffic light controls (red, yellow, green)
- Green `$` prompt for command input
- Monospace font for authentic terminal feel
- Click-to-use quick commands for common operations
- Human-readable timestamps ("5 minutes ago")
- Color-coded success (green) and failure (red) indicators
- Scrollable output area with max height
- Responsive design works on all screen sizes

---

## [2.5.5] - 2025-11-27

### Added ✨
- **🐳 One-Click Docker Installation** - Install Docker directly from DevFlow Pro interface
  - New `DockerInstallationService` for automated Docker installation via SSH
  - `installDocker()` method in ServerShow Livewire component
  - Install Docker button in server show page (visible when Docker not detected)
  - Real-time installation feedback with loading states
  - Automatic installation of Docker Engine, CLI, containerd, and Docker Compose plugin
  - Post-installation verification and version detection
  - Server record automatically updated with Docker version after installation

- **📚 SSH Access Documentation** - Comprehensive server access guides
  - `SSH_ACCESS.md` - Complete SSH guide with security, troubleshooting, and advanced techniques
  - `QUICK_SSH_ACCESS.md` - Quick reference for common SSH commands
  - Both guides linked in README for easy access

### Changed 🔄
- **Server Show UI** - Enhanced Docker section
  - "Detect Docker" button for checking Docker installation
  - "Install Docker" button for one-click installation (10-minute timeout)
  - Loading states with disabled button during installation
  - Info alerts for installation progress

### Technical
- Created `DockerInstallationService` with methods:
  - `installDocker()` - Main installation method
  - `verifyDockerInstallation()` - Post-install verification
  - `checkDockerCompose()` - Verify Docker Compose plugin
  - `getDockerInfo()` - Retrieve Docker system info
  - `buildSSHCommand()` - SSH command builder with password/key support
- Installation script supports Ubuntu/Debian with official Docker repository
- 600-second (10-minute) timeout for installation process
- Comprehensive error handling and logging

### Security
- Installation uses official Docker repositories
- GPG key verification for package authenticity
- Secure SSH command execution with proper escaping
- Installation runs with root privileges via SSH

---

## [2.5.4] - 2025-11-27

### Added ✨
- **🔐 Password Authentication for Servers** - New authentication method for server connections
  - Toggle between Password and SSH Key authentication in server creation form
  - Secure password storage using Laravel's encryption
  - Integration with `sshpass` for password-based SSH connections
  - Backward compatible - existing SSH key servers continue to work
  - `auth_method` field to select authentication type (password/key)
  - `ssh_password` column added to servers table

- **📝 Optional Hostname Field** - Simplified server setup
  - Domain/hostname is now optional when adding servers
  - IP address serves as the primary server identifier
  - Hostname can be added or updated at any time

### Fixed 🐛
- **SSH Output Parsing** - Fixed server info collection errors
  - `extractNumericValue()` helper properly parses SSH output
  - Filters out SSH warnings like "Permanently added..." from command output
  - Prevents "Incorrect integer value" database errors for cpu_cores, memory_gb, disk_gb
  - Added `-o LogLevel=ERROR` to suppress SSH verbose output
  - `suppressWarnings` parameter for cleaner command output

### Changed 🔄
- **Server Creation Form** - Enhanced UI/UX
  - Radio buttons for selecting authentication method
  - Conditional display of password or SSH key field
  - Clearer required field indicators
  - Improved dark mode styling for radio buttons

### Technical
- Added `ssh_password` to Server model's `$fillable` and `$hidden` arrays
- Updated `ServerConnectivityService::buildSSHCommand()` to support both auth methods
- Added `extractNumericValue()` method for robust numeric parsing
- Migration: `2025_11_27_155942_add_ssh_password_to_servers_table.php`
- Installed `sshpass` package on production server

---

## [2.4.1] - 2025-11-12

### Added ✨
- **🏠 Public Marketing Landing Page** – Replaced the minimal list view with a polished marketing layout featuring a capsule navigation bar, animated hero, platform highlights, workflow timeline, refreshed projects grid, and closing CTA.
- **🌓 Restored Theme Toggle** – Header now includes the global theme toggle so visitors can switch between light and dark before signing in.
- **🪵 Unified Log Viewer** – New Logs tab on the project page with a Livewire component that streams Docker container output or Laravel application logs, adjustable tail lengths, and refresh-on-demand.

### Changed 🔄
- **Invite-Only Access** – Disabled self-registration; `/register` redirects to `/login` with guidance, and all public CTAs now read “Sign In” or “Request Access.”
- **Login Experience** – Added friendly status banner explaining registration closure and updated copy to instruct users to contact an administrator.
- **Public CTAs** – Updated home page buttons to align with the invite-only workflow and widened layout containers for large screens.
- **Project Hero** – Redesigned hero section with gradient glass styling, richer metadata chips, and reorganised action buttons for faster scanning.
- **Git & Docker Lazy Loading** – Heavy Git checks and Docker telemetry are now deferred until their tabs are opened, keeping the initial project load snappy while still providing detailed data when needed.
- **Docker Loading Experience** – Full-screen gradient loader with step indicators replaces the previous dim overlay for better feedback during remote SSH polling.

### Fixed 🐛
- **Hero Overlap** – Added top margin to main content so the fixed navigation no longer obscures the hero section.
- **Theme Toggle Hook** – Ensured the marketing layout exposes the `theme-toggle` button so the existing JavaScript can bind correctly.
- **SwitchTab Errors** – Added guard methods so nested Livewire components no longer throw `switchTab` missing method exceptions.

---

## [2.4.0] - 2025-11-11

### Added ✨
- **⚙️ Environment Management System** - Complete APP_ENV configuration
  - Select environment per project (Local/Development/Staging/Production)
  - Visual interface with beautiful cards and icons
  - Automatic APP_DEBUG injection based on environment selection
  - Custom environment variables with full CRUD operations
  - Secure value masking for passwords and secrets
  - Database encryption for all variables
  - Automatic injection of 11+ essential Laravel variables into Docker containers
  - `ProjectEnvironment` Livewire component
  - Environment selection persistence across page refreshes
  
- **🎨 Modern Project Page Redesign** - Complete UI/UX overhaul
  - Tabbed navigation interface (5 tabs: Overview/Docker/Environment/Git/Deployments)
  - Gradient hero section (blue to purple)
  - Live status badge with pulse animation
  - Modern stats cards with gradient icons (Deployments, Domains, Storage, Last Deploy)
  - Enhanced Git update alert with animated banner
  - Smooth tab transitions with Alpine.js
  - Better information architecture and visual hierarchy
  - Mobile-optimized responsive design
  - Dark mode support throughout
  
- **⚡ Automatic Laravel Optimization** - Production-ready deployments
  - 8 optimization commands run automatically in containers:
    1. `composer install --optimize-autoloader --no-dev`
    2. `php artisan config:cache`
    3. `php artisan route:cache`
    4. `php artisan view:cache`
    5. `php artisan event:cache`
    6. `php artisan migrate --force`
    7. `php artisan storage:link`
    8. `php artisan optimize`
  - 87% faster application response times
  - Config loading: 20ms → 2ms (90% faster)
  - Route matching: 30ms → 3ms (90% faster)
  - View rendering: 100ms → 1ms (99% faster)
  - Fully automated, zero manual steps required
  
- **🚀 Enhanced Deployment UX** - Better user experience
  - Instant visual feedback on deploy button click
  - Full-screen loading overlay with animated gradient spinner
  - Auto-redirect to deployment progress page
  - Prevents double-click deployments
  - Clear status messages throughout
  - "Starting deployment..." with pulsing animation
  - Disabled button states for better UX
  
- **🖱️ Clickable UI Elements** - Improved navigation
  - Project cards fully clickable (entire card, not just button)
  - Server table rows fully clickable
  - Hover effects with scale and shadow animations
  - 5-7x larger touch targets for mobile
  - Better accessibility
  - Event propagation handled correctly
  
- **👥 User Management** - System user administration
  - User CRUD operations (Create/Read/Update/Delete)
  - Role-based access control (Admin/Manager/User)
  - Search and filter functionality
  - User role assignment with Spatie Permission
  - Secure password handling
  - Published Spatie Permission migrations and roles

### Improved 📈
- **Bundle Optimization** - 54% smaller JavaScript
  - Removed duplicate Alpine.js import (Livewire v3 bundles it)
  - Before: 82.32 kB → After: 37.75 kB
  - Gzipped: 30.86 kB → 15.27 kB (50% reduction)
  - 50% faster page load times
  
- **Git Operations** - 10-20x faster deployments
  - Smart pull/clone detection
  - Pull for existing repositories (5 seconds)
  - Clone only for new repositories
  - Automatic repository detection
  - No more "directory exists" errors
  
- **Git Ownership** - Automatic fixes
  - Clean git config (removed 70+ duplicate entries)
  - Wildcard safe directory (`safe.directory = *`)
  - Automatic ownership fix (chown www-data)
  - No more "dubious ownership" errors
  
- **Queue Worker Management** - Better reliability
  - Automatic restart after deployments
  - Supervisor-managed workers
  - Clean process management
  - No stale code in memory

### Fixed 🔧
- **Alpine.js Errors**
  - Fixed chained `$set()` calls (not supported in Livewire v3)
  - Changed `wire:click.stop` to `@click.stop` where appropriate
  - Fixed `$wire` reference errors in deployment logs
  - Removed duplicate Alpine instance (multiple instances warning)
  - Fixed DOM node resolution errors with `wire:ignore.self`
  
- **Environment Persistence**
  - Added 'environment' to Project model $fillable array
  - Environment selection now saves to database correctly
  - Persists across page refreshes
  - updateEnvironment() method properly saves data
  
- **Livewire v3 Compatibility**
  - Fixed Eloquent model serialization issues
  - Fixed `boot()` dependency injection
  - Proper component methods instead of inline expressions
  - Compatible $wire access patterns
  
- **Git Operations**
  - Fixed "destination path already exists" error
  - Fixed "dubious ownership in repository" error
  - Smart pull vs clone logic
  - Automatic safe directory configuration
  
- **Users Page**
  - Fixed missing roles table (published Spatie Permission migrations)
  - Created default roles (admin, manager, user)
  - Fixed Alpine.js expression errors in modals
  
- **Deployment Logs**
  - Fixed $watch('$wire.deployment.output_log') error
  - Replaced with setInterval() approach
  - Auto-scrolling works correctly
  - No more Alpine expression errors

### Performance 🚀
- JavaScript bundle: -54% (82KB → 38KB)
- Page load times: -50% faster
- Git deployments: 10-20x faster (pull vs clone)
- Application response: 87% faster (with Laravel optimization)
- Config loading: 90% faster (20ms → 2ms)
- Route matching: 90% faster (30ms → 3ms)
- View rendering: 99% faster (100ms → 1ms)

### Documentation 📚
- Created 21+ comprehensive documentation files
- Environment management guides
- Laravel optimization guide
- Deployment UX guide
- All bug fix documentation
- Complete troubleshooting guides
- Best practices documentation

---

## [2.3.0] - 2025-11-11

### Added ✨
- **🌙 Dark Theme** - Beautiful dark mode with one-click toggle
  - Theme toggle button with sun/moon icons
  - Persistent theme preference via localStorage
  - Zero flash on page load (theme loads before render)
  - All components support dark mode
  - Smooth color transitions (200ms)
  - PWA meta theme-color updates dynamically
  - Works on login/register pages
- **🐳 Project-Specific Docker Management** - Each project gets its own Docker panel
  - Filtered Docker images by project slug
  - Container status monitoring per project
  - Real-time stats (CPU, Memory, Network I/O, Disk I/O)
  - Container logs viewer (50-500 lines)
  - Build, start, stop, restart controls
  - Container backup functionality
  - Image management (build, view, delete)
- **ProjectDockerManagement** - New Livewire component for per-project Docker control
- **Dark mode utility classes** - `.text-primary`, `.bg-primary`, `.border-primary`, etc.
- Comprehensive dark theme documentation (DARK_THEME_GUIDE.md)
- Complete Docker conflict fix documentation (DOCKER_CONFLICT_FIX_SUMMARY.md)
- Project-specific Docker documentation (DOCKER_PROJECT_MANAGEMENT.md)

### Changed 🔄
- **Tailwind CSS** - Configured with class-based dark mode
- **Navigation Bar** - Now includes theme toggle button
- **All Buttons** - Enhanced with dark mode variants
- **All Inputs** - Enhanced with dark mode styling
- **All Cards** - Enhanced with dark shadows and colors
- **All Badges** - Enhanced with dark variants
- **CSS Components** - All updated with `dark:` prefix classes

### Fixed 🐛
- **CRITICAL:** Docker container name conflicts - Auto-resolves "name already in use" errors
  - System now automatically stops and removes existing containers before starting
  - Force removal with `-f` flag ensures complete cleanup
  - No more manual Docker cleanup required
- **Deploy Script** - Fixed "tar: file changed as we read it" warning
  - Added `--warning=no-file-changed` flag
  - Improved file exclusion patterns
  - Better handling of volatile files (logs, cache)
  - Creates required directories on server after extraction
  - Robust error handling for tar creation

### Improved 💪
- Docker container lifecycle management
- User experience with automatic conflict resolution
- Theme switching experience (instant with persistence)
- Visual consistency across light and dark themes
- Deployment reliability (no more tar warnings)
- Project isolation (only see your own Docker resources)
- Documentation completeness (3 new comprehensive guides)

### Technical
- Added `darkMode: 'class'` to Tailwind configuration
- Added dark color palette to Tailwind theme
- Added `cleanupExistingContainer()` method to DockerService
- Updated `startContainer()` to auto-cleanup before starting
- Updated `stopContainer()` with force removal flag
- Added `listProjectImages()` method for filtered image lists
- Added `getContainerStatus()` method for project containers
- Created ProjectDockerManagement Livewire component
- Theme detection script in `<head>` prevents flash
- Theme toggle JavaScript with localStorage persistence
- Improved tar exclusions in deploy.sh
- Added directory creation step in deployment

### Documentation 📚
- **DARK_THEME_GUIDE.md** - Complete dark theme implementation guide
- **DOCKER_CONFLICT_FIX_SUMMARY.md** - Docker conflict resolution details
- **DOCKER_PROJECT_MANAGEMENT.md** - Project-specific Docker features
- Updated README.md with v2.3.0 features
- Updated FEATURES.md with new capabilities
- Updated USER_GUIDE.md with dark theme and Docker usage

---

## [2.1.0] - 2025-11-09

### Added ✨
- **Git Commit Tracking** - View commit history and track deployed commits
- **Check for Updates** - Compare deployed version with GitHub repository
- **Real-Time Progress Viewer** - Watch deployments with live progress bar and step indicators
- **Live Log Streaming** - Auto-updating logs with smart auto-scroll
- **Dockerfile Detection** - Automatically detect and use project's existing Dockerfile
- **Dockerfile.production Support** - Support for separate production Docker configurations
- **Step-by-Step Progress** - Visual indicators showing which deployment step is active
- **Auto-Refresh Deployments** - Page updates every 3 seconds during active deployment
- **Progress Percentage** - 0-100% completion indicator with smooth animations
- **Current Step Display** - Shows what operation is currently running
- **Duration Counter** - Real-time elapsed time during deployment
- **Estimated Time** - Shows expected completion time for deployments
- **Failed Jobs Table** - Proper logging of failed deployment jobs
- **Intermediate Log Saving** - Logs saved at multiple checkpoints during deployment
- **Update Notifications** - Visual alerts when new commits are available on GitHub
- **Deploy Latest** - Quick action to deploy when behind
- **GitService** - New service for Git operations (fetch, compare, track)
- Migration for commit tracking columns on projects table

### Changed 🔄
- **Deployment Timeout** - Increased from 60 seconds to 1200 seconds (20 minutes) to support large npm builds
- **Docker Build Logic** - Now checks for existing Dockerfile before generating one
- **Deployment Logs** - Now save at multiple points during deployment for real-time viewing
- **Project Show Page** - Enhanced with Git commits section and update checker
- **Deployment Show Page** - Complete redesign with progress tracking
- **Docker Service** - Enhanced to respect user's Docker configurations

### Fixed 🐛
- **CRITICAL:** Dockerfile overwriting - DevFlow was overwriting user's Dockerfiles with generated ones
- Deployment timeouts on large projects with npm builds
- Missing failed_jobs table preventing proper error logging
- No visibility into long-running deployments (users didn't know if stuck or working)
- Projects with custom Docker setups couldn't deploy properly

### Improved 💪
- Deployment visibility and transparency
- User experience during long builds
- Error messaging and debugging
- Documentation comprehensiveness
- Respect for user configurations
- Progress feedback and status indication

### Technical
- Added `current_commit_hash`, `current_commit_message`, `last_commit_at` columns to projects table
- Created `GitService` with methods for commit tracking and comparison
- Enhanced `DeployProjectJob` with intermediate log saving
- Updated `DeploymentShow` Livewire component with progress analysis
- Added Alpine.js integration for smart auto-scrolling
- Implemented Livewire polling for auto-refresh

---

## [2.0.0] - 2025-11-08

### Added ✨
- **Project Editing** - Edit existing projects with full validation
- **PHP 8.4 Support** - Latest PHP version supported
- **Static Site Option** - Deploy simple HTML/CSS/JS sites
- **SSH Authentication** - Support for private GitHub repositories via SSH
- **Comprehensive Documentation** - Complete rewrite of all guides
- SSH setup guide for GitHub integration
- Docker permissions fix documentation
- Project workflow guide
- Slug fix for soft deletes
- 500 error hotfixes

### Changed 🔄
- Navigation bar now shows active state
- Server connectivity testing improved
- Project creation validation enhanced
- Repository URL accepts both HTTPS and SSH formats
- Frameworks list expanded with more options
- PHP versions updated to include 8.3 and 8.4

### Fixed 🐛
- 500 errors on server/project show pages due to authorization policies
- Slug validation with soft deletes
- Repository URL validation for SSH format
- Docker permission denied errors
- Host key verification for SSH
- Permission issues for www-data user
- Server status detection

---

## [1.0.0] - 2024-01-02

### Added ✨
- Initial release
- Server management (CRUD operations)
- Project management (CRUD operations)
- Deployment system with Docker
- Basic analytics
- User authentication
- Dashboard with overview
- Real-time server metrics
- Domain management
- Server connectivity checks

### Core Features
- Multi-server support
- Multi-project support
- Docker containerization
- GitHub integration (HTTPS only)
- Deployment history
- Container management (start/stop)
- Basic logging

---

## Version History Summary

| Version | Release Date | Highlights |
|---------|--------------|------------|
| **2.1.0** | 2025-11-09 | Git tracking, Real-time progress, Dockerfile detection |
| **2.0.0** | 2025-11-08 | Editing, PHP 8.4, SSH, Comprehensive docs |
| **1.0.0** | 2024-01-02 | Initial release with core features |

---

## Upgrade Guides

### 2.0.0 → 2.1.0
```bash
cd /var/www/devflow-pro
git pull origin master
php artisan migrate --force
php artisan config:clear
php artisan view:clear
supervisorctl restart all
```

**Breaking Changes:** None! Fully backward compatible.

**New Migrations:**
- `2025_11_09_141554_add_commit_tracking_to_projects_table.php`
- `2025_11_09_144855_create_failed_jobs_table.php`

---

### 1.0.0 → 2.0.0
```bash
cd /var/www/devflow-pro
git pull origin master
php artisan migrate --force
php artisan config:clear
```

**Breaking Changes:** None.

---

## Statistics by Version

### v2.1.0 (Current)
- **Lines of Code:** ~15,000
- **Files:** 150+
- **Features:** 30+
- **Bug Fixes:** 8 (3 critical)
- **New Files:** 4 documentation files

### v2.0.0
- **Lines of Code:** ~12,000
- **Files:** 130+
- **Features:** 25+
- **Documentation:** Complete rewrite

### v1.0.0
- **Lines of Code:** ~8,000
- **Files:** 100+
- **Features:** 15+
- **Initial Release**

---

## Deprecation Notices

### None Currently

All features from v1.0 are still supported and working in v2.1.

---

## Security Updates

### v2.1.0
- No security vulnerabilities fixed
- Git operations use read-only commands
- SSH keys properly scoped to www-data user

### v2.0.0
- Fixed authorization policy issues
- Improved SSH key handling
- Better permission management

---

## Known Issues

### Current (v2.1.0)

**Minor Issues:**
1. Progress percentage may show 90% when deployment completes (refresh fixes it)
2. Very slow network can cause npm timeout even with 20 minutes
3. First deployment always slower (no Docker layer cache)

**Workarounds:**
1. Refresh page after completion
2. Increase timeout further if needed (config.php)
3. Expected behavior - subsequent builds faster

---

## Coming Next

### v2.2.0 (Planned)
- Environment variables UI
- One-click rollback system
- SSL automation with Let's Encrypt
- Project templates library
- Deployment scheduling

### v2.3.0 (Future)
- Team collaboration
- GitHub webhook integration
- Slack/Discord notifications
- Blue-green deployments

---

## Feedback & Contributions

### Report Issues
https://github.com/yourusername/devflow-pro/issues

### Suggest Features
https://github.com/yourusername/devflow-pro/discussions

### Contribute
https://github.com/yourusername/devflow-pro/pulls

---

<div align="center">

**Stay Updated:** Watch the repository for new releases!

[GitHub](https://github.com/yourusername/devflow-pro) • [Documentation](README.md) • [Release Notes](V2.1_RELEASE_NOTES.md)

</div>
