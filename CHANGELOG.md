# Changelog

All notable changes to DevFlow Pro will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
