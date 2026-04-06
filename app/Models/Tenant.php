<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'status',
        'plan',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function credits()
    {
        return $this->hasOne(TenantCredit::class);
    }

    public function integrations()
    {
        return $this->hasOne(TenantIntegration::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class);
    }

    public function audioLogs()
    {
        return $this->hasMany(AudioLog::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function pricingOverrides()
    {
        return $this->hasMany(TenantPricingOverride::class);
    }

    public function hasCredit(float $amount): bool
    {
        return $this->credits->balance >= $amount;
    }

    public function deductCredit(float $amount, string $description = null, string $referenceId = null): CreditTransaction
    {
        $transaction = CreditTransaction::create([
            'tenant_id' => $this->id,
            'type' => 'usage',
            'amount' => -$amount,
            'description' => $description,
            'reference_id' => $referenceId,
        ]);

        $this->credits->decrement('balance', $amount);
        $this->credits->increment('total_used', $amount);

        return $transaction;
    }

    public function addCredit(float $amount, string $description = null): CreditTransaction
    {
        $transaction = CreditTransaction::create([
            'tenant_id' => $this->id,
            'type' => 'purchase',
            'amount' => $amount,
            'description' => $description,
        ]);

        $this->credits->increment('balance', $amount);
        $this->credits->increment('total_purchased', $amount);
        $this->credits->update(['last_recharged_at' => now()]);

        return $transaction;
    }
}
