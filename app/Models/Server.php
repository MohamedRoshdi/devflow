<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Deployment, $this>
     */
    public function deployments()
    {
        return $this->hasMany(Deployment::class);
    }

    public function metrics()
    {
        return $this->hasMany(ServerMetric::class);
    }

    public function sshKeys()
    {
        return $this->belongsToMany(SSHKey::class, 'server_ssh_key')
            ->withPivot('deployed_at')
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(ServerTag::class, 'server_tag_pivot', 'server_id', 'tag_id');
    }

    public function sslCertificates()
    {
        return $this->hasMany(SSLCertificate::class);
    }

    public function resourceAlerts()
    {
        return $this->hasMany(ResourceAlert::class);
    }

    public function alertHistory()
    {
        return $this->hasMany(AlertHistory::class);
    }

    public function backups()
    {
        return $this->hasMany(ServerBackup::class);
    }

    public function backupSchedules()
    {
        return $this->hasMany(ServerBackupSchedule::class);
    }

    public function firewallRules()
    {
        return $this->hasMany(FirewallRule::class);
    }

    public function securityEvents()
    {
        return $this->hasMany(SecurityEvent::class);
    }

    public function sshConfiguration()
    {
        return $this->hasOne(SshConfiguration::class);
    }

    public function securityScans()
    {
        return $this->hasMany(SecurityScan::class);
    }

    public function latestSecurityScan()
    {
        return $this->hasOne(SecurityScan::class)->latestOfMany();
    }

    public function latestMetric()
    {
        return $this->hasOne(ServerMetric::class)->latestOfMany('recorded_at');
    }

    public function provisioningLogs()
    {
        return $this->hasMany(ProvisioningLog::class);
    }

    public function latestProvisioningLog()
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
        return match ($this->status) {
            'online' => 'green',
            'offline' => 'red',
            'maintenance' => 'yellow',
            default => 'gray',
        };
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
