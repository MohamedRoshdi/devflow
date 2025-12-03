# Server Provisioning and SSL Management Implementation

## Overview

This implementation adds comprehensive server provisioning and SSL certificate management capabilities to DevFlow Pro. The system allows automated server setup with package installation and complete SSL certificate lifecycle management using Let's Encrypt.

## Features Implemented

### 1. Server Provisioning
- **Automated Package Installation**: Install Nginx, MySQL, PHP, Composer, Node.js
- **System Configuration**: UFW firewall, swap file, SSH security hardening
- **Multiple OS Support**: Ubuntu 22.04, 24.04
- **Progress Tracking**: Real-time provisioning logs with status tracking
- **Non-interactive Mode**: All installations run without user prompts

### 2. SSL Certificate Management
- **Let's Encrypt Integration**: Automatic SSL certificate issuance and renewal
- **Expiry Monitoring**: Track certificate expiration dates with color-coded alerts
- **Automated Renewal**: Scheduled daily renewal checks with notifications
- **Bulk Operations**: Renew multiple expiring certificates at once
- **Certificate Revocation**: Safely revoke certificates when needed

## Database Schema

### New Tables

#### `provisioning_logs`
Tracks all provisioning tasks executed on servers.

```sql
- id: bigint (primary key)
- server_id: bigint (foreign key to servers)
- task: string (e.g., 'install_nginx', 'setup_ssl')
- status: enum('pending', 'running', 'completed', 'failed')
- output: longtext
- error_message: text
- started_at: timestamp
- completed_at: timestamp
- duration_seconds: integer
- created_at, updated_at: timestamps
```

### Modified Tables

#### `servers` - New Columns
```sql
- provisioned_at: timestamp (nullable)
- provision_status: enum('pending', 'provisioning', 'completed', 'failed')
- installed_packages: json (tracks installed packages)
- ssh_password: string (nullable, for password-based SSH)
```

## Services

### ServerProvisioningService

Location: `app/Services/ServerProvisioningService.php`

**Main Methods:**

```php
// Provision server with selected options
provisionServer(Server $server, array $options): void

// Individual package installation methods
installNginx(Server $server): bool
installMySQL(Server $server, string $rootPassword): bool
installPHP(Server $server, string $version = '8.4'): bool
installComposer(Server $server): bool
installNodeJS(Server $server, string $version = '20'): bool

// System configuration
configureFirewall(Server $server, array $ports = [22, 80, 443]): bool
setupSwap(Server $server, int $sizeGB = 2): bool
secureSSH(Server $server): bool

// Generate standalone provisioning script
getProvisioningScript(array $options): string
```

**Usage Example:**

```php
use App\Services\ServerProvisioningService;

$service = app(ServerProvisioningService::class);

$options = [
    'update_system' => true,
    'install_nginx' => true,
    'install_php' => true,
    'php_version' => '8.4',
    'install_mysql' => false,
    'install_composer' => true,
    'install_nodejs' => true,
    'node_version' => '20',
    'configure_firewall' => true,
    'firewall_ports' => [22, 80, 443],
    'setup_swap' => true,
    'swap_size_gb' => 2,
    'secure_ssh' => true,
];

$service->provisionServer($server, $options);
```

### SSLManagementService

Location: `app/Services/SSLManagementService.php`

**Main Methods:**

```php
// Issue new SSL certificate
issueCertificate(Domain $domain): bool

// Renew existing certificate
renewCertificate(Domain $domain): bool

// Check certificate expiry
checkExpiry(Domain $domain): ?Carbon

// Setup auto-renewal cron job
setupAutoRenewal(Server $server): bool

// Get expiring certificates
getExpiringCertificates(int $daysThreshold = 30): Collection

// Bulk renewal
renewExpiringCertificates(int $daysThreshold = 30): array

// Revoke certificate
revokeCertificate(Domain $domain): bool

// Get certificate details
getCertificateInfo(Domain $domain): ?array
```

**Usage Example:**

```php
use App\Services\SSLManagementService;

$service = app(SSLManagementService::class);

// Issue new certificate
$service->issueCertificate($domain);

// Renew certificate
$service->renewCertificate($domain);

// Get expiring certificates
$expiring = $service->getExpiringCertificates(30);

// Bulk renewal
$results = $service->renewExpiringCertificates(30);
```

