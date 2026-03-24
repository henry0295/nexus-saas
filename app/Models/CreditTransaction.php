<?php

namespace App\Models;

class CreditTransaction extends BaseModel
{
    protected $fillable = ['tenant_id', 'type', 'amount', 'description', 'reference_id'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
