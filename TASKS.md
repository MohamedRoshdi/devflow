# DevFlow Pro — Platform Integration Tasks

> Improvement roadmap for supporting bare-metal + PostgreSQL + Redis deployments.
> Source: `/home/roshdy/Work/empire/projects/stores/DEPLOYMENT-PLAN.md` → "DevFlow Improvement Roadmap"
>
> **Context:** DevFlow manages deployments for the Store Platform — a multi-vertical SaaS
> (e-stores, fashion, general) running on bare-metal VPS with PostgreSQL + Redis (no Docker on app server).

---

## Current State (What Already Works)

Before starting, note what's **already implemented**:

| Feature | Status | File |
|---------|--------|------|
| `DatabaseBackupService` — PostgreSQL backup (`pg_dump`) | **Done** | `app/Services/DatabaseBackupService.php` |
| `DatabaseBackupService` — PostgreSQL restore (`pg_restore`) | **Done** | Same file |
| `InstallScriptGenerator` — PostgreSQL install | **Done** | `app/Services/InstallScriptGenerator.php` (via `db_driver => 'pgsql'`) |
| `InstallScriptGenerator` — Redis install | **Done** | Same file (via `enable_redis => true`) |
| `InstallScriptGenerator` — Supervisor config | **Done** | Same file (via `enable_supervisor => true`) |
| `InstallScriptGenerator` — Nginx vhost | **Done** | Same file (generates server block inline) |
| Docker deployments | **Working** | `app/Jobs/DeployProjectJob.php` → `handleDirectDeployment()` |
| Pipeline deployments (SSH commands) | **Working** | `app/Services/CICD/PipelineExecutionService.php` |
| Security Guardian | **Working** | `app/Services/Security/SecurityGuardianService.php` |
| Server monitoring | **Working** | `app/Services/ServerMetricsService.php` |
| Webhooks | **Working** | `app/Services/WebhookService.php` |

---

## Priority 1: Launch Blockers (~8 hours)

### Task 1.1: Add PostgreSQL + Redis to ServerProvisioningService (~5 hours)

**File:** `app/Services/ServerProvisioningService.php`

**Problem:** `provisionServer()` has `install_mysql` but no `install_postgresql` or `install_redis` options. The `InstallScriptGenerator` already knows how to generate bash scripts for PostgreSQL/Redis, but `ServerProvisioningService` can't provision them via SSH.

**What to do:**

1. Add two new options to `provisionServer()`:
   ```php
   'install_postgresql' => $options['install_postgresql'] ?? false,
   'install_redis' => $options['install_redis'] ?? false,
   ```

2. Create `installPostgreSQL(Server $server, string $version = '16', ?string $password = null): bool`
   - Install PostgreSQL via apt (same pattern as `installMySQL()`)
   - Commands: add pgdg apt repo, `apt install postgresql-{version}`, create application user, configure `pg_hba.conf` for password auth
   - Create initial databases via SQL: `CREATE DATABASE {name};` + `GRANT ALL PRIVILEGES`
   - Optionally accept an array of database names to create (for multi-vertical: `['estores_db', 'admin_db']`)

3. Create `installRedis(Server $server, ?string $password = null, int $maxMemoryMB = 512): bool`
   - Install via apt: `apt install redis-server`
   - Configure `/etc/redis/redis.conf`: `bind 127.0.0.1`, `requirepass {password}`, `maxmemory {mb}mb`, `maxmemory-policy allkeys-lru`
   - Enable and start: `systemctl enable redis-server && systemctl start redis-server`

4. Add firewall port for Redis (6379) when `install_redis` is true — but only bind to localhost (no external access by default)

5. Update `getProvisioningScript()` to include PostgreSQL and Redis in the generated bash script

6. Update the provisioning UI (Livewire component) to show PostgreSQL/Redis checkboxes alongside the existing MySQL checkbox

**Test:** Provision a test server and verify PostgreSQL + Redis are installed and accessible.

**References:**
- Existing MySQL install: `installMySQL()` method in same file
- `InstallScriptGenerator` PostgreSQL script: check `getPostgresInstallation()` method for the bash commands to reuse
- Server model already has `installed_packages` JSON field — store `['postgresql-16', 'redis-server']` there

---

### Task 1.2: Add npm Build to Bare-Metal Deploy Pipeline (~1 hour)

**File:** `app/Jobs/DeployProjectJob.php`