## Artisan Commands

### 1. Server Provisioning Command

**Command:** `php artisan server:provision`

**Signature:**
```bash
php artisan server:provision {server} [options]
```

**Arguments:**
- `server`: Server ID or hostname

**Options:**
- `--packages=*`: Packages to install (nginx,php,mysql,composer,nodejs)
- `--php-version=8.4`: PHP version to install
- `--node-version=20`: Node.js version to install
- `--mysql-password=`: MySQL root password
- `--swap-size=2`: Swap file size in GB
- `--no-firewall`: Skip firewall configuration
- `--no-swap`: Skip swap configuration
- `--no-ssh-security`: Skip SSH security hardening

**Examples:**

```bash
# Provision server with all packages
php artisan server:provision 1

# Provision with specific packages
php artisan server:provision myserver --packages=nginx,php,composer

# Custom PHP version
php artisan server:provision 1 --php-version=8.3

# Install MySQL with password
php artisan server:provision 1 --packages=mysql --mysql-password=SecurePass123

# Skip firewall setup
php artisan server:provision 1 --no-firewall
```

### 2. Check SSL Expiry Command

**Command:** `php artisan ssl:check-expiry`

**Options:**
- `--days=30`: Check for certificates expiring within this many days
- `--renew`: Automatically renew expiring certificates

**Examples:**

```bash
# Check certificates expiring in 30 days
php artisan ssl:check-expiry

# Check and auto-renew certificates expiring in 14 days
php artisan ssl:check-expiry --days=14 --renew

# Check critical certificates (7 days)
php artisan ssl:check-expiry --days=7
```

### 3. Renew SSL Command

**Command:** `php artisan ssl:renew`

**Arguments:**
- `domain`: (Optional) Specific domain to renew

**Options:**
- `--all`: Renew all certificates regardless of expiry
- `--force`: Force renewal even if not expiring soon

**Examples:**

```bash
# Renew all expiring certificates (30 days threshold)
php artisan ssl:renew

# Renew specific domain
php artisan ssl:renew example.com

# Force renewal for specific domain
php artisan ssl:renew example.com --force

# Renew ALL certificates
php artisan ssl:renew --all
```

## Scheduled Tasks

Added to `routes/console.php`:

```php
// Check SSL expiry and auto-renew daily at 3 AM
Schedule::command('ssl:check-expiry --days=14 --renew')->daily()->at('03:00');
```

The scheduler automatically:
- Checks for certificates expiring within 14 days
- Attempts to renew expiring certificates
- Sends notifications for critical certificates
- Logs all renewal attempts

## Livewire Components

### 1. ServerProvisioning Component

**Location:** `app/Livewire/Servers/ServerProvisioning.php`

**Features:**
- Package selection (checkboxes for each package)
- Configuration options (PHP version, Node.js version, swap size)
- Real-time provisioning logs display
- Download provisioning script
- Status tracking with color-coded badges

**Blade View:** `resources/views/livewire/servers/server-provisioning.blade.php`

**Usage in Blade:**
```blade
<livewire:servers.server-provisioning :server="$server" />
```

### 2. SSLManager Component

**Location:** `app/Livewire/SSL/SSLManager.php`

**Features:**
- SSL certificate list with filtering
- Status badges with color coding:
  - 🟢 Green: > 30 days until expiry
  - 🟡 Yellow: 7-30 days until expiry
  - 🔴 Red: < 7 days until expiry or expired
- Statistics cards (Total, Active, Expiring, Expired, Failed)
- Critical certificates alert banner
- Bulk renewal operations
- Certificate detail modal
- Individual certificate actions (View, Renew, Revoke, Check Expiry)

**Blade View:** `resources/views/livewire/ssl/ssl-manager.blade.php`

**Usage in Blade:**
```blade
<livewire:ssl.ssl-manager />
```

## Notifications

### 1. SSLCertificateExpiring

**Trigger:** Daily check finds certificates expiring within threshold

**Channels:** Email + Database

**Content:**
- Domain name
- Server name
- Expiry date
- Days remaining
- Urgency level (🟢/🟡/🟠/🔴)
- Auto-renewal status

### 2. SSLCertificateRenewed

**Trigger:** Successful certificate renewal

**Channels:** Email + Database

