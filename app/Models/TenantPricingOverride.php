<?php

namespace App\Models;

class TenantPricingOverride extends BaseModel
{
    protected $fillable = ['tenant_id', 'channel', 'custom_price', 'reason', 'effective_from', 'effective_to'];
    
    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