**Problem:** The `handleDirectDeployment()` method (Docker path) runs `composer install` but there's no `npm ci && npm run build` step for bare-metal (non-Docker) deploys. The pipeline path (`PipelineExecutionService`) can run arbitrary commands, but users shouldn't have to manually configure npm build — it should be default for Laravel projects.

**What to do:**

1. In `DeployProjectJob`, after `composer install` in the deploy sequence, add:
   ```
   npm ci --no-audit --no-fund
   npm run build
   ```
   Only run if `package.json` exists in the project root (check via SSH: `test -f /var/www/{slug}/package.json`)

2. Also update the default pipeline template for Laravel projects in `PipelineBuilderService` to include npm build as a default stage

**Test:** Deploy a Laravel project with Vite assets and verify the `public/build/` directory is created.

---

### Task 1.3: Add Maintenance Mode to Deploy Pipeline (~1 hour)

**File:** `app/Jobs/DeployProjectJob.php`

**What to do:**

1. Before running migrations, put the app in maintenance mode:
   ```
   php artisan down --retry=30 --secret="devflow-deploy"
   ```

2. After all deploy steps complete, bring it back up:
   ```
   php artisan up
   ```

3. If deploy fails, ensure `php artisan up` is called in the catch/finally block

**Note:** This is a stopgap until symlink-based releases (Task 2.1) are implemented.

---

## Priority 2: Zero-Downtime Deploy (~13 hours)

### Task 2.1: Symlink-Based Release Deployments (~6 hours)

**File:** `app/Jobs/DeployProjectJob.php` (or new `app/Services/ReleaseDeploymentService.php`)

**Problem:** Current bare-metal deploy does `git pull` in-place, overwriting live code. This causes downtime during `composer install` and `npm run build`.

**What to do:**

1. Create a new service `ReleaseDeploymentService` with the release directory pattern:

   ```
   /var/www/
   ├── {slug} -> /var/www/releases/{slug}/20260218120000  (symlink — Nginx points here)
   ├── releases/{slug}/
   │   ├── 20260219100000/   (current)
   │   ├── 20260218120000/   (previous)
   │   └── ...
   └── shared/{slug}/
       ├── .env
       └── storage/
   ```

2. Deploy flow:
   - `git clone --depth 1 -b {branch} {repo} /var/www/releases/{slug}/{timestamp}`
   - Link shared files: `ln -sf /var/www/shared/{slug}/.env {release}/.env` and `ln -sf /var/www/shared/{slug}/storage {release}/storage`
   - `cd {release} && composer install --no-dev --optimize-autoloader --no-interaction`
   - `cd {release} && npm ci && npm run build` (if package.json exists)
   - `cd {release} && php artisan migrate --force --no-interaction`
   - `cd {release} && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache`
   - **Atomic switch:** `ln -sfn {release} /var/www/{slug}` (this is instant — zero downtime)
   - `sudo systemctl reload php8.4-fpm`
   - `cd {release} && php artisan queue:restart`
   - Cleanup: keep last 5 releases, delete older ones

3. Add project config option: `deploy_strategy` enum (`in_place`, `release`) — default to `release` for new projects

4. Store release paths in deployments table for rollback support

**Test:** Deploy twice and verify the symlink switches atomically. Verify old release still exists for rollback.

**Reference:** The manual deploy script in `/home/roshdy/Work/empire/projects/stores/INFRASTRUCTURE.md` → "Fallback: Manual Deploy Script" has the exact bash flow to implement.

---

### Task 2.2: Nginx Vhost Generator Service (~4 hours)

**File:** New `app/Services/NginxConfigService.php`

**Problem:** `InstallScriptGenerator` generates Nginx config as part of a full install script, but there's no way to add/update/remove individual vhosts after initial setup.

**What to do:**

1. Create `NginxConfigService` with methods:
   - `generateVhost(Project $project, Domain $domain): string` — returns Nginx server block content
   - `installVhost(Server $server, Project $project, Domain $domain): bool` — writes config to `/etc/nginx/sites-available/{domain}`, creates symlink in `sites-enabled/`, runs `nginx -t && systemctl reload nginx`
   - `removeVhost(Server $server, Domain $domain): bool` — removes config and symlink, reloads
   - `generateWildcardVhost(Server $server, string $baseDomain): string` — for `*.store.eg` style wildcard configs
   - `testConfig(Server $server): bool` — runs `nginx -t` via SSH