**Content:**
- Domain name
- Server name
- New expiry date
- Days until next expiry

### 3. ServerProvisioningCompleted

**Trigger:** Server provisioning completes (success or failure)

**Channels:** Email + Database

**Content:**
- Server name and IP
- Installed packages list
- Success/failure status
- Error message (if failed)

## Installation Steps

### 1. Run Migrations

```bash
php artisan migrate
```

This creates:
- `provisioning_logs` table
- Adds provisioning columns to `servers` table

### 2. Setup Scheduler

Ensure the Laravel scheduler is running:

```bash
# Add to crontab
* * * * * cd /path-to-devflow-pro && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Install Required System Packages

On the DevFlow Pro server (not managed servers):

```bash
# SSH/SSHPASS for remote server access
sudo apt-get install -y sshpass

# If not already installed
sudo apt-get install -y openssh-client
```

### 4. Configure Server SSH Access

Each server must have either:
- **SSH Key** (recommended): Store private key in `servers.ssh_key`
- **SSH Password**: Store password in `servers.ssh_password`

Example:
```php
$server = Server::create([
    'name' => 'Production Server',
    'ip_address' => '192.168.1.100',
    'port' => 22,
    'username' => 'root',
    'ssh_key' => file_get_contents('/path/to/private_key'), // OR
    'ssh_password' => 'secure_password', // Use one or the other
]);
```

## Security Considerations

### SSH Security
- The provisioning script can optionally disable password authentication
- Forces key-based authentication for enhanced security
- Disables root login with password

### MySQL Security
- Auto-generated strong passwords (16 bytes = 32 hex characters)
- Users must save the password securely
- Passwords stored in `servers.metadata` if needed

### SSL Certificate Security
- Uses Let's Encrypt trusted certificates
- Automatic renewal prevents expiry
- Certificates stored in standard `/etc/letsencrypt/` paths
- Private keys secured with proper permissions

### Firewall Configuration
- Only specified ports are opened (default: 22, 80, 443)
- UFW (Uncomplicated Firewall) configured and enabled
- All other ports blocked by default

## Provisioning Process Flow

```
1. User initiates provisioning
   ↓
2. ServerProvisioningService validates options
   ↓
3. Server status → 'provisioning'
   ↓
4. For each selected package:
   - Create ProvisioningLog (status: 'running')
   - Execute SSH commands
   - Update log with output/errors
   - Mark as 'completed' or 'failed'
   ↓
5. Update server:
   - provision_status → 'completed' or 'failed'
   - provisioned_at → now()
   - installed_packages → ['nginx', 'php-8.4', ...]
   ↓
6. Send notification to server owner
```

## SSL Certificate Lifecycle

```
1. Domain added to project
   ↓
2. Issue Certificate
   - Install certbot if needed
   - Run certbot with --nginx flag
   - Extract certificate paths
   - Create SSLCertificate record
   - Update Domain record
   ↓
3. Active Certificate
   - Daily expiry checks
   - Email notifications at 30, 14, 7, 3, 1 day thresholds
   ↓
4. Auto-Renewal (30 days before expiry)
   - Scheduled task runs ssl:check-expiry
   - Renews expiring certificates
   - Updates expiry dates
   - Sends success notification
   ↓
5. Certificate Renewed
   - New 90-day validity
   - Cycle repeats
```

## Error Handling

### Provisioning Errors
- All errors logged to `provisioning_logs.error_message`
- Server status set to 'failed'
- User notified via email and database
- Detailed error output saved for debugging

### SSL Errors
- Failed issuance/renewal logged to `ssl_certificates.renewal_error`
- Status set to 'failed'
- Last attempt timestamp recorded
- User notified immediately

### SSH Connection Errors
- Timeout after 30 seconds for provisioning commands
- Timeout after 10 seconds for metrics collection
- Fallback to localhost commands if IP is local
- Graceful degradation with error messages

## Monitoring and Logs

### View Provisioning Logs

Via UI:
- Server detail page → Provisioning tab
- Shows all tasks with status, duration, output

Via Database:
```php
$logs = ProvisioningLog::where('server_id', $serverId)
    ->orderBy('created_at', 'desc')
    ->get();
