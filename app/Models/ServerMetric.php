<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'cpu_usage',
        'memory_usage',
        'disk_usage',
        'network_in',
        'network_out',
        'load_average',
        'active_connections',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'cpu_usage' => 'decimal:2',
            'memory_usage' => 'decimal:2',
            'disk_usage' => 'decimal:2',
            'network_in' => 'integer',
            'network_out' => 'integer',
            'load_average' => 'decimal:2',
            'active_connections' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}

