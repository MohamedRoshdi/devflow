<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Server extends Model
{
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
        'latitude',
        'longitude',
        'location_name',
        'last_ping_at',
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
            'last_ping_at' => 'datetime',
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
        return match($this->status) {
            'online' => 'green',
            'offline' => 'red',
            'maintenance' => 'yellow',
            default => 'gray',
        };
    }
}


