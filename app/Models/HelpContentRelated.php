<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpContentRelated extends Model
{
    /** @use HasFactory<\Database\Factories\HelpContentRelatedFactory> */
    use HasFactory;

    protected $table = 'help_content_related';

    protected $fillable = [
        'help_content_id',
        'related_help_content_id',
        'relevance_score',
    ];

    protected $casts = [
        'relevance_score' => 'integer',
    ];

    /**
     * @return BelongsTo<HelpContent, $this>
     */
    public function helpContent(): BelongsTo
    {
        return $this->belongsTo(HelpContent::class);
    }

    /**
     * @return BelongsTo<HelpContent, $this>
     */
    public function relatedHelpContent(): BelongsTo
    {
        return $this->belongsTo(HelpContent::class, 'related_help_content_id');
    }
}
