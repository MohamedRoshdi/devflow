<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerMetric extends Model
{
    /** @use HasFactory<\Database\Factories\ServerMetricFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'cpu_usage',
        'memory_usage',
        'memory_used_mb',
        'memory_total_mb',
        'disk_usage',
        'disk_used_gb',
        'disk_total_gb',
        'load_average_1',
        'load_average_5',
        'load_average_15',
        'network_in_bytes',
        'network_out_bytes',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'cpu_usage' => 'decimal:2',
            'memory_usage' => 'decimal:2',
            'memory_used_mb' => 'integer',
            'memory_total_mb' => 'integer',
            'disk_usage' => 'decimal:2',
            'disk_used_gb' => 'integer',
            'disk_total_gb' => 'integer',
            'load_average_1' => 'decimal:2',
            'load_average_5' => 'decimal:2',
            'load_average_15' => 'decimal:2',
            'network_in_bytes' => 'integer',
            'network_out_bytes' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
