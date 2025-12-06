<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @use HasFactory<TenantFactory>
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string $subdomain
 * @property string $database
 * @property string $admin_email
 * @property string|null $admin_password
 * @property string $plan
 * @property string $status
 * @property array<string, mixed>|null $custom_config
 * @property array<string, mixed>|null $features
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property \Illuminate\Support\Carbon|null $last_deployed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $url
 * @property-read Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TenantDeployment> $deployments
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'project_id',
        'name',
        'subdomain',
        'database',
        'admin_email',
        'admin_password',
        'plan',
        'status',
        'custom_config',
        'features',
        'trial_ends_at',
        'last_deployed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'custom_config' => 'array',
        'features' => 'array',
        'trial_ends_at' => 'datetime',
        'last_deployed_at' => 'datetime',
    ];

    /** @var array<int, string> */
    protected $hidden = [
        'admin_password',
    ];

    /**
     * @param  string|null  $value
     */
    public function setAdminPasswordAttribute($value): void
    {
        $this->attributes['admin_password'] = $value ? encrypt($value) : null;
    }

    /**
     * @param  string|null  $value
     */
    public function getAdminPasswordAttribute($value): ?string
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * @return BelongsTo<Project, Tenant>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<TenantDeployment, $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(TenantDeployment::class);
    }

    public function getUrlAttribute(): ?string
    {
        $project = $this->project;
        $domain = $project->domains()->where('is_primary', true)->first();

        if ($domain) {
            return "https://{$this->subdomain}.{$domain->domain}";
        }

        return null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }
}
