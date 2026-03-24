<?php

namespace App\Models;

class AuditLog extends BaseModel
{
    protected $fillable = ['admin_id', 'action', 'tenant_id', 'old_data', 'new_data', 'ip_address'];
    
    protected $casts = ['old_data' => 'array', 'new_data' => 'array'];

    protected static function boot()
    {
        parent::boot();
        // NO aplicar tenant scope - auditoría global
        static::withoutGlobalScope('tenant');
    }

    public function admin()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
