<?php

namespace App\Models;

class Invoice extends BaseModel
{
    protected $fillable = ['tenant_id', 'invoice_number', 'amount', 'period_month', 'period_year', 'status', 'due_date', 'paid_at', 'line_items'];
    
    protected $casts = ['line_items' => 'array', 'due_date' => 'date', 'paid_at' => 'date'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
