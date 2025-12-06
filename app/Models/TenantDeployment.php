<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $deployment_id
 * @property string $status
 * @property string|null $output
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Tenant $tenant
 * @property-read Deployment $deployment
 *
 * @method static \Illuminate\Database\Eloquent\Builder|TenantDeployment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TenantDeployment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TenantDeployment query()
 */
class TenantDeployment extends Model
{
    /** @use HasFactory<\Database\Factories\TenantDeploymentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'deployment_id',
        'status',
        'output',
    ];

    /**
     * Get the tenant that owns the deployment.
     *
     * @return BelongsTo<Tenant, TenantDeployment>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the deployment that owns the tenant deployment.
     *
     * @return BelongsTo<Deployment, TenantDeployment>
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
