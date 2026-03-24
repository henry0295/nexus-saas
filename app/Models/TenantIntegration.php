<?php

namespace App\Models;

class TenantIntegration extends BaseModel
{
    protected $fillable = [
        'tenant_id', 'ses_verified_domain', 'ses_dkim_tokens', 'ses_sending_limit', 'ses_verified',
        'sns_alias', 'audio_api_key'
    ];
    
    protected $casts = [
        'ses_dkim_tokens' => 'array',
        'ses_verified' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
