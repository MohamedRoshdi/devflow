<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ThreatCategory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $category
 * @property string $signature_type
 * @property string $pattern
 * @property string $severity
 * @property string|null $description
 * @property string|null $remediation_hint
 * @property bool $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 * @method static \Illuminate\Database\Eloquent\Builder<static> enabled()
 * @method static \Illuminate\Database\Eloquent\Builder<static> forCategory(string $category)
 */
class KnownThreatSignature extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'category',
        'signature_type',
        'pattern',
        'severity',
        'description',
        'remediation_hint',
        'enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function getCategoryEnum(): ?ThreatCategory
    {
        return ThreatCategory::tryFrom($this->category);
    }
}