2. Vhost templates (use Blade or simple string interpolation):
   - **App vhost:** `app.{domain}` → PHP-FPM, security headers, static asset caching
   - **Wildcard storefront:** `*.{domain}` → same root, 90-day cache for storefronts
   - **Custom domain:** individual store domains → same root, different server_name

3. SSL config should reference Cloudflare origin cert path (`/etc/ssl/cloudflare/{domain}.pem`)

4. Integrate with `DomainService` — when a domain is created, auto-generate and install the vhost

**Reference:** See `/home/roshdy/Work/empire/projects/stores/INFRASTRUCTURE.md` → "Nginx Configuration" for the exact server block templates to use.

---

### Task 2.3: Supervisor Config Per Project (~3 hours)

**File:** New `app/Services/SupervisorConfigService.php`

**Problem:** `InstallScriptGenerator` generates Supervisor config inline during install, but there's no way to manage configs per-project after setup.

**What to do:**

1. Create `SupervisorConfigService` with methods:
   - `generateConfig(Project $project, array $options = []): string` — returns `[program:]` block
   - `installConfig(Server $server, Project $project, array $options = []): bool` — writes to `/etc/supervisor/conf.d/{slug}-*.conf`, runs `supervisorctl reread && supervisorctl update`
   - `removeConfig(Server $server, Project $project): bool` — stops workers, removes config, updates
   - `restartWorkers(Server $server, Project $project): bool` — `supervisorctl restart {slug}-*`
   - `getWorkerStatus(Server $server, Project $project): array` — parse `supervisorctl status` output

2. Config options:
   - `queue_names` — array of queue names (default: `['default']`)
   - `num_workers` — workers per queue (default: 2)
   - `max_tries` — retry count (default: 3)
   - `max_time` — worker max lifetime seconds (default: 3600)
   - `memory_limit` — in MB (default: 128)

3. Integrate with deploy pipeline — after deploy, call `restartWorkers()` instead of manual `queue:restart`

**Reference:** See `/home/roshdy/Work/empire/projects/stores/INFRASTRUCTURE.md` → "Queue & Scheduler" for config template.

---

## Priority 3: Operations (~12 hours)

### Task 3.1: Server Role Field (~2 hours)

**File:** `app/Models/Server.php` + new migration

**What to do:**

1. Add migration: `role` enum column (`app`, `database`, `control`, `mixed`) — default `mixed`
2. Update Server model `$fillable` and `$casts`
3. Filter available actions in UI by role:
   - `app` servers: deploy, nginx, supervisor, PHP-FPM
   - `database` servers: backup, restore, create database
   - `control` servers: DevFlow management only
   - `mixed`: all actions (default for single-server setups)
4. Show role badge in server list dashboard

---

### Task 3.2: Cloudflare DNS API Integration (~6 hours)

**File:** `app/Services/DomainService.php`

**What to do:**

1. Replace the stubbed `configureCloudflare()` with real Cloudflare API calls
2. Use Cloudflare API v4: `https://api.cloudflare.com/client/v4/`
3. Required env vars: `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_ZONE_ID`
4. Implement:
   - `configureCloudflare(Domain $domain, array $config): bool` — creates A/CNAME record
   - `removeDNSRecords(Domain $domain): bool` — deletes the record
   - `listDNSRecords(string $zoneId): array` — for verification
   - `updateDNSRecord(Domain $domain, string $ip): bool` — for IP changes
5. Support record types: A (for VPS IP), CNAME (for custom domains), TXT (for verification)
6. Store Cloudflare record ID in `domains.metadata` JSON for future updates/deletes

**API Reference:** https://developers.cloudflare.com/api/operations/dns-records-for-a-zone-create-dns-record

---

### Task 3.3: Cloudflare Origin Cert Upload (~2 hours)

**File:** New `app/Services/SSLCertificateService.php` (or extend existing `SSLManagementService`)

**What to do:**

1. UI: file upload for `.pem` and `.key` files (or paste textarea)
2. Upload to server via SCP: `/etc/ssl/cloudflare/{domain}.pem` and `.key`
3. Set permissions: `chmod 600`
4. Reload Nginx after upload
5. Track cert expiry in `domains` table (Cloudflare origin certs are 15 years but still worth tracking)

---

### Task 3.4: Per-Project Cron Management (~2 hours)

**File:** New `app/Services/CronConfigService.php`

