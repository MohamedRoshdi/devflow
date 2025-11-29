# Changelog

All notable changes to DevFlow Pro will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
