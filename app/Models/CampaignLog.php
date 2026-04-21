<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'type', // 'email', 'sms', 'audio'
        'campaign_name',
        'recipients_count',
        'cost',
        'status', // 'pending', 'sent', 'failed'
    ];

    protected $casts = [
        'recipients_count' => 'integer',
        'cost' => 'float',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
