<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SSLCertificate extends Model
{
    use HasFactory;

    protected $table = 'ssl_certificates';

    protected $fillable = [
        'server_id',
        'domain_id',
        'domain_name',
        'provider',
        'status',
        'certificate_path',
        'private_key_path',
        'chain_path',
        'issued_at',
        'expires_at',
        'auto_renew',
        'last_renewal_attempt',
        'renewal_error',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_renewal_attempt' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    // Relationships
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    // Helper Methods
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        return $this->expires_at->diffInDays(now()) <= $days;
    }

    public function needsRenewal(int $days = 30): bool
    {
        if (!$this->auto_renew) {
            return false;
        }

        if ($this->status === 'revoked') {
            return false;
        }

        return $this->isExpired() || $this->isExpiringSoon($days);
    }

    public function daysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        if ($this->isExpired()) {
            return 0;
        }

        return (int) $this->expires_at->diffInDays(now());
    }

    public function getStatusBadgeClass(): string
    {
        if ($this->isExpired()) {
            return 'bg-red-500/20 text-red-400 border-red-500/30';
        }

        if ($this->isExpiringSoon(30)) {
            return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
        }

        return match($this->status) {
            'issued' => 'bg-green-500/20 text-green-400 border-green-500/30',
            'pending' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
            'failed' => 'bg-red-500/20 text-red-400 border-red-500/30',
            'revoked' => 'bg-gray-500/20 text-gray-400 border-gray-500/30',
            default => 'bg-gray-500/20 text-gray-400 border-gray-500/30',
        };
    }

    public function getStatusLabel(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        if ($this->isExpiringSoon(30)) {
            $days = $this->daysUntilExpiry();
            return "Expiring ({$days}d)";
        }

        return match($this->status) {
            'issued' => 'Active',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'revoked' => 'Revoked',
            'expired' => 'Expired',
            default => ucfirst($this->status),
        };
    }
}
