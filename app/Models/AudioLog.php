<?php

namespace App\Models;

class AudioLog extends BaseModel
{
    protected $fillable = [
        'tenant_id', 'phone_number', 'message', 'gender', 'language', 'campaign', 
        'status', 'duration', 'response', 'provider_call_id', 'cost'
    ];
    
    protected $casts = ['response' => 'array'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
