<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KubernetesCluster extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_server_url',
        'kubeconfig',
        'namespace',
        'context',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'kubeconfig', // Hide sensitive data
    ];

    public function setKubeconfigAttribute($value)
    {
        $this->attributes['kubeconfig'] = $value ? encrypt($value) : null;
    }

    public function getKubeconfigAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }
}