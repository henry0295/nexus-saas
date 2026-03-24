<?php

namespace App\Models;

class PricingRule extends BaseModel
{
    public $timestamps = true;
    protected $fillable = [
        'channel', 'provider', 'cost_per_unit', 'margin_percent', 'selling_price', 'is_active', 'updated_by_admin'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'cost_per_unit' => 'decimal:6',
        'selling_price' => 'decimal:6',
    ];

    protected static function boot()
    {
        parent::boot();
        // NO aplicar global scope de tenant aquí - son precios globales
    }

    public function updatedByAdmin()
    {
        return $this->belongsTo(User::class, 'updated_by_admin');
    }
}
