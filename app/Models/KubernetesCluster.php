<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $api_server_url
 * @property string|null $kubeconfig
 * @property string|null $namespace
 * @property string|null $context
 * @property bool $is_active
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|KubernetesCluster newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KubernetesCluster newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KubernetesCluster query()
 */
class KubernetesCluster extends Model
{
    /** @use HasFactory<\Database\Factories\KubernetesClusterFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'api_server_url',
        'kubeconfig',
        'namespace',
        'context',
        'is_active',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /** @var array<int, string> */
    protected $hidden = [
        'kubeconfig', // Hide sensitive data
    ];

    /**
     * @param  string|null  $value
     * @return void
     */
    public function setKubeconfigAttribute($value)
    {
        $this->attributes['kubeconfig'] = $value ? encrypt($value) : null;
    }

    /**
     * @param  string|null  $value
     * @return string|null
     */
    public function getKubeconfigAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Get projects deployed to this cluster.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Project, $this>
     */
    public function projects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Project::class);
    }
}
