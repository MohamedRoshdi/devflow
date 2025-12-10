<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpInteraction extends Model
{
    protected $fillable = [
        'user_id',
        'help_content_id',
        'interaction_type',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function helpContent(): BelongsTo
    {
        return $this->belongsTo(HelpContent::class);
    }
}