**What to do:**

1. Generate cron entry: `* * * * * {user} cd /var/www/{slug} && php artisan schedule:run >> /dev/null 2>&1`
2. Write to `/etc/cron.d/{slug}-scheduler` via SSH
3. Enable/disable per project via dashboard toggle
4. Remove cron entry when project is deactivated/deleted

---

## Priority 4: Polish (~10 hours)

### Task 4.1: PHP-FPM Pool Per Project (~3 hours)

**File:** New `app/Services/PhpFpmPoolService.php`

**What to do:**

1. Generate per-project pool config at `/etc/php/8.4/fpm/pool.d/{slug}.conf`
2. Separate socket per project: `/run/php/{slug}.sock`
3. Configurable: `pm.max_children`, `pm.start_servers`, worker user, memory limit
4. Update Nginx vhost to use project-specific socket
5. Useful for ProMax tenant isolation (dedicated FPM pool per high-value store)

---

### Task 4.2: Pre-Migration Auto-Backup (~2 hours)

**File:** `app/Jobs/DeployProjectJob.php` or `ReleaseDeploymentService`

**What to do:**

1. Before `php artisan migrate --force`, trigger a database backup via `DatabaseBackupService`
2. Tag backup as `pre-migration-{deployment_id}`
3. If migration fails, offer one-click restore from this backup
4. Configurable: `auto_backup_before_migrate` project setting (default: true)

---

### Task 4.3: One-Click Deploy Rollback (~2 hours)

**File:** `app/Services/RollbackService.php` (already exists — extend it)

**What to do:**

1. With symlink-based releases (Task 2.1), rollback = switch symlink to previous release directory
2. Flow: `ln -sfn /var/www/releases/{slug}/{previous_timestamp} /var/www/{slug}` + reload FPM + restart queue
3. UI: "Rollback" button on deployment detail page
4. Track which release is current in `deployments` table

---

### Task 4.4: Per-Project Log Filtering (~3 hours)

**File:** Extend `app/Services/LogManagerService.php`

**What to do:**

1. Filter logs by project: read from `/var/www/{slug}/storage/logs/laravel.log` via SSH
2. Show deploy logs per project (from `deployments.output` column)
3. Show queue worker logs per project (from Supervisor stdout log path)
4. UI: tabbed view — Application Logs | Deploy Logs | Queue Logs

---

## Architecture Notes

### Key File Paths in DevFlow

| File | Purpose |
|------|---------|
| `app/Services/ServerProvisioningService.php` | SSH-based server provisioning (Tasks 1.1) |
| `app/Services/DatabaseBackupService.php` | DB backup/restore — **already has pg_dump** |
| `app/Services/InstallScriptGenerator.php` | Bash script generation — **already has PostgreSQL/Redis/Nginx/Supervisor** |
| `app/Jobs/DeployProjectJob.php` | Deploy pipeline (Tasks 1.2, 1.3, 2.1) |
| `app/Services/DomainService.php` | Domain + DNS management (Task 3.2) |
| `app/Services/RollbackService.php` | Deploy rollback (Task 4.3) |
| `app/Services/CICD/PipelineExecutionService.php` | Pipeline stage execution via SSH |
| `app/Models/Server.php` | Server model (Task 3.1) |

### Platform Architecture (What DevFlow Deploys)

```
VPS 2 (Platform — Bare-Metal):
  Nginx (native) → PHP-FPM 8.4 → Laravel app
  PostgreSQL 16 (native)
  ├── admin_db
  ├── estores_db (electronics)
  ├── closes_db  (fashion — later)
  └── store_*_db (ProMax — on demand)
  Redis 7 (native)
  Supervisor (queue workers)
  Cron (Laravel scheduler)
```

All verticals share ONE codebase and ONE VPS. Each vertical gets its own PostgreSQL database. DevFlow deploys to this bare-metal server via SSH (no Docker on VPS 2).

### What DevFlow Does NOT Need to Handle

- ProMax database creation (`CREATE DATABASE`) — handled by E-Stores app
- Custom domain DNS verification — handled by E-Stores app
- Tenant routing — handled by E-Stores app middleware
- Marketing site deploy — handled by Cloudflare Pages
- Wildcard SSL — handled by Cloudflare (origin certs)

---

*Created: February 2026*
*Source: stores/DEPLOYMENT-PLAN.md → DevFlow Improvement Roadmap*
