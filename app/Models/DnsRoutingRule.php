<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoutingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsRoutingRule extends Model
{
    /** @use HasFactory<\Database\Factories\DnsRoutingRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'region_id',
        'routing_type',
        'weight',
        'priority',
        'geo_restrictions',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'routing_type' => RoutingType::class,
            'geo_restrictions' => 'array',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'weight' => 'integer',
            'priority' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
