<?php

namespace App\Http\Controllers\Api;

use App\Models\CreditTransaction;
use App\Models\TenantCredit;
use Illuminate\Http\Request;

class CreditsController
{
    /**
     * Get available credit packages for purchase
     */
    public function packages()
    {
        $packages = [
            ['id' => 'starter', 'credits' => 500, 'price' => 5.00, 'discount' => 0],
            ['id' => 'growth', 'credits' => 2500, 'price' => 20.00, 'discount' => 0.10],  // 10% discount
            ['id' => 'professional', 'credits' => 10000, 'price' => 70.00, 'discount' => 0.15],  // 15% discount
            ['id' => 'enterprise', 'credits' => 50000, 'price' => 300.00, 'discount' => 0.20],  // 20% discount
        ];

        return response()->json(['packages' => $packages]);
    }

    /**
     * Purchase credits for current tenant
     * 
     * In real world, this would:
     * 1. Create a Stripe/PayU payment request
     * 2. Return redirect/payment_url
     * 3. Use webhook to confirm payment and add credits
     * 
     * For now, we'll simulate it with status 'pending'
     */
    public function purchase(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|in:starter,growth,professional,enterprise',
            'payment_method' => 'nullable|in:credit_card,paypal,bank_transfer',
        ]);

        $packages = [
            'starter' => ['credits' => 500, 'price' => 5.00],
            'growth' => ['credits' => 2500, 'price' => 20.00],
            'professional' => ['credits' => 10000, 'price' => 70.00],
            'enterprise' => ['credits' => 50000, 'price' => 300.00],
        ];

        $package = $packages[$validated['package_id']] ?? null;

        if (!$package) {
            return response()->json(['error' => 'Invalid package'], 400);
        }

        $tenant = $request->user()->tenant;
        $credits = $tenant->credits;

        // Create transaction record
        $transaction = CreditTransaction::create([
            'tenant_id' => $tenant->id,
            'type' => 'purchase',
            'amount' => $package['credits'],
            'price' => $package['price'],
            'status' => 'pending',
            'description' => "Purchase {$validated['package_id']} package ({$package['credits']} credits)",
            'reference' => 'txn_' . uniqid(),
            'metadata' => json_encode([
                'package_id' => $validated['package_id'],
                'payment_method' => $validated['payment_method'] ?? 'credit_card',
            ]),
        ]);

        // In real implementation, this would redirect to Stripe/PayU
        // For now, we simulate pending status
        
        return response()->json([
            'success' => true,
            'transaction_id' => $transaction->id,
            'status' => 'pending',
            'reference' => $transaction->reference,
            'message' => 'Purchase initiated. Please complete payment.',
            // In real world:
            // 'payment_url' => 'https://stripe.com/pay/xxx',
        ], 201);
    }

    /**
     * Get credit transactions for current tenant
     */
    public function transactions(Request $request)
    {
        $transactions = CreditTransaction::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate(20);

        return response()->json($transactions);
    }
}
