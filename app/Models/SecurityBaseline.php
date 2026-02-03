<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property array<string, mixed> $running_services
 * @property array<string, mixed> $listening_ports
 * @property array<string, mixed> $system_users
 * @property array<string, mixed> $crontab_entries
 * @property array<string, mixed> $systemd_services
 * @property array<string, mixed>|null $installed_packages
 * @property float $avg_cpu_usage
 * @property float $avg_memory_usage
 * @property int $total_processes
 * @property array<string, mixed>|null $network_connections_summary
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Server $server
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 */
class SecurityBaseline extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'server_id',
        'running_services',
        'listening_ports',
        'system_users',
        'crontab_entries',
        'systemd_services',
        'installed_packages',
        'avg_cpu_usage',
        'avg_memory_usage',
        'total_processes',
        'network_connections_summary',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'running_services' => 'array',
            'listening_ports' => 'array',
            'system_users' => 'array',
            'crontab_entries' => 'array',
            'systemd_services' => 'array',
            'installed_packages' => 'array',
            'avg_cpu_usage' => 'float',
            'avg_memory_usage' => 'float',
            'total_processes' => 'integer',
            'network_connections_summary' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Server, self>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Compare this baseline with another and return differences
     *
     * @return array<string, array<string, mixed>>
     */
    public function diffWith(self $other): array
    {
        $diffs = [];

        $newServices = array_diff($other->running_services, $this->running_services);
        $removedServices = array_diff($this->running_services, $other->running_services);
        if (! empty($newServices) || ! empty($removedServices)) {
            $diffs['running_services'] = ['added' => array_values($newServices), 'removed' => array_values($removedServices)];
        }

        $newPorts = array_diff($other->listening_ports, $this->listening_ports);
        $removedPorts = array_diff($this->listening_ports, $other->listening_ports);
        if (! empty($newPorts) || ! empty($removedPorts)) {
            $diffs['listening_ports'] = ['added' => array_values($newPorts), 'removed' => array_values($removedPorts)];
        }

        $newUsers = array_diff($other->system_users, $this->system_users);
        $removedUsers = array_diff($this->system_users, $other->system_users);
        if (! empty($newUsers) || ! empty($removedUsers)) {
            $diffs['system_users'] = ['added' => array_values($newUsers), 'removed' => array_values($removedUsers)];
        }

        $newCrons = array_diff($other->crontab_entries, $this->crontab_entries);
        $removedCrons = array_diff($this->crontab_entries, $this->crontab_entries);
        if (! empty($newCrons) || ! empty($removedCrons)) {
            $diffs['crontab_entries'] = ['added' => array_values($newCrons), 'removed' => array_values($removedCrons)];
        }

        $cpuDelta = $other->avg_cpu_usage - $this->avg_cpu_usage;
        if (abs($cpuDelta) > 10.0) {
            $diffs['cpu_usage'] = ['baseline' => $this->avg_cpu_usage, 'current' => $other->avg_cpu_usage, 'delta' => round($cpuDelta, 2)];
        }

        $memDelta = $other->avg_memory_usage - $this->avg_memory_usage;
        if (abs($memDelta) > 10.0) {
            $diffs['memory_usage'] = ['baseline' => $this->avg_memory_usage, 'current' => $other->avg_memory_usage, 'delta' => round($memDelta, 2)];
        }

        return $diffs;
    }
}
