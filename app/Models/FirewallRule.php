<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirewallRule extends Model
{
    /** @use HasFactory<\Database\Factories\FirewallRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'action',
        'direction',
        'protocol',
        'port',
        'from_ip',
        'to_ip',
        'description',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = [];

        if ($this->port) {
            $parts[] = $this->port.'/'.$this->protocol;
        }

        if ($this->from_ip) {
            $parts[] = 'from '.$this->from_ip;
        }

        return implode(' ', $parts) ?: 'Any';
    }

    public function toUfwCommand(): string
    {
        $cmd = 'ufw '.$this->action;

        if ($this->direction === 'out') {
            $cmd .= ' out';
        }

        if ($this->from_ip) {
            $cmd .= ' from '.$this->from_ip;
        }

        if ($this->port) {
            $cmd .= ' to any port '.$this->port;
        }

        if ($this->protocol !== 'any') {
            $cmd .= ' proto '.$this->protocol;
        }

        return $cmd;
    }
}
