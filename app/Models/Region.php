<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RegionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    /** @use HasFactory<\Database\Factories\RegionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'continent',
        'latitude',
        'longitude',
        'status',
        'dns_zone',
        'failover_config',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegionStatus::class,
            'failover_config' => 'array',
            'metadata' => 'array',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    /**
     * @return HasMany<Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * @return HasMany<RegionDeployment, $this>
     */
    public function regionDeployments(): HasMany
    {
        return $this->hasMany(RegionDeployment::class);
    }

    /**
     * @return HasMany<DnsRoutingRule, $this>
     */
    public function dnsRoutingRules(): HasMany
    {
        return $this->hasMany(DnsRoutingRule::class);
    }

    public function isActive(): bool
    {
        return $this->status === RegionStatus::Active;
    }

    public function getServerCount(): int
    {
        return $this->servers()->count();
    }
}
