<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostRate extends Model
{
    /** @use HasFactory<\Database\Factories\CostRateFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'resource_type',
        'rate_per_unit',
        'unit',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate_per_unit' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
