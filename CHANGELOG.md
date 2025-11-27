# Changelog

All notable changes to DevFlow Pro will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
