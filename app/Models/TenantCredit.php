<?php

namespace App\Models;

class TenantCredit extends BaseModel
{
    protected $fillable = ['tenant_id', 'balance', 'total_purchased', 'total_used', 'last_recharged_at'];
    protected $casts = ['last_recharged_at' => 'datetime'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
