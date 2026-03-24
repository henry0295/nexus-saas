<?php

namespace App\Services;

use App\Models\PricingRule;
use App\Models\TenantPricingOverride;
use App\Models\Tenant;

class PricingService
{
    public function getSellingPrice(string $channel, Tenant $tenant = null): float
    {
        if ($tenant) {
            $override = TenantPricingOverride::where([
                ['tenant_id', $tenant->id],
                ['channel', $channel],
                ['effective_from', '<=', now()],
            ])
                ->where(function ($query) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', now());
                })
                ->first();

            if ($override) {
                return (float) $override->custom_price;
            }
        }

        $pricing = PricingRule::withoutGlobalScope('tenant')
            ->where(['channel' => $channel, 'is_active' => true])
            ->first();

        if (!$pricing) {
            throw new \Exception("No pricing rule found for channel: {$channel}");
        }

        return (float) $pricing->selling_price;
    }

    public function analyzePrice(string $channel): array
    {
        $rule = PricingRule::withoutGlobalScope('tenant')
            ->where(['channel' => $channel, 'is_active' => true])
            ->first();

        if (!$rule) {
            throw new \Exception("Pricing rule not found");
        }

        $profit = $rule->selling_price - $rule->cost_per_unit;
        $profitPercent = ($profit / $rule->selling_price) * 100;

        return [
            'channel'          => $channel,
            'aws_cost'         => (float) $rule->cost_per_unit,
            'margin_percent'   => (float) $rule->margin_percent,
            'selling_price'    => (float) $rule->selling_price,
            'profit_per_unit'  => $profit,
            'profit_percent'   => $profitPercent,
        ];
    }
}
