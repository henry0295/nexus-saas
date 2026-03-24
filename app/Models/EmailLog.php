<?php

namespace App\Models;

class EmailLog extends BaseModel
{
    protected $fillable = [
        'tenant_id', 'recipient', 'subject', 'body', 'status', 'response', 'aws_message_id', 'cost'
    ];
    
    protected $casts = ['response' => 'array'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
