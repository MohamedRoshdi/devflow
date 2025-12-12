<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerTag extends Model
{
    /** @use HasFactory<\Database\Factories\ServerTagFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];

    protected function casts(): array
    {
        return [];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function servers()
    {
        return $this->belongsToMany(Server::class, 'server_tag_pivot', 'tag_id', 'server_id');
    }
}
