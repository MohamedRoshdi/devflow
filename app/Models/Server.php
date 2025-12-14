<?php

declare(strict_types=1);

namespace App\Models;

use App\Mappers\HealthScoreMapper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property array<int, string>|null $installed_packages
 */
class Server extends Model
{
    /** @use HasFactory<\Database\Factories\ServerFactory> */
    use HasFactory, SoftDeletes;

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
        'latitude',
        'longitude',
        'location_name',
        'last_ping_at',
        'provisioned_at',
        'provision_status',
        'installed_packages',
        'metadata',
    ];

    protected $hidden = [
        'ssh_key',
        'ssh_password',
    ];

    protected function casts(): array
    {
        return [
            'docker_installed' => 'boolean',
            'ufw_installed' => 'boolean',
            'ufw_enabled' => 'boolean',
            'fail2ban_installed' => 'boolean',
            'fail2ban_enabled' => 'boolean',
            'security_score' => 'integer',
            'last_security_scan_at' => 'datetime',
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
        ];
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
}
