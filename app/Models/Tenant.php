<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

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

    protected $casts = [
        'custom_config' => 'array',
        'features' => 'array',
        'trial_ends_at' => 'datetime',
        'last_deployed_at' => 'datetime',
    ];

    protected $hidden = [
        'admin_password',
    ];

    public function setAdminPasswordAttribute($value)
    {
        $this->attributes['admin_password'] = $value ? encrypt($value) : null;
    }

    public function getAdminPasswordAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(TenantDeployment::class);
    }

    public function getUrlAttribute()
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