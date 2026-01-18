<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpContentTranslation extends Model
{
    /** @use HasFactory<\Database\Factories\HelpContentTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'help_content_id',
        'locale',
        'brief',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    /**
     * @return BelongsTo<HelpContent, $this>
     */
    public function helpContent(): BelongsTo
    {
        return $this->belongsTo(HelpContent::class);
    }
}
