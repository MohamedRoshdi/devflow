<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeploymentScript extends Model
{
    /** @use HasFactory<\Database\Factories\DeploymentScriptFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'language',
        'script',
        'variables',
        'run_as',
        'timeout',
        'is_template',
        'tags',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_template' => 'boolean',
        'tags' => 'array',
        'timeout' => 'integer',
    ];

    /**
     * @return HasMany<DeploymentScriptRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(DeploymentScriptRun::class);
    }

    public function getExecutableScriptAttribute()
    {
        $script = $this->script;

        if ($this->variables) {
            foreach ($this->variables as $key => $value) {
                // Direct replacement - keys should include the full placeholder (e.g., '{VAR1}')
                $script = str_replace($key, $value, $script);
            }
        }

        return $script;
    }
}
