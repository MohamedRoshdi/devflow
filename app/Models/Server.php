<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServerRole;
use App\Mappers\HealthScoreMapper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property bool $guardian_enabled
 * @property \Illuminate\Support\Carbon|null $last_guardian_scan_at
 * @property \Illuminate\Support\Carbon|null $last_baseline_at
 * @property \Illuminate\Support\Carbon|null $last_hardening_at
 * @property string|null $hardening_level
 */

/**
 * @property array<int, string>|null $installed_packages
 */
class Server extends Model
{
    /** @use HasFactory<\Database\Factories\ServerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Fields that should have HTML tags stripped for XSS protection.
     *
     * @var array<int, string>
     */
    protected array $sanitizeFields = [
        'name',
        'hostname',
        'location_name',
        'os',
    ];

    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'hostname',
        'ip_address',
        'port',
        'username',
        'ssh_key',
        'ssh_password',
        'status',
        'role',
        'is_current_server',
        'os',
        'cpu_cores',
        'memory_gb',
        'disk_gb',
        'docker_installed',
        'docker_version',
        'ufw_installed',
        'ufw_enabled',
        'fail2ban_installed',
        'fail2ban_enabled',
        'security_score',
        'last_security_scan_at',
        'last_threat_scan_at',
        'active_incidents_count',
        'lockdown_mode',
        'auto_remediation_enabled',
        'latitude',
        'longitude',
        'location_name',
        'last_ping_at',
        'provisioned_at',
        'provision_status',
        'installed_packages',
        'metadata',
        'guardian_enabled',
        'last_guardian_scan_at',
        'last_baseline_at',
        'last_hardening_at',
        'hardening_level',
        'region_id',
    ];

    protected $hidden = [
        'ssh_key',
        'ssh_password',
    ];

    protected function casts(): array
    {
        return [
            'is_current_server' => 'boolean',
            'docker_installed' => 'boolean',
            'ufw_installed' => 'boolean',
            'ufw_enabled' => 'boolean',
            'fail2ban_installed' => 'boolean',
            'fail2ban_enabled' => 'boolean',
            'security_score' => 'integer',
            'last_security_scan_at' => 'datetime',
            'last_threat_scan_at' => 'datetime',
            'active_incidents_count' => 'integer',
            'lockdown_mode' => 'boolean',
            'auto_remediation_enabled' => 'boolean',
            'last_ping_at' => 'datetime',
            'provisioned_at' => 'datetime',
            'installed_packages' => 'array',
            'metadata' => 'array',
            'cpu_cores' => 'integer',
            'memory_gb' => 'integer',
            'disk_gb' => 'integer',
            'port' => 'integer',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'guardian_enabled' => 'boolean',
            'last_guardian_scan_at' => 'datetime',
            'last_baseline_at' => 'datetime',
            'last_hardening_at' => 'datetime',
            'role' => ServerRole::class,
        ];
    }

    /**
     * Boot the model and register event handlers.
     */
    protected static function booted(): void
    {
        static::saving(function (Server $server) {
            $server->sanitizeInputs();
        });
    }

    /**
     * Sanitize input fields by stripping HTML tags to prevent XSS attacks.
     *
     * This provides defense-in-depth alongside Blade's {{ }} escaping.
     */
    protected function sanitizeInputs(): void
    {
        foreach ($this->sanitizeFields as $field) {
            if (isset($this->attributes[$field]) && is_string($this->attributes[$field])) {
                $this->attributes[$field] = strip_tags($this->attributes[$field]);
            }
        }
    }

    // Relationships
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<Deployment, $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * @return HasMany<ServerMetric, $this>
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(ServerMetric::class);
    }

    /**
     * @return BelongsToMany<SSHKey, $this>
     */
    public function sshKeys(): BelongsToMany
    {
        return $this->belongsToMany(SSHKey::class, 'server_ssh_key')
            ->withPivot('deployed_at')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<ServerTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ServerTag::class, 'server_tag_pivot', 'server_id', 'tag_id');
    }

    /**
     * @return HasMany<SSLCertificate, $this>
     */
    public function sslCertificates(): HasMany
    {
        return $this->hasMany(SSLCertificate::class);
    }

    /**
     * @return HasMany<ResourceAlert, $this>
     */
    public function resourceAlerts(): HasMany
    {
        return $this->hasMany(ResourceAlert::class);
    }

    /**
     * @return HasMany<AlertHistory, $this>
     */
    public function alertHistory(): HasMany
    {
        return $this->hasMany(AlertHistory::class);
    }

    /**
     * @return HasMany<ServerBackup, $this>
     */
    public function backups(): HasMany
    {
        return $this->hasMany(ServerBackup::class);
    }

    /**
     * @return HasMany<ServerBackupSchedule, $this>
     */
    public function backupSchedules(): HasMany
    {
        return $this->hasMany(ServerBackupSchedule::class);
    }

    /**
     * @return HasMany<FirewallRule, $this>
     */
    public function firewallRules(): HasMany
    {
        return $this->hasMany(FirewallRule::class);
    }

    /**
     * @return HasMany<SecurityEvent, $this>
     */
    public function securityEvents(): HasMany
    {
        return $this->hasMany(SecurityEvent::class);
    }

    /**
     * @return HasOne<SshConfiguration, $this>
     */
    public function sshConfiguration(): HasOne
    {
        return $this->hasOne(SshConfiguration::class);
    }

    /**
     * @return HasMany<SecurityScan, $this>
     */
    public function securityScans(): HasMany
    {
        return $this->hasMany(SecurityScan::class);
    }

    /**
     * @return HasOne<SecurityScan, $this>
     */
    public function latestSecurityScan(): HasOne
    {
        return $this->hasOne(SecurityScan::class)->latestOfMany();
    }

    /**
     * @return HasMany<SecurityIncident, $this>
     */
    public function securityIncidents(): HasMany
    {
        return $this->hasMany(SecurityIncident::class);
    }

    /**
     * @return HasMany<SecurityIncident, $this>
     */
    public function activeSecurityIncidents(): HasMany
    {
        return $this->hasMany(SecurityIncident::class)->active();
    }

    /**
     * @return HasOne<SecurityIncident, $this>
     */
    public function latestSecurityIncident(): HasOne
    {
        return $this->hasOne(SecurityIncident::class)->latestOfMany();
    }

    /**
     * @return HasOne<ServerMetric, $this>
     */
    public function latestMetric(): HasOne
    {
        return $this->hasOne(ServerMetric::class)->latestOfMany('recorded_at');
    }

    /**
     * @return HasMany<ProvisioningLog, $this>
     */
    public function provisioningLogs(): HasMany
    {
        return $this->hasMany(ProvisioningLog::class);
    }

    /**
     * @return HasOne<ProvisioningLog, $this>
     */
    public function latestProvisioningLog(): HasOne
    {
        return $this->hasOne(ProvisioningLog::class)->latestOfMany();
    }

    /**
     * @return HasMany<SecurityBaseline, $this>
     */
    public function securityBaselines(): HasMany
    {
        return $this->hasMany(SecurityBaseline::class);
    }

    /**
     * @return HasOne<SecurityBaseline, $this>
     */
    public function latestBaseline(): HasOne
    {
        return $this->hasOne(SecurityBaseline::class)->latestOfMany();
    }

    /**
     * @return HasMany<SecurityPrediction, $this>
     */
    public function securityPredictions(): HasMany
    {
        return $this->hasMany(SecurityPrediction::class);
    }

    /**
     * @return HasMany<SecurityPrediction, $this>
     */
    public function activePredictions(): HasMany
    {
        return $this->hasMany(SecurityPrediction::class)->where('status', SecurityPrediction::STATUS_ACTIVE);
    }

    /**
     * @return HasMany<RemediationLog, $this>
     */
    public function remediationLogs(): HasMany
    {
        return $this->hasMany(RemediationLog::class);
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return HasMany<ServerCommandHistory, $this>
     */
    public function commandHistory(): HasMany
    {
        return $this->hasMany(ServerCommandHistory::class);
    }

    /**
     * @return HasOne<ServerCommandHistory, $this>
     */
    public function latestCommand(): HasOne
    {
        return $this->hasOne(ServerCommandHistory::class)->latestOfMany();
    }

    // Status helpers
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }

    public function getStatusColorAttribute(): string
    {
        return HealthScoreMapper::statusToColor($this->status);
    }

    public function getSecurityScoreColorAttribute(): string
    {
        return match (true) {
            $this->security_score >= 91 => 'emerald',
            $this->security_score >= 81 => 'green',
            $this->security_score >= 61 => 'yellow',
            $this->security_score >= 41 => 'orange',
            $this->security_score !== null => 'red',
            default => 'gray',
        };
    }

    public function getSecurityRiskLevelAttribute(): string
    {
        return match (true) {
            $this->security_score >= 91 => 'secure',
            $this->security_score >= 81 => 'low',
            $this->security_score >= 61 => 'medium',
            $this->security_score >= 41 => 'high',
            $this->security_score !== null => 'critical',
            default => 'unknown',
        };
    }

    public function isProvisioned(): bool
    {
        return $this->provision_status === 'completed';
    }

    public function isProvisioning(): bool
    {
        return $this->provision_status === 'provisioning';
    }

    public function hasPackageInstalled(string $package): bool
    {
        return in_array($package, $this->installed_packages ?? []);
    }

    /**
     * Check if this server is the current server (where DevFlow is running)
     */
    public function isCurrentServer(): bool
    {
        return $this->is_current_server === true;
    }

    /**
     * Check if commands should be executed locally (no SSH needed)
     */
    public function shouldExecuteLocally(): bool
    {
        if ($this->is_current_server) {
            return true;
        }

        // Also check if IP matches current server
        $localIPs = ['127.0.0.1', '::1', 'localhost'];
        if (in_array($this->ip_address, $localIPs)) {
            return true;
        }

        // Check if IP matches server's own IP
        $serverIP = gethostbyname((string) gethostname());
        if ($this->ip_address === $serverIP) {
            return true;
        }

        return false;
    }
}
