<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SshConfiguration extends Model
{
    /** @use HasFactory<\Database\Factories\SshConfigurationFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'port',
        'root_login_enabled',
        'password_auth_enabled',
        'pubkey_auth_enabled',
        'max_auth_tries',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'root_login_enabled' => 'boolean',
            'password_auth_enabled' => 'boolean',
            'pubkey_auth_enabled' => 'boolean',
            'max_auth_tries' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function isHardened(): bool
    {
        return ! $this->root_login_enabled
            && ! $this->password_auth_enabled
            && $this->pubkey_auth_enabled
            && $this->port !== 22
            && $this->max_auth_tries <= 3;
    }

    public function getSecurityScoreAttribute(): int
    {
        $score = 0;

        if (! $this->root_login_enabled) {
            $score += 20;
        }

        if (! $this->password_auth_enabled) {
            $score += 20;
        }

        if ($this->pubkey_auth_enabled) {
            $score += 20;
        }

        if ($this->port !== 22) {
            $score += 20;
        }

        if ($this->max_auth_tries <= 3) {
            $score += 20;
        }

        return $score;
    }
}