```

### View SSL Certificate Status

Via UI:
- SSL Manager page (dedicated route)
- Shows all certificates with expiry dates
- Color-coded status indicators

Via Command:
```bash
php artisan ssl:check-expiry
```

## API Endpoints (Optional)

If you want to expose provisioning/SSL management via API:

```php
// routes/api.php

Route::middleware('auth:sanctum')->group(function () {
    // Provisioning
    Route::post('/servers/{server}/provision', [ServerController::class, 'provision']);
    Route::get('/servers/{server}/provisioning-logs', [ServerController::class, 'provisioningLogs']);

    // SSL Management
    Route::post('/domains/{domain}/ssl/issue', [SSLController::class, 'issue']);
    Route::post('/domains/{domain}/ssl/renew', [SSLController::class, 'renew']);
    Route::post('/domains/{domain}/ssl/revoke', [SSLController::class, 'revoke']);
    Route::get('/ssl/expiring', [SSLController::class, 'expiring']);
});
```

## Testing

### Test Server Provisioning

```php
// tests/Feature/ServerProvisioningTest.php

public function test_can_provision_server()
{
    $server = Server::factory()->create([
        'ip_address' => '127.0.0.1', // Use localhost for testing
    ]);

    $service = app(ServerProvisioningService::class);

    $service->provisionServer($server, [
        'install_nginx' => true,
        'install_php' => true,
        'php_version' => '8.4',
    ]);

    $server->refresh();

    $this->assertEquals('completed', $server->provision_status);
    $this->assertContains('nginx', $server->installed_packages);
    $this->assertContains('php-8.4', $server->installed_packages);
}
```

### Test SSL Certificate Management

```php
// tests/Feature/SSLManagementTest.php

public function test_can_get_expiring_certificates()
{
    SSLCertificate::factory()->create([
        'expires_at' => now()->addDays(15), // Expiring soon
    ]);

    SSLCertificate::factory()->create([
        'expires_at' => now()->addDays(60), // Not expiring
    ]);

    $service = app(SSLManagementService::class);
    $expiring = $service->getExpiringCertificates(30);

    $this->assertCount(1, $expiring);
}
```

## Troubleshooting

### Provisioning Issues

**Problem:** SSH connection timeout
- **Solution:** Check firewall allows port 22, verify SSH credentials, ensure server is online

**Problem:** Package installation fails
- **Solution:** Check provisioning logs for specific errors, verify internet connectivity on target server

**Problem:** Provisioning stuck in "provisioning" status
- **Solution:** Check Laravel queue workers are running, review provisioning logs for errors

### SSL Issues

**Problem:** Certificate issuance fails
- **Solution:** Verify domain DNS points to server, ensure Nginx is installed, check port 80/443 are accessible

**Problem:** Auto-renewal not working
- **Solution:** Verify scheduler is running (`php artisan schedule:work`), check cron configuration

**Problem:** Certificate expired despite auto-renewal
- **Solution:** Check `renewal_error` in `ssl_certificates` table, verify certbot is installed on server

## Performance Considerations

### Provisioning
- Each package installation can take 1-5 minutes
- Full server provisioning: 10-20 minutes depending on packages
- Run as background job to avoid HTTP timeouts
- Use queue workers for asynchronous processing

### SSL Operations
- Certificate issuance: 30-60 seconds
- Certificate renewal: 30-60 seconds
- Bulk renewal: Process in batches to avoid memory issues
- Schedule during low-traffic periods (2-4 AM)

## Future Enhancements

### Provisioning
- [ ] Support for CentOS/RHEL servers
- [ ] Docker installation and configuration
- [ ] Redis installation option
- [ ] PostgreSQL installation option
- [ ] Custom script execution
- [ ] Rollback failed provisions

### SSL Management
- [ ] Support for wildcard certificates
- [ ] Custom CA integration
- [ ] Certificate monitoring API
- [ ] Multi-domain certificates (SAN)
- [ ] Certificate usage analytics
- [ ] Integration with Cloudflare SSL

## Support

For issues or questions:
1. Check provisioning logs in database
2. Review Laravel logs: `storage/logs/laravel.log`
3. Run commands with `--verbose` flag for detailed output
4. Check server accessibility with `ping` and `ssh` tests

---

**Version:** 1.0.0
**Last Updated:** 2025-12-03
**Compatibility:** Laravel 12, PHP 8.4, Ubuntu 22.04+
