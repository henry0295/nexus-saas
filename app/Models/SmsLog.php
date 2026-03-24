<?php

namespace App\Models;

class SmsLog extends BaseModel
{
    protected $fillable = [
        'tenant_id', 'recipient', 'message', 'campaign', 'parts', 'status', 'response', 'aws_message_id', 'cost'
    ];
    
    protected $casts = ['response' => 'array'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
