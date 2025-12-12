<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SSHKey extends Model
{
    /** @use HasFactory<\Database\Factories\SSHKeyFactory> */
    use HasFactory;

    protected $table = 'ssh_keys';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'public_key',
        'private_key_encrypted',
        'fingerprint',
        'expires_at',
    ];

    protected $hidden = [
        'private_key_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function servers()
    {
        return $this->belongsToMany(Server::class, 'server_ssh_key')
            ->withPivot('deployed_at')
            ->withTimestamps();
    }

    // Accessors
    public function getMaskedPrivateKeyAttribute(): string
    {
        if (! $this->private_key_encrypted) {
            return '';
        }

        try {
            $decrypted = Crypt::decryptString($this->private_key_encrypted);
            $length = strlen($decrypted);

            if ($length <= 40) {
                return str_repeat('*', $length);
            }

            $start = substr($decrypted, 0, 20);
            $end = substr($decrypted, -20);
            $middle = str_repeat('*', min($length - 40, 50));

            return $start.$middle.$end;
        } catch (\Exception $e) {
            return '[Encrypted]';
        }
    }

    public function getDecryptedPrivateKeyAttribute(): ?string
    {
        if (! $this->private_key_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->private_key_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    // Mutators
    public function setPrivateKeyAttribute(string $value): void
    {
        $this->attributes['private_key_encrypted'] = Crypt::encryptString($value);
    }

    // Helper methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isDeployedToServer(Server $server): bool
    {
        return $this->servers()->where('server_id', $server->id)->exists();
    }
}
