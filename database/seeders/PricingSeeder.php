<?php

namespace Database\Seeders;

use App\Models\PricingRule;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    public function run(): void
    {
        PricingRule::create([
            'channel' => 'sms',
            'provider' => 'aws',
            'cost_per_unit' => 0.02,
            'margin_percent' => 30,
            'selling_price' => 0.026,
            'is_active' => true,
        ]);

        PricingRule::create([
            'channel' => 'email',
            'provider' => 'aws',
            'cost_per_unit' => 0.0001,
            'margin_percent' => 900,
            'selling_price' => 0.001,
            'is_active' => true,
        ]);

        PricingRule::create([
            'channel' => 'audio',
            'provider' => '360nrs',
            'cost_per_unit' => 0.05,
            'margin_percent' => 40,
            'selling_price' => 0.07,
            'is_active' => true,
        ]);
    }
}
