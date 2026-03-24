<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('tenant', function ($query) {
            $user = auth()->user();
            
            if ($user && $user->tenant_id && $user->role !== 'superadmin') {
                $query->where('tenant_id', $user->tenant_id);
            }
        });
    }

    public static function withoutTenantScope()
    {
        return static::withoutGlobalScope('tenant');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
